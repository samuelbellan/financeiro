<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tempo real cronometrado no /estudos para a atividade (só calibra a meta; nunca vira domínio). */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        Schema::connection($this->connection)->table('meta_atividades', function (Blueprint $t) {
            $t->decimal('minutos_reais', 7, 1)->nullable()->after('minutos');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('meta_atividades', function (Blueprint $t) {
            $t->dropColumn('minutos_reais');
        });
    }
};
