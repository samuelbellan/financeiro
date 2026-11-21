<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liga uma sessão do cronômetro a uma atividade da meta do módulo de concursos ("concursos:123").
 * É uma referência lógica: o módulo de concursos vive em outro banco (Postgres local).
 * Serve só para calibrar o tempo da meta; tempo nunca vira domínio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->string('atividade_ref', 40)->nullable()->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropColumn('atividade_ref');
        });
    }
};
