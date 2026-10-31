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
