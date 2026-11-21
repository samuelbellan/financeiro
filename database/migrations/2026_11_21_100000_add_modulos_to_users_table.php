<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Módulos que cada usuário pode acessar (null = todos).
 * Convidados existentes (nem dono nem gestor) ficam restritos aos módulos de estudo,
 * que é o que eles já usam: o login por convite já os levava direto para /estudos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('modulos')->nullable()->after('is_owner');
        });

        DB::table('users')
            ->where('is_owner', false)
            ->where('is_admin', false)
            ->update(['modulos' => json_encode(['estudos', 'concursos'])]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('modulos');
        });
    }
};
