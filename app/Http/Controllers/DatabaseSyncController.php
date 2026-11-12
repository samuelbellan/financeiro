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

        // A transferência faz dezenas de idas e voltas à Neon (~0,7 s cada) e
        // passa facilmente dos 30 s padrão do PHP, matando a requisição no meio.
        set_time_limit(0);
        ignore_user_abort(true);

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
