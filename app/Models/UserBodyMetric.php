<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBodyMetric extends Model
{
    use HasFactory;

    protected $table = 'user_body_metrics';

    protected $fillable = [
        'user_id',
        'data_medicao',
        'peso_kg',
        'altura_cm',
        'percentual_gordura',
        'massa_muscular_kg',
        'pescoco_cm',
        'torax_cm',
        'braco_relaxado_dir_cm',
        'braco_relaxado_esq_cm',
        'braco_contraido_dir_cm',
        'braco_contraido_esq_cm',
        'antebraco_dir_cm',
        'antebraco_esq_cm',
        'cintura_cm',
        'abdome_cm',
        'quadril_cm',
        'coxa_proximal_dir_cm',
        'coxa_proximal_esq_cm',
        'panturrilha_dir_cm',
        'panturrilha_esq_cm',
        'foto_frente_path',
        'foto_lado_path',
        'foto_costas_path',
        'observacoes',
    ];

    protected $casts = [
        'data_medicao' => 'date',
        'peso_kg' => 'decimal:2',
        'altura_cm' => 'decimal:1',
        'percentual_gordura' => 'decimal:2',
        'massa_muscular_kg' => 'decimal:2',
        'pescoco_cm' => 'decimal:2',
        'torax_cm' => 'decimal:2',
        'braco_relaxado_dir_cm' => 'decimal:2',
        'braco_relaxado_esq_cm' => 'decimal:2',
        'braco_contraido_dir_cm' => 'decimal:2',
        'braco_contraido_esq_cm' => 'decimal:2',
        'antebraco_dir_cm' => 'decimal:2',
        'antebraco_esq_cm' => 'decimal:2',
        'cintura_cm' => 'decimal:2',
        'abdome_cm' => 'decimal:2',
        'quadril_cm' => 'decimal:2',
        'coxa_proximal_dir_cm' => 'decimal:2',
        'coxa_proximal_esq_cm' => 'decimal:2',
        'panturrilha_dir_cm' => 'decimal:2',
        'panturrilha_esq_cm' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getImcAttribute(): ?float
    {
        if (!$this->altura_cm || (float)$this->altura_cm <= 0) {
            return null;
        }

        $alturaMetros = (float)$this->altura_cm / 100.0;
        return round((float)$this->peso_kg / ($alturaMetros * $alturaMetros), 1);
    }

    public function getMassaGordaKgAttribute(): ?float
    {
        if (!$this->percentual_gordura || (float)$this->percentual_gordura <= 0) {
            return null;
        }

        return round(((float)$this->peso_kg * (float)$this->percentual_gordura) / 100.0, 2);
    }
}
