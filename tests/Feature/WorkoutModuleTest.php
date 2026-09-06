<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\ExercisePersonalRecord;
use App\Models\GearItem;
use App\Models\RunningLog;
use App\Models\RunningSplit;
use App\Models\StretchingLog;
use App\Models\User;
use App\Models\UserBodyMetric;
use App\Models\WorkoutGoal;
use App\Models\WorkoutPlan;
use App\Models\WorkoutPlanItem;
use App\Models\WorkoutSession;
use App\Models\WorkoutSessionExercise;
use App\Models\WorkoutSet;
use Database\Seeders\ExerciseCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'name' => 'Atleta Teste',
            'email' => 'atleta@teste.com',
        ]);

        $this->seed(ExerciseCatalogSeeder::class);
    }

    public function test_exercise_catalog_is_seeded_and_categorized()
    {
        $this->assertGreaterThanOrEqual(30, Exercise::count());
        $this->assertGreaterThanOrEqual(20, Exercise::musculacao()->count());
        $this->assertGreaterThanOrEqual(4, Exercise::corrida()->count());
        $this->assertGreaterThanOrEqual(5, Exercise::alongamento()->count());

        $supino = Exercise::where('nome', 'Supino Reto com Barra')->first();
        $this->assertNotNull($supino);
        $this->assertEquals('peito', $supino->grupo_muscular_primario);
        $this->assertContains('triceps', $supino->grupo_muscular_secundario);
    }

    public function test_can_create_workout_plan_with_items()
    {
        $plan = WorkoutPlan::create([
            'user_id' => $this->user->id,
            'nome' => 'Treino A - Peito e Tríceps',
            'identificador_letra' => 'A',
            'modalidade' => 'musculacao',
            'frequencia_semanal_sugerida' => 2,
        ]);

        $supino = Exercise::where('nome', 'Supino Reto com Barra')->first();

        $item = WorkoutPlanItem::create([
            'workout_plan_id' => $plan->id,
            'exercise_id' => $supino->id,
            'ordem' => 1,
            'series_planejadas' => 4,
            'reps_min' => 8,
            'reps_max' => 10,
            'carga_alvo_kg' => 80.00,
            'tempo_descanso_segundos' => 90,
        ]);

        $this->assertCount(1, $plan->items);
        $this->assertEquals('Supino Reto com Barra', $plan->items->first()->exercise->nome);
        $this->assertEquals(80.00, (float)$plan->items->first()->carga_alvo_kg);
    }

    public function test_workout_sets_automatically_calculate_volume_and_one_rep_max()
    {
        $session = WorkoutSession::create([
            'user_id' => $this->user->id,
            'nome_sessao' => 'Treino Peitoral Pesado',
            'modalidade' => 'musculacao',
            'data_hora_inicio' => now(),
            'duracao_minutos' => 55,
            'esforco_percebido_rpe' => 8,
        ]);

        $supino = Exercise::where('nome', 'Supino Reto com Barra')->first();

        $sessionExercise = WorkoutSessionExercise::create([
            'workout_session_id' => $session->id,
            'exercise_id' => $supino->id,
            'ordem' => 1,
        ]);

        // Série 1: 100kg x 1 rep -> 1RM = 100kg, Volume = 100kg
        $set1 = WorkoutSet::create([
            'workout_session_exercise_id' => $sessionExercise->id,
            'numero_serie' => 1,
            'carga_kg' => 100.00,
            'repeticoes' => 1,
        ]);

        $this->assertEquals(100.00, (float)$set1->volume_kg);
        $this->assertEquals(100.00, (float)$set1->um_rm_estimado);

        // Série 2: 80kg x 10 reps -> Volume = 800kg, 1RM Epley = 80 * (1 + 10/30) = 80 * 1.3333 = 106.67kg
        $set2 = WorkoutSet::create([
            'workout_session_exercise_id' => $sessionExercise->id,
            'numero_serie' => 2,
            'carga_kg' => 80.00,
            'repeticoes' => 10,
        ]);

        $this->assertEquals(800.00, (float)$set2->volume_kg);
        $this->assertEquals(106.67, (float)$set2->um_rm_estimado);

        // Volume total da sessão
        $this->assertEquals(900.00, $session->volume_total_kg);
    }

    public function test_running_log_calculates_pace_speed_and_increments_shoe_mileage()
    {
        $tenis = GearItem::create([
            'user_id' => $this->user->id,
            'tipo' => 'tenis_corrida',
            'marca' => 'Nike',
            'modelo' => 'Pegasus 40',
            'quilometragem_inicial_km' => 50.00,
            'quilometragem_acumulada_km' => 0.00,
            'vida_util_limite_km' => 800.00,
        ]);

        $session = WorkoutSession::create([
            'user_id' => $this->user->id,
            'nome_sessao' => 'Treino de Ritmo 10k',
            'modalidade' => 'corrida',
            'data_hora_inicio' => now(),
            'duracao_minutos' => 50,
        ]);

        // 10km em 3000 segundos (50 minutos) -> Pace = 300s (5'00"/km), Velocidade = 12 km/h
        $run = RunningLog::create([
            'workout_session_id' => $session->id,
            'gear_item_id' => $tenis->id,
            'tipo_treino' => 'ritmo_tempo_run',
            'distancia_km' => 10.000,
            'duracao_segundos' => 3000,
            'frequencia_cardiaca_media' => 162,
        ]);

        $this->assertEquals(300, $run->pace_medio_segundos_por_km);
        $this->assertEquals("5'00\"/km", $run->pace_formatado);
        $this->assertEquals(12.00, (float)$run->velocidade_media_kmh);
        $this->assertEquals("50:00", $run->duracao_formatada);

        // Verifica splits da corrida
        RunningSplit::create([
            'running_log_id' => $run->id,
            'quilometro' => 1,
            'tempo_segundos' => 305,
            'pace_segundos_por_km' => 305,
        ]);

        $this->assertCount(1, $run->splits);
        $this->assertEquals("5'05\"", $run->splits->first()->pace_formatado);

        // Verifica que o tênis teve sua quilometragem incrementada
        $tenis->refresh();
        $this->assertEquals(10.00, (float)$tenis->quilometragem_acumulada_km);
        $this->assertEquals(60.00, $tenis->quilometragem_total);
        $this->assertEquals('normal', $tenis->alerta_desgaste);
    }

    public function test_stretching_log_tracks_relief_delta()
    {
        $session = WorkoutSession::create([
            'user_id' => $this->user->id,
            'nome_sessao' => 'Mobilidade e Descompressão Lombar',
            'modalidade' => 'alongamento',
            'data_hora_inicio' => now(),
            'duracao_minutos' => 20,
        ]);

        $stretch = StretchingLog::create([
            'workout_session_id' => $session->id,
            'tipo_sessao' => 'mobilidade_articular',
            'foco_anatomico' => ['quadril', 'toracica', 'lombar'],
            'nivel_rigidez_inicial' => 5,
            'nivel_rigidez_final' => 2,
            'sensacao_alivio' => 'Quadril muito mais solto, sem dores na lombar.',
        ]);

        $this->assertEquals(3, $stretch->delta_alivio);
    }

    public function test_user_body_metrics_calculates_imc_and_fat_mass()
    {
        $metric = UserBodyMetric::create([
            'user_id' => $this->user->id,
            'data_medicao' => now()->toDateString(),
            'peso_kg' => 80.00,
            'altura_cm' => 180.0,
            'percentual_gordura' => 15.00,
            'cintura_cm' => 82.0,
            'braco_contraido_dir_cm' => 39.5,
        ]);

        // Altura 1.80m, Peso 80kg -> IMC = 80 / (1.80 * 1.80) = 24.7
        $this->assertEquals(24.7, $metric->imc);
        // Gordura 15% de 80kg = 12kg
        $this->assertEquals(12.00, $metric->massa_gorda_kg);
    }

    public function test_exercise_personal_records_and_workout_goals()
    {
        $supino = Exercise::where('nome', 'Supino Reto com Barra')->first();

        $pr = ExercisePersonalRecord::create([
            'user_id' => $this->user->id,
            'exercise_id' => $supino->id,
            'modalidade' => 'musculacao',
            'tipo_recorde' => 'carga_maxima_1rm',
            'valor_numerico' => 120.00,
            'valor_formatado' => '120 kg',
            'data_recorde' => now()->toDateString(),
        ]);

        $this->assertEquals(1, ExercisePersonalRecord::musculacao()->count());
        $this->assertEquals('120 kg', $pr->valor_formatado);

        $goal = WorkoutGoal::create([
            'user_id' => $this->user->id,
            'titulo' => 'Volume Semanal de Corrida',
            'modalidade' => 'corrida',
            'tipo_meta' => 'distancia_km',
            'periodo' => 'semanal',
            'valor_alvo' => 25.00,
        ]);

        $this->assertEquals(1, WorkoutGoal::ativo()->count());
        $this->assertEquals(25.00, (float)$goal->valor_alvo);
    }

    public function test_treinos_route_requires_authentication()
    {
        $response = $this->get(route('treinos.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_treinos_dashboard()
    {
        $response = $this->actingAs($this->user)->get(route('treinos.index'));
        $response->assertStatus(200);
        $response->assertSee('Treinos & Exercícios Físicos', false);
        $response->assertSee('Sessões nesta Semana', false);
    }
}
