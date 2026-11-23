<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConcursoState extends Model
{
    protected $table = 'concursos_states';

    protected $fillable = [
        'user_id',
        'chave',
        'valor',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
