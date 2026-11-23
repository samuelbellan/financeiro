<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Questões erradas dos cadernos (bateria de retomada cega), para refazer dentro do material de
 * resumo, e as tentativas do aluno. Cada questão pode apontar para o ponto do resumo que a explica.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->create('material_questoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_id')->constrained('materiais')->cascadeOnDelete();
            $t->foreignId('secao_id')->constrained('material_secoes')->cascadeOnDelete();
            $t->foreignId('ponto_id')->nullable()->constrained('material_pontos')->nullOnDelete();
            $t->string('chave', 60);
            $t->string('id_externo', 20)->nullable(); // nº da questão no TEC
            $t->string('origem')->nullable();         // banca / órgão / cargo
            $t->string('tipo', 2);                    // ME (múltipla escolha) | CE (certo/errado)
            $t->text('enunciado');
            $t->json('alternativas')->nullable();     // [{letra, texto}]
            $t->string('gabarito', 1);                // A–E, ou C/E
            $t->text('comentario')->nullable();       // diagnóstico do caderno (texto corrido)
            $t->unsignedSmallInteger('ordem')->default(0);
            $t->timestamps();
            $t->unique(['material_id', 'chave']);
        });

        $schema->create('material_tentativas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('questao_id')->constrained('material_questoes')->cascadeOnDelete();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('resposta', 1);
            $t->boolean('acertou');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['questao_id', 'created_at']);
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->dropIfExists('material_tentativas');
        $schema->dropIfExists('material_questoes');
    }
};
