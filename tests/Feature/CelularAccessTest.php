<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\NetworkService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CelularAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_network_service_returns_valid_ip_and_url(): void
    {
        $ip = NetworkService::getLocalIp();
        $this->assertNotEmpty($ip);
        $this->assertNotFalse(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));

        $url = NetworkService::getLocalUrl();
        $this->assertStringStartsWith('http://', $url);
        $this->assertStringContainsString($ip, $url);

        $qrUrl = NetworkService::getOnlineQrCodeUrl();
        $this->assertStringContainsString('api.qrserver.com', $qrUrl);
    }

    public function test_celular_view_is_accessible_and_renders_qr_code_info(): void
    {
        $response = $this->get('/celular');

        $response->assertStatus(200);
        $response->assertSee('Acessar no Celular');
        $response->assertSee('REDE WI-FI LOCAL');
        $response->assertSee('qrContainer');
        $response->assertSee(NetworkService::getLocalUrl());
    }

    public function test_home_page_includes_celular_modal_and_sidebar_button(): void
    {
        $user = User::factory()->create([
            'name' => 'Teste Usuario',
            'email' => 'teste@email.com',
        ]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('celularModalBackdrop');
        $response->assertSee('openCelularModal()');
        $response->assertSee('Acessar no Celular');
    }
}
