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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
