<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoParcela;
use App\Models\User;
use App\Services\CreditCardReconciliationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CreditCardReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Cartao $cartao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->cartao = Cartao::create([
            'user_id'        => $this->user->id,
            'nome'           => 'Cartão Nubank',
            'bandeira'       => 'Mastercard',
            'cor'            => '#8b5cf6',
            'limite'         => 6000.00,
            'dia_fechamento' => 5,
            'dia_vencimento' => 12,
            'ativo'          => true,
        ]);
    }

    public function test_reconciliation_service_correctly_categorizes_matches_divergences_and_credits(): void
    {
        $mes = 10;
        $ano = 2026;

        // 1. Lançamento no sistema que deve bater 100% com a fatura (Valor 150.00, Supermercado Dia)
        $compra1 = CartaoCompra::create([
            'cartao_id'       => $this->cartao->id,
            'descricao'       => 'Supermercado Dia',
            'valor_total'     => 150.00,
            'tipo'            => 'avista',
            'numero_parcelas' => 1,
            'categoria'       => 'Mercado',
            'data_compra'     => '2026-09-28',
        ]);
        CartaoParcela::create([
            'cartao_compra_id' => $compra1->id,
            'numero_parcela'   => 1,
            'valor_parcela'    => 150.00,
            'data_vencimento'  => '2026-10-12',
            'status'           => 'aberta',
        ]);

        // 2. Lançamento no sistema com divergência de valor (Sistema: 50.00, Fatura: 55.90)
        $compra2 = CartaoCompra::create([
            'cartao_id'       => $this->cartao->id,
            'descricao'       => 'Restaurante Sabor',
            'valor_total'     => 50.00,
            'tipo'            => 'avista',
            'numero_parcelas' => 1,
            'categoria'       => 'Alimentação',
            'data_compra'     => '2026-10-01',
        ]);
        CartaoParcela::create([
            'cartao_compra_id' => $compra2->id,
            'numero_parcela'   => 1,
            'valor_parcela'    => 50.00,
            'data_vencimento'  => '2026-10-12',
            'status'           => 'aberta',
        ]);

        // 3. Lançamento no sistema que SOBRA (está no sistema, mas não veio na fatura fechada)
        $compra3 = CartaoCompra::create([
            'cartao_id'       => $this->cartao->id,
            'descricao'       => 'Compra Cancelada Loja X',
            'valor_total'     => 80.00,
            'tipo'            => 'avista',
            'numero_parcelas' => 1,
            'categoria'       => 'Outros',
            'data_compra'     => '2026-10-02',
        ]);
        CartaoParcela::create([
            'cartao_compra_id' => $compra3->id,
            'numero_parcela'   => 1,
            'valor_parcela'    => 80.00,
            'data_vencimento'  => '2026-10-12',
            'status'           => 'aberta',
        ]);

        // Criar conteúdo CSV simulando a fatura do banco
        // Contém:
        // - PAG*Supermercado Dia (150.00) -> deve conciliar com compra 1
        // - Restaurante Sabor (55.90) -> deve acusar divergência com compra 2
        // - Farmácia Central (90.00) -> deve acusar FALTANDO no sistema
        // - PAGAMENTO RECEBIDO (-1000.00) -> deve ser separado em pagamentos/estornos
        $csvContent = implode("\n", [
            'Data;Estabelecimento;Valor',
            '28/09/2026;PAG*Supermercado Dia;150,00',
            '01/10/2026;Restaurante Sabor;55,90',
            '03/10/2026;Farmácia Central;90,00',
            '05/10/2026;PAGAMENTO RECEBIDO;-1000,00',
        ]);

        $file = UploadedFile::fake()->createWithContent('fatura_outubro.csv', $csvContent);

        $service = new CreditCardReconciliationService();
        $resultado = $service->reconcile($file, $this->cartao, $mes, $ano);

        $this->assertTrue($resultado['sucesso']);
        $this->assertEquals(1, $resultado['contadores']['conciliados']);
        $this->assertEquals(1, $resultado['contadores']['divergencias_valor']);
        $this->assertEquals(1, $resultado['contadores']['faltando_no_sistema']);
        $this->assertEquals(1, $resultado['contadores']['sobrando_no_sistema']);
        $this->assertEquals(1, $resultado['contadores']['pagamentos_estornos']);

        // Verificar item conciliado
        $conciliado = $resultado['grupos']['conciliados'][0];
        $this->assertEquals(150.00, $conciliado['fatura_item']['valor']);
        $this->assertEquals('Supermercado Dia', $conciliado['sistema_item']['descricao']);

        // Verificar divergência
        $div = $resultado['grupos']['divergencias_valor'][0];
        $this->assertEquals(55.90, $div['valor_fatura']);
        $this->assertEquals(50.00, $div['valor_sistema']);
        $this->assertEquals(5.90, round($div['diferenca'], 2));

        // Verificar faltando e sobrando
        $this->assertEquals('Farmácia Central', $resultado['grupos']['faltando_no_sistema'][0]['descricao']);
        $this->assertEquals('Compra Cancelada Loja X', $resultado['grupos']['sobrando_no_sistema'][0]['descricao']);

        // Verificar totais: fatura despesas (150 + 55.90 + 90 = 295.90) vs sistema (150 + 50 + 80 = 280.00)
        $this->assertEquals(295.90, $resultado['totais']['fatura_despesas']);
        $this->assertEquals(280.00, $resultado['totais']['sistema']);
        $this->assertEquals(15.90, round($resultado['totais']['diferenca'], 2));
    }

    public function test_cent_differences_are_reconciled_with_tolerance_when_installments_match(): void
    {
        // Sistema: 65,93 (3/3) com nome genérico; fatura: DROGASIL 65,92 03/03 -> deve conciliar por tolerância
        $this->criarCompraParcelada('Remedio', '2026-08-07', 3, 65.93, 3, '2026-10-12');
        // Sistema: 50,00 sem relação nenhuma (nome, data e parcela diferentes) -> não pode conciliar com 49,95
        $this->criarCompraParcelada('Academia', '2026-06-01', 1, 50.00, 1, '2026-10-12', 'avista');

        $csv = implode("\n", [
            'Data;Estabelecimento;Valor',
            '08/08/2026;DROGASIL 2565 03/03;65,92',
            '20/09/2026;Loja Qualquer;49,95',
        ]);

        $resultado = (new CreditCardReconciliationService())->reconcile(
            UploadedFile::fake()->createWithContent('fatura.csv', $csv), $this->cartao, 10, 2026
        );

        $this->assertEquals(1, $resultado['contadores']['conciliados']);
        $conciliado = $resultado['grupos']['conciliados'][0];
        $this->assertEquals('tolerancia', $conciliado['status']);
        $this->assertEquals('Remedio', $conciliado['sistema_item']['descricao']);
        $this->assertEquals(-0.01, $conciliado['diferenca_valor']);

        $this->assertEquals('Loja Qualquer', $resultado['grupos']['faltando_no_sistema'][0]['descricao']);
        $this->assertEquals('Academia', $resultado['grupos']['sobrando_no_sistema'][0]['descricao']);
    }

    public function test_installment_registered_midway_matches_by_remaining_installments(): void
    {
        // Compra original de 12x cadastrada no sistema só com as 7 parcelas restantes, com data do cadastro
        $this->criarCompraParcelada('PG *TEC CONCURSOS', '2026-04-24', 7, 49.89, 7, '2026-10-12');

        $csv = implode("\n", [
            'Data;Estabelecimento;Valor',
            '04/11/2025;PG *TEC CONCURSOS 12/12;49,89',
        ]);

        $resultado = (new CreditCardReconciliationService())->reconcile(
            UploadedFile::fake()->createWithContent('fatura.csv', $csv), $this->cartao, 10, 2026
        );

        $this->assertEquals(1, $resultado['contadores']['conciliados']);
        $this->assertEquals(0, $resultado['contadores']['sobrando_no_sistema']);
    }

    public function test_warns_when_invoice_seems_to_belong_to_another_card(): void
    {
        $outro = Cartao::create([
            'user_id' => $this->user->id, 'nome' => 'VISA SX', 'bandeira' => 'Visa', 'cor' => '#000',
            'limite' => 5000, 'dia_fechamento' => 25, 'dia_vencimento' => 12, 'ativo' => true,
        ]);
        foreach ([10.50, 22.30, 99.90] as $valor) {
            $compra = CartaoCompra::create([
                'cartao_id' => $outro->id, 'descricao' => 'Compra', 'valor_total' => $valor, 'tipo' => 'avista',
                'numero_parcelas' => 1, 'categoria' => 'Outros', 'data_compra' => '2026-09-20',
            ]);
            CartaoParcela::create([
                'cartao_compra_id' => $compra->id, 'numero_parcela' => 1, 'valor_parcela' => $valor,
                'data_vencimento' => '2026-10-12', 'status' => 'aberta',
            ]);
        }

        $csv = implode("\n", ['Data;Estabelecimento;Valor', '20/09/2026;A;10,50', '21/09/2026;B;22,30', '22/09/2026;C;99,90']);

        $resultado = (new CreditCardReconciliationService())->reconcile(
            UploadedFile::fake()->createWithContent('fatura.csv', $csv), $this->cartao, 10, 2026
        );

        $this->assertEquals(0, $resultado['contadores']['conciliados']);
        $this->assertStringContainsString('VISA SX', $resultado['aviso']);
    }

    protected function criarCompraParcelada(string $descricao, string $dataCompra, int $numeroParcelas, float $valorParcela, int $parcelaNoMes, string $vencimento, string $tipo = 'parcelada'): void
    {
        $compra = CartaoCompra::create([
            'cartao_id'       => $this->cartao->id,
            'descricao'       => $descricao,
            'valor_total'     => $valorParcela * $numeroParcelas,
            'tipo'            => $tipo,
            'numero_parcelas' => $numeroParcelas,
            'categoria'       => 'Outros',
            'data_compra'     => $dataCompra,
        ]);
        CartaoParcela::create([
            'cartao_compra_id' => $compra->id,
            'numero_parcela'   => $parcelaNoMes,
            'valor_parcela'    => $valorParcela,
            'data_vencimento'  => $vencimento,
            'status'           => 'aberta',
        ]);
    }

    public function test_http_endpoint_processes_invoice_upload_and_stores_session(): void
    {
        $csvContent = implode("\n", [
            'Data;Estabelecimento;Valor',
            '10/10/2026;Padaria Pao Quente;25,50',
        ]);

        $file = UploadedFile::fake()->createWithContent('fatura.csv', $csvContent);

        $response = $this->actingAs($this->user)->postJson(route('cartoes.conciliar.fatura'), [
            'arquivo'   => $file,
            'cartao_id' => $this->cartao->id,
            'mes'       => 10,
            'ano'       => 2026,
        ]);

        $response->assertOk();
        $response->assertJsonPath('sucesso', true);
        $response->assertJsonPath('cartao.nome', 'Cartão Nubank');
        $response->assertSessionHas('ultima_conciliacao_fatura');
    }

    public function test_pdf_export_endpoint_returns_download_stream(): void
    {
        // Simular sessão preenchida
        session([
            'ultima_conciliacao_fatura' => [
                'sucesso'  => true,
                'cartao'   => [
                    'id'   => $this->cartao->id,
                    'nome' => 'Cartão Nubank',
                ],
                'periodo'  => [
                    'mes'      => 10,
                    'ano'      => 2026,
                    'nome_mes' => 'Outubro / 2026',
                ],
                'totais'   => [
                    'fatura_despesas'   => 25.50,
                    'fatura_pagamentos' => 0.0,
                    'sistema'           => 25.50,
                    'diferenca'         => 0.0,
                    'is_alinhado'       => true,
                ],
                'contadores' => [
                    'conciliados'         => 1,
                    'divergencias_valor'  => 0,
                    'faltando_no_sistema' => 0,
                    'sobrando_no_sistema' => 0,
                    'pagamentos_estornos' => 0,
                    'total_fatura'        => 1,
                    'total_sistema'       => 1,
                ],
                'grupos'   => [
                    'faltando_no_sistema' => [],
                    'sobrando_no_sistema' => [],
                    'divergencias_valor'  => [],
                    'conciliados'         => [
                        [
                            'status'       => 'certeiro',
                            'confianca'    => 100,
                            'fatura_item'  => ['data' => '2026-10-10', 'descricao' => 'Padaria Pao Quente', 'valor' => 25.50],
                            'sistema_item' => ['data_compra' => '2026-10-10', 'descricao' => 'Padaria Pao Quente', 'parcela_texto' => 'À vista', 'valor_parcela' => 25.50],
                        ],
                    ],
                    'pagamentos_estornos' => [],
                ],
            ],
        ]);

        $response = $this->actingAs($this->user)->get(route('cartoes.conciliar.pdf'));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_pdf_file_reconciliation_executes_without_method_errors(): void
    {
        $stream = "BT /F1 12 Tf 72 712 Td (10/10/2026 Loja de Calcados R$ 199,90) Tj ET";
        $pdfContent = "%PDF-1.4\n1 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";

        $file = UploadedFile::fake()->createWithContent('fatura_teste.pdf', $pdfContent);

        $service = new CreditCardReconciliationService();
        $resultado = $service->reconcile($file, $this->cartao, 10, 2026);

        // Deve processar sem lançar erro de método inexistente
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('sucesso', $resultado);
    }
}
