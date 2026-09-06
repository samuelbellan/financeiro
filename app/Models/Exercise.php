<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use HasFactory;

    protected $table = 'exercises';

    protected $fillable = [
        'user_id',
        'nome',
        'slug',
        'categoria',
        'grupo_muscular_primario',
        'grupo_muscular_secundario',
        'tipo_medicao',
        'equipamento',
        'instrucoes_execucao',
        'link_video_demonstrativo',
        'ativo',
    ];

    protected $casts = [
        'grupo_muscular_secundario' => 'array',
        'ativo' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function planItems()
    {
        return $this->hasMany(WorkoutPlanItem::class);
    }

    public function sessionExercises()
    {
        return $this->hasMany(WorkoutSessionExercise::class);
    }

    public function personalRecords()
    {
        return $this->hasMany(ExercisePersonalRecord::class);
    }

    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeMusculacao($query)
    {
        return $query->where('categoria', 'musculacao');
    }

    public function scopeCorrida($query)
    {
        return $query->where('categoria', 'corrida');
    }

    public function scopeAlongamento($query)
    {
        return $query->where('categoria', 'alongamento');
    }
}
