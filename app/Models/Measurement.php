<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Measurement extends Model
{
    use HasFactory;

    protected $table = 'measurements';

    protected $fillable = [
        'user_id',
        'date',
        'weight_kg',
        'chest_cm',
        'waist_narrow_cm',
        'abdomen_umbilical_cm',
        'hips_cm',
        'arm_right_cm',
        'thigh_right_cm',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'weight_kg' => 'decimal:2',
        'chest_cm' => 'decimal:2',
        'waist_narrow_cm' => 'decimal:2',
        'abdomen_umbilical_cm' => 'decimal:2',
        'hips_cm' => 'decimal:2',
        'arm_right_cm' => 'decimal:2',
        'thigh_right_cm' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function photos()
    {
        return $this->hasMany(ProgressPhoto::class, 'measurement_id');
    }

    /**
     * Relação Cintura-Quadril (RCQ).
     * Fórmula: waist_narrow_cm / hips_cm
     */
    public function getRcqAttribute(): ?float
    {
        if (!$this->waist_narrow_cm || !$this->hips_cm || (float)$this->hips_cm <= 0) {
            return null;
        }

        return round((float)$this->waist_narrow_cm / (float)$this->hips_cm, 2);
    }

    /**
     * Alvo Masculino: <= 0,90 (zona de baixo risco metabólico).
     */
    public function getRcqStatusAttribute(): string
    {
        $rcq = $this->rcq;
        if ($rcq === null) {
            return 'indefinido';
        }

        return $rcq <= 0.90 ? 'baixo_risco' : 'risco_aumentado';
    }

    public function getRcqLabelAttribute(): string
    {
        $rcq = $this->rcq;
        if ($rcq === null) {
            return 'Não calculado';
        }

        return $rcq <= 0.90 ? 'Baixo Risco (Alvo Atingido ≤ 0,90)' : 'Risco Aumentado (> 0,90)';
    }
}
