<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Disciplina extends ConcursosModel
{
    protected $table = 'disciplinas';

    public function assuntos(): HasMany
    {
        return $this->hasMany(Assunto::class);
    }
}
