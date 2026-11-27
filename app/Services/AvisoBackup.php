<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Avisa no Telegram quando um backup falha, e de novo quando ele volta a funcionar.
 * Para não mandar a mesma falha toda hora, repete o aviso de um mesmo backup só a cada
 * AVISO_REPETE_HORAS enquanto ele continuar falhando.
 */
class AvisoBackup
{
    public const REPETE_HORAS = 6;

    public function __construct(private TelegramService $telegram)
    {
    }

    public function falhou(string $qual, string $motivo): void
    {
        Log::error("[Backup] {$qual}: {$motivo}");
        $chave = $this->chave($qual);
        $ultimo = Cache::get($chave);
        if ($ultimo && now()->diffInHours($ultimo, true) < self::REPETE_HORAS) {
            return;
        }
        Cache::forever($chave, now());
        $this->enviar("⚠️ Backup falhou: {$qual}\n{$motivo}\n\nO PC tenta de novo a cada hora; aviso de novo em " . self::REPETE_HORAS . ' h se continuar.');
    }

    public function ok(string $qual): void
    {
        if (Cache::pull($this->chave($qual))) {
            $this->enviar("✅ Backup voltou a funcionar: {$qual}");
        }
    }

    public function avisar(string $texto): void
    {
        $this->enviar($texto);
    }

    private function enviar(string $texto): void
    {
        $chat = config('telegram.allowed_chat_id');
        if (!$chat) {
            Log::warning('[Backup] TELEGRAM_ALLOWED_CHAT_ID vazio: aviso não enviado.', ['texto' => $texto]);

            return;
        }
        $this->telegram->sendMessage($chat, $texto);
    }

    private function chave(string $qual): string
    {
        return 'backup-aviso:' . md5($qual);
    }
}
