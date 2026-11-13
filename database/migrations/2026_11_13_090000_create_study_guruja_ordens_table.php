<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ordem das pendentes da meta da Guruja escolhida pelo aluno no card (↕ Organizar). Fica separada da foto
 * da meta, que a sincronização substitui a cada hora. Vale só para a meta `target_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_guruja_ordens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('target_id');
            $table->json('codigos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_guruja_ordens');
    }
};
