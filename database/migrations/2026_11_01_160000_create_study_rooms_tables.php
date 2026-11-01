<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Salas de estudo do módulo de estudos (estilo YPT): quem entra pelo código vê, de cada membro,
 * o nome, o tempo estudado hoje e se está estudando agora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome', 60);
            $table->string('codigo', 12)->unique();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('study_room_members', function (Blueprint $table) {
            $table->uuid('room_id');
            $table->foreign('room_id')->references('id')->on('study_rooms')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('joined_at')->nullable();
            $table->primary(['room_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_room_members');
        Schema::dropIfExists('study_rooms');
    }
};
