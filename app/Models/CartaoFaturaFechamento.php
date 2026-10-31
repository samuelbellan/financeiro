<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartaoFaturaFechamento extends Model
{
    protected $table = 'cartao_fatura_fechamentos';

    protected $fillable = [
        'user_id',
        'cartao_id',
        'mes',
        'ano',
        'fechada',
        'fechada_em',
    ];

    protected $casts = [
        'mes'        => 'integer',
        'ano'        => 'integer',
        'fechada'    => 'boolean',
        'fechada_em' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cartao(): BelongsTo
    {
        return $this->belongsTo(Cartao::class, 'cartao_id');
    }
}
