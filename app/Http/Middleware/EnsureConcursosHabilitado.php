<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Rotas do módulo novo de concursos só existem onde CONCURSOS_ENABLED=true (o PC com o Postgres). */
class EnsureConcursosHabilitado
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('concursos.enabled'), 404);

        return $next($request);
    }
}
