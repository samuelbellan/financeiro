<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Telas do planejamento dirigido (meta, questões, desempenho, simulados, perfil): só para quem segue o plano. */
class EnsurePlanoConcursos
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->temPlanoConcursos()) {
            return redirect()->route('concursos.material.index');
        }

        return $next($request);
    }
}
