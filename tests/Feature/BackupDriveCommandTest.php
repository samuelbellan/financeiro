<?php

namespace Tests\Feature;

use App\Console\Commands\BackupDriveCommand;
use App\Support\Dados;
use App\Services\AvisoBackup;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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

    public function test_falha_avisa_no_telegram_uma_vez_a_cada_6h_e_avisa_quando_volta(): void
    {
        config(['telegram.bot_token' => 'x', 'telegram.allowed_chat_id' => '42', 'backup.dir' => $this->raiz . '/nao-montado/Backups/financeiro']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->artisan('backup:drive')->assertFailed();
        $this->artisan('backup:drive')->assertFailed(); // mesma falha logo depois: não repete
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => str_contains($r['text'], 'Backup falhou: banco principal') && str_contains($r['text'], 'Google Drive'));

        $this->travel(AvisoBackup::REPETE_HORAS + 1)->hours();
        $this->artisan('backup:drive')->assertFailed();
        Http::assertSentCount(2);

        config(['backup.dir' => $this->raiz . '/drive/Backups/financeiro', 'backup.local' => $this->raiz . '/pc/espelho', 'dados.dir' => $this->raiz . '/drive/dados']);
        $this->artisan('backup:drive')->assertSuccessful();
        Http::assertSent(fn ($r) => str_contains($r['text'], 'voltou a funcionar'));
        Http::assertSentCount(3);
        $this->artisan('backup:drive')->assertSuccessful();
        Http::assertSentCount(3);
    }

    public function test_conferir_roda_o_backup_quando_atrasado_e_avisa_na_segunda_vez(): void
    {
        config(['telegram.bot_token' => 'x', 'telegram.allowed_chat_id' => '42', 'backup.dir' => $this->raiz . '/drive/Backups/financeiro',
            'backup.local' => $this->raiz . '/pc/espelho', 'dados.dir' => $this->raiz . '/drive/dados']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        File::ensureDirectoryExists($this->raiz . '/drive/Backups/financeiro/db');
        $velho = $this->raiz . '/drive/Backups/financeiro/db/financeiro_20260101_000000.sqlite';
        file_put_contents($velho, 'x');
        touch($velho, time() - 5 * 3600);

        // Primeiro atraso: só roda o backup (PC pode ter dormido), sem aviso.
        $this->artisan('backup:conferir')->assertSuccessful();
        $this->assertCount(2, glob($this->raiz . '/drive/Backups/financeiro/db/financeiro_*.sqlite'));
        Http::assertNothingSent();

        // Em dia: zera a contagem.
        $this->artisan('backup:conferir')->expectsOutput('Backups em dia.')->assertSuccessful();
        $this->assertNull(Cache::get(\App\Console\Commands\BackupConferirCommand::CHAVE_ATRASOS));

        // Dois atrasos seguidos: avisa que a tarefa não está rodando.
        array_map(fn ($f) => touch($f, time() - 5 * 3600), glob($this->raiz . '/drive/Backups/financeiro/db/*.sqlite'));
        $this->artisan('backup:conferir');
        array_map(fn ($f) => touch($f, time() - 5 * 3600), glob($this->raiz . '/drive/Backups/financeiro/db/*.sqlite'));
        $this->artisan('backup:conferir');
        Http::assertSent(fn ($r) => str_contains($r['text'], 'não está rodando sozinho'));
    }

    public function test_backup_drive_so_chama_o_de_concursos_quando_ha_postgres_e_pasta(): void
    {
        config(['concursos.backup.dir' => $this->raiz . '/drive/Backups/concursos']);
        $this->assertFalse(BackupDriveCommand::concursosConfigurado()); // nos testes a conexão é SQLite
        config(['database.connections.concursos_pgsql.driver' => 'pgsql']);
        $this->assertTrue(BackupDriveCommand::concursosConfigurado());
        config(['concursos.backup.dir' => '']);
        $this->assertFalse(BackupDriveCommand::concursosConfigurado());
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
