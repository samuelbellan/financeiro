<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada resposta do aluno a uma questão. É a matéria-prima dos índices de domínio:
 * acerto com certeza "chute" não conta como domínio, e o motivo do erro orienta a revisão.
 * user_id é referência lógica ao banco principal (sem FK entre bancos).
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('tentativas', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->foreignId('questao_id')->constrained('questoes')->cascadeOnDelete();
            $t->string('resposta', 6);   // A-E | Certo | Errado
            $t->string('gabarito', 6);   // o gabarito no momento da resposta (o TEC pode corrigir depois)
            $t->boolean('correta');
            $t->string('certeza', 6);    // sei | acho | chute
            $t->string('motivo_erro', 20)->nullable(); // ver Tentativa::MOTIVOS
            $t->unsignedInteger('tempo_segundos')->nullable();
            $t->string('origem', 20)->default('avulsa'); // avulsa | meta | revisao
            $t->timestamps();
            $t->index(['user_id', 'questao_id']);
            $t->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('tentativas');
    }
};
