<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoParcela;
use App\Models\CartaoPrevisao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditCardDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Cartao $cartaoAtivo;
    protected Cartao $cartaoDesabilitado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->cartaoAtivo = Cartao::create([
            'user_id'        => $this->user->id,
            'nome'           => 'Cartão Ativo',
            'bandeira'       => 'Mastercard',
            'cor'            => '#3b82f6',
            'limite'         => 5000.00,
            'dia_fechamento' => 10,
            'dia_vencimento' => 20,
            'ativo'          => true,
        ]);

        $this->cartaoDesabilitado = Cartao::create([
            'user_id'        => $this->user->id,
            'nome'           => 'Cartão Desabilitado',
            'bandeira'       => 'Visa',
            'cor'            => '#64748b',
            'limite'         => 3000.00,
            'dia_fechamento' => 5,
            'dia_vencimento' => 15,
            'ativo'          => false,
        ]);
    }

    public function test_cannot_delete_active_credit_card(): void
    {
        $response = $this->actingAs($this->user)
            ->delete(route('cartoes.destroy', $this->cartaoAtivo));

        $response->assertRedirect();
        $response->assertSessionHas('error', "O cartão \"Cartão Ativo\" precisa ser desabilitado antes de ser excluído.");

        // Cartão ativo continua existindo no banco
        $this->assertDatabaseHas('cartoes', ['id' => $this->cartaoAtivo->id]);
    }

    public function test_can_delete_credit_card_after_disabled(): void
    {
        // Cria compras e parcelas vinculadas para testar cascata
        $compra = CartaoCompra::create([
            'cartao_id'       => $this->cartaoDesabilitado->id,
            'descricao'       => 'Compra Antiga',
            'categoria'       => 'Outros',
            'valor_total'     => 150.00,
            'tipo'            => 'avista',
            'numero_parcelas' => 1,
            'data_compra'     => '2026-09-10',
        ]);

        CartaoParcela::create([
            'cartao_compra_id' => $compra->id,
            'numero_parcela'   => 1,
            'valor_parcela'    => 150.00,
            'data_vencimento'  => '2026-09-15',
            'status'           => 'paga',
        ]);

        CartaoPrevisao::create([
            'cartao_id'      => $this->cartaoDesabilitado->id,
            'categoria'      => 'Geral',
            'valor_previsto' => 500.00,
            'mes'            => 10,
            'ano'            => 2026,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('cartoes.destroy', $this->cartaoDesabilitado));

        $response->assertRedirect();
        $response->assertSessionHas('success', "Cartão \"Cartão Desabilitado\" excluído com sucesso!");

        // Cartão e seus dependentes foram excluídos
        $this->assertDatabaseMissing('cartoes', ['id' => $this->cartaoDesabilitado->id]);
        $this->assertDatabaseMissing('cartao_compras', ['id' => $compra->id]);
        $this->assertDatabaseMissing('cartao_previsoes', ['cartao_id' => $this->cartaoDesabilitado->id]);
    }

    public function test_ui_shows_delete_button_only_for_disabled_card(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('cartoes.index', ['mes' => 10, 'ano' => 2026]));

        $response->assertOk();

        // Para o cartão desabilitado, deve renderizar o botão de excluir
        $response->assertSee('Excluir Cartão Desabilitado');

        // Um usuário não autorizado não pode excluir
        $outroUser = User::factory()->create();
        $responseOutro = $this->actingAs($outroUser)
            ->delete(route('cartoes.destroy', $this->cartaoDesabilitado));
        $responseOutro->assertForbidden();
    }
}
