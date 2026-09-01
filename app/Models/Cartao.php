<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cartao extends Model
{
    use HasFactory;

    protected $table = 'cartoes';

    protected $fillable = [
        'user_id',
        'nome',
        'cor',
        'limite',
        'bandeira',
        'dia_fechamento',
        'dia_vencimento',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function compras()
    {
        return $this->hasMany(CartaoCompra::class);
    }
}
