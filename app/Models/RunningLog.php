<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RunningLog extends Model
{
    use HasFactory;

    protected $table = 'running_logs';

    protected $fillable = [
        'workout_session_id',
        'gear_item_id',
        'tipo_treino',
        'distancia_km',
        'duracao_segundos',
        'pace_medio_segundos_por_km',
        'pace_maximo_segundos_por_km',
        'velocidade_media_kmh',
        'frequencia_cardiaca_media',
        'frequencia_cardiaca_maxima',
        'tempo_zona_cardio',
        'elevacao_ganho_metros',
        'elevacao_perda_metros',
        'cadencia_media_spm',
        'comprimento_passo_metros',
        'terreno',
        'clima_temperatura_celsius',
        'clima_condicao',
        'gpx_polyline_data',
    ];

    protected $casts = [
        'distancia_km' => 'decimal:3',
        'duracao_segundos' => 'integer',
        'pace_medio_segundos_por_km' => 'integer',
        'pace_maximo_segundos_por_km' => 'integer',
        'velocidade_media_kmh' => 'decimal:2',
        'frequencia_cardiaca_media' => 'integer',
        'frequencia_cardiaca_maxima' => 'integer',
        'tempo_zona_cardio' => 'array',
        'elevacao_ganho_metros' => 'integer',
        'elevacao_perda_metros' => 'integer',
        'cadencia_media_spm' => 'integer',
        'comprimento_passo_metros' => 'decimal:2',
        'clima_temperatura_celsius' => 'decimal:1',
    ];

    protected static function booted()
    {
        static::saving(function (RunningLog $log) {
            $distancia = (float) $log->distancia_km;
            $duracao = (int) $log->duracao_segundos;

            // Calcular velocidade média (km/h) se não fornecida
            if ($duracao > 0 && $distancia > 0) {
                $log->velocidade_media_kmh = round(($distancia / ($duracao / 3600)), 2);

                // Calcular Pace médio se não fornecido
                if (!$log->pace_medio_segundos_por_km) {
                    $log->pace_medio_segundos_por_km = (int) round($duracao / $distancia);
                }
            }
        });

        static::created(function (RunningLog $log) {
            // Atualizar km acumulado no tênis/equipamento se vinculado
            if ($log->gear_item_id && $log->distancia_km > 0) {
                $gear = GearItem::find($log->gear_item_id);
                if ($gear) {
                    $gear->increment('quilometragem_acumulada_km', $log->distancia_km);
                }
            }
        });
    }

    public function session()
    {
        return $this->belongsTo(WorkoutSession::class, 'workout_session_id');
    }

    public function gearItem()
    {
        return $this->belongsTo(GearItem::class);
    }

    public function splits()
    {
        return $this->hasMany(RunningSplit::class)->orderBy('quilometro');
    }

    public function getPaceFormatadoAttribute(): string
    {
        $segundos = $this->pace_medio_segundos_por_km;
        if (!$segundos) return "--:--";

        $min = floor($segundos / 60);
        $sec = $segundos % 60;
        return sprintf("%d'%02d\"/km", $min, $sec);
    }

    public function getDuracaoFormatadaAttribute(): string
    {
        $totalSegundos = $this->duracao_segundos;
        $horas = floor($totalSegundos / 3600);
        $minutos = floor(($totalSegundos % 3600) / 60);
        $segundos = $totalSegundos % 60;

        if ($horas > 0) {
            return sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);
        }
        return sprintf("%02d:%02d", $minutos, $segundos);
    }
}
