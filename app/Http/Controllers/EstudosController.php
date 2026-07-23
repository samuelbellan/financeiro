<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudyGoal;
use App\Models\StudyLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EstudosController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        
        // Buscar todas as metas do usuário
        $goals = StudyGoal::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        
        // Buscar meta ativa da sessão, se não existir pega a primeira, se não houver metas será nulo
        $activeGoalId = session('active_study_goal_id');
        $activeGoal = null;
        
        if ($activeGoalId) {
            $activeGoal = StudyGoal::where('user_id', $userId)->find($activeGoalId);
        }
        
        if (!$activeGoal && $goals->isNotEmpty()) {
            $activeGoal = $goals->first();
            session(['active_study_goal_id' => $activeGoal->id]);
        }
        
        $stats = null;
        $logs = collect();
        $chartData = [
            'dates' => [],
            'real' => [],
            'target' => [],
        ];
        $heatmapData = [];

        if ($activeGoal) {
            // Logs ordenados do mais recente
            $logs = StudyLog::where('study_goal_id', $activeGoal->id)
                ->orderBy('data', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
                
            $totalHorasEstudadas = $logs->sum('horas') + $activeGoal->horas_iniciais;
            $horasMeta = $activeGoal->horas_meta;
            $progressoPercentual = $horasMeta > 0 ? min(100, round(($totalHorasEstudadas / $horasMeta) * 100, 1)) : 0;
            
            // Dias corridos desde o início
            $dataInicio = Carbon::parse($activeGoal->data_inicio);
            $hoje = Carbon::today();
            $diasDesdeInicio = max(1, $dataInicio->diffInDays($hoje) + 1);
            
            // Mapeamento de logs para consistência (heatmap dos últimos 105 dias - 15 semanas)
            $logsPorData = $logs->groupBy(function($log) {
                return $log->data->format('Y-m-d');
            })->map(function($group) {
                return $group->sum('horas');
            });
            
            $heatmapStart = Carbon::today()->subDays(104); // 15 semanas atrás
            for ($d = clone $heatmapStart; $d->lte($hoje); $d->addDay()) {
                $dateStr = $d->format('Y-m-d');
                $heatmapData[$dateStr] = [
                    'date' => $d->format('d/m/Y'),
                    'dayOfWeek' => $d->dayOfWeekIso, // 1 (Mon) to 7 (Sun)
                    'hours' => $logsPorData->get($dateStr, 0),
                ];
            }
            
            // Dias reais estudados (dias onde horas > 0)
            $diasEstudados = $logsPorData->filter(fn($h) => $h > 0)->count();
            
            // Médias diárias
            $mediaRealDiaria = $diasEstudados > 0 ? ($totalHorasEstudadas / $diasEstudados) : 0;
            $mediaGeralDiaria = $totalHorasEstudadas / $diasDesdeInicio;
            
            // Horas restantes
            $horasRestantes = max(0, $horasMeta - $totalHorasEstudadas);
            
            // Ritmo futuro simulado/planejado
            $ritmoPlanejado = $activeGoal->horas_diarias_padrao > 0 ? $activeGoal->horas_diarias_padrao : 2.0;
            
            // Projeções
            $diasParaTerminarPlanejado = $ritmoPlanejado > 0 ? ceil($horasRestantes / $ritmoPlanejado) : 0;
            $dataProjetadaPlanejada = Carbon::today()->addDays($diasParaTerminarPlanejado);
            
            if ($mediaGeralDiaria > 0) {
                $diasParaTerminarGeral = ceil($horasRestantes / $mediaGeralDiaria);
                $dataProjetadaGeral = Carbon::today()->addDays($diasParaTerminarGeral);
            } else {
                $diasParaTerminarGeral = null;
                $dataProjetadaGeral = null;
            }

            if ($mediaRealDiaria > 0) {
                $diasParaTerminarReal = ceil($horasRestantes / $mediaRealDiaria);
                $dataProjetadaReal = Carbon::today()->addDays($diasParaTerminarReal);
            } else {
                $diasParaTerminarReal = null;
                $dataProjetadaReal = null;
            }

            // Status em relação ao prazo final (se houver)
            $statusPrazo = null;
            $diasDeDiferencaPrazo = null;
            if ($activeGoal->data_limite) {
                $dataLimite = Carbon::parse($activeGoal->data_limite);
                $diasAteLimite = Carbon::today()->diffInDays($dataLimite, false);
                
                if ($diasAteLimite < 0) {
                    $statusPrazo = 'atrasado'; // Data limite já passou
                    $diasDeDiferencaPrazo = abs($diasAteLimite);
                } else {
                    // Horas por dia necessárias a partir de hoje
                    $horasNecessariasDia = $diasAteLimite > 0 ? ($horasRestantes / $diasAteLimite) : $horasRestantes;
                    $statusPrazo = 'em_andamento';
                    $stats['horas_necessarias_dia'] = round($horasNecessariasDia, 2);
                }
            }

            $stats = [
                'total_estudado' => $totalHorasEstudadas,
                'meta' => $horasMeta,
                'progresso' => $progressoPercentual,
                'dias_desde_inicio' => $diasDesdeInicio,
                'dias_estudados' => $diasEstudados,
                'media_real' => round($mediaRealDiaria, 2),
                'media_geral' => round($mediaGeralDiaria, 2),
                'horas_restantes' => $horasRestantes,
                'dias_restantes_planejado' => $diasParaTerminarPlanejado,
                'data_projetada_planejada' => $dataProjetadaPlanejada->format('d/m/Y'),
                'dias_restantes_real' => $diasParaTerminarReal,
                'data_projetada_real' => $dataProjetadaReal ? $dataProjetadaReal->format('d/m/Y') : 'N/A',
                'status_prazo' => $statusPrazo,
                'dias_diferenca_prazo' => $diasDeDiferencaPrazo,
            ];

            // Dados para o Gráfico de Progresso Acumulado
            $datasAcumuladas = [];
            $horasAcumuladas = [];
            $metaAcumuladaIdeal = [];
            
            $primeiraData = Carbon::parse($activeGoal->data_inicio);
            $ultimaData = Carbon::today();
            if ($primeiraData->gt($ultimaData)) {
                $primeiraData = clone $ultimaData;
            }
            
            $progressoAcumulado = $activeGoal->horas_iniciais;
            $passoIdeal = $activeGoal->horas_diarias_padrao;
            $acumuladoIdeal = 0;
            
            $logsAgrupadosGrafico = $logs->groupBy(function($log) {
                return $log->data->format('Y-m-d');
            });
            
            for ($d = clone $primeiraData; $d->lte($ultimaData); $d->addDay()) {
                $dateStr = $d->format('Y-m-d');
                $horasDoDia = $logsAgrupadosGrafico->has($dateStr) ? $logsAgrupadosGrafico->get($dateStr)->sum('horas') : 0;
                $progressoAcumulado += $horasDoDia;
                $acumuladoIdeal += $passoIdeal;
                
                $datasAcumuladas[] = $d->format('d/m');
                $horasAcumuladas[] = round($progressoAcumulado, 1);
                $metaAcumuladaIdeal[] = round(min($activeGoal->horas_meta, $acumuladoIdeal), 1);
            }
            
            $chartData = [
                'dates' => $datasAcumuladas,
                'real' => $horasAcumuladas,
                'target' => $metaAcumuladaIdeal,
            ];
        }
        
        return view('estudos.index', compact('goals', 'activeGoal', 'stats', 'logs', 'chartData', 'heatmapData'));
    }

    public function storeGoal(Request $request)
    {
        $userId = Auth::id();
        
        $request->validate([
            'nome' => 'required|string|max:255',
            'horas_meta' => 'required|numeric|min:0.1',
            'horas_iniciais' => 'nullable|numeric|min:0',
            'data_inicio' => 'required|date',
            'data_limite' => 'nullable|date|after_or_equal:data_inicio',
            'horas_diarias_padrao' => 'required|numeric|min:0.01|max:24',
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
        
        $defaultCarga = $request->input('horas_diarias_padrao') ?: 2.0;
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
            session()->forget('active_study_goal_id');
        }
        
        return redirect()->route('estudos.index')->with('success', 'Meta de estudo excluída.');
    }

    public function storeLog(Request $request)
    {
        $userId = Auth::id();
        
        $request->validate([
            'study_goal_id' => 'required|exists:study_goals,id',
            'data' => 'required|date',
            'horas_inteiras' => 'required|integer|min:0|max:24',
            'minutos' => 'required|integer|min:0|max:59',
            'observacoes' => 'nullable|string|max:1000',
        ]);
        
        // Validar propriedade da meta
        $goal = StudyGoal::where('user_id', $userId)->findOrFail($request->input('study_goal_id'));
        
        // Converter horas e minutos para decimal
        $horas = $request->input('horas_inteiras') + ($request->input('minutos') / 60);
        
        if ($horas <= 0) {
            return redirect()->back()->withErrors(['horas_inteiras' => 'Você precisa registrar mais que 0 minutos estudados.']);
        }
        
        $log = new StudyLog();
        $log->user_id = $userId;
        $log->study_goal_id = $goal->id;
        $log->data = $request->input('data');
        $log->horas = $horas;
        $log->observacoes = $request->input('observacoes');
        $log->save();
        
        return redirect()->route('estudos.index')->with('success', 'Tempo de estudo registrado com sucesso!');
    }

    public function destroyLog($id)
    {
        $userId = Auth::id();
        $log = StudyLog::where('user_id', $userId)->findOrFail($id);
        $log->delete();
        
        return redirect()->route('estudos.index')->with('success', 'Registro de estudo excluído.');
    }
}
