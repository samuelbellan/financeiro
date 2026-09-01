<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudyGoal;
use App\Models\StudyLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EstudosController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        
        // Buscar todas as metas do usuário
        $goals = StudyGoal::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        
        // Determinar modo de visualização / meta ativa
        if ($request->has('goal')) {
            $viewGoalParam = $request->query('goal');
            session(['active_study_goal_id' => $viewGoalParam]);
        }
        
        $activeGoalId = session('active_study_goal_id', 'geral'); // Padrão: Visão Geral
        $activeGoal = null;
        $viewMode = 'geral'; // geral, avulso, ou goal

        if ($activeGoalId === 'geral' || $activeGoalId === 'all') {
            $viewMode = 'geral';
        } elseif ($activeGoalId === 'avulso' || $activeGoalId === 'none') {
            $viewMode = 'avulso';
        } else {
            $foundGoal = StudyGoal::where('user_id', $userId)->find($activeGoalId);
            if ($foundGoal) {
                $activeGoal = $foundGoal;
                $viewMode = 'goal';
            } else {
                $viewMode = 'geral';
                session(['active_study_goal_id' => 'geral']);
            }
        }

        // Filtro por Período
        $periodo = $request->query('periodo', session('study_filter_periodo', 'all'));
        session(['study_filter_periodo' => $periodo]);

        // Construir a Query de Logs
        $logsQuery = StudyLog::where('user_id', $userId)->with('goal');

        if ($viewMode === 'goal' && $activeGoal) {
            $logsQuery->where('study_goal_id', $activeGoal->id);
        } elseif ($viewMode === 'avulso') {
            $logsQuery->whereNull('study_goal_id');
        }

        // Aplicar Filtro de Data caso selecionado
        $hoje = Carbon::today();
        if ($periodo === 'month') {
            $logsQuery->whereYear('data', $hoje->year)->whereMonth('data', $hoje->month);
        } elseif ($periodo === '30days') {
            $logsQuery->where('data', '>=', Carbon::today()->subDays(30));
        } elseif ($periodo === 'year') {
            $logsQuery->whereYear('data', $hoje->year);
        } elseif ($periodo === 'custom' && $request->filled('data_inicio') && $request->filled('data_fim')) {
            $logsQuery->whereBetween('data', [$request->input('data_inicio'), $request->input('data_fim')]);
        }

        $logs = $logsQuery->orderBy('data', 'desc')->orderBy('created_at', 'desc')->get();

        // Todos os logs do usuário no modo atual (para Heatmap e gráficos completos)
        $allLogsQuery = StudyLog::where('user_id', $userId);
        if ($viewMode === 'goal' && $activeGoal) {
            $allLogsQuery->where('study_goal_id', $activeGoal->id);
        } elseif ($viewMode === 'avulso') {
            $allLogsQuery->whereNull('study_goal_id');
        }
        $allLogs = $allLogsQuery->get();

        // Totais e Métricas
        $totalHorasEstudadas = $logs->sum('horas');
        if ($activeGoal) {
            $totalHorasEstudadas += ($activeGoal->horas_iniciais ?: 0);
        }

        $horasMeta = ($activeGoal && $activeGoal->horas_meta > 0) ? (float)$activeGoal->horas_meta : null;
        $progressoPercentual = ($horasMeta && $horasMeta > 0) ? min(100, round(($totalHorasEstudadas / $horasMeta) * 100, 1)) : 0;

        // Mapeamento por data
        $logsPorData = $allLogs->groupBy(function($log) {
            return Carbon::parse($log->data)->format('Y-m-d');
        })->map(function($group) {
            return $group->sum('horas');
        });

        // Heatmap dos últimos 105 dias (15 semanas)
        $heatmapData = [];
        $heatmapStart = Carbon::today()->subDays(104);
        for ($d = clone $heatmapStart; $d->lte($hoje); $d->addDay()) {
            $dateStr = $d->format('Y-m-d');
            $heatmapData[$dateStr] = [
                'date' => $d->format('d/m/Y'),
                'dayOfWeek' => $d->dayOfWeekIso,
                'hours' => $logsPorData->get($dateStr, 0),
            ];
        }

        // Dias Reais Estudados (onde horas > 0)
        $diasEstudados = $logsPorData->filter(fn($h) => $h > 0)->count();

        // Calcular Streak Atual (dias consecutivos de estudo)
        $streakAtual = 0;
        $checkDate = Carbon::today();
        if (!$logsPorData->has($checkDate->format('Y-m-d')) || $logsPorData->get($checkDate->format('Y-m-d')) == 0) {
            $checkDate->subDay();
        }
        while ($logsPorData->has($checkDate->format('Y-m-d')) && $logsPorData->get($checkDate->format('Y-m-d')) > 0) {
            $streakAtual++;
            $checkDate->subDay();
        }

        // Data de Início das métricas
        if ($activeGoal && $activeGoal->data_inicio) {
            $dataInicio = Carbon::parse($activeGoal->data_inicio);
        } else {
            $primeiroLogData = $allLogs->min('data');
            $dataInicio = $primeiroLogData ? Carbon::parse($primeiroLogData) : Carbon::today()->subDays(30);
        }
        $diasDesdeInicio = max(1, $dataInicio->diffInDays($hoje) + 1);

        // Médias diárias
        $mediaGeralDiaria = $diasDesdeInicio > 0 ? ($totalHorasEstudadas / $diasDesdeInicio) : 0;
        $mediaRealDiaria = ($diasEstudados > 0) ? ($totalHorasEstudadas / $diasEstudados) : $mediaGeralDiaria;
        
        // Se a média real for zero por falta de logs individuais, usar a média geral do histórico
        $mediaProjecao = $mediaRealDiaria > 0 ? $mediaRealDiaria : $mediaGeralDiaria;

        // Horas restantes e projeções
        $horasRestantes = $horasMeta ? max(0, $horasMeta - $totalHorasEstudadas) : 0;
        $ritmoPlanejado = ($activeGoal && $activeGoal->horas_diarias_padrao > 0) ? (float)$activeGoal->horas_diarias_padrao : 2.0;

        $diasParaTerminarPlanejado = ($horasRestantes > 0 && $ritmoPlanejado > 0) ? ceil($horasRestantes / $ritmoPlanejado) : 0;
        $dataProjetadaPlanejada = Carbon::today()->addDays($diasParaTerminarPlanejado);

        if ($horasRestantes > 0 && $mediaProjecao > 0) {
            $diasParaTerminarReal = ceil($horasRestantes / $mediaProjecao);
            $dataProjetadaReal = Carbon::today()->addDays($diasParaTerminarReal);
        } else {
            $diasParaTerminarReal = null;
            $dataProjetadaReal = null;
        }

        $statusPrazo = null;
        $diasDeDiferencaPrazo = null;
        $horasNecessariasDia = null;
        if ($activeGoal && $activeGoal->data_limite && $horasRestantes > 0) {
            $dataLimite = Carbon::parse($activeGoal->data_limite);
            $diasAteLimite = Carbon::today()->diffInDays($dataLimite, false);
            
            if ($diasAteLimite < 0) {
                $statusPrazo = 'atrasado';
                $diasDeDiferencaPrazo = abs($diasAteLimite);
            } else {
                $horasNecessariasDia = $diasAteLimite > 0 ? ($horasRestantes / $diasAteLimite) : $horasRestantes;
                $statusPrazo = 'em_andamento';
            }
        }

        $stats = [
            'view_mode' => $viewMode,
            'total_estudado' => $totalHorasEstudadas,
            'meta' => $horasMeta,
            'progresso' => $progressoPercentual,
            'dias_desde_inicio' => $diasDesdeInicio,
            'dias_estudados' => $diasEstudados,
            'streak' => $streakAtual,
            'media_real' => round($mediaRealDiaria, 2),
            'media_geral' => round($mediaGeralDiaria, 2),
            'horas_restantes' => $horasRestantes,
            'dias_restantes_planejado' => $diasParaTerminarPlanejado,
            'data_projetada_planejada' => $dataProjetadaPlanejada->format('d/m/Y'),
            'dias_restantes_real' => $diasParaTerminarReal,
            'data_projetada_real' => $dataProjetadaReal ? $dataProjetadaReal->format('d/m/Y') : 'N/A',
            'status_prazo' => $statusPrazo,
            'dias_diferenca_prazo' => $diasDeDiferencaPrazo,
            'horas_necessarias_dia' => $horasNecessariasDia ? round($horasNecessariasDia, 2) : null,
            'periodo' => $periodo,
        ];

        // Gráfico de Progresso Acumulado
        $datasAcumuladas = [];
        $horasAcumuladas = [];
        $metaAcumuladaIdeal = [];

        $primeiraData = clone $dataInicio;
        $ultimaData = Carbon::today();
        if ($primeiraData->gt($ultimaData)) {
            $primeiraData = clone $ultimaData;
        }

        $progressoAcumulado = ($activeGoal && $activeGoal->horas_iniciais) ? (float)$activeGoal->horas_iniciais : 0;
        $passoIdeal = $ritmoPlanejado;
        $acumuladoIdeal = 0;

        $logsAgrupadosGrafico = $allLogs->groupBy(function($log) {
            return Carbon::parse($log->data)->format('Y-m-d');
        });

        for ($d = clone $primeiraData; $d->lte($ultimaData); $d->addDay()) {
            $dateStr = $d->format('Y-m-d');
            $horasDoDia = $logsAgrupadosGrafico->has($dateStr) ? $logsAgrupadosGrafico->get($dateStr)->sum('horas') : 0;
            $progressoAcumulado += $horasDoDia;
            $acumuladoIdeal += $passoIdeal;

            $datasAcumuladas[] = $d->format('d/m');
            $horasAcumuladas[] = round($progressoAcumulado, 1);
            if ($horasMeta) {
                $metaAcumuladaIdeal[] = round(min($horasMeta, $acumuladoIdeal), 1);
            } else {
                $metaAcumuladaIdeal[] = round($acumuladoIdeal, 1);
            }
        }

        $chartData = [
            'dates' => $datasAcumuladas,
            'real' => $horasAcumuladas,
            'target' => $metaAcumuladaIdeal,
        ];

        return view('estudos.index', compact('goals', 'activeGoal', 'viewMode', 'stats', 'logs', 'chartData', 'heatmapData'));
    }

    public function storeGoal(Request $request)
    {
        $userId = Auth::id();
        
        $request->validate([
            'nome' => 'required|string|max:255',
            'horas_meta' => 'nullable|numeric|min:0',
            'horas_iniciais' => 'nullable|numeric|min:0',
            'data_inicio' => 'required|date',
            'data_limite' => 'nullable|date|after_or_equal:data_inicio',
            'horas_diarias_padrao' => 'nullable|numeric|min:0.01|max:24',
            'carga_seg' => 'nullable|numeric|min:0|max:24',
            'carga_ter' => 'nullable|numeric|min:0|max:24',
            'carga_qua' => 'nullable|numeric|min:0|max:24',
            'carga_qui' => 'nullable|numeric|min:0|max:24',
            'carga_sex' => 'nullable|numeric|min:0|max:24',
            'carga_sab' => 'nullable|numeric|min:0|max:24',
            'carga_dom' => 'nullable|numeric|min:0|max:24',
        ]);
        
        $goalId = $request->input('goal_id');
        $data = $request->all();
        $data['horas_iniciais'] = $request->input('horas_iniciais') ?: 0;
        $data['horas_diarias_padrao'] = $request->input('horas_diarias_padrao') ?: 2.0;
        
        $defaultCarga = $data['horas_diarias_padrao'];
        $data['carga_seg'] = $request->input('carga_seg') ?? $defaultCarga;
        $data['carga_ter'] = $request->input('carga_ter') ?? $defaultCarga;
        $data['carga_qua'] = $request->input('carga_qua') ?? $defaultCarga;
        $data['carga_qui'] = $request->input('carga_qui') ?? $defaultCarga;
        $data['carga_sex'] = $request->input('carga_sex') ?? $defaultCarga;
        $data['carga_sab'] = $request->input('carga_sab') ?? $defaultCarga;
        $data['carga_dom'] = $request->input('carga_dom') ?? $defaultCarga;
        
        if ($goalId) {
            $goal = StudyGoal::where('user_id', $userId)->findOrFail($goalId);
            $goal->update($data);
            $message = 'Meta de estudo atualizada com sucesso!';
        } else {
            $goal = new StudyGoal($data);
            $goal->user_id = $userId;
            $goal->save();
            $message = 'Nova meta de estudo criada!';
        }
        
        session(['active_study_goal_id' => $goal->id]);
        
        return redirect()->route('estudos.index')->with('success', $message);
    }

    public function activateGoal($id)
    {
        $userId = Auth::id();
        if ($id === 'geral' || $id === 'avulso') {
            session(['active_study_goal_id' => $id]);
            return redirect()->route('estudos.index')->with('success', "Modo de visualização alterado.");
        }

        $goal = StudyGoal::where('user_id', $userId)->findOrFail($id);
        session(['active_study_goal_id' => $goal->id]);
        
        return redirect()->route('estudos.index')->with('success', "Meta \"{$goal->nome}\" ativada.");
    }

    public function destroyGoal($id)
    {
        $userId = Auth::id();
        $goal = StudyGoal::where('user_id', $userId)->findOrFail($id);
        
        $goal->delete();
        
        if (session('active_study_goal_id') == $id) {
            session(['active_study_goal_id' => 'geral']);
        }
        
        return redirect()->route('estudos.index')->with('success', 'Meta de estudo excluída.');
    }

    public function storeLog(Request $request)
    {
        $userId = Auth::id();
        
        $request->validate([
            'study_goal_id' => 'nullable',
            'data' => 'required|date',
            'horas_inteiras' => 'required|integer|min:0|max:24',
            'minutos' => 'required|integer|min:0|max:59',
            'observacoes' => 'nullable|string|max:1000',
        ]);
        
        $goalId = $request->input('study_goal_id');
        if ($goalId === 'none' || $goalId === 'avulso' || $goalId === '' || $goalId === 'geral') {
            $goalId = null;
        }

        if ($goalId) {
            StudyGoal::where('user_id', $userId)->findOrFail($goalId);
        }
        
        $horas = $request->input('horas_inteiras') + ($request->input('minutos') / 60);
        
        if ($horas <= 0) {
            return redirect()->back()->withErrors(['horas_inteiras' => 'Você precisa registrar mais que 0 minutos estudados.']);
        }
        
        $log = new StudyLog();
        $log->user_id = $userId;
        $log->study_goal_id = $goalId;
        $log->data = $request->input('data');
        $log->horas = $horas;
        $log->observacoes = $request->input('observacoes');
        $log->save();
        
        return redirect()->route('estudos.index')->with('success', 'Tempo de estudo registrado com sucesso!');
    }

    public function updateLog(Request $request, $id)
    {
        $userId = Auth::id();
        $log = StudyLog::where('user_id', $userId)->findOrFail($id);
        
        $request->validate([
            'study_goal_id' => 'nullable',
            'data' => 'required|date',
            'horas_inteiras' => 'required|integer|min:0|max:24',
            'minutos' => 'required|integer|min:0|max:59',
            'observacoes' => 'nullable|string|max:1000',
        ]);
        
        $goalId = $request->input('study_goal_id');
        if ($goalId === 'none' || $goalId === 'avulso' || $goalId === '' || $goalId === 'geral') {
            $goalId = null;
        }

        if ($goalId) {
            StudyGoal::where('user_id', $userId)->findOrFail($goalId);
        }
        
        $horas = $request->input('horas_inteiras') + ($request->input('minutos') / 60);
        
        if ($horas <= 0) {
            return redirect()->back()->withErrors(['horas_inteiras' => 'Você precisa registrar mais que 0 minutos estudados.']);
        }
        
        $log->study_goal_id = $goalId;
        $log->data = $request->input('data');
        $log->horas = $horas;
        $log->observacoes = $request->input('observacoes');
        $log->save();
        
        return redirect()->route('estudos.index')->with('success', 'Registro de estudo atualizado com sucesso!');
    }

    public function destroyLog($id)
    {
        $userId = Auth::id();
        $log = StudyLog::where('user_id', $userId)->findOrFail($id);
        $log->delete();
        
        return redirect()->route('estudos.index')->with('success', 'Registro de estudo excluído.');
    }
}
