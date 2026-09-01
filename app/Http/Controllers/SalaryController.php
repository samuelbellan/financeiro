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
        // Define default mock values for Samuel's scenario
        $defaultConfig = [
            'dt_exercicio' => '2025-05-05',
            'cargo' => 'TNSU',
            'referencia_inicial' => '1',
            'aq_permanente_pct' => 8.0,
            'regime_integral' => false,
            'outros_adicionais_pct' => 0.0,
            'dependentes_irrf' => 1,
            'dependentes_cassems' => 3,
            'tem_conjuge' => false,
            'consignados' => 697.00,
            'salario_substituicao' => 1634.81,
            'funcao_comissao_valor' => 0.0,
            'teto_rgps' => true,
            'anos_projecao' => 10,
            'start_mes_ano' => '2025-05', // Default starting from entry month
            'reajuste_auxilio_pct' => 3.0,
            'reajuste_mes' => 5,
        ];

        $defaultAqTemp = [
            ['nome' => 'Capacitação A (1%)', 'percentual' => 1.0, 'mes_inicio' => '2025-06', 'mes_fim' => '2029-05'],
            ['nome' => 'Capacitação B (1%)', 'percentual' => 1.0, 'mes_inicio' => '2025-08', 'mes_fim' => '2029-07'],
            ['nome' => 'Capacitação C (1%)', 'percentual' => 1.0, 'mes_inicio' => '2025-10', 'mes_fim' => '2029-09'],
            ['nome' => 'Capacitação D (1%)', 'percentual' => 1.0, 'mes_inicio' => '2025-12', 'mes_fim' => '2029-11'],
            ['nome' => 'Capacitação E (1%)', 'percentual' => 1.0, 'mes_inicio' => '2026-06', 'mes_fim' => '2030-05'],
        ];

        $defaultEvents = [
            ['mes_ano_inicio' => '2025-05', 'mes_ano_fim' => null, 'tipo_auxilio' => 'AUXILIO_ALIMENTACAO', 'valor' => 2200.00, 'acao' => 'CRIAR'],
            ['mes_ano_inicio' => '2025-05', 'mes_ano_fim' => null, 'tipo_auxilio' => 'AUXILIO_CRECHE', 'valor' => 558.78, 'acao' => 'CRIAR'],
            ['mes_ano_inicio' => '2025-05', 'mes_ano_fim' => null, 'tipo_auxilio' => 'AUXILIO_TRANSPORTE', 'valor' => 1000.00, 'acao' => 'CRIAR'],
        ];

        $defaultReajustes = [
            2027 => 4.5,
            2028 => 3.0,
            2030 => 5.0,
        ];

        $defaultConsignados = [
            ['valor' => 697.00, 'mes_inicio' => '2025-05', 'mes_fim' => '2029-04']
        ];

        $defaultAqPermanenteCursos = [
            ['nome' => '1º Curso AQ Permanente', 'percentual' => 2.0, 'mes_inicio' => '2025-09']
        ];

        $defaultFilhos = [
            ['nome' => 'Filho 1', 'dt_nascimento' => '2023-05-05', 'idade_escola' => 2]
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
        }

        return view('salary.index', compact(
            'defaultConfig', 
            'defaultAqTemp', 
            'defaultEvents', 
            'defaultReajustes',
            'defaultConsignados',
            'defaultAqPermanenteCursos',
            'defaultFilhos',
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

        $anosProjecao = (int) ($serverData['anos_projecao'] ?? 10);
        $startMesAno = $serverData['start_mes_ano'] ?? date('Y-m');

        $projection = $this->calculatorService->gerarProjecao(
            $servidor,
            $aqTempList,
            $eventosAuxilios,
            $reajustes,
            $anosProjecao,
            $startMesAno,
            $consignadosList,
            $aqPermanenteCursosList,
            $filhosList,
            $valorUnitarioCreche
        );

        return response()->json([
            'success' => true,
            'projection' => $projection
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
