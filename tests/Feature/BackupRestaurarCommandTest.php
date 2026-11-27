<?php

namespace Tests\Feature;

use App\Console\Commands\BackupDriveCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PDO;
use Tests\TestCase;

/** Cofre de configuração, restauração e teste semanal do backup (só SQLite; o Postgres usa pg_restore real). */
class BackupRestaurarCommandTest extends TestCase
{
    private string $raiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->raiz = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'backup-restaurar-test-' . uniqid();
        File::ensureDirectoryExists($this->raiz . '/drive/Backups/financeiro/db');
        File::ensureDirectoryExists($this->raiz . '/pc');
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        File::deleteDirectory($this->raiz);
        parent::tearDown();
    }

    private function sqlite(string $arquivo, int $linhas): void
    {
        $pdo = new PDO('sqlite:' . $arquivo);
        $pdo->exec('create table gastos (id integer primary key, valor int)');
        for ($i = 1; $i <= $linhas; $i++) {
            $pdo->exec("insert into gastos (valor) values ({$i})");
        }
    }

    /** Banco "em uso" num arquivo temporário (os testes usam :memory:). */
    private function usarBanco(string $arquivo): void
    {
        config(['database.connections.sqlite.database' => $arquivo, 'database.default' => 'sqlite']);
        DB::purge('sqlite');
    }

    public function test_cofre_guarda_so_quando_muda_e_mantem_as_ultimas_versoes(): void
    {
        $pasta = $this->raiz . '/drive/Backups/financeiro/config';

        $this->assertTrue(BackupDriveCommand::guardarSeMudou($pasta, 'env', 'txt', 'A=1', 3));
        $this->assertFalse(BackupDriveCommand::guardarSeMudou($pasta, 'env', 'txt', 'A=1', 3));
        foreach (['A=2', 'A=3', 'A=4'] as $i => $c) {
            $this->travel($i + 1)->seconds();
            $this->assertTrue(BackupDriveCommand::guardarSeMudou($pasta, 'env', 'txt', $c, 3));
        }
        $versoes = glob($pasta . '/env_*.txt');
        sort($versoes);
        $this->assertCount(3, $versoes);
        $this->assertSame('A=4', file_get_contents(end($versoes)));
        $this->assertSame('A=2', file_get_contents($versoes[0]));
    }

    public function test_backup_drive_leva_o_env_para_o_cofre(): void
    {
        config(['backup.dir' => $this->raiz . '/drive/Backups/financeiro', 'backup.local' => $this->raiz . '/pc/espelho', 'dados.dir' => '']);

        $this->artisan('backup:drive')->assertSuccessful();

        $envs = glob($this->raiz . '/drive/Backups/financeiro/config/env_*.txt');
        $this->assertCount(1, $envs);
        $this->assertSame(file_get_contents(base_path('.env')), file_get_contents($envs[0]));
    }

    public function test_restaurar_troca_o_banco_e_guarda_o_anterior(): void
    {
        $emUso = $this->raiz . '/pc/database.sqlite';
        $this->sqlite($emUso, 1);
        $this->sqlite($this->raiz . '/drive/Backups/financeiro/db/financeiro_20261101_100000.sqlite', 2);
        $this->sqlite($this->raiz . '/drive/Backups/financeiro/db/financeiro_20261127_100000.sqlite', 5);
        config(['backup.dir' => $this->raiz . '/drive/Backups/financeiro']);
        $this->usarBanco($emUso);

        $this->artisan('backup:restaurar', ['--so' => 'principal', '--force' => true])->assertSuccessful();

        $this->assertSame(5, DB::table('gastos')->count()); // a cópia mais nova
        $guardados = glob($this->raiz . '/pc/database.antes-restaurar-*.sqlite');
        $this->assertCount(1, $guardados);
        $this->assertSame(1, (int) (new PDO('sqlite:' . $guardados[0]))->query('select count(*) from gastos')->fetchColumn());
    }

    public function test_restaurar_sem_copia_ou_sem_confirmar_nao_mexe_em_nada(): void
    {
        $emUso = $this->raiz . '/pc/database.sqlite';
        $this->sqlite($emUso, 1);
        $this->usarBanco($emUso);
        config(['backup.dir' => $this->raiz . '/drive/Backups/financeiro']);

        $this->artisan('backup:restaurar', ['--so' => 'principal', '--force' => true])->assertFailed();

        $this->sqlite($this->raiz . '/drive/Backups/financeiro/db/financeiro_20261127_100000.sqlite', 5);
        $this->artisan('backup:restaurar', ['--so' => 'principal'])->expectsConfirmation('Continuar?', 'no')->assertFailed();
        $this->assertSame(1, DB::table('gastos')->count());
        $this->assertSame([], glob($this->raiz . '/pc/database.antes-restaurar-*.sqlite'));
    }

    public function test_testar_confere_a_copia_e_avisa_quando_falta_dado(): void
    {
        config(['backup.dir' => $this->raiz . '/drive/Backups/financeiro', 'telegram.bot_token' => 'x', 'telegram.allowed_chat_id' => '42']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $emUso = $this->raiz . '/pc/database.sqlite';
        $this->sqlite($emUso, 40);
        $this->usarBanco($emUso);

        $this->sqlite($this->raiz . '/drive/Backups/financeiro/db/financeiro_20261127_100000.sqlite', 39);
        $this->artisan('backup:testar')->assertSuccessful();
        Http::assertSent(fn ($r) => str_contains($r['text'], 'Teste semanal do backup') && str_contains($r['text'], '39 linhas'));

        // Cópia mais nova veio quase vazia: avisa.
        $this->sqlite($this->raiz . '/drive/Backups/financeiro/db/financeiro_20261127_110000.sqlite', 3);
        $this->artisan('backup:testar')->assertFailed();
        Http::assertSent(fn ($r) => str_contains($r['text'], 'Backup falhou: teste de restauração') && str_contains($r['text'], 'gastos tem 3 linhas'));

        // Arquivo corrompido.
        file_put_contents($this->raiz . '/drive/Backups/financeiro/db/financeiro_20261127_120000.sqlite', 'lixo');
        $this->artisan('backup:testar', ['--sem-aviso' => true])->assertFailed();
    }
}
