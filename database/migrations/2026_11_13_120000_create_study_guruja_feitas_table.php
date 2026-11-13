<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Atividades que o aluno marcou como concluídas no card ("✓ Concluí") antes de a sincronização com a Guruja
 * confirmar. Valem só para a meta `target_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_guruja_feitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('target_id');
            $table->string('codigo', 40);
            $table->timestamps();
            $table->unique(['user_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_guruja_feitas');
    }
};
