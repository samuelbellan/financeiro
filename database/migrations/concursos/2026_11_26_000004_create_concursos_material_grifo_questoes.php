<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grifo do resumo → questões que ele decide (Fase 2 do plano de matérias). Vem dos .jsonl em
 * storage/concursos/grifos/<materia>/ (concursos:importar-grifos). A cor do grifo é calculada
 * na hora pela última tentativa dessas questões, então não fica guardada aqui.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('material_grifo_questoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('capitulo_id')->constrained('material_capitulos')->cascadeOnDelete();
            $t->string('marca', 40);
            $t->foreignId('questao_id')->constrained('material_questoes')->cascadeOnDelete();
            $t->string('letra', 1)->nullable();
            $t->text('por_que')->nullable();
            $t->timestamps();
            $t->unique(['capitulo_id', 'marca', 'questao_id']);
            $t->index('questao_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('material_grifo_questoes');
    }
};
