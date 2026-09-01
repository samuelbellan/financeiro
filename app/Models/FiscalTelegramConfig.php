<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalTelegramConfig extends Model
{
    use HasFactory;

    protected $table = 'fiscal_telegram_configs';

    protected $fillable = [
        'user_id',
        'chat_id',
        'notificar_automaticamente',
        'notificar_federal',
        'notificar_estadual',
        'notificar_municipal',
        'filtro_salario_minimo',
        'ufs_interesse',
        'ultimo_disparo_em',
    ];

    protected $casts = [
        'notificar_automaticamente' => 'boolean',
        'notificar_federal'         => 'boolean',
        'notificar_estadual'        => 'boolean',
        'notificar_municipal'       => 'boolean',
        'filtro_salario_minimo'     => 'decimal:2',
        'ufs_interesse'             => 'array',
        'ultimo_disparo_em'         => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
