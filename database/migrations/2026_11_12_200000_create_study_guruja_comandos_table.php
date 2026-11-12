<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comando (o que fazer) de cada atividade da Guruja, copiado pelo próprio aluno da janela da atividade
 * no portal (favorito "Enviar comando"). Chave: código da atividade (ex.: DTRIB.3886.115658).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_guruja_comandos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('codigo', 40);
            $table->text('texto');
            $table->timestamps();
            $table->unique(['user_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_guruja_comandos');
    }
};
