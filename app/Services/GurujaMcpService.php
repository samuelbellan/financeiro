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

    public function isConnected(int $userId): bool
    {
        if (config('services.guruja_mcp.access_token') || env('GURUJA_ACCESS_TOKEN')) {
            return true;
        }

        return DB::table('study_guruja_tokens')
            ->where('user_id', $userId)
            ->whereNotNull('access_token')
            ->exists();
    }

    public function disconnect(int $userId): void
    {
        DB::table('study_guruja_tokens')->where('user_id', $userId)->delete();
    }

    /**
     * Obtém ou registra um client_id no Authorization Server da Guruja.
     */
    public function getClientId(string $redirectUri): string
    {
        $cached = Cache::get('guruja_mcp_client_id');
        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::timeout(10)->post(self::REGISTER_ENDPOINT, [
                'client_name' => 'Financeiro Estudos',
                'redirect_uris' => [$redirectUri],
            ]);

            if ($response->successful() && !empty($response->json('client_id'))) {
                $clientId = $response->json('client_id');
                Cache::put('guruja_mcp_client_id', $clientId, now()->addDays(30));
                return $clientId;
            }
        } catch (\Throwable $e) {
            Log::warning('Registro dinâmico de client_id no Guruja MCP falhou: ' . $e->getMessage());
        }

        // Fallback para client_id já registrado anteriormente
        return 'mcp-f2b0d76e-0176-4af7-9713-81de3be1c628';
    }

    /**
     * Inicia o fluxo OAuth PKCE e retorna a URL de autorização.
     */
    public function buildAuthUrl(int $userId, string $redirectUri): string
    {
        $clientId = $this->getClientId($redirectUri);
        $state = Str::random(40);
        $verifier = Str::random(64);

        // PKCE S256 challenge
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        Cache::put("guruja_oauth:{$state}", [
            'user_id' => $userId,
            'verifier' => $verifier,
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
        ], now()->addMinutes(15));

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'mcp:read',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'state' => $state,
        ]);

        return self::AUTH_ENDPOINT . '?' . $params;
    }

    /**
     * Trata o retorno do OAuth, troca o code pelo token e salva no banco.
     */
    public function handleCallback(string $code, string $state): array
    {
        $data = Cache::pull("guruja_oauth:{$state}");
        if (!$data || empty($data['user_id'])) {
            throw new \RuntimeException('Sessão de autorização expirada ou inválida. Tente novamente.');
        }

        $userId = (int) $data['user_id'];
        $verifier = $data['verifier'];
        $clientId = $data['client_id'];
        $redirectUri = $data['redirect_uri'];

        $response = Http::asForm()->timeout(15)->post(self::TOKEN_ENDPOINT, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $clientId,
            'code_verifier' => $verifier,
        ]);

        if (!$response->successful()) {
            Log::warning('Erro na troca de token do Guruja MCP: ' . $response->body());
            $msg = $response->json('error_description') ?? $response->json('error') ?? "Erro {$response->status()} ao obter token";
            throw new \RuntimeException("Não foi possível conectar com a Guruja: {$msg}");
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'];
        $refreshToken = $tokenData['refresh_token'] ?? null;
        $expiresIn = $tokenData['expires_in'] ?? 3600;

        DB::table('study_guruja_tokens')->updateOrInsert(
            ['user_id' => $userId],
            [
                'client_id' => $clientId,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => now()->addSeconds((int) $expiresIn),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return [
            'user_id' => $userId,
            'access_token' => $accessToken,
        ];
    }

    /**
     * Obtém um token de acesso válido, renovando com o refresh_token se necessário.
     */
    public function getValidAccessToken(int $userId): ?string
    {
        $directToken = config('services.guruja_mcp.access_token') ?: env('GURUJA_ACCESS_TOKEN');
        if (!empty($directToken)) {
            return $directToken;
        }

        $row = DB::table('study_guruja_tokens')->where('user_id', $userId)->first();
        if (!$row || empty($row->access_token)) {
            return null;
        }

        $expiresAt = $row->expires_at ? Carbon::parse($row->expires_at) : null;
        if (!$expiresAt || $expiresAt->isPast() || $expiresAt->diffInSeconds(now()) < 60) {
            if (!empty($row->refresh_token)) {
                $refreshed = $this->refreshToken($userId, $row);
                if ($refreshed) {
                    return $refreshed;
                }
            }
        }

        return $row->access_token;
    }

    protected function refreshToken(int $userId, object $row): ?string
    {
        try {
            $response = Http::asForm()->timeout(15)->post(self::TOKEN_ENDPOINT, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $row->refresh_token,
                'client_id' => $row->client_id ?? $this->getClientId(''),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'];
                $refreshToken = $data['refresh_token'] ?? $row->refresh_token;
                $expiresIn = $data['expires_in'] ?? 3600;

                DB::table('study_guruja_tokens')->where('user_id', $userId)->update([
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'expires_at' => now()->addSeconds((int) $expiresIn),
                    'updated_at' => now(),
                ]);

                return $accessToken;
            }
        } catch (\Throwable $e) {
            Log::warning("Falha ao renovar token MCP da Guruja para usuário {$userId}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Faz requisição JSON-RPC ao MCP Streamable HTTP.
     */
    public function callMcp(string $accessToken, string $method, array $params = [], int $id = 1): array
    {
        $response = Http::timeout(25)
            ->withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(self::MCP_ENDPOINT, [
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => (object) $params,
                'id' => $id,
            ]);

        if ($response->status() === 401) {
            throw new \RuntimeException('Token MCP expirado ou não reconhecido pela Guruja.');
        }

        if (!$response->successful()) {
            throw new \RuntimeException("Erro HTTP {$response->status()} ao consultar MCP da Guruja.");
        }

        $json = $response->json();
        if (isset($json['error'])) {
            $msg = $json['error']['message'] ?? json_encode($json['error']);
            throw new \RuntimeException("Erro MCP da Guruja: {$msg}");
        }

        return $json['result'] ?? [];
    }

    /**
     * Consulta a meta atual através do conector MCP da Guruja e atualiza o banco local.
     */
    public function fetchCurrentMeta(int $userId): array
    {
        $token = $this->getValidAccessToken($userId);
        if (!$token) {
            throw new \RuntimeException('Não conectado à Guruja. Faça login para autorizar a sincronização.');
        }

        // 1. Inicializar
        $this->callMcp($token, 'initialize', [
            'protocolVersion' => '2024-11-05',
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'Financeiro Estudos', 'version' => '1.0'],
        ], 1);

        // 2. Listar ferramentas disponíveis
        $toolsResult = $this->callMcp($token, 'tools/list', [], 2);
        $tools = $toolsResult['tools'] ?? [];
        if (empty($tools)) {
            throw new \RuntimeException('O servidor MCP da Guruja não retornou ferramentas disponíveis.');
        }

        // 3. Identificar a ferramenta de meta / planejamento
        $metaTool = null;
        $fallbackTool = $tools[0]['name'] ?? null;
        foreach ($tools as $t) {
            $name = strtolower($t['name'] ?? '');
            $desc = strtolower($t['description'] ?? '');
            if (str_contains($name, 'meta') || str_contains($desc, 'meta') || str_contains($name, 'planejamento') || str_contains($name, 'atividade')) {
                $metaTool = $t['name'];
                break;
            }
        }
        $toolToCall = $metaTool ?? $fallbackTool;
        if (!$toolToCall) {
            throw new \RuntimeException('Nenhuma ferramenta de metas encontrada no conector da Guruja.');
        }

        // 4. Executar chamada da ferramenta
        $callResult = $this->callMcp($token, 'tools/call', [
            'name' => $toolToCall,
            'arguments' => (object) [],
        ], 3);

        $content = $callResult['content'] ?? [];
        $rawText = '';
        foreach ($content as $item) {
            if (($item['type'] ?? '') === 'text' && !empty($item['text'])) {
                $rawText .= $item['text'];
            }
        }

        if (empty($rawText)) {
            throw new \RuntimeException('A ferramenta da Guruja não retornou conteúdo de texto.');
        }

        // Tentar decodificar JSON retornado pela ferramenta
        $parsed = json_decode($rawText, true);
        if (!is_array($parsed)) {
            // Se o retorno contiver blocos de código markdown ```json ... ```
            if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $rawText, $matches)) {
                $parsed = json_decode($matches[1], true);
            }
        }

        if (!is_array($parsed)) {
            throw new \RuntimeException('O conector da Guruja retornou dados em formato não interpretável.');
        }

        $meta = $this->normalizeMetaPayload($parsed);
        $this->saveMetaToDatabase($userId, $meta, now());

        return $meta;
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
        foreach ($meta['reforcos'] ?? [] as $r) {
            if (!is_array($r) || empty($r['titulo'])) {
                continue;
            }
            $reforcos[] = [
                'target_activity_id' => $r['target_activity_id'] ?? $r['id'] ?? null,
                'instrucao' => $r['instrucao'] ?? null,
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

        return $meta;
    }

    /**
     * Persiste a meta no banco de dados study_guruja_metas mantendo estimativas prévias se aplicável.
     */
    public function saveMetaToDatabase(int $userId, array $meta, ?Carbon $lidoEm = null): void
    {
        $lidoEm = $lidoEm ?? now();
        $ordem = $meta['ordem'] ?? null;

        if (isset($meta['estimativas'])) {
            $meta['estimativas_ordem'] = $ordem;
        } else {
            $anterior = DB::table('study_guruja_metas')->where('user_id', $userId)->value('payload');
            $anterior = $anterior ? json_decode($anterior, true) : [];
            if (!empty($anterior['estimativas'])) {
                $meta['estimativas'] = $anterior['estimativas'];
                $meta['estimativas_ordem'] = $anterior['estimativas_ordem'] ?? ($anterior['ordem'] ?? null);
            }
        }

        DB::table('study_guruja_metas')->updateOrInsert(
            ['user_id' => $userId],
            [
                'payload' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'lido_em' => $lidoEm,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
