<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comentário da questão editável (com imagens): material_questoes.comentario_html guarda a versão
 * do aluno ou a gerada pelo Gemini (quando o caderno não trouxe comentário de professor).
 * O texto importado do caderno (comentario) fica intacto. comentario_fonte: 'aluno' | 'gemini'.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        Schema::connection($this->connection)->table('material_questoes', function (Blueprint $t) {
            $t->longText('comentario_html')->nullable();
            $t->string('comentario_fonte', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('material_questoes', fn (Blueprint $t) => $t->dropColumn(['comentario_html', 'comentario_fonte']));
    }
};
