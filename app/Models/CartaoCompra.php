<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartaoCompra extends Model
{
    use HasFactory;

    protected $fillable = [
        'cartao_id',
        'descricao',
        'valor_total',
        'tipo',
        'numero_parcelas',
        'categoria',
        'data_compra',
    ];

    public function cartao()
    {
        return $this->belongsTo(Cartao::class);
    }

    public function parcelas()
    {
        return $this->hasMany(CartaoParcela::class);
    }
}
