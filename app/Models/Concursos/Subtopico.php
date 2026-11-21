<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subtopico extends ConcursosModel
{
    protected $table = 'subtopicos';

    public function assunto(): BelongsTo
    {
        return $this->belongsTo(Assunto::class);
    }
}
