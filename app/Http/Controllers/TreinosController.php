<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\ExercisePersonalRecord;
use App\Models\GearItem;
use App\Models\RunningLog;
use App\Models\UserBodyMetric;
use App\Models\WorkoutGoal;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use App\Models\DailyLog;
use App\Models\Measurement;
use App\Models\ProgressPhoto;
use App\Services\BodyTrackerService;
use App\Services\FitnessAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TreinosController extends Controller
{
    protected BodyTrackerService $trackerService;
    protected FitnessAiService $fitnessAi;

    public function __construct(BodyTrackerService $trackerService, FitnessAiService $fitnessAi)
    {
        $this->trackerService = $trackerService;
        $this->fitnessAi = $fitnessAi;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;

        // Fichas de treino do usuário
        $workoutPlans = WorkoutPlan::where('user_id', $userId)
            ->withCount('items')
            ->orderBy('nome')
            ->get();

        // Sessões recentes de treino
        $recentSessions = WorkoutSession::where('user_id', $userId)
            ->with(['plan', 'sessionExercises.exercise', 'runningLog', 'stretchingLog'])
            ->orderByDesc('data_hora_inicio')
            ->limit(10)
            ->get();

        // Resumo semanal (últimos 7 dias)
        $inicioSemana = now()->startOfWeek();
        $sessoesEstaSemana = WorkoutSession::where('user_id', $userId)
            ->where('data_hora_inicio', '>=', $inicioSemana)
            ->count();

        // Km de corrida nesta semana
        $kmCorridaSemana = RunningLog::whereHas('session', function ($q) use ($userId, $inicioSemana) {
            $q->where('user_id', $userId)->where('data_hora_inicio', '>=', $inicioSemana);
        })->sum('distancia_km');

        // Tênis e Equipamentos Ativos
        $activeGears = GearItem::where('user_id', $userId)
            ->where('ativo', true)
            ->get();

        // Última medição corporal (compatibilidade e novo BodyTracker)
        $latestMetric = UserBodyMetric::where('user_id', $userId)
            ->orderByDesc('data_medicao')
            ->first();

        $latestMeasurement = Measurement::where('user_id', $userId)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();

        // Recordes Pessoais (PRs)
        $personalRecords = ExercisePersonalRecord::where('user_id', $userId)
            ->with('exercise')
            ->orderByDesc('data_recorde')
            ->limit(8)
            ->get();

        // Metas ativas
        $activeGoals = WorkoutGoal::where('user_id', $userId)
            ->where('ativo', true)
            ->get();

        // Estatísticas do Catálogo de Exercícios
        $exercisesStats = [
            'total' => Exercise::ativo()->count(),
            'musculacao' => Exercise::ativo()->musculacao()->count(),
            'corrida' => Exercise::ativo()->corrida()->count(),
            'alongamento' => Exercise::ativo()->alongamento()->count(),
        ];

        // Dados do BodyTracker
        $todayDailyLog = $this->trackerService->getDailyLogForDate($userId, now()->toDateString());
        $trackerSummary = $this->trackerService->getSummaryDeltas($userId);
        $recentPhotos = $this->trackerService->getPhotos($userId);
        $consistencyStreak = $this->trackerService->getConsistencyStreak($userId);

        // Histórico de diários para o Mapa de Calor / Heatmap (último ano / 52 semanas no estilo GitHub)
        $startDateHeatmap = now()->subYear()->subDays(7)->toDateString();
        $dailyLogsHistory = DailyLog::where('user_id', $userId)
            ->where('date', '>=', $startDateHeatmap)
            ->orderBy('date', 'asc')
            ->get();

        $dailyLogsMap = [];
        foreach ($dailyLogsHistory as $dLog) {
            $dateKey = is_string($dLog->date) ? substr($dLog->date, 0, 10) : $dLog->date->format('Y-m-d');
            $dailyLogsMap[$dateKey] = [
                'date' => $dateKey,
                'adherence_score' => $dLog->adherence_score,
                'workout_done' => (bool) $dLog->workout_done,
                'workout_type' => $dLog->workout_type,
                'workout_duration_min' => $dLog->workout_duration_min,
                'water_volume_ml' => $dLog->water_volume_ml,
                'breakfast_clean' => (bool) $dLog->breakfast_clean,
                'lunch_clean' => (bool) $dLog->lunch_clean,
                'snack_done' => (bool) $dLog->snack_done,
                'dinner_clean' => (bool) $dLog->dinner_clean,
                'notes' => $dLog->notes,
            ];
        }

        // Catálogo de Exercícios para os selects nos modais
        $allExercises = Exercise::ativo()->orderBy('nome')->get();

        return view('treinos.index', compact(
            'workoutPlans',
            'recentSessions',
            'sessoesEstaSemana',
            'kmCorridaSemana',
            'activeGears',
            'latestMetric',
            'latestMeasurement',
            'personalRecords',
            'activeGoals',
            'exercisesStats',
            'todayDailyLog',
            'trackerSummary',
            'recentPhotos',
            'consistencyStreak',
            'allExercises',
            'dailyLogsMap'
        ));
    }

    /**
     * POST /treinos/plans
     * Cria uma nova ficha de treino.
     */
    public function storePlan(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'identificador_letra' => 'nullable|string|max:10',
            'modalidade' => 'required|string|in:musculacao,corrida,alongamento,misto',
            'frequencia_semanal_sugerida' => 'nullable|integer|min:1|max:7',
            'descricao' => 'nullable|string|max:1000',
            'exercise_ids' => 'nullable|array',
            'exercise_ids.*' => 'integer|exists:exercises,id',
        ]);

        $plan = WorkoutPlan::create([
            'user_id' => $userId,
            'nome' => $validated['nome'],
            'identificador_letra' => $validated['identificador_letra'] ? strtoupper($validated['identificador_letra']) : null,
            'modalidade' => $validated['modalidade'],
            'frequencia_semanal_sugerida' => $validated['frequencia_semanal_sugerida'] ?? 2,
            'descricao' => $validated['descricao'] ?? null,
            'ativo' => true,
        ]);

        if (!empty($validated['exercise_ids'])) {
            foreach ($validated['exercise_ids'] as $index => $exId) {
                \App\Models\WorkoutPlanItem::create([
                    'workout_plan_id' => $plan->id,
                    'exercise_id' => $exId,
                    'ordem' => $index + 1,
                    'series_planejadas' => 3,
                    'reps_min' => 8,
                    'reps_max' => 12,
                    'tempo_descanso_segundos' => 60,
                ]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Ficha criada com sucesso!', 'data' => $plan]);
        }

        return redirect()->route('treinos.index')->with('success', 'Ficha criada com sucesso!');
    }

    /**
     * DELETE /treinos/plans/{id}
     */
    public function destroyPlan(int $id)
    {
        $userId = Auth::id();
        $plan = WorkoutPlan::where('user_id', $userId)->where('id', $id)->firstOrFail();
        $plan->delete();

        return response()->json(['success' => true, 'message' => 'Ficha excluída com sucesso.']);
    }

    /**
     * POST /treinos/plans/generate-default
     * Cria 3 fichas clássicas ABC estruturadas com exercícios do catálogo.
     */
    public function generateDefaultPlans(Request $request)
    {
        $userId = Auth::id();

        // 1. Ficha A - Peito, Deltoides e Tríceps (Push)
        $planA = WorkoutPlan::create([
            'user_id' => $userId,
            'nome' => 'Treino A - Peitoral, Deltoides & Tríceps',
            'identificador_letra' => 'A',
            'modalidade' => 'musculacao',
            'frequencia_semanal_sugerida' => 2,
            'descricao' => 'Foco em cadeia anterior: peitoral superior/médio, deltoides e tríceps.',
            'ativo' => true,
        ]);

        $pushExercises = [
            'Supino Reto com Barra' => [4, 8, 10, 80],
            'Supino Inclinado com Halteres' => [3, 10, 12, 26],
            'Desenvolvimento Militar para Ombros' => [3, 8, 10, 40],
            'Elevação Lateral com Halteres' => [4, 12, 15, 12],
            'Tríceps Corda na Polia' => [3, 10, 12, 25],
        ];

        $ordem = 1;
        foreach ($pushExercises as $name => [$series, $rmin, $rmax, $carga]) {
            $ex = Exercise::where('nome', 'like', "%{$name}%")->first();
            if ($ex) {
                \App\Models\WorkoutPlanItem::create([
                    'workout_plan_id' => $planA->id,
                    'exercise_id' => $ex->id,
                    'ordem' => $ordem++,
                    'series_planejadas' => $series,
                    'reps_min' => $rmin,
                    'reps_max' => $rmax,
                    'carga_alvo_kg' => $carga,
                ]);
            }
        }

        // 2. Ficha B - Costas, Bíceps e Trapézio (Pull)
        $planB = WorkoutPlan::create([
            'user_id' => $userId,
            'nome' => 'Treino B - Dorsais, Bíceps & Trapézio',
            'identificador_letra' => 'B',
            'modalidade' => 'musculacao',
            'frequencia_semanal_sugerida' => 2,
            'descricao' => 'Foco em cadeia posterior: largura de costas (V-taper) e flexores de cotovelo.',
            'ativo' => true,
        ]);

        $pullExercises = [
            'Puxada Frontal na Polia' => [4, 8, 10, 60],
            'Remada Curvada com Barra' => [4, 8, 10, 70],
            'Remada Baixa no Triângulo' => [3, 10, 12, 55],
            'Rosca Direta com Barra' => [3, 8, 10, 30],
            'Rosca Martelo com Halteres' => [3, 10, 12, 14],
        ];

        $ordem = 1;
        foreach ($pullExercises as $name => [$series, $rmin, $rmax, $carga]) {
            $ex = Exercise::where('nome', 'like', "%{$name}%")->first();
            if ($ex) {
                \App\Models\WorkoutPlanItem::create([
                    'workout_plan_id' => $planB->id,
                    'exercise_id' => $ex->id,
                    'ordem' => $ordem++,
                    'series_planejadas' => $series,
                    'reps_min' => $rmin,
                    'reps_max' => $rmax,
                    'carga_alvo_kg' => $carga,
                ]);
            }
        }

        // 3. Ficha C - Quadríceps, Isquiotibiais e Panturrilhas (Legs)
        $planC = WorkoutPlan::create([
            'user_id' => $userId,
            'nome' => 'Treino C - Pernas Completas & Abdômen',
            'identificador_letra' => 'C',
            'modalidade' => 'musculacao',
            'frequencia_semanal_sugerida' => 2,
            'descricao' => 'Membros inferiores para firmeza de glúteo/quadril e reforço de core.',
            'ativo' => true,
        ]);

        $legExercises = [
            'Agachamento Livre com Barra' => [4, 8, 10, 90],
            'Leg Press 45 Graus' => [4, 10, 12, 180],
            'Cadeira Extensora' => [3, 12, 15, 50],
            'Mesa Flexora' => [3, 10, 12, 45],
            'Elevação de Panturrilha em Pé' => [4, 15, 20, 60],
        ];

        $ordem = 1;
        foreach ($legExercises as $name => [$series, $rmin, $rmax, $carga]) {
            $ex = Exercise::where('nome', 'like', "%{$name}%")->first();
            if ($ex) {
                \App\Models\WorkoutPlanItem::create([
                    'workout_plan_id' => $planC->id,
                    'exercise_id' => $ex->id,
                    'ordem' => $ordem++,
                    'series_planejadas' => $series,
                    'reps_min' => $rmin,
                    'reps_max' => $rmax,
                    'carga_alvo_kg' => $carga,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Fichas A, B e C geradas com sucesso!']);
    }

    /**
     * POST /treinos/gears
     * Cadastra um novo tênis ou equipamento.
     */
    public function storeGear(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'tipo' => 'required|string|in:tenis_corrida,tenis_rodagem,tenis_prova,acessorio,outro',
            'quilometragem_inicial_km' => 'nullable|numeric|min:0',
            'vida_util_limite_km' => 'nullable|numeric|min:1',
            'data_aquisicao' => 'nullable|date',
        ]);

        $gear = GearItem::create([
            'user_id' => $userId,
            'marca' => $validated['marca'],
            'modelo' => $validated['modelo'],
            'tipo' => $validated['tipo'],
            'quilometragem_inicial_km' => $validated['quilometragem_inicial_km'] ?? 0,
            'quilometragem_acumulada_km' => 0,
            'vida_util_limite_km' => $validated['vida_util_limite_km'] ?? 800,
            'data_aquisicao' => $validated['data_aquisicao'] ?? now()->toDateString(),
            'ativo' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Equipamento salvo com sucesso!', 'data' => $gear]);
    }

    /**
     * DELETE /treinos/gears/{id}
     */
    public function destroyGear(int $id)
    {
        $userId = Auth::id();
        $gear = GearItem::where('user_id', $userId)->where('id', $id)->firstOrFail();
        $gear->delete();

        return response()->json(['success' => true, 'message' => 'Equipamento excluído com sucesso.']);
    }

    /**
     * POST /treinos/prs
     * Cadastra um novo Recorde Pessoal (PR).
     */
    public function storePr(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'modalidade' => 'required|string|in:musculacao,corrida',
            'exercise_id' => 'nullable|integer|exists:exercises,id',
            'tipo_recorde' => 'required|string|max:100',
            'valor_numerico' => 'required|numeric|min:0.01',
            'valor_formatado' => 'required|string|max:50',
            'data_recorde' => 'required|date',
            'notas' => 'nullable|string|max:500',
        ]);

        $pr = ExercisePersonalRecord::create([
            'user_id' => $userId,
            'modalidade' => $validated['modalidade'],
            'exercise_id' => $validated['exercise_id'] ?? null,
            'tipo_recorde' => $validated['tipo_recorde'],
            'valor_numerico' => $validated['valor_numerico'],
            'valor_formatado' => $validated['valor_formatado'],
            'data_recorde' => $validated['data_recorde'],
            'notas' => $validated['notas'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Recorde pessoal registrado!', 'data' => $pr]);
    }

    /**
     * DELETE /treinos/prs/{id}
     */
    public function destroyPr(int $id)
    {
        $userId = Auth::id();
        $pr = ExercisePersonalRecord::where('user_id', $userId)->where('id', $id)->firstOrFail();
        $pr->delete();

        return response()->json(['success' => true, 'message' => 'Recorde excluído com sucesso.']);
    }

    /**
     * POST /treinos/sessions
     * Cadastra uma sessão de treino realizada.
     */
    public function storeSession(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'nome_sessao' => 'required|string|max:255',
            'modalidade' => 'required|string|in:musculacao,corrida,alongamento,misto',
            'data_hora_inicio' => 'required|date',
            'duracao_minutos' => 'nullable|integer|min:1|max:720',
            'esforco_percebido_rpe' => 'nullable|integer|min:1|max:10',
            'distancia_km' => 'nullable|numeric|min:0.1|max:200',
            'observacoes' => 'nullable|string|max:1000',
        ]);

        $session = WorkoutSession::create([
            'user_id' => $userId,
            'nome_sessao' => $validated['nome_sessao'],
            'modalidade' => $validated['modalidade'],
            'data_hora_inicio' => $validated['data_hora_inicio'],
            'duracao_minutos' => $validated['duracao_minutos'] ?? null,
            'esforco_percebido_rpe' => $validated['esforco_percebido_rpe'] ?? null,
            'observacoes' => $validated['observacoes'] ?? null,
            'status' => 'concluido',
        ]);

        // Se for corrida e forneceu distância, cria o running log
        if ($validated['modalidade'] === 'corrida' && !empty($validated['distancia_km'])) {
            $duracaoSegundos = ($validated['duracao_minutos'] ?? 30) * 60;
            $distancia = (float) $validated['distancia_km'];
            $paceSegundos = $distancia > 0 ? (int) round($duracaoSegundos / $distancia) : 300;

            RunningLog::create([
                'workout_session_id' => $session->id,
                'distancia_km' => $distancia,
                'duracao_segundos' => $duracaoSegundos,
                'pace_medio_segundos_por_km' => $paceSegundos,
                'tipo_treino' => 'rodagem',
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Sessão de treino registrada!', 'data' => $session]);
    }

    /**
     * DELETE /treinos/sessions/{id}
     */
    public function destroySession(int $id)
    {
        $userId = Auth::id();
        $session = WorkoutSession::where('user_id', $userId)->where('id', $id)->firstOrFail();
        $session->delete();

        return response()->json(['success' => true, 'message' => 'Sessão excluída com sucesso.']);
    }

    /**
     * POST /treinos/ai/analyze-meal
     * Analisa desvios na dieta (ex: almoço fora da dieta / refeição livre) via Gemini.
     */
    public function analyzeMeal(Request $request)
    {
        $validated = $request->validate([
            'descricao' => 'required|string|min:3|max:1000',
            'date' => 'nullable|date',
        ]);

        $userId = Auth::id();
        $date = $validated['date'] ?? now()->toDateString();
        $dailyLog = DailyLog::where('user_id', $userId)->where('date', $date)->first();
        $latestMeasurement = Measurement::where('user_id', $userId)->orderByDesc('date')->orderByDesc('id')->first();

        $result = $this->fitnessAi->analyzeMealDeviation($validated['descricao'], $dailyLog, $latestMeasurement);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * POST /treinos/ai/analyze-workout
     * Analisa treino extra ou sobrecarga física via Gemini.
     */
    public function analyzeWorkout(Request $request)
    {
        $validated = $request->validate([
            'descricao' => 'required|string|min:3|max:1000',
            'date' => 'nullable|date',
        ]);

        $userId = Auth::id();
        $date = $validated['date'] ?? now()->toDateString();
        $dailyLog = DailyLog::where('user_id', $userId)->where('date', $date)->first();
        $latestMeasurement = Measurement::where('user_id', $userId)->orderByDesc('date')->orderByDesc('id')->first();

        $result = $this->fitnessAi->analyzeExtraWorkout($validated['descricao'], $dailyLog, $latestMeasurement);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * POST /treinos/ai/daily-review
     * Avaliação holística do dia selecionado pelo Coach IA.
     */
    public function dailyReview(Request $request)
    {
        $userId = Auth::id();
        $date = $request->input('date', now()->toDateString());
        $dailyLog = DailyLog::firstOrCreate(
            ['user_id' => $userId, 'date' => $date],
            [
                'workout_done' => false,
                'breakfast_clean' => false,
                'lunch_clean' => true,
                'snack_done' => false,
                'dinner_clean' => true,
                'water_volume_ml' => 0,
            ]
        );
        $latestMeasurement = Measurement::where('user_id', $userId)->orderByDesc('date')->orderByDesc('id')->first();

        $result = $this->fitnessAi->evaluateDailyProgress($dailyLog, $latestMeasurement);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * POST /treinos/ai/apply-suggestion
     * Aplica o resumo da IA diretamente no DailyLog do dia correspondente.
     */
    public function applyAiSuggestion(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'resumo_para_notas' => 'required|string|max:1000',
            'tipo' => 'required|string|in:meal_deviation,extra_workout,review',
            'agua_extra_ml' => 'nullable|integer|min:0|max:3000',
        ]);

        $userId = Auth::id();
        $date = $validated['date'] ?? now()->toDateString();

        $dailyLog = DailyLog::firstOrCreate(
            ['user_id' => $userId, 'date' => $date],
            [
                'workout_done' => false,
                'breakfast_clean' => false,
                'lunch_clean' => true,
                'snack_done' => false,
                'dinner_clean' => true,
                'water_volume_ml' => 0,
            ]
        );

        // Concatenar notas mantendo histórico prévio se houver
        $resumo = trim($validated['resumo_para_notas']);
        if (!empty($dailyLog->notes)) {
            $dailyLog->notes = $dailyLog->notes . "\n\n[IA Gemini] " . $resumo;
        } else {
            $dailyLog->notes = "[IA Gemini] " . $resumo;
        }

        // Ajustes pontuais conforme o tipo
        if ($validated['tipo'] === 'meal_deviation') {
            $dailyLog->lunch_clean = false; // Sinaliza o desvio na refeição
        } elseif ($validated['tipo'] === 'extra_workout') {
            $dailyLog->workout_done = true; // Garante que foi registrado treino/atividade
        }

        $dailyLog->save();

        return response()->json([
            'success' => true,
            'message' => 'Orientações da IA aplicadas ao diário de ' . ($dailyLog->date ? $dailyLog->date->format('d/m/Y') : $date) . '!',
            'data' => $dailyLog,
        ]);
    }
}
