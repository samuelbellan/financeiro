<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipos de estudo personalizados: além dos tipos padrão, a sessão aceita "x:<nome>" (até 40 caracteres no nome).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->string('tipo', 60)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->string('tipo', 20)->nullable()->change();
        });
    }
};
