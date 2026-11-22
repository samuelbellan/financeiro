<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simulado cronometrado no formato da prova (quadro de áreas do edital), sem gabarito até a entrega.
 * Na entrega, cada resposta vira uma tentativa (origem "simulado"), que alimenta domínio e revisão.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->create('simulados', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->foreignId('cargo_id')->constrained('edital_cargos')->cascadeOnDelete();
            $t->string('tipo', 10); // completo | rapido
            $t->json('composicao'); // [{area, pontos_por_questao, previstas, questao_ids}]
            $t->json('questao_ids');
            $t->json('respostas')->nullable(); // {questao_id: {r: "A", chute: bool}}
            $t->unsignedSmallInteger('duracao_min');
            $t->timestamp('iniciado_em');
            $t->timestamp('prazo_em');
            $t->timestamp('finalizado_em')->nullable();
            $t->unsignedSmallInteger('acertos')->nullable();
            $t->decimal('nota_total', 6, 2)->nullable();
            $t->decimal('pontos_total', 6, 2)->nullable();
            $t->json('resultado')->nullable(); // por área
            $t->timestamps();
        });

        $schema->table('tentativas', function (Blueprint $t) {
            $t->foreignId('simulado_id')->nullable()->after('origem')->constrained('simulados')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->table('tentativas', function (Blueprint $t) {
            $t->dropConstrainedForeignId('simulado_id');
        });
        $schema->dropIfExists('simulados');
    }
};
