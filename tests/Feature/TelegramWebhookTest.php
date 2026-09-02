<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transacao;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Mockery;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
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

    public function test_accepts_sanitized_secret_token(): void
    {
        config(['telegram.webhook_secret' => 'secret.with+symbols=123']);
        config(['telegram.allowed_chat_id' => '12345678']);

        $this->mock(TelegramService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturn(true);
        });

        // Telegram enviará o token limpo (somente a-zA-Z0-9_-)
        $cleanSecret = 'secretwithsymbols123';

        $response = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => $cleanSecret,
        ])->postJson(route('webhook.telegram'), [
            'update_id' => 10001,
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => 'saldo conta corrente',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    public function test_rejects_invalid_secret_token(): void
    {
        config(['telegram.webhook_secret' => 'valid_secret_123']);

        $response = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong_secret',
        ])->postJson(route('webhook.telegram'), [
            'update_id' => 10002,
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => 'saldo',
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_notifies_unauthorized_chat(): void
    {
        config(['telegram.webhook_secret' => '']);
        config(['telegram.allowed_chat_id' => '99999999']);

        $this->mock(TelegramService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with(12345678, Mockery::on(function ($msg) {
                    return str_contains($msg, 'Acesso Não Autorizado') && str_contains($msg, '12345678');
                }))
                ->andReturn(true);
        });

        $response = $this->postJson(route('webhook.telegram'), [
            'update_id' => 10003,
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => 'saldo',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true, 'skipped' => 'unauthorized chat']);
    }
}
