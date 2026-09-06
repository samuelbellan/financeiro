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
        // 1. Catálogo de Exercícios
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nome');
            $table->string('slug')->nullable();
            $table->string('categoria'); // musculacao, corrida, alongamento, cardio, funcional
            $table->string('grupo_muscular_primario'); // peito, costas, quadriceps, etc.
            $table->json('grupo_muscular_secundario')->nullable();
            $table->string('tipo_medicao')->default('peso_e_reps'); // peso_e_reps, tempo_e_distancia, tempo_apenas, etc.
            $table->string('equipamento')->nullable(); // barra, halteres, maquina, polia, peso_corporal, etc.
            $table->text('instrucoes_execucao')->nullable();
            $table->string('link_video_demonstrativo')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['categoria', 'grupo_muscular_primario']);
        });

        // 2. Fichas e Planilhas de Treino
        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nome');
            $table->string('identificador_letra', 10)->nullable(); // A, B, C, Upper, Lower
            $table->string('modalidade')->default('musculacao'); // musculacao, corrida, alongamento, misto
            $table->unsignedInteger('frequencia_semanal_sugerida')->nullable();
            $table->json('dias_semana_recomendados')->nullable();
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // 3. Itens do Plano (Exercícios prescritos na ficha)
        Schema::create('workout_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_plan_id')->constrained('workout_plans')->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained('exercises')->cascadeOnDelete();
            $table->unsignedInteger('ordem')->default(1);
            $table->unsignedInteger('series_planejadas')->nullable()->default(3);
            $table->unsignedInteger('reps_min')->nullable();
            $table->unsignedInteger('reps_max')->nullable();
            $table->decimal('carga_alvo_kg', 8, 2)->nullable();
            $table->unsignedInteger('tempo_alvo_segundos')->nullable();
            $table->unsignedInteger('distancia_alvo_metros')->nullable();
            $table->unsignedInteger('tempo_descanso_segundos')->nullable()->default(60);
            $table->string('tecnica_avancada')->nullable(); // drop-set, rest-pause, bi-set
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        // 4. Equipamentos e Tênis (Gear Tracking)
        Schema::create('gear_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo')->default('tenis_corrida'); // tenis_corrida, acessorio, etc.
            $table->string('marca');
            $table->string('modelo');
            $table->decimal('quilometragem_inicial_km', 8, 2)->default(0);
            $table->decimal('quilometragem_acumulada_km', 8, 2)->default(0);
            $table->decimal('vida_util_limite_km', 8, 2)->nullable(); // ex: 800 km
            $table->date('data_aquisicao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // 5. Sessões de Treino Executadas
        Schema::create('workout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('workout_plan_id')->nullable()->constrained('workout_plans')->nullOnDelete();
            $table->string('nome_sessao');
            $table->string('modalidade')->default('musculacao'); // musculacao, corrida, alongamento, misto, outro
            $table->dateTime('data_hora_inicio');
            $table->dateTime('data_hora_fim')->nullable();
            $table->unsignedInteger('duracao_minutos')->nullable();
            $table->unsignedTinyInteger('esforco_percebido_rpe')->nullable(); // 1 a 10
            $table->unsignedTinyInteger('nivel_energia_humor')->nullable(); // 1 a 5
            $table->unsignedInteger('calorias_queimadas')->nullable();
            $table->text('observacoes')->nullable();
            $table->string('status')->default('concluido'); // em_andamento, concluido, cancelado
            $table->timestamps();

            $table->index(['user_id', 'data_hora_inicio']);
        });

        // 6. Exercícios da Sessão
        Schema::create('workout_session_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_id')->constrained('workout_sessions')->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained('exercises')->cascadeOnDelete();
            $table->unsignedInteger('ordem')->default(1);
            $table->string('observacoes')->nullable();
            $table->timestamps();
        });

        // 7. Séries Executadas na Musculação
        Schema::create('workout_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_exercise_id')->constrained('workout_session_exercises')->cascadeOnDelete();
            $table->unsignedInteger('numero_serie');
            $table->string('tipo_serie')->default('normal'); // normal, aquecimento, preparatoria, top_set, backoff_set, dropset, falha
            $table->decimal('carga_kg', 8, 2)->default(0);
            $table->unsignedInteger('repeticoes')->default(0);
            $table->decimal('rpe', 3, 1)->nullable();
            $table->unsignedTinyInteger('rir')->nullable(); // reps in reserve
            $table->unsignedInteger('tempo_descanso_segundos')->nullable();
            $table->decimal('um_rm_estimado', 8, 2)->nullable();
            $table->decimal('volume_kg', 10, 2)->nullable();
            $table->boolean('concluida')->default(true);
            $table->timestamps();
        });

        // 8. Dados Detalhados de Corrida & Cardio
        Schema::create('running_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_id')->unique()->constrained('workout_sessions')->cascadeOnDelete();
            $table->foreignId('gear_item_id')->nullable()->constrained('gear_items')->nullOnDelete();
            $table->string('tipo_treino')->default('rodagem'); // regenerativo, ritmo_tempo_run, intervalado_tiros, longao, subida, prova_competicao, esteira_indoor, outro
            $table->decimal('distancia_km', 8, 3);
            $table->unsignedInteger('duracao_segundos');
            $table->unsignedInteger('pace_medio_segundos_por_km'); // ex: 300 = 5'00"/km
            $table->unsignedInteger('pace_maximo_segundos_por_km')->nullable();
            $table->decimal('velocidade_media_kmh', 5, 2)->nullable();
            $table->unsignedSmallInteger('frequencia_cardiaca_media')->nullable();
            $table->unsignedSmallInteger('frequencia_cardiaca_maxima')->nullable();
            $table->json('tempo_zona_cardio')->nullable(); // Z1 a Z5
            $table->integer('elevacao_ganho_metros')->nullable();
            $table->integer('elevacao_perda_metros')->nullable();
            $table->unsignedSmallInteger('cadencia_media_spm')->nullable();
            $table->decimal('comprimento_passo_metros', 4, 2)->nullable();
            $table->string('terreno')->default('asfalto'); // asfalto, esteira, trilha_terra, pista_atletismo, misto
            $table->decimal('clima_temperatura_celsius', 4, 1)->nullable();
            $table->string('clima_condicao')->nullable();
            $table->text('gpx_polyline_data')->nullable();
            $table->timestamps();
        });

        // 9. Parciais e Voltas de Corrida (Splits km a km)
        Schema::create('running_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('running_log_id')->constrained('running_logs')->cascadeOnDelete();
            $table->unsignedInteger('quilometro');
            $table->unsignedInteger('tempo_segundos');
            $table->unsignedInteger('pace_segundos_por_km');
            $table->unsignedSmallInteger('frequencia_cardiaca_media')->nullable();
            $table->integer('elevacao_ganho_metros')->nullable();
            $table->timestamps();
        });

        // 10. Alongamento e Mobilidade
        Schema::create('stretching_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_id')->unique()->constrained('workout_sessions')->cascadeOnDelete();
            $table->string('tipo_sessao')->default('mobilidade_articular'); // pre_treino_dinamico, pos_treino_estatico, mobilidade_articular, yoga_flexibilidade, liberacao_miofascial
            $table->json('foco_anatomico')->nullable(); // ["quadril", "toracica", "tornozelo", "posterior"]
            $table->unsignedTinyInteger('nivel_rigidez_inicial')->default(3); // 1 a 5
            $table->unsignedTinyInteger('nivel_rigidez_final')->default(2); // 1 a 5
            $table->text('sensacao_alivio')->nullable();
            $table->timestamps();
        });

        // 11. Métricas Corporais e Antropometria
        Schema::create('user_body_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('data_medicao');
            $table->decimal('peso_kg', 6, 2);
            $table->decimal('altura_cm', 5, 1)->nullable();
            $table->decimal('percentual_gordura', 5, 2)->nullable();
            $table->decimal('massa_muscular_kg', 6, 2)->nullable();
            
            // Circunferências em cm
            $table->decimal('pescoco_cm', 5, 2)->nullable();
            $table->decimal('torax_cm', 5, 2)->nullable();
            $table->decimal('braco_relaxado_dir_cm', 5, 2)->nullable();
            $table->decimal('braco_relaxado_esq_cm', 5, 2)->nullable();
            $table->decimal('braco_contraido_dir_cm', 5, 2)->nullable();
            $table->decimal('braco_contraido_esq_cm', 5, 2)->nullable();
            $table->decimal('antebraco_dir_cm', 5, 2)->nullable();
            $table->decimal('antebraco_esq_cm', 5, 2)->nullable();
            $table->decimal('cintura_cm', 5, 2)->nullable();
            $table->decimal('abdome_cm', 5, 2)->nullable();
            $table->decimal('quadril_cm', 5, 2)->nullable();
            $table->decimal('coxa_proximal_dir_cm', 5, 2)->nullable();
            $table->decimal('coxa_proximal_esq_cm', 5, 2)->nullable();
            $table->decimal('panturrilha_dir_cm', 5, 2)->nullable();
            $table->decimal('panturrilha_esq_cm', 5, 2)->nullable();

            $table->string('foto_frente_path')->nullable();
            $table->string('foto_lado_path')->nullable();
            $table->string('foto_costas_path')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'data_medicao']);
        });

        // 12. Recordes Pessoais (PRs)
        Schema::create('exercise_personal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained('exercises')->nullOnDelete();
            $table->foreignId('workout_session_id')->nullable()->constrained('workout_sessions')->nullOnDelete();
            $table->string('modalidade'); // musculacao, corrida
            $table->string('tipo_recorde'); // carga_maxima_1rm, maior_peso_serie, maior_volume_exercicio, melhor_tempo_5k, etc.
            $table->decimal('valor_numerico', 10, 3);
            $table->string('valor_formatado'); // ex: "120 kg", "22m 15s"
            $table->date('data_recorde');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'modalidade', 'tipo_recorde']);
        });

        // 13. Metas de Treino
        Schema::create('workout_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('titulo');
            $table->string('modalidade')->default('geral'); // musculacao, corrida, alongamento, geral
            $table->string('tipo_meta')->default('quantidade_sessoes'); // distancia_km, quantidade_sessoes, minutos_totais
            $table->string('periodo')->default('semanal'); // semanal, mensal
            $table->decimal('valor_alvo', 8, 2);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_goals');
        Schema::dropIfExists('exercise_personal_records');
        Schema::dropIfExists('user_body_metrics');
        Schema::dropIfExists('stretching_logs');
        Schema::dropIfExists('running_splits');
        Schema::dropIfExists('running_logs');
        Schema::dropIfExists('workout_sets');
        Schema::dropIfExists('workout_session_exercises');
        Schema::dropIfExists('workout_sessions');
        Schema::dropIfExists('gear_items');
        Schema::dropIfExists('workout_plan_items');
        Schema::dropIfExists('workout_plans');
        Schema::dropIfExists('exercises');
    }
};
