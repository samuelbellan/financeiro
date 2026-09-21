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
        // 1. Registro Diário de Execução (Hábitos, Treino, Dieta e Hidratação)
        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->boolean('workout_done')->default(false);
            $table->string('workout_type')->nullable(); // STRENGTH_CIRCUIT, STREET_RUN, CHEST_UPPER, LEGS_RUN, REST, EXTRA_CARDIO, etc.
            $table->unsignedSmallInteger('workout_duration_min')->nullable();
            $table->boolean('breakfast_clean')->default(false); // Café matinal sem mel/açúcar e equilibrado
            $table->boolean('lunch_clean')->default(true); // Almoço equilibrado
            $table->boolean('snack_done')->default(false); // Lanche vespertino proteico
            $table->boolean('dinner_clean')->default(true); // Jantar leve reparador
            $table->unsignedInteger('water_volume_ml')->default(0); // Meta 3000ml
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['user_id', 'date']);
        });

        // 2. Check-in Antropométrico Periódico (Medidas Corporais)
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('weight_kg', 5, 2);
            $table->decimal('chest_cm', 5, 2);
            $table->decimal('waist_narrow_cm', 5, 2); // Ponto mais estreito, acima do umbigo
            $table->decimal('abdomen_umbilical_cm', 5, 2); // Exato na cicatriz umbilical
            $table->decimal('hips_cm', 5, 2); // Ponto de maior projeção do glúteo
            $table->decimal('arm_right_cm', 5, 2);
            $table->decimal('thigh_right_cm', 5, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });

        // 3. Registro Fotográfico Padronizado de Evolução
        Schema::create('progress_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('measurement_id')->nullable()->constrained('measurements')->nullOnDelete();
            $table->date('date');
            $table->string('angle'); // FRONT, BACK, SIDE_RIGHT, SIDE_LEFT
            $table->string('file_path');
            $table->timestamps();

            $table->index(['user_id', 'date', 'angle']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_photos');
        Schema::dropIfExists('measurements');
        Schema::dropIfExists('daily_logs');
    }
};
