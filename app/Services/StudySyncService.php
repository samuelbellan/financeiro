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

    /** Duração máxima aceita para uma sessão. */
    public const MAX_SESSION_HOURS = 24;

    protected const DB_FORMAT = 'Y-m-d H:i:s.v';

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

        DB::transaction(function () use ($changes, $payload, &$conflicts) {
            foreach ($changes['subjects'] ?? [] as $item) {
                $this->applySubject($item);
            }
            $touchedSessions = [];
            foreach ($changes['sessions'] ?? [] as $item) {
                if ($id = $this->applySession($item, $payload['device_id'] ?? null)) {
                    $touchedSessions[] = $id;
                }
            }
            foreach ($changes['ddays'] ?? [] as $item) {
                $this->applyDday($item);
            }
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

    protected function applySubject(mixed $item): void
    {
        $data = $this->validateItem('subjects', $item, [
            'id' => 'required|uuid',
            'nome' => 'required|string|min:1|max:60',
            'cor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'ordem' => 'nullable|integer|min:0|max:100000',
            'arquivada_em' => 'nullable|date',
            'deleted_at' => 'nullable|date',
        ]);
        if (!$data || !$this->guardOwnership('subjects', $data['id'])) {
            return;
        }

        $this->write('subjects', $data['id'], [
            'nome' => $data['nome'],
            'cor' => strtoupper($data['cor']),
            'ordem' => $data['ordem'] ?? 0,
            'arquivada_em' => $this->toDb($data['arquivada_em'] ?? null),
        ], !empty($data['deleted_at']));
    }

    protected function applySession(mixed $item, ?string $deviceId): ?string
    {
        $data = $this->validateItem('sessions', $item, [
            'id' => 'required|uuid',
            'subject_id' => 'required|uuid',
            'inicio' => 'required|date',
            'fim' => 'nullable|date|after_or_equal:inicio',
            'origem' => 'required|in:timer,manual,importado',
            'status' => 'required|in:ok,suspeita',
            'device_id' => 'nullable|uuid',
            'deleted_at' => 'nullable|date',
        ]);
        if (!$data || !$this->guardOwnership('sessions', $data['id'])) {
            return null;
        }

        $inicio = Carbon::parse($data['inicio'])->utc();
        $fim = !empty($data['fim']) ? Carbon::parse($data['fim'])->utc() : null;

        if ($inicio->gt(Carbon::now('UTC')->addDay())) {
            return $this->reject('sessions', $data['id'], 'Início no futuro.');
        }
        if ($fim && $inicio->diffInHours($fim) > self::MAX_SESSION_HOURS) {
            return $this->reject('sessions', $data['id'], 'Sessão com mais de ' . self::MAX_SESSION_HOURS . 'h.');
        }
        $subjectOk = DB::table('study_subjects')
            ->where('id', $data['subject_id'])
            ->where('user_id', $this->userId)
            ->exists();
        if (!$subjectOk) {
            return $this->reject('sessions', $data['id'], 'Matéria inexistente.');
        }

        $deleted = !empty($data['deleted_at']);
        $written = $this->write('sessions', $data['id'], [
            'subject_id' => $data['subject_id'],
            'inicio' => $inicio->format(self::DB_FORMAT),
            'fim' => $fim?->format(self::DB_FORMAT),
            'origem' => $data['origem'],
            'status' => $data['status'],
            'device_id' => $data['device_id'] ?? $deviceId,
        ], $deleted);

        return ($written && !$deleted) ? $data['id'] : null;
    }

    protected function applyDday(mixed $item): void
    {
        $data = $this->validateItem('ddays', $item, [
            'id' => 'required|uuid',
            'titulo' => 'required|string|min:1|max:80',
            'data' => 'required|date_format:Y-m-d',
            'deleted_at' => 'nullable|date',
        ]);
        if (!$data || !$this->guardOwnership('ddays', $data['id'])) {
            return;
        }

        $this->write('ddays', $data['id'], [
            'titulo' => $data['titulo'],
            'data' => $data['data'],
        ], !empty($data['deleted_at']));
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
                'updated_at' => $this->stamp(),
            ]
        );
    }

    /**
     * Grava (insert/update) carimbando updated_at. Registros já excluídos não são alterados.
     */
    protected function write(string $type, string $id, array $values, bool $delete): bool
    {
        $table = DB::table(self::TABLES[$type]);
        $existing = (clone $table)->where('id', $id)->first();
        $stamp = $this->stamp();

        if ($existing && $existing->deleted_at !== null) {
            $this->forced[$type][] = $id;
            return false;
        }

        $values['updated_at'] = $stamp;
        if ($delete) {
            $values['deleted_at'] = $stamp;
        }

        if ($existing) {
            (clone $table)->where('id', $id)->update($values);
        } else {
            (clone $table)->insert($values + ['id' => $id, 'user_id' => $this->userId, 'created_at' => $stamp]);
        }

        return true;
    }

    protected function guardOwnership(string $type, string $id): bool
    {
        $owner = DB::table(self::TABLES[$type])->where('id', $id)->value('user_id');
        if ($owner !== null && (int) $owner !== $this->userId) {
            $this->reject($type, $id, 'Registro pertence a outro usuário.');
            return false;
        }
        return true;
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
        $now = Carbon::now('UTC')->format(self::DB_FORMAT);

        foreach (array_unique($sessionIds) as $id) {
            $session = DB::table('study_sessions')->where('id', $id)->first();
            // Sessões importadas têm horários fictícios: não geram conflito.
            if (!$session || $session->origem === 'importado') {
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
        foreach (['ordem', 'virada_hora', 'limite_suspeita_min', 'meta_diaria_min'] as $col) {
            if (isset($row[$col])) {
                $row[$col] = (int) $row[$col];
            }
        }
        if (isset($row['retro_sound'])) {
            $row['retro_sound'] = (bool) $row['retro_sound'];
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
