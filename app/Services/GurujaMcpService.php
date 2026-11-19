<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GurujaMcpService
{
    public const MCP_ENDPOINT = 'https://mcp.guruja.com.br/mcp';
    public const AUTH_ENDPOINT = 'https://mcp.guruja.com.br/oauth/authorize';
    public const TOKEN_ENDPOINT = 'https://mcp.guruja.com.br/oauth/token';
    public const REGISTER_ENDPOINT = 'https://mcp.guruja.com.br/oauth/register';
    public const PROTOCOL_VERSION = '2025-03-26';

    public function isConnected(int $userId, ?string $connection = null): bool
    {
        if (config('services.guruja_mcp.access_token') || env('GURUJA_ACCESS_TOKEN')) {
            return true;
        }

        return DB::connection($connection)->table('study_guruja_tokens')
            ->where('user_id', $userId)
            ->whereNotNull('access_token')
            ->exists();
    }

    public function disconnect(int $userId, ?string $connection = null): void
    {
        DB::connection($connection)->table('study_guruja_tokens')->where('user_id', $userId)->delete();
    }

    /**
     * Obtém ou registra um client_id no Authorization Server da Guruja (RFC 7591).
     */
    public function getClientId(string $redirectUri): string
    {
        $cacheKey = $redirectUri !== '' ? 'guruja_mcp_client_id:' . md5($redirectUri) : 'guruja_mcp_client_id';
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::timeout(10)->post(self::REGISTER_ENDPOINT, [
                'client_name' => 'Financeiro Estudos',
                'redirect_uris' => [$redirectUri],
                'grant_types' => ['authorization_code', 'refresh_token'],
                'response_types' => ['code'],
                'token_endpoint_auth_method' => 'none',
            ]);

            if ($response->successful() && !empty($response->json('client_id'))) {
                $clientId = (string) $response->json('client_id');
                Cache::put($cacheKey, $clientId, now()->addDays(30));

                return $clientId;
            }

            Log::warning('Registro dinâmico de client_id no Guruja MCP retornou HTTP ' . $response->status() . ': ' . $response->body());
        } catch (\Throwable $e) {
            Log::warning('Registro dinâmico de client_id no Guruja MCP falhou: ' . $e->getMessage());
        }

        // Fallback para client_id já registrado anteriormente
        return 'mcp-f2b0d76e-0176-4af7-9713-81de3be1c628';
    }

    /**
     * Gera um par PKCE (verifier + S256 challenge).
     *
     * @return array{verifier: string, challenge: string}
     */
    public function generatePkcePair(): array
    {
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return [
            'verifier' => $verifier,
            'challenge' => $challenge,
        ];
    }

    /**
     * Monta a URL de autorização OAuth 2.1 + PKCE + RFC 8707 (resource).
     */
    public function buildAuthorizeUrl(string $clientId, string $redirectUri, string $challenge, string $state): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'mcp:read',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'state' => $state,
            'resource' => self::MCP_ENDPOINT,
        ]);

        return self::AUTH_ENDPOINT . '?' . $params;
    }

    /**
     * Inicia o fluxo OAuth PKCE web e retorna a URL de autorização.
     */
    public function buildAuthUrl(int $userId, string $redirectUri): string
    {
        $clientId = $this->getClientId($redirectUri);
        $state = Str::random(40);
        $pkce = $this->generatePkcePair();

        Cache::put("guruja_oauth:{$state}", [
            'user_id' => $userId,
            'verifier' => $pkce['verifier'],
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
        ], now()->addMinutes(15));

        return $this->buildAuthorizeUrl($clientId, $redirectUri, $pkce['challenge'], $state);
    }

    /**
     * Troca o authorization code pelos tokens OAuth na Guruja.
     *
     * @return array{client_id: string, access_token: string, refresh_token: ?string, expires_in: int}
     */
    public function exchangeAuthorizationCode(string $code, string $verifier, string $clientId, string $redirectUri): array
    {
        $response = Http::asForm()->timeout(15)->post(self::TOKEN_ENDPOINT, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $clientId,
            'code_verifier' => $verifier,
            'resource' => self::MCP_ENDPOINT,
        ]);

        if (!$response->successful()) {
            Log::warning('Erro na troca de token do Guruja MCP: ' . $response->body());
            $msg = $response->json('error_description') ?? $response->json('error') ?? "Erro {$response->status()} ao obter token";
            throw new \RuntimeException("Não foi possível conectar com a Guruja: {$msg}");
        }

        $tokenData = $response->json();
        if (empty($tokenData['access_token'])) {
            throw new \RuntimeException('A Guruja não retornou um access_token válido.');
        }

        return [
            'client_id' => $clientId,
            'access_token' => (string) $tokenData['access_token'],
            'refresh_token' => isset($tokenData['refresh_token']) ? (string) $tokenData['refresh_token'] : null,
            'expires_in' => (int) ($tokenData['expires_in'] ?? 3600),
        ];
    }

    /**
     * Salva os tokens OAuth na tabela study_guruja_tokens (local ou nuvem).
     *
     * @param array{client_id: ?string, access_token: string, refresh_token?: ?string, expires_in?: int} $tokenData
     */
    public function saveTokens(int $userId, array $tokenData, ?string $connection = null): void
    {
        $expiresIn = (int) ($tokenData['expires_in'] ?? 3600);

        DB::connection($connection)->table('study_guruja_tokens')->updateOrInsert(
            ['user_id' => $userId],
            [
                'client_id' => $tokenData['client_id'] ?? null,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($expiresIn),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Trata o retorno do OAuth web, troca o code pelo token e salva no banco.
     */
    public function handleCallback(string $code, string $state): array
    {
        $data = Cache::pull("guruja_oauth:{$state}");
        if (!$data || empty($data['user_id'])) {
            throw new \RuntimeException('Sessão de autorização expirada ou inválida. Tente novamente.');
        }

        $userId = (int) $data['user_id'];
        $tokenData = $this->exchangeAuthorizationCode(
            $code,
            (string) $data['verifier'],
            (string) $data['client_id'],
            (string) $data['redirect_uri'],
        );

        $this->saveTokens($userId, $tokenData);

        return [
            'user_id' => $userId,
            'access_token' => $tokenData['access_token'],
        ];
    }

    /**
     * Obtém um token de acesso válido, renovando com o refresh_token se necessário.
     */
    public function getValidAccessToken(int $userId, bool $forceRefresh = false, ?string $connection = null): ?string
    {
        $directToken = config('services.guruja_mcp.access_token') ?: env('GURUJA_ACCESS_TOKEN');
        if (!empty($directToken)) {
            return $forceRefresh ? null : $directToken;
        }

        $row = DB::connection($connection)->table('study_guruja_tokens')->where('user_id', $userId)->first();
        if (!$row || empty($row->access_token)) {
            return null;
        }

        $expiresAt = $row->expires_at ? Carbon::parse($row->expires_at) : null;
        $needsRefresh = $forceRefresh || !$expiresAt || $expiresAt->isPast() || $expiresAt->diffInSeconds(now(), false) > -60;

        if ($needsRefresh) {
            if (!empty($row->refresh_token)) {
                $refreshed = $this->refreshToken($userId, $row, $connection);
                if ($refreshed) {
                    return $refreshed;
                }
            }

            if ($forceRefresh) {
                return null;
            }
        }

        return $row->access_token;
    }

    protected function refreshToken(int $userId, object $row, ?string $connection = null): ?string
    {
        try {
            $payload = [
                'grant_type' => 'refresh_token',
                'refresh_token' => $row->refresh_token,
                'client_id' => $row->client_id ?? $this->getClientId(''),
                'resource' => self::MCP_ENDPOINT,
            ];

            $response = Http::asForm()->timeout(15)->post(self::TOKEN_ENDPOINT, $payload);

            if ($response->successful() && !empty($response->json('access_token'))) {
                $data = $response->json();
                $accessToken = (string) $data['access_token'];
                $refreshToken = !empty($data['refresh_token']) ? (string) $data['refresh_token'] : $row->refresh_token;
                $expiresIn = (int) ($data['expires_in'] ?? 3600);

                DB::connection($connection)->table('study_guruja_tokens')->where('user_id', $userId)->update([
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'expires_at' => now()->addSeconds($expiresIn),
                    'updated_at' => now(),
                ]);

                return $accessToken;
            }

            Log::warning("Renovação de token MCP da Guruja retornou HTTP {$response->status()} para usuário {$userId}: " . substr($response->body(), 0, 300));
        } catch (\Throwable $e) {
            Log::warning("Falha ao renovar token MCP da Guruja para usuário {$userId}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Faz requisição JSON-RPC ao MCP Streamable HTTP (suporta JSON direto, SSE e Mcp-Session-Id).
     */
    public function callMcp(
        string $accessToken,
        string $method,
        array $params = [],
        ?int $id = 1,
        ?string &$sessionId = null
    ): array {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/event-stream',
        ];
        if (!empty($sessionId)) {
            $headers['Mcp-Session-Id'] = $sessionId;
        }

        $body = [
            'jsonrpc' => '2.0',
            'method' => $method,
        ];
        if ($id !== null || !empty($params)) {
            $body['params'] = (object) $params;
        }
        if ($id !== null) {
            $body['id'] = $id;
        }

        $response = Http::timeout(25)
            ->withToken($accessToken)
            ->withHeaders($headers)
            ->post(self::MCP_ENDPOINT, $body);

        if ($response->status() === 401) {
            throw new \RuntimeException('Token MCP expirado ou não reconhecido pela Guruja. Reconecte sua conta.', 401);
        }

        if (!$response->successful()) {
            throw new \RuntimeException("Erro HTTP {$response->status()} ao consultar MCP da Guruja.");
        }

        $returnedSession = $response->header('Mcp-Session-Id') ?: $response->header('mcp-session-id');
        if (!empty($returnedSession)) {
            $sessionId = $returnedSession;
        }

        // Notificações JSON-RPC (ex.: notifications/initialized) normalmente respondem 202 sem corpo
        if ($id === null || $response->status() === 202 || trim($response->body()) === '') {
            return [];
        }

        $json = $this->parseMcpResponseBody($response->body(), (string) $response->header('Content-Type'), $id);
        if (!is_array($json)) {
            throw new \RuntimeException('Resposta inválida recebida do servidor MCP da Guruja.');
        }

        if (isset($json['error'])) {
            $msg = $json['error']['message'] ?? json_encode($json['error']);
            throw new \RuntimeException("Erro MCP da Guruja: {$msg}");
        }

        return $json['result'] ?? [];
    }

    /**
     * Interpreta o corpo da resposta Streamable HTTP (application/json ou text/event-stream).
     */
    protected function parseMcpResponseBody(string $body, string $contentType, ?int $expectedId = null): ?array
    {
        $trimmed = trim($body);
        if ($trimmed === '') {
            return null;
        }

        $isSse = str_contains(strtolower($contentType), 'text/event-stream')
            || str_starts_with($trimmed, 'event:')
            || str_starts_with($trimmed, 'data:');

        if (!$isSse) {
            $decoded = json_decode($trimmed, true);

            return is_array($decoded) ? $decoded : null;
        }

        $messages = [];
        $dataLines = [];
        foreach (preg_split("/\r\n|\n|\r/", $body) as $line) {
            if ($line === '') {
                if (!empty($dataLines)) {
                    $decoded = json_decode(implode("\n", $dataLines), true);
                    if (is_array($decoded)) {
                        $messages[] = $decoded;
                    }
                    $dataLines = [];
                }
                continue;
            }
            if (str_starts_with($line, 'data:')) {
                $dataLines[] = ltrim(substr($line, 5));
            }
        }
        if (!empty($dataLines)) {
            $decoded = json_decode(implode("\n", $dataLines), true);
            if (is_array($decoded)) {
                $messages[] = $decoded;
            }
        }

        if (empty($messages)) {
            return null;
        }

        if ($expectedId !== null) {
            foreach ($messages as $msg) {
                if (isset($msg['id']) && (int) $msg['id'] === $expectedId) {
                    return $msg;
                }
            }
        }

        foreach (array_reverse($messages) as $msg) {
            if (isset($msg['result']) || isset($msg['error'])) {
                return $msg;
            }
        }

        return end($messages) ?: null;
    }

    /**
     * Consulta a meta atual através do conector MCP da Guruja e atualiza o banco.
     */
    public function fetchCurrentMeta(int $userId, ?string $connection = null): array
    {
        $token = $this->getValidAccessToken($userId, false, $connection);
        if (!$token) {
            throw new \RuntimeException('Não conectado à Guruja. Conecte sua conta para autorizar a sincronização.');
        }

        try {
            return $this->executeFetchCurrentMeta($userId, $token, $connection);
        } catch (\RuntimeException $e) {
            if ($e->getCode() !== 401) {
                throw $e;
            }

            $refreshed = $this->getValidAccessToken($userId, true, $connection);
            if (!$refreshed) {
                throw $e;
            }

            return $this->executeFetchCurrentMeta($userId, $refreshed, $connection);
        }
    }

    protected function executeFetchCurrentMeta(int $userId, string $token, ?string $connection = null): array
    {
        $sessionId = null;
        $rpcId = 1;

        // 1. Inicializar sessão MCP
        $this->callMcp($token, 'initialize', [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'Financeiro Estudos', 'version' => '1.0'],
        ], $rpcId++, $sessionId);

        // 2. Notificar inicialização concluída (spec MCP)
        try {
            $this->callMcp($token, 'notifications/initialized', [], null, $sessionId);
        } catch (\RuntimeException $e) {
            if ($e->getCode() === 401) {
                throw $e;
            }
            Log::debug('Notificação MCP notifications/initialized ignorada: ' . $e->getMessage());
        }

        // 3. Listar ferramentas disponíveis
        $toolsResult = $this->callMcp($token, 'tools/list', [], $rpcId++, $sessionId);
        $tools = $toolsResult['tools'] ?? [];
        if (empty($tools)) {
            throw new \RuntimeException('O servidor MCP da Guruja não retornou ferramentas disponíveis.');
        }

        // 4. Fluxo oficial da Guruja em 2 etapas: historico_de_metas -> detalhe_de_uma_meta
        $historicoTool = $this->findTool($tools, ['historico_de_metas'], ['historico', 'meta']);
        $detalheTool = $this->findTool($tools, ['detalhe_de_uma_meta'], ['detalhe', 'meta']);

        if ($historicoTool && $detalheTool) {
            $meta = $this->fetchViaTwoStepTools($userId, $token, $historicoTool, $detalheTool, $rpcId, $sessionId, $connection);

            return $this->saveMetaToDatabase($userId, $meta, now(), $connection);
        }

        // 5. Fallback para servidores/mocks com ferramenta única de meta
        $metaTool = null;
        $fallbackTool = $tools[0]['name'] ?? null;
        foreach ($tools as $t) {
            $name = strtolower((string) ($t['name'] ?? ''));
            $desc = strtolower((string) ($t['description'] ?? ''));
            if (str_contains($name, 'meta') || str_contains($desc, 'meta') || str_contains($name, 'planejamento') || str_contains($name, 'atividade')) {
                $metaTool = (string) $t['name'];
                break;
            }
        }
        $toolToCall = $metaTool ?? $fallbackTool;
        if (!$toolToCall) {
            throw new \RuntimeException('Nenhuma ferramenta de metas encontrada no conector da Guruja.');
        }

        $callResult = $this->callMcp($token, 'tools/call', [
            'name' => $toolToCall,
            'arguments' => (object) [],
        ], $rpcId++, $sessionId);

        $parsed = $this->extractToolJson($callResult);
        $meta = $this->normalizeMetaPayload($parsed);

        return $this->saveMetaToDatabase($userId, $meta, now(), $connection);
    }

    /**
     * Busca a meta em andamento usando historico_de_metas + detalhe_de_uma_meta e calcula estimativas em PHP se necessário.
     */
    protected function fetchViaTwoStepTools(
        int $userId,
        string $token,
        array $historicoTool,
        array $detalheTool,
        int &$rpcId,
        ?string &$sessionId,
        ?string $connection = null
    ): array {
        $historicoResult = $this->callMcp($token, 'tools/call', [
            'name' => (string) $historicoTool['name'],
            'arguments' => (object) [],
        ], $rpcId++, $sessionId);

        $historicoParsed = $this->extractToolJson($historicoResult);
        $metasList = $this->extractMetaList($historicoParsed);
        if (empty($metasList)) {
            throw new \RuntimeException('Nenhuma meta retornada pelo histórico da Guruja.');
        }

        $currentSummary = $this->findActiveMeta($metasList);
        if (!$currentSummary) {
            throw new \RuntimeException('Nenhuma meta em andamento encontrada na Guruja.');
        }

        $targetId = $currentSummary['target_id'] ?? $currentSummary['id'] ?? null;
        if ($targetId === null) {
            throw new \RuntimeException('A meta em andamento da Guruja não possui target_id.');
        }

        $paramName = $this->resolveTargetIdParamName($detalheTool);
        $detalheResult = $this->callMcp($token, 'tools/call', [
            'name' => (string) $detalheTool['name'],
            'arguments' => [$paramName => is_numeric($targetId) ? (int) $targetId : $targetId],
        ], $rpcId++, $sessionId);

        $detalheParsed = $this->extractToolJson($detalheResult);
        $detalheBody = isset($detalheParsed['meta']) && is_array($detalheParsed['meta'])
            ? $detalheParsed['meta']
            : $detalheParsed;

        // Preenche eventuais campos de resumo ausentes no detalhe
        $merged = $detalheBody;
        foreach (['target_id', 'ordem', 'status', 'data_inicio', 'dias_atraso', 'questoes_resolvidas', 'acertos', 'minutos_estudados'] as $key) {
            if (!isset($merged[$key]) && isset($currentSummary[$key])) {
                $merged[$key] = $currentSummary[$key];
            }
        }
        if (!isset($merged['target_id'])) {
            $merged['target_id'] = (int) $targetId;
        }

        $meta = $this->normalizeMetaPayload($merged);

        // Se a meta mudou ou ainda não há estimativas, recalcula medianas em PHP pelas 3 últimas metas concluídas
        $ordem = $meta['ordem'] ?? null;
        if ($this->needsEstimativas($userId, $ordem, $connection)) {
            $completedIds = $this->selectRecentCompletedMetaIds($metasList, (int) $targetId, 3);
            $historicalMetas = [];
            foreach ($completedIds as $histId) {
                try {
                    $histResult = $this->callMcp($token, 'tools/call', [
                        'name' => (string) $detalheTool['name'],
                        'arguments' => [$paramName => $histId],
                    ], $rpcId++, $sessionId);
                    $histParsed = $this->extractToolJson($histResult);
                    $historicalMetas[] = $this->normalizeMetaPayload($histParsed);
                } catch (\Throwable $e) {
                    Log::info("Não foi possível ler meta concluída {$histId} para estimativas: " . $e->getMessage());
                }
            }

            $estimativas = $this->computeEstimativas($historicalMetas);
            if (!empty($estimativas)) {
                $meta['estimativas'] = $estimativas;
            }
        }

        return $meta;
    }

    /**
     * Localiza uma ferramenta na lista do MCP por nome exato ou combinação de palavras-chave.
     */
    protected function findTool(array $tools, array $exactNames, array $requiredKeywords): ?array
    {
        foreach ($tools as $tool) {
            $name = strtolower((string) ($tool['name'] ?? ''));
            if (in_array($name, $exactNames, true)) {
                return $tool;
            }
        }

        foreach ($tools as $tool) {
            $name = strtolower((string) ($tool['name'] ?? ''));
            $allMatch = true;
            foreach ($requiredKeywords as $kw) {
                if (!str_contains($name, $kw)) {
                    $allMatch = false;
                    break;
                }
            }
            if ($allMatch) {
                return $tool;
            }
        }

        return null;
    }

    protected function resolveTargetIdParamName(array $detalheTool): string
    {
        $props = $detalheTool['inputSchema']['properties'] ?? [];
        if (!is_array($props) || empty($props)) {
            return 'target_id';
        }
        if (array_key_exists('target_id', $props)) {
            return 'target_id';
        }
        foreach (array_keys($props) as $key) {
            if (str_contains(strtolower((string) $key), 'target') || str_contains(strtolower((string) $key), 'id')) {
                return (string) $key;
            }
        }

        return (string) array_key_first($props);
    }

    /**
     * Extrai JSON retornado por uma chamada tools/call (structuredContent ou content[].text).
     */
    protected function extractToolJson(array $callResult): array
    {
        if (!empty($callResult['isError'])) {
            $errText = $this->extractTextFromContent($callResult['content'] ?? []);
            throw new \RuntimeException('A ferramenta da Guruja retornou erro: ' . ($errText ?: 'erro desconhecido'));
        }

        if (isset($callResult['structuredContent']) && is_array($callResult['structuredContent'])) {
            return $callResult['structuredContent'];
        }

        $rawText = $this->extractTextFromContent($callResult['content'] ?? []);
        if ($rawText === '') {
            throw new \RuntimeException('A ferramenta da Guruja não retornou conteúdo de texto.');
        }

        $parsed = json_decode($rawText, true);
        if (!is_array($parsed) && preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $rawText, $matches)) {
            $parsed = json_decode($matches[1], true);
        }

        if (!is_array($parsed)) {
            throw new \RuntimeException('O conector da Guruja retornou dados em formato não interpretável.');
        }

        return $parsed;
    }

    protected function extractTextFromContent(array $content): string
    {
        $rawText = '';
        foreach ($content as $item) {
            if (($item['type'] ?? '') === 'text' && !empty($item['text'])) {
                $rawText .= $item['text'];
            }
        }

        return trim($rawText);
    }

    /**
     * Extrai a lista de metas a partir de formatos variados retornados por historico_de_metas.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function extractMetaList(array $parsed): array
    {
        foreach (['metas', 'historico', 'items', 'data', 'results'] as $key) {
            if (isset($parsed[$key]) && is_array($parsed[$key])) {
                return array_values(array_filter($parsed[$key], 'is_array'));
            }
        }

        if (array_is_list($parsed)) {
            return array_values(array_filter($parsed, 'is_array'));
        }

        if (isset($parsed['target_id']) || isset($parsed['id'])) {
            return [$parsed];
        }

        return [];
    }

    protected function findActiveMeta(array $metasList): ?array
    {
        foreach ($metasList as $m) {
            if (!empty($m['esta_em_andamento'])) {
                return $m;
            }
        }

        foreach ($metasList as $m) {
            $status = strtolower((string) ($m['status'] ?? ''));
            if (in_array($status, ['em_andamento', 'em andamento', 'ativa', 'ativo', 'in_progress', 'active'], true)) {
                return $m;
            }
        }

        return null;
    }

    /**
     * Seleciona os target_id das últimas N metas concluídas para cálculo de estimativas.
     *
     * @return array<int, int>
     */
    protected function selectRecentCompletedMetaIds(array $metasList, int $currentTargetId, int $limit = 3): array
    {
        $candidates = [];
        foreach ($metasList as $m) {
            $tid = $m['target_id'] ?? $m['id'] ?? null;
            if (!is_numeric($tid) || (int) $tid === $currentTargetId) {
                continue;
            }
            if (!empty($m['esta_em_andamento'])) {
                continue;
            }
            $candidates[] = [
                'target_id' => (int) $tid,
                'ordem' => isset($m['ordem']) && is_numeric($m['ordem']) ? (int) $m['ordem'] : 0,
            ];
        }

        usort($candidates, fn (array $a, array $b) => $b['ordem'] <=> $a['ordem']);

        return array_slice(array_column($candidates, 'target_id'), 0, $limit);
    }

    protected function needsEstimativas(int $userId, mixed $ordem, ?string $connection = null): bool
    {
        $anteriorRaw = DB::connection($connection)->table('study_guruja_metas')
            ->where('user_id', $userId)
            ->value('payload');
        $anterior = $anteriorRaw ? json_decode($anteriorRaw, true) : [];

        if (empty($anterior['estimativas']) || !is_array($anterior['estimativas'])) {
            return true;
        }

        if ($ordem === null) {
            return false;
        }

        $estimativasOrdem = $anterior['estimativas_ordem'] ?? ($anterior['ordem'] ?? null);

        return (int) $estimativasOrdem !== (int) $ordem;
    }

    /**
     * Calcula medianas de minutos por tipo de atividade (e Reforço) a partir de metas concluídas.
     *
     * @param array<int, array<string, mixed>> $historicalMetas
     * @return array<string, int>
     */
    public function computeEstimativas(array $historicalMetas): array
    {
        $byType = [];

        foreach ($historicalMetas as $meta) {
            foreach ($meta['atividades'] ?? [] as $a) {
                if (empty($a['concluida']) || empty($a['tipo']) || !isset($a['minutos'])) {
                    continue;
                }
                $min = (int) $a['minutos'];
                if ($min > 0) {
                    $byType[(string) $a['tipo']][] = $min;
                }
            }

            foreach ($meta['reforcos'] ?? [] as $r) {
                if (empty($r['concluido']) || !isset($r['minutos'])) {
                    continue;
                }
                $min = (int) $r['minutos'];
                if ($min > 0) {
                    $byType['Reforço'][] = $min;
                }
            }
        }

        $estimativas = [];
        foreach ($byType as $tipo => $values) {
            sort($values, SORT_NUMERIC);
            $count = count($values);
            $mid = intdiv($count, 2);
            $median = $count % 2 === 1
                ? $values[$mid]
                : ($values[$mid - 1] + $values[$mid]) / 2;
            $estimativas[$tipo] = max(1, min(600, (int) round($median)));
        }

        return $estimativas;
    }

    /**
     * Normaliza formatos variados (com ou sem chave 'meta') para a estrutura padrão.
     */
    public function normalizeMetaPayload(array $input): array
    {
        $meta = $input['meta'] ?? $input;
        if (!isset($meta['target_id'])) {
            $meta['target_id'] = $meta['id'] ?? (int) now()->timestamp;
        }
        if (is_numeric($meta['target_id'])) {
            $meta['target_id'] = (int) $meta['target_id'];
        }
        if (isset($meta['ordem']) && is_numeric($meta['ordem'])) {
            $meta['ordem'] = (int) $meta['ordem'];
        }
        if (!empty($meta['data_inicio']) && is_string($meta['data_inicio'])) {
            $meta['data_inicio'] = $this->normalizeDataInicio($meta['data_inicio']);
        }

        if (!isset($meta['atividades']) && isset($meta['tasks'])) {
            $meta['atividades'] = $meta['tasks'];
        }
        if (!isset($meta['atividades']) && is_array($meta)) {
            $atividades = [];
            foreach ($meta as $k => $v) {
                if (is_array($v) && isset($v['titulo'])) {
                    $atividades[] = $v;
                }
            }
            if (!empty($atividades)) {
                $meta = ['target_id' => (int) now()->timestamp, 'atividades' => $atividades];
            }
        }

        $atividades = [];
        foreach ($meta['atividades'] ?? [] as $a) {
            if (!is_array($a) || empty($a['titulo'])) {
                continue;
            }
            $atividades[] = [
                'target_activity_id' => $a['target_activity_id'] ?? $a['id'] ?? null,
                'codigo_atividade' => $a['codigo_atividade'] ?? $a['codigo'] ?? null,
                'titulo' => (string) $a['titulo'],
                'disciplina' => $a['disciplina'] ?? null,
                'tipo' => $a['tipo'] ?? null,
                'concluida' => (bool) ($a['concluida'] ?? $a['concluido'] ?? false),
                'ignorada' => (bool) ($a['ignorada'] ?? $a['ignorado'] ?? false),
                'questoes' => isset($a['questoes']) ? (int) $a['questoes'] : (isset($a['questoes_resolvidas']) ? (int) $a['questoes_resolvidas'] : null),
                'acertos' => isset($a['acertos']) ? (int) $a['acertos'] : null,
                'minutos' => isset($a['minutos']) ? (int) $a['minutos'] : null,
                'relevancia' => isset($a['relevancia']) ? (int) $a['relevancia'] : null,
            ];
        }
        $meta['atividades'] = $atividades;

        $reforcos = [];
        $rawReforcos = $meta['reforcos'] ?? $meta['reforços'] ?? [];
        foreach ($rawReforcos as $r) {
            if (!is_array($r) || empty($r['titulo'])) {
                continue;
            }
            $reforcos[] = [
                'target_activity_id' => $r['target_activity_id'] ?? $r['id'] ?? null,
                'instrucao' => $r['instrucao'] ?? $r['instrução'] ?? null,
                'titulo' => (string) $r['titulo'],
                'disciplina' => $r['disciplina'] ?? null,
                'concluido' => (bool) ($r['concluido'] ?? $r['concluida'] ?? false),
                'ignorado' => (bool) ($r['ignorado'] ?? $r['ignorada'] ?? false),
                'questoes_resolvidas' => isset($r['questoes_resolvidas']) ? (int) $r['questoes_resolvidas'] : (isset($r['questoes']) ? (int) $r['questoes'] : null),
                'acertos' => isset($r['acertos']) ? (int) $r['acertos'] : null,
                'minutos' => isset($r['minutos']) ? (int) $r['minutos'] : null,
            ];
        }
        $meta['reforcos'] = $reforcos;
        unset($meta['reforços']);

        return $meta;
    }

    protected function normalizeDataInicio(string $value): string
    {
        $trimmed = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2}(\.\d+)?)?$/', $trimmed)) {
            return str_replace(' ', 'T', $trimmed) . '-03:00';
        }

        return $trimmed;
    }

    /**
     * Persiste a meta no banco de dados study_guruja_metas mantendo estimativas prévias se aplicável.
     */
    public function saveMetaToDatabase(int $userId, array $meta, ?Carbon $lidoEm = null, ?string $connection = null): array
    {
        $lidoEm = $lidoEm ?? now();
        $ordem = $meta['ordem'] ?? null;

        if (isset($meta['estimativas'])) {
            $meta['estimativas_ordem'] = $ordem;
        } else {
            $anterior = DB::connection($connection)->table('study_guruja_metas')->where('user_id', $userId)->value('payload');
            $anterior = $anterior ? json_decode($anterior, true) : [];
            if (!empty($anterior['estimativas'])) {
                $meta['estimativas'] = $anterior['estimativas'];
                $meta['estimativas_ordem'] = $anterior['estimativas_ordem'] ?? ($anterior['ordem'] ?? null);
            }
        }

        DB::connection($connection)->table('study_guruja_metas')->updateOrInsert(
            ['user_id' => $userId],
            [
                'payload' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'lido_em' => $lidoEm,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return $meta;
    }
}
