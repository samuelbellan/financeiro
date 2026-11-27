<?php

namespace App\Models\Concursos;

/** Nota do aluno num ponto do texto de um artigo da lei seca (texto + tinta), ancorada pelo contexto (ver Reancorador). */
class LeiNota extends ConcursosModel
{
    protected $table = 'lei_notas';

    protected $casts = ['tracos' => 'array', 'altura' => 'integer'];
}
