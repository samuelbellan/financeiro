<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GurujaMcpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GurujaConnectCommand extends Command
{
    protected $signature = 'estudos:guruja-connect
                            {--user= : ID do usuário local (padrão: primeiro usuário)}
                            {--port=8765 : Porta local temporária em 127.0.0.1 para o callback OAuth}
                            {--timeout=180 : Tempo máximo de espera pelo login no navegador (em segundos)}
                            {--local-only : Salva apenas no banco local, sem enviar para o banco da nuvem (pgsql_cloud)}
                            {--cloud-only : Salva apenas no banco da nuvem (pgsql_cloud)}
                            {--no-sync : Não executa uma sincronização de teste após conectar}';

    protected $description = 'Conecta a conta da Guruja via OAuth PKCE (callback em 127.0.0.1) e grava os tokens no app (local e nuvem).';

    public function handle(GurujaMcpService $mcpService): int
    {
        $localUser = $this->option('user')
            ? User::query()->find((int) $this->option('user'))
            : User::query()->first();

        $localUserId = $localUser?->id ?? (int) ($this->option('user') ?: 1);
        $preferredPort = max(0, (int) $this->option('port'));
        $timeoutSec = max(30, (int) $this->option('timeout'));

        // 1. Abrir servidor HTTP temporário em 127.0.0.1
        $server = @stream_socket_server("tcp://127.0.0.1:{$preferredPort}", $errno, $errstr);
        if (!$server) {
            $server = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        }
        if (!$server) {
            $this->error("Não foi possível abrir porta local em 127.0.0.1: {$errstr} ({$errno})");

            return self::FAILURE;
        }

        $boundName = stream_socket_get_name($server, false);
        $actualPort = (int) substr((string) $boundName, strrpos((string) $boundName, ':') + 1);
        $redirectUri = "http://127.0.0.1:{$actualPort}/oauth/callback";

        // 2. Registrar cliente OAuth e gerar URL de autorização PKCE
        $this->info("Registrando cliente OAuth na Guruja para {$redirectUri}...");
        $clientId = $mcpService->getClientId($redirectUri);
        $pkce = $mcpService->generatePkcePair();
        $state = Str::random(40);
        $authUrl = $mcpService->buildAuthorizeUrl($clientId, $redirectUri, $pkce['challenge'], $state);

        $this->newLine();
        $this->line('Abrindo o login da Guruja no navegador...');
        $this->line("Se não abrir automaticamente, acesse:\n  {$authUrl}");
        $this->newLine();
        $this->info("Aguardando autorização em {$redirectUri} (até {$timeoutSec}s)...");

        $this->openBrowser($authUrl);

        // 3. Aguardar callback OAuth no socket loopback
        $callbackParams = $this->waitForOAuthCallback($server, $timeoutSec);
        fclose($server);

        if (!$callbackParams) {
            $this->error('Tempo esgotado aguardando o retorno da Guruja.');

            return self::FAILURE;
        }

        if (!empty($callbackParams['error'])) {
            $desc = $callbackParams['error_description'] ?? $callbackParams['error'];
            $this->error("Autorização recusada pela Guruja: {$desc}");

            return self::FAILURE;
        }

        if (($callbackParams['state'] ?? '') !== $state) {
            $this->error('Parâmetro state inválido no retorno OAuth. Tente novamente.');

            return self::FAILURE;
        }

        $code = (string) ($callbackParams['code'] ?? '');
        if ($code === '') {
            $this->error('A Guruja não retornou o código de autorização.');

            return self::FAILURE;
        }

        // 4. Trocar o code pelos tokens (access_token + refresh_token)
        $this->info('Trocando código de autorização pelos tokens OAuth...');
        try {
            $tokenData = $mcpService->exchangeAuthorizationCode($code, $pkce['verifier'], $clientId, $redirectUri);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $hasRefresh = !empty($tokenData['refresh_token']);
        $this->info('✓ Tokens recebidos com sucesso! (refresh_token: ' . ($hasRefresh ? 'sim' : 'não') . ', expira em ' . $tokenData['expires_in'] . 's)');

        // 5. Persistir localmente
        if (!$this->option('cloud-only')) {
            $mcpService->saveTokens($localUserId, $tokenData);
            $this->info("✓ Token salvo no banco local (user_id={$localUserId}).");
        }

        // 6. Persistir na nuvem (pgsql_cloud / Render), se configurada
        $cloudSaved = false;
        $cloudUserId = null;
        if (!$this->option('local-only') && $this->hasCloudDatabase()) {
            try {
                $cloudUserId = $this->resolveCloudUserId($localUser?->email, $localUserId);
                $mcpService->saveTokens($cloudUserId, $tokenData, 'pgsql_cloud');
                $cloudSaved = true;
                $this->info("✓ Token salvo no banco da nuvem / Render (pgsql_cloud, user_id={$cloudUserId}).");
            } catch (\Throwable $e) {
                $this->warn('Não foi possível salvar o token em pgsql_cloud: ' . $e->getMessage());
            }
        }

        // 7. Testar sincronização real imediatamente
        if (!$this->option('no-sync')) {
            $this->info('Testando leitura da meta atual direto no MCP da Guruja...');
            try {
                $meta = $this->option('cloud-only') && $cloudSaved && $cloudUserId
                    ? $mcpService->fetchCurrentMeta($cloudUserId, 'pgsql_cloud')
                    : $mcpService->fetchCurrentMeta($localUserId);

                if (!$this->option('cloud-only') && $cloudSaved && $cloudUserId) {
                    $mcpService->saveMetaToDatabase($cloudUserId, $meta, now(), 'pgsql_cloud');
                }

                $ordem = $meta['ordem'] ?? '?';
                $total = count($meta['atividades'] ?? []);
                $done = count(array_filter($meta['atividades'] ?? [], fn ($a) => !empty($a['concluida']) || !empty($a['ignorada'])));
                $reforcos = count($meta['reforcos'] ?? []);
                $estCount = count($meta['estimativas'] ?? []);

                $this->info("✓ Sincronização concluída! Meta {$ordem}: {$done}/{$total} atividades concluídas | {$reforcos} reforços | {$estCount} estimativas.");
            } catch (\Throwable $e) {
                $this->warn('Conexão OAuth salva, mas o teste inicial de leitura falhou: ' . $e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Aguarda a requisição GET /oauth/callback no servidor TCP local.
     *
     * @param resource $server
     * @return array<string, string>|null
     */
    protected function waitForOAuthCallback($server, int $timeoutSec): ?array
    {
        $deadline = microtime(true) + $timeoutSec;

        while (microtime(true) < $deadline) {
            $remaining = max(1, (int) ceil($deadline - microtime(true)));
            $client = @stream_socket_accept($server, min(5, $remaining));
            if (!$client) {
                continue;
            }

            stream_set_timeout($client, 5);
            $requestLine = (string) fgets($client, 8192);
            // Consumir cabeçalhos restantes
            while (($line = fgets($client, 4096)) !== false && trim($line) !== '') {
                // noop
            }

            if (!preg_match('/^GET\s+(\S+)\s+HTTP\//i', $requestLine, $matches)) {
                $this->writeHttpResponse($client, 400, '<h1>Requisição inválida</h1>');
                fclose($client);
                continue;
            }

            $parts = parse_url($matches[1]);
            $path = $parts['path'] ?? '/';

            if ($path !== '/oauth/callback') {
                $this->writeHttpResponse($client, 404, '<h1>Não encontrado</h1>');
                fclose($client);
                continue;
            }

            $query = [];
            parse_str($parts['query'] ?? '', $query);

            if (!empty($query['error'])) {
                $msg = htmlspecialchars((string) ($query['error_description'] ?? $query['error']), ENT_QUOTES, 'UTF-8');
                $this->writeHttpResponse(
                    $client,
                    200,
                    "<h1>Autorização não concluída</h1><p>{$msg}</p><p>Você pode fechar esta aba.</p>"
                );
            } else {
                $this->writeHttpResponse(
                    $client,
                    200,
                    '<h1>✓ Guruja conectada!</h1><p>Os tokens foram recebidos pelo app de estudos. Você já pode fechar esta aba e voltar ao terminal.</p>'
                );
            }

            fclose($client);

            return array_map('strval', $query);
        }

        return null;
    }

    /**
     * @param resource $client
     */
    protected function writeHttpResponse($client, int $status, string $innerHtml): void
    {
        $statusText = match ($status) {
            200 => 'OK',
            400 => 'Bad Request',
            default => 'Not Found',
        };
        $html = '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Guruja OAuth</title>'
            . '<style>body{font-family:system-ui,sans-serif;background:#0a0a0b;color:#f4f4f5;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px;}'
            . 'main{max-width:440px;background:#18181b;border:1px solid #27272a;border-radius:14px;padding:28px;text-align:center;}'
            . 'h1{margin:0 0 12px;font-size:20px;color:#bcef44;}p{margin:0;color:#d4d4d8;line-height:1.5;}</style></head>'
            . "<body><main>{$innerHtml}</main></body></html>";

        $response = "HTTP/1.1 {$status} {$statusText}\r\n"
            . "Content-Type: text/html; charset=utf-8\r\n"
            . 'Content-Length: ' . strlen($html) . "\r\n"
            . "Connection: close\r\n\r\n"
            . $html;

        @fwrite($client, $response);
    }

    protected function openBrowser(string $url): void
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $safe = str_replace("'", "''", $url);
                pclose(popen("powershell -NoProfile -NonInteractive -Command \"Start-Process '{$safe}'\"", 'r'));
            } elseif (PHP_OS_FAMILY === 'Darwin') {
                exec('open ' . escapeshellarg($url) . ' >/dev/null 2>&1 &');
            } else {
                exec('xdg-open ' . escapeshellarg($url) . ' >/dev/null 2>&1 &');
            }
        } catch (\Throwable) {
            // Se falhar, a URL já está impressa no terminal
        }
    }

    protected function hasCloudDatabase(): bool
    {
        return !empty(env('CLOUD_DATABASE_URL')) || !empty(env('DATABASE_URL'));
    }

    protected function resolveCloudUserId(?string $email, int $fallbackId): int
    {
        if ($email) {
            $id = DB::connection('pgsql_cloud')->table('users')->where('email', $email)->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        $firstId = DB::connection('pgsql_cloud')->table('users')->orderBy('id')->value('id');

        return $firstId ? (int) $firstId : $fallbackId;
    }
}
