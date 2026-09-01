<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class NotaFiscal extends Model
{
    use HasFactory;

    protected $table = 'notas_fiscais';

    protected $fillable = [
        'user_id',
        'transacao_id',
        'cartao_compra_id',
        'estabelecimento',
        'data_compra',
        'valor_total',
        'foto_path',
        'forma_pagamento',
        'cartao_nome',
        'observacoes',
    ];

    protected $casts = [
        'data_compra' => 'datetime',
        'valor_total' => 'decimal:2',
    ];

    protected $appends = [
        'foto_url',
    ];

    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->foto_path) {
            return null;
        }

        return Storage::disk('public')->url($this->foto_path);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transacao()
    {
        return $this->belongsTo(Transacao::class);
    }

    public function cartaoCompra()
    {
        return $this->belongsTo(CartaoCompra::class, 'cartao_compra_id');
    }

    public function itens()
    {
        return $this->hasMany(NotaFiscalItem::class, 'nota_fiscal_id');
    }
}
