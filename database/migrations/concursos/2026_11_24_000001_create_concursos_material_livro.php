<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Material de resumo em formato de livro: capítulos e subcapítulos (pela árvore de assuntos do TEC
 * ou criados à mão) com texto do editor visual (JSON do TipTap), notas na margem e imagens.
 * Os pontos de erro passam a poder viver dentro de um capítulo (sem seção de caderno).
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->create('material_capitulos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_id')->constrained('materiais')->cascadeOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('material_capitulos')->cascadeOnDelete();
            $t->foreignId('assunto_id')->nullable()->constrained('assuntos')->nullOnDelete();
            $t->string('chave', 80)->nullable(); // origem estável (a12 = assunto, s3 = seção) para converter sem duplicar
            $t->string('titulo');
            $t->unsignedSmallInteger('ordem')->default(0);
            $t->json('conteudo')->nullable();    // documento do editor (TipTap)
            $t->text('texto')->nullable();       // texto puro, para a busca
            $t->timestamps();
            $t->unique(['material_id', 'chave']);
            $t->index(['material_id', 'parent_id', 'ordem']);
        });

        $schema->create('material_anotacoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('capitulo_id')->constrained('material_capitulos')->cascadeOnDelete();
            $t->string('marca', 40);             // id do grifo no texto
            $t->string('cor', 12)->default('amarelo');
            $t->text('trecho')->nullable();
            $t->text('nota');
            $t->timestamps();
            $t->unique(['capitulo_id', 'marca']);
        });

        $schema->create('material_anexos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_id')->constrained('materiais')->cascadeOnDelete();
            $t->string('caminho');
            $t->string('nome')->nullable();
            $t->string('mime', 80);
            $t->unsignedInteger('tamanho');
            $t->timestamps();
        });

        $schema->table('material_pontos', function (Blueprint $t) {
            $t->foreignId('capitulo_id')->nullable()->after('secao_id')->constrained('material_capitulos')->nullOnDelete();
        });
        $schema->table('material_pontos', function (Blueprint $t) {
            $t->unsignedBigInteger('secao_id')->nullable()->change();
        });

        $schema->table('material_questoes', function (Blueprint $t) {
            $t->foreignId('capitulo_id')->nullable()->after('secao_id')->constrained('material_capitulos')->nullOnDelete();
            $t->string('assunto', 500)->nullable()->after('origem');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->table('material_questoes', function (Blueprint $t) {
            $t->dropConstrainedForeignId('capitulo_id');
            $t->dropColumn('assunto');
        });
        $schema->table('material_pontos', function (Blueprint $t) {
            $t->dropConstrainedForeignId('capitulo_id');
        });
        $schema->dropIfExists('material_anexos');
        $schema->dropIfExists('material_anotacoes');
        $schema->dropIfExists('material_capitulos');
    }
};
