<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jornada dos Bosses do módulo de estudos: { inicio, objetivo, minutos } em JSON (null = não começou).
 * O placar (golpes e bosses derrotados) é calculado no aparelho a partir das sessões.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_settings', function (Blueprint $table) {
            $table->json('jornada')->nullable()->after('tema');
        });
    }

    public function down(): void
    {
        Schema::table('study_settings', function (Blueprint $table) {
            $table->dropColumn('jornada');
        });
    }
};
