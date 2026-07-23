<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyLog extends Model
{
    use HasFactory;

    protected $table = 'study_logs';

    protected $fillable = [
        'user_id',
        'study_goal_id',
        'data',
        'horas',
        'observacoes',
    ];

    protected $casts = [
        'horas' => 'decimal:2',
        'data' => 'date',
    ];

    public function goal()
    {
        return $this->belongsTo(StudyGoal::class, 'study_goal_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
