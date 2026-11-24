<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telemetria de uso do módulo: uma linha por tela aberta ou ação feita, com o tempo que a
 * tela ficou visível (enviado pelo navegador ao sair). Não guarda o corpo dos pedidos.
 * user_id é referência lógica ao banco principal (sem FK entre bancos).
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('uso_eventos', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->unsignedBigInteger('user_id');
            $t->string('sessao', 16);           // hash curto da sessão do navegador
            $t->string('rota', 80);             // nome da rota (concursos.meta, ...)
            $t->string('metodo', 6);
            $t->string('caminho', 200);
            $t->json('parametros')->nullable(); // ids da rota e filtros da URL
            $t->unsignedSmallInteger('status');
            $t->unsignedInteger('duracao_ms');  // tempo do servidor
            $t->unsignedInteger('visivel_s')->nullable(); // tempo com a tela à vista
            $t->string('dispositivo', 10);      // celular | computador
            $t->timestamp('created_at');
            $t->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('uso_eventos');
    }
};
