<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyGoal extends Model
{
    use HasFactory;

    protected $table = 'study_goals';

    protected $fillable = [
        'user_id',
        'nome',
        'horas_meta',
        'horas_iniciais',
        'data_inicio',
        'data_limite',
        'horas_diarias_padrao',
        'carga_seg',
        'carga_ter',
        'carga_qua',
        'carga_qui',
        'carga_sex',
        'carga_sab',
        'carga_dom',
    ];

    protected $casts = [
        'horas_meta' => 'decimal:2',
        'horas_iniciais' => 'decimal:2',
        'horas_diarias_padrao' => 'decimal:2',
        'carga_seg' => 'decimal:2',
        'carga_ter' => 'decimal:2',
        'carga_qua' => 'decimal:2',
        'carga_qui' => 'decimal:2',
        'carga_sex' => 'decimal:2',
        'carga_sab' => 'decimal:2',
        'carga_dom' => 'decimal:2',
        'data_inicio' => 'date',
        'data_limite' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(StudyLog::class, 'study_goal_id');
    }
}
