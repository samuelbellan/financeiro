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
        'conta_id',
        'descricao',
        'valor',
        'tipo',
        'categoria',
        'subcategoria',
        'categoria_id',
        'subcategoria_id',
        'data',
        'data_hora',
        'fitid',
        'numero_documento',
        'tipo_transacao_ofx',
        'memo',
        'valor_original_ofx',
    ];

    protected $casts = [
        'valor'              => 'decimal:2',
        'valor_original_ofx' => 'decimal:2',
        'data'               => 'date',
        'data_hora'          => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conta()
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }
}
