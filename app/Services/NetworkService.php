<?php

namespace App\Services;

class NetworkService
{
    /**
     * Obter o endereço IPv4 local do servidor na rede Wi-Fi / Ethernet.
     */
    public static function getLocalIp(): string
    {
        // 1. Se a requisição atual já veio por um IP de rede local, aproveitamos ele
        try {
            if (function_exists('request') && request()) {
                $requestHost = request()->getHost();
                if ($requestHost && filter_var($requestHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    if (!str_starts_with($requestHost, '127.') && !str_starts_with($requestHost, '172.29.')) {
                        return $requestHost;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignorar erro se fora do ciclo de requisição HTTP
        }

        // 2. Tentar detectar via UDP socket (método nativo do SO, sem tráfego real de rede)
        try {
            $client = @stream_socket_client('udp://8.8.8.8:53', $errno, $errstr, 1);
            if ($client) {
                $name = stream_socket_get_name($client, false);
                fclose($client);
                if ($name) {
                    $ip = explode(':', $name)[0] ?? null;
                    if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !str_starts_with($ip, '127.') && !str_starts_with($ip, '172.29.')) {
                        return $ip;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignorar e tentar fallback
        }

        // 3. Fallback no Windows via PowerShell (busca o adaptador com a rota padrão do gateway)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            try {
                $output = [];
                @exec('powershell -NoProfile -Command "(Get-NetRoute -DestinationPrefix \'0.0.0.0/0\' | Sort-Object RouteMetric | Select-Object -First 1 | Get-NetIPAddress -AddressFamily IPv4).IPAddress"', $output);
                $ip = trim(implode('', $output));
                if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !str_starts_with($ip, '127.')) {
                    return $ip;
                }
            } catch (\Throwable $e) {
                // Ignorar
            }
        }

        // 4. IP padrão identificado na máquina
        return '192.168.15.11';
    }

    /**
     * Obter a porta do servidor atual.
     */
    public static function getPort(): int
    {
        try {
            if (function_exists('request') && request()) {
                $port = request()->getPort();
                if ($port && !in_array($port, [80, 443])) {
                    return (int) $port;
                }
            }
        } catch (\Throwable $e) {
            // Ignorar
        }

        return (int) env('SERVER_PORT', 8000);
    }

    /**
     * Obter a URL completa para acesso na rede local (ex: http://192.168.15.11:8000).
     */
    public static function getLocalUrl(string $path = ''): string
    {
        $ip = self::getLocalIp();
        $port = self::getPort();
        $portStr = ($port === 80) ? '' : ":{$port}";
        $pathStr = $path ? '/' . ltrim($path, '/') : '';

        return "http://{$ip}{$portStr}{$pathStr}";
    }

    /**
     * URL do QR code online (como alternativa/fallback caso JS offline falhe).
     */
    public static function getOnlineQrCodeUrl(?string $url = null): string
    {
        $target = $url ?: self::getLocalUrl();
        return 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($target);
    }
}
