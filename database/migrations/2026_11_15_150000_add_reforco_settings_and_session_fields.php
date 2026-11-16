<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configurações de tempo máximo para reforços (graus leve, médio e intenso) e
 * rastreamento do limite/grau na sessão de estudo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('reforco_leve_min')->nullable()->after('descanso_min');
            $table->unsignedSmallInteger('reforco_medio_min')->nullable()->after('reforco_leve_min');
            $table->unsignedSmallInteger('reforco_intenso_min')->nullable()->after('reforco_medio_min');
            $table->string('reforco_padrao_grau', 10)->nullable()->after('reforco_intenso_min');
        });

        Schema::table('study_sessions', function (Blueprint $table) {
            $table->unsignedSmallInteger('reforco_limite_min')->nullable()->after('tipo');
            $table->string('reforco_grau', 10)->nullable()->after('reforco_limite_min');
        });
    }

    public function down(): void
    {
        Schema::table('study_settings', function (Blueprint $table) {
            $table->dropColumn(['reforco_leve_min', 'reforco_medio_min', 'reforco_intenso_min', 'reforco_padrao_grau']);
        });

        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropColumn(['reforco_limite_min', 'reforco_grau']);
        });
    }
};
