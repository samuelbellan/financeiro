<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Meses atípicos (entrada, erro de folha) ficam visíveis, mas fora das métricas de erro.
     */
    public function up(): void
    {
        Schema::table('salary_payslips', function (Blueprint $table) {
            $table->boolean('outlier')->default(false)->after('total_liquido');
        });
    }

    public function down(): void
    {
        Schema::table('salary_payslips', function (Blueprint $table) {
            $table->dropColumn('outlier');
        });
    }
};
