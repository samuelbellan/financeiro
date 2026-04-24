<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartaoParcela extends Model
{
    use HasFactory;

    protected $fillable = [
        'cartao_compra_id',
        'numero_parcela',
        'valor_parcela',
        'data_vencimento',
        'status',
    ];

    public function compra()
    {
        return $this->belongsTo(CartaoCompra::class, 'cartao_compra_id');
    }
}
