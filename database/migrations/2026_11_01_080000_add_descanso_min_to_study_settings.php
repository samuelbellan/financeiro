<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Duração do descanso (minutos) do módulo de estudos: o app avisa (bip + tela piscando) ao se aproximar e ao chegar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('descanso_min')->nullable()->after('retro_sound');
        });
    }

    public function down(): void
    {
        Schema::table('study_settings', function (Blueprint $table) {
            $table->dropColumn('descanso_min');
        });
    }
};
