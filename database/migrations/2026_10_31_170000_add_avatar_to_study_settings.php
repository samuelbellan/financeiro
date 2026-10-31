<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avatar escolhido no módulo de estudos (evolui com o total estudado no dia).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_settings', function (Blueprint $table) {
            $table->string('avatar', 40)->default('bichinho')->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('study_settings', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }
};
