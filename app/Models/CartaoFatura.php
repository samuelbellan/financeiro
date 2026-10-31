<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartaoFatura extends Model
{
    protected $table = 'cartao_faturas';

    protected $fillable = [
        'user_id',
        'cartao_id',
        'mes',
        'ano',
        'arquivo_path',
        'arquivo_nome_original',
        'total_fatura',
        'total_despesas',
        'total_pagamentos',
        'itens_count',
        'itens_json',
        'status',
        'diferenca_total',
        'pendencias_count',
    ];

    protected $casts = [
        'mes'              => 'integer',
        'ano'              => 'integer',
        'total_fatura'     => 'decimal:2',
        'total_despesas'   => 'decimal:2',
        'total_pagamentos' => 'decimal:2',
        'diferenca_total'  => 'decimal:2',
        'itens_count'      => 'integer',
        'pendencias_count' => 'integer',
        'itens_json'       => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cartao(): BelongsTo
    {
        return $this->belongsTo(Cartao::class, 'cartao_id');
    }

    public function getPeriodoFormatadoAttribute(): string
    {
        $meses = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        $nomeMes = $meses[$this->mes] ?? str_pad((string)$this->mes, 2, '0', STR_PAD_LEFT);
        return "{$nomeMes} / {$this->ano}";
    }
}
