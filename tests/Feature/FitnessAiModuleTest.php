<?php

namespace Tests\Feature;

use App\Models\DailyLog;
use App\Models\Measurement;
use App\Models\User;
use App\Services\FitnessAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FitnessAiModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'name' => 'Samuel Fitness Test',
            'email' => 'samuel.fit@teste.com',
        ]);
    }

    public function test_can_analyze_meal_deviation_via_gemini()
    {
        // Mock da resposta do Gemini
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'calorias_estimadas' => 920,
                                        'resumo_refeicao' => 'Pizza de calabresa e refrigerante',
                                        'impacto_metabolico' => 'Pico glicêmico rápido e retenção de sódio.',
                                        'ajuste_jantar' => 'Jantar leve com 150g de peito de frango grelhado e folhas verdes.',
                                        'ajuste_lanche' => 'Chá de hibisco ou apenas água.',
                                        'agua_extra_ml' => 800,
                                        'cardio_compensatorio_sugestao' => '30 minutos de caminhada rápida (~180 kcal).',
                                        'dica_mental' => 'Retome o plano com calma no jantar.',
                                        'resumo_para_notas' => 'Almoço livre: 3 fatias de pizza. Compensação: frango + salada à noite e +800ml de água.',
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('treinos.ai.analyze-meal'), [
            'descricao' => 'Almocei 3 fatias de pizza de calabresa e refrigerante',
            'date' => now()->toDateString(),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'calorias_estimadas' => 920,
                'agua_extra_ml' => 800,
            ],
        ]);
    }

    public function test_can_analyze_extra_workout_via_gemini()
    {
        // Mock da resposta do Gemini
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'calorias_gastas_estimadas' => 520,
                                        'intensidade' => 'alta',
                                        'ajuste_nutricional' => 'Consuma 35g de proteína e batata doce no pós-treino para recuperar glicogênio.',
                                        'agua_adicional_ml' => 900,
                                        'recomendacao_dia_seguinte' => 'Descanso ativo para as pernas ou focar em superiores.',
                                        'tempo_recuperacao_horas' => 36,
                                        'dica_performance' => 'Excelente volume aeróbico!',
                                        'resumo_para_notas' => 'Treino extra: 6km corrida (~520 kcal). Reposição: reforço hídrico (+900ml) e repouso.',
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('treinos.ai.analyze-workout'), [
            'descricao' => 'Corri 6 km na rua além da musculação',
            'date' => now()->toDateString(),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'calorias_gastas_estimadas' => 520,
                'intensidade' => 'alta',
            ],
        ]);
    }

    public function test_can_evaluate_daily_review()
    {
        // Cria DailyLog para hoje
        $log = DailyLog::create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'workout_done' => true,
            'workout_type' => 'STRENGTH_CIRCUIT',
            'workout_duration_min' => 45,
            'breakfast_clean' => true,
            'lunch_clean' => true,
            'snack_done' => true,
            'dinner_clean' => true,
            'water_volume_ml' => 3000,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('treinos.ai.daily-review'), [
            'date' => now()->toDateString(),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'score_geral',
                'destaque_positivo',
                'ponto_de_atencao',
                'proximo_passo',
                'frase_do_dia',
            ],
        ]);
    }

    public function test_can_apply_ai_suggestion_to_daily_log()
    {
        $today = now()->toDateString();

        $response = $this->actingAs($this->user)->postJson(route('treinos.ai.apply-suggestion'), [
            'date' => $today,
            'tipo' => 'meal_deviation',
            'resumo_para_notas' => 'Almoço fora da dieta compensado com jantar proteico e salada.',
            'agua_extra_ml' => 750,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('daily_logs', [
            'user_id' => $this->user->id,
            'date' => $today,
            'lunch_clean' => false,
        ]);

        $savedLog = DailyLog::where('user_id', $this->user->id)->where('date', $today)->first();
        $this->assertStringContainsString('Almoço fora da dieta compensado', $savedLog->notes);
    }
}
