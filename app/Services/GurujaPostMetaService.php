<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Monta e cacheia os dados do carrossel (post semanal) de uma meta da Guruja,
 * combinando histórico/detalhe da Guruja (via MCP) com as sessões locais do app.
 */
class GurujaPostMetaService
{
    public const DEFAULT_TIMEZONE = 'America/Cuiaba';
    public const DEFAULT_VIRADA = 1;
    public const AVISO_FINALIZAR_NO_PORTAL = 'Finalize a meta no portal da Guruja para ela aparecer como concluída';

    public function __construct(
        protected GurujaMcpService $mcpService
    ) {}

    public function buildForUser(int $userId, ?int $ordem = null): ?array
    {
        if ($ordem !== null) {
            $cached = $this->getCachedPayload($userId, $ordem);
            if ($cached !== null) {
                $cached['cached'] = true;

                return $cached;
            }
        }

        $result = $this->mcpService->fetchMetaForPost(
            $userId,
            $ordem,
            fn (int $targetOrdem) => $this->getCachedPayload($userId, $targetOrdem)
        );

        if (empty($result['found'])) {
            return null;
        }

        $ctx = $this->context($userId);
        $metasDisponiveis = $this->buildAvailableMetas(
            $userId,
            $result['completed_metas'] ?? [],
            $result['active_meta'] ?? null,
            $ctx
        );
        $historico = [
            'metas_concluidas' => (int) ($result['metas_concluidas'] ?? 0),
            'metas_adiadas' => (int) ($result['metas_adiadas'] ?? 0),
        ];

        if (!empty($result['cached']) && is_array($result['cached_payload'] ?? null)) {
            $payload = $result['cached_payload'];
            $payload['cached'] = true;
            $payload['metas_disponiveis'] = $metasDisponiveis;
            $payload['historico'] = $historico;
            $payload['aviso'] = null;

            return $payload;
        }

        $meta = $result['meta'];
        $isCompleted = (bool) ($result['concluida'] ?? false);
        $payload = $this->assemblePayload($userId, $meta, $isCompleted, $metasDisponiveis, $historico, $ctx);

        if ($isCompleted && isset($payload['meta']['ordem']) && $payload['meta']['ordem'] !== null) {
            $this->saveCachedPayload($userId, (int) $payload['meta']['ordem'], $payload);
        }

        return $payload;
    }

    protected function getCachedPayload(int $userId, int $ordem): ?array
    {
        $row = DB::table('study_guruja_posts')
            ->where('user_id', $userId)
            ->where('ordem', $ordem)
            ->first();

        if (!$row || empty($row->payload)) {
            return null;
        }

        $decoded = json_decode($row->payload, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function saveCachedPayload(int $userId, int $ordem, array $payload): void
    {
        $toStore = $payload;
        $toStore['cached'] = false;

        DB::table('study_guruja_posts')->updateOrInsert(
            ['user_id' => $userId, 'ordem' => $ordem],
            [
                'payload' => json_encode($toStore, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Lista metas concluídas (mais recente primeiro). Se a meta em andamento estiver 100% feita
     * (segundo o resumo do histórico ou o snapshot local), também a inclui para exibir o aviso.
     */
    protected function buildAvailableMetas(int $userId, array $completedMetas, ?array $activeMeta, array $ctx): array
    {
        $list = [];

        if ($activeMeta && isset($activeMeta['ordem']) && $this->isActiveMeta100PercentDone($userId, $activeMeta)) {
            $list[] = [
                'ordem' => (int) $activeMeta['ordem'],
                'target_id' => (int) ($activeMeta['target_id'] ?? $activeMeta['id'] ?? 0),
                'status' => (string) ($activeMeta['status'] ?? 'em_andamento'),
                'concluida' => false,
                'periodo' => $this->formatPeriodFromDates($activeMeta['data_inicio'] ?? null, null, $ctx),
            ];
        }

        foreach ($completedMetas as $m) {
            if (!isset($m['ordem'])) {
                continue;
            }
            $list[] = [
                'ordem' => (int) $m['ordem'],
                'target_id' => (int) ($m['target_id'] ?? $m['id'] ?? 0),
                'status' => (string) ($m['status'] ?? 'ontime'),
                'concluida' => true,
                'periodo' => $this->formatPeriodFromDates($m['data_inicio'] ?? null, $m['data_conclusao'] ?? null, $ctx),
            ];
        }

        return $list;
    }

    protected function isActiveMeta100PercentDone(int $userId, array $activeMeta): bool
    {
        if (isset($activeMeta['percentual_conclusao']) && (float) $activeMeta['percentual_conclusao'] >= 100.0) {
            return true;
        }
        if (isset($activeMeta['atividades_total'], $activeMeta['atividades_concluidas'])
            && (int) $activeMeta['atividades_total'] > 0
            && (int) $activeMeta['atividades_concluidas'] >= (int) $activeMeta['atividades_total']
        ) {
            return true;
        }

        $localRaw = DB::table('study_guruja_metas')->where('user_id', $userId)->value('payload');
        if (!$localRaw) {
            return false;
        }
        $local = json_decode($localRaw, true);
        if (!is_array($local) || (int) ($local['ordem'] ?? -1) !== (int) ($activeMeta['ordem'] ?? -2)) {
            return false;
        }
        $nonIgnored = array_values(array_filter($local['atividades'] ?? [], fn ($a) => empty($a['ignorada'])));
        if (empty($nonIgnored)) {
            return false;
        }
        $done = count(array_filter($nonIgnored, fn ($a) => !empty($a['concluida'])));

        return $done >= count($nonIgnored);
    }

    protected function assemblePayload(
        int $userId,
        array $meta,
        bool $isCompleted,
        array $metasDisponiveis,
        array $historico,
        array $ctx
    ): array {
        $atividades = $meta['atividades'] ?? [];
        $reforcos = $meta['reforcos'] ?? [];

        $nonIgnored = array_values(array_filter($atividades, fn (array $a) => empty($a['ignorada'])));
        $atividadesTotal = count($nonIgnored);
        $atividadesFeitas = count(array_filter($nonIgnored, fn (array $a) => !empty($a['concluida'])));

        $aviso = null;
        if (!$isCompleted && $atividadesTotal > 0 && $atividadesFeitas >= $atividadesTotal) {
            $aviso = self::AVISO_FINALIZAR_NO_PORTAL;
        }

        $questoesResolvidas = (int) ($meta['questoes_resolvidas'] ?? 0);
        $acertos = (int) ($meta['acertos'] ?? 0);
        $pctAcerto = $questoesResolvidas > 0
            ? round(($acertos / $questoesResolvidas) * 100, 1)
            : null;

        $minutosEstudados = (int) ($meta['minutos_estudados'] ?? 0);
        $horasPorMateria = $this->computeHoursBySubject($atividades, $reforcos);
        $destaques = $this->computeHighlights($atividades);

        $diasAtraso = (int) ($meta['dias_atraso'] ?? 0);
        $statusRaw = strtolower((string) ($meta['status'] ?? ($isCompleted ? 'ontime' : 'em_andamento')));
        [$statusTexto, $statusCurto] = $this->resolveStatusLabels($statusRaw, $diasAtraso, $isCompleted);

        [$windowStart, $windowEnd] = $this->resolveWindow(
            $meta['data_inicio'] ?? null,
            $isCompleted ? ($meta['data_conclusao'] ?? null) : null,
            $ctx
        );
        $horarios = $this->computeAppSchedule($userId, $windowStart, $windowEnd, $ctx);
        $periodo = $this->formatPeriodFromBounds($windowStart, $windowEnd, $ctx);

        return [
            'cached' => false,
            'aviso' => $aviso,
            'metas_disponiveis' => $metasDisponiveis,
            'meta' => [
                'target_id' => (int) ($meta['target_id'] ?? 0),
                'ordem' => isset($meta['ordem']) ? (int) $meta['ordem'] : null,
                'concluida' => $isCompleted,
                'status' => $statusRaw,
                'status_texto' => $statusTexto,
                'status_curto' => $statusCurto,
                'dias_atraso' => $diasAtraso,
                'vezes_adiada' => (int) ($meta['vezes_adiada'] ?? 0),
                'data_inicio' => $meta['data_inicio'] ?? null,
                'data_conclusao' => $meta['data_conclusao'] ?? null,
                'periodo' => $periodo,
                'atividades_feitas' => $atividadesFeitas,
                'atividades_total' => $atividadesTotal,
                'questoes_resolvidas' => $questoesResolvidas,
                'acertos' => $acertos,
                'pct_acerto' => $pctAcerto,
                'minutos_estudados' => $minutosEstudados,
                'horas_formatado' => self::formatMinutes($minutosEstudados),
            ],
            'destaques' => $destaques,
            'horas_por_materia' => $horasPorMateria,
            'horarios' => $horarios,
            'historico' => $historico,
        ];
    }

    /**
     * Destaques: até 3 atividades concluídas com questoes > 0 e maior % de acerto (desempate: mais questões).
     */
    protected function computeHighlights(array $atividades): array
    {
        $candidates = [];
        foreach ($atividades as $idx => $a) {
            if (empty($a['concluida']) || !empty($a['ignorada'])) {
                continue;
            }
            $questoes = (int) ($a['questoes'] ?? 0);
            if ($questoes <= 0) {
                continue;
            }
            $acertos = max(0, (int) ($a['acertos'] ?? 0));
            $titulo = trim((string) ($a['titulo'] ?? 'Atividade'));
            $ratio = $acertos / $questoes;

            $candidates[] = [
                'idx' => $idx,
                'ratio' => $ratio,
                'titulo' => $titulo,
                'rotulo' => self::truncateLabel($titulo, 38),
                'disciplina' => $a['disciplina'] ?? null,
                'questoes' => $questoes,
                'acertos' => $acertos,
                'pct_acerto' => round($ratio * 100, 1),
            ];
        }

        usort($candidates, function (array $a, array $b): int {
            if ($b['ratio'] !== $a['ratio']) {
                return $b['ratio'] <=> $a['ratio'];
            }
            if ($b['questoes'] !== $a['questoes']) {
                return $b['questoes'] <=> $a['questoes'];
            }

            return $a['idx'] <=> $b['idx'];
        });

        return array_map(
            fn (array $item) => [
                'titulo' => $item['titulo'],
                'rotulo' => $item['rotulo'],
                'disciplina' => $item['disciplina'],
                'questoes' => $item['questoes'],
                'acertos' => $item['acertos'],
                'pct_acerto' => $item['pct_acerto'],
            ],
            array_slice($candidates, 0, 3)
        );
    }

    /**
     * Horas por matéria: soma minutos de atividades e reforços por disciplina, ordem decrescente.
     */
    protected function computeHoursBySubject(array $atividades, array $reforcos): array
    {
        $bySubject = [];

        foreach ($atividades as $a) {
            $min = (int) ($a['minutos'] ?? 0);
            if ($min <= 0) {
                continue;
            }
            $disc = trim((string) ($a['disciplina'] ?? '')) ?: 'Outros';
            $bySubject[$disc] = ($bySubject[$disc] ?? 0) + $min;
        }

        foreach ($reforcos as $r) {
            $min = (int) ($r['minutos'] ?? 0);
            if ($min <= 0) {
                continue;
            }
            $disc = trim((string) ($r['disciplina'] ?? '')) ?: 'Outros';
            $bySubject[$disc] = ($bySubject[$disc] ?? 0) + $min;
        }

        $rows = [];
        foreach ($bySubject as $disciplina => $minutos) {
            $rows[] = [
                'disciplina' => $disciplina,
                'minutos' => $minutos,
                'horas_formatado' => self::formatMinutes($minutos),
            ];
        }

        usort($rows, function (array $a, array $b): int {
            if ($b['minutos'] !== $a['minutos']) {
                return $b['minutos'] <=> $a['minutos'];
            }

            return strcmp($a['disciplina'], $b['disciplina']);
        });

        return $rows;
    }

    /**
     * @return array{0: string, 1: string} [status_texto, status_curto]
     */
    protected function resolveStatusLabels(string $status, int $diasAtraso, bool $isCompleted): array
    {
        if (!$isCompleted) {
            return ['em andamento', ''];
        }

        return match ($status) {
            'ontime' => ['no prazo', 'no prazo'],
            'anticipated' => ['antecipada', 'antes do prazo'],
            'late' => [
                $diasAtraso === 1
                    ? 'com 1 dia de atraso'
                    : ($diasAtraso > 1 ? "com {$diasAtraso} dias de atraso" : 'com atraso'),
                '',
            ],
            default => ['no prazo', 'no prazo'],
        };
    }

    /** @return array{timezone: string, virada: int, now: CarbonImmutable} */
    protected function context(int $userId): array
    {
        $settings = DB::table('study_settings')->where('user_id', $userId)->first();

        return [
            'timezone' => $settings->timezone ?? self::DEFAULT_TIMEZONE,
            'virada' => (int) ($settings->virada_hora ?? self::DEFAULT_VIRADA),
            'now' => CarbonImmutable::now('UTC'),
        ];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    protected function resolveWindow(?string $dataInicio, ?string $dataConclusao, array $ctx): array
    {
        $end = $dataConclusao
            ? $this->parseGurujaTimestamp($dataConclusao, true)
            : $ctx['now'];

        $start = $dataInicio
            ? $this->parseGurujaTimestamp($dataInicio, false)
            : $end->subDays(7);

        if ($end->lte($start)) {
            $end = $start->addHour();
        }

        return [$start, $end];
    }

    protected function parseGurujaTimestamp(string $value, bool $isEnd): CarbonImmutable
    {
        $trimmed = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed)) {
            $time = $isEnd ? '23:59:59' : '00:00:00';

            return CarbonImmutable::parse("{$trimmed}T{$time}-03:00")->utc();
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2}(\.\d+)?)?$/', $trimmed)) {
            return CarbonImmutable::parse(str_replace(' ', 'T', $trimmed) . '-03:00')->utc();
        }

        return CarbonImmutable::parse($trimmed)->utc();
    }

    protected function formatPeriodFromDates(?string $dataInicio, ?string $dataConclusao, array $ctx): string
    {
        if (!$dataInicio) {
            return '';
        }
        [$start, $end] = $this->resolveWindow($dataInicio, $dataConclusao, $ctx);

        return $this->formatPeriodFromBounds($start, $end, $ctx);
    }

    protected function formatPeriodFromBounds(CarbonImmutable $start, CarbonImmutable $end, array $ctx): string
    {
        $first = CarbonImmutable::parse($this->studyDayOf($start, $ctx));
        $last = CarbonImmutable::parse($this->studyDayOf($end->subMillisecond(), $ctx));

        return $first->format('d/m') . ' a ' . $last->format('d/m');
    }

    protected function studyDayOf(CarbonImmutable $at, array $ctx): string
    {
        return $at->setTimezone($ctx['timezone'])->subHours($ctx['virada'])->format('Y-m-d');
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    protected function dayBounds(string $day, array $ctx): array
    {
        $midnight = CarbonImmutable::parse($day . ' 00:00:00', $ctx['timezone']);

        return [
            $midnight->addHours($ctx['virada'])->utc(),
            $midnight->addDay()->addHours($ctx['virada'])->utc(),
        ];
    }

    protected function computeAppSchedule(int $userId, CarbonImmutable $windowStart, CarbonImmutable $windowEnd, array $ctx): array
    {
        $tz = $ctx['timezone'];
        $now = $ctx['now'];

        $first = CarbonImmutable::parse($this->studyDayOf($windowStart, $ctx));
        $last = CarbonImmutable::parse($this->studyDayOf($windowEnd->subMillisecond(), $ctx));
        $diasTotal = max(1, $first->diffInDays($last) + 1);

        $from = $windowStart->format('Y-m-d H:i:s.v');
        $rows = DB::table('study_sessions')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where('status', 'ok')
            ->where('inicio', '<', $windowEnd->format('Y-m-d H:i:s.v'))
            ->where(fn ($q) => $q->where('fim', '>', $from)->orWhereNull('fim'))
            ->orderBy('inicio')
            ->get();

        $dias = [];
        $totalNonImportedMs = 0;
        $before7Ms = 0;
        $maiorFocoMs = 0;
        $diasComEstudo = 0;
        $firstTimerStartsMin = [];
        $temImportado = false;

        for ($i = 0; $i < $diasTotal; $i++) {
            $day = $first->addDays($i)->format('Y-m-d');
            [$dayStart, $dayEnd] = $this->dayBounds($day, $ctx);
            $clipStart = $dayStart->max($windowStart);
            $clipEnd = $dayEnd->min($windowEnd);

            $dayMidnight = CarbonImmutable::parse($day . ' 00:00:00', $tz)->utc();
            $sevenAm = CarbonImmutable::parse($day . ' 07:00:00', $tz)->utc();

            $daySessions = [];
            $dayHasNonImported = false;
            $firstTimerStart = null;

            foreach ($rows as $row) {
                $inicio = CarbonImmutable::parse($row->inicio, 'UTC');
                $fim = $row->fim ? CarbonImmutable::parse($row->fim, 'UTC') : $now;
                if (!$inicio->lt($clipEnd) || !$fim->gt($clipStart)) {
                    continue;
                }

                $pieceStart = $inicio->max($clipStart);
                $pieceEnd = $fim->min($clipEnd);
                $pieceMs = $pieceEnd->getTimestampMs() - $pieceStart->getTimestampMs();
                if ($pieceMs <= 0) {
                    continue;
                }

                $origem = (string) ($row->origem ?? 'timer');
                $inicioMin = round(($pieceStart->getTimestampMs() - $dayMidnight->getTimestampMs()) / 60000, 1);
                $fimMin = round(($pieceEnd->getTimestampMs() - $dayMidnight->getTimestampMs()) / 60000, 1);

                $daySessions[] = [
                    'inicio' => $pieceStart->utc()->format('Y-m-d\TH:i:s.v\Z'),
                    'fim' => $pieceEnd->utc()->format('Y-m-d\TH:i:s.v\Z'),
                    'inicio_min' => $inicioMin,
                    'fim_min' => $fimMin,
                    'origem' => $origem,
                ];

                if ($origem === 'importado') {
                    $temImportado = true;
                    continue;
                }

                // Estatísticas: somente cronômetro (timer) e manual.
                $dayHasNonImported = true;
                $totalNonImportedMs += $pieceMs;
                if ($pieceMs > $maiorFocoMs) {
                    $maiorFocoMs = $pieceMs;
                }

                if ($pieceStart->lt($sevenAm)) {
                    $overlapEnd = $pieceEnd->min($sevenAm);
                    $beforeMs = $overlapEnd->getTimestampMs() - $pieceStart->getTimestampMs();
                    if ($beforeMs > 0) {
                        $before7Ms += $beforeMs;
                    }
                }

                if ($origem === 'timer') {
                    $firstTimerStart = $firstTimerStart ? $firstTimerStart->min($pieceStart) : $pieceStart;
                }
            }

            if ($dayHasNonImported) {
                $diasComEstudo++;
            }
            if ($firstTimerStart !== null) {
                $firstTimerStartsMin[] = ($firstTimerStart->getTimestampMs() - $dayMidnight->getTimestampMs()) / 60000;
            }

            $dias[] = [
                'data' => $day,
                'rotulo' => $this->weekdayShort($day) . ' ' . substr($day, 8, 2),
                'sessoes' => $daySessions,
            ];
        }

        $totalNonImportedMin = $totalNonImportedMs / 60000;
        $mediaDiaMin = round($totalNonImportedMin / $diasTotal, 1);
        $inicioMedio = !empty($firstTimerStartsMin)
            ? self::formatClockMinutes(array_sum($firstTimerStartsMin) / count($firstTimerStartsMin))
            : null;
        $pctAntes7h = $totalNonImportedMs > 0
            ? round(($before7Ms / $totalNonImportedMs) * 100, 1)
            : 0.0;
        $maiorFocoMin = round($maiorFocoMs / 60000, 1);

        return [
            'fuso' => $tz,
            'virada_hora' => $ctx['virada'],
            'dias_com_estudo' => $diasComEstudo,
            'dias_total' => $diasTotal,
            'total_min' => round($totalNonImportedMin, 1),
            'media_dia_min' => $mediaDiaMin,
            'inicio_medio' => $inicioMedio,
            'pct_antes_7h' => $pctAntes7h,
            'maior_foco_min' => $maiorFocoMin,
            'tem_importado' => $temImportado,
            'dias' => $dias,
        ];
    }

    protected function weekdayShort(string $day): string
    {
        return ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'][(int) CarbonImmutable::parse($day)->format('w')];
    }

    public static function formatMinutes(int|float $minutes): string
    {
        $total = max(0, (int) round($minutes));
        if ($total < 60) {
            return $total . 'min';
        }

        return intdiv($total, 60) . 'h' . str_pad((string) ($total % 60), 2, '0', STR_PAD_LEFT);
    }

    public static function formatClockMinutes(float $minutesSinceMidnight): string
    {
        $total = ((int) round($minutesSinceMidnight)) % 1440;
        if ($total < 0) {
            $total += 1440;
        }
        $h = intdiv($total, 60);
        $m = $total % 60;

        return str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    }

    public static function truncateLabel(string $text, int $maxLength = 38): string
    {
        $trimmed = trim($text);
        if (mb_strlen($trimmed) <= $maxLength) {
            return $trimmed;
        }

        return rtrim(mb_substr($trimmed, 0, $maxLength - 1)) . '…';
    }
}
