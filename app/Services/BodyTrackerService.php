<?php

namespace App\Services;

use App\Models\DailyLog;
use App\Models\Measurement;
use App\Models\ProgressPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BodyTrackerService
{
    /**
     * Obtém o diário de um usuário para uma data específica.
     */
    public function getDailyLogForDate(int $userId, string $date): ?DailyLog
    {
        return DailyLog::where('user_id', $userId)
            ->where('date', $date)
            ->first();
    }

    /**
     * Salva ou atualiza o registro diário do usuário.
     */
    public function saveDailyLog(int $userId, array $data): DailyLog
    {
        $date = $data['date'] ?? now()->toDateString();

        return DailyLog::updateOrCreate(
            ['user_id' => $userId, 'date' => $date],
            [
                'workout_done' => (bool) ($data['workout_done'] ?? false),
                'workout_type' => $data['workout_type'] ?? null,
                'workout_duration_min' => isset($data['workout_duration_min']) ? (int) $data['workout_duration_min'] : null,
                'breakfast_clean' => (bool) ($data['breakfast_clean'] ?? false),
                'lunch_clean' => (bool) ($data['lunch_clean'] ?? true),
                'snack_done' => (bool) ($data['snack_done'] ?? false),
                'dinner_clean' => (bool) ($data['dinner_clean'] ?? true),
                'water_volume_ml' => (int) ($data['water_volume_ml'] ?? 0),
                'notes' => $data['notes'] ?? null,
            ]
        );
    }

    /**
     * Retorna o histórico de diários dentro de um intervalo de datas.
     */
    public function getDailyLogs(int $userId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = DailyLog::where('user_id', $userId)->orderByDesc('date');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Salva um check-in antropométrico.
     */
    public function saveMeasurement(int $userId, array $data): Measurement
    {
        return Measurement::create([
            'user_id' => $userId,
            'date' => $data['date'] ?? now()->toDateString(),
            'weight_kg' => $data['weight_kg'],
            'chest_cm' => $data['chest_cm'],
            'waist_narrow_cm' => $data['waist_narrow_cm'],
            'abdomen_umbilical_cm' => $data['abdomen_umbilical_cm'],
            'hips_cm' => $data['hips_cm'],
            'arm_right_cm' => $data['arm_right_cm'],
            'thigh_right_cm' => $data['thigh_right_cm'],
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Retorna a medição mais recente do usuário.
     */
    public function getLatestMeasurement(int $userId): ?Measurement
    {
        return Measurement::where('user_id', $userId)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Retorna a medição inicial (baseline / marco zero) do usuário.
     */
    public function getBaselineMeasurement(int $userId): ?Measurement
    {
        return Measurement::where('user_id', $userId)
            ->orderBy('date')
            ->orderBy('id')
            ->first();
    }

    /**
     * Consolida o resumo completo de métricas corporais, deltas absolutos e percentuais
     * e séries históricas para o Manequim Interativo (SVG Body Mapper).
     */
    public function getSummaryDeltas(int $userId): array
    {
        $allMeasurements = Measurement::where('user_id', $userId)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($allMeasurements->isEmpty()) {
            return [
                'has_data' => false,
                'total_checkins' => 0,
                'latest' => null,
                'baseline' => null,
                'points' => [],
                'rcq' => null,
            ];
        }

        $baseline = $allMeasurements->first();
        $latest = $allMeasurements->last();
        $previous = $allMeasurements->count() > 1
            ? $allMeasurements[$allMeasurements->count() - 2]
            : $baseline;

        $metricsMap = [
            'chest_cm' => [
                'key' => 'chest',
                'name' => 'Tórax / Peitoral',
                'category' => 'muscle',
                'unit' => 'cm',
                'anchor' => ['x' => 150, 'y' => 140],
            ],
            'waist_narrow_cm' => [
                'key' => 'waist_narrow',
                'name' => 'Cintura Alta (Estreita)',
                'category' => 'fat',
                'unit' => 'cm',
                'anchor' => ['x' => 150, 'y' => 195],
            ],
            'abdomen_umbilical_cm' => [
                'key' => 'abdomen_umbilical',
                'name' => 'Abdômen Umbilical',
                'category' => 'fat',
                'unit' => 'cm',
                'anchor' => ['x' => 150, 'y' => 235],
            ],
            'hips_cm' => [
                'key' => 'hips',
                'name' => 'Quadril',
                'category' => 'neutral',
                'unit' => 'cm',
                'anchor' => ['x' => 150, 'y' => 280],
            ],
            'arm_right_cm' => [
                'key' => 'arm_right',
                'name' => 'Braço Direito',
                'category' => 'muscle',
                'unit' => 'cm',
                'anchor' => ['x' => 85, 'y' => 175],
            ],
            'thigh_right_cm' => [
                'key' => 'thigh_right',
                'name' => 'Coxa Direita',
                'category' => 'muscle',
                'unit' => 'cm',
                'anchor' => ['x' => 118, 'y' => 360],
            ],
            'weight_kg' => [
                'key' => 'weight',
                'name' => 'Peso Corporal',
                'category' => 'neutral',
                'unit' => 'kg',
                'anchor' => null,
            ],
        ];

        $points = [];

        foreach ($metricsMap as $column => $meta) {
            $baseVal = (float) $baseline->{$column};
            $currentVal = (float) $latest->{$column};
            $prevVal = (float) $previous->{$column};

            $deltaAbs = round($currentVal - $baseVal, 2);
            $deltaPct = $baseVal > 0 ? round((($currentVal - $baseVal) / $baseVal) * 100, 2) : 0.0;

            $deltaPrevAbs = round($currentVal - $prevVal, 2);
            $deltaPrevPct = $prevVal > 0 ? round((($currentVal - $prevVal) / $prevVal) * 100, 2) : 0.0;

            // Semântica de progresso positivo:
            // Gordura (cintura, abdômen): delta <= 0 é bom (verde)
            // Músculo (tórax, braço): delta >= 0 é ganho muscular; pequena redução pode ser perda de gordura
            $isPositive = false;
            if ($meta['category'] === 'fat') {
                $isPositive = $deltaPct <= 0;
            } elseif ($meta['category'] === 'muscle') {
                $isPositive = $deltaPct >= 0 || abs($deltaPct) <= 1.5;
            } else {
                $isPositive = $deltaPct <= 0;
            }

            // Histórico para mini-gráfico (sparkline) - últimas 6 medições
            $history = $allMeasurements->take(-6)->map(function ($m) use ($column) {
                return [
                    'date' => $m->date ? $m->date->format('d/m') : '',
                    'value' => (float) $m->{$column},
                ];
            })->values()->all();

            $points[$meta['key']] = [
                'key' => $meta['key'],
                'name' => $meta['name'],
                'category' => $meta['category'],
                'unit' => $meta['unit'],
                'anchor' => $meta['anchor'],
                'current' => $currentVal,
                'baseline' => $baseVal,
                'previous' => $prevVal,
                'delta_abs' => $deltaAbs,
                'delta_pct' => $deltaPct,
                'delta_prev_abs' => $deltaPrevAbs,
                'delta_prev_pct' => $deltaPrevPct,
                'is_positive' => $isPositive,
                'history' => $history,
            ];
        }

        $latestRcq = $latest->rcq;
        $baselineRcq = $baseline->rcq;
        $deltaRcq = ($latestRcq !== null && $baselineRcq !== null)
            ? round($latestRcq - $baselineRcq, 2)
            : null;

        // Histórico completo para a Linha do Tempo Animada (Time-Lapse Slider)
        $timeline = $allMeasurements->map(function ($m) {
            return [
                'id' => $m->id,
                'date' => $m->date ? $m->date->format('Y-m-d') : null,
                'date_formatted' => $m->date ? $m->date->format('d/m/Y') : null,
                'weight_kg' => (float) $m->weight_kg,
                'chest_cm' => (float) $m->chest_cm,
                'waist_narrow_cm' => (float) $m->waist_narrow_cm,
                'abdomen_umbilical_cm' => (float) $m->abdomen_umbilical_cm,
                'hips_cm' => (float) $m->hips_cm,
                'arm_right_cm' => (float) $m->arm_right_cm,
                'thigh_right_cm' => (float) $m->thigh_right_cm,
                'rcq' => $m->rcq,
            ];
        })->values()->all();

        return [
            'has_data' => true,
            'total_checkins' => $allMeasurements->count(),
            'latest' => [
                'id' => $latest->id,
                'date' => $latest->date ? $latest->date->format('Y-m-d') : null,
                'date_formatted' => $latest->date ? $latest->date->format('d/m/Y') : null,
                'weight_kg' => (float) $latest->weight_kg,
            ],
            'baseline' => [
                'id' => $baseline->id,
                'date' => $baseline->date ? $baseline->date->format('Y-m-d') : null,
                'date_formatted' => $baseline->date ? $baseline->date->format('d/m/Y') : null,
                'weight_kg' => (float) $baseline->weight_kg,
            ],
            'points' => $points,
            'timeline' => $timeline,
            'rcq' => [
                'current' => $latestRcq,
                'baseline' => $baselineRcq,
                'delta' => $deltaRcq,
                'status' => $latest->rcq_status,
                'label' => $latest->rcq_label,
                'target' => 0.90,
            ],
        ];
    }

    /**
     * Calcula o streak (sequência em dias consecutivos de consistência nos hábitos).
     * Considera dias com adesão >= 60% ou treino realizado.
     */
    public function getConsistencyStreak(int $userId): int
    {
        $today = now()->toDateString();
        $logs = DailyLog::where('user_id', $userId)
            ->where('date', '<=', $today)
            ->orderByDesc('date')
            ->get()
            ->keyBy(function ($item) {
                return $item->date ? $item->date->format('Y-m-d') : '';
            });

        if ($logs->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $currentDate = now();

        // Se hoje ainda não tem log ou tem adesão baixa, começa a verificar a partir de ontem
        $todayKey = $today;
        $todayLog = $logs->get($todayKey);

        if ($todayLog && ($todayLog->adherence_score >= 60 || $todayLog->workout_done)) {
            $streak++;
            $currentDate = $currentDate->subDay();
        } elseif (!$todayLog) {
            // Se hoje ainda não preencheu, não quebra a sequência de ontem
            $currentDate = $currentDate->subDay();
        }

        // Percorre os dias anteriores consecutivos
        for ($i = 0; $i < 60; $i++) {
            $key = $currentDate->format('Y-m-d');
            $log = $logs->get($key);

            if ($log && ($log->adherence_score >= 60 || $log->workout_done)) {
                $streak++;
                $currentDate = $currentDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Faz o upload e registro de foto de evolução.
     */
    public function savePhoto(int $userId, UploadedFile $file, string $date, string $angle, ?int $measurementId = null): ProgressPhoto
    {
        $path = $file->store('tracker_photos', 'public');

        return ProgressPhoto::create([
            'user_id' => $userId,
            'measurement_id' => $measurementId,
            'date' => $date,
            'angle' => strtoupper($angle),
            'file_path' => $path,
        ]);
    }

    /**
     * Retorna a galeria de fotos do usuário, opcionalmente filtrada por ângulo.
     */
    public function getPhotos(int $userId, ?string $angle = null)
    {
        $query = ProgressPhoto::where('user_id', $userId)->orderByDesc('date')->orderByDesc('id');

        if ($angle) {
            $query->where('angle', strtoupper($angle));
        }

        return $query->get();
    }

    /**
     * Exclui uma foto de evolução e remove o arquivo do disco.
     */
    public function deletePhoto(int $userId, int $photoId): bool
    {
        $photo = ProgressPhoto::where('user_id', $userId)->where('id', $photoId)->first();

        if (!$photo) {
            return false;
        }

        if (Storage::disk('public')->exists($photo->file_path)) {
            Storage::disk('public')->delete($photo->file_path);
        }

        return $photo->delete();
    }
}
