<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Núcleo do banco de concursos: taxonomia canônica (Disciplina → Assunto TEC → Subtópico),
 * questões com alternativas e o registro de cada importação.
 * Banco separado (concursos_pgsql): user_id é referência lógica, sem FK entre bancos.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->create('disciplinas', function (Blueprint $t) {
            $t->id();
            $t->string('nome', 150)->unique();
            $t->timestamps();
        });

        $schema->create('assuntos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('disciplina_id')->constrained('disciplinas')->cascadeOnDelete();
            $t->string('nome', 250);
            // Agrupamento opcional feito na curadoria (ex.: "Prescrição" sob "Parte Geral").
            $t->foreignId('parent_id')->nullable()->constrained('assuntos')->nullOnDelete();
            // Quando o TEC renomeia um assunto, o antigo aponta para o novo (união na curadoria).
            $t->foreignId('unido_em_id')->nullable()->constrained('assuntos')->nullOnDelete();
            $t->timestamps();
            $t->unique(['disciplina_id', 'nome']);
        });

        $schema->create('subtopicos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assunto_id')->constrained('assuntos')->cascadeOnDelete();
            $t->string('slug', 80);
            $t->string('nome', 150);
            $t->text('descricao')->nullable();
            $t->string('status', 12)->default('pendente'); // pendente | aprovado
            $t->unsignedSmallInteger('ordem_estudo')->nullable();
            $t->timestamps();
            $t->unique(['assunto_id', 'slug']);
        });

        $schema->create('importacoes', function (Blueprint $t) {
            $t->id();
            $t->string('tipo', 20); // pdf_tec | ledger | resolucoes | mapa | edital
            $t->string('arquivo_original', 500);
            $t->string('arquivo_salvo', 500); // em storage/concursos/imports
            $t->char('sha256', 64)->index();
            $t->string('referencia', 250)->nullable(); // ex.: título/id do caderno
            $t->string('status', 12); // ok | recusada
            $t->json('resumo')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
        });

        $schema->create('questoes', function (Blueprint $t) {
            $t->id();
            $t->string('fonte', 10)->default('tec'); // tec | inedita
            $t->string('id_externo', 20);
            $t->string('banca', 120);
            $t->string('banca_sigla', 60)->index();
            $t->string('prova', 200)->nullable();
            $t->string('orgao', 150)->nullable();
            $t->string('cargo', 250)->nullable();
            $t->unsignedSmallInteger('ano')->index();
            $t->string('cabecalho', 400)->nullable();
            $t->char('tipo', 2); // ME | CE
            $t->text('enunciado');
            $t->string('gabarito', 6)->nullable(); // A-E | Certo | Errado; null se anulada
            $t->boolean('anulada')->default(false);
            $t->boolean('desatualizada')->default(false);
            $t->foreignId('importacao_id')->nullable()->constrained('importacoes')->nullOnDelete();
            $t->timestamps();
            $t->unique(['fonte', 'id_externo']);
        });

        $schema->create('questao_alternativas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('questao_id')->constrained('questoes')->cascadeOnDelete();
            $t->string('letra', 6);
            $t->text('texto');
            $t->unsignedTinyInteger('ordem');
            $t->unique(['questao_id', 'letra']);
        });

        $schema->create('questao_assunto', function (Blueprint $t) {
            $t->foreignId('questao_id')->constrained('questoes')->cascadeOnDelete();
            $t->foreignId('assunto_id')->constrained('assuntos')->cascadeOnDelete();
            $t->boolean('principal')->default(false);
            $t->primary(['questao_id', 'assunto_id']);
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        foreach (['questao_assunto', 'questao_alternativas', 'questoes', 'importacoes', 'subtopicos', 'assuntos', 'disciplinas'] as $tabela) {
            $schema->dropIfExists($tabela);
        }
    }
};
