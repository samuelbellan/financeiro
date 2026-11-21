<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia o acesso a um módulo que o usuário não tem liberado (users.modulos).
 * O módulo é descoberto pelo início da URL, conforme config/modulos.php.
 */
class EnsureModuloPermitido
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $modulo = self::moduloDaRota($request->path());

        if ($user && $modulo && !$user->podeAcessar($modulo)) {
            abort(403, 'Você não tem acesso a este módulo.');
        }

        return $next($request);
    }

    public static function moduloDaRota(string $path): ?string
    {
        $path = trim($path, '/');

        foreach (config('modulos.disponiveis', []) as $chave => $modulo) {
            foreach ($modulo['prefixos'] as $prefixo) {
                if ($path === $prefixo || str_starts_with($path, $prefixo . '/')) {
                    return $chave;
                }
            }
        }

        return null;
    }
}
