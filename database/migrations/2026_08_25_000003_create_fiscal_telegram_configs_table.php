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
        Schema::create('fiscal_telegram_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('chat_id', 100)->nullable();
            $table->boolean('notificar_automaticamente')->default(true);
            $table->boolean('notificar_federal')->default(true);
            $table->boolean('notificar_estadual')->default(true);
            $table->boolean('notificar_municipal')->default(true);
            $table->decimal('filtro_salario_minimo', 12, 2)->default(0); // Filtrar concursos com inicial acima de X
            $table->json('ufs_interesse')->nullable(); // Ex: ["SP", "RJ", "MG", "DF"] ou null para todas
            $table->dateTime('ultimo_disparo_em')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_telegram_configs');
    }
};
