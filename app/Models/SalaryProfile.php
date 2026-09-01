<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryProfile extends Model
{
    protected $fillable = [
        'user_id',
        'nome',
        'config_data',
        'is_active',
    ];

    protected $casts = [
        'config_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the salary profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
