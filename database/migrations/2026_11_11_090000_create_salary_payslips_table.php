<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Holerites reais do servidor, um por mês, para comparar com a projeção.
     */
    public function up(): void
    {
        Schema::create('salary_payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mes_ano', 7); // YYYY-MM
            $table->decimal('total_bruto', 12, 2);
            $table->decimal('total_descontos', 12, 2);
            $table->decimal('total_liquido', 12, 2);
            $table->decimal('base_rpps', 12, 2)->nullable();
            $table->decimal('base_irrf', 12, 2)->nullable();
            $table->json('pagamentos');
            $table->json('descontos');
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'mes_ano']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payslips');
    }
};
