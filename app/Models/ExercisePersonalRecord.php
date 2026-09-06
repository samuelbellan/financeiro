<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExercisePersonalRecord extends Model
{
    use HasFactory;

    protected $table = 'exercise_personal_records';

    protected $fillable = [
        'user_id',
        'exercise_id',
        'workout_session_id',
        'modalidade',
        'tipo_recorde',
        'valor_numerico',
        'valor_formatado',
        'data_recorde',
        'notas',
    ];

    protected $casts = [
        'valor_numerico' => 'decimal:3',
        'data_recorde' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    public function session()
    {
        return $this->belongsTo(WorkoutSession::class, 'workout_session_id');
    }

    public function scopeMusculacao($query)
    {
        return $query->where('modalidade', 'musculacao');
    }

    public function scopeCorrida($query)
    {
        return $query->where('modalidade', 'corrida');
    }
}
