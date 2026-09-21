<?php

namespace App\Http\Controllers;

use App\Services\BodyTrackerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BodyTrackerController extends Controller
{
    protected BodyTrackerService $trackerService;

    public function __construct(BodyTrackerService $trackerService)
    {
        $this->trackerService = $trackerService;
    }

    /**
     * POST /api/tracker/daily-log
     * Grava ou atualiza o registro diário de hábitos, treino e hidratação.
     */
    public function saveDailyLog(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'workout_done' => 'nullable|boolean',
            'workout_type' => 'nullable|string|max:100',
            'workout_duration_min' => 'nullable|integer|min:0|max:1440',
            'breakfast_clean' => 'nullable|boolean',
            'lunch_clean' => 'nullable|boolean',
            'snack_done' => 'nullable|boolean',
            'dinner_clean' => 'nullable|boolean',
            'water_volume_ml' => 'nullable|integer|min:0|max:20000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $userId = Auth::id();
        $log = $this->trackerService->saveDailyLog($userId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Diário registrado com sucesso!',
            'data' => $log,
            'adherence_score' => $log->adherence_score,
            'water_progress_percent' => $log->water_progress_percent,
        ]);
    }

    /**
     * GET /api/tracker/daily-log/today
     * Retorna o diário do dia de hoje (ou objeto inicializado caso ainda não exista).
     */
    public function todayDailyLog(): JsonResponse
    {
        $userId = Auth::id();
        $today = now()->toDateString();
        $log = $this->trackerService->getDailyLogForDate($userId, $today);

        if (!$log) {
            return response()->json([
                'exists' => false,
                'data' => [
                    'date' => $today,
                    'workout_done' => false,
                    'workout_type' => 'STRENGTH_CIRCUIT',
                    'workout_duration_min' => 30,
                    'breakfast_clean' => false,
                    'lunch_clean' => true,
                    'snack_done' => false,
                    'dinner_clean' => true,
                    'water_volume_ml' => 0,
                    'notes' => null,
                    'adherence_score' => 40,
                    'water_progress_percent' => 0,
                ],
            ]);
        }

        return response()->json([
            'exists' => true,
            'data' => $log,
            'adherence_score' => $log->adherence_score,
            'water_progress_percent' => $log->water_progress_percent,
        ]);
    }

    /**
     * GET /api/tracker/daily-log/by-date?date=YYYY-MM-DD
     * Retorna o diário de uma data específica.
     */
    public function getDailyLogByDate(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $date = $request->query('date', now()->toDateString());
        $log = $this->trackerService->getDailyLogForDate($userId, $date);

        if (!$log) {
            return response()->json([
                'exists' => false,
                'data' => [
                    'date' => $date,
                    'workout_done' => false,
                    'workout_type' => 'STRENGTH_CIRCUIT',
                    'workout_duration_min' => 30,
                    'breakfast_clean' => false,
                    'lunch_clean' => false,
                    'snack_done' => false,
                    'dinner_clean' => false,
                    'water_volume_ml' => 0,
                    'notes' => null,
                    'adherence_score' => 0,
                    'water_progress_percent' => 0,
                ],
            ]);
        }

        return response()->json([
            'exists' => true,
            'data' => $log,
            'adherence_score' => $log->adherence_score,
            'water_progress_percent' => $log->water_progress_percent,
        ]);
    }

    /**
     * GET /api/tracker/daily-log?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
     */
    public function getDailyLogs(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $logs = $this->trackerService->getDailyLogs($userId, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * POST /api/tracker/measurements
     * Registra um novo check-in antropométrico.
     */
    public function saveMeasurement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'weight_kg' => 'required|numeric|min:20|max:300',
            'chest_cm' => 'required|numeric|min:40|max:200',
            'waist_narrow_cm' => 'required|numeric|min:40|max:200',
            'abdomen_umbilical_cm' => 'required|numeric|min:40|max:200',
            'hips_cm' => 'required|numeric|min:40|max:200',
            'arm_right_cm' => 'required|numeric|min:15|max:100',
            'thigh_right_cm' => 'required|numeric|min:20|max:150',
            'notes' => 'nullable|string|max:2000',
        ]);

        $userId = Auth::id();
        $measurement = $this->trackerService->saveMeasurement($userId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Check-in antropométrico salvo com sucesso!',
            'data' => $measurement,
            'rcq' => $measurement->rcq,
            'rcq_status' => $measurement->rcq_status,
            'rcq_label' => $measurement->rcq_label,
        ]);
    }

    /**
     * GET /api/tracker/measurements/latest
     */
    public function latestMeasurement(): JsonResponse
    {
        $userId = Auth::id();
        $latest = $this->trackerService->getLatestMeasurement($userId);

        if (!$latest) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma medição registrada ainda.',
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $latest,
            'rcq' => $latest->rcq,
            'rcq_status' => $latest->rcq_status,
            'rcq_label' => $latest->rcq_label,
        ]);
    }

    /**
     * GET /api/tracker/measurements/summary-deltas
     * Retorna os dados antropométricos estruturados prontos para renderizar no Manequim SVG.
     */
    public function summaryDeltas(): JsonResponse
    {
        $userId = Auth::id();
        $summary = $this->trackerService->getSummaryDeltas($userId);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * POST /api/tracker/photos
     * Upload multipart de foto de evolução com ângulo e data.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|max:15360', // Max 15MB
            'date' => 'required|date',
            'angle' => 'required|string|in:FRONT,BACK,SIDE_RIGHT,SIDE_LEFT',
            'measurement_id' => 'nullable|integer|exists:measurements,id',
        ]);

        $userId = Auth::id();
        $photo = $this->trackerService->savePhoto(
            $userId,
            $request->file('photo'),
            $request->input('date'),
            $request->input('angle'),
            $request->input('measurement_id') ? (int)$request->input('measurement_id') : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Foto de evolução cadastrada com sucesso!',
            'data' => [
                'id' => $photo->id,
                'date' => $photo->date ? $photo->date->format('Y-m-d') : null,
                'angle' => $photo->angle,
                'angle_label' => $photo->angle_label,
                'url' => $photo->url,
            ],
        ]);
    }

    /**
     * GET /api/tracker/photos?angle=FRONT
     */
    public function getPhotos(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $angle = $request->query('angle');

        $photos = $this->trackerService->getPhotos($userId, $angle);

        return response()->json([
            'success' => true,
            'data' => $photos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'date' => $photo->date ? $photo->date->format('Y-m-d') : null,
                    'date_formatted' => $photo->date ? $photo->date->format('d/m/Y') : null,
                    'angle' => $photo->angle,
                    'angle_label' => $photo->angle_label,
                    'url' => $photo->url,
                ];
            }),
        ]);
    }

    /**
     * DELETE /api/tracker/photos/{id}
     */
    public function deletePhoto(int $id): JsonResponse
    {
        $userId = Auth::id();
        $deleted = $this->trackerService->deletePhoto($userId, $id);

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Foto não encontrada ou não autorizada.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Foto excluída com sucesso.']);
    }

    /**
     * GET /tracker/photos/{id}
     * Serve o arquivo de imagem diretamente para o usuário autenticado.
     */
    public function showPhotoFile(int $id)
    {
        $userId = Auth::id();
        $photo = \App\Models\ProgressPhoto::where('user_id', $userId)->where('id', $id)->firstOrFail();

        if (!Storage::disk('public')->exists($photo->file_path)) {
            abort(404, 'Arquivo de imagem não encontrado no servidor.');
        }

        return Storage::disk('public')->response($photo->file_path);
    }
}
