<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // *.ts.net = Tailscale Funnel: o acesso externo é sempre HTTPS, mesmo que o proxy
        // não envie X-Forwarded-Proto ao repassar para o php artisan serve local.
        if (config('app.env') === 'production'
            || request()->header('x-forwarded-proto') === 'https'
            || str_ends_with(request()->getHost(), '.ts.net')) {
            URL::forceScheme('https');
        }
    }
}
