<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cartao_faturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cartao_id')->constrained('cartoes')->cascadeOnDelete();
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('ano');
            $table->string('arquivo_path')->nullable();
            $table->string('arquivo_nome_original')->nullable();
            $table->decimal('total_fatura', 12, 2)->default(0);
            $table->decimal('total_despesas', 12, 2)->default(0);
            $table->decimal('total_pagamentos', 12, 2)->default(0);
            $table->unsignedInteger('itens_count')->default(0);
            $table->longText('itens_json')->nullable();
            $table->string('status', 30)->default('pendente'); // 'conciliada', 'pendente'
            $table->decimal('diferenca_total', 12, 2)->default(0);
            $table->unsignedInteger('pendencias_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'cartao_id', 'ano', 'mes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartao_faturas');
    }
};
