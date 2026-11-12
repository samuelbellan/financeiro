<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Conta;
use App\Models\Transacao;
use App\Services\StatementImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class StatementImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['id' => 1]);
    }

    public function test_financas_page_renders_import_extrato_button_and_modal(): void
    {
        $response = $this->actingAs($this->user)->get('/financas');

        $response->assertStatus(200);
        $response->assertSee('Importar Extrato');
        $response->assertSee('importExtratoModalBackdrop');
        $response->assertSee('openImportExtratoModal()');
    }

    public function test_csv_import_creates_transactions_and_account(): void
    {
        $csvContent = "Data;Descricao;Valor;Tipo;Documento\n" .
                      "20/10/2026;Supermercado Big;150,50;Debito;DOC001\n" .
                      "21/10/2026;Salario Mensal;5000,00;Credito;DOC002\n" .
                      "22/10/2026;Farmacia Vida;-45,00;;DOC003\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_') . '.csv';
        file_put_contents($tempFile, $csvContent);

        $uploadedFile = new UploadedFile($tempFile, 'extrato_teste.csv', 'text/csv', null, true);

        $service = new StatementImportService();
        $result = $service->import($uploadedFile, $this->user->id);

        $this->assertTrue($result['sucesso']);
        $this->assertEquals(3, $result['total_lidos']);
        $this->assertEquals(3, $result['inseridos']);
        $this->assertEquals(0, $result['duplicados']);
        $this->assertEquals(5000.00, $result['total_entradas']);
        $this->assertEquals(195.50, $result['total_saidas']);

        // Verificar no banco
        $this->assertDatabaseHas('transacoes', [
            'user_id' => $this->user->id,
            'descricao' => 'Supermercado Big',
            'valor' => 150.50,
            'tipo' => 'despesa',
        ]);

        $this->assertDatabaseHas('transacoes', [
            'user_id' => $this->user->id,
            'descricao' => 'Salario Mensal',
            'valor' => 5000.00,
            'tipo' => 'receita',
        ]);

        // Reimportar mesmo arquivo para testar deduplicação
        $result2 = $service->import($uploadedFile, $this->user->id);
        $this->assertTrue($result2['sucesso']);
        $this->assertEquals(0, $result2['inseridos']);
        $this->assertEquals(3, $result2['duplicados']);

        @unlink($tempFile);
    }

    public function test_csv_with_overlapping_period_does_not_duplicate_and_keeps_identical_same_day_entries(): void
    {
        $service = new StatementImportService();
        $importar = function (string $csv, string $nome) use ($service): array {
            $tmp = tempnam(sys_get_temp_dir(), 'csv_') . '.csv';
            file_put_contents($tmp, $csv);
            $result = $service->import(new UploadedFile($tmp, $nome, 'text/csv', null, true), $this->user->id);
            @unlink($tmp);
            return $result;
        };

        // Extrato 1: 23/10 a 26/10, com dois Pix idênticos no mesmo dia (sem coluna de documento)
        $r1 = $importar(
            "Data;Descricao;Valor
" .
            "26/10/2026;Pix recebido;5865,00
" .
            "23/10/2026;Pix enviado;-7,55
" .
            "23/10/2026;Pix enviado;-7,55
",
            'extrato_a.csv'
        );
        $this->assertSame(3, $r1['inseridos']);

        // Extrato 2: período sobreposto, linhas em outras posições + lançamentos novos
        $r2 = $importar(
            "Data;Descricao;Valor
" .
            "06/11/2026;Pagamento de boleto;-450,00
" .
            "04/11/2026;Pix recebido;3200,00
" .
            "26/10/2026;Pix recebido;5865,00
" .
            "23/10/2026;Pix enviado;-7,55
" .
            "23/10/2026;Pix enviado;-7,55
",
            'extrato_b.csv'
        );

        $this->assertSame(2, $r2['inseridos']);
        $this->assertSame(3, $r2['duplicados']);
        $this->assertSame(2, Transacao::where('valor', 7.55)->count());
        $this->assertSame(1, Transacao::where('valor', 5865)->count());

        $saldo = Transacao::selectRaw("SUM(CASE WHEN tipo = 'receita' THEN valor ELSE -valor END) as t")->value('t');
        $this->assertEqualsWithDelta(5865 - 15.10 + 3200 - 450, (float) $saldo, 0.001);
    }

    public function test_csv_reconcilia_lancamento_mesclado_com_fitid_legado(): void
    {
        $conta = Conta::create(['user_id' => $this->user->id, 'nome' => 'Corrente', 'tipo' => 'corrente', 'moeda' => 'BRL', 'saldo_atual' => 0, 'ativo' => true]);

        // Lançamentos já conciliados/mesclados antes: descrição editada, data um pouco
        // diferente e fitid gerado por uma versão antiga do importador.
        $legados = [
            ['2026-10-16', 75.00, 'despesa', 'Josefa', 'legado01'],
            ['2026-10-17', 56.00, 'receita', 'PIX ZAMILDA', 'legado02'],
            ['2026-10-18', 100.00, 'despesa', 'Pix Marcelo (Missoes)', 'legado03'],
        ];
        foreach ($legados as [$data, $valor, $tipo, $desc, $fitid]) {
            Transacao::create(['user_id' => $this->user->id, 'conta_id' => $conta->id, 'data' => $data, 'valor' => $valor, 'tipo' => $tipo, 'descricao' => $desc, 'fitid' => $fitid, 'categoria' => 'Outros']);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'csv_') . '.csv';
        file_put_contents($tmp, "Data;Descrição;Detalhe;Valor;Saldo\n" .
            "20/10/2026;Pix enviado;Fulano;-75,00;R$ 0,00\n" .   // outro Pix de 75: novo
            "19/10/2026;Pix enviado;Marcelo;-100,00;R$ 0,00\n" .
            "19/10/2026;Pix recebido;Zamilda;56,00;R$ 0,00\n" .
            "16/10/2026;Pix enviado;Josefa;-75,00;R$ 0,00\n");

        $service = new StatementImportService();
        $r = $service->import(new UploadedFile($tmp, 'extrato.csv', 'text/csv', null, true), $this->user->id, $conta->id);
        $this->assertSame(1, $r['inseridos']);
        $this->assertSame(3, $r['duplicados']);
        $this->assertSame(4, Transacao::count());

        // Os mesclados adotam o fitid atual: reimportar casa direto pelo fitid
        $this->assertSame(0, Transacao::where('fitid', 'like', 'legado%')->count());
        $r2 = $service->import(new UploadedFile($tmp, 'extrato.csv', 'text/csv', null, true), $this->user->id, $conta->id);
        $this->assertSame(0, $r2['inseridos']);
        $this->assertSame(4, Transacao::count());
        @unlink($tmp);
    }

    public function test_http_endpoint_imports_csv_via_ajax(): void
    {
        $csvContent = "Data,Historico,Valor\n" .
                      "15/10/2026,Restaurante Bom Sabor,-89.90\n" .
                      "16/10/2026,Transferencia Recebida,300.00\n";

        $file = UploadedFile::fake()->createWithContent('extrato_banco.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson(route('financas.importar-extrato'), [
                'extrato' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'sucesso' => true,
            'total_lidos' => 2,
            'inseridos' => 2,
            'duplicados' => 0,
        ]);
    }

    public function test_xlsx_import_creates_transactions(): void
    {
        // Criar um arquivo .xlsx válido em tempo de execução
        $zipFile = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';
        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::CREATE);

        $sharedStrings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="6" uniqueCount="6">' .
            '<si><t>Data</t></si>' .
            '<si><t>Descricao</t></si>' .
            '<si><t>Valor</t></si>' .
            '<si><t>20/10/2026</t></si>' .
            '<si><t>Posto Gasolina</t></si>' .
            '<si><t>21/10/2026</t></si>' .
            '</sst>';

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetData>' .
            '<row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c></row>' .
            '<row r="2"><c r="A2" t="s"><v>3</v></c><c r="B2" t="s"><v>4</v></c><c r="C2"><v>-200.00</v></c></row>' .
            '</sheetData>' .
            '</worksheet>';

        $zip->addFromString('xl/sharedStrings.xml', $sharedStrings);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        $uploadedFile = new UploadedFile($zipFile, 'extrato_excel.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $service = new StatementImportService();
        $result = $service->import($uploadedFile, $this->user->id);

        $this->assertTrue($result['sucesso']);
        $this->assertEquals(1, $result['total_lidos']);
        $this->assertEquals(1, $result['inseridos']);
        $this->assertEquals('EXCEL', $result['formato']);

        $this->assertDatabaseHas('transacoes', [
            'user_id' => $this->user->id,
            'descricao' => 'Posto Gasolina',
            'valor' => 200.00,
            'tipo' => 'despesa',
        ]);

        @unlink($zipFile);
    }
}
