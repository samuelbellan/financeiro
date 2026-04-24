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
        Schema::create('cartao_parcelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartao_compra_id')->constrained('cartao_compras')->onDelete('cascade');
            $table->integer('numero_parcela');
            $table->decimal('valor_parcela', 15, 2);
            $table->date('data_vencimento');
            $table->enum('status', ['aberta', 'paga'])->default('aberta');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartao_parcelas');
    }
};
