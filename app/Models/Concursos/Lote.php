<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lote extends ConcursosModel
{
    protected $table = 'lotes';

    public function assunto(): BelongsTo
    {
        return $this->belongsTo(Assunto::class);
    }

    public function questoes(): BelongsToMany
    {
        return $this->belongsToMany(Questao::class, 'lote_questao')->withPivot('ordem')->orderByPivot('ordem');
    }
}
