<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaFiscalItem extends Model
{
    use HasFactory;

    protected $table = 'nota_fiscal_itens';

    protected $fillable = [
        'user_id',
        'nota_fiscal_id',
        'transacao_id',
        'cartao_compra_id',
        'estabelecimento',
        'data_compra',
        'nome_item',
        'categoria_item',
        'quantidade',
        'valor_unitario',
        'valor_total',
    ];

    protected $casts = [
        'data_compra' => 'datetime',
        'quantidade' => 'decimal:3',
        'valor_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'nota_fiscal_id');
    }

    public function transacao()
    {
        return $this->belongsTo(Transacao::class);
    }

    public function cartaoCompra()
    {
        return $this->belongsTo(CartaoCompra::class, 'cartao_compra_id');
    }
}
