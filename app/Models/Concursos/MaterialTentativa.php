<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialTentativa extends ConcursosModel
{
    public const UPDATED_AT = null;

    protected $table = 'material_tentativas';

    protected $casts = ['acertou' => 'boolean', 'created_at' => 'datetime'];

    public function questao(): BelongsTo
    {
        return $this->belongsTo(MaterialQuestao::class, 'questao_id');
    }
}
