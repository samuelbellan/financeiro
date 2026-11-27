<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * material_questoes.trechos_errados: o que torna cada alternativa errada, gerado pelo Gemini na
 * primeira vez que o aluno erra a questão e guardado para as próximas.
 * Formato: {"B": {"trechos": ["presidencialismo"], "motivo": "..."}, ...}; em Certo/Errado com
 * gabarito E, a chave é "enunciado". Null = ainda não gerado.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        Schema::connection($this->connection)->table('material_questoes', function (Blueprint $t) {
            $t->json('trechos_errados')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('material_questoes', fn (Blueprint $t) => $t->dropColumn('trechos_errados'));
    }
};
