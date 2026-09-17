<?php

namespace App\Http\Controllers;

use App\Services\DatabaseSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DatabaseSyncController extends Controller
{
    public function __construct(
        protected DatabaseSyncService $syncService
    ) {}

    /**
     * Obter status atual da sincronização e conectividade com a nuvem.
     */
    public function status(): JsonResponse
    {
        $status = $this->syncService->getStatus();
        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Executar sincronização de dados.
     */
    public function sync(Request $request): JsonResponse
    {
        $direction = $request->input('direction', 'both');

        if (!in_array($direction, ['both', 'push', 'pull'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Direção de sincronização inválida.',
            ], 422);
        }

        if ($direction === 'pull') {
            $result = $this->syncService->pull();
        } elseif ($direction === 'push') {
            $result = $this->syncService->push();
        } else {
            $result = $this->syncService->sync();
        }

        $statusCode = $result['success'] ? 200 : 500;

        return response()->json($result, $statusCode);
    }
}
