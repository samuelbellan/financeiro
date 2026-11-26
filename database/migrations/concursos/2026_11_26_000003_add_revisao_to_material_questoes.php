<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Área de Questões do material (fila de revisão em caixas) e status calculado pelas tentativas:
 *  - material_questoes.resultado_tec: como a questão saiu no TEC (certa | errada); até aqui só
 *    entravam erradas, então as existentes viram "errada";
 *  - material_questoes.caixa / revisar_em: repetição espaçada (1 → amanhã, 2 → 7 dias, 3 → 30 dias,
 *    4 = saiu da fila). Erradas no TEC começam na caixa 1, vencidas hoje;
 *  - material_tentativas.certeza: sei | acho | chute (acerto no chute não sobe de caixa);
 *  - material_pontos.etiqueta: origem do ponto quando dois materiais se juntam (ex.: "TEC", "Expert").
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->table('material_questoes', function (Blueprint $t) {
            $t->string('resultado_tec', 6)->nullable();
            $t->unsignedTinyInteger('caixa')->nullable();
            $t->date('revisar_em')->nullable();
            $t->index(['material_id', 'revisar_em']);
        });
        $schema->table('material_tentativas', function (Blueprint $t) {
            $t->string('certeza', 6)->nullable();
        });
        $schema->table('material_pontos', function (Blueprint $t) {
            $t->string('etiqueta', 20)->nullable();
        });

        DB::connection($this->connection)->table('material_questoes')
            ->update(['resultado_tec' => 'errada', 'caixa' => 1, 'revisar_em' => now()->toDateString()]);
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->table('material_questoes', function (Blueprint $t) {
            $t->dropIndex(['material_id', 'revisar_em']);
            $t->dropColumn(['resultado_tec', 'caixa', 'revisar_em']);
        });
        $schema->table('material_tentativas', fn (Blueprint $t) => $t->dropColumn('certeza'));
        $schema->table('material_pontos', fn (Blueprint $t) => $t->dropColumn('etiqueta'));
    }
};
