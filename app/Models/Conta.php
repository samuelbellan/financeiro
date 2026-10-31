<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conta extends Model
{
    use HasFactory;

    protected $table = 'contas';

    protected $fillable = [
        'user_id',
        'nome',
        'tipo',
        'banco_id',
        'agencia',
        'numero_conta',
        'moeda',
        'saldo_atual',
        'data_ultimo_saldo',
        'ativo',
    ];

    protected $casts = [
        'saldo_atual'       => 'decimal:2',
        'data_ultimo_saldo' => 'datetime',
        'ativo'             => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transacoes(): HasMany
    {
        return $this->hasMany(Transacao::class, 'conta_id');
    }
}
