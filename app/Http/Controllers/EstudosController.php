<?php

namespace App\Http\Controllers;

use App\Services\GurujaComandoResumoService;
use App\Services\StudySyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        $meta = $row ? json_decode($row->payload, true) : null;
        $ordem = DB::table('study_guruja_ordens')->where('user_id', Auth::id())->first();

        return response()->json([
            'meta' => $meta,
            // Ordem escolhida no card, só se for da meta atual (meta nova começa na ordem da Guruja).
            'ordem' => ($ordem && $meta && (int) $ordem->target_id === (int) ($meta['target_id'] ?? 0)) ? json_decode($ordem->codigos, true) : null,
            // Marcadas como feitas no card ("✓ Concluí"), só as da meta atual.
            'feitas' => $meta ? DB::table('study_guruja_feitas')->where('user_id', Auth::id())
                ->where('target_id', (int) ($meta['target_id'] ?? 0))->orderBy('id')->pluck('codigo')->all() : [],
            'atualizar_disponivel' => (bool) (config('services.guruja_sync.fire_url') && config('services.guruja_sync.fire_token')),
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

    /**
     * Salva a ordem das pendentes escolhida no card (↕ Organizar). Lista vazia = volta à ordem da Guruja.
     */
    public function gurujaOrdem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target_id' => 'required|integer',
            'codigos' => 'present|array|max:200',
            'codigos.*' => ['string', 'max:40', 'regex:/^(R:\d{1,12}|[A-Z]{2,10}\.\d{1,6}\.\d{1,9})$/'],
        ]);
        if (!$data['codigos']) {
            DB::table('study_guruja_ordens')->where('user_id', Auth::id())->delete();

            return response()->json(['ok' => true]);
        }
        DB::table('study_guruja_ordens')->updateOrInsert(
            ['user_id' => Auth::id()],
            ['target_id' => $data['target_id'], 'codigos' => json_encode(array_values(array_unique($data['codigos']))), 'updated_at' => now(), 'created_at' => now()],
        );

        return response()->json(['ok' => true]);
    }

    /**
     * "✓ Concluí" no card: marca (ou desmarca) uma atividade/reforço como feito antes da sincronização confirmar.
     */
    public function gurujaFeita(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target_id' => 'required|integer',
            'codigo' => ['required', 'string', 'max:40', 'regex:/^(R:\d{1,12}|[A-Z]{2,10}\.\d{1,6}\.\d{1,9})$/'],
            'feita' => 'required|boolean',
        ]);
        if ($data['feita']) {
            DB::table('study_guruja_feitas')->updateOrInsert(
                ['user_id' => Auth::id(), 'codigo' => $data['codigo']],
                ['target_id' => $data['target_id'], 'updated_at' => now(), 'created_at' => now()],
            );
        } else {
            DB::table('study_guruja_feitas')->where('user_id', Auth::id())->where('codigo', $data['codigo'])->delete();
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Botão "Atualizar" do card: dispara a tarefa agendada do Claude que lê a Guruja (gatilho de API). O card
     * percebe a atualização quando o lido_em da meta muda. Um disparo a cada 2 minutos, no máximo.
     */
    public function gurujaAtualizar(): JsonResponse
    {
        $url = config('services.guruja_sync.fire_url');
        $token = config('services.guruja_sync.fire_token');
        if (!$url || !$token) {
            return response()->json(['message' => 'Atualização sob demanda não configurada (GURUJA_SYNC_FIRE_URL e GURUJA_SYNC_FIRE_TOKEN no Render).'], 503);
        }
        $key = 'guruja-atualizar:' . Auth::id();
        if ($desde = Cache::get($key)) {
            return response()->json(['ok' => true, 'disparado_em' => $desde, 'repetido' => true]);
        }
        try {
            $response = Http::timeout(20)->withToken($token)->withHeaders([
                'anthropic-beta' => 'experimental-cc-routine-2026-04-01',
                'anthropic-version' => '2023-06-01',
            ])->post($url, ['text' => 'Atualização pedida pelo botão do card da Guruja no app de estudos.']);
        } catch (\Throwable $e) {
            Log::warning('Disparo da atualização da Guruja falhou: ' . $e->getMessage());

            return response()->json(['message' => 'Não consegui disparar a atualização agora. Tente de novo em instantes.'], 502);
        }
        if (!$response->successful()) {
            Log::warning("Disparo da atualização da Guruja: status {$response->status()} " . substr($response->body(), 0, 200));

            return response()->json(['message' => "A tarefa de atualização recusou o disparo (erro {$response->status()})."], 502);
        }
        $agora = now()->utc()->format('Y-m-d\\TH:i:s\\Z');
        Cache::put($key, $agora, now()->addMinutes(2));

        return response()->json(['ok' => true, 'disparado_em' => $agora]);
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
