<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fiscal_concursos', function (Blueprint $table) {
            $table->string('regiao', 50)->nullable()->after('uf')->index(); // Sudeste, Sul, Nordeste, Centro-Oeste, Norte
            $table->string('ultimo_concurso_status_vigencia', 50)->nullable()->default('vencido')->after('ultimo_concurso_link')->index(); // vencido, vigente, prorrogado, edital_aberto, sem_concurso_valido
            $table->string('ultimo_concurso_validade_fim', 50)->nullable()->after('ultimo_concurso_status_vigencia'); // Ex: '12/2026', '2024-12-31', 'Expirado em 2017'
            $table->text('ultimo_concurso_vigencia_detalhes')->nullable()->after('ultimo_concurso_validade_fim'); // Ex: 'Homologado em 12/2022 com validade até 12/2024, prorrogado por mais 2 anos até 12/2026.'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiscal_concursos', function (Blueprint $table) {
            $table->dropColumn([
                'regiao',
                'ultimo_concurso_status_vigencia',
                'ultimo_concurso_validade_fim',
                'ultimo_concurso_vigencia_detalhes',
            ]);
        });
    }
};
