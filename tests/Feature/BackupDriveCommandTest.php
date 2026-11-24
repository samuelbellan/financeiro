<?php

namespace Tests\Feature;

use App\Console\Commands\BackupDriveCommand;
use App\Support\Dados;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupDriveCommandTest extends TestCase
{
    private string $raiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->raiz = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'backup-drive-test-' . uniqid();
        File::ensureDirectoryExists($this->raiz . '/drive/dados/app/public/notas_fiscais');
        File::ensureDirectoryExists($this->raiz . '/drive/Backups');
        file_put_contents($this->raiz . '/drive/dados/app/public/notas_fiscais/nf.jpg', 'jpg');
        file_put_contents($this->raiz . '/drive/dados/app/salary_structures.json', '{}');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->raiz);
        parent::tearDown();
    }

    public function test_falha_sem_pasta_configurada(): void
    {
        config(['backup.dir' => null]);

        $this->artisan('backup:drive')->assertFailed();
    }

    public function test_falha_se_o_drive_nao_estiver_montado(): void
    {
        config(['backup.dir' => $this->raiz . '/nao-montado/Backups/financeiro']);

        $this->artisan('backup:drive')->assertFailed();
    }

    public function test_banco_vai_para_o_drive_e_arquivos_do_drive_para_o_pc(): void
    {
        config([
            'backup.dir' => $this->raiz . '/drive/Backups/financeiro',
            'backup.local' => $this->raiz . '/pc/espelho',
            'dados.dir' => $this->raiz . '/drive/dados',
        ]);

        $this->artisan('backup:drive')->assertSuccessful();

        $copias = glob($this->raiz . '/drive/Backups/financeiro/db/financeiro_*.sqlite');
        $this->assertCount(1, $copias);
        $this->assertSame("SQLite format 3\0", file_get_contents($copias[0], false, null, 0, 16));
        $this->assertSame('jpg', file_get_contents($this->raiz . '/pc/espelho/app/public/notas_fiscais/nf.jpg'));
        $this->assertFileExists($this->raiz . '/pc/espelho/app/salary_structures.json');
    }

    public function test_dados_path_usa_o_drive_quando_configurado_e_storage_sem_ele(): void
    {
        config(['dados.dir' => 'G:\\Meu Drive\\financeiro']);
        $this->assertSame('G:\\Meu Drive\\financeiro' . DIRECTORY_SEPARATOR . 'concursos' . DIRECTORY_SEPARATOR . 'leis', Dados::path('concursos/leis'));

        config(['dados.dir' => '']);
        $this->assertSame(storage_path('app' . DIRECTORY_SEPARATOR . 'midias'), Dados::path('app/midias'));
    }

    public function test_espelho_so_copia_o_que_mudou_e_nao_apaga_no_destino(): void
    {
        $origem = $this->raiz . '/drive/dados';
        $destino = $this->raiz . '/pc/espelho';

        $this->assertSame(2, BackupDriveCommand::espelhar($origem, $destino));
        $this->assertSame(0, BackupDriveCommand::espelhar($origem, $destino));

        file_put_contents($origem . '/app/salary_structures.json', '{"mudou": true}');
        unlink($origem . '/app/public/notas_fiscais/nf.jpg');
        $this->assertSame(1, BackupDriveCommand::espelhar($origem, $destino));
        $this->assertSame('{"mudou": true}', file_get_contents($destino . '/app/salary_structures.json'));
        $this->assertFileExists($destino . '/app/public/notas_fiscais/nf.jpg');
    }

    public function test_retencao_horaria_diaria_e_semanal(): void
    {
        // Uma cópia por hora durante 60 dias
        $arquivos = [];
        $d = new \DateTimeImmutable('2026-09-01 00:00:00');
        for ($i = 0; $i < 60 * 24; $i++) {
            $arquivos[] = 'G:/b/db/financeiro_' . $d->modify("+{$i} hours")->format('Ymd_His') . '.sqlite';
        }
        $arquivos[] = 'G:/b/db/outro_20260901_000000.sqlite';

        $remover = BackupDriveCommand::aplicarRetencao($arquivos, 24, 14, 8);
        $mantidos = array_values(array_diff($arquivos, $remover));

        foreach (array_slice($arquivos, -25, 24) as $recente) {
            $this->assertContains($recente, $mantidos);
        }
        $this->assertContains($arquivos[0], $remover);
        $this->assertNotContains('G:/b/db/outro_20260901_000000.sqlite', $remover);
        $this->assertLessThanOrEqual(24 + 14 + 8 + 1, count($mantidos));
    }
}
