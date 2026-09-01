<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\DTOs\ServidorDTO;
use App\DTOs\QualificacaoTemporariaDTO;
use App\DTOs\EventoAuxilioDTO;
use App\DTOs\ConsignadoDTO;
use App\DTOs\QualificacaoPermanenteDTO;
use App\DTOs\FilhoDTO;
use App\Services\SalaryCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SalaryProjectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that salary routes require authentication.
     */
    public function test_salary_routes_require_authentication(): void
    {
        $response = $this->get('/salario');
        $response->assertRedirect('/login');

        $responsePost = $this->postJson('/salario/projetar', []);
        $responsePost->assertStatus(401);
    }

    /**
     * Test that authenticated user can access salary index view.
     */
    public function test_authenticated_user_can_access_salary_view(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/salario');
        $response->assertStatus(200);
        $response->assertViewIs('salary.index');
        $response->assertViewHas('defaultConfig');
    }

    /**
     * Test the calculator service against Samuel Bellan's July 2026 holerite values.
     */
    public function test_calculator_service_matches_samuel_july_2026_holerite(): void
    {
        $service = new SalaryCalculatorService();

        // 1. Create server functional DTO
        $servidor = new ServidorDTO(
            dt_exercicio: '2025-05-05',
            cargo: 'TNSU',
            referencia_inicial: '1',
            aq_permanente_pct: 8.0,
            regime_integral: false,
            outros_adicionais_pct: 0.0,
            dependentes_irrf: 1,
            dependentes_cassems: 3,
            tem_conjuge: false,
            consignados: 697.00,
            teto_rgps: true,
            salario_substituicao: 1634.81,
            funcao_comissao_valor: 0.0
        );

        // Active auxílios in July 2026
        $auxilios = [
            'AUXILIO_ALIMENTACAO' => 2200.00,
            'AUXILIO_CRECHE' => 558.78,
            'AUXILIO_TRANSPORTE' => 1000.00
        ];

        // Active qualifications in July 2026 (4 * 82.64 = 330.56, and 1 * 13.77 = 13.77)
        // Total AQ Temp = 344.33
        // We simulate this by passing custom items that sum up to 344.33
        $aqTemps = [
            new QualificacaoTemporariaDTO('A', 1.0, '2025-06', '2029-05'),
            new QualificacaoTemporariaDTO('B', 1.0, '2025-08', '2029-07'),
            new QualificacaoTemporariaDTO('C', 1.0, '2025-10', '2029-09'),
            new QualificacaoTemporariaDTO('D', 1.0, '2025-12', '2029-11'),
            new QualificacaoTemporariaDTO('E', 0.1666, '2026-06', '2030-05') // Pro-rata course (approx 0.1666% of 8264.28 = 13.77)
        ];

        $holerite = $service->calcularHolerite(
            $servidor,
            '2026-07',
            $auxilios,
            $aqTemps
        );

        // Verify Base Salary for TNSU-1 in 2026-04 is 8264.28
        $this->assertEquals(8264.28, $holerite['baseSalary']);

        // Proventos checks
        $this->assertEquals(8264.28, $holerite['proventos']['vencimento']);
        $this->assertEquals(1634.81, $holerite['proventos']['substituicao']);
        $this->assertEquals(661.14, $holerite['proventos']['aq_permanente']);
        
        // Sum of temporary qualifications should equal roughly 344.33
        $this->assertEquals(344.33, $holerite['proventos']['aq_temporario']);

        // Salário Bruto (Total Tributável) = 10904.56
        $this->assertEquals(10904.56, $holerite['proventos']['total_tributavel']);

        // MS-PREV: Base = Vencimento (8264.28) + AQ Perm (661.14) = 8925.42, capped at RGPS ceiling (8475.55)
        // 14% of 8475.55 = 1186.58
        $this->assertEquals(8475.55, $holerite['msprev_base']);
        $this->assertEquals(1186.58, $holerite['descontos']['msprev']);

        // CASSEMS: Base = 8925.42. 7.5% of 8925.42 = 669.41. Fixo = 3 * 35.00 = 105.00. Total = 774.41.
        $this->assertEquals(669.41, $holerite['descontos']['cassems_pct']);
        $this->assertEquals(105.00, $holerite['descontos']['cassems_fixo']);
        $this->assertEquals(774.41, $holerite['descontos']['cassems_total']);

        // IRRF: Base = 10904.56 - 1186.58 - 189.59 = 9528.39.
        // Tax = (9528.39 * 27.5%) - 908.73 = 1711.58.
        $this->assertEquals(9528.39, $holerite['irrf_base']);
        $this->assertEquals(1711.58, $holerite['descontos']['irrf']);

        // Consignados
        $this->assertEquals(697.00, $holerite['descontos']['consignados']);

        // Total Descontos = 1186.58 + 774.41 + 1711.58 + 697.00 = 4369.57
        $this->assertEquals(4369.57, $holerite['descontos']['total_descontos']);

        // Salário Líquido (provisório) = 10904.56 - 4369.57 = 6534.99
        $this->assertEquals(6534.99, $holerite['proventos']['total_tributavel'] - $holerite['descontos']['total_descontos']);

        // Total Exempt Auxílios = 2200.00 + 558.78 + 1000.00 = 3758.78
        $this->assertEquals(3758.78, $holerite['total_isento']);

        // Net Salary = 6534.99 + 3758.78 = 10293.77
        $this->assertEquals(10293.77, $holerite['salario_liquido']);
    }

    /**
     * Test biennial progression functional level increments.
     */
    public function test_progression_advances_level_every_2_years(): void
    {
        $service = new SalaryCalculatorService();

        // 2025-05-05 to 2026-07-01 -> 14 months (0 bienios completed) -> level remains 1
        $level1 = $service->getProgressionLevel('2025-05-05', '1', '2026-07');
        $this->assertEquals('1', $level1);

        // 2025-05-05 to 2027-05-01 -> 24 months (1 bienio completed) -> level becomes 2
        $level2 = $service->getProgressionLevel('2025-05-05', '1', '2027-05');
        $this->assertEquals('2', $level2);

        // 2025-05-05 to 2029-05-01 -> 48 months (2 bienios completed) -> level becomes 3
        $level3 = $service->getProgressionLevel('2025-05-05', '1', '2029-05');
        $this->assertEquals('3', $level3);
    }

    /**
     * Test the newly added dynamic simulator features (multi-loans, dynamic AQ Perm, automated Creche, and events).
     */
    public function test_dynamic_projection_features(): void
    {
        $service = new SalaryCalculatorService();

        $servidor = new ServidorDTO(
            dt_exercicio: '2025-05-05',
            cargo: 'TNSU',
            referencia_inicial: '1',
            aq_permanente_pct: 8.0,
            regime_integral: false,
            outros_adicionais_pct: 0.0,
            dependentes_irrf: 0,
            dependentes_cassems: 0,
            tem_conjuge: false,
            consignados: 0.0,
            teto_rgps: true,
            salario_substituicao: 0.0,
            funcao_comissao_valor: 0.0,
            reajuste_auxilio_pct: 10.0
        );

        $consignados = [
            new ConsignadoDTO(300.00, '2025-05', '2025-08')
        ];

        $aqPermanentes = [
            new QualificacaoPermanenteDTO('Curso Perm A', 2.0, '2025-09')
        ];

        $filhos = [
            new FilhoDTO('Filho A', '2023-05-05', 2)
        ];

        $eventosAuxilios = [
            new EventoAuxilioDTO('2025-07', null, 'Auxílio Moradia', 750.00, 'CRIAR')
        ];

        // Project 10 months starting in 2025-05 (May 2025 to Feb 2026)
        $projection = $service->gerarProjecao(
            servidor: $servidor,
            aqTempList: [],
            eventosAuxilios: $eventosAuxilios,
            reajustes: [],
            anos: 1, // 12 months
            startMesAno: '2025-05',
            consignadosList: $consignados,
            aqPermanenteList: $aqPermanentes,
            filhosList: $filhos,
            valorUnitarioCreche: 500.00
        );

        // 1. May 2025 Check (Month 1, Index 0)
        $monthMay = $projection[0];
        $this->assertEquals('2025-05', $monthMay['mesAno']);
        $this->assertEquals(300.00, $monthMay['descontos']['consignados']);
        $this->assertEquals(636.88, $monthMay['proventos']['aq_permanente']); // 8.0% of 7961.00 base salary
        $this->assertEquals(500.00, $monthMay['auxilios']['AUXILIO_CRECHE']);
        $this->assertArrayNotHasKey('Auxílio Moradia', $monthMay['auxilios']);

        // 2. September 2025 Check (Month 5, Index 4)
        $monthSept = $projection[4];
        $this->assertEquals('2025-09', $monthSept['mesAno']);
        // Consignado should be 0.0 (ended in August)
        $this->assertEquals(0.00, $monthSept['descontos']['consignados']);
        // AQ Permanente should be 8% + 2% = 10% (796.10)
        $this->assertEquals(796.10, $monthSept['proventos']['aq_permanente']);

        // Verify Event signaling in September 2025
        $hasAqPermEvent = false;
        foreach ($monthSept['eventos'] as $event) {
            if ($event['tipo'] === 'aq_permanente' && str_contains($event['descricao'], 'AQ Permanente vai para 10')) {
                $hasAqPermEvent = true;
            }
        }
        $this->assertTrue($hasAqPermEvent, 'Deve sinalizar a alteração de AQ Permanente para 10%');

        // 3. January 2026 Check (Month 9, Index 8)
        $monthJan = $projection[8];
        $this->assertEquals('2026-01', $monthJan['mesAno']);
        // Auxilio Creche should be adjusted by 10% (500.00 * 1.10 = 550.00)
        $this->assertEquals(550.00, $monthJan['auxilios']['AUXILIO_CRECHE']);
        // Auxílio Moradia should be adjusted by 10% (750.00 * 1.10 = 825.00)
        $this->assertEquals(825.00, $monthJan['auxilios']['Auxílio Moradia']);
        
        // Verify Event signaling for Auxilio Reajuste
        $hasReajusteAuxEvent = false;
        foreach ($monthJan['eventos'] as $event) {
            if ($event['tipo'] === 'reajuste_auxilio' && str_contains($event['descricao'], 'Auxílios Reajustados')) {
                $hasReajusteAuxEvent = true;
            }
        }
        $this->assertTrue($hasReajusteAuxEvent, 'Deve sinalizar o reajuste anual de auxílios');
    }

    /**
     * Test salary profile saving, loading, activating, and deletion.
     */
    public function test_profile_persistence_and_management(): void
    {
        $user = User::factory()->create();

        // 1. Save profile
        $response = $this->actingAs($user)->postJson(route('salary.profiles.save'), [
            'nome' => 'Cenário Teste Samuel',
            'servidor' => [
                'dt_exercicio' => '2025-05-05',
                'cargo' => 'ASSJ',
                'referencia_inicial' => '2',
                'aq_permanente_pct' => 12.0
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $profileId = $response->json('profile.id');

        $this->assertDatabaseHas('salary_profiles', [
            'id' => $profileId,
            'user_id' => $user->id,
            'nome' => 'Cenário Teste Samuel'
        ]);

        // 2. Activate profile
        $actResponse = $this->actingAs($user)->postJson(route('salary.profiles.activate', ['id' => $profileId]));
        $actResponse->assertStatus(200);

        // 3. Load salary index and verify values are loaded from active profile
        $indexResponse = $this->actingAs($user)->get(route('salary.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertViewHas('activeProfileId', $profileId);
        
        $defaultConfig = $indexResponse->viewData('defaultConfig');
        $this->assertEquals('ASSJ', $defaultConfig['cargo']);
        $this->assertEquals('2', $defaultConfig['referencia_inicial']);
        $this->assertEquals(12.0, $defaultConfig['aq_permanente_pct']);

        // 3b. Update profile
        $updateResponse = $this->actingAs($user)->putJson(route('salary.profiles.update', ['id' => $profileId]), [
            'servidor' => [
                'dt_exercicio' => '2025-05-05',
                'cargo' => 'ASSJ',
                'referencia_inicial' => '5',
                'aq_permanente_pct' => 15.0
            ]
        ]);
        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('success', true);

        // Verify updated configuration is loaded
        $indexResponse2 = $this->actingAs($user)->get(route('salary.index'));
        $indexResponse2->assertStatus(200);
        $defaultConfig2 = $indexResponse2->viewData('defaultConfig');
        $this->assertEquals('5', $defaultConfig2['referencia_inicial']);
        $this->assertEquals(15.0, $defaultConfig2['aq_permanente_pct']);

        // 4. Delete profile
        $delResponse = $this->actingAs($user)->deleteJson(route('salary.profiles.delete', ['id' => $profileId]));
        $delResponse->assertStatus(200);

        $this->assertDatabaseMissing('salary_profiles', [
            'id' => $profileId
        ]);
    }

    public function test_general_reajuste_applied_in_specific_month(): void
    {
        $service = $this->app->make(SalaryCalculatorService::class);

        $servidor = new ServidorDTO(
            dt_exercicio: '2025-06-05',
            cargo: 'TNSU',
            referencia_inicial: '1',
            aq_permanente_pct: 0.0,
            reajuste_mes: 5
        );

        $reajustes = [
            2027 => 10.0
        ];

        $projection = $service->gerarProjecao(
            servidor: $servidor,
            aqTempList: [],
            eventosAuxilios: [],
            reajustes: $reajustes,
            anos: 3,
            startMesAno: '2025-05'
        );

        // April 2027 (Month 24, Index 23)
        $monthApril27 = $projection[23];
        $this->assertEquals('2027-04', $monthApril27['mesAno']);
        $this->assertEquals(8264.28, $monthApril27['proventos']['vencimento']);

        // May 2027 (Month 25, Index 24)
        $monthMay27 = $projection[24];
        $this->assertEquals('2027-05', $monthMay27['mesAno']);
        $this->assertEquals(9090.71, $monthMay27['proventos']['vencimento']);

        // Verify event is emitted in May 2027
        $hasReajusteEvent = false;
        foreach ($monthMay27['eventos'] as $event) {
            if ($event['tipo'] === 'reajuste' && str_contains($event['descricao'], 'Reajuste Geral: +10.0%')) {
                $hasReajusteEvent = true;
            }
        }
        $this->assertTrue($hasReajusteEvent, 'Deve sinalizar o reajuste geral em maio');
    }
}
