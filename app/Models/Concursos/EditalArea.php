<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EditalArea extends ConcursosModel
{
    protected $table = 'edital_areas';

    protected function casts(): array
    {
        return ['pontos_por_questao' => 'float', 'minimo_pontos' => 'float'];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(EditalCargo::class, 'cargo_id');
    }

    public function disciplinas(): HasMany
    {
        return $this->hasMany(EditalDisciplina::class, 'area_id')->orderBy('posicao');
    }
}
