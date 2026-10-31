<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sons retrô 8-bit do módulo de estudos (subir de estágio e pausar o foco).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_settings', function (Blueprint $table) {
            $table->boolean('retro_sound')->default(false)->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('study_settings', function (Blueprint $table) {
            $table->dropColumn('retro_sound');
        });
    }
};
