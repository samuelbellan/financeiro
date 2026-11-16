<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GurujaMcpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GurujaSyncCommand extends Command
{
    protected $signature = 'estudos:guruja-sync
                            {--user= : ID do usuário (padrão: 1 ou primeiro)}
                            {--json= : Payload JSON completo para importar diretamente}
                            {--file= : Caminho de arquivo JSON com a meta}
                            {--token= : Token MCP avulso da Guruja para esta chamada}';

    protected $description = 'Sincroniza a meta e as atividades atuais da Guruja no app de estudos via MCP, rotina do Claude ou JSON.';

    public function handle(GurujaMcpService $mcpService): int
    {
        $userId = $this->option('user') ? (int) $this->option('user') : (User::query()->value('id') ?? 1);

        // 1. Importação direta via JSON (--json ou --file)
        $rawJson = $this->option('json');
        if (!$rawJson && $this->option('file')) {
            $filePath = $this->option('file');
            if (!file_exists($filePath)) {
                $this->error("Arquivo não encontrado: {$filePath}");
                return self::FAILURE;
            }
            $rawJson = file_get_contents($filePath);
        }

        if ($rawJson) {
            $parsed = json_decode($rawJson, true);
            if (!is_array($parsed)) {
                $this->error('O conteúdo informado não é um JSON válido.');
                return self::FAILURE;
            }
            $meta = $mcpService->normalizeMetaPayload($parsed);
            $mcpService->saveMetaToDatabase($userId, $meta, now());
            $this->info("✓ Meta importada com sucesso para o usuário {$userId}! Atividades: " . count($meta['atividades'] ?? []) . " | Reforços: " . count($meta['reforcos'] ?? []));
            return self::SUCCESS;
        }

        // 2. Token MCP passado por parâmetro ou configurado
        $overrideToken = $this->option('token');
        if ($overrideToken) {
            $this->info('Consultando MCP da Guruja com o token fornecido...');
            try {
                // Configura temporariamente
                config(['services.guruja_mcp.access_token' => $overrideToken]);
                $meta = $mcpService->fetchCurrentMeta($userId);
                $this->info("✓ Sincronização via MCP realizada com sucesso! Atividades: " . count($meta['atividades'] ?? []));
                return self::SUCCESS;
            } catch (\Throwable $e) {
                $this->error('Erro ao consultar MCP: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        // 3. Usuário conectado via OAuth MCP no banco ou env
        if ($mcpService->isConnected($userId) || config('services.guruja_mcp.access_token') || env('GURUJA_ACCESS_TOKEN')) {
            $this->info("Conectando ao conector MCP da Guruja (https://mcp.guruja.com.br/mcp)...");
            try {
                $meta = $mcpService->fetchCurrentMeta($userId);
                $this->info("✓ Sincronização MCP concluída com sucesso! Total de atividades: " . count($meta['atividades'] ?? []));
                return self::SUCCESS;
            } catch (\Throwable $e) {
                $this->warn('Tentativa via MCP falhou: ' . $e->getMessage());
            }
        }

        // 4. Fallback: disparo de rotina externa do Claude se configurada
        $url = config('services.guruja_sync.fire_url');
        $token = config('services.guruja_sync.fire_token');
        if ($url && $token) {
            $this->info("Disparando rotina agendada do Claude para atualizar a Guruja...");
            try {
                $res = Http::timeout(20)->withToken($token)->withHeaders([
                    'anthropic-beta' => 'experimental-cc-routine-2026-04-01',
                    'anthropic-version' => '2023-06-01',
                ])->post($url, ['text' => 'Sincronização solicitada via comando Artisan.']);

                if ($res->successful()) {
                    $this->info("✓ Rotina do Claude disparada com sucesso! A meta será atualizada em 1–2 minutos.");
                    return self::SUCCESS;
                }
                $this->error("A rotina recusou o disparo: status {$res->status()} - {$res->body()}");
            } catch (\Throwable $e) {
                $this->error("Falha ao contatar a rotina do Claude: " . $e->getMessage());
            }
            return self::FAILURE;
        }

        $this->warn('Nenhum método automático configurado (OAuth MCP pendente de aprovação e GURUJA_SYNC_FIRE_* não definidos).');
        $this->line('Para sincronizar agora, você pode:');
        $this->line('  1. Passar um JSON: php artisan estudos:guruja-sync --json=\'{"atividades":[...]}\'');
        $this->line('  2. Passar um arquivo: php artisan estudos:guruja-sync --file=caminho.json');
        $this->line('  3. Passar o Bearer token do MCP: php artisan estudos:guruja-sync --token="<token>"');

        return self::FAILURE;
    }
}
