<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O que volta do Gemini, por questão: a classificação (ledger) e a resolução comentada.
 * Uma linha por questão em cada tabela; reimportar um lote sobrescreve.
 * As explicações das alternativas ficam em JSON na resolução (e não em questao_alternativas)
 * porque reimportar o PDF do TEC recria as alternativas.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->create('questao_classificacoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('questao_id')->unique()->constrained('questoes')->cascadeOnDelete();
            $t->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $t->string('tipologia', 30);
            $t->char('dificuldade', 1); // B | M | A
            $t->string('ponto_cobrado', 300);
            $t->string('comando', 12)->nullable();
            $t->string('distrator_letra', 6)->nullable();
            $t->string('distrator_mecanismo', 300)->nullable();
            $t->json('dispositivos')->nullable();
            $t->json('flags')->nullable();
            $t->string('duplicada_de', 20)->nullable();
            $t->text('observacoes')->nullable();
            $t->foreignId('importacao_id')->nullable()->constrained('importacoes')->nullOnDelete();
            $t->timestamps();
        });

        $schema->create('questao_subtopico', function (Blueprint $t) {
            $t->foreignId('questao_id')->constrained('questoes')->cascadeOnDelete();
            $t->foreignId('subtopico_id')->constrained('subtopicos')->cascadeOnDelete();
            $t->boolean('principal')->default(false);
            $t->primary(['questao_id', 'subtopico_id']);
        });

        $schema->create('questao_resolucoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('questao_id')->unique()->constrained('questoes')->cascadeOnDelete();
            $t->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $t->text('resumo');
            $t->text('fundamentacao')->nullable();
            $t->json('dispositivos')->nullable();
            $t->json('alternativas'); // [{letra, correta, explicacao, palavras_alteradas?}]
            $t->foreignId('importacao_id')->nullable()->constrained('importacoes')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        foreach (['questao_resolucoes', 'questao_subtopico', 'questao_classificacoes'] as $tabela) {
            $schema->dropIfExists($tabela);
        }
    }
};
