<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RunningSplit extends Model
{
    use HasFactory;

    protected $table = 'running_splits';

    protected $fillable = [
        'running_log_id',
        'quilometro',
        'tempo_segundos',
        'pace_segundos_por_km',
        'frequencia_cardiaca_media',
        'elevacao_ganho_metros',
    ];

    protected $casts = [
        'quilometro' => 'integer',
        'tempo_segundos' => 'integer',
        'pace_segundos_por_km' => 'integer',
        'frequencia_cardiaca_media' => 'integer',
        'elevacao_ganho_metros' => 'integer',
    ];

    public function runningLog()
    {
        return $this->belongsTo(RunningLog::class);
    }

    public function getPaceFormatadoAttribute(): string
    {
        $segundos = $this->pace_segundos_por_km;
        $min = floor($segundos / 60);
        $sec = $segundos % 60;
        return sprintf("%d'%02d\"", $min, $sec);
    }

    public function getTempoFormatadoAttribute(): string
    {
        $min = floor($this->tempo_segundos / 60);
        $sec = $this->tempo_segundos % 60;
        return sprintf("%02d:%02d", $min, $sec);
    }
}
