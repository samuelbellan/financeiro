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
use App\DTOs\CassemsEventoDTO;
use App\DTOs\DespesaForaFolhaDTO;
use App\Models\SalaryPayslip;
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

    /**
     * Cenário real do Samuel, usado para conferir o cálculo com os holerites de 05/2025 a 10/2026.
     */
    private function projecaoConferidaComHolerites(SalaryCalculatorService $service, int $mesFerias = 0): array
    {
        $servidor = new ServidorDTO(
            dt_exercicio: '2025-05-05',
            cargo: 'TNSU',
            referencia_inicial: '1',
            dependentes_irrf: 1,
            dependentes_cassems: 3,
            mes_ferias: $mesFerias
        );

        $aqTemp = [];
        foreach ([
            ['2025-06', '2026-05'], ['2025-06', '2026-05'], ['2025-06', '2026-05'],
            ['2025-07', '2026-06'], ['2025-07', '2026-06'],
            ['2026-06', '2027-04'], ['2026-06', '2027-04'], ['2026-06', '2027-05'],
            ['2026-07', '2027-05'], ['2026-07', '2027-06'],
        ] as $i => [$inicio, $fim]) {
            $aqTemp[] = new QualificacaoTemporariaDTO('Ação ' . $i, 1.0, $inicio, $fim);
        }

        return $service->gerarProjecao(
            servidor: $servidor,
            aqTempList: $aqTemp,
            eventosAuxilios: [
                new EventoAuxilioDTO('2025-05', null, 'AUXILIO_ALIMENTACAO', 2200.00, 'CRIAR'),
                new EventoAuxilioDTO('2025-05', null, 'AUXILIO_CRECHE', 558.78, 'CRIAR'),
                new EventoAuxilioDTO('2025-05', null, 'AUXILIO_TRANSPORTE', 500.00, 'CRIAR'),
                new EventoAuxilioDTO('2026-03', null, 'AUXILIO_TRANSPORTE', 1000.00, 'ALTERAR_VALOR'),
            ],
            reajustes: [],
            anos: 2,
            startMesAno: '2025-05',
            consignadosList: [
                new ConsignadoDTO(137.00, '2025-08', null),
                new ConsignadoDTO(560.00, '2025-10', null),
            ],
            aqPermanenteList: [new QualificacaoPermanenteDTO('AQ 8%', 8.0, '2025-10')]
        );
    }

    /**
     * Folhas mensais: valores iguais aos dos holerites reais.
     */
    public function test_folha_mensal_confere_com_holerites_reais(): void
    {
        $projection = $this->projecaoConferidaComHolerites(new SalaryCalculatorService());

        // 08/2025: tabela de IRRF de maio/2025 já em vigor; MS-PREV abaixo do teto
        $ago25 = $projection[3];
        $this->assertEquals('2025-08', $ago25['mesAno']);
        $this->assertEquals(8359.02, $ago25['proventos']['total_tributavel']);
        $this->assertEquals(1114.54, $ago25['descontos']['msprev']);
        $this->assertEquals(1031.36, $ago25['descontos']['irrf']);
        $this->assertEquals(137.00, $ago25['descontos']['consignados']);

        // 12/2025: AQ permanente de 8% e MS-PREV no teto do RGPS de 2025
        $dez25 = $projection[7];
        $this->assertEquals(8995.90, $dez25['proventos']['total_tributavel']);
        $this->assertEquals(8157.41, $dez25['msprev_base']);
        $this->assertEquals(1142.04, $dez25['descontos']['msprev']);
        $this->assertEquals(1198.94, $dez25['descontos']['irrf']);
        $this->assertEquals(697.00, $dez25['descontos']['consignados']);

        // 01/2026: novo teto do RGPS
        $jan26 = $projection[8];
        $this->assertEquals(1186.58, $jan26['descontos']['msprev']);
        $this->assertEquals(1186.70, $jan26['descontos']['irrf']);

        // 09/2026: holerite inteiro, até o total líquido
        $set26 = $projection[16];
        $this->assertEquals('2026-09', $set26['mesAno']);
        $this->assertEquals(8264.28, $set26['proventos']['vencimento']);
        $this->assertEquals(661.14, $set26['proventos']['aq_permanente']);
        $this->assertEquals(413.20, $set26['proventos']['aq_temporario']);
        $this->assertEquals(9338.62, $set26['proventos']['total_tributavel']);
        $this->assertEquals(1186.58, $set26['descontos']['msprev']);
        $this->assertEquals(1280.94, $set26['descontos']['irrf']);
        $this->assertEquals(669.41, $set26['descontos']['cassems_pct']);
        $this->assertEquals(105.00, $set26['descontos']['cassems_fixo']);
        $this->assertEquals(3938.93, $set26['descontos']['total_descontos']);
        $this->assertEquals(3758.78, $set26['total_isento']);
        $this->assertEquals(9158.47, $set26['salario_liquido']);
    }

    /**
     * 13º em duas parcelas (junho e novembro), com as rubricas 406, 460, 948 e 949 dos holerites.
     */
    public function test_gratificacao_natalina_confere_com_holerites_reais(): void
    {
        $service = new SalaryCalculatorService();

        // Entrada em 05/05/2025: maio conta (27 dias) -> 8/12 no ano e 2/12 até junho
        $this->assertSame(8, $service->getAvosGratificacaoNatalina('2025-05-05', 2025));
        $this->assertSame(2, $service->getAvosGratificacaoNatalina('2025-05-05', 2025, 6));
        $this->assertSame(7, $service->getAvosGratificacaoNatalina('2025-05-20', 2025));
        $this->assertSame(12, $service->getAvosGratificacaoNatalina('2025-05-05', 2026));
        $this->assertSame(6, $service->getAvosGratificacaoNatalina('2025-05-05', 2026, 6));
        $this->assertSame(0, $service->getAvosGratificacaoNatalina('2025-05-05', 2024));
        $this->assertSame(0, $service->getAvosGratificacaoNatalina('2025-08-01', 2025, 6));

        $projection = $this->projecaoConferidaComHolerites($service);

        // 06/2025: adiantamento de 2/12 do vencimento e metade do AQ temporário, só com MS-PREV
        $jun25 = $projection[1]['extras']['gratificacao_natalina'];
        $this->assertSame(1, $jun25['parcela']);
        $this->assertEquals(1326.83, $jun25['com_previdencia']);
        $this->assertEquals(119.40, $jun25['sem_previdencia']);
        $this->assertEquals(185.76, $jun25['msprev']);
        $this->assertEquals(0.0, $jun25['irrf']);

        // 11/2025: restante, com IRRF exclusivo sobre o 13º inteiro
        $nov25 = $projection[6]['extras']['gratificacao_natalina'];
        $this->assertSame(2, $nov25['parcela']);
        $this->assertSame(8, $nov25['avos']);
        $this->assertEquals(4405.07, $nov25['com_previdencia']);
        $this->assertEquals(278.65, $nov25['sem_previdencia']);
        $this->assertEquals(616.71, $nov25['msprev']);
        $this->assertEquals(504.19, $nov25['irrf']);
        $this->assertEquals(round(4405.07 + 278.65 - 616.71 - 504.19, 2), $nov25['liquido']);
        $this->assertEquals(
            round($projection[6]['salario_liquido'] + $nov25['liquido'], 2),
            $projection[6]['liquido_total']
        );

        // Dezembro não tem mais 13º
        $this->assertNull($projection[7]['extras']['gratificacao_natalina']);

        // 06/2026: 6/12 de vencimento + AQ permanente e metade dos cinco cursos
        $jun26 = $projection[13]['extras']['gratificacao_natalina'];
        $this->assertEquals(4462.71, $jun26['com_previdencia']);
        $this->assertEquals(206.60, $jun26['sem_previdencia']);
        $this->assertEquals(624.78, $jun26['msprev']);

        // Desligando a opção, nada é projetado
        $servidor = new ServidorDTO(dt_exercicio: '2025-05-05', cargo: 'TNSU', referencia_inicial: '1', incluir_13: false);
        $semExtras = $service->gerarProjecao($servidor, [], [], [], 2, '2025-05');
        $this->assertEquals(0.0, array_sum(array_column($semExtras, 'liquido_extras')));
    }

    /**
     * Terço de férias nas rubricas 249 e 296 do holerite de 04/2026.
     */
    public function test_adicional_de_ferias_confere_com_holerite_real(): void
    {
        $service = new SalaryCalculatorService();
        $projection = $this->projecaoConferidaComHolerites($service, 3);

        // 03/2026 tem a mesma remuneração do holerite de 04/2026 (o reajuste de abril só entrou em maio)
        $mar26 = $projection[10];
        $this->assertEquals('2026-03', $mar26['mesAno']);
        $ferias = $service->calcularAdicionalFerias($mar26, 2026, 3);
        $this->assertEquals(2998.65, $ferias['bruto']); // 2.865,95 + 132,70
        $this->assertEqualsWithDelta(824.63, $ferias['irrf'], 0.01);

        // Com dez meses de exercício ainda não há terço; a partir de onze, sim
        $this->assertNull($mar26['extras']['adicional_ferias']);
        $comOnzeMeses = $this->projecaoConferidaComHolerites($service, 4)[11];
        $this->assertEquals('2026-04', $comOnzeMeses['mesAno']);
        $this->assertNotNull($comOnzeMeses['extras']['adicional_ferias']);
        $this->assertContains('ferias', array_column($comOnzeMeses['eventos'], 'tipo'));
    }

    /**
     * Anos sem percentual informado usam o reajuste padrão da data-base.
     */
    public function test_reajuste_padrao_cobre_anos_sem_percentual(): void
    {
        $service = new SalaryCalculatorService();

        // 2027 informado (10%), 2028 cai no padrão (4%)
        $this->assertEquals(
            round(8512.22 * 1.10 * 1.04, 2),
            $service->getBaseSalary('TNSU', '2', '2028-05', [2027 => 10.0], 5, 4.0)
        );
        // Antes do mês da data-base de 2028 só vale o reajuste de 2027
        $this->assertEquals(round(8512.22 * 1.10, 2), $service->getBaseSalary('TNSU', '2', '2028-04', [2027 => 10.0], 5, 4.0));
        // Sem padrão, o comportamento antigo se mantém
        $this->assertEquals(round(8512.22 * 1.10, 2), $service->getBaseSalary('TNSU', '2', '2028-05', [2027 => 10.0]));
    }

    /**
     * Mapa da carreira do TNSU para quem entrou em maio/2025.
     */
    public function test_mapa_da_carreira_tnsu(): void
    {
        $service = new SalaryCalculatorService();
        $servidor = new ServidorDTO(dt_exercicio: '2025-05-05', cargo: 'TNSU', referencia_inicial: '1');

        $mapa = $service->gerarMapaCarreira($servidor, '2026-11');

        $this->assertSame(1, $mapa['referencia_atual']);
        $this->assertEquals(8264.28, $mapa['vencimento_atual']);
        $this->assertEquals(0.0, $mapa['ats_atual_pct']);

        $this->assertEquals('TNSU-2', $mapa['proxima_progressao']['codigo']);
        $this->assertEquals('2027-05-05', $mapa['proxima_progressao']['data']);
        $this->assertSame(6, $mapa['proxima_progressao']['meses_restantes']);
        $this->assertEquals(247.94, $mapa['proxima_progressao']['ganho']);

        $this->assertEquals(10.0, $mapa['proximo_ats']['pct']);
        $this->assertEquals('2030-05-05', $mapa['proximo_ats']['data']);

        $this->assertCount(19, $mapa['referencias']);
        $this->assertEquals(3.0, $mapa['referencias'][1]['degrau_pct']);   // até o 10º biênio
        $this->assertEquals(3.5, $mapa['referencias'][10]['degrau_pct']);  // biênios seguintes
        $this->assertEquals('TNSU-19', $mapa['topo']['codigo']);
        $this->assertEquals('2061-05-05', $mapa['topo']['data']);
        $this->assertEquals(14696.17, $mapa['topo']['vencimento']);
        $this->assertEquals('2060-05-05', $mapa['ats'][6]['data']);
        $this->assertEquals(40.0, $mapa['ats'][6]['pct']);

        // Depois da primeira progressão a referência atual avança
        $this->assertSame(2, $service->gerarMapaCarreira($servidor, '2027-05')['referencia_atual']);
    }

    /**
     * Importação dos holerites reais: grava por usuário, não duplica e só aparece para o dono.
     */
    public function test_importa_holerites_reais_e_mostra_so_para_o_dono(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();

        // Arquivo fictício: os holerites reais ficam fora do repositório
        $arquivo = base_path('tests/Fixtures/holerites_exemplo.json');
        $args = ['arquivo' => $arquivo, '--user' => $dono->id];

        $this->artisan('salary:importar-holerites', $args)->assertExitCode(0);
        $this->artisan('salary:importar-holerites', $args)->assertExitCode(0);

        // Dois meses válidos, sem duplicar na reimportação; o registro com mes_ano inválido é ignorado
        $this->assertSame(2, $dono->salaryPayslips()->count());
        $this->assertEquals(7700.00, round((float) $dono->salaryPayslips()->sum('total_liquido'), 2));

        $jan = $dono->salaryPayslips()->where('mes_ano', '2030-01')->first();
        $this->assertEquals(3800.00, $jan->total_liquido);
        $this->assertEquals(
            $jan->total_bruto,
            round(array_sum(array_column($jan->pagamentos, 'valor')), 2)
        );

        $this->assertCount(2, $this->actingAs($dono)->get('/salario')->viewData('holerites'));
        $this->assertCount(0, $this->actingAs($outro)->get('/salario')->viewData('holerites'));

        $this->artisan('salary:importar-holerites', ['arquivo' => $arquivo, '--user' => 999999])->assertExitCode(1);
        $this->artisan('salary:importar-holerites', ['arquivo' => base_path('nao-existe.json'), '--user' => $dono->id])->assertExitCode(1);
    }

    /**
     * Teto do RGPS pelo INPC e tabela do IRRF corrigida só quando o cenário pede.
     */
    public function test_teto_rgps_e_correcao_da_tabela_do_irrf(): void
    {
        $service = new SalaryCalculatorService();

        $this->assertEquals(8157.41, $service->getRgpsCeiling(2025));
        $this->assertEquals(8475.55, $service->getRgpsCeiling(2026));
        $this->assertEquals(round(8475.55 * 1.039, 2), $service->getRgpsCeiling(2027));
        $this->assertEquals(round(8475.55 * 1.039 * 1.039, 2), $service->getRgpsCeiling(2028, 3.9));
        $this->assertEquals(round(8475.55 * 1.05, 2), $service->getRgpsCeiling(2027, 5.0));

        // Até 2026, ou sem correção, nada muda
        $this->assertEquals($service->calculateIrrf(7962.45, 2026), $service->calculateIrrf(7962.45, 2026, 12, 3.9));
        $this->assertEquals($service->calculateIrrf(8000.00, 2028), $service->calculateIrrf(8000.00, 2028, 12, 0.0));

        // Faixas e parcela a deduzir corrigidas por f: imposto = f × imposto(base / f)
        $fator = 1.039 ** 2;
        $this->assertEqualsWithDelta(
            round($service->calculateIrrf(8000.00 / $fator, 2028) * $fator, 2),
            $service->calculateIrrf(8000.00, 2028, 12, 3.9),
            0.01
        );
        // Na faixa de 27,5%, a correção só reduz pela parcela a deduzir corrigida
        $this->assertEqualsWithDelta(
            $service->calculateIrrf(8000.00, 2028) - 908.73 * ($fator - 1),
            $service->calculateIrrf(8000.00, 2028, 12, 3.9),
            0.02
        );

        $this->assertEquals(189.59, $service->deducaoDependentesIrrf(1, 2026, 3.9));
        $this->assertEquals(round(189.59 * $fator, 2), $service->deducaoDependentesIrrf(1, 2028, 3.9));
    }

    /**
     * Renovação automática das ações educacionais depois do último curso informado.
     */
    public function test_renovacao_automatica_do_aq_temporario(): void
    {
        $service = new SalaryCalculatorService();
        $servidor = new ServidorDTO(
            dt_exercicio: '2025-05-05',
            cargo: 'TNSU',
            referencia_inicial: '1',
            aq_temp_renovacao_qtd: 5
        );
        $explicitos = [new QualificacaoTemporariaDTO('Ação 2026', 1.0, '2026-06', '2027-05')];

        $renovacoes = $service->gerarRenovacoesAqTemporario($servidor, $explicitos, '2027-01', '2028-12');
        $this->assertCount(10, $renovacoes);
        $this->assertEquals('2027-06', $renovacoes[0]->mes_inicio);
        $this->assertEquals('2028-05', $renovacoes[0]->mes_fim);
        $this->assertEquals('2028-06', $renovacoes[9]->mes_inicio);
        $this->assertEquals('Renovação AQ 2027 (5 × 1%)', $renovacoes[0]->nome);

        // Sem quantidade, não renova
        $semRenovar = new ServidorDTO(dt_exercicio: '2025-05-05', cargo: 'TNSU', referencia_inicial: '1');
        $this->assertSame([], $service->gerarRenovacoesAqTemporario($semRenovar, $explicitos, '2027-01', '2028-12'));

        $projection = $service->gerarProjecao($servidor, $explicitos, [], [], 2, '2027-01');
        $porMes = array_column($projection, null, 'mesAno');

        // Maio/2027: só o curso informado. Junho em diante: as cinco renovações, sem lacuna na virada
        $this->assertEquals(round(8512.22 * 0.01, 2), $porMes['2027-05']['proventos']['aq_temporario']);
        $this->assertEquals(5 * round(8512.22 * 0.01, 2), $porMes['2027-06']['proventos']['aq_temporario']);
        $this->assertEquals(5 * round(8512.22 * 0.01, 2), $porMes['2028-05']['proventos']['aq_temporario']);
        $this->assertEquals(5 * round(8512.22 * 0.01, 2), $porMes['2028-06']['proventos']['aq_temporario']);

        // Um evento por lote, não um por curso
        $inicios = array_filter($porMes['2027-06']['eventos'], fn ($e) => $e['tipo'] === 'aq_temp_inicio');
        $this->assertCount(1, $inicios);
    }

    /**
     * Curso permanente com vigência em setembro e pagamento em novembro, com o retroativo.
     */
    public function test_aq_permanente_com_retroativo_e_teto_so_do_permanente(): void
    {
        $service = new SalaryCalculatorService();
        $servidor = new ServidorDTO(dt_exercicio: '2025-05-05', cargo: 'TNSU', referencia_inicial: '1');
        $cursos = [
            new QualificacaoPermanenteDTO('AQ 8%', 8.0, '2025-10'),
            QualificacaoPermanenteDTO::fromArray(['nome' => 'Curso 2026', 'percentual' => 2.0, 'mes_inicio' => '2026-09', 'mes_pagamento' => '2026-11']),
        ];
        $aqTemp = [new QualificacaoTemporariaDTO('Ação', 1.0, '2026-06', '2027-05')];

        $projection = $service->gerarProjecao($servidor, $aqTemp, [], [], 1, '2026-08', [], $cursos);
        $porMes = array_column($projection, null, 'mesAno');

        // Setembro e outubro ainda em 8%
        $this->assertEquals(661.14, $porMes['2026-09']['proventos']['aq_permanente']);
        $this->assertEquals(0.0, $porMes['2026-10']['proventos']['aq_permanente_retroativo']);

        // Novembro: 10% e dois meses de retroativo de 2 pontos
        $nov = $porMes['2026-11'];
        $this->assertEquals(826.43, $nov['proventos']['aq_permanente']);
        $this->assertEquals(round(8264.28 * 0.04, 2), $nov['proventos']['aq_permanente_retroativo']);
        $this->assertEquals(round(8264.28 + 826.43, 2), $nov['base_permanente']);
        $this->assertEquals(round(8264.28 + 826.43 + 330.57, 2), $nov['cassems_base']);
        $this->assertContains('aq_permanente_retroativo', array_column($nov['eventos'], 'tipo'));

        // O retroativo não entra no 13º: total = remuneração permanente + AQ temporário
        $this->assertEquals(round(8264.28 + 826.43 + 82.64, 2), $nov['extras']['gratificacao_natalina']['total_bruto']);

        // Dezembro: só os 10%, sem retroativo
        $this->assertEquals(826.43, $porMes['2026-12']['proventos']['aq_permanente']);
        $this->assertEquals(0.0, $porMes['2026-12']['proventos']['aq_permanente_retroativo']);

        // Teto de 20% só do permanente: com 18%, um curso de 4 pontos rende 2 (e o retroativo também)
        $perto = new ServidorDTO(dt_exercicio: '2025-05-05', cargo: 'TNSU', referencia_inicial: '1', aq_permanente_pct: 18.0);
        $noTeto = $service->gerarProjecao($perto, $aqTemp, [], [], 1, '2026-08', [], [
            QualificacaoPermanenteDTO::fromArray(['nome' => 'Mestrado', 'percentual' => 4.0, 'mes_inicio' => '2026-09', 'mes_pagamento' => '2026-10']),
        ]);
        $out = array_column($noTeto, null, 'mesAno')['2026-10'];
        $this->assertEquals(round(8264.28 * 0.20, 2), $out['proventos']['aq_permanente']);
        $this->assertEquals(round(8264.28 * 0.02, 2), $out['proventos']['aq_permanente_retroativo']);
        $this->assertEquals(82.64, $out['proventos']['aq_temporario']); // temporário fora do teto

        // Pagamento anterior à vigência é ignorado
        $this->assertNull(QualificacaoPermanenteDTO::fromArray(['mes_inicio' => '2026-09', 'mes_pagamento' => '2026-08'])->mes_pagamento);
    }

    /**
     * CASSEMS datada substitui a regra por dependentes; despesas fora da folha só mexem no disponível.
     */
    public function test_cassems_datada_e_despesas_fora_da_folha(): void
    {
        $service = new SalaryCalculatorService();
        $servidor = new ServidorDTO(dt_exercicio: '2025-05-05', cargo: 'TNSU', referencia_inicial: '1', dependentes_cassems: 3);

        $projection = $service->gerarProjecao(
            servidor: $servidor,
            aqTempList: [],
            eventosAuxilios: [],
            reajustes: [],
            anos: 1,
            startMesAno: '2026-01',
            cassemsEventos: [
                new CassemsEventoDTO('2026-06', 7.5, 105.00),
                new CassemsEventoDTO('2025-12', 7.5, 140.00),
            ],
            despesasForaFolha: [new DespesaForaFolhaDTO('Boleto CASSEMS cônjuge', 450.00, '2026-06')]
        );
        $porMes = array_column($projection, null, 'mesAno');

        // Fora de ordem na entrada, mas vale o último evento iniciado até o mês
        $this->assertEquals(140.00, $porMes['2026-03']['descontos']['cassems_fixo']);
        $this->assertEquals(105.00, $porMes['2026-07']['descontos']['cassems_fixo']);
        $this->assertEquals(round(8264.28 * 0.075, 2), $porMes['2026-07']['descontos']['cassems_pct']);

        // Sem evento algum, volta a regra por dependentes
        $semEventos = $service->gerarProjecao($servidor, [], [], [], 1, '2026-01');
        $this->assertEquals(105.00, $semEventos[0]['descontos']['cassems_fixo']);

        // Despesa só a partir de junho e só no disponível
        $this->assertEquals($porMes['2026-05']['liquido_total'], $porMes['2026-05']['liquido_disponivel']);
        $this->assertEquals(round($porMes['2026-07']['liquido_total'] - 450.00, 2), $porMes['2026-07']['liquido_disponivel']);
        $this->assertEquals(450.00, $porMes['2026-07']['total_despesas_fora_folha']);
        $this->assertEquals($porMes['2026-07']['salario_liquido'] + $porMes['2026-07']['liquido_extras'], $porMes['2026-07']['liquido_total']);
    }

    /**
     * A rota devolve os três cenários, ordenados como se espera, e o padrão traz as premissas novas.
     */
    public function test_rota_devolve_cenarios_e_padroes_novos(): void
    {
        $user = User::factory()->create();

        $view = $this->actingAs($user)->get('/salario');
        $config = $view->viewData('defaultConfig');
        $this->assertEquals('base', $config['cenario']);
        $this->assertEquals(4.0, $config['reajuste_padrao_pct']);
        $this->assertEquals(5, $config['aq_temp_renovacao_qtd']);
        $this->assertSame([], $view->viewData('defaultReajustes'));
        $this->assertCount(3, $view->viewData('defaultCassemsEventos'));
        $this->assertEquals('2026-11', $view->viewData('defaultAqPermanenteCursos')[1]['mes_pagamento']);
        // Consignados da CEF: 24 parcelas desde 08/2025 e 60 desde 10/2025
        $this->assertSame(['2027-07', '2030-09'], array_column($view->viewData('defaultConsignados'), 'mes_fim'));

        $response = $this->actingAs($user)->postJson(route('salary.project'), [
            'servidor' => [
                'dt_exercicio' => '2025-05-05',
                'cargo' => 'TNSU',
                'referencia_inicial' => '1',
                'anos_projecao' => 6,
                'start_mes_ano' => '2026-01',
                'reajuste_padrao_pct' => 4.0,
                'aq_temp_renovacao_qtd' => 5,
            ],
            'aq_temporario' => [['nome' => 'Ação', 'percentual' => 1, 'mes_inicio' => '2026-06', 'mes_fim' => '2027-05']],
            'cassems_eventos' => [['mes_inicio' => '2026-01', 'aliquota_pct' => 7.5, 'fixo' => 105]],
            'despesas_fora_folha' => [['descricao' => 'Boleto', 'valor' => 450, 'mes_inicio' => '2026-06']],
        ]);

        $response->assertStatus(200);
        $cenarios = $response->json('cenarios');
        $this->assertSame(['conservador', 'base', 'otimista'], array_keys($cenarios));

        // Em 2031 os cenários já se separam: conservador < base < otimista
        $this->assertLessThan($cenarios['base']['2031']['liquido_total'], $cenarios['conservador']['2031']['liquido_total']);
        $this->assertLessThan($cenarios['otimista']['2031']['liquido_total'], $cenarios['base']['2031']['liquido_total']);
        $this->assertEquals(12, $cenarios['base']['2031']['meses']);

        // O cenário base da rota é o mesmo da projeção principal com as mesmas premissas
        $somaPrincipal = round(array_sum(array_map(
            fn ($m) => str_starts_with($m['mesAno'], '2031') ? $m['liquido_total'] : 0,
            $response->json('projection')
        )), 2);
        $this->assertEquals($somaPrincipal, $cenarios['base']['2031']['liquido_total']);

        // Despesa fora da folha aparece no disponível
        $this->assertEquals(
            round($cenarios['base']['2031']['liquido_total'] - 12 * 450, 2),
            $cenarios['base']['2031']['liquido_disponivel']
        );
    }

    /**
     * Outlier: vem do arquivo, pode ser trocado na tela e só pelo dono.
     */
    public function test_marcacao_de_outlier(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();
        $arquivo = base_path('tests/Fixtures/holerites_exemplo.json');

        $this->artisan('salary:importar-holerites', ['arquivo' => $arquivo, '--user' => $dono->id])->assertExitCode(0);
        $fev = fn () => SalaryPayslip::where('user_id', $dono->id)->where('mes_ano', '2030-02')->first();
        $jan = fn () => SalaryPayslip::where('user_id', $dono->id)->where('mes_ano', '2030-01')->first();
        $this->assertTrue($fev()->outlier);
        $this->assertFalse($jan()->outlier);

        // Marcar na tela
        $this->actingAs($dono)->patchJson(route('salary.payslips.outlier', ['mesAno' => '2030-01']), ['outlier' => true])
            ->assertOk()
            ->assertJsonPath('outlier', true);
        $this->assertTrue($jan()->outlier);

        // Reimportar não desfaz a marcação de um mês que não traz o campo no arquivo
        $this->artisan('salary:importar-holerites', ['arquivo' => $arquivo, '--user' => $dono->id])->assertExitCode(0);
        $this->assertTrue($jan()->outlier);

        // Sem o campo na requisição, alterna
        $this->actingAs($dono)->patchJson(route('salary.payslips.outlier', ['mesAno' => '2030-01']))->assertJsonPath('outlier', false);

        // Outro usuário não enxerga o holerite; mês inexistente também dá 404
        $this->actingAs($outro)->patchJson(route('salary.payslips.outlier', ['mesAno' => '2030-02']), ['outlier' => false])->assertNotFound();
        $this->assertTrue($fev()->outlier);
        $this->actingAs($dono)->patchJson(route('salary.payslips.outlier', ['mesAno' => '2031-01']))->assertNotFound();

        $this->assertTrue(collect($this->actingAs($dono)->get('/salario')->viewData('holerites'))->firstWhere('mes_ano', '2030-02')->outlier);
    }

    /**
     * Férias já marcadas num ano substituem o mês padrão só naquele ano.
     */
    public function test_ferias_marcadas_por_ano(): void
    {
        $this->assertSame([2026 => 4, 2027 => 1], ServidorDTO::parseFeriasExcecoes('2027-01, 2026-04; lixo 2026-13'));
        $this->assertSame([], ServidorDTO::parseFeriasExcecoes(null));

        $servidor = ServidorDTO::fromRequest([
            'dt_exercicio' => '2025-05-05',
            'cargo' => 'TNSU',
            'referencia_inicial' => '1',
            'mes_ferias' => 7,
            'ferias_excecoes' => '2026-04',
        ]);
        $projection = (new SalaryCalculatorService())->gerarProjecao($servidor, [], [], [], 2, '2026-01');
        $comFerias = array_values(array_map(
            fn ($m) => $m['mesAno'],
            array_filter($projection, fn ($m) => $m['extras']['adicional_ferias'] !== null)
        ));

        $this->assertSame(['2026-04', '2027-07'], $comFerias);
    }

    /**
     * A rota de projeção devolve o mapa da carreira junto com os meses.
     */
    public function test_rota_de_projecao_inclui_carreira(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('salary.project'), [
            'servidor' => [
                'dt_exercicio' => '2025-05-05',
                'cargo' => 'TNSU',
                'referencia_inicial' => '1',
                'anos_projecao' => 1,
                'start_mes_ano' => '2025-05',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('carreira.topo.codigo', 'TNSU-19');
        $response->assertJsonCount(12, 'projection');
        $response->assertJsonPath('projection.6.extras.gratificacao_natalina.avos', 8);
    }
}
