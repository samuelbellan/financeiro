<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Resumo do comando gerado pelo Gemini ao salvar (nulo se a IA não respondeu). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_guruja_comandos', function (Blueprint $table) {
            $table->text('resumo')->nullable()->after('texto');
        });
    }

    public function down(): void
    {
        Schema::table('study_guruja_comandos', function (Blueprint $table) {
            $table->dropColumn('resumo');
        });
    }
};
