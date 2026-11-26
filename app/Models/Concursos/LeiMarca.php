<?php

namespace App\Models\Concursos;

/** Marcador e nota do aluno num artigo da lei seca (lei = sigla do acervo, ancora = "art-98"). */
class LeiMarca extends ConcursosModel
{
    protected $table = 'lei_marcas';

    protected $casts = ['marcado' => 'boolean'];
}
