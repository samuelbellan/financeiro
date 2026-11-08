<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alvo de horas totais da calculadora de projeção (ex.: 2000). Nulo = sem alvo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_settings', function (Blueprint $table) {
            $table->unsignedInteger('alvo_horas')->nullable()->after('tema');
        });
    }

    public function down(): void
    {
        Schema::table('study_settings', function (Blueprint $table) {
            $table->dropColumn('alvo_horas');
        });
    }
};
