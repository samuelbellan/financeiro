<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgressPhoto extends Model
{
    use HasFactory;

    protected $table = 'progress_photos';

    protected $fillable = [
        'user_id',
        'measurement_id',
        'date',
        'angle',
        'file_path',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function measurement()
    {
        return $this->belongsTo(Measurement::class, 'measurement_id');
    }

    public function getUrlAttribute(): string
    {
        return route('tracker.photo.show', ['id' => $this->id]);
    }

    public function getAngleLabelAttribute(): string
    {
        return match ($this->angle) {
            'FRONT' => 'Frente',
            'BACK' => 'Costas',
            'SIDE_RIGHT' => 'Lateral Direita',
            'SIDE_LEFT' => 'Lateral Esquerda',
            default => $this->angle,
        };
    }
}
