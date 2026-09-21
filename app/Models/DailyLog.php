<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyLog extends Model
{
    use HasFactory;

    protected $table = 'daily_logs';

    protected $fillable = [
        'user_id',
        'date',
        'workout_done',
        'workout_type',
        'workout_duration_min',
        'breakfast_clean',
        'lunch_clean',
        'snack_done',
        'dinner_clean',
        'water_volume_ml',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'workout_done' => 'boolean',
        'workout_duration_min' => 'integer',
        'breakfast_clean' => 'boolean',
        'lunch_clean' => 'boolean',
        'snack_done' => 'boolean',
        'dinner_clean' => 'boolean',
        'water_volume_ml' => 'integer',
    ];

    protected $appends = [
        'adherence_score',
        'water_progress_percent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Percentual de adesão aos hábitos saudáveis do dia (0 a 100%).
     * Considera: Treino, Café sem açúcar/mel, Almoço equilibrado, Lanche proteico e Jantar leve.
     */
    public function getAdherenceScoreAttribute(): int
    {
        $habits = [
            $this->workout_done,
            $this->breakfast_clean,
            $this->lunch_clean,
            $this->snack_done,
            $this->dinner_clean,
        ];

        $completed = count(array_filter($habits));
        return (int) round(($completed / count($habits)) * 100);
    }

    /**
     * Progresso de hidratação relativo à meta de 3000ml.
     */
    public function getWaterProgressPercentAttribute(): int
    {
        if ($this->water_volume_ml <= 0) {
            return 0;
        }
        return (int) min(100, round(($this->water_volume_ml / 3000) * 100));
    }
}
