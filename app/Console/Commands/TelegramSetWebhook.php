<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook
                            {url? : URL pública do webhook (ex: https://seu-dominio.com/webhook/telegram)}
                            {--delete : Remove o webhook atual}
                            {--info : Exibe o status atual do webhook no Telegram}';

    protected $description = 'Configura, remove ou consulta o status do webhook do Telegram Bot';

    public function handle(TelegramService $telegram): int
    {
        if ($this->option('info')) {
            $info = $telegram->getWebhookInfo();
            if ($info['ok'] ?? false) {
                $res = $info['result'] ?? [];
                $this->info('📊 Informações atuais do Webhook no Telegram:');
                $this->line('  URL atual: ' . (!empty($res['url']) ? $res['url'] : '(nenhuma / webhook desativado)'));
                $this->line('  Mensagens pendentes: ' . ($res['pending_update_count'] ?? 0));
                if (!empty($res['last_error_message'])) {
                    $this->warn('  Último erro: ' . $res['last_error_message']);
                    if (!empty($res['last_error_date'])) {
                        $this->line('  Data do erro: ' . date('Y-m-d H:i:s', $res['last_error_date']));
                    }
                }
            } else {
                $this->error('❌ Falha ao obter informações do webhook: ' . ($info['description'] ?? 'Erro desconhecido'));
                return 1;
            }
            return 0;
        }

        if ($this->option('delete')) {
            $result = $telegram->deleteWebhook();
            if ($result['ok'] ?? false) {
                $this->info('✅ Webhook removido com sucesso.');
            } else {
                $this->error('❌ Erro ao remover webhook: ' . ($result['description'] ?? 'Erro desconhecido'));
            }
            return 0;
        }

        $url = $this->argument('url');

        if (empty($url)) {
            $url = $this->ask('Digite a URL pública do webhook (ex: https://abc123.ngrok.io/webhook/telegram)');
        }

        if (empty($url)) {
            $this->error('URL não informada.');
            return 1;
        }

        // Garante que o path do webhook está correto sem barras duplicadas
        if (!str_contains($url, '/webhook/telegram')) {
            $url = rtrim($url, '/') . '/webhook/telegram';
        } else {
            $url = preg_replace('#([^:])//+#', '$1/', $url);
        }

        $secret = config('telegram.webhook_secret');

        $this->info("Configurando webhook...");
        $this->line("  URL: {$url}");
        if ($secret) {
            $this->line("  Secret: (configurado)");
        }

        $result = $telegram->setWebhook($url, $secret);

        if ($result['ok'] ?? false) {
            $this->info('✅ Webhook configurado com sucesso!');
            $this->newLine();
            $this->info('Agora envie uma mensagem para o bot no Telegram para testar.');
        } else {
            $this->error('❌ Erro: ' . ($result['description'] ?? 'Erro desconhecido'));
            return 1;
        }

        return 0;
    }
}
