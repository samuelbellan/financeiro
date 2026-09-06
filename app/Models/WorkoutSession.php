<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutSession extends Model
{
    use HasFactory;

    protected $table = 'workout_sessions';

    protected $fillable = [
        'user_id',
        'workout_plan_id',
        'nome_sessao',
        'modalidade',
        'data_hora_inicio',
        'data_hora_fim',
        'duracao_minutos',
        'esforco_percebido_rpe',
        'nivel_energia_humor',
        'calorias_queimadas',
        'observacoes',
        'status',
    ];

    protected $casts = [
        'data_hora_inicio' => 'datetime',
        'data_hora_fim' => 'datetime',
        'duracao_minutos' => 'integer',
        'esforco_percebido_rpe' => 'integer',
        'nivel_energia_humor' => 'integer',
        'calorias_queimadas' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(WorkoutPlan::class, 'workout_plan_id');
    }

    public function sessionExercises()
    {
        return $this->hasMany(WorkoutSessionExercise::class)->orderBy('ordem');
    }

    public function runningLog()
    {
        return $this->hasOne(RunningLog::class);
    }

    public function stretchingLog()
    {
        return $this->hasOne(StretchingLog::class);
    }

    public function personalRecords()
    {
        return $this->hasMany(ExercisePersonalRecord::class);
    }

    public function getVolumeTotalKgAttribute(): float
    {
        return (float) $this->sessionExercises
            ->flatMap(fn($item) => $item->sets)
            ->where('concluida', true)
            ->sum('volume_kg');
    }
}
