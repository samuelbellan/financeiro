<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Sincronização offline-first do módulo de estudos.
 *
 * Regras (ver docs/estudos-ypt-plano.md):
 * - IDs são UUIDs gerados no cliente.
 * - updated_at é carimbado pelo servidor no recebimento: vence quem sincronizar por último.
 * - Exclusão é lógica e definitiva: uma alteração recebida para um registro já excluído é ignorada.
 * - O cursor é o relógio do servidor, com margem para cobrir requisições concorrentes.
 * - Sessões com origem 'importado' (histórico de outro app) não entram na checagem de sobreposição.
 */
class StudySyncService
{
    /** Margem (segundos) subtraída do cursor devolvido; registros nessa janela são reenviados (idempotente). */
    public const CURSOR_MARGIN_SECONDS = 5;

    /** Tipos de estudo aceitos (mesma lista de STUDY_TYPES em resources/js/estudos/metrics.js). */
    public const SESSION_TYPES = ['teoria', 'questoes', 'revisao', 'lei_seca', 'videoaula', 'resumo', 'simulado', 'outro'];

    /** Tipo criado pela pessoa: "x:" + nome (até 40 caracteres, sem < >). */
    public const CUSTOM_TYPE_REGEX = '/^x:[^<>\x00-\x1F]{1,40}$/u';

    /** Duração máxima aceita para uma sessão. */
    public const MAX_SESSION_HOURS = 24;

    protected const DB_FORMAT = 'Y-m-d H:i:s.v';

    /** Registros por consulta em lote (12 colunas × 500 fica bem abaixo do limite de parâmetros do Postgres). */
    protected const CHUNK = 500;

    protected const TABLES = [
        'subjects' => 'study_subjects',
        'sessions' => 'study_sessions',
        'ddays' => 'study_ddays',
    ];

    protected int $userId;

    /** @var array<int, array{type: string, id: ?string, motivo: string}> */
    protected array $rejected = [];

    /** @var array<string, array<int, string>> registros que devem voltar ao cliente mesmo fora do cursor */
    protected array $forced = ['subjects' => [], 'sessions' => [], 'ddays' => []];

    /**
     * @param array{cursor?: ?string, device_id?: ?string, changes?: array} $payload
     */
    public function sync(int $userId, array $payload): array
    {
        $this->userId = $userId;
        $this->rejected = [];
        $this->forced = ['subjects' => [], 'sessions' => [], 'ddays' => []];

        $requestStart = Carbon::now('UTC');
        $cursorIn = !empty($payload['cursor']) ? Carbon::parse($payload['cursor'])->utc() : null;
        $changes = $payload['changes'] ?? [];
        $conflicts = [];

        // Cada tipo é gravado em lote (poucas consultas por requisição, não por item): o banco fica em
        // outra rede e uma importação grande com consultas item a item estourava o tempo do Render.
        DB::transaction(function () use ($changes, $payload, &$conflicts) {
            $this->applySubjects($changes['subjects'] ?? []);
            $touchedSessions = $this->applySessions($changes['sessions'] ?? [], $payload['device_id'] ?? null);
            $this->applyDdays($changes['ddays'] ?? []);
            if (!empty($changes['settings'])) {
                $this->applySettings($changes['settings']);
            }
            $conflicts = $this->detectOverlaps($touchedSessions);
        });

        return [
            'cursor' => $requestStart->copy()->subSeconds(self::CURSOR_MARGIN_SECONDS)->format('Y-m-d\TH:i:s.v\Z'),
            'server_time' => $requestStart->format('Y-m-d\TH:i:s.v\Z'),
            'changes' => $this->collectChanges($cursorIn),
            'conflicts' => $conflicts,
            'rejected' => $this->rejected,
        ];
    }

    protected function applySubjects(array $items): void
    {
        $records = [];
        foreach ($items as $item) {
            $data = $this->validateItem('subjects', $item, [
                'id' => 'required|uuid',
                'nome' => 'required|string|min:1|max:60',
                'cor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'ordem' => 'nullable|integer|min:0|max:100000',
                'arquivada_em' => 'nullable|date',
                'deleted_at' => 'nullable|date',
            ]);
            if (!$data) {
                continue;
            }
            $records[$data['id']] = [[
                'nome' => $data['nome'],
                'cor' => strtoupper($data['cor']),
                'ordem' => $data['ordem'] ?? 0,
                'arquivada_em' => $this->toDb($data['arquivada_em'] ?? null),
            ], !empty($data['deleted_at'])];
        }

        $this->writeMany('subjects', $records);
    }

    /**
     * @return array<int, string> sessões ativas gravadas que entram na checagem de sobreposição
     */
    protected function applySessions(array $items, ?string $deviceId): array
    {
        $valid = [];
        foreach ($items as $item) {
            $data = $this->validateItem('sessions', $item, [
                'id' => 'required|uuid',
                'subject_id' => 'required|uuid',
                'inicio' => 'required|date',
                'fim' => 'nullable|date|after_or_equal:inicio',
                'origem' => 'required|in:timer,manual,importado',
                'status' => 'required|in:ok,suspeita',
                'observacao' => 'nullable|string|max:1000',
                'tipo' => ['nullable', 'string', 'max:60', function (string $attribute, mixed $value, \Closure $fail) {
                    if (!in_array($value, self::SESSION_TYPES, true) && !preg_match(self::CUSTOM_TYPE_REGEX, (string) $value)) {
                        $fail('Tipo de estudo inválido.');
                    }
                }],
                'device_id' => 'nullable|uuid',
                'deleted_at' => 'nullable|date',
            ]);
            if (!$data) {
                continue;
            }

            $inicio = Carbon::parse($data['inicio'])->utc();
            $fim = !empty($data['fim']) ? Carbon::parse($data['fim'])->utc() : null;
            if ($inicio->gt(Carbon::now('UTC')->addDay())) {
                $this->reject('sessions', $data['id'], 'Início no futuro.');
                continue;
            }
            if ($fim && $inicio->diffInHours($fim) > self::MAX_SESSION_HOURS) {
                $this->reject('sessions', $data['id'], 'Sessão com mais de ' . self::MAX_SESSION_HOURS . 'h.');
                continue;
            }
            $valid[$data['id']] = [$data, $inicio, $fim];
        }

        $subjectIds = array_unique(array_map(fn ($v) => $v[0]['subject_id'], $valid));
        $ownSubjects = [];
        foreach (array_chunk($subjectIds, self::CHUNK) as $chunk) {
            $ownSubjects += array_flip(DB::table('study_subjects')
                ->where('user_id', $this->userId)
                ->whereIn('id', $chunk)
                ->pluck('id')
                ->all());
        }

        $records = [];
        $overlapCandidates = [];
        foreach ($valid as $id => [$data, $inicio, $fim]) {
            if (!isset($ownSubjects[$data['subject_id']])) {
                $this->reject('sessions', $id, 'Matéria inexistente.');
                continue;
            }
            $deleted = !empty($data['deleted_at']);
            $records[$id] = [[
                'subject_id' => $data['subject_id'],
                'inicio' => $inicio->format(self::DB_FORMAT),
                'fim' => $fim?->format(self::DB_FORMAT),
                'origem' => $data['origem'],
                'status' => $data['status'],
                'observacao' => $data['observacao'] ?? null,
                'tipo' => $data['tipo'] ?? null,
                'device_id' => $data['device_id'] ?? $deviceId,
            ], $deleted];
            // Sessões importadas têm horários fictícios: não geram conflito.
            if (!$deleted && $data['origem'] !== 'importado') {
                $overlapCandidates[] = $id;
            }
        }

        $written = $this->writeMany('sessions', $records);

        return array_values(array_intersect($overlapCandidates, $written));
    }

    protected function applyDdays(array $items): void
    {
        $records = [];
        foreach ($items as $item) {
            $data = $this->validateItem('ddays', $item, [
                'id' => 'required|uuid',
                'titulo' => 'required|string|min:1|max:80',
                'data' => 'required|date_format:Y-m-d',
                'deleted_at' => 'nullable|date',
            ]);
            if (!$data) {
                continue;
            }
            $records[$data['id']] = [[
                'titulo' => $data['titulo'],
                'data' => $data['data'],
            ], !empty($data['deleted_at'])];
        }

        $this->writeMany('ddays', $records);
    }

    protected function applySettings(mixed $item): void
    {
        $data = $this->validateItem('settings', $item, [
            'virada_hora' => 'required|integer|min:0|max:12',
            'limite_suspeita_min' => 'required|integer|min:30|max:1440',
            'meta_diaria_min' => 'nullable|integer|min:0|max:1440',
            'timezone' => 'required|timezone:all',
            'avatar' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/'],
            'retro_sound' => 'nullable|boolean',
            'descanso_min' => 'nullable|integer|min:1|max:240',
            'tema' => 'nullable|in:auto,claro,escuro,floresta,lavanda,areia,contraste',
            'jornada' => 'nullable|array:inicio,objetivo,minutos',
            'jornada.inicio' => 'required_with:jornada|date_format:Y-m-d',
            'jornada.objetivo' => ['required_with:jornada', 'string', 'max:60', 'regex:/^(total|tipo:([a-z_]+|x:[^<>\x00-\x1F]{1,40})|materia:[0-9a-f-]{36})$/u'],
            'jornada.minutos' => 'required_with:jornada|integer|min:10|max:1440',
        ]);
        if (!$data) {
            return;
        }

        DB::table('study_settings')->updateOrInsert(
            ['user_id' => $this->userId],
            [
                'virada_hora' => $data['virada_hora'],
                'limite_suspeita_min' => $data['limite_suspeita_min'],
                'meta_diaria_min' => $data['meta_diaria_min'] ?? null,
                'timezone' => $data['timezone'],
                'avatar' => $data['avatar'] ?? 'bichinho',
                'retro_sound' => (bool) ($data['retro_sound'] ?? false),
                'descanso_min' => $data['descanso_min'] ?? null,
                'tema' => $data['tema'] ?? 'auto',
                'jornada' => empty($data['jornada']) ? null : json_encode([
                    'inicio' => $data['jornada']['inicio'],
                    'objetivo' => $data['jornada']['objetivo'],
                    'minutos' => (int) $data['jornada']['minutos'],
                ]),
                'updated_at' => $this->stamp(),
            ]
        );
    }

    /**
     * Grava em lote (upsert) carimbando updated_at. Registros de outro usuário são recusados e
     * registros já excluídos não são alterados (voltam ao cliente como tombstone).
     *
     * @param array<string, array{0: array, 1: bool}> $records id => [valores, excluir?]
     * @return array<int, string> ids gravados
     */
    protected function writeMany(string $type, array $records): array
    {
        if (!$records) {
            return [];
        }

        $table = self::TABLES[$type];
        $existing = [];
        foreach (array_chunk(array_keys($records), self::CHUNK) as $chunk) {
            foreach (DB::table($table)->whereIn('id', $chunk)->get(['id', 'user_id', 'deleted_at']) as $row) {
                $existing[$row->id] = $row;
            }
        }

        $stamp = $this->stamp();
        $rows = [];
        foreach ($records as $id => [$values, $delete]) {
            $current = $existing[$id] ?? null;
            if ($current && (int) $current->user_id !== $this->userId) {
                $this->reject($type, $id, 'Registro pertence a outro usuário.');
                continue;
            }
            if ($current && $current->deleted_at !== null) {
                $this->forced[$type][] = $id;
                continue;
            }
            $rows[] = $values + [
                'id' => $id,
                'user_id' => $this->userId,
                'created_at' => $stamp,
                'updated_at' => $stamp,
                'deleted_at' => $delete ? $stamp : null,
            ];
        }
        if (!$rows) {
            return [];
        }

        // Na atualização, id, dono e created_at ficam como estão.
        $update = array_values(array_diff(array_keys($rows[0]), ['id', 'user_id', 'created_at']));
        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::table($table)->upsert($chunk, ['id'], $update);
        }

        return array_column($rows, 'id');
    }

    protected function validateItem(string $type, mixed $item, array $rules): ?array
    {
        if (!is_array($item)) {
            $this->reject($type, null, 'Formato inválido.');
            return null;
        }
        $validator = Validator::make($item, $rules);
        if ($validator->fails()) {
            $this->reject($type, is_string($item['id'] ?? null) ? $item['id'] : null, $validator->errors()->first());
            return null;
        }
        return $validator->validated();
    }

    protected function reject(string $type, ?string $id, string $motivo): null
    {
        $this->rejected[] = ['type' => $type, 'id' => $id, 'motivo' => $motivo];
        return null;
    }

    /**
     * Sessões recebidas que se sobrepõem a outras sessões ativas do usuário.
     *
     * @param array<int, string> $sessionIds
     * @return array<int, array{session_id: string, overlaps_with: string}>
     */
    protected function detectOverlaps(array $sessionIds): array
    {
        $conflicts = [];
        if (!$sessionIds) {
            return $conflicts;
        }
        $now = Carbon::now('UTC')->format(self::DB_FORMAT);

        // Só sessões de cronômetro/manuais chegam aqui (poucas por sincronização): uma consulta por sessão.
        $sessions = DB::table('study_sessions')->whereIn('id', array_unique($sessionIds))->get()->keyBy('id');
        foreach (array_unique($sessionIds) as $id) {
            if (!$session = $sessions->get($id)) {
                continue;
            }
            $end = $session->fim ?? $now;
            $others = DB::table('study_sessions')
                ->where('user_id', $this->userId)
                ->where('id', '!=', $id)
                ->where('origem', '!=', 'importado')
                ->whereNull('deleted_at')
                ->where('inicio', '<', $end)
                ->where(fn ($q) => $q->whereNull('fim')->orWhere('fim', '>', $session->inicio))
                ->pluck('id');
            foreach ($others as $otherId) {
                $conflicts[] = ['session_id' => $id, 'overlaps_with' => $otherId];
            }
        }

        return $conflicts;
    }

    protected function collectChanges(?Carbon $cursorIn): array
    {
        $result = [];
        foreach (self::TABLES as $type => $tableName) {
            $query = DB::table($tableName)->where('user_id', $this->userId);
            if ($cursorIn) {
                $forced = $this->forced[$type];
                $query->where(function ($q) use ($cursorIn, $forced) {
                    $q->where('updated_at', '>', $cursorIn->format(self::DB_FORMAT));
                    if ($forced) {
                        $q->orWhereIn('id', $forced);
                    }
                });
            } else {
                $query->whereNull('deleted_at');
            }
            $result[$type] = $query->get()->map(fn ($row) => $this->present($type, (array) $row))->values()->all();
        }

        $settings = DB::table('study_settings')->where('user_id', $this->userId)->first();
        $result['settings'] = ($settings && (!$cursorIn || Carbon::parse($settings->updated_at, 'UTC')->gt($cursorIn)))
            ? $this->present('settings', (array) $settings)
            : null;

        return $result;
    }

    protected function present(string $type, array $row): array
    {
        unset($row['user_id']);
        foreach (['inicio', 'fim', 'arquivada_em', 'created_at', 'updated_at', 'deleted_at'] as $col) {
            if (array_key_exists($col, $row)) {
                $row[$col] = $row[$col] === null ? null : Carbon::parse($row[$col], 'UTC')->format('Y-m-d\TH:i:s.v\Z');
            }
        }
        foreach (['ordem', 'virada_hora', 'limite_suspeita_min', 'meta_diaria_min', 'descanso_min'] as $col) {
            if (isset($row[$col])) {
                $row[$col] = (int) $row[$col];
            }
        }
        if (isset($row['retro_sound'])) {
            $row['retro_sound'] = (bool) $row['retro_sound'];
        }
        if (array_key_exists('jornada', $row)) {
            $row['jornada'] = is_string($row['jornada']) ? json_decode($row['jornada'], true) : null;
        }
        if ($type === 'ddays' && isset($row['data'])) {
            $row['data'] = substr((string) $row['data'], 0, 10);
        }
        return $row;
    }

    protected function toDb(?string $value): ?string
    {
        return $value ? Carbon::parse($value)->utc()->format(self::DB_FORMAT) : null;
    }

    protected function stamp(): string
    {
        return Carbon::now('UTC')->format(self::DB_FORMAT);
    }
}
