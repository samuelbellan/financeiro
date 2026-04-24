<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartaoPrevisao extends Model
{
    use HasFactory;

    protected $table = 'cartao_previsoes';

    protected $fillable = [
        'cartao_id',
        'categoria',
        'valor_previsto',
        'mes',
        'ano',
    ];

    public function cartao()
    {
        return $this->belongsTo(Cartao::class);
    }
}
