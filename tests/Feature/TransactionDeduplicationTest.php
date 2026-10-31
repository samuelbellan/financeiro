<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conta;
use App\Models\Transacao;
use App\Models\User;
use App\Services\TransactionDeduplicationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Conta $conta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->conta = Conta::create([
            'user_id'      => $this->user->id,
            'nome'         => 'Nubank Conta',
            'banco_nome'   => 'Nu Pagamentos',
            'saldo_atual'  => 1000.00,
            'ativo'        => true,
        ]);
    }

    public function test_detects_user_reported_duplicate_examples(): void
    {
        // Par 1 relatado pelo usuário:
        // 18/10/2026 Pix Marcelo (Missoes) Outros - R$ 100,00
        // = 19/10/2026 Pix enviado - - R$ 100,00
        $t1 = Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-18',
            'descricao' => 'Pix Marcelo (Missoes)',
            'categoria' => 'Outros',
            'valor'     => 100.00,
            'tipo'      => 'despesa',
        ]);

        $t2 = Transacao::create([
            'user_id'   => $this->user->id,
            'conta_id'  => $this->conta->id,
            'data'      => '2026-10-19',
            'descricao' => 'Pix enviado',
            'valor'     => 100.00,
            'tipo'      => 'despesa',
            'fitid'     => '20261019001',
        ]);

        // Par 2 relatado pelo usuário:
        // 19/10/2026 Pix enviado open finance - - R$ 18,90
        // = 17/10/2026 Cicclo (Lavanderia 24h) Outros - R$ 18,90 (sábado para segunda)
        $t3 = Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-17',
            'descricao' => 'Cicclo (Lavanderia 24h)',
            'categoria' => 'Outros',
            'valor'     => 18.90,
            'tipo'      => 'despesa',
        ]);

        $t4 = Transacao::create([
            'user_id'   => $this->user->id,
            'conta_id'  => $this->conta->id,
            'data'      => '2026-10-19',
            'descricao' => 'Pix enviado open finance',
            'valor'     => 18.90,
            'tipo'      => 'despesa',
            'fitid'     => '20261019002',
        ]);

        $duplicidades = TransactionDeduplicationService::detectDuplicates($this->user->id, 10, 2026);

        $this->assertCount(2, $duplicidades);

        // Verifica Par 1
        $par100 = collect($duplicidades)->first(fn($p) => $p['t1']['valor'] == 100.00);
        $this->assertNotNull($par100);
        $this->assertEquals(1, $par100['dias_diferenca']);
        $this->assertEquals($t1->id, $par100['sugestao']['manter_id'], 'Deve sugerir manter o lançamento enriquecido Pix Marcelo (Missoes)');
        $this->assertEquals($t2->id, $par100['sugestao']['excluir_id'], 'Deve sugerir excluir o lançamento genérico Pix enviado do extrato');

        // Verifica Par 2
        $par18 = collect($duplicidades)->first(fn($p) => $p['t1']['valor'] == 18.90);
        $this->assertNotNull($par18);
        $this->assertEquals(2, $par18['dias_diferenca']);
        $this->assertEquals($t3->id, $par18['sugestao']['manter_id'], 'Deve sugerir manter o lançamento enriquecido Cicclo (Lavanderia 24h)');
        $this->assertEquals($t4->id, $par18['sugestao']['excluir_id'], 'Deve sugerir excluir o lançamento genérico Pix enviado open finance');
    }

    public function test_does_not_flag_distinct_purchases_with_same_amount(): void
    {
        // Duas compras legítimas no mesmo dia ou dias próximos com o mesmo valor,
        // mas estabelecimentos completamente diferentes e nenhum genérico
        Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-15',
            'descricao' => 'Farmácia Pague Menos',
            'categoria' => 'Saúde',
            'valor'     => 50.00,
            'tipo'      => 'despesa',
        ]);

        Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-16',
            'descricao' => 'Posto Shell Combustíveis',
            'categoria' => 'Transporte',
            'valor'     => 50.00,
            'tipo'      => 'despesa',
        ]);

        $duplicidades = TransactionDeduplicationService::detectDuplicates($this->user->id, 10, 2026);
        $this->assertEmpty($duplicidades, 'Não deve sinalizar compras distintas em estabelecimentos diferentes como duplicatas');
    }

    public function test_merges_transactions_preserving_enriched_data(): void
    {
        $manual = Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-18',
            'descricao' => 'Pix Marcelo (Missoes)',
            'categoria' => 'Outros',
            'valor'     => 100.00,
            'tipo'      => 'despesa',
        ]);

        $extrato = Transacao::create([
            'user_id'            => $this->user->id,
            'conta_id'           => $this->conta->id,
            'data'               => '2026-10-19',
            'descricao'          => 'Pix enviado',
            'valor'              => 100.00,
            'tipo'               => 'despesa',
            'fitid'              => 'FITID_123456',
            'tipo_transacao_ofx' => 'OFX',
            'numero_documento'   => 'DOC9988',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('financas.duplicidades.mesclar'), [
                'manter_id'  => $manual->id,
                'excluir_id' => $extrato->id,
            ]);

        $response->assertOk()
            ->assertJson(['sucesso' => true]);

        // A transação extrato deve ter sido excluída
        $this->assertDatabaseMissing('transacoes', ['id' => $extrato->id]);

        // A transação manual sobreviveu e foi enriquecida com os dados do extrato
        $manual->refresh();
        $this->assertEquals('Pix Marcelo (Missoes)', $manual->descricao);
        $this->assertEquals('Outros', $manual->categoria);
        $this->assertEquals($this->conta->id, $manual->conta_id);
        $this->assertEquals('FITID_123456', $manual->fitid);
        $this->assertEquals('OFX', $manual->tipo_transacao_ofx);
        $this->assertEquals('DOC9988', $manual->numero_documento);
        $this->assertStringContainsString('Mesclado com lançamento duplicado', $manual->memo);
    }

    public function test_merge_all_suggested_duplicates_endpoint(): void
    {
        $t1 = Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-18',
            'descricao' => 'Pix Marcelo (Missoes)',
            'categoria' => 'Outros',
            'valor'     => 100.00,
            'tipo'      => 'despesa',
        ]);

        $t2 = Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-19',
            'descricao' => 'Pix enviado',
            'valor'     => 100.00,
            'tipo'      => 'despesa',
        ]);

        $t3 = Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-17',
            'descricao' => 'Cicclo (Lavanderia 24h)',
            'categoria' => 'Outros',
            'valor'     => 18.90,
            'tipo'      => 'despesa',
        ]);

        $t4 = Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-19',
            'descricao' => 'Pix enviado open finance',
            'valor'     => 18.90,
            'tipo'      => 'despesa',
        ]);

        $this->assertEquals(4, Transacao::where('user_id', $this->user->id)->count());

        $response = $this->actingAs($this->user)
            ->postJson(route('financas.duplicidades.mesclar-todas'), [
                'mes' => 10,
                'ano' => 2026,
            ]);

        $response->assertOk()
            ->assertJson(['sucesso' => true, 'resolvidos' => 2]);

        // Restam apenas 2 transações (as manuais enriquecidas)
        $this->assertEquals(2, Transacao::where('user_id', $this->user->id)->count());
        $this->assertDatabaseHas('transacoes', ['id' => $t1->id]);
        $this->assertDatabaseHas('transacoes', ['id' => $t3->id]);
        $this->assertDatabaseMissing('transacoes', ['id' => $t2->id]);
        $this->assertDatabaseMissing('transacoes', ['id' => $t4->id]);
    }

    public function test_ignore_duplicate_pair(): void
    {
        $t1 = Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-18',
            'descricao' => 'Pix Doação',
            'valor'     => 50.00,
            'tipo'      => 'despesa',
        ]);

        $t2 = Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-19',
            'descricao' => 'Pix enviado',
            'valor'     => 50.00,
            'tipo'      => 'despesa',
        ]);

        $this->assertCount(1, TransactionDeduplicationService::detectDuplicates($this->user->id, 10, 2026));

        $response = $this->actingAs($this->user)
            ->postJson(route('financas.duplicidades.ignorar'), [
                'id_a' => $t1->id,
                'id_b' => $t2->id,
            ]);

        $response->assertOk()
            ->assertJson(['sucesso' => true]);

        // Não deve mais aparecer como duplicata
        $this->assertEmpty(TransactionDeduplicationService::detectDuplicates($this->user->id, 10, 2026));
    }

    public function test_financas_index_view_renders_duplicate_counter_and_tags(): void
    {
        Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-18',
            'descricao' => 'Pix Marcelo (Missoes)',
            'categoria' => 'Outros',
            'valor'     => 100.00,
            'tipo'      => 'despesa',
        ]);

        Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-19',
            'descricao' => 'Pix enviado',
            'valor'     => 100.00,
            'tipo'      => 'despesa',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('financas.index', ['mes' => 10, 'ano' => 2026]));

        $response->assertOk();
        $response->assertSee('Verificar Duplicidades');
        $response->assertSee('duplicidade suspeita identificada');
        $response->assertSee('⚠️ Duplicata');
    }

    public function test_statement_import_automatically_deduplicates_and_enriches_manual_entries(): void
    {
        // Cria previamente os 2 lançamentos manuais no sistema
        $manual1 = Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-18',
            'descricao' => 'Pix Marcelo (Missoes)',
            'categoria' => 'Outros',
            'valor'     => 100.00,
            'tipo'      => 'despesa',
        ]);

        $manual2 = Transacao::create([
            'user_id'   => $this->user->id,
            'data'      => '2026-10-17',
            'descricao' => 'Cicclo (Lavanderia 24h)',
            'categoria' => 'Outros',
            'valor'     => 18.90,
            'tipo'      => 'despesa',
        ]);

        // Simula importação de extrato CSV contendo os lançamentos correspondentes
        $csvContent = "Data;Descricao;Valor\n" .
                      "19/10/2026;Pix enviado;-100,00\n" .
                      "19/10/2026;Pix enviado open finance;-18,90\n";

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('extrato.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(route('financas.importar-extrato'), [
            'extrato'  => $file,
            'conta_id' => $this->conta->id,
        ]);

        $response->assertRedirect();

        // Não deve ter criado novos registros duplicados! Devem existir apenas 2 transações no total
        $this->assertEquals(2, Transacao::where('user_id', $this->user->id)->count());

        $manual1->refresh();
        $manual2->refresh();

        // Preservou descrições e categorias manuais
        $this->assertEquals('Pix Marcelo (Missoes)', $manual1->descricao);
        $this->assertEquals('Outros', $manual1->categoria);
        $this->assertEquals($this->conta->id, $manual1->conta_id);

        $this->assertEquals('Cicclo (Lavanderia 24h)', $manual2->descricao);
        $this->assertEquals('Outros', $manual2->categoria);
        $this->assertEquals($this->conta->id, $manual2->conta_id);
    }
}
