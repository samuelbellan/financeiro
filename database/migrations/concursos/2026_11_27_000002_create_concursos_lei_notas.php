<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notas num ponto do texto da lei seca (ícone no artigo que abre a nota com texto e tinta da caneta).
 * Como os grifos, não guardam posição: guardam ~30 caracteres antes e depois do ponto e são
 * reposicionadas no texto atual (Reancorador::localizarPonto). Se o ponto sumir, a nota vai para o
 * fim do artigo, nunca é perdida.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('lei_notas', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('lei', 30);
            $t->string('ancora', 40);
            $t->string('antes', 120)->default('');
            $t->string('depois', 120)->default('');
            $t->text('texto')->nullable();
            $t->json('tracos')->nullable();
            $t->unsignedInteger('altura')->default(420);
            $t->timestamps();
            $t->index(['user_id', 'lei']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('lei_notas');
    }
};
