<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aula em PDF dentro de um capítulo do livro (ex.: caderno de TI): o PDF original fica como
 * anexo do material e aparece abaixo do texto do capítulo (concursos:material-pdfs).
 */
return new class extends Migration
{
    protected $connection = 'concursos_pgsql';

    public function up(): void
    {
        Schema::connection($this->connection)->table('material_capitulos', function (Blueprint $t) {
            $t->foreignId('pdf_anexo_id')->nullable()->constrained('material_anexos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('material_capitulos', function (Blueprint $t) {
            $t->dropConstrainedForeignId('pdf_anexo_id');
        });
    }
};
