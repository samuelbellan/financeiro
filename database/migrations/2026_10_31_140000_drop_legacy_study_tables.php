<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove o módulo antigo de horas de estudo (study_goals / study_logs).
 * Antes do drop, grava um backup JSON em storage/app/backups/ para permitir restauração manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        $backup = [];
        foreach (['study_goals', 'study_logs'] as $table) {
            if (Schema::hasTable($table)) {
                $backup[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            }
        }

        if (!empty(array_filter($backup))) {
            $dir = storage_path('app/backups');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents(
                $dir . '/study_legacy_' . date('Ymd_His') . '.json',
                json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

        Schema::dropIfExists('study_logs');
        Schema::dropIfExists('study_goals');
    }

    /**
     * Recria apenas a estrutura antiga (vazia). Os dados devem ser restaurados a partir do backup JSON.
     */
    public function down(): void
    {
        Schema::create('study_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nome');
            $table->decimal('horas_meta', 8, 2)->nullable();
            $table->decimal('horas_iniciais', 8, 2)->default(0.00);
            $table->date('data_inicio');
            $table->date('data_limite')->nullable();
            $table->decimal('horas_diarias_padrao', 4, 2)->default(2.00);
            foreach (['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'] as $dia) {
                $table->decimal("carga_{$dia}", 4, 2)->default(2.00);
            }
            $table->timestamps();
        });

        Schema::create('study_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('study_goal_id')->nullable();
            $table->date('data');
            $table->decimal('horas', 5, 2);
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }
};
