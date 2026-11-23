<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quem segue o planejamento dirigido do concurso (meta do dia, questões, desempenho, simulados, perfil).
 * Sem ele, o módulo de concursos mostra só Resumos e Leis. Alunos novos entram com o plano;
 * o dono não faz o concurso piloto, então começa sem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('concursos_plano')->default(true);
        });

        DB::table('users')->where('is_owner', true)->update(['concursos_plano' => false]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('concursos_plano');
        });
    }
};
