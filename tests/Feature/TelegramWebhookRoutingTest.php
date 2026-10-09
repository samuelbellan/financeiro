<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transacao;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

class TelegramWebhookRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create([
            'id' => 1,
            'name' => 'Samuel',
            'email' => 'samuel@teste.com',
        ]);
    }

    public function test_ping_endpoint_returns_online_status(): void
    {
        $response = $this->getJson(route('webhook.telegram.ping'));

        $response->assertStatus(200);
        $response->assertJson([
            'ok' => true,
            'status' => 'online',
        ]);
    }

    public function test_register_local_url_stores_in_cache(): void
    {
        config(['telegram.webhook_secret' => 'test_secret_123']);

        $response = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'test_secret_123',
        ])->postJson(route('webhook.telegram.register-local'), [
            'url' => 'https://my-local-test.ngrok-free.dev',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'ok' => true,
            'local_url' => 'https://my-local-test.ngrok-free.dev/webhook/telegram',
        ]);

        $this->assertEquals(
            'https://my-local-test.ngrok-free.dev/webhook/telegram',
            Cache::get('telegram_dynamic_local_url')
        );
    }

    public function test_forwards_to_local_when_local_is_online(): void
    {
        config([
            'telegram.webhook_secret' => 'secret123',
            'telegram.allowed_chat_id' => '12345678',
            'telegram.local_webhook_url' => 'https://remote-local-pc.ngrok-free.dev/webhook/telegram',
            'app.url' => 'https://financeiro-app.onrender.com',
        ]);

        // Mocking HTTP: o ping responde 200 (online) e o forward responde 200
        Http::fake([
            'https://remote-local-pc.ngrok-free.dev/webhook/telegram/ping' => Http::response(['ok' => true, 'status' => 'online'], 200),
            'https://remote-local-pc.ngrok-free.dev/webhook/telegram' => Http::response(['ok' => true, 'handled_by' => 'local_machine'], 200),
        ]);

        $response = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'secret123',
        ])->postJson(route('webhook.telegram'), [
            'update_id' => 20001,
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => 'Gastei 50 no mercado',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'ok' => true,
            'handled_by' => 'local',
        ]);

        // Nenhuma transação criada na Nuvem, pois foi tratada pelo Local
        $this->assertEquals(0, Transacao::count());
    }

    public function test_falls_back_to_cloud_when_local_is_offline(): void
    {
        config([
            'telegram.webhook_secret' => 'secret123',
            'telegram.allowed_chat_id' => '12345678',
            'telegram.local_webhook_url' => 'https://remote-local-pc.ngrok-free.dev/webhook/telegram',
            'app.url' => 'https://financeiro-app.onrender.com',
        ]);

        // Mocking HTTP: o ping falha com 502/timeout (PC local offline)
        Http::fake([
            'https://remote-local-pc.ngrok-free.dev/webhook/telegram/ping' => Http::response('Offline', 502),
        ]);

        $this->mock(TelegramService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturn(true);
        });

        $response = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'secret123',
        ])->postJson(route('webhook.telegram'), [
            'update_id' => 20002,
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => 'Almoço 35',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        // A Nuvem processou a transação em fallback
        $this->assertEquals(1, Transacao::count());
        $this->assertEquals(35, Transacao::first()->valor);
    }

    public function test_does_not_forward_when_already_forwarded_from_cloud(): void
    {
        config([
            'telegram.webhook_secret' => 'secret123',
            'telegram.allowed_chat_id' => '12345678',
            'telegram.local_webhook_url' => 'https://another-url.com/webhook/telegram',
            'app.url' => 'https://financeiro-app.onrender.com',
        ]);

        $this->mock(TelegramService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturn(true);
        });

        // Quando a requisição vem com cabeçalho X-Forwarded-From-Cloud, não deve tentar encaminhar de novo
        $response = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'secret123',
            'X-Forwarded-From-Cloud' => 'true',
        ])->postJson(route('webhook.telegram'), [
            'update_id' => 20003,
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => 'Café 10',
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, Transacao::count());
    }
}
