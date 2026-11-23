<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Material de resumo: um documento de revisão por aluno e disciplina, dividido em seções
 * (um caderno de erros, um bloco de assuntos) e "pontos" (o que o aluno ainda erra: armadilha,
 * regra certa, macete, base legal). O conteúdo vem de um JSON importado ou é digitado na tela;
 * o status de cada ponto (errando/revisando/dominado) acompanha o aluno ao enriquecer o material.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->create('materiais', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->foreignId('disciplina_id')->nullable()->constrained('disciplinas')->nullOnDelete();
            $t->string('slug', 80);
            $t->string('titulo');
            $t->text('descricao')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'slug']);
        });

        $schema->create('material_secoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_id')->constrained('materiais')->cascadeOnDelete();
            $t->string('chave', 40);
            $t->string('titulo');
            $t->string('origem')->nullable(); // ex.: "Caderno C04 · 22 questões · 8 erros"
            $t->text('intro')->nullable();    // markdown
            $t->unsignedSmallInteger('ordem')->default(0);
            $t->timestamps();
            $t->unique(['material_id', 'chave']);
        });

        $schema->create('material_pontos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('secao_id')->constrained('material_secoes')->cascadeOnDelete();
            $t->string('chave', 60)->nullable(); // id estável para reimportar sem duplicar
            $t->string('titulo');
            $t->text('armadilha')->nullable();   // markdown: o que a banca fez / onde eu caí
            $t->text('regra');                   // markdown: a regra certa
            $t->text('macete')->nullable();      // markdown
            $t->text('base_legal')->nullable();  // markdown com [[ctn 98]] etc.
            $t->json('questoes')->nullable();    // ids do TEC
            $t->string('status', 10)->default('errando'); // errando | revisando | dominado
            $t->unsignedSmallInteger('vezes_errado')->default(1);
            $t->date('ultimo_erro_em')->nullable();
            $t->string('origem', 10)->default('manual'); // caderno | manual
            $t->unsignedSmallInteger('ordem')->default(0);
            $t->timestamps();
            $t->unique(['secao_id', 'chave']);
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->dropIfExists('material_pontos');
        $schema->dropIfExists('material_secoes');
        $schema->dropIfExists('materiais');
    }
};
