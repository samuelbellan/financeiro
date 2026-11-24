<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De onde veio a resolução: 'ia' (Gemini / inéditas) ou 'tec' (comentário do professor no TEC).
 * A resolução da IA prevalece; a do TEC só entra onde não há outra, para nenhuma questão ficar sem.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        Schema::connection($this->connection)->table('questao_resolucoes', function (Blueprint $t) {
            $t->string('fonte', 10)->default('ia');
            $t->string('autor', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('questao_resolucoes', function (Blueprint $t) {
            $t->dropColumn(['fonte', 'autor']);
        });
    }
};
