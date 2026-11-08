<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;

/**
 * Resumo de um período do módulo de estudos, para as mídias do Instagram (estudos:resumo).
 *
 * Espelha as regras de resources/js/estudos/metrics.js:
 * - conta só sessões com status 'ok' e sem deleted_at; sessão em andamento vai até "agora";
 * - o "dia de estudo" vira em settings.virada_hora, no fuso settings.timezone;
 * - sequência = dias consecutivos com estudo; o último dia sem estudo ainda não quebra a de ontem.
 */
class StudySummaryService
{
    public const TYPE_NAMES = [
        'teoria' => 'Teoria / leitura',
        'questoes' => 'Questões',
        'revisao' => 'Revisão',
        'lei_seca' => 'Lei seca',
        'videoaula' => 'Videoaula',
        'resumo' => 'Resumo / mapas',
        'simulado' => 'Simulado',
        'outro' => 'Outro',
        'sem_tipo' => 'Sem tipo',
    ];

    protected array $settings;

    protected array $subjects = [];

    /** @var array<int, array{start: int, end: int, subject_id: string, tipo: string, origem: string}> instantes em ms UTC */
    protected array $sessions = [];

    protected int $nowMs;

    public function __construct(protected ConnectionInterface $db, protected int $userId, ?CarbonImmutable $now = null)
    {
        $this->nowMs = ($now ?? CarbonImmutable::now('UTC'))->getTimestampMs();
        $row = (array) ($db->table('study_settings')->where('user_id', $userId)->first() ?? []);
        $this->settings = [
            'virada_hora' => (int) ($row['virada_hora'] ?? 1),
            'timezone' => $row['timezone'] ?? 'America/Sao_Paulo',
            'alvo_horas' => isset($row['alvo_horas']) ? (int) $row['alvo_horas'] : null,
        ];
        foreach ($db->table('study_subjects')->where('user_id', $userId)->get() as $s) {
            $this->subjects[$s->id] = ['nome' => $s->nome, 'cor' => $s->cor];
        }
        $rows = $db->table('study_sessions')->where('user_id', $userId)->whereNull('deleted_at')->where('status', 'ok')->get();
        foreach ($rows as $s) {
            $start = $this->parseUtc($s->inicio);
            $end = $s->fim ? $this->parseUtc($s->fim) : $this->nowMs;
            if ($end > $start) {
                $this->sessions[] = ['start' => $start, 'end' => $end, 'subject_id' => $s->subject_id, 'tipo' => $s->tipo ?: 'sem_tipo', 'origem' => $s->origem];
            }
        }
    }

    public function timezone(): string
    {
        return $this->settings['timezone'];
    }

    /** "2026-11-02" → [início, fim) do dia de estudo em ms UTC. */
    public function dayBounds(string $day): array
    {
        $start = CarbonImmutable::parse($day . ' 00:00:00', $this->timezone())->addHours($this->settings['virada_hora']);
        $end = CarbonImmutable::parse($day . ' 00:00:00', $this->timezone())->addDay()->addHours($this->settings['virada_hora']);
        return [$start->getTimestampMs(), $end->getTimestampMs()];
    }

    /** Dia de estudo (Y-m-d) a que o instante pertence. */
    public function studyDayOf(int $ms): string
    {
        return CarbonImmutable::createFromTimestampMs($ms, 'UTC')->setTimezone($this->timezone())
            ->subHours($this->settings['virada_hora'])->format('Y-m-d');
    }

    /** Instante "local" (no fuso do usuário, ex.: data da Guruja) → ms UTC. */
    public function localToMs(string $local): int
    {
        return CarbonImmutable::parse($local, $this->timezone())->getTimestampMs();
    }

    /**
     * Resumo do intervalo [fromMs, toMs). `previous` é o intervalo de comparação (padrão: mesmo tamanho, logo antes).
     */
    public function summarize(int $fromMs, int $toMs, ?array $previous = null): array
    {
        $toMs = min($toMs, $this->nowMs);
        [$prevFrom, $prevTo] = $previous ?? [$fromMs - ($toMs - $fromMs), $fromMs];

        $bySubject = [];
        $byType = [];
        $daily = [];
        $maxFocus = 0;
        $total = 0;
        foreach ($this->pieces($fromMs, $toMs) as $p) {
            $ms = $p['end'] - $p['start'];
            $total += $ms;
            $bySubject[$p['subject_id']] = ($bySubject[$p['subject_id']] ?? 0) + $ms;
            $byType[$p['tipo']] = ($byType[$p['tipo']] ?? 0) + $ms;
            $daily[$p['day']] = ($daily[$p['day']] ?? 0) + $ms;
            if ($p['origem'] === 'timer') {
                $maxFocus = max($maxFocus, $ms);
            }
        }
        arsort($bySubject);
        arsort($byType);

        $firstDay = $this->studyDayOf($fromMs);
        $lastDay = $this->studyDayOf($toMs - 1);
        $elapsedDays = CarbonImmutable::parse($firstDay)->diffInDays(CarbonImmutable::parse($lastDay)) + 1;
        $historyMs = array_sum(array_map(fn ($s) => min($s['end'], $this->nowMs) - $s['start'], $this->sessions));

        $minutes = fn (int $ms) => (int) round($ms / 60_000);
        return [
            'periodo' => [
                'inicio' => $this->iso($fromMs),
                'fim' => $this->iso($toMs),
                'primeiro_dia' => $firstDay,
                'ultimo_dia' => $lastDay,
                'dias' => $elapsedDays,
            ],
            'total_min' => $minutes($total),
            'anterior_total_min' => $minutes($this->amountBetween($prevFrom, $prevTo)),
            'dias_estudados' => count(array_filter($daily)),
            'media_dia_min' => $minutes((int) ($total / max(1, $elapsedDays))),
            'maior_foco_min' => $minutes($maxFocus),
            'melhor_dia' => $daily ? ['dia' => array_search(max($daily), $daily, true), 'min' => $minutes(max($daily))] : null,
            'por_dia' => array_map($minutes, $this->fillDays($daily, $firstDay, $lastDay)),
            'por_materia' => array_values(array_map(fn ($id, $ms) => [
                'nome' => $this->subjects[$id]['nome'] ?? 'Removida',
                'cor' => $this->subjects[$id]['cor'] ?? '#888888',
                'min' => $minutes($ms),
            ], array_keys($bySubject), $bySubject)),
            'por_tipo' => array_values(array_map(fn ($tipo, $ms) => [
                'tipo' => $tipo,
                // Tipos "x:Nome" vêm de históricos importados de outros apps: mostra só o nome.
                'nome' => self::TYPE_NAMES[$tipo] ?? (str_starts_with($tipo, 'x:') ? substr($tipo, 2) : $tipo),
                'min' => $minutes($ms),
            ], array_keys($byType), $byType)),
            'sequencia_dias' => $this->streak($lastDay),
            'total_historico_min' => $minutes($historyMs),
            'alvo_horas' => $this->settings['alvo_horas'],
            'ddays' => $this->ddays($lastDay),
            'timezone' => $this->timezone(),
        ];
    }

    /** Pedaços das sessões cortados nas viradas de dia e no intervalo pedido. */
    protected function pieces(int $fromMs, int $toMs): array
    {
        $pieces = [];
        foreach ($this->sessions as $s) {
            $cursor = max($s['start'], $fromMs);
            $end = min($s['end'], $toMs);
            while ($cursor < $end) {
                $day = $this->studyDayOf($cursor);
                $pieceEnd = min($end, $this->dayBounds($day)[1]);
                $pieces[] = ['day' => $day, 'start' => $cursor, 'end' => $pieceEnd] + $s;
                $cursor = $pieceEnd;
            }
        }
        return $pieces;
    }

    protected function amountBetween(int $fromMs, int $toMs): int
    {
        $total = 0;
        foreach ($this->sessions as $s) {
            $total += max(0, min($s['end'], $toMs) - max($s['start'], $fromMs));
        }
        return $total;
    }

    protected function fillDays(array $daily, string $from, string $to): array
    {
        $out = [];
        for ($d = CarbonImmutable::parse($from); $d->format('Y-m-d') <= $to; $d = $d->addDay()) {
            $out[$d->format('Y-m-d')] = $daily[$d->format('Y-m-d')] ?? 0;
        }
        return $out;
    }

    protected function streak(string $lastDay): int
    {
        $days = [];
        foreach ($this->pieces(PHP_INT_MIN, $this->nowMs) as $p) {
            $days[$p['day']] = true;
        }
        $day = CarbonImmutable::parse($lastDay);
        if (!isset($days[$day->format('Y-m-d')])) {
            $day = $day->subDay();
        }
        $count = 0;
        while (isset($days[$day->format('Y-m-d')])) {
            $count++;
            $day = $day->subDay();
        }
        return $count;
    }

    protected function ddays(string $refDay): array
    {
        return $this->db->table('study_ddays')->where('user_id', $this->userId)->whereNull('deleted_at')
            ->orderBy('data')->get()
            ->map(fn ($d) => ['titulo' => $d->titulo, 'data' => substr((string) $d->data, 0, 10)])
            ->filter(fn ($d) => $d['data'] >= $refDay)
            ->map(fn ($d) => $d + ['dias' => (int) CarbonImmutable::parse($refDay)->diffInDays(CarbonImmutable::parse($d['data']))])
            ->values()->all();
    }

    protected function parseUtc(string $value): int
    {
        return CarbonImmutable::parse($value, 'UTC')->getTimestampMs();
    }

    protected function iso(int $ms): string
    {
        return CarbonImmutable::createFromTimestampMs($ms, 'UTC')->setTimezone($this->timezone())->format('Y-m-d\TH:i:sP');
    }
}
