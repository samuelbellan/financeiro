<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('fiscal_noticias');
        Schema::dropIfExists('fiscal_concursos');
        Schema::dropIfExists('fiscal_telegram_configs');
    }

    public function down(): void
    {
        // Módulo de concursos fiscais removido.
    }
};
