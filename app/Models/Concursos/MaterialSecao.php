<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialSecao extends ConcursosModel
{
    protected $table = 'material_secoes';

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function pontos(): HasMany
    {
        return $this->hasMany(MaterialPonto::class, 'secao_id')->orderBy('ordem')->orderBy('id');
    }
}
