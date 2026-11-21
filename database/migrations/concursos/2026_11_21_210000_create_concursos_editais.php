<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edital verticalizado: edital → cargo → área (quadro de provas: nº de questões e pontos)
 * → disciplina → tópico (texto literal). O edital é a "lente" sobre a taxonomia canônica:
 * cada tópico se liga N:N a assuntos do TEC (sugerido pelo Gemini, confirmado na curadoria).
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->create('editais', function (Blueprint $t) {
            $t->id();
            $t->string('orgao', 150);
            $t->string('numero', 150);
            $t->string('banca', 120);
            $t->date('data_publicacao')->nullable();
            $t->date('data_prova')->nullable();
            $t->unsignedSmallInteger('duracao_min')->nullable();
            $t->string('tipo_questao', 6)->nullable(); // ME5 | ME4 | CE | misto
            $t->text('nota_minima')->nullable();
            $t->timestamps();
            $t->unique(['orgao', 'numero']);
        });

        $schema->create('edital_cargos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('edital_id')->constrained('editais')->cascadeOnDelete();
            $t->string('codigo', 30);
            $t->string('nome', 250);
            $t->unsignedInteger('vagas')->nullable();
            $t->string('nivel', 12)->nullable();
            $t->foreignId('importacao_id')->nullable()->constrained('importacoes')->nullOnDelete();
            $t->timestamps();
            $t->unique(['edital_id', 'codigo']);
        });

        $schema->create('edital_areas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cargo_id')->constrained('edital_cargos')->cascadeOnDelete();
            $t->string('nome', 150);
            $t->unsignedSmallInteger('n_questoes');
            $t->decimal('pontos_por_questao', 6, 2);
            $t->decimal('minimo_pontos', 6, 2)->nullable();
            $t->unsignedSmallInteger('posicao');
            $t->timestamps();
            $t->unique(['cargo_id', 'nome']);
        });

        $schema->create('edital_disciplinas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('area_id')->constrained('edital_areas')->cascadeOnDelete();
            $t->string('nome', 250);
            $t->unsignedSmallInteger('posicao');
            $t->timestamps();
            $t->unique(['area_id', 'nome']);
        });

        $schema->create('edital_topicos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('disciplina_id')->constrained('edital_disciplinas')->cascadeOnDelete();
            $t->string('ordem', 20); // "6.2"
            $t->text('texto');
            $t->json('normas')->nullable();
            $t->boolean('sem_questoes_tec')->default(false);
            $t->unsignedSmallInteger('posicao');
            $t->timestamps();
            $t->unique(['disciplina_id', 'ordem']);
        });

        $schema->create('edital_topico_assunto', function (Blueprint $t) {
            $t->foreignId('topico_id')->constrained('edital_topicos')->cascadeOnDelete();
            $t->foreignId('assunto_id')->constrained('assuntos')->cascadeOnDelete();
            $t->string('status', 12)->default('sugerido'); // sugerido | confirmado
            $t->timestamps();
            $t->primary(['topico_id', 'assunto_id']);
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        foreach (['edital_topico_assunto', 'edital_topicos', 'edital_disciplinas', 'edital_areas', 'edital_cargos', 'editais'] as $tabela) {
            $schema->dropIfExists($tabela);
        }
    }
};
