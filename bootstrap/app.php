<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'owner' => \App\Http\Middleware\EnsureUserIsOwner::class,
            'modulos' => \App\Http\Middleware\EnsureModuloPermitido::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhook/whatsapp',
            'webhook/telegram',
            'webhook/telegram/register-local',
            'api/tracker/*',
            'api/tv/*',
            'api/estudos/guruja/*',
        ]);
        // O texto do livro de resumo (JSON do editor) chega em pedaços: aparar cada um come os
        // espaços entre palavras com e sem negrito/link.
        $semAparar = fn (\Illuminate\Http\Request $r) => $r->is('concursos/material/capitulos/*/conteudo');
        \Illuminate\Foundation\Http\Middleware\TrimStrings::skipWhen($semAparar);
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::skipWhen($semAparar);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
