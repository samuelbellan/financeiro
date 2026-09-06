<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutSet extends Model
{
    use HasFactory;

    protected $table = 'workout_sets';

    protected $fillable = [
        'workout_session_exercise_id',
        'numero_serie',
        'tipo_serie',
        'carga_kg',
        'repeticoes',
        'rpe',
        'rir',
        'tempo_descanso_segundos',
        'um_rm_estimado',
        'volume_kg',
        'concluida',
    ];

    protected $casts = [
        'numero_serie' => 'integer',
        'carga_kg' => 'decimal:2',
        'repeticoes' => 'integer',
        'rpe' => 'decimal:1',
        'rir' => 'integer',
        'tempo_descanso_segundos' => 'integer',
        'um_rm_estimado' => 'decimal:2',
        'volume_kg' => 'decimal:2',
        'concluida' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function (WorkoutSet $set) {
            $carga = (float) $set->carga_kg;
            $reps = (int) $set->repeticoes;

            // Volume da série = Carga * Repetições
            $set->volume_kg = round($carga * $reps, 2);

            // Estimativa de 1RM pela fórmula consagrada de Epley: Carga * (1 + Reps / 30)
            if ($reps > 0 && $carga > 0) {
                if ($reps === 1) {
                    $set->um_rm_estimado = $carga;
                } else {
                    $set->um_rm_estimado = round($carga * (1 + ($reps / 30)), 2);
                }
            } else {
                $set->um_rm_estimado = null;
            }
        });
    }

    public function sessionExercise()
    {
        return $this->belongsTo(WorkoutSessionExercise::class, 'workout_session_exercise_id');
    }
}
