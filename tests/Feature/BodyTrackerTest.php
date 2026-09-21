<?php

namespace Tests\Feature;

use App\Models\DailyLog;
use App\Models\Measurement;
use App\Models\ProgressPhoto;
use App\Models\User;
use Database\Seeders\ExerciseCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BodyTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'name' => 'Samuel Atleta',
            'email' => 'samuel@teste.com',
        ]);

        $this->seed(ExerciseCatalogSeeder::class);
    }

    public function test_can_create_and_update_daily_log()
    {
        $today = now()->toDateString();

        $response = $this->actingAs($this->user)->postJson(route('api.tracker.daily-log.save'), [
            'date' => $today,
            'workout_done' => true,
            'workout_type' => 'STRENGTH_CIRCUIT',
            'workout_duration_min' => 35,
            'breakfast_clean' => true,
            'lunch_clean' => true,
            'snack_done' => true,
            'dinner_clean' => true,
            'water_volume_ml' => 3000,
            'notes' => 'Excelente energia no treino.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'adherence_score' => 100,
            'water_progress_percent' => 100,
        ]);

        $this->assertDatabaseHas('daily_logs', [
            'user_id' => $this->user->id,
            'date' => $today,
            'workout_type' => 'STRENGTH_CIRCUIT',
            'water_volume_ml' => 3000,
        ]);

        // Consulta diário de hoje
        $todayResponse = $this->actingAs($this->user)->getJson(route('api.tracker.daily-log.today'));
        $todayResponse->assertStatus(200);
        $todayResponse->assertJson([
            'exists' => true,
            'adherence_score' => 100,
        ]);
    }

    public function test_can_save_anthropometric_measurement_and_calculate_rcq()
    {
        $response = $this->actingAs($this->user)->postJson(route('api.tracker.measurements.save'), [
            'date' => '2026-09-20',
            'weight_kg' => 86.00,
            'chest_cm' => 104.50,
            'waist_narrow_cm' => 94.00,
            'abdomen_umbilical_cm' => 99.50,
            'hips_cm' => 102.00,
            'arm_right_cm' => 36.50,
            'thigh_right_cm' => 59.00,
            'notes' => 'Domingo em jejum pela manhã.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'rcq' => 0.92, // 94.00 / 102.00 = 0.9215... -> 0.92
            'rcq_status' => 'risco_aumentado',
        ]);

        $this->assertDatabaseHas('measurements', [
            'user_id' => $this->user->id,
            'date' => '2026-09-20',
            'weight_kg' => 86.00,
            'waist_narrow_cm' => 94.00,
        ]);
    }

    public function test_summary_deltas_calculates_baseline_and_latest_deltas_correctly()
    {
        // 1. Marco Zero (Semana 1)
        Measurement::create([
            'user_id' => $this->user->id,
            'date' => '2026-09-01',
            'weight_kg' => 88.00,
            'chest_cm' => 104.00,
            'waist_narrow_cm' => 96.00,
            'abdomen_umbilical_cm' => 102.00,
            'hips_cm' => 104.00,
            'arm_right_cm' => 36.00,
            'thigh_right_cm' => 59.00,
        ]);

        // 2. Medição Atual (Semana 4)
        Measurement::create([
            'user_id' => $this->user->id,
            'date' => '2026-09-20',
            'weight_kg' => 85.50, // -2.5 kg
            'chest_cm' => 104.50, // +0.5 cm
            'waist_narrow_cm' => 92.00, // -4.0 cm
            'abdomen_umbilical_cm' => 97.00, // -5.0 cm
            'hips_cm' => 102.00, // -2.0 cm
            'arm_right_cm' => 36.50, // +0.5 cm
            'thigh_right_cm' => 59.00, // 0.0 cm
        ]);

        $response = $this->actingAs($this->user)->getJson(route('api.tracker.measurements.summary-deltas'));
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertTrue($data['has_data']);
        $this->assertEquals(2, $data['total_checkins']);

        // Abdômen Umbilical: de 102 para 97 => delta_abs = -5.00 cm
        $this->assertEquals(-5.00, $data['points']['abdomen_umbilical']['delta_abs']);
        $this->assertTrue($data['points']['abdomen_umbilical']['is_positive']);

        // Cintura Estreita: de 96 para 92 => delta_abs = -4.00 cm
        $this->assertEquals(-4.00, $data['points']['waist_narrow']['delta_abs']);
        $this->assertTrue($data['points']['waist_narrow']['is_positive']);

        // Tórax: de 104 para 104.5 => delta_abs = +0.50 cm (massa preservada)
        $this->assertEquals(0.50, $data['points']['chest']['delta_abs']);
        $this->assertTrue($data['points']['chest']['is_positive']);

        // RCQ atual: 92 / 102 = 0.90 (alvo masculino atingido)
        $this->assertEquals(0.90, $data['rcq']['current']);
        $this->assertEquals('baixo_risco', $data['rcq']['status']);
    }

    public function test_can_upload_and_list_progress_photos()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('frente.jpg', 640, 480);

        $response = $this->actingAs($this->user)->postJson(route('api.tracker.photos.upload'), [
            'date' => '2026-09-20',
            'angle' => 'FRONT',
            'photo' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'angle' => 'FRONT',
                'angle_label' => 'Frente',
            ],
        ]);

        $this->assertDatabaseHas('progress_photos', [
            'user_id' => $this->user->id,
            'angle' => 'FRONT',
        ]);

        // Listar fotos com filtro de ângulo
        $listResponse = $this->actingAs($this->user)->getJson(route('api.tracker.photos.index', ['angle' => 'FRONT']));
        $listResponse->assertStatus(200);
        $this->assertCount(1, $listResponse->json('data'));
    }

    public function test_treinos_dashboard_renders_with_body_tracker_components()
    {
        $response = $this->actingAs($this->user)->get(route('treinos.index'));
        $response->assertStatus(200);
        $response->assertSee('Treinos & Recomposição Corporal', false);
        $response->assertSee('Registro Diário de Hábitos & Dieta', false);
        $response->assertSee('Mapa de Consistência & Adesão aos Dados', false);
        $response->assertSee('daily-log-picker', false);
        $response->assertSee('heatmap-grid-container', false);
        $response->assertSee('Manequim & Antropometria', false);
        $response->assertSee('Fotos de Evolução & Comparador', false);
        $response->assertSee('body-mapper-svg', false);
    }

    public function test_can_get_daily_log_by_specific_date()
    {
        $pastDate = '2026-09-15';

        // 1. Data sem registro deve retornar exists: false e defaults
        $responseEmpty = $this->actingAs($this->user)->getJson(route('api.tracker.daily-log.by-date', ['date' => $pastDate]));
        $responseEmpty->assertStatus(200);
        $responseEmpty->assertJson([
            'exists' => false,
            'data' => [
                'date' => $pastDate,
                'water_volume_ml' => 0,
            ],
        ]);

        // 2. Cria registro para a data passada
        $saveResponse = $this->actingAs($this->user)->postJson(route('api.tracker.daily-log.save'), [
            'date' => $pastDate,
            'workout_done' => true,
            'workout_type' => 'STREET_RUN',
            'workout_duration_min' => 45,
            'breakfast_clean' => true,
            'lunch_clean' => true,
            'snack_done' => true,
            'dinner_clean' => true,
            'water_volume_ml' => 3000,
            'notes' => 'Treino retroativo registrado com sucesso.',
        ]);
        $saveResponse->assertStatus(200);
        $saveResponse->assertJson([
            'success' => true,
            'adherence_score' => 100,
        ]);

        // 3. Consulta novamente a data passada
        $responseFilled = $this->actingAs($this->user)->getJson(route('api.tracker.daily-log.by-date', ['date' => $pastDate]));
        $responseFilled->assertStatus(200);
        $responseFilled->assertJson([
            'exists' => true,
            'data' => [
                'date' => $pastDate,
                'workout_done' => true,
                'workout_type' => 'STREET_RUN',
                'workout_duration_min' => 45,
                'water_volume_ml' => 3000,
            ],
            'adherence_score' => 100,
            'water_progress_percent' => 100,
        ]);
    }

    public function test_daily_log_appends_adherence_score_in_json_and_array()
    {
        $log = DailyLog::create([
            'user_id' => $this->user->id,
            'date' => '2026-09-10',
            'workout_done' => true,
            'breakfast_clean' => true,
            'lunch_clean' => true,
            'snack_done' => false,
            'dinner_clean' => false,
            'water_volume_ml' => 1500,
        ]);

        $array = $log->toArray();
        $this->assertArrayHasKey('adherence_score', $array);
        $this->assertArrayHasKey('water_progress_percent', $array);
        $this->assertEquals(60, $array['adherence_score']); // 3 de 5 hábitos = 60%
        $this->assertEquals(50, $array['water_progress_percent']); // 1500/3000 = 50%
    }
}
