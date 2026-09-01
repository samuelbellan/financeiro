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
        Schema::create('notas_fiscais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('transacao_id')->nullable()->constrained('transacoes')->nullOnDelete();
            $table->foreignId('cartao_compra_id')->nullable()->constrained('cartao_compras')->nullOnDelete();
            $table->string('estabelecimento')->nullable();
            $table->dateTime('data_compra')->nullable();
            $table->decimal('valor_total', 10, 2)->default(0.00);
            $table->string('foto_path')->nullable();
            $table->string('forma_pagamento')->default('casa'); // 'casa' ou 'cartao'
            $table->string('cartao_nome')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });

        Schema::table('nota_fiscal_itens', function (Blueprint $table) {
            $table->foreignId('nota_fiscal_id')->nullable()->after('cartao_compra_id')->constrained('notas_fiscais')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nota_fiscal_itens', function (Blueprint $table) {
            $table->dropForeign(['nota_fiscal_id']);
            $table->dropColumn('nota_fiscal_id');
        });

        Schema::dropIfExists('notas_fiscais');
    }
};
