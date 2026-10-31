<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImportOfxCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_when_folder_is_empty(): void
    {
        $this->artisan('ofx:import')
            ->expectsOutputToContain('IMPORTAÇÃO DE EXTRATOS BANCÁRIOS (.OFX)')
            ->assertExitCode(0);
    }

    public function test_command_processes_ofx_file_in_custom_directory(): void
    {
        $user = User::factory()->create(['id' => 1]);

        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_ofx_' . uniqid();
        mkdir($tempDir, 0777, true);

        $ofxContent = <<<OFX
<OFX>
<BANKMSGSRSV1>
<STMTTRNRS>
<STMTRS>
<BANKACCTFROM>
<BANKID>0260
<BRANCHID>0001
<ACCTID>987654-3
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261025120000
<TRNAMT>-75.50
<FITID>TEST_FITID_999
<MEMO>FARMACIA POPULAR
</STMTTRN>
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>1500.00
<DTASOF>20261025120000
</LEDGERBAL>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;

        $ofxFilePath = $tempDir . DIRECTORY_SEPARATOR . 'nubank_teste.ofx';
        file_put_contents($ofxFilePath, $ofxContent);

        $this->artisan('ofx:import', ['path' => $tempDir, '--user' => 1])
            ->expectsOutputToContain('Arquivos encontrados para processamento: 1')
            ->expectsOutputToContain('Sucesso: Conta')
            ->expectsOutputToContain('Novos inseridos: 1')
            ->assertExitCode(0);

        // O arquivo deve ter sido movido para processados/
        $this->assertFileDoesNotExist($ofxFilePath);
        $this->assertDirectoryExists($tempDir . DIRECTORY_SEPARATOR . 'processados');

        // Limpeza
        array_map('unlink', glob("{$tempDir}/processados/*.*"));
        rmdir($tempDir . DIRECTORY_SEPARATOR . 'processados');
        rmdir($tempDir);
    }
}
