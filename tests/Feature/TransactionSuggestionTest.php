<?php

namespace Tests\Feature;

use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\Categoria;
use App\Models\Transacao;
use App\Models\User;
use App\Services\TransactionSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionSuggestionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_suggestion_endpoint_requires_authentication(): void
    {
        $response = $this->getJson(route('financas.sugestoes-descricao'));
        $response->assertUnauthorized();
    }

    public function test_suggestion_endpoint_returns_suggestions_from_transactions_and_card_purchases(): void
    {
        // 1. Criar transação
        Transacao::create([
            'user_id' => $this->user->id,
            'descricao' => 'Fort Atacadista',
            'valor' => 350.00,
            'tipo' => 'despesa',
            'categoria' => 'Alimentação',
            'subcategoria' => 'Supermercado',
            'data' => now()->toDateString(),
        ]);

        // 2. Criar compra no cartão
        $cartao = Cartao::create([
            'user_id' => $this->user->id,
            'nome' => 'Nubank',
            'cor' => '#8b5cf6',
            'limite' => 5000,
            'dia_fechamento' => 1,
            'dia_vencimento' => 10,
        ]);

        CartaoCompra::create([
            'cartao_id' => $cartao->id,
            'descricao' => 'Posto Ipiranga',
            'valor_total' => 150.00,
            'tipo' => 'avista',
            'numero_parcelas' => 1,
            'categoria' => 'Transporte',
            'data_compra' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('financas.sugestoes-descricao'));

        $response->assertOk();
        $data = $response->json();

        $this->assertCount(2, $data);

        $descricoes = array_column($data, 'descricao');
        $this->assertContains('Fort Atacadista', $descricoes);
        $this->assertContains('Posto Ipiranga', $descricoes);

        $fort = collect($data)->firstWhere('descricao', 'Fort Atacadista');
        $this->assertEquals('Alimentação', $fort['categoria']);
        $this->assertEquals('Supermercado', $fort['subcategoria']);
        $this->assertEquals('despesa', $fort['tipo']);

        $posto = collect($data)->firstWhere('descricao', 'Posto Ipiranga');
        $this->assertEquals('Transporte', $posto['categoria']);
    }

    public function test_suggestion_endpoint_filters_by_query_insensitively(): void
    {
        Transacao::create([
            'user_id' => $this->user->id,
            'descricao' => 'Café Divino',
            'valor' => 25.00,
            'tipo' => 'despesa',
            'categoria' => 'Alimentação',
            'data' => now()->toDateString(),
        ]);

        Transacao::create([
            'user_id' => $this->user->id,
            'descricao' => 'Energia Elétrica',
            'valor' => 200.00,
            'tipo' => 'despesa',
            'categoria' => 'Moradia',
            'data' => now()->toDateString(),
        ]);

        // Busca sem acento "cafe" deve encontrar "Café Divino"
        $response = $this->actingAs($this->user)->getJson(route('financas.sugestoes-descricao', ['q' => 'cafe']));
        $response->assertOk();
        $data = $response->json();

        $this->assertCount(1, $data);
        $this->assertEquals('Café Divino', $data[0]['descricao']);

        // Busca "eletrica" deve encontrar "Energia Elétrica"
        $response2 = $this->actingAs($this->user)->getJson(route('financas.sugestoes-descricao', ['q' => 'eletrica']));
        $response2->assertOk();
        $data2 = $response2->json();

        $this->assertCount(1, $data2);
        $this->assertEquals('Energia Elétrica', $data2[0]['descricao']);
    }

    public function test_suggestion_service_isolates_users(): void
    {
        $otherUser = User::factory()->create();

        Transacao::create([
            'user_id' => $otherUser->id,
            'descricao' => 'Segredo de Outro Usuário',
            'valor' => 100.00,
            'tipo' => 'despesa',
            'data' => now()->toDateString(),
        ]);

        $suggestions = TransactionSuggestionService::getSuggestions($this->user->id);
        $this->assertEmpty($suggestions);

        $otherSuggestions = TransactionSuggestionService::getSuggestions($otherUser->id);
        $this->assertCount(1, $otherSuggestions);
        $this->assertEquals('Segredo de Outro Usuário', $otherSuggestions[0]['descricao']);
    }

    public function test_suggestion_service_sanitizes_category_with_user_categories(): void
    {
        Categoria::create([
            'user_id' => $this->user->id,
            'nome' => 'Educação',
            'tipo' => 'despesa',
            'cor' => '#3b82f6',
        ]);

        // Transação com categoria em maiúsculas sem acento
        Transacao::create([
            'user_id' => $this->user->id,
            'descricao' => 'Curso de Laravel',
            'valor' => 99.00,
            'tipo' => 'despesa',
            'categoria' => 'EDUCACAO',
            'data' => now()->toDateString(),
        ]);

        $suggestions = TransactionSuggestionService::getSuggestions($this->user->id);
        $this->assertCount(1, $suggestions);
        $this->assertEquals('Curso de Laravel', $suggestions[0]['descricao']);
        // Deve mapear para a categoria oficial cadastrada 'Educação'
        $this->assertEquals('Educação', $suggestions[0]['categoria']);
    }
}
