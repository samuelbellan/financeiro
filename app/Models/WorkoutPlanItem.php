<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutPlanItem extends Model
{
    use HasFactory;

    protected $table = 'workout_plan_items';

    protected $fillable = [
        'workout_plan_id',
        'exercise_id',
        'ordem',
        'series_planejadas',
        'reps_min',
        'reps_max',
        'carga_alvo_kg',
        'tempo_alvo_segundos',
        'distancia_alvo_metros',
        'tempo_descanso_segundos',
        'tecnica_avancada',
        'notas',
    ];

    protected $casts = [
        'ordem' => 'integer',
        'series_planejadas' => 'integer',
        'reps_min' => 'integer',
        'reps_max' => 'integer',
        'carga_alvo_kg' => 'decimal:2',
        'tempo_alvo_segundos' => 'integer',
        'distancia_alvo_metros' => 'integer',
        'tempo_descanso_segundos' => 'integer',
    ];

    public function plan()
    {
        return $this->belongsTo(WorkoutPlan::class, 'workout_plan_id');
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }
}
