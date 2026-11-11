<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryPayslip extends Model
{
    protected $fillable = [
        'user_id',
        'mes_ano',
        'total_bruto',
        'total_descontos',
        'total_liquido',
        'outlier',
        'base_rpps',
        'base_irrf',
        'pagamentos',
        'descontos',
        'observacao',
    ];

    protected $casts = [
        'total_bruto' => 'float',
        'total_descontos' => 'float',
        'total_liquido' => 'float',
        'outlier' => 'boolean',
        'base_rpps' => 'float',
        'base_irrf' => 'float',
        'pagamentos' => 'array',
        'descontos' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
