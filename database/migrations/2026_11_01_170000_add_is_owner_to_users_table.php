<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dono do sistema: o único que delega (ou retira) a função de gestor (is_admin).
 * Gestores convidam e removem usuários e criam/gerenciam salas de estudo.
 * O dono é o primeiro administrador (o mesmo critério que criou o primeiro admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_owner')->default(false)->after('is_admin');
        });

        $owner = DB::table('users')->where('is_admin', true)->orderBy('id')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');
        if ($owner) {
            DB::table('users')->where('id', $owner)->update(['is_owner' => true, 'is_admin' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_owner');
        });
    }
};
