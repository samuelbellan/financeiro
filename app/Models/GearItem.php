<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GearItem extends Model
{
    use HasFactory;

    protected $table = 'gear_items';

    protected $fillable = [
        'user_id',
        'tipo',
        'marca',
        'modelo',
        'quilometragem_inicial_km',
        'quilometragem_acumulada_km',
        'vida_util_limite_km',
        'data_aquisicao',
        'ativo',
    ];

    protected $casts = [
        'quilometragem_inicial_km' => 'decimal:2',
        'quilometragem_acumulada_km' => 'decimal:2',
        'vida_util_limite_km' => 'decimal:2',
        'data_aquisicao' => 'date',
        'ativo' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function runningLogs()
    {
        return $this->hasMany(RunningLog::class);
    }

    public function getQuilometragemTotalAttribute(): float
    {
        return (float) ($this->quilometragem_inicial_km + $this->quilometragem_acumulada_km);
    }

    public function getPercentualUsoAttribute(): ?float
    {
        if (!$this->vida_util_limite_km || (float)$this->vida_util_limite_km <= 0) {
            return null;
        }

        return round(($this->quilometragem_total / (float)$this->vida_util_limite_km) * 100, 1);
    }

    public function getAlertaDesgasteAttribute(): string
    {
        $pct = $this->percentual_uso;
        if ($pct === null) return 'normal';
        if ($pct >= 100) return 'esgotado';
        if ($pct >= 85) return 'alerta';
        return 'normal';
    }
}
