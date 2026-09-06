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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TreinosController extends Controller
{
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

        // Última medição corporal
        $latestMetric = UserBodyMetric::where('user_id', $userId)
            ->orderByDesc('data_medicao')
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

        return view('treinos.index', compact(
            'workoutPlans',
            'recentSessions',
            'sessoesEstaSemana',
            'kmCorridaSemana',
            'activeGears',
            'latestMetric',
            'personalRecords',
            'activeGoals',
            'exercisesStats'
        ));
    }
}
