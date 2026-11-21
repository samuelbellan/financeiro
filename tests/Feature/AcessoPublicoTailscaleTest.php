<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Mockery;
use Tests\TestCase;

class AcessoPublicoTailscaleTest extends TestCase
{
    public function test_host_ts_net_gera_urls_https(): void
    {
        $this->get('http://meu-pc.tail1234.ts.net/login');
        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://meu-pc.tail1234.ts.net/', URL::to('/home'));
    }

    public function test_login_google_pelo_ts_net_volta_para_o_mesmo_host(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'segredo']);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('redirectUrl')->once()
            ->with('https://meu-pc.tail1234.ts.net/auth/google/callback')->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('https://meu-pc.tail1234.ts.net/auth/google')->assertRedirect();
    }

    public function test_login_google_local_mantem_redirect_do_env(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'segredo']);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldNotReceive('redirectUrl');
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('http://localhost:8000/auth/google')->assertRedirect();
    }
}
