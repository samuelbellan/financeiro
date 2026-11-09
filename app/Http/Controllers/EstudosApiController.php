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

        $settings = DB::table('study_settings')->where('user_id', $userId)->first();
        $timezone = $settings->timezone ?? self::DEFAULT_TIMEZONE;
        $virada = (int) ($settings->virada_hora ?? self::DEFAULT_VIRADA);
        $now = CarbonImmutable::now('UTC');

        $day = $request->query('data')
            ?? $now->setTimezone($timezone)->subHours($virada)->format('Y-m-d');
        $start = CarbonImmutable::parse($day . ' 00:00:00', $timezone)->addHours($virada)->utc();
        $end = CarbonImmutable::parse($day . ' 00:00:00', $timezone)->addDay()->addHours($virada)->utc();

        $subjects = DB::table('study_subjects')->where('user_id', $userId)->get()->keyBy('id');
        $rows = DB::table('study_sessions')
            ->where('user_id', $userId)
            ->where('inicio', '<', $end->format('Y-m-d H:i:s.v'))
            // Sem fim: em andamento vai até agora; excluída vai até a exclusão (não aparece nos dias seguintes).
            ->where(fn ($q) => $q->where('fim', '>', $start->format('Y-m-d H:i:s.v'))
                ->orWhere(fn ($q) => $q->whereNull('fim')->where(fn ($q) => $q->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>', $start->format('Y-m-d H:i:s.v')))))
            ->orderBy('inicio')
            ->get();

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
        $meta = $settings->meta_diaria_min ?? null;

        return response()->json([
            'data' => $day,
            'fuso' => $timezone,
            'virada_hora' => $virada,
            'periodo' => ['inicio' => $this->iso($start), 'fim' => $this->iso($end)],
            'gerado_em' => $this->iso($now),
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
            // Apagadas (não contam em nada): só para diagnóstico. Sem fim, o tempo vai até a exclusão.
            'sessoes_excluidas' => $deleted,
        ]);
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
