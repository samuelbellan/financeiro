<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quantas questões o TEC tem de cada assunto num caderno (a contagem da árvore, "386 (63.70%)").
 * É a incidência "segundo o TEC": não depende de quantos PDFs já foram baixados.
 * A contagem do pai inclui a dos filhos. Uma linha por (assunto, caderno); reimportar a árvore
 * do mesmo caderno substitui as linhas dele.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('assunto_incidencias', function (Blueprint $t) {
            $t->foreignId('assunto_id')->constrained('assuntos')->cascadeOnDelete();
            $t->string('caderno', 120);
            $t->unsignedInteger('questoes');
            $t->timestamps();
            $t->primary(['assunto_id', 'caderno']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('assunto_incidencias');
    }
};
