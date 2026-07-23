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
            $table->decimal('carga_seg', 4, 2)->default(2.00);
            $table->decimal('carga_ter', 4, 2)->default(2.00);
            $table->decimal('carga_qua', 4, 2)->default(2.00);
            $table->decimal('carga_qui', 4, 2)->default(2.00);
            $table->decimal('carga_sex', 4, 2)->default(2.00);
            $table->decimal('carga_sab', 4, 2)->default(2.00);
            $table->decimal('carga_dom', 4, 2)->default(2.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_goals', function (Blueprint $table) {
            $table->dropColumn([
                'carga_seg', 'carga_ter', 'carga_qua', 'carga_qui', 'carga_sex', 'carga_sab', 'carga_dom'
            ]);
        });
    }
};
