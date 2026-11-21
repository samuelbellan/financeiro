<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perfil do aluno (cargo-alvo, minutos por dia da semana, o que já viu de cada tópico)
 * e a meta do dia gerada pelo motor: gerada 1× por dia e congelada.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->create('perfis', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->unique();
            $t->foreignId('cargo_id')->constrained('edital_cargos')->cascadeOnDelete();
            $t->json('minutos_semana'); // {"1": 120, ... "7": 0}, ISO: 1 = segunda
            $t->timestamps();
        });

        $schema->create('perfil_topicos', function (Blueprint $t) {
            $t->unsignedBigInteger('user_id');
            $t->foreignId('topico_id')->constrained('edital_topicos')->cascadeOnDelete();
            $t->string('declarado', 10); // nao_vi | vi_parte | vi_tudo
            $t->timestamps();
            $t->primary(['user_id', 'topico_id']);
        });

        $schema->create('metas', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->date('data');
            $t->unsignedSmallInteger('minutos_orcamento');
            $t->timestamps();
            $t->unique(['user_id', 'data']);
        });

        $schema->create('meta_atividades', function (Blueprint $t) {
            $t->id();
            $t->foreignId('meta_id')->constrained('metas')->cascadeOnDelete();
            $t->unsignedSmallInteger('ordem');
            $t->string('tipo', 10); // teoria | questoes | revisao
            $t->foreignId('topico_id')->nullable()->constrained('edital_topicos')->nullOnDelete();
            $t->json('questao_ids')->nullable();
            $t->unsignedSmallInteger('minutos');
            $t->text('motivo');
            $t->string('status', 10)->default('pendente'); // pendente | feita | adiada
            $t->unsignedTinyInteger('adiamentos')->default(0);
            $t->timestamp('concluida_em')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        foreach (['meta_atividades', 'metas', 'perfil_topicos', 'perfis'] as $tabela) {
            $schema->dropIfExists($tabela);
        }
    }
};
