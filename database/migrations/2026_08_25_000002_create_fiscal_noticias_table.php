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
        Schema::create('fiscal_noticias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_concurso_id')->nullable()->constrained('fiscal_concursos')->nullOnDelete();
            $table->string('titulo', 350);
            $table->text('resumo')->nullable();
            $table->longText('conteudo')->nullable();
            $table->string('url', 700)->unique();
            $table->string('fonte', 100); // Direção Concursos, Estratégia, Gran, QConcursos, DOU, etc.
            $table->enum('esfera', ['federal', 'estadual', 'municipal', 'geral'])->default('geral')->index();
            $table->string('uf', 2)->nullable()->index();
            $table->string('status_detectado', 50)->nullable(); // Edital Publicado, Comissão Formada, etc.
            $table->dateTime('publicado_em')->index();
            $table->boolean('notificado_telegram')->default(false)->index();
            $table->dateTime('notificado_em')->nullable();
            $table->json('dados_remuneracao_snapshot')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_noticias');
    }
};
