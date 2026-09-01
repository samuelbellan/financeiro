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
        Schema::create('nota_fiscal_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('transacao_id')->nullable()->constrained('transacoes')->onDelete('cascade');
            $table->foreignId('cartao_compra_id')->nullable()->constrained('cartao_compras')->onDelete('cascade');
            $table->string('estabelecimento')->nullable();
            $table->dateTime('data_compra')->nullable();
            $table->string('nome_item');
            $table->string('categoria_item')->default('Outros');
            $table->decimal('quantidade', 10, 3)->default(1.000);
            $table->decimal('valor_unitario', 10, 2)->default(0.00);
            $table->decimal('valor_total', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nota_fiscal_itens');
    }
};
