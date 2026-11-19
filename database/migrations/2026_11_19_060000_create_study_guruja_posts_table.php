<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache persistente do payload pronto para o carrossel de uma meta concluída da Guruja.
 * Metas concluídas não mudam mais, evitando novas consultas ao MCP da Guruja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_guruja_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('ordem');
            $table->json('payload');
            $table->timestamps();

            $table->unique(['user_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_guruja_posts');
    }
};
