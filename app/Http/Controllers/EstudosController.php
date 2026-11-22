<?php

namespace App\Http\Controllers;

use App\Services\GurujaComandoResumoService;
use App\Services\GurujaMcpService;
use App\Services\GurujaPostMetaService;
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
    /** Código de atividade da Guruja (ex.: DTRIB.3886.115658). */
    protected const CODIGO_RULE = ['required', 'string', 'max:40', 'regex:/^[A-Z]{2,10}\.\d{1,6}\.\d{1,9}$/'];

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
    public function guruja(GurujaMcpService $mcpService): JsonResponse
    {
        $row = DB::table('study_guruja_metas')->where('user_id', Auth::id())->first();
        $comandos = DB::table('study_guruja_comandos')->where('user_id', Auth::id())
            ->get(['codigo', 'texto', 'resumo'])
            ->mapWithKeys(fn ($c) => [$c->codigo => ['texto' => $c->texto, 'resumo' => $c->resumo]]);

        $meta = $row ? json_decode($row->payload, true) : null;
        $ordem = DB::table('study_guruja_ordens')->where('user_id', Auth::id())->first();
        $mcpConectado = $mcpService->isConnected(Auth::id());

        return response()->json([
            'meta' => $meta,
            // Ordem escolhida no card, só se for da meta atual (meta nova começa na ordem da Guruja).
            'ordem' => ($ordem && $meta && (int) $ordem->target_id === (int) ($meta['target_id'] ?? 0)) ? json_decode($ordem->codigos, true) : null,
            // Marcadas como feitas no card ("✓ Concluí"), só as da meta atual.
            'feitas' => $meta ? DB::table('study_guruja_feitas')->where('user_id', Auth::id())
                ->where('target_id', (int) ($meta['target_id'] ?? 0))->orderBy('id')->pluck('codigo')->all() : [],
            'atualizar_disponivel' => $mcpConectado || (bool) (config('services.guruja_sync.fire_url') && config('services.guruja_sync.fire_token')),
            'mcp_conectado' => $mcpConectado,
            'mcp_auth_url' => route('estudos.guruja.auth'),
            'mcp_desconectar_url' => route('estudos.guruja.desconectar'),
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
            'codigo' => self::CODIGO_RULE,
            'texto' => 'required|string|max:10000',
            'titulo' => 'nullable|string|max:300',
            // Presente = resumo escrito à mão na edição (vazio apaga o resumo); a IA não é chamada.
            'resumo' => 'sometimes|nullable|string|max:2000',
            // Força um resumo novo do Gemini mesmo com o texto igual.
            'resumir' => 'sometimes|boolean',
        ]);
        $texto = trim($data['texto']);
        $chave = ['user_id' => Auth::id(), 'codigo' => $data['codigo']];
        $atual = DB::table('study_guruja_comandos')->where($chave)->first();
        $salvar = fn (?string $resumo) => DB::table('study_guruja_comandos')->updateOrInsert($chave,
            ['texto' => $texto, 'resumo' => $resumo, 'updated_at' => now(), 'created_at' => $atual->created_at ?? now()]);

        if ($request->has('resumo')) {
            $resumo = trim((string) $data['resumo']) ?: null;
            $salvar($resumo);

            return response()->json(['ok' => true, 'texto' => $texto, 'resumo' => $resumo]);
        }
        // Mesmo texto já resumido: não chama a IA de novo.
        if ($atual && $atual->texto === $texto && $atual->resumo && !($data['resumir'] ?? false)) {
            $salvar($atual->resumo);

            return response()->json(['ok' => true, 'texto' => $texto, 'resumo' => $atual->resumo]);
        }
        // Grava o texto novo antes de chamar a IA (que pode levar dezenas de segundos ou falhar):
        // o comando trocado nunca se perde, mesmo que a página feche no meio.
        $salvar(null);
        $resumo = $resumos->resumir($texto, $data['titulo'] ?? null);
        if ($resumo) {
            $salvar($resumo);
        }

        return response()->json(['ok' => true, 'texto' => $texto, 'resumo' => $resumo]);
    }

    /**
     * Apaga o comando salvo de uma atividade (o card volta a pedir o favorito "Enviar comando").
     */
    public function gurujaComandoApagar(Request $request): JsonResponse
    {
        $data = $request->validate(['codigo' => self::CODIGO_RULE]);
        DB::table('study_guruja_comandos')->where('user_id', Auth::id())->where('codigo', $data['codigo'])->delete();

        return response()->json(['ok' => true]);
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
     * Dados prontos para o carrossel do Instagram de uma meta concluída (ou em andamento) da Guruja.
     */
    public function gurujaPostMeta(
        Request $request,
        GurujaMcpService $mcpService,
        GurujaPostMetaService $postMetaService
    ): JsonResponse {
        $data = $request->validate([
            'ordem' => 'nullable|integer|min:1',
        ]);

        $userId = Auth::id();
        if (!$mcpService->isConnected($userId)) {
            return response()->json([
                'message' => 'Conecte sua conta da Guruja para gerar o post da meta.',
                'mcp_auth_url' => route('estudos.guruja.auth'),
            ], 409);
        }

        $ordem = isset($data['ordem']) ? (int) $data['ordem'] : null;

        try {
            $payload = $postMetaService->buildForUser($userId, $ordem);
        } catch (\Throwable $e) {
            Log::warning('Erro ao montar post da meta da Guruja: ' . $e->getMessage());

            return response()->json([
                'message' => 'Falha ao consultar dados da Guruja: ' . $e->getMessage(),
            ], 502);
        }

        if ($payload === null) {
            return response()->json([
                'message' => $ordem !== null
                    ? "Meta {$ordem} não encontrada no histórico da Guruja."
                    : 'Nenhuma meta concluída encontrada na Guruja.',
            ], 404);
        }

        return response()->json($payload);
    }

    /**
     * Botão "Atualizar" do card: se conectado via MCP (https://mcp.guruja.com.br/mcp),
     * busca as metas instantaneamente. Caso contrário, dispara a rotina externa se configurada.
     */
    public function gurujaAtualizar(GurujaMcpService $mcpService): JsonResponse
    {
        if ($mcpService->isConnected(Auth::id())) {
            try {
                $meta = $mcpService->fetchCurrentMeta(Auth::id());

                return response()->json([
                    'ok' => true,
                    'via_mcp' => true,
                    'meta' => $meta,
                    'lido_em' => now()->utc()->format('Y-m-d\\TH:i:s\\Z'),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Atualização via Guruja MCP falhou: ' . $e->getMessage());

                return response()->json([
                    'message' => 'Falha ao sincronizar com a Guruja: ' . $e->getMessage(),
                ], 502);
            }
        }

        $url = config('services.guruja_sync.fire_url');
        $token = config('services.guruja_sync.fire_token');
        if (!$url || !$token) {
            return response()->json([
                'message' => 'Guruja não conectada. Conecte sua conta via OAuth ou cole o JSON manualmente.',
            ], 503);
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

    /**
     * Inicia autorização OAuth PKCE com o conector da Guruja.
     */
    public function gurujaAuth(Request $request, GurujaMcpService $mcpService)
    {
        $redirectUri = route('estudos.guruja.callback');
        $authUrl = $mcpService->buildAuthUrl(Auth::id(), $redirectUri);

        return redirect()->away($authUrl);
    }

    /**
     * Callback do OAuth da Guruja: recebe o code, troca pelo token e tenta a primeira sincronização.
     */
    public function gurujaCallback(Request $request, GurujaMcpService $mcpService)
    {
        $code = $request->input('code');
        $state = $request->input('state');
        $error = $request->input('error') ?? $request->input('error_description');

        if ($error) {
            return redirect()->to(route('estudos.index') . '#guruja-erro=' . urlencode($error));
        }

        if (!$code || !$state) {
            return redirect()->to(route('estudos.index') . '#guruja-erro=' . urlencode('Parâmetros de autorização incompletos.'));
        }

        try {
            $mcpService->handleCallback($code, $state);

            try {
                $mcpService->fetchCurrentMeta(Auth::id());
            } catch (\Throwable $e) {
                Log::warning('Conexão Guruja concluída, mas primeira busca falhou: ' . $e->getMessage());

                return redirect()->to(route('estudos.index') . '#guruja-conectado-aviso=' . urlencode($e->getMessage()));
            }

            return redirect()->to(route('estudos.index') . '#guruja-conectado');
        } catch (\Throwable $e) {
            Log::warning('Erro no callback OAuth da Guruja: ' . $e->getMessage());

            return redirect()->to(route('estudos.index') . '#guruja-erro=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Desconecta a conta Guruja e remove tokens salvos.
     */
    public function gurujaDesconectar(GurujaMcpService $mcpService): JsonResponse
    {
        $mcpService->disconnect(Auth::id());

        return response()->json(['ok' => true]);
    }

    /**
     * Atualização manual: o usuário cola o JSON da meta ou de atividades diretamente no card.
     */
    public function gurujaImportar(Request $request, GurujaMcpService $mcpService): JsonResponse
    {
        $raw = $request->input('payload') ?? $request->input('json') ?? $request->getContent();
        if (is_string($raw)) {
            $data = json_decode($raw, true);
        } else {
            $data = $raw;
        }

        if (!is_array($data)) {
            return response()->json(['message' => 'O payload fornecido não é um JSON válido.'], 422);
        }

        $meta = $mcpService->normalizeMetaPayload($data);
        if (empty($meta['atividades']) && empty($meta['reforcos'])) {
            return response()->json(['message' => 'Nenhuma atividade ou reforço foi identificado no JSON fornecido.'], 422);
        }

        $meta = $mcpService->saveMetaToDatabase(Auth::id(), $meta, now());

        return response()->json([
            'ok' => true,
            'meta' => $meta,
            'lido_em' => now()->utc()->format('Y-m-d\\TH:i:s\\Z'),
        ]);
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
