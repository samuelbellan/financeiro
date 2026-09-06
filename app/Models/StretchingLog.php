<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StretchingLog extends Model
{
    use HasFactory;

    protected $table = 'stretching_logs';

    protected $fillable = [
        'workout_session_id',
        'tipo_sessao',
        'foco_anatomico',
        'nivel_rigidez_inicial',
        'nivel_rigidez_final',
        'sensacao_alivio',
    ];

    protected $casts = [
        'foco_anatomico' => 'array',
        'nivel_rigidez_inicial' => 'integer',
        'nivel_rigidez_final' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(WorkoutSession::class, 'workout_session_id');
    }

    public function getDeltaAlivioAttribute(): int
    {
        // Se inicial era 4 e final ficou 2, o alívio foi de +2 pontos
        return (int) ($this->nivel_rigidez_inicial - $this->nivel_rigidez_final);
    }
}
