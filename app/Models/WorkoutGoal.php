<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutGoal extends Model
{
    use HasFactory;

    protected $table = 'workout_goals';

    protected $fillable = [
        'user_id',
        'titulo',
        'modalidade',
        'tipo_meta',
        'periodo',
        'valor_alvo',
        'ativo',
    ];

    protected $casts = [
        'valor_alvo' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }
}
