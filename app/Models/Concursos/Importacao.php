<?php

namespace App\Models\Concursos;

class Importacao extends ConcursosModel
{
    protected $table = 'importacoes';

    protected function casts(): array
    {
        return ['resumo' => 'array'];
    }
}
