<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EditalDisciplina extends ConcursosModel
{
    protected $table = 'edital_disciplinas';

    public function area(): BelongsTo
    {
        return $this->belongsTo(EditalArea::class, 'area_id');
    }

    public function topicos(): HasMany
    {
        return $this->hasMany(EditalTopico::class, 'disciplina_id')->orderBy('posicao');
    }
}
