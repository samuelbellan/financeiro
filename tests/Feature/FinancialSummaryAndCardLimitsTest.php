<?php

namespace Tests\Feature;

use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoParcela;
use App\Models\CartaoPrevisao;
use App\Models\Transacao;
use App\Models\TransacaoPrevisao;
use App\Models\User;
use App\Services\FinancialSummaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialSummaryAndCardLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Cartao $cartao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->cartao = Cartao::create([
            'user_id' => $this->user->id,
            'nome' => 'Inter Black',
            'bandeira' => 'Mastercard',
            'cor' => '#000000',
            'limite' => 10000.00,
            'dia_fechamento' => 10,
            'dia_vencimento' => 20,
            'ativo' => true,
        ]);
    }

    public function test_user_can_create_card_previsao_or_limit(): void
    {
        $response = $this->actingAs($this->user)->post(route('cartoes.previsoes.store'), [
            'cartao_id' => $this->cartao->id,
            'categoria' => 'Alimentação',
            'valor_previsto' => '1.500,00',
            'mes' => 10,
            'ano' => 2026,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cartao_previsoes', [
            'cartao_id' => $this->cartao->id,
            'categoria' => 'Alimentação',
            'valor_previsto' => 1500.00,
            'mes' => 10,
            'ano' => 2026,
        ]);
    }

    public function test_whatsapp_summary_endpoint_generates_correct_data(): void
    {
        // 1. Saldo em Conta Corrente
        Transacao::create([
            'user_id' => $this->user->id,
            'descricao' => 'Salário',
            'valor' => 5000.00,
            'tipo' => 'receita',
            'data' => '2026-10-05',
        ]);

        Transacao::create([
            'user_id' => $this->user->id,
            'descricao' => 'Aluguel Pago',
            'valor' => 1200.00,
            'tipo' => 'despesa',
            'categoria' => 'Moradia',
            'data' => '2026-10-08',
        ]);

        // Conta prevista que ainda não foi paga totalmente
        TransacaoPrevisao::create([
            'user_id' => $this->user->id,
            'categoria' => 'Energia',
            'valor_previsto' => 300.00,
            'tipo' => 'despesa',
            'mes' => 10,
            'ano' => 2026,
        ]);

        // 2. Compras no Cartão
        // Compra à vista
        $compraVista = CartaoCompra::create([
            'cartao_id' => $this->cartao->id,
            'descricao' => 'Supermercado Fort',
            'valor_total' => 450.00,
            'tipo' => 'avista',
            'categoria' => 'Alimentação',
            'data_compra' => '2026-10-02',
            'numero_parcelas' => 1,
        ]);
        CartaoParcela::create([
            'cartao_compra_id' => $compraVista->id,
            'numero_parcela' => 1,
            'valor_parcela' => 450.00,
            'data_vencimento' => '2026-10-20',
            'status' => 'aberta',
        ]);

        // Compra parcelada (a prazo)
        $compraPrazo = CartaoCompra::create([
            'cartao_id' => $this->cartao->id,
            'descricao' => 'Celular Samsung',
            'valor_total' => 1000.00,
            'tipo' => 'parcelada',
            'categoria' => 'Eletrônicos',
            'data_compra' => '2026-10-01',
            'numero_parcelas' => 5,
        ]);
        CartaoParcela::create([
            'cartao_compra_id' => $compraPrazo->id,
            'numero_parcela' => 1,
            'valor_parcela' => 200.00,
            'data_vencimento' => '2026-10-20',
            'status' => 'aberta',
        ]);

        // Limite para Alimentação
        CartaoPrevisao::create([
            'cartao_id' => $this->cartao->id,
            'categoria' => 'Alimentação',
            'valor_previsto' => 600.00,
            'mes' => 10,
            'ano' => 2026,
        ]);

        // Chamada à rota como JSON
        $response = $this->actingAs($this->user)->getJson(route('financas.whatsapp.resumo', [
            'mes' => 10,
            'ano' => 2026,
        ]));

        $response->assertOk();
        $data = $response->json();

        // Saldo CC Atual = 5000 - 1200 = 3800
        $this->assertEquals(3800.00, $data['saldo_cc']);
        $this->assertEquals(5000.00, $data['entradas_realizadas']);
        $this->assertEquals(1200.00, $data['saidas_realizadas']);
        
        // Saídas que faltam pagar: Energia (300) + Fatura aberta (450 + 200 = 650) + Margem de cartão restante (600 - 450 = 150) = 1100
        $this->assertEquals(1100.00, $data['saidas_faltam']);
        $this->assertEquals(150.00, $data['cartao_previsoes_restante']);
        $this->assertEquals(650.00, $data['faturas_abertas']);
        $this->assertEquals(300.00, $data['saidas_previsoes_faltam']);

        // 1º Resultado Líquido do Mês: (Entradas Realizadas: 5000 + Faltam: 0) - (Saídas Realizadas: 1200 + Faltam: 1100) = 5000 - 2300 = 2700
        $this->assertEquals(2700.00, $data['resultado_liquido_mes']);

        // 2º Saldo Bancário Final Previsto: Saldo CC Atual (3800) + Entradas que Faltam (0) - Saídas que Faltam (1100) = 2700
        $this->assertEquals(2700.00, $data['saldo_final_conta']);

        // Cartão: à vista = 450, a prazo = 200
        $this->assertEquals(450.00, $data['total_a_vista']);
        $this->assertEquals(200.00, $data['total_a_prazo']);

        // Limite de categoria: Alimentação gasto 450 de 600, falta 150
        $this->assertCount(1, $data['limites_categoria']);
        $this->assertEquals(150.00, $data['limites_categoria'][0]['falta']);

        // Dicas geradas e Contatos
        $this->assertNotEmpty($data['dicas']);
        $this->assertStringContainsString('5567984376473', $data['whatsapp_urls']['5567984376473']);
        $this->assertStringContainsString('5567984456378', $data['whatsapp_urls']['5567984456378']);
        $this->assertNotEmpty($data['pdf_url']);
        $this->assertStringContainsString('RELATÓRIO DETALHADO EM PDF', $data['whatsapp_text']);
        $this->assertStringContainsString('CONTA CORRENTE', $data['whatsapp_text']);
        $this->assertStringContainsString('Resultado Líquido do Mês', $data['whatsapp_text']);
        $this->assertStringContainsString('Saldo Bancário Final Previsto', $data['whatsapp_text']);
        $this->assertStringContainsString('CARTÃO DE CRÉDITO', $data['whatsapp_text']);
    }

    public function test_card_previsao_influences_checking_account_dashboard(): void
    {
        // Previsão na CC de Despesa fixa: R$ 500
        TransacaoPrevisao::create([
            'user_id' => $this->user->id,
            'categoria' => 'Internet',
            'valor_previsto' => 500.00,
            'tipo' => 'despesa',
            'mes' => 10,
            'ano' => 2026,
        ]);

        // Previsão no Cartão de Crédito de Alimentação: R$ 1.000
        CartaoPrevisao::create([
            'cartao_id' => $this->cartao->id,
            'categoria' => 'Alimentação',
            'valor_previsto' => 1000.00,
            'mes' => 10,
            'ano' => 2026,
        ]);

        // Compra no Cartão de R$ 300 em Alimentação
        $compra = CartaoCompra::create([
            'cartao_id' => $this->cartao->id,
            'descricao' => 'Mercado',
            'valor_total' => 300.00,
            'tipo' => 'avista',
            'categoria' => 'Alimentação',
            'data_compra' => '2026-10-05',
            'numero_parcelas' => 1,
        ]);
        CartaoParcela::create([
            'cartao_compra_id' => $compra->id,
            'numero_parcela' => 1,
            'valor_parcela' => 300.00,
            'data_vencimento' => '2026-10-20',
            'status' => 'aberta',
        ]);

        // Acessa o dashboard da Conta Corrente
        $response = $this->actingAs($this->user)->get(route('financas.index', [
            'mes' => 10,
            'ano' => 2026,
        ]));

        $response->assertOk();
        // Total Previsto de Despesa deve ser: 500 (CC) + 300 (Fatura Cartão) + 700 (Margem Restante Cartão) = 1.500
        $response->assertViewHas('totalPrevistoDespesa', 1500.00);
        $response->assertViewHas('totalCartaoPrevisoesRestante', 700.00);

        // Verifica que o cartão aparece no planejamento com o total previsto de 1000
        $cardsPlanejamento = $response->viewData('cardsPlanejamento');
        $this->assertNotEmpty($cardsPlanejamento);
        $this->assertEquals(1000.00, $cardsPlanejamento[0]->total_previsto);
        $this->assertEquals(300.00, $cardsPlanejamento[0]->fatura_valor);
        $this->assertEquals(700.00, $cardsPlanejamento[0]->margem_restante);
    }

    public function test_card_previsao_abates_existing_invoice_parcelas_from_previous_months(): void
    {
        // Compra feita em OUTUBRO (ou parcelada), mas cuja parcela vence em NOVEMBRO
        $compraOutubro = CartaoCompra::create([
            'cartao_id' => $this->cartao->id,
            'descricao' => 'Fort Atacadista',
            'valor_total' => 800.00,
            'tipo' => 'avista',
            'categoria' => 'Alimentação',
            'data_compra' => '2026-10-25',
            'numero_parcelas' => 1,
        ]);
        CartaoParcela::create([
            'cartao_compra_id' => $compraOutubro->id,
            'numero_parcela' => 1,
            'valor_parcela' => 800.00,
            'data_vencimento' => '2026-11-20',
            'status' => 'aberta',
        ]);

        // Previsão lançada para NOVEMBRO de Alimentação: R$ 2.000,00
        CartaoPrevisao::create([
            'cartao_id' => $this->cartao->id,
            'categoria' => 'Alimentação',
            'valor_previsto' => 2000.00,
            'mes' => 11,
            'ano' => 2026,
        ]);

        // 1. Tela de Cartões em Novembro deve abater os 800 já lançados na fatura de novembro
        $responseCartoes = $this->actingAs($this->user)->get(route('cartoes.index', [
            'mes' => 11,
            'ano' => 2026,
        ]));
        $responseCartoes->assertOk();
        $previsoesCartao = $responseCartoes->viewData('previsoes');
        $this->assertCount(1, $previsoesCartao);
        $this->assertEquals(800.00, $previsoesCartao[0]->consumo_real);
        $this->assertEquals(1200.00, $previsoesCartao[0]->restante);

        // 2. Tela de Conta Corrente em Novembro deve abater: Fatura (800) + Margem Restante (1200) = 2000
        $responseFinancas = $this->actingAs($this->user)->get(route('financas.index', [
            'mes' => 11,
            'ano' => 2026,
        ]));
        $responseFinancas->assertOk();
        $responseFinancas->assertViewHas('totalPrevistoDespesa', 2000.00);
        $responseFinancas->assertViewHas('totalCartaoPrevisoesRestante', 1200.00);

        $cardsPlanejamento = $responseFinancas->viewData('cardsPlanejamento');
        $this->assertEquals(2000.00, $cardsPlanejamento[0]->total_previsto);
        $this->assertEquals(800.00, $cardsPlanejamento[0]->fatura_valor);
        $this->assertEquals(1200.00, $cardsPlanejamento[0]->margem_restante);
    }

    public function test_export_resumo_financeiro_pdf_generates_valid_pdf(): void
    {
        $response = $this->actingAs($this->user)->get(route('financas.relatorio.pdf', [
            'mes' => 10,
            'ano' => 2026,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_signed_url_allows_guest_to_download_resumo_financeiro_pdf(): void
    {
        $signedUrl = \Illuminate\Support\Facades\URL::signedRoute('financas.relatorio.pdf', [
            'mes' => 10,
            'ano' => 2026,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get($signedUrl);
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }
}
