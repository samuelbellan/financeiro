<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vade Mecum anotável: marcador e nota do aluno em cada artigo da lei seca. A chave é a sigla da
 * lei no acervo + a âncora do artigo ("ctn" + "art-98", "adct" + "adct-art-136"), estáveis mesmo
 * quando o texto da lei é baixado de novo. user_id é referência lógica ao banco principal.
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('lei_marcas', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('lei', 30);
            $t->string('ancora', 40);
            $t->boolean('marcado')->default(false);
            $t->text('nota')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'lei', 'ancora']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('lei_marcas');
    }
};
