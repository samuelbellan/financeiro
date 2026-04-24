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
        Schema::create('cartao_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartao_id')->constrained('cartoes')->onDelete('cascade');
            $table->string('descricao');
            $table->decimal('valor_total', 15, 2);
            $table->enum('tipo', ['avista', 'parcelada', 'recorrente']);
            $table->integer('numero_parcelas')->default(1);
            $table->string('categoria')->nullable();
            $table->date('data_compra');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartao_compras');
    }
};
