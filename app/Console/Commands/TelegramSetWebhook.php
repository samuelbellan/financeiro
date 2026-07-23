<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook
                            {url? : URL pública do webhook (ex: https://seu-dominio.com/webhook/telegram)}
                            {--delete : Remove o webhook atual}';

    protected $description = 'Configura ou remove o webhook do Telegram Bot';

    public function handle(TelegramService $telegram): int
    {
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

        // Garante que o path do webhook está correto
        if (!str_contains($url, '/webhook/telegram')) {
            $url = rtrim($url, '/') . '/webhook/telegram';
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
