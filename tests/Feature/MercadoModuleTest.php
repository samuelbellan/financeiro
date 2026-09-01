<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\NotaFiscal;
use App\Models\NotaFiscalItem;
use App\Models\Cartao;
use App\Models\Transacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MercadoModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_mercado_page_requires_authentication(): void
    {
        $response = $this->get('/financas/mercado');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_mercado_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/financas/mercado');

        $response->assertStatus(200);
        $response->assertSee('Gastos de Supermercado');
        $response->assertSee('Total Gasto no Período');
    }

    public function test_mercado_page_renders_receipts_and_items(): void
    {
        $user = User::factory()->create();

        $nf = NotaFiscal::create([
            'user_id'         => $user->id,
            'estabelecimento' => 'Hipermercado Teste ABC',
            'data_compra'     => now(),
            'valor_total'     => 120.50,
            'forma_pagamento' => 'casa',
            'observacoes'     => 'Teste Unitário',
        ]);

        $item1 = NotaFiscalItem::create([
            'user_id'         => $user->id,
            'nota_fiscal_id'  => $nf->id,
            'estabelecimento' => 'Hipermercado Teste ABC',
            'data_compra'     => now(),
            'nome_item'       => 'Contra Filé 1kg',
            'categoria_item'  => 'Carnes',
            'quantidade'      => 1,
            'valor_unitario'  => 80.50,
            'valor_total'     => 80.50,
        ]);

        $item2 = NotaFiscalItem::create([
            'user_id'         => $user->id,
            'nota_fiscal_id'  => $nf->id,
            'estabelecimento' => 'Hipermercado Teste ABC',
            'data_compra'     => now(),
            'nome_item'       => 'Detergente Neutro',
            'categoria_item'  => 'Limpeza',
            'quantidade'      => 4,
            'valor_unitario'  => 10.00,
            'valor_total'     => 40.00,
        ]);

        $response = $this->actingAs($user)->get('/financas/mercado');

        $response->assertStatus(200);
        $response->assertSee('Hipermercado Teste ABC');
        $response->assertSee('Contra Filé 1kg');
        $response->assertSee('Detergente Neutro');
        $response->assertSee('120,50');
    }

    public function test_user_can_delete_nota_fiscal(): void
    {
        $user = User::factory()->create();

        $nf = NotaFiscal::create([
            'user_id'         => $user->id,
            'estabelecimento' => 'Mercado Para Deletar',
            'data_compra'     => now(),
            'valor_total'     => 50.00,
        ]);

        $response = $this->actingAs($user)->delete("/financas/mercado/notas/{$nf->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('notas_fiscais', ['id' => $nf->id]);
    }

    public function test_user_can_delete_single_item_and_recalculate_total(): void
    {
        $user = User::factory()->create();

        $nf = NotaFiscal::create([
            'user_id'         => $user->id,
            'estabelecimento' => 'Mercado Teste Itens',
            'data_compra'     => now(),
            'valor_total'     => 70.00,
        ]);

        $item1 = NotaFiscalItem::create([
            'user_id'         => $user->id,
            'nota_fiscal_id'  => $nf->id,
            'nome_item'       => 'Item A',
            'quantidade'      => 1,
            'valor_total'     => 40.00,
        ]);

        $item2 = NotaFiscalItem::create([
            'user_id'         => $user->id,
            'nota_fiscal_id'  => $nf->id,
            'nome_item'       => 'Item B',
            'quantidade'      => 1,
            'valor_total'     => 30.00,
        ]);

        $response = $this->actingAs($user)->deleteJson("/financas/mercado/itens/{$item2->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('nota_fiscal_itens', ['id' => $item2->id]);
        $this->assertEquals(40.00, $nf->fresh()->valor_total);
    }
}

