<?php

namespace App\Models\Concursos;

/** Grifo do aluno num artigo da lei seca, ancorado pelo trecho + contexto (ver Reancorador). */
class LeiGrifo extends ConcursosModel
{
    public const CORES = MaterialAnotacao::CORES;

    protected $table = 'lei_grifos';
}
