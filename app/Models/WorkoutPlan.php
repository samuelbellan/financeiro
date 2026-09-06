<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutPlan extends Model
{
    use HasFactory;

    protected $table = 'workout_plans';

    protected $fillable = [
        'user_id',
        'nome',
        'identificador_letra',
        'modalidade',
        'frequencia_semanal_sugerida',
        'dias_semana_recomendados',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'dias_semana_recomendados' => 'array',
        'ativo' => 'boolean',
        'frequencia_semanal_sugerida' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(WorkoutPlanItem::class)->orderBy('ordem');
    }

    public function sessions()
    {
        return $this->hasMany(WorkoutSession::class);
    }

    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }
}
