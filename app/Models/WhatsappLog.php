<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    protected $fillable = [
        'numero',
        'mensagem_original',
        'transacao_id',
        'status',
        'resposta',
        'erro_detalhes',
    ];

    public function transacao()
    {
        return $this->belongsTo(Transacao::class);
    }
}
