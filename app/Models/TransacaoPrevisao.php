<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransacaoPrevisao extends Model
{
    protected $table = 'transacao_previsoes';

    protected $fillable = [
        'user_id',
        'categoria',
        'subcategoria',
        'observacao',
        'tipo',
        'valor_previsto',
        'mes',
        'ano',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
