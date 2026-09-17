<?php

namespace Tests\Feature;

use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartaoCompraTest extends TestCase
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
            'nome' => 'Cartão Nubank',
            'bandeira' => 'Mastercard',
            'cor' => '#8b5cf6',
            'limite' => 5000.00,
            'dia_fechamento' => 5,
            'dia_vencimento' => 12,
            'ativo' => true,
        ]);
    }

    public function test_user_can_create_purchase_by_total_value(): void
    {
        $response = $this->actingAs($this->user)->post(route('cartoes.compras.store'), [
            'cartao_id' => $this->cartao->id,
            'descricao' => 'Notebook Dell',
            'valor_total' => 3000.00,
            'tipo' => 'parcelada',
            'numero_parcelas' => 10,
            'data_compra' => now()->toDateString(),
            'categoria' => 'Eletrônicos',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cartao_compras', [
            'cartao_id' => $this->cartao->id,
            'descricao' => 'Notebook Dell',
            'valor_total' => 3000.00,
            'numero_parcelas' => 10,
        ]);

        $compra = CartaoCompra::where('descricao', 'Notebook Dell')->first();
        $this->assertNotNull($compra);
        $this->assertCount(10, $compra->parcelas);

        foreach ($compra->parcelas as $parcela) {
            $this->assertEquals(300.00, (float)$parcela->valor_parcela);
        }
    }

    public function test_user_can_create_purchase_by_installment_value_and_quantity(): void
    {
        // Usuário informa apenas valor_parcela (119.90) e numero_parcelas (10)
        $response = $this->actingAs($this->user)->post(route('cartoes.compras.store'), [
            'cartao_id' => $this->cartao->id,
            'descricao' => 'Curso de Programação',
            'valor_parcela' => 119.90,
            'tipo' => 'parcelada',
            'numero_parcelas' => 10,
            'data_compra' => now()->toDateString(),
            'categoria' => 'Educação',
        ]);

        $response->assertSessionHasNoErrors();

        // 119.90 * 10 = 1199.00
        $this->assertDatabaseHas('cartao_compras', [
            'cartao_id' => $this->cartao->id,
            'descricao' => 'Curso de Programação',
            'valor_total' => 1199.00,
            'numero_parcelas' => 10,
        ]);

        $compra = CartaoCompra::where('descricao', 'Curso de Programação')->first();
        $this->assertNotNull($compra);
        $this->assertCount(10, $compra->parcelas);

        foreach ($compra->parcelas as $parcela) {
            $this->assertEquals(119.90, (float)$parcela->valor_parcela);
        }
    }

    public function test_user_can_create_purchase_with_json_request(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('cartoes.compras.store'), [
            'cartao_id' => $this->cartao->id,
            'descricao' => 'Tênis de Corrida',
            'valor_parcela' => '89,90',
            'tipo' => 'parcelada',
            'numero_parcelas' => 5,
            'data_compra' => now()->toDateString(),
            'categoria' => 'Esportes',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Compra registrada com sucesso!',
            'compra' => [
                'descricao' => 'Tênis de Corrida',
                'valor_total' => 449.50,
                'tipo' => 'parcelada',
            ],
        ]);

        $compra = CartaoCompra::where('descricao', 'Tênis de Corrida')->first();
        $this->assertNotNull($compra);
        $this->assertCount(5, $compra->parcelas);
        $this->assertEquals(89.90, (float)$compra->parcelas->first()->valor_parcela);
    }
}
