<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $botToken;
    protected string $baseUrl;

    public function __construct()
    {
        $this->botToken = config('telegram.bot_token', '');
        $this->baseUrl  = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Retorna um PendingRequest com timeout e, em ambiente local,
     * desabilita a verificação SSL (Windows dev não tem CA bundle).
     */
    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::timeout(10);

        if (app()->environment('local')) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    /**
     * Envia uma mensagem de texto para o chat informado.
     *
     * @param int|string $chatId  ID do chat
     * @param string     $message Texto a enviar (suporta Markdown)
     */
    public function sendMessage(int|string $chatId, string $message): bool
    {
        if (empty($this->botToken)) {
            Log::warning('[Telegram] Token não configurado — mensagem não enviada.', [
                'chat_id'  => $chatId,
                'mensagem' => $message,
            ]);
            return false;
        }

        try {
            $response = $this->http()->post("{$this->baseUrl}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $message,
                'parse_mode' => 'Markdown',
            ]);

            if (!$response->successful()) {
                Log::error('[Telegram] Falha ao enviar mensagem.', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[Telegram] Exceção ao enviar mensagem: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envia um arquivo/documento para o chat informado.
     *
     * @param int|string $chatId
     * @param string     $fileContents Conteúdo binário do arquivo
     * @param string     $filename     Nome do arquivo (ex: fatura.pdf)
     * @param string|null $caption     Legenda opcional
     */
    public function sendDocument(int|string $chatId, string $fileContents, string $filename, ?string $caption = null): bool
    {
        if (empty($this->botToken)) {
            Log::warning('[Telegram] Token não configurado — documento não enviado.');
            return false;
        }

        try {
            $request = $this->http();
            
            $multipart = [
                'chat_id' => $chatId,
            ];
            if ($caption) {
                $multipart['caption'] = $caption;
            }

            $response = $request->attach('document', $fileContents, $filename)
                ->post("{$this->baseUrl}/sendDocument", $multipart);

            if (!$response->successful()) {
                Log::error('[Telegram] Falha ao enviar documento.', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[Telegram] Exceção ao enviar documento: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Configura o webhook do Telegram.
     *
     * @param string $url    URL pública do webhook
     * @param string $secret Token secreto para validação
     */
    public function setWebhook(string $url, string $secret = ''): array
    {
        $params = [
            'url'             => $url,
            'allowed_updates' => ['message'],
        ];

        if (!empty($secret)) {
            $params['secret_token'] = $secret;
        }

        $response = $this->http()->post("{$this->baseUrl}/setWebhook", $params);

        return $response->json() ?? ['ok' => false, 'description' => 'No response'];
    }

    /**
     * Remove o webhook do Telegram.
     */
    public function deleteWebhook(): array
    {
        $response = $this->http()->post("{$this->baseUrl}/deleteWebhook");
        return $response->json() ?? ['ok' => false];
    }

    /**
     * Obtém as últimas atualizações (útil para pegar chat_id).
     */
    public function getUpdates(): array
    {
        $response = $this->http()->get("{$this->baseUrl}/getUpdates");
        return $response->json() ?? ['ok' => false];
    }
}
