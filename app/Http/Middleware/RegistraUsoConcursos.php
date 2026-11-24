<?php

namespace App\Http\Middleware;

use App\Models\Concursos\UsoEvento;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Telemetria do módulo de concursos: registra cada tela aberta e cada ação (rota, ids, filtros,
 * status, tempo). O corpo do pedido não é guardado. Falha aqui nunca derruba a página.
 * A tela recebe o uuid do evento (atributo "uso_evento") para o navegador mandar o tempo visível.
 */
class RegistraUsoConcursos
{
    /** Rotas de apoio que só fariam ruído (imagens, beacon, prévias). */
    private const IGNORAR = ['concursos.uso.tempo', 'concursos.material.anexos.show', 'concursos.material.citacao'];

    public function handle(Request $request, Closure $next): Response
    {
        $rota = $request->route()?->getName();
        $user = $request->user();
        if (!$user || !$rota || in_array($rota, self::IGNORAR, true)) {
            return $next($request);
        }

        $uuid = (string) Str::uuid();
        $request->attributes->set('uso_evento', $uuid);
        $inicio = hrtime(true);

        $response = $next($request);

        try {
            $parametros = array_filter([
                'rota' => array_map(fn ($v) => is_object($v) ? ($v->getKey() ?? null) : $v, $request->route()->parameters()),
                'url' => $request->query(),
            ]);
            $json = $parametros ? json_encode($parametros, JSON_UNESCAPED_UNICODE) : null;

            UsoEvento::create([
                'uuid' => $uuid,
                'user_id' => $user->id,
                'sessao' => substr(hash('sha256', $request->hasSession() ? $request->session()->getId() : $request->ip()), 0, 16),
                'rota' => $rota,
                'metodo' => $request->method(),
                'caminho' => Str::limit('/' . ltrim($request->path(), '/'), 197),
                'parametros' => $json && strlen($json) <= 1000 ? $parametros : null,
                'status' => $response->getStatusCode(),
                'duracao_ms' => (int) ((hrtime(true) - $inicio) / 1e6),
                'dispositivo' => preg_match('/Mobi|Android|iPhone|iPad/i', (string) $request->userAgent()) ? 'celular' : 'computador',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telemetria de concursos falhou: ' . $e->getMessage());
        }

        return $response;
    }
}
