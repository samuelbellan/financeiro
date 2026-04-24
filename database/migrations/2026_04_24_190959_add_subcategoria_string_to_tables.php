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
        Schema::table('transacoes', function (Blueprint $table) {
            $table->string('subcategoria')->nullable()->after('categoria');
        });
        Schema::table('transacao_previsoes', function (Blueprint $table) {
            $table->string('subcategoria')->nullable()->after('categoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transacoes', function (Blueprint $table) {
            $table->dropColumn('subcategoria');
        });
        Schema::table('transacao_previsoes', function (Blueprint $table) {
            $table->dropColumn('subcategoria');
        });
    }
};
