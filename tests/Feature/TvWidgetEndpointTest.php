<?php

namespace Tests\Feature;

use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoParcela;
use App\Models\Transacao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TvWidgetEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.tv_widget.token' => 'test_tv_token_xyz']);
        config(['telegram.user_id' => null]);

        $this->user = User::factory()->create();
    }

    public function test_rejects_unauthorized_request_when_token_is_invalid(): void
    {
        $response = $this->getJson('/api/tv/widget-resumo?token=wrong_token');

        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Não autorizado',
        ]);
    }

    public function test_accepts_valid_token_via_query_string(): void
    {
        $response = $this->getJson('/api/tv/widget-resumo?token=test_tv_token_xyz');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'mes',
            'ano',
            'mes_extenso',
            'saldo_cc',
            'entradas_realizadas',
            'entradas_faltam',
            'total_entradas',
            'saidas_realizadas',
            'saidas_faltam',
            'total_saidas',
            'faturas_abertas',
            'resultado_liquido_mes',
            'saldo_final_previsto',
            'dicas',
            'atualizado_em',
        ]);
    }

    public function test_accepts_valid_token_via_header(): void
    {
        $response = $this->getJson('/api/tv/widget-resumo', [
            'X-TV-Token' => 'test_tv_token_xyz',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('success', $response->json('status'));
    }

    public function test_computes_accurate_financial_metrics_for_tv(): void
    {
        $now = Carbon::now();
        $mes = $now->month;
        $ano = $now->year;

        // Cria transações na conta corrente
        Transacao::create([
            'user_id' => $this->user->id,
            'tipo' => 'receita',
            'descricao' => 'Salário',
            'valor' => 5000.00,
            'data' => $now->copy()->day(5)->toDateString(),
            'categoria' => 'Rendimentos',
        ]);

        Transacao::create([
            'user_id' => $this->user->id,
            'tipo' => 'despesa',
            'descricao' => 'Aluguel',
            'valor' => 1500.00,
            'data' => $now->copy()->day(10)->toDateString(),
            'categoria' => 'Moradia',
        ]);

        // Cria fatura de cartão aberta
        $cartao = Cartao::create([
            'user_id' => $this->user->id,
            'nome' => 'Nubank Black',
            'bandeira' => 'Mastercard',
            'cor' => '#820ad1',
            'limite' => 10000.00,
            'dia_fechamento' => 1,
            'dia_vencimento' => 15,
            'ativo' => true,
        ]);

        $compra = CartaoCompra::create([
            'cartao_id' => $cartao->id,
            'descricao' => 'Supermercado',
            'valor_total' => 600.00,
            'numero_parcelas' => 1,
            'data_compra' => $now->copy()->day(2)->toDateString(),
            'categoria' => 'Alimentação',
            'tipo' => 'avista',
        ]);

        CartaoParcela::create([
            'cartao_compra_id' => $compra->id,
            'numero_parcela' => 1,
            'valor_parcela' => 600.00,
            'data_vencimento' => $now->copy()->day(15)->toDateString(),
            'status' => 'aberta',
        ]);

        $response = $this->getJson("/api/tv/widget-resumo?token=test_tv_token_xyz&mes={$mes}&ano={$ano}");

        $response->assertStatus(200);
        $data = $response->json();

        // Saldo CC = 5000 - 1500 = 3500
        $this->assertEquals(3500.00, $data['saldo_cc']);
        $this->assertEquals(5000.00, $data['entradas_realizadas']);
        $this->assertEquals(1500.00, $data['saidas_realizadas']);
        $this->assertEquals(600.00, $data['faturas_abertas']);
        $this->assertIsArray($data['dicas']);
    }

    public function test_tv_control_ui_loads_successfully(): void
    {
        $response = $this->get('/tv');
        $response->assertStatus(200);
        $response->assertSee('Controle da TV Philco');
    }

    public function test_tv_action_rejects_empty_url(): void
    {
        $response = $this->postJson('/api/tv/action', ['type' => 'open_url', 'url' => '']);
        $response->assertStatus(422);
    }

    public function test_tv_action_rejects_invalid_key(): void
    {
        $response = $this->postJson('/api/tv/action', ['type' => 'key', 'key' => 'invalid_key']);
        $response->assertStatus(422);
    }
}
