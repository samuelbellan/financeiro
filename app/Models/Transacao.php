<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transacao extends Model
{
    /** @use HasFactory<\Database\Factories\TransacaoFactory> */
    use HasFactory;

    protected $table = 'transacoes';

    protected $fillable = [
        'user_id',
        'descricao',
        'valor',
        'tipo',
        'categoria',
        'subcategoria',
        'data',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
