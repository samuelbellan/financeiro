<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $instance;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('whatsapp.evolution_url', ''), '/');
        $this->apiKey   = config('whatsapp.evolution_key', '');
        $this->instance = config('whatsapp.evolution_instance', '');
    }

    /**
     * Envia uma mensagem de texto para o número informado.
     *
     * @param string $number  Número com DDI, ex: "5511999999999"
     * @param string $message Texto a enviar
     */
    public function sendText(string $number, string $message): bool
    {
        if (empty($this->baseUrl) || empty($this->apiKey) || empty($this->instance)) {
            Log::warning('[WhatsApp] Configuração incompleta — mensagem não enviada.', [
                'para'     => $number,
                'mensagem' => $message,
            ]);
            return false;
        }

        try {
            $response = Http::withHeaders([
                'apikey'       => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/message/sendText/{$this->instance}", [
                'number'  => $number,
                'options' => ['delay' => 0],
                'textMessage' => ['text' => $message],
            ]);

            if (!$response->successful()) {
                Log::error('[WhatsApp] Falha ao enviar mensagem.', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[WhatsApp] Exceção ao enviar mensagem: ' . $e->getMessage());
            return false;
        }
    }
}
