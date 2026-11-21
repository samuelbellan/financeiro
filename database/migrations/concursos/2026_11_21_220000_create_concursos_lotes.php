<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lotes de engenharia reversa (sistema → Gemini). Cada lote é um pacote contexto.json com até
 * ~40 questões de UM assunto. Uma questão entra em um lote só: quando chegam PDFs novos, os
 * próximos pacotes levam apenas as questões ainda não enviadas.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->create('lotes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assunto_id')->constrained('assuntos')->cascadeOnDelete();
            $t->unsignedSmallInteger('numero');
            $t->string('arquivo', 500);
            $t->string('status', 12)->default('enviado'); // enviado | ledger | completo
            $t->timestamps();
            $t->unique(['assunto_id', 'numero']);
        });

        $schema->create('lote_questao', function (Blueprint $t) {
            $t->foreignId('lote_id')->constrained('lotes')->cascadeOnDelete();
            $t->foreignId('questao_id')->unique()->constrained('questoes')->cascadeOnDelete();
            $t->unsignedSmallInteger('ordem');
            $t->primary(['lote_id', 'questao_id']);
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->dropIfExists('lote_questao');
        $schema->dropIfExists('lotes');
    }
};
