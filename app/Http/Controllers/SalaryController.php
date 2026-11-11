<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DTOs\ServidorDTO;
use App\DTOs\QualificacaoTemporariaDTO;
use App\DTOs\EventoAuxilioDTO;
use App\DTOs\ConsignadoDTO;
use App\DTOs\QualificacaoPermanenteDTO;
use App\DTOs\FilhoDTO;
use App\DTOs\CassemsEventoDTO;
use App\DTOs\DespesaForaFolhaDTO;
use App\Services\SalaryCalculatorService;
use App\Models\SalaryProfile;

class SalaryController extends Controller
{
    private SalaryCalculatorService $calculatorService;

    public function __construct(SalaryCalculatorService $calculatorService)
    {
        $this->calculatorService = $calculatorService;
    }

    public function index(Request $request)
    {
        // Cenário padrão conferido com os holerites de 05/2025 a 10/2026 (matrícula do Samuel)
        $defaultConfig = [
            'dt_exercicio' => '2025-05-05',
            'cargo' => 'TNSU',
            'referencia_inicial' => '1',
            'aq_permanente_pct' => 0.0,
            'regime_integral' => false,
            'outros_adicionais_pct' => 0.0,
            'dependentes_irrf' => 1,
            'dependentes_cassems' => 3,
            'tem_conjuge' => false,
            'consignados' => 0.0,
            'salario_substituicao' => 0.0,
            'funcao_comissao_valor' => 0.0,
            'teto_rgps' => true,
            'anos_projecao' => 10,
            'start_mes_ano' => '2025-05', // Default starting from entry month
            'reajuste_auxilio_pct' => 0.0,
            'reajuste_mes' => 5,
            'mes_ferias' => 7,
            'ferias_excecoes' => '2026-04', // em 2026 as férias foram em abril (holerite de 04/2026)
            'incluir_13' => true,
            // Cenário base (sabatina de 11/2026); o seletor do formulário troca as três premissas juntas
            'cenario' => 'base',
            'reajuste_padrao_pct' => SalaryCalculatorService::CENARIOS['base']['reajuste_padrao_pct'],
            'aq_temp_renovacao_qtd' => SalaryCalculatorService::CENARIOS['base']['aq_temp_renovacao_qtd'],
            'irrf_correcao_pct' => SalaryCalculatorService::CENARIOS['base']['irrf_correcao_pct'],
            'aq_temp_renovacao_pct' => 1.0,
            'aq_temp_renovacao_mes' => 6,
            'aq_temp_renovacao_ano' => 0,
            'teto_rgps_reajuste_pct' => 3.9,
        ];

        // Cada ação educacional vale 1% por cerca de um ano; de 2027 em diante a renovação é automática
        $defaultAqTemp = [
            ['nome' => 'Ação educacional 2025 A', 'percentual' => 1.0, 'mes_inicio' => '2025-06', 'mes_fim' => '2026-05'],
            ['nome' => 'Ação educacional 2025 B', 'percentual' => 1.0, 'mes_inicio' => '2025-06', 'mes_fim' => '2026-05'],
            ['nome' => 'Ação educacional 2025 C', 'percentual' => 1.0, 'mes_inicio' => '2025-06', 'mes_fim' => '2026-05'],
            ['nome' => 'Ação educacional 2025 D', 'percentual' => 1.0, 'mes_inicio' => '2025-07', 'mes_fim' => '2026-06'],
            ['nome' => 'Ação educacional 2025 E', 'percentual' => 1.0, 'mes_inicio' => '2025-07', 'mes_fim' => '2026-06'],
            ['nome' => 'Ação educacional 2026 A', 'percentual' => 1.0, 'mes_inicio' => '2026-06', 'mes_fim' => '2027-04'],
            ['nome' => 'Ação educacional 2026 B', 'percentual' => 1.0, 'mes_inicio' => '2026-06', 'mes_fim' => '2027-04'],
            ['nome' => 'Ação educacional 2026 C', 'percentual' => 1.0, 'mes_inicio' => '2026-06', 'mes_fim' => '2027-05'],
            ['nome' => 'Ação educacional 2026 D', 'percentual' => 1.0, 'mes_inicio' => '2026-07', 'mes_fim' => '2027-05'],
            ['nome' => 'Ação educacional 2026 E', 'percentual' => 1.0, 'mes_inicio' => '2026-07', 'mes_fim' => '2027-06'],
        ];

        $defaultEvents = [
            ['mes_ano_inicio' => '2025-05', 'mes_ano_fim' => null, 'tipo_auxilio' => 'AUXILIO_ALIMENTACAO', 'valor' => 2200.00, 'acao' => 'CRIAR'],
            ['mes_ano_inicio' => '2025-05', 'mes_ano_fim' => null, 'tipo_auxilio' => 'AUXILIO_TRANSPORTE', 'valor' => 500.00, 'acao' => 'CRIAR'],
            ['mes_ano_inicio' => '2026-03', 'mes_ano_fim' => null, 'tipo_auxilio' => 'AUXILIO_TRANSPORTE', 'valor' => 1000.00, 'acao' => 'ALTERAR_VALOR'],
        ];

        // Sem percentuais por ano: o cenário define o reajuste; informe um ano só quando houver notícia concreta
        $defaultReajustes = [];

        // Contratos da CEF: fim = primeiro desconto + número de parcelas − 1 (contagem de pagas confere com os holerites)
        $defaultConsignados = [
            ['descricao' => 'C.E.F. EMPRESTIMO (24x)', 'valor' => 137.00, 'mes_inicio' => '2025-08', 'mes_fim' => '2027-07'],
            ['descricao' => 'C.E.F. EMPRESTIMO (60x)', 'valor' => 560.00, 'mes_inicio' => '2025-10', 'mes_fim' => '2030-09'],
        ];

        // 8% desde 10/2025; depois um curso de +2 pontos por setembro, pago em novembro com retroativo, até 20%
        $defaultAqPermanenteCursos = [
            ['nome' => 'AQ Permanente (8%)', 'percentual' => 8.0, 'mes_inicio' => '2025-10', 'mes_pagamento' => null],
        ];
        foreach (range(2026, 2031) as $ano) {
            $defaultAqPermanenteCursos[] = [
                'nome' => "Curso permanente {$ano}",
                'percentual' => 2.0,
                'mes_inicio' => "{$ano}-09",
                'mes_pagamento' => "{$ano}-11",
            ];
        }

        // Mensalidade da CASSEMS em folha, conferida nos holerites
        $defaultCassemsEventos = [
            ['mes_inicio' => '2025-05', 'aliquota_pct' => 7.25, 'fixo' => 105.00],
            ['mes_inicio' => '2025-12', 'aliquota_pct' => 7.5, 'fixo' => 140.00],
            ['mes_inicio' => '2026-06', 'aliquota_pct' => 7.5, 'fixo' => 105.00],
        ];

        // O cônjuge saiu do desconto em folha e passou a pagar boleto à parte
        $defaultDespesas = [
            ['descricao' => 'Boleto CASSEMS cônjuge', 'valor' => 450.00, 'mes_inicio' => '2026-06', 'mes_fim' => null],
        ];

        // Data fictícia: só serve para o auxílio-educação infantil aparecer desde o início.
        // As datas reais ficam no perfil salvo de cada usuário.
        $defaultFilhos = [
            ['nome' => 'Filho 1', 'dt_nascimento' => '2022-07-01', 'idade_escola' => 2],
        ];

        // Load profiles for current user
        $profiles = auth()->user()->salaryProfiles()->orderBy('nome')->get();
        $activeProfile = $profiles->where('is_active', true)->first();
        $activeProfileId = $activeProfile ? $activeProfile->id : null;

        // Query param loading
        $profileIdQuery = $request->query('profile_id');
        if ($profileIdQuery) {
            $selectedProfile = $profiles->where('id', (int)$profileIdQuery)->first();
            if ($selectedProfile) {
                $activeProfile = $selectedProfile;
                $activeProfileId = $selectedProfile->id;
            }
        }

        // Apply profile configuration if active
        if ($activeProfile) {
            $config = $activeProfile->config_data;
            $defaultConfig = array_merge($defaultConfig, $config['servidor'] ?? []);
            $defaultAqTemp = $config['aq_temporario'] ?? $defaultAqTemp;
            $defaultEvents = $config['eventos_auxilios'] ?? $defaultEvents;
            $defaultReajustes = $config['reajustes'] ?? $defaultReajustes;
            $defaultConsignados = $config['consignados_list'] ?? $defaultConsignados;
            $defaultAqPermanenteCursos = $config['aq_permanente_cursos'] ?? $defaultAqPermanenteCursos;
            $defaultFilhos = $config['filhos'] ?? $defaultFilhos;
            $defaultCassemsEventos = $config['cassems_eventos'] ?? $defaultCassemsEventos;
            $defaultDespesas = $config['despesas_fora_folha'] ?? $defaultDespesas;
        }

        $cenarios = SalaryCalculatorService::CENARIOS;

        $holerites = auth()->user()->salaryPayslips()->orderBy('mes_ano')->get([
            'mes_ano', 'total_bruto', 'total_descontos', 'total_liquido', 'outlier', 'pagamentos', 'descontos', 'observacao',
        ]);

        return view('salary.index', compact(
            'holerites',
            'defaultConfig', 
            'defaultAqTemp', 
            'defaultEvents', 
            'defaultReajustes',
            'defaultConsignados',
            'defaultAqPermanenteCursos',
            'defaultFilhos',
            'defaultCassemsEventos',
            'defaultDespesas',
            'cenarios',
            'profiles',
            'activeProfileId'
        ));
    }

    public function project(Request $request)
    {
        $serverData = $request->input('servidor', []);
        $servidor = ServidorDTO::fromRequest($serverData);

        // Parse Qualificações Temporárias
        $aqTempList = [];
        $aqTempData = $request->input('aq_temporario', []);
        foreach ($aqTempData as $aq) {
            if (!empty($aq['percentual']) && !empty($aq['mes_inicio']) && !empty($aq['mes_fim'])) {
                $aqTempList[] = QualificacaoTemporariaDTO::fromArray($aq);
            }
        }

        // Parse Eventos Auxílios
        $eventosAuxilios = [];
        $eventsData = $request->input('eventos_auxilios', []);
        foreach ($eventsData as $event) {
            if (!empty($event['mes_ano_inicio']) && !empty($event['tipo_auxilio']) && isset($event['valor'])) {
                $eventosAuxilios[] = EventoAuxilioDTO::fromArray($event);
            }
        }

        // Parse Reajustes
        $reajustes = [];
        $reajustesData = $request->input('reajustes', []);
        foreach ($reajustesData as $year => $pct) {
            if ($pct !== null && $pct !== '') {
                $reajustes[(int)$year] = (float)$pct;
            }
        }

        // Parse Consignados
        $consignadosList = [];
        $consignadosData = $request->input('consignados_list', []);
        foreach ($consignadosData as $cons) {
            if (!empty($cons['valor']) && !empty($cons['mes_inicio'])) {
                $consignadosList[] = ConsignadoDTO::fromArray($cons);
            }
        }

        // Parse AQ Permanente Cursos
        $aqPermanenteCursosList = [];
        $aqPermData = $request->input('aq_permanente_cursos', []);
        foreach ($aqPermData as $aq) {
            if (!empty($aq['percentual']) && !empty($aq['mes_inicio'])) {
                $aqPermanenteCursosList[] = QualificacaoPermanenteDTO::fromArray($aq);
            }
        }

        // Parse Filhos
        $filhosList = [];
        $filhosData = $request->input('filhos', []);
        foreach ($filhosData as $filho) {
            if (!empty($filho['dt_nascimento'])) {
                $filhosList[] = FilhoDTO::fromArray($filho);
            }
        }

        $valorUnitarioCreche = (float) ($request->input('valor_unitario_creche', 558.78));

        $cassemsEventos = [];
        foreach ($request->input('cassems_eventos', []) as $evento) {
            if (!empty($evento['mes_inicio']) && isset($evento['aliquota_pct']) && $evento['aliquota_pct'] !== '') {
                $cassemsEventos[] = CassemsEventoDTO::fromArray($evento);
            }
        }

        $despesasForaFolha = [];
        foreach ($request->input('despesas_fora_folha', []) as $despesa) {
            if (!empty($despesa['mes_inicio']) && !empty($despesa['valor'])) {
                $despesasForaFolha[] = DespesaForaFolhaDTO::fromArray($despesa);
            }
        }

        $anosProjecao = max(1, min(40, (int) ($serverData['anos_projecao'] ?? 10)));
        $startMesAno = $serverData['start_mes_ano'] ?? date('Y-m');

        $projetar = fn (ServidorDTO $s) => $this->calculatorService->gerarProjecao(
            $s,
            $aqTempList,
            $eventosAuxilios,
            $reajustes,
            $anosProjecao,
            $startMesAno,
            $consignadosList,
            $aqPermanenteCursosList,
            $filhosList,
            $valorUnitarioCreche,
            $cassemsEventos,
            $despesasForaFolha
        );

        $projection = $projetar($servidor);

        // Os três cenários com os mesmos dados, trocando só as premissas de cada um
        $cenarios = [];
        foreach (SalaryCalculatorService::CENARIOS as $nome => $premissas) {
            $variante = clone $servidor;
            foreach ($premissas as $campo => $valor) {
                $variante->{$campo} = $valor;
            }
            $cenarios[$nome] = $this->resumirPorAno($projetar($variante));
        }

        return response()->json([
            'success' => true,
            'projection' => $projection,
            'carreira' => $this->calculatorService->gerarMapaCarreira($servidor),
            'cenarios' => $cenarios,
        ]);
    }

    /**
     * Totais anuais da projeção (líquido com 13º e férias, e líquido disponível).
     */
    private function resumirPorAno(array $projection): array
    {
        $anos = [];
        foreach ($projection as $mes) {
            $ano = substr($mes['mesAno'], 0, 4);
            $anos[$ano] ??= ['meses' => 0, 'liquido_total' => 0.0, 'liquido_disponivel' => 0.0];
            $anos[$ano]['meses']++;
            $anos[$ano]['liquido_total'] += $mes['liquido_total'];
            $anos[$ano]['liquido_disponivel'] += $mes['liquido_disponivel'];
        }

        return array_map(fn ($a) => [
            'meses' => $a['meses'],
            'liquido_total' => round($a['liquido_total'], 2),
            'liquido_disponivel' => round($a['liquido_disponivel'], 2),
        ], $anos);
    }

    /**
     * Marca ou desmarca um holerite real como outlier (fora das métricas de erro).
     */
    public function toggleOutlier(Request $request, string $mesAno)
    {
        $holerite = auth()->user()->salaryPayslips()->where('mes_ano', $mesAno)->firstOrFail();
        $holerite->update(['outlier' => $request->boolean('outlier', !$holerite->outlier)]);

        return response()->json([
            'success' => true,
            'mes_ano' => $holerite->mes_ano,
            'outlier' => $holerite->outlier,
        ]);
    }

    public function saveProfile(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $configData = [
            'servidor' => $request->input('servidor', []),
            'aq_temporario' => $request->input('aq_temporario', []),
            'eventos_auxilios' => $request->input('eventos_auxilios', []),
            'reajustes' => $request->input('reajustes', []),
            'consignados_list' => $request->input('consignados_list', []),
            'aq_permanente_cursos' => $request->input('aq_permanente_cursos', []),
            'filhos' => $request->input('filhos', []),
            'valor_unitario_creche' => $request->input('valor_unitario_creche', 558.78),
            'cassems_eventos' => $request->input('cassems_eventos', []),
            'despesas_fora_folha' => $request->input('despesas_fora_folha', []),
        ];

        $profile = auth()->user()->salaryProfiles()->create([
            'nome' => $request->input('nome'),
            'config_data' => $configData,
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'profile' => $profile,
            'profiles' => auth()->user()->salaryProfiles()->orderBy('nome')->get(),
        ]);
    }

    public function updateProfile(Request $request, $id)
    {
        $profile = auth()->user()->salaryProfiles()->findOrFail($id);

        $configData = [
            'servidor' => $request->input('servidor', []),
            'aq_temporario' => $request->input('aq_temporario', []),
            'eventos_auxilios' => $request->input('eventos_auxilios', []),
            'reajustes' => $request->input('reajustes', []),
            'consignados_list' => $request->input('consignados_list', []),
            'aq_permanente_cursos' => $request->input('aq_permanente_cursos', []),
            'filhos' => $request->input('filhos', []),
            'valor_unitario_creche' => $request->input('valor_unitario_creche', 558.78),
            'cassems_eventos' => $request->input('cassems_eventos', []),
            'despesas_fora_folha' => $request->input('despesas_fora_folha', []),
        ];

        $profile->update([
            'config_data' => $configData,
        ]);

        return response()->json([
            'success' => true,
            'profile' => $profile,
            'profiles' => auth()->user()->salaryProfiles()->orderBy('nome')->get(),
        ]);
    }

    public function loadProfile($id)
    {
        $profile = auth()->user()->salaryProfiles()->findOrFail($id);

        return response()->json([
            'success' => true,
            'profile' => $profile,
        ]);
    }

    public function deleteProfile($id)
    {
        $profile = auth()->user()->salaryProfiles()->findOrFail($id);
        $profile->delete();

        return response()->json([
            'success' => true,
            'profiles' => auth()->user()->salaryProfiles()->orderBy('nome')->get(),
        ]);
    }

    public function activateProfile($id)
    {
        if ($id === 'clear' || $id === 'none') {
            auth()->user()->salaryProfiles()->update(['is_active' => false]);
        } else {
            $profile = auth()->user()->salaryProfiles()->findOrFail($id);
            auth()->user()->salaryProfiles()->update(['is_active' => false]);
            $profile->update(['is_active' => true]);
        }

        return response()->json([
            'success' => true,
            'profiles' => auth()->user()->salaryProfiles()->orderBy('nome')->get(),
        ]);
    }
}
