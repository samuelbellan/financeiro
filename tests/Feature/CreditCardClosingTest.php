<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoParcela;
use App\Models\CartaoPrevisao;
use App\Models\User;
use App\Services\CreditCardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditCardClosingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Cartao $cartaoVisa;
    protected Cartao $cartaoNubank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // VISA SX: Fechamento dia 25, Vencimento dia 9 (Vencimento no mês seguinte ao fechamento)
        $this->cartaoVisa = Cartao::create([
            'user_id'        => $this->user->id,
            'nome'           => 'VISA SX',
            'bandeira'       => 'Visa',
            'cor'            => '#1e40af',
            'limite'         => 8000.00,
            'dia_fechamento' => 25,
            'dia_vencimento' => 9,
            'ativo'          => true,
        ]);

        // Nubank: Fechamento dia 5, Vencimento dia 12 (Fechamento e vencimento no mesmo mês)
        $this->cartaoNubank = Cartao::create([
            'user_id'        => $this->user->id,
            'nome'           => 'Nubank',
            'bandeira'       => 'Mastercard',
            'cor'            => '#8b5cf6',
            'limite'         => 5000.00,
            'dia_fechamento' => 5,
            'dia_vencimento' => 12,
            'ativo'          => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_closing_date_calculation_for_different_cycles(): void
    {
        // VISA SX (fechamento 25, vencimento 9): para fatura 11/2026, fecha em 25/10/2026
        $dataVisaNov = CreditCardService::getDataFechamentoFatura($this->cartaoVisa, 11, 2026);
        $this->assertEquals('2026-10-25', $dataVisaNov->format('Y-m-d'));

        // Nubank (fechamento 5, vencimento 12): para fatura 11/2026, fecha em 05/11/2026
        $dataNuNov = CreditCardService::getDataFechamentoFatura($this->cartaoNubank, 11, 2026);
        $this->assertEquals('2026-11-05', $dataNuNov->format('Y-m-d'));
    }

    public function test_automatic_closing_detection_on_current_date(): void
    {
        // Simulando hoje = 31 de Outubro de 2026
        Carbon::setTestNow('2026-10-31 10:00:00');

        // Para a fatura de Novembro/2026 do VISA SX (fechou 25/10/2026): já está FECHADA
        $this->assertTrue(CreditCardService::isFaturaFechada($this->cartaoVisa, 11, 2026));

        // Para a fatura de Dezembro/2026 do VISA SX (fecha 25/11/2026): ainda está ABERTA
        $this->assertFalse(CreditCardService::isFaturaFechada($this->cartaoVisa, 12, 2026));

        // Para a fatura de Novembro/2026 do Nubank (fecha 05/11/2026): em 31/10 ainda está ABERTA
        $this->assertFalse(CreditCardService::isFaturaFechada($this->cartaoNubank, 11, 2026));

        // Avançando o tempo para 06/11/2026: a fatura de Novembro do Nubank agora está FECHADA
        Carbon::setTestNow('2026-11-06 10:00:00');
        $this->assertTrue(CreditCardService::isFaturaFechada($this->cartaoNubank, 11, 2026));
    }

    public function test_manual_override_can_close_or_reopen_invoice(): void
    {
        Carbon::setTestNow('2026-10-31 10:00:00');

        // Nubank 11/2026 está aberto por data
        $this->assertFalse(CreditCardService::isFaturaFechada($this->cartaoNubank, 11, 2026));

        // Usuário fecha manualmente com antecedência
        $registro = CreditCardService::setFaturaFechada($this->cartaoNubank, 11, 2026, true, $this->user->id);
        $this->assertTrue($registro->fechada);
        $this->assertTrue(CreditCardService::isFaturaFechada($this->cartaoNubank, 11, 2026));

        // VISA SX 11/2026 está fechado por data, mas usuário deseja reabrir manualmente para ajustes
        $this->assertTrue(CreditCardService::isFaturaFechada($this->cartaoVisa, 11, 2026));
        CreditCardService::setFaturaFechada($this->cartaoVisa, 11, 2026, false, $this->user->id);
        $this->assertFalse(CreditCardService::isFaturaFechada($this->cartaoVisa, 11, 2026));

        // Alternando sem passar boolean explícito (toggle)
        CreditCardService::setFaturaFechada($this->cartaoVisa, 11, 2026, null, $this->user->id);
        $this->assertTrue(CreditCardService::isFaturaFechada($this->cartaoVisa, 11, 2026));
    }

    public function test_toggle_fechamento_endpoint(): void
    {
        Carbon::setTestNow('2026-10-31 10:00:00');

        // Toggle via JSON request
        $response = $this->actingAs($this->user)
            ->postJson(route('cartoes.fatura.fechamento', $this->cartaoVisa), [
                'mes' => 11,
                'ano' => 2026,
                'fechada' => false,
            ]);

        $response->assertOk()
            ->assertJson([
                'sucesso' => true,
                'fechada' => false,
            ]);

        $this->assertFalse(CreditCardService::isFaturaFechada($this->cartaoVisa, 11, 2026));

        // Outro usuário não pode alterar
        $outroUser = User::factory()->create();
        $responseOutro = $this->actingAs($outroUser)
            ->postJson(route('cartoes.fatura.fechamento', $this->cartaoVisa), [
                'mes' => 11,
                'ano' => 2026,
                'fechada' => true,
            ]);
        $responseOutro->assertForbidden();
    }

    public function test_financas_view_zeroes_remaining_margin_when_invoice_is_closed(): void
    {
        Carbon::setTestNow('2026-10-31 10:00:00');

        // Cria uma compra de R$ 6.357,81 na fatura de 11/2026 do VISA SX
        $compra = CartaoCompra::create([
            'cartao_id' => $this->cartaoVisa->id,
            'descricao' => 'Fatura fechada Visa',
            'categoria' => 'Geral',
            'tipo' => 'avulsa',
            'valor_total' => 6357.81,
            'numero_parcelas' => 1,
            'data_compra' => '2026-10-15',
        ]);
        CartaoParcela::create([
            'cartao_compra_id' => $compra->id,
            'numero_parcela' => 1,
            'valor_parcela' => 6357.81,
            'data_vencimento' => '2026-11-09',
            'status' => 'aberta',
        ]);

        // Cria uma previsão de cartão de R$ 6.499,99 (margem de 142,18 se estivesse aberta)
        CartaoPrevisao::create([
            'cartao_id' => $this->cartaoVisa->id,
            'categoria' => 'Geral',
            'valor_previsto' => 6499.99,
            'mes' => 11,
            'ano' => 2026,
        ]);

        // Acessa finanças de Novembro/2026
        $response = $this->actingAs($this->user)
            ->get(route('financas.index', ['mes' => 11, 'ano' => 2026]));

        $response->assertOk();

        $cardsPlanejamento = $response->viewData('cardsPlanejamento');
        $this->assertNotEmpty($cardsPlanejamento);

        $cardVisaPlanejado = collect($cardsPlanejamento)->firstWhere('cartao.id', $this->cartaoVisa->id);
        $this->assertNotNull($cardVisaPlanejado);

        // Como a fatura está fechada (dia 25/10 passou):
        // 1. is_fechada deve ser true
        // 2. margem_restante deve ser R$ 0,00
        // 3. total_previsto deve ser estritamente o valor da fatura (6.357,81) e não 6.499,99
        $this->assertTrue($cardVisaPlanejado->is_fechada);
        $this->assertEquals(0.0, $cardVisaPlanejado->margem_restante);
        $this->assertEquals(6357.81, $cardVisaPlanejado->total_previsto);

        // A view deve renderizar o badge 'Fechada' e a informação de fechamento
        $response->assertSee('Fechada');
        $response->assertSee('6.357,81');
    }
}
