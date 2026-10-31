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

    public function test_nota_fiscal_replaces_existing_cartao_compra_with_exact_date_and_value(): void
    {
        config(['telegram.webhook_secret' => '']);
        config(['telegram.allowed_chat_id' => '12345678']);
        $user = User::find(1);

        $cartao = \App\Models\Cartao::create([
            'user_id' => $user->id,
            'nome' => 'Nubank Black',
            'limite' => 5000,
            'dia_fechamento' => 1,
            'dia_vencimento' => 10,
            'ativo' => true,
        ]);

        $dataCompra = '2026-10-09';
        $valorExato = 145.80;

        // Compra prévia lançada no cartão
        $compraPrevia = \App\Models\CartaoCompra::create([
            'cartao_id'       => $cartao->id,
            'descricao'       => 'Supermercado Rápido',
            'valor_total'     => $valorExato,
            'tipo'            => 'avista',
            'numero_parcelas' => 1,
            'categoria'       => 'Alimentação',
            'data_compra'     => $dataCompra,
        ]);

        \App\Models\CartaoParcela::create([
            'cartao_compra_id' => $compraPrevia->id,
            'numero_parcela'   => 1,
            'valor_parcela'    => $valorExato,
            'data_vencimento'  => '2026-11-10',
            'status'           => 'aberta',
        ]);

        $this->assertEquals(1, \App\Models\CartaoCompra::count());

        $this->mock(TelegramService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->atLeast()->once()->andReturn(true);
            $mock->shouldReceive('getFile')->once()->andReturn(['file_path' => 'photos/nf.jpg']);
            $mock->shouldReceive('downloadFile')->once()->andReturn('fake-image-data');
        });

        $this->mock(\App\Services\GeminiService::class, function (MockInterface $mock) use ($valorExato, $dataCompra, $cartao) {
            $mock->shouldReceive('parseReceiptImage')->once()->andReturn([
                'tipo'              => 'nota_fiscal',
                'estabelecimento'   => 'Carrefour Hiper',
                'data_compra'       => "{$dataCompra} 11:30:00",
                'valor_total'       => $valorExato,
                'transacao_destino' => 'cartao',
                'cartao_id'         => $cartao->id,
                'itens'             => [
                    [
                        'nome'           => 'Café Especial',
                        'categoria_item' => 'Bebidas',
                        'quantidade'     => 2,
                        'valor_unitario' => 20.00,
                        'valor_total'    => 40.00,
                    ],
                    [
                        'nome'           => 'Carne Bovina',
                        'categoria_item' => 'Carnes',
                        'quantidade'     => 1,
                        'valor_unitario' => 105.80,
                        'valor_total'    => 105.80,
                    ],
                ],
            ]);
        });

        $response = $this->postJson(route('webhook.telegram'), [
            'update_id' => 10004,
            'message' => [
                'chat'  => ['id' => 12345678],
                'photo' => [
                    ['file_id' => 'p1', 'file_size' => 100],
                    ['file_id' => 'p2_large', 'file_size' => 500],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true, 'processed' => 'nota_fiscal_photo']);

        // Deve existir apenas 1 compra no cartão (substituída, sem duplicata!)
        $this->assertEquals(1, \App\Models\CartaoCompra::count());
        $this->assertEquals(1, \App\Models\NotaFiscal::count());
        $this->assertEquals(2, \App\Models\NotaFiscalItem::count());

        $novaCompra = \App\Models\CartaoCompra::first();
        $this->assertStringContainsString('Carrefour Hiper', $novaCompra->descricao);
        $this->assertEquals($valorExato, (float)$novaCompra->valor_total);

        $nf = \App\Models\NotaFiscal::first();
        $this->assertEquals($novaCompra->id, $nf->cartao_compra_id);
        $this->assertStringContainsString('Substituiu compra prévia', $nf->observacoes);
    }

    public function test_nota_fiscal_replaces_existing_transacao_with_exact_date_and_value(): void
    {
        config(['telegram.webhook_secret' => '']);
        config(['telegram.allowed_chat_id' => '12345678']);
        $user = User::find(1);

        $dataCompra = '2026-10-09';
        $valorExato = 85.50;

        // Despesa prévia lançada nas contas da casa
        $transacaoPrevia = Transacao::create([
            'user_id'      => $user->id,
            'descricao'    => 'Mercadinho do Bairro',
            'valor'        => $valorExato,
            'tipo'         => 'despesa',
            'categoria'    => 'Alimentação',
            'subcategoria' => 'Mercado',
            'data'         => $dataCompra,
        ]);

        $this->assertEquals(1, Transacao::count());

        $this->mock(TelegramService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->atLeast()->once()->andReturn(true);
            $mock->shouldReceive('getFile')->once()->andReturn(['file_path' => 'photos/nf2.jpg']);
            $mock->shouldReceive('downloadFile')->once()->andReturn('fake-image-data');
        });

        $this->mock(\App\Services\GeminiService::class, function (MockInterface $mock) use ($valorExato, $dataCompra) {
            $mock->shouldReceive('parseReceiptImage')->once()->andReturn([
                'tipo'              => 'nota_fiscal',
                'estabelecimento'   => 'Pão de Açúcar',
                'data_compra'       => "{$dataCompra} 14:00:00",
                'valor_total'       => $valorExato,
                'transacao_destino' => 'casa',
                'itens'             => [
                    [
                        'nome'           => 'Queijo Mussarela',
                        'categoria_item' => 'Laticínios',
                        'quantidade'     => 1,
                        'valor_unitario' => 85.50,
                        'valor_total'    => 85.50,
                    ],
                ],
            ]);
        });

        $response = $this->postJson(route('webhook.telegram'), [
            'update_id' => 10005,
            'message' => [
                'chat'  => ['id' => 12345678],
                'photo' => [
                    ['file_id' => 'p_foto', 'file_size' => 200],
                ],
            ],
        ]);

        $response->assertStatus(200);

        // Deve existir apenas 1 transação (substituída, sem duplicata!)
        $this->assertEquals(1, Transacao::count());
        $this->assertEquals(1, \App\Models\NotaFiscal::count());

        $transacaoAtual = Transacao::first();
        $this->assertStringContainsString('Pão de Açúcar', $transacaoAtual->descricao);
        $this->assertEquals($valorExato, (float)$transacaoAtual->valor);

        $nf = \App\Models\NotaFiscal::first();
        $this->assertEquals($transacaoAtual->id, $nf->transacao_id);
    }

    public function test_nota_fiscal_does_not_replace_when_value_or_date_differs(): void
    {
        config(['telegram.webhook_secret' => '']);
        config(['telegram.allowed_chat_id' => '12345678']);
        $user = User::find(1);

        // Despesa prévia com DATA DIFERENTE
        Transacao::create([
            'user_id'      => $user->id,
            'descricao'    => 'Padaria Ontem',
            'valor'        => 50.00,
            'tipo'         => 'despesa',
            'categoria'    => 'Alimentação',
            'data'         => '2026-10-08',
        ]);

        // Despesa prévia com VALOR DIFERENTE na mesma data
        Transacao::create([
            'user_id'      => $user->id,
            'descricao'    => 'Padaria Hoje',
            'valor'        => 55.00,
            'tipo'         => 'despesa',
            'categoria'    => 'Alimentação',
            'data'         => '2026-10-09',
        ]);

        $this->assertEquals(2, Transacao::count());

        $this->mock(TelegramService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')->atLeast()->once()->andReturn(true);
            $mock->shouldReceive('getFile')->once()->andReturn(['file_path' => 'photos/nf3.jpg']);
            $mock->shouldReceive('downloadFile')->once()->andReturn('fake-image-data');
        });

        $this->mock(\App\Services\GeminiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('parseReceiptImage')->once()->andReturn([
                'tipo'              => 'nota_fiscal',
                'estabelecimento'   => 'Padaria Central',
                'data_compra'       => '2026-10-09 08:00:00',
                'valor_total'       => 50.00, // Mesmo valor da de ontem, mas hoje!
                'transacao_destino' => 'casa',
                'itens'             => [],
            ]);
        });

        $response = $this->postJson(route('webhook.telegram'), [
            'update_id' => 10006,
            'message' => [
                'chat'  => ['id' => 12345678],
                'photo' => [
                    ['file_id' => 'p_foto3', 'file_size' => 200],
                ],
            ],
        ]);

        $response->assertStatus(200);

        // Não substituiu nenhuma das anteriores porque data ou valor não bateram exatamente
        $this->assertEquals(3, Transacao::count());
        $this->assertEquals(1, \App\Models\NotaFiscal::count());
    }
}

