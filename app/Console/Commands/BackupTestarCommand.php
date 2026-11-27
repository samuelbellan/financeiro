<?php

namespace App\Console\Commands;

use App\Services\AvisoBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use Symfony\Component\Process\Process;

/**
 * Prova que as cópias do Drive restauram (tarefa semanal). Não mexe nos bancos em uso.
 *
 *  - Banco principal: copia o .sqlite mais novo para uma pasta temporária, roda integrity_check e
 *    conta as linhas de cada tabela.
 *  - Banco de concursos: restaura o .dump mais novo no banco de teste (CONCURSOS_BACKUP_TESTE_DB,
 *    criado uma vez por scripts\backup-teste-setup.ps1) e conta as linhas. Sem esse banco, só
 *    confere se o dump abre e lista todas as tabelas (teste parcial).
 *
 * As contagens são comparadas com os bancos em uso: falta de tabela, ou tabela com 10+ linhas
 * que veio com menos da metade, é problema. Resultado vai para o Telegram (sucesso também).
 */
class BackupTestarCommand extends Command
{
    protected $signature = 'backup:testar {--sem-aviso : Não manda o resultado no Telegram}';

    protected $description = 'Restaura as cópias mais novas do Drive em bancos temporários e confere se estão completas.';

    public const NOME = 'teste de restauração';

    /** @var list<string> */
    private array $problemas = [];

    /** @var list<string> */
    private array $resumo = [];

    public function handle(AvisoBackup $aviso): int
    {
        $this->testarSqlite();
        if (BackupDriveCommand::concursosConfigurado()) {
            $this->testarPostgres();
        }

        foreach ($this->resumo as $l) {
            $this->line($l);
        }
        if ($this->problemas) {
            foreach ($this->problemas as $p) {
                $this->error($p);
            }
            if (!$this->option('sem-aviso')) {
                $aviso->falhou(self::NOME, implode("\n", $this->problemas));
            }

            return self::FAILURE;
        }
        $this->info('Cópias restauram e estão completas.');
        if (!$this->option('sem-aviso')) {
            $aviso->ok(self::NOME);
            $aviso->avisar("✅ Teste semanal do backup: as cópias do Drive restauram.\n" . implode("\n", $this->resumo));
        }

        return self::SUCCESS;
    }

    private function testarSqlite(): void
    {
        $arquivo = BackupRestaurarCommand::maisNovo(config('backup.dir'), 'financeiro_*.sqlite');
        if (!$arquivo) {
            $this->problemas[] = 'Banco principal: nenhuma cópia no Drive.';

            return;
        }
        $temp = tempnam(sys_get_temp_dir(), 'teste-backup-') . '.sqlite';
        try {
            copy($arquivo, $temp);
            $pdo = new PDO('sqlite:' . $temp, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $integridade = $pdo->query('PRAGMA integrity_check')->fetchColumn();
            if ($integridade !== 'ok') {
                $this->problemas[] = "Banco principal: integrity_check = {$integridade}.";
            }
            $copia = [];
            foreach ($pdo->query("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN) as $t) {
                $copia[$t] = (int) $pdo->query('select count(*) from "' . str_replace('"', '""', $t) . '"')->fetchColumn();
            }
            $pdo = null;

            $conexao = DB::connection();
            $emUso = [];
            if ($conexao->getDriverName() === 'sqlite') {
                foreach ($conexao->select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'") as $r) {
                    $emUso[$r->name] = $conexao->table($r->name)->count();
                }
            }
            $this->comparar('Banco principal', basename($arquivo), $copia, $emUso);
        } catch (\Throwable $e) {
            $this->problemas[] = 'Banco principal: a cópia não abre (' . $e->getMessage() . ').';
        } finally {
            @unlink($temp);
            @unlink(substr($temp, 0, -7)); // o arquivo vazio criado pelo tempnam
        }
    }

    private function testarPostgres(): void
    {
        $dump = BackupRestaurarCommand::maisNovo(config('concursos.backup.dir'), 'concursos_*.dump');
        if (!$dump) {
            $this->problemas[] = 'Banco de concursos: nenhuma cópia no Drive.';

            return;
        }
        $nome = config('concursos.connection');
        $cfg = config("database.connections.{$nome}");
        $bin = rtrim((string) config('concursos.backup.pg_bin'), '\\/');
        $exe = fn (string $n) => $bin !== '' ? $bin . DIRECTORY_SEPARATOR . $n : $n;
        $conexao = ['--host=' . $cfg['host'], '--port=' . $cfg['port'], '--username=' . $cfg['username']];
        $env = ['PGPASSWORD' => (string) $cfg['password']];

        $emUso = [];
        $live = DB::connection($nome);
        foreach ($live->select('select tablename from pg_tables where schemaname = current_schema()') as $r) {
            $emUso[$r->tablename] = $live->table($r->tablename)->count();
        }

        $testeDb = (string) config('concursos.backup.teste_db');
        if ($testeDb !== '' && $this->bancoExiste($nome, $cfg, $testeDb)) {
            $p = new Process([$exe('pg_restore'), '--clean', '--if-exists', '--no-owner', '--no-privileges', '--single-transaction',
                ...$conexao, '--dbname=' . $testeDb, $dump], null, $env, null, 1800);
            $p->run();
            if (!$p->isSuccessful()) {
                $this->problemas[] = 'Banco de concursos: pg_restore falhou (' . trim($p->getErrorOutput()) . ').';

                return;
            }
            config(["database.connections.backup_teste" => array_merge($cfg, ['database' => $testeDb])]);
            $teste = DB::connection('backup_teste');
            $copia = [];
            foreach ($teste->select('select tablename from pg_tables where schemaname = current_schema()') as $r) {
                $copia[$r->tablename] = $teste->table($r->tablename)->count();
            }
            DB::purge('backup_teste');
            $this->comparar('Banco de concursos', basename($dump), $copia, $emUso);

            return;
        }

        // Sem banco de teste: só confere se o dump abre e traz os dados de todas as tabelas.
        $p = new Process([$exe('pg_restore'), '--list', $dump], null, null, null, 300);
        $p->run();
        if (!$p->isSuccessful()) {
            $this->problemas[] = 'Banco de concursos: o dump não abre (' . trim($p->getErrorOutput()) . ').';

            return;
        }
        preg_match_all('/TABLE DATA \S+ (\S+) /', $p->getOutput(), $m);
        $faltam = array_diff(array_keys($emUso), $m[1]);
        if ($faltam) {
            $this->problemas[] = 'Banco de concursos: o dump não tem os dados de ' . implode(', ', $faltam) . '.';
        }
        $this->resumo[] = 'Banco de concursos (' . basename($dump) . '): o dump abre e traz as ' . count($m[1]) . ' tabelas. '
            . 'Teste parcial: rode scripts\backup-teste-setup.ps1 uma vez para o teste restaurar de verdade.';
    }

    private function bancoExiste(string $nome, array $cfg, string $banco): bool
    {
        return (bool) DB::connection($nome)->selectOne('select 1 as ok from pg_database where datname = ?', [$banco]);
    }

    /**
     * @param  array<string, int>  $copia
     * @param  array<string, int>  $emUso
     */
    private function comparar(string $qual, string $arquivo, array $copia, array $emUso): void
    {
        $faltam = array_diff(array_keys($emUso), array_keys($copia));
        if ($faltam) {
            $this->problemas[] = "{$qual}: a cópia não tem as tabelas " . implode(', ', $faltam) . '.';
        }
        foreach ($emUso as $t => $n) {
            if ($n >= 10 && isset($copia[$t]) && $copia[$t] < $n / 2) {
                $this->problemas[] = "{$qual}: {$t} tem {$copia[$t]} linhas na cópia e {$n} no banco em uso.";
            }
        }
        $this->resumo[] = sprintf('%s (%s): %d tabelas, %s linhas, restaurou.', $qual, $arquivo, count($copia),
            number_format(array_sum($copia), 0, ',', '.'));
    }
}
