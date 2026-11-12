<?php

namespace App\Http\Controllers;

use App\Services\GurujaComandoResumoService;
use App\Services\StudySyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Módulo de estudos estilo YPT (PWA offline-first).
 * O servidor só entrega a casca da PWA e sincroniza dados; todas as métricas são calculadas no cliente.
 */
class EstudosController extends Controller
{
    public function index()
    {
        if ($redirect = $this->cloudRedirect()) {
            return $redirect;
        }

        return view('estudos.app');
    }

    /**
     * Token CSRF atual: a casca em cache da PWA pode carregar um token vencido.
     */
    public function token(): JsonResponse
    {
        return response()->json(['token' => csrf_token(), 'user_id' => Auth::id()]);
    }

    /**
     * Última foto da meta atual da Guruja (ou null se nunca foi enviada).
     */
    public function guruja(): JsonResponse
    {
        $row = DB::table('study_guruja_metas')->where('user_id', Auth::id())->first();
        $comandos = DB::table('study_guruja_comandos')->where('user_id', Auth::id())
            ->get(['codigo', 'texto', 'resumo'])
            ->mapWithKeys(fn ($c) => [$c->codigo => ['texto' => $c->texto, 'resumo' => $c->resumo]]);

        return response()->json([
            'meta' => $row ? json_decode($row->payload, true) : null,
            'lido_em' => $row?->lido_em ? Carbon::parse($row->lido_em)->utc()->format('Y-m-d\\TH:i:s\\Z') : null,
            'comandos' => (object) $comandos->all(),
        ]);
    }

    /**
     * Salva o comando de uma atividade, copiado do portal da Guruja pelo favorito (via /estudos#gj-cmd=...).
     */
    public function gurujaComando(Request $request, GurujaComandoResumoService $resumos): JsonResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:40', 'regex:/^[A-Z]{2,10}\.\d{1,6}\.\d{1,9}$/'],
            'texto' => 'required|string|max:10000',
            'titulo' => 'nullable|string|max:300',
        ]);
        $texto = trim($data['texto']);
        $atual = DB::table('study_guruja_comandos')->where('user_id', Auth::id())->where('codigo', $data['codigo'])->first();
        // Mesmo texto já resumido: não chama a IA de novo.
        $resumo = ($atual && $atual->texto === $texto && $atual->resumo) ? $atual->resumo : $resumos->resumir($texto, $data['titulo'] ?? null);
        DB::table('study_guruja_comandos')->updateOrInsert(
            ['user_id' => Auth::id(), 'codigo' => $data['codigo']],
            ['texto' => $texto, 'resumo' => $resumo, 'updated_at' => now(), 'created_at' => $atual->created_at ?? now()],
        );

        return response()->json(['ok' => true, 'texto' => $texto, 'resumo' => $resumo]);
    }

    public function sync(Request $request, StudySyncService $service): JsonResponse
    {
        if ($this->cloudRedirect()) {
            return response()->json([
                'message' => 'O módulo de estudos sincroniza apenas com a nuvem. Use ' . $this->cloudUrl() . '/estudos',
            ], 409);
        }

        $payload = $request->validate([
            'device_id' => 'required|uuid',
            'cursor' => 'nullable|date',
            'changes' => 'nullable|array',
            'changes.subjects' => 'nullable|array|max:1000',
            'changes.sessions' => 'nullable|array|max:1000',
            'changes.ddays' => 'nullable|array|max:1000',
            'changes.settings' => 'nullable|array',
        ]);

        return response()->json($service->sync(Auth::id(), $payload));
    }

    /**
     * Fora da nuvem, o módulo redireciona para a URL da nuvem (fonte única da verdade para estudos).
     */
    protected function cloudRedirect()
    {
        $cloud = $this->cloudUrl();
        if (!$cloud || app()->environment('testing')) {
            return null;
        }

        $currentHost = parse_url(request()->getSchemeAndHttpHost(), PHP_URL_HOST);
        $cloudHost = parse_url($cloud, PHP_URL_HOST);
        if (!$cloudHost || $currentHost === $cloudHost) {
            return null;
        }

        return redirect()->away($cloud . '/estudos');
    }

    protected function cloudUrl(): ?string
    {
        $url = config('services.estudos.cloud_url');
        return $url ? rtrim($url, '/') : null;
    }
}
