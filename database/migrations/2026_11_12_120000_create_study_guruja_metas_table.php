<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Última foto da meta em andamento na Guruja (uma linha por usuário). Quem envia é uma rotina externa
 * que lê a Guruja (POST /api/estudos/guruja/meta, token pessoal); o app só exibe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_guruja_metas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->json('payload');
            $table->timestamp('lido_em')->nullable(); // quando a Guruja foi consultada
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_guruja_metas');
    }
};
