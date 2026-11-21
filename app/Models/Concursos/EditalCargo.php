<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EditalCargo extends ConcursosModel
{
    protected $table = 'edital_cargos';

    public function edital(): BelongsTo
    {
        return $this->belongsTo(Edital::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(EditalArea::class, 'cargo_id')->orderBy('posicao');
    }

    /** Pontos máximos da prova (soma de n_questoes × pontos_por_questao). */
    public function pontosTotais(): float
    {
        return (float) $this->areas->sum(fn (EditalArea $a) => $a->n_questoes * $a->pontos_por_questao);
    }
}
