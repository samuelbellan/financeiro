<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramRegisterLocalCommand extends Command
{
    /**
     * O nome e assinatura do comando.
     *
     * @var string
     */
    protected $signature = 'telegram:register-local
                            {url? : URL pública do seu projeto local (ex: https://unfrosted-surreal-ducky.ngrok-free.dev)}
                            {--cloud= : URL do projeto na Nuvem (padrão: env CLOUD_APP_URL ou https://financeiro-app-mrik.onrender.com)}';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Registra o endereço público do projeto local na Nuvem para roteamento automático de mensagens do bot do Telegram';

    /**
     * Executa o comando.
     */
    public function handle(): int
    {
        $localUrl = $this->argument('url') ?: config('app.url');

        if (empty($localUrl)) {
            $this->error('❌ URL local não definida. Passe como argumento ou configure APP_URL no .env.');
            return 1;
        }

        $cloudUrl = $this->option('cloud') ?: env('CLOUD_APP_URL', 'https://financeiro-app-mrik.onrender.com');
        $endpoint = rtrim($cloudUrl, '/') . '/webhook/telegram/register-local';
        $secret = config('telegram.webhook_secret');

        $this->info("Conectando à Nuvem ({$cloudUrl})...");
        $this->line("Registrando URL do projeto local: {$localUrl}");

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Telegram-Bot-Api-Secret-Token' => $secret,
                    'Accept'                          => 'application/json',
                ])
                ->post($endpoint, [
                    'url'    => $localUrl,
                    'secret' => $secret,
                ]);

            if ($response->successful()) {
                $this->info('✅ URL local registrada na Nuvem com sucesso!');
                $this->line('  URL ativa: ' . ($response->json('local_url') ?? $localUrl));
                $this->newLine();
                $this->info('🤖 Roteamento Inteligente Configurado:');
                $this->line('  1. O Bot na Nuvem recebe a nota fiscal / mensagem no Telegram.');
                $this->line('  2. A Nuvem verifica se o seu projeto local está ONLINE.');
                $this->line('  3. Se ONLINE: encaminha os dados e fotos diretamente para o seu projeto LOCAL!');
                $this->line('  4. Se OFFLINE: salva na NUVEM automaticamente como fallback de segurança!');
                return 0;
            } else {
                $this->error('❌ Falha ao registrar na Nuvem: HTTP ' . $response->status() . ' - ' . $response->body());
                return 1;
            }
        } catch (\Throwable $e) {
            $this->error('❌ Erro de conexão com a Nuvem: ' . $e->getMessage());
            return 1;
        }
    }
}
