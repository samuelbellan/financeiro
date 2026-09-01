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
        Schema::table('study_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('study_goal_id')->nullable()->change();
        });

        Schema::table('study_goals', function (Blueprint $table) {
            $table->decimal('horas_meta', 8, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('study_goal_id')->nullable(false)->change();
        });

        Schema::table('study_goals', function (Blueprint $table) {
            $table->decimal('horas_meta', 8, 2)->nullable(false)->change();
        });
    }
};
