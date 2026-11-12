<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * API somente leitura do módulo de estudos, autenticada por token pessoal (Authorization: Bearer ...).
 * Os tokens são gerados e revogados pelo próprio usuário no app (rotas web com login).
 */
class EstudosApiController extends Controller
{
    /** Mesmos padrões do cliente (metrics.js) para quem ainda não salvou preferências. */
    protected const DEFAULT_TIMEZONE = 'America/Sao_Paulo';
    protected const DEFAULT_VIRADA = 1;

    /** Período máximo de /periodo, em dias. */
    protected const MAX_PERIOD_DAYS = 31;

    /**
     * Todos os dados de um dia de estudo: sessões (inclusive excluídas, para diagnóstico) e totais
     * calculados como no app — o dia vai da virada (hora local) até a virada seguinte, e sessões que
     * cruzam a virada contam só a parte dentro do dia.
     */
    public function dia(Request $request): JsonResponse
    {
        $userId = $this->authenticate($request);
        if (!$userId) {
            return response()->json(['message' => 'Token ausente ou inválido.'], 401);
        }
        $request->validate(['data' => 'nullable|date_format:Y-m-d']);

        $ctx = $this->context($userId);
        $day = $request->query('data') ?? $this->today($ctx);
        [$start, $end] = $this->bounds($day, $ctx);
        $rows = $this->sessionRows($userId, $start, $end);
        $subjects = DB::table('study_subjects')->where('user_id', $userId)->get()->keyBy('id');
        $data = $this->buildDay($day, $rows, $subjects, $ctx);

        return response()->json([
            'data' => $day,
            'fuso' => $ctx['timezone'],
            'virada_hora' => $ctx['virada'],
            'periodo' => ['inicio' => $this->iso($start), 'fim' => $this->iso($end)],
            'gerado_em' => $this->iso($ctx['now']),
            'totais' => $data['totais'],
            'por_materia' => $data['por_materia'],
            'por_tipo' => $data['por_tipo'],
            'sessoes' => $data['sessoes'],
            // Apagadas (não contam em nada): só para diagnóstico. Sem fim, o tempo vai até a exclusão.
            'sessoes_excluidas' => $data['sessoes_excluidas'],
        ]);
    }

    /**
     * Vários dias de estudo (até MAX_PERIOD_DAYS): uma linha por dia (inclusive os sem estudo) e o resumo
     * do período. Com sessoes=1, cada dia traz também as sessões ativas e as excluídas.
     */
    public function periodo(Request $request): JsonResponse
    {
        $userId = $this->authenticate($request);
        if (!$userId) {
            return response()->json(['message' => 'Token ausente ou inválido.'], 401);
        }
        // Data (AAAA-MM-DD) = dia de estudo inteiro; data e hora (AAAA-MM-DDTHH:MM, opcionalmente com
        // segundos e fuso, ex. -03:00) = corte exato. Sem fuso, a hora é a do fuso do app.
        $moment = 'regex:/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}(:\d{2})?(Z|[+-]\d{2}:?\d{2})?)?$/';
        $request->validate([
            'desde' => ['required', 'string', $moment],
            'ate' => ['required', 'string', $moment],
            'sessoes' => 'nullable|boolean',
        ]);

        $ctx = $this->context($userId);
        try {
            [$windowStart] = $this->moment($request->query('desde'), $ctx);
            [, $windowEnd] = $this->moment($request->query('ate'), $ctx);
        } catch (\Throwable) {
            return response()->json(['message' => 'Data ou hora inválida.', 'errors' => ['desde' => ['Data ou hora inválida.']]], 422);
        }
        if ($windowEnd->lte($windowStart)) {
            return response()->json(['message' => '`ate` precisa ser depois de `desde`.', 'errors' => ['ate' => ['`ate` precisa ser depois de `desde`.']]], 422);
        }
        $first = CarbonImmutable::parse($this->studyDayOf($windowStart, $ctx));
        $last = CarbonImmutable::parse($this->studyDayOf($windowEnd->subMillisecond(), $ctx));
        $count = $first->diffInDays($last) + 1;
        if ($count > self::MAX_PERIOD_DAYS) {
            return response()->json([
                'message' => 'Período máximo: ' . self::MAX_PERIOD_DAYS . ' dias.',
                'errors' => ['ate' => ['Período máximo: ' . self::MAX_PERIOD_DAYS . ' dias.']],
            ], 422);
        }
        $withSessions = $request->boolean('sessoes');

        $rows = $this->sessionRows($userId, $windowStart, $windowEnd);
        $subjects = DB::table('study_subjects')->where('user_id', $userId)->get()->keyBy('id');

        $days = [];
        $sum = ['total_min' => 0, 'cronometro_min' => 0, 'manual_min' => 0, 'importado_min' => 0];
        $bySubject = [];
        $byType = [];
        $studied = 0;
        $metaHits = 0;
        $maxFocus = 0;
        $elapsed = 0;
        $today = $this->today($ctx);
        for ($i = 0; $i < $count; $i++) {
            $day = $first->addDays($i)->format('Y-m-d');
            [$dayStart, $dayEnd] = $this->bounds($day, $ctx);
            $partial = $windowStart->gt($dayStart) || $windowEnd->lt($dayEnd);
            $dayStart = $dayStart->max($windowStart);
            $dayEnd = $dayEnd->min($windowEnd);
            $dayRows = $rows->filter(fn ($row) => $this->overlaps($row, $dayStart, $dayEnd));
            $data = $this->buildDay($day, $dayRows, $subjects, $ctx, $dayStart, $dayEnd);
            $t = $data['totais'];

            foreach ($sum as $key => $value) {
                $sum[$key] = $value + $t[$key];
            }
            foreach ($data['por_materia'] as $name => $minutes) {
                $bySubject[$name] = ($bySubject[$name] ?? 0) + $minutes;
            }
            foreach ($data['por_tipo'] as $type => $minutes) {
                $byType[$type] = ($byType[$type] ?? 0) + $minutes;
            }
            $studied += $t['total_min'] > 0 ? 1 : 0;
            $metaHits += $t['meta_atingida'] ? 1 : 0;
            $maxFocus = max($maxFocus, $t['max_foco_min']);
            $elapsed += $day <= $today ? 1 : 0;

            $line = ['data' => $day, 'dia_semana' => $this->weekday($day), 'dia_parcial' => $partial] + $t;
            if ($partial) {
                $line['recorte_local'] = [
                    'inicio' => $dayStart->setTimezone($ctx['timezone'])->format('Y-m-d H:i'),
                    'fim' => $dayEnd->setTimezone($ctx['timezone'])->format('Y-m-d H:i'),
                ];
            }
            if ($withSessions) {
                $line['sessoes'] = $data['sessoes'];
                $line['sessoes_excluidas'] = $data['sessoes_excluidas'];
            }
            $days[] = $line;
        }

        arsort($bySubject);
        arsort($byType);
        $round = fn (array $values) => array_map(fn ($v) => round($v, 1), $values);

        return response()->json([
            'desde' => $first->format('Y-m-d'),
            'ate' => $last->format('Y-m-d'),
            'janela' => [
                'inicio' => $this->iso($windowStart),
                'fim' => $this->iso($windowEnd),
                'inicio_local' => $windowStart->setTimezone($ctx['timezone'])->format('Y-m-d H:i'),
                'fim_local' => $windowEnd->setTimezone($ctx['timezone'])->format('Y-m-d H:i'),
            ],
            'fuso' => $ctx['timezone'],
            'virada_hora' => $ctx['virada'],
            'gerado_em' => $this->iso($ctx['now']),
            'resumo' => $round($sum) + [
                'total_horas' => $this->hours($sum['total_min']),
                'dias' => $count,
                'dias_decorridos' => $elapsed,
                'dias_estudados' => $studied,
                'dias_sem_estudo' => $elapsed - $studied,
                'media_por_dia_min' => $elapsed ? round($sum['total_min'] / $elapsed, 1) : 0,
                'media_por_dia_estudado_min' => $studied ? round($sum['total_min'] / $studied, 1) : 0,
                'meta_diaria_min' => $ctx['meta'],
                'dias_meta_atingida' => $ctx['meta'] ? $metaHits : null,
                'max_foco_min' => $maxFocus,
            ],
            'por_materia' => $round($bySubject),
            'por_tipo' => $round($byType),
            'dias' => $days,
        ]);
    }

    /** @return array{timezone: string, virada: int, meta: ?int, now: CarbonImmutable} */
    protected function context(int $userId): array
    {
        $settings = DB::table('study_settings')->where('user_id', $userId)->first();

        return [
            'timezone' => $settings->timezone ?? self::DEFAULT_TIMEZONE,
            'virada' => (int) ($settings->virada_hora ?? self::DEFAULT_VIRADA),
            'meta' => isset($settings->meta_diaria_min) ? (int) $settings->meta_diaria_min : null,
            'now' => CarbonImmutable::now('UTC'),
        ];
    }

    protected function today(array $ctx): string
    {
        return $ctx['now']->setTimezone($ctx['timezone'])->subHours($ctx['virada'])->format('Y-m-d');
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} início e fim (UTC) do dia de estudo */
    protected function bounds(string $day, array $ctx): array
    {
        $midnight = CarbonImmutable::parse($day . ' 00:00:00', $ctx['timezone']);

        return [$midnight->addHours($ctx['virada'])->utc(), $midnight->addDay()->addHours($ctx['virada'])->utc()];
    }

    protected function sessionRows(int $userId, CarbonImmutable $start, CarbonImmutable $end)
    {
        $from = $start->format('Y-m-d H:i:s.v');

        return DB::table('study_sessions')
            ->where('user_id', $userId)
            ->where('inicio', '<', $end->format('Y-m-d H:i:s.v'))
            // Sem fim: em andamento vai até agora; excluída vai até a exclusão (não aparece nos dias seguintes).
            ->where(fn ($q) => $q->where('fim', '>', $from)
                ->orWhere(fn ($q) => $q->whereNull('fim')->where(fn ($q) => $q->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>', $from))))
            ->orderBy('inicio')
            ->get();
    }

    protected function overlaps(object $row, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        $inicio = CarbonImmutable::parse($row->inicio, 'UTC');
        $until = $row->fim ?? $row->deleted_at;
        $until = $until ? CarbonImmutable::parse($until, 'UTC') : null;

        return $inicio->lt($end) && ($until === null || $until->gt($start));
    }

    /**
     * Interpreta `desde`/`ate`: data = dia de estudo inteiro; data e hora = instante exato.
     * @return array{0: CarbonImmutable, 1: CarbonImmutable} início e fim (UTC) do trecho pedido
     */
    protected function moment(string $value, array $ctx): array
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $this->bounds(CarbonImmutable::createFromFormat('!Y-m-d', $value)->format('Y-m-d'), $ctx);
        }
        $at = CarbonImmutable::parse($value, $ctx['timezone'])->utc();

        return [$at, $at];
    }

    protected function studyDayOf(CarbonImmutable $at, array $ctx): string
    {
        return $at->setTimezone($ctx['timezone'])->subHours($ctx['virada'])->format('Y-m-d');
    }

    protected function buildDay(string $day, $rows, $subjects, array $ctx, ?CarbonImmutable $clipStart = null, ?CarbonImmutable $clipEnd = null): array
    {
        [$start, $end] = $this->bounds($day, $ctx);
        $start = $clipStart ? $start->max($clipStart) : $start;
        $end = $clipEnd ? $end->min($clipEnd) : $end;
        $timezone = $ctx['timezone'];
        $now = $ctx['now'];
        $sessions = [];
        $totals = ['total_min' => 0, 'cronometro_min' => 0, 'manual_min' => 0, 'importado_min' => 0, 'max_foco_min' => 0];
        $bySubject = [];
        $byType = [];
        $timerStart = null;
        $timerEnd = null;

        foreach ($rows as $row) {
            $inicio = CarbonImmutable::parse($row->inicio, 'UTC');
            $fim = $row->fim ? CarbonImmutable::parse($row->fim, 'UTC') : null;
            $pieceStart = $inicio->max($start);
            $deletedAt = $row->deleted_at ? CarbonImmutable::parse($row->deleted_at, 'UTC') : null;
            $pieceEnd = ($fim ?? $deletedAt ?? $now)->min($end);
            $minutes = max(0, round(($pieceEnd->getTimestampMs() - $pieceStart->getTimestampMs()) / 60000, 1));
            $subject = $subjects->get($row->subject_id);
            $countable = $row->deleted_at === null && $row->status === 'ok';

            $sessions[] = [
                'id' => $row->id,
                'excluida' => $deletedAt !== null,
                'materia' => $subject->nome ?? null,
                'materia_id' => $row->subject_id,
                'inicio' => $this->iso($inicio),
                'fim' => $fim ? $this->iso($fim) : null,
                'inicio_local' => $inicio->setTimezone($timezone)->format('Y-m-d H:i'),
                'fim_local' => $fim?->setTimezone($timezone)->format('Y-m-d H:i'),
                'minutos_no_dia' => $minutes,
                'tipo' => $row->tipo ?? null,
                'origem' => $row->origem,
                'status' => $row->status,
                'em_andamento' => $fim === null && $row->deleted_at === null,
                'observacao' => $row->observacao ?? null,
                'conta_no_total' => $countable,
                'excluida_em' => $deletedAt ? $this->iso($deletedAt) : null,
                'criada_em' => $row->created_at ? $this->iso(CarbonImmutable::parse($row->created_at, 'UTC')) : null,
                'atualizada_em' => $row->updated_at ? $this->iso(CarbonImmutable::parse($row->updated_at, 'UTC')) : null,
            ];

            if (!$countable) {
                continue;
            }
            $totals['total_min'] += $minutes;
            $origemKey = ['timer' => 'cronometro_min', 'manual' => 'manual_min', 'importado' => 'importado_min'][$row->origem] ?? null;
            if ($origemKey) {
                $totals[$origemKey] += $minutes;
            }
            $name = $subject->nome ?? 'Matéria removida';
            $bySubject[$name] = ($bySubject[$name] ?? 0) + $minutes;
            $type = $row->tipo ?? 'sem_tipo';
            $byType[$type] = ($byType[$type] ?? 0) + $minutes;
            if ($row->origem === 'timer') {
                $totals['max_foco_min'] = max($totals['max_foco_min'], $minutes);
                $timerStart = $timerStart ? $timerStart->min($pieceStart) : $pieceStart;
                $timerEnd = $timerEnd ? $timerEnd->max($pieceEnd) : $pieceEnd;
            }
        }

        $active = array_values(array_filter($sessions, fn ($s) => !$s['excluida']));
        $deleted = array_values(array_filter($sessions, fn ($s) => $s['excluida']));
        arsort($bySubject);
        arsort($byType);
        $round = fn (array $values) => array_map(fn ($v) => round($v, 1), $values);
        $meta = $ctx['meta'];

        return [
            'totais' => $round($totals) + [
                'total_horas' => $this->hours($totals['total_min']),
                'meta_diaria_min' => $meta,
                'meta_atingida' => $meta ? $totals['total_min'] >= $meta : null,
                'primeiro_inicio_local' => $timerStart?->setTimezone($timezone)->format('H:i'),
                'ultimo_fim_local' => $timerEnd?->setTimezone($timezone)->format('H:i'),
                'sessoes_ativas' => count($active),
                'sessoes_excluidas' => count($deleted),
            ],
            'por_materia' => $round($bySubject),
            'por_tipo' => $round($byType),
            'sessoes' => $active,
            'sessoes_excluidas' => $deleted,
        ];
    }

    protected function weekday(string $day): string
    {
        return ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'][(int) CarbonImmutable::parse($day)->format('w')];
    }

    /** Gera um token novo (mostrado uma única vez). Rota web, com login. */
    public function createToken(Request $request): JsonResponse
    {
        $data = $request->validate(['nome' => 'nullable|string|max:60']);
        $plain = 'est_' . Str::random(48);
        DB::table('study_api_tokens')->insert([
            'user_id' => Auth::id(),
            'nome' => $data['nome'] ?? 'Token da API',
            'token_hash' => hash('sha256', $plain),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['token' => $plain, 'tokens' => $this->tokenList()], 201);
    }

    public function listTokens(): JsonResponse
    {
        return response()->json(['tokens' => $this->tokenList()]);
    }

    /** Revoga todos os tokens do usuário. */
    public function revokeTokens(): JsonResponse
    {
        DB::table('study_api_tokens')->where('user_id', Auth::id())->delete();

        return response()->json(['tokens' => []]);
    }

    protected function tokenList(): array
    {
        return DB::table('study_api_tokens')
            ->where('user_id', Auth::id())
            ->orderByDesc('id')
            ->get(['id', 'nome', 'created_at', 'last_used_at'])
            ->map(fn ($t) => (array) $t)
            ->all();
    }

    /**
     * Recebe a foto da meta atual da Guruja (enviada por uma rotina que lê a plataforma) e substitui a
     * anterior. Única rota de escrita da API: não toca nas sessões de estudo.
     */
    public function gurujaStore(Request $request): JsonResponse
    {
        $userId = $this->authenticate($request);
        if (!$userId) {
            return response()->json(['message' => 'Token ausente ou inválido.'], 401);
        }
        $data = $request->validate([
            'lido_em' => 'nullable|date',
            'meta' => 'required|array',
            'meta.target_id' => 'required|integer',
            'meta.ordem' => 'nullable|integer',
            'meta.status' => 'nullable|string|max:30',
            'meta.data_inicio' => 'nullable|string|max:40',
            'meta.dias_atraso' => 'nullable|integer',
            'meta.questoes_resolvidas' => 'nullable|integer',
            'meta.acertos' => 'nullable|integer',
            'meta.minutos_estudados' => 'nullable|integer',
            'meta.atividades' => 'required|array|max:200',
            'meta.atividades.*.target_activity_id' => 'nullable|integer',
            'meta.atividades.*.codigo_atividade' => 'nullable|string|max:40',
            'meta.atividades.*.titulo' => 'required|string|max:300',
            'meta.atividades.*.disciplina' => 'nullable|string|max:150',
            'meta.atividades.*.tipo' => 'nullable|string|max:60',
            'meta.atividades.*.concluida' => 'required|boolean',
            'meta.atividades.*.ignorada' => 'nullable|boolean',
            'meta.atividades.*.questoes' => 'nullable|integer',
            'meta.atividades.*.acertos' => 'nullable|integer',
            'meta.atividades.*.minutos' => 'nullable|integer',
            'meta.atividades.*.relevancia' => 'nullable|integer',
            'meta.reforcos' => 'nullable|array|max:100',
            'meta.prazo' => 'nullable|date_format:Y-m-d',
            'meta.estimativas' => 'nullable|array|max:30',
            'meta.estimativas.*' => 'integer|min:1|max:600',
            'meta.reforcos.*.target_activity_id' => 'nullable|integer',
            'meta.reforcos.*.instrucao' => 'nullable|string|max:2000',
            'meta.reforcos.*.titulo' => 'required|string|max:300',
            'meta.reforcos.*.disciplina' => 'nullable|string|max:150',
            'meta.reforcos.*.concluido' => 'required|boolean',
            'meta.reforcos.*.ignorado' => 'nullable|boolean',
            'meta.reforcos.*.questoes_resolvidas' => 'nullable|integer',
            'meta.reforcos.*.acertos' => 'nullable|integer',
            'meta.reforcos.*.minutos' => 'nullable|integer',
        ]);

        $lidoEm = isset($data['lido_em']) ? Carbon::parse($data['lido_em']) : now();
        // Estimativas de tempo por tipo são recalculadas só de vez em quando: sem elas no envio, mantém as anteriores.
        if (!isset($data['meta']['estimativas'])) {
            $anterior = DB::table('study_guruja_metas')->where('user_id', $userId)->value('payload');
            $estimativas = $anterior ? (json_decode($anterior, true)['estimativas'] ?? null) : null;
            if ($estimativas) {
                $data['meta']['estimativas'] = $estimativas;
            }
        }
        DB::table('study_guruja_metas')->updateOrInsert(
            ['user_id' => $userId],
            ['payload' => json_encode($data['meta'], JSON_UNESCAPED_UNICODE), 'lido_em' => $lidoEm, 'updated_at' => now(), 'created_at' => now()],
        );

        return response()->json(['ok' => true, 'lido_em' => $lidoEm->utc()->format('Y-m-d\TH:i:s\Z')]);
    }

    protected function authenticate(Request $request): ?int
    {
        $plain = $request->bearerToken();
        if (!$plain) {
            return null;
        }
        $token = DB::table('study_api_tokens')->where('token_hash', hash('sha256', $plain))->first();
        if (!$token) {
            return null;
        }
        DB::table('study_api_tokens')->where('id', $token->id)->update(['last_used_at' => now()]);

        return (int) $token->user_id;
    }

    protected function iso(CarbonImmutable $at): string
    {
        return $at->utc()->format('Y-m-d\TH:i:s.v\Z');
    }

    protected function hours(float $minutes): string
    {
        $total = (int) round($minutes);

        return intdiv($total, 60) . 'h' . str_pad((string) ($total % 60), 2, '0', STR_PAD_LEFT);
    }
}
