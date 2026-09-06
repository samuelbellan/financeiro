<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutSessionExercise extends Model
{
    use HasFactory;

    protected $table = 'workout_session_exercises';

    protected $fillable = [
        'workout_session_id',
        'exercise_id',
        'ordem',
        'observacoes',
    ];

    protected $casts = [
        'ordem' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(WorkoutSession::class, 'workout_session_id');
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    public function sets()
    {
        return $this->hasMany(WorkoutSet::class)->orderBy('numero_serie');
    }

    public function getVolumeKgAttribute(): float
    {
        return (float) $this->sets->where('concluida', true)->sum('volume_kg');
    }

    public function getMaiorCargaAttribute(): float
    {
        return (float) $this->sets->where('concluida', true)->max('carga_kg') ?? 0.0;
    }

    public function getMelhorUmRmEstimadoAttribute(): float
    {
        return (float) $this->sets->where('concluida', true)->max('um_rm_estimado') ?? 0.0;
    }
}
