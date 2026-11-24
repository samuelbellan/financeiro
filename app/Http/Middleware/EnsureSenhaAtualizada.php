<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quem recebeu senha definida pelo gestor só usa o sistema depois de trocá-la.
 */
class EnsureSenhaAtualizada
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password && !$request->session()->get('shadow_login') && !$request->routeIs('conta.senha*', 'logout')) {
            return redirect()->route('conta.senha')
                ->with('info', 'Defina uma nova senha para continuar.');
        }

        return $next($request);
    }
}
