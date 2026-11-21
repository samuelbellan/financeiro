<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Libera a rota só para o dono do sistema (users.is_owner).
 * Usado em operações que afetam o banco inteiro, como a sincronização com a nuvem.
 */
class EnsureUserIsOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->is_owner) {
            abort(403, 'Apenas o dono do sistema pode fazer isso.');
        }

        return $next($request);
    }
}
