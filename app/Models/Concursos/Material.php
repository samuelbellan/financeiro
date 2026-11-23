<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Material extends ConcursosModel
{
    protected $table = 'materiais';

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function secoes(): HasMany
    {
        return $this->hasMany(MaterialSecao::class)->orderBy('ordem')->orderBy('id');
    }

    public function pontos(): HasManyThrough
    {
        return $this->hasManyThrough(MaterialPonto::class, MaterialSecao::class, 'material_id', 'secao_id');
    }
}
