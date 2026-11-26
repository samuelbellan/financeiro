<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grifos do aluno na lei seca. Não guardam posição: guardam o trecho e o contexto em volta
 * (Reancorador), para sobreviver a mudanças no texto do artigo. Se o trecho sumir, o grifo fica
 * órfão e continua listado no artigo, até o aluno apagá-lo.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('lei_grifos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('lei', 30);
            $t->string('ancora', 40);
            $t->string('cor', 12)->default('amarelo');
            $t->text('trecho');
            $t->string('antes', 120)->default('');
            $t->string('depois', 120)->default('');
            $t->timestamps();
            $t->index(['user_id', 'lei']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('lei_grifos');
    }
};
