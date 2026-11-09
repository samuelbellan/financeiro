<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tokens pessoais da API de leitura do módulo de estudos (GET /api/estudos/dia).
 * Guarda só o SHA-256 do token: o valor aparece uma única vez, quando é gerado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nome', 60);
            $table->char('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_api_tokens');
    }
};
