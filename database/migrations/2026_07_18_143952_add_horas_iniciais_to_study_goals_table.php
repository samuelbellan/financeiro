<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('study_goals', function (Blueprint $table) {
            $table->decimal('horas_iniciais', 8, 2)->default(0.00)->after('horas_meta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_goals', function (Blueprint $table) {
            $table->dropColumn('horas_iniciais');
        });
    }
};
