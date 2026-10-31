<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Novo módulo de estudos (estilo YPT, offline-first).
 * IDs são UUIDs gerados no cliente; updated_at é carimbado pelo servidor e serve de cursor de sync;
 * exclusões são lógicas (deleted_at) para propagarem entre aparelhos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nome', 60);
            $table->char('cor', 7);
            $table->integer('ordem')->default(0);
            $table->timestamp('arquivada_em', 3)->nullable();
            $table->timestamp('created_at', 3)->nullable();
            $table->timestamp('updated_at', 3)->nullable();
            $table->timestamp('deleted_at', 3)->nullable();
            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('study_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('subject_id');
            $table->foreign('subject_id')->references('id')->on('study_subjects')->onDelete('cascade');
            $table->timestamp('inicio', 3);
            $table->timestamp('fim', 3)->nullable();
            $table->string('origem', 10)->default('timer');
            $table->string('status', 10)->default('ok');
            $table->uuid('device_id')->nullable();
            $table->timestamp('created_at', 3)->nullable();
            $table->timestamp('updated_at', 3)->nullable();
            $table->timestamp('deleted_at', 3)->nullable();
            $table->index(['user_id', 'updated_at']);
            $table->index(['user_id', 'inicio']);
        });

        Schema::create('study_settings', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('virada_hora')->default(3);
            $table->unsignedSmallInteger('limite_suspeita_min')->default(180);
            $table->unsignedSmallInteger('meta_diaria_min')->nullable();
            $table->string('timezone', 64)->default('America/Sao_Paulo');
            $table->timestamp('updated_at', 3)->nullable();
        });

        Schema::create('study_ddays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('titulo', 80);
            $table->date('data');
            $table->timestamp('created_at', 3)->nullable();
            $table->timestamp('updated_at', 3)->nullable();
            $table->timestamp('deleted_at', 3)->nullable();
            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_ddays');
        Schema::dropIfExists('study_settings');
        Schema::dropIfExists('study_sessions');
        Schema::dropIfExists('study_subjects');
    }
};
