<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Restaura os bancos a partir do Google Drive (PC novo ou desastre). Roteiro completo em
 * docs/recuperacao.md. Pare o servidor do app antes (php artisan serve / tarefa do servidor).
 *
 *  - Banco principal: a cópia .sqlite mais nova de <BACKUP_DRIVE_DIR>/db substitui o banco em uso.
 *  - Banco de concursos: o .dump mais novo de <CONCURSOS_BACKUP_DIR>/db entra com pg_restore --clean.
 *
 * Antes de sobrescrever, guarda o que existe: o SQLite atual vira database/database.antes-restaurar-*.sqlite
 * e o Postgres atual ganha um pg_dump em storage/app/antes-restaurar/.
 */
class BackupRestaurarCommand extends Command
{
    protected $signature = 'backup:restaurar
                            {--so= : Só um dos bancos: principal ou concursos}
                            {--sqlite= : Arquivo .sqlite a restaurar (padrão: o mais novo do Drive)}
                            {--dump= : Arquivo .dump a restaurar (padrão: o mais novo do Drive)}
                            {--force : Não pede confirmação}';

    protected $description = 'Restaura o banco principal e o de concursos a partir das cópias no Google Drive.';

    public function handle(): int
    {
        $so = $this->option('so');
        if ($so && !in_array($so, ['principal', 'concursos'], true)) {
            $this->error('--so aceita "principal" ou "concursos".');

            return self::FAILURE;
        }

        $sqlite = $so === 'concursos' ? null : ($this->option('sqlite') ?: self::maisNovo(config('backup.dir'), 'financeiro_*.sqlite'));
        $dump = $so === 'principal' ? null : ($this->option('dump') ?: self::maisNovo(config('concursos.backup.dir'), 'concursos_*.dump'));

        if ($so !== 'concursos' && !$sqlite) {
            $this->error('Não achei cópia do banco principal (BACKUP_DRIVE_DIR/db/financeiro_*.sqlite). O Google Drive está aberto e sincronizado?');

            return self::FAILURE;
        }
        if ($so !== 'principal' && !$dump) {
            $this->error('Não achei cópia do banco de concursos (CONCURSOS_BACKUP_DIR/db/concursos_*.dump).');

            return self::FAILURE;
        }

        $this->line('Vou restaurar:');
        foreach (array_filter(['Banco principal' => $sqlite, 'Banco de concursos' => $dump]) as $nome => $arq) {
            $this->line(sprintf('  %s: %s (de %s)', $nome, $arq, date('d/m/Y H:i', filemtime($arq))));
        }
        $this->warn('O que está nos bancos agora será substituído (uma cópia de segurança é guardada antes). Pare o servidor do app antes.');
        if (!$this->option('force') && !$this->confirm('Continuar?')) {
            return self::FAILURE;
        }

        $ok = true;
        if ($sqlite) {
            $ok = $this->restaurarSqlite($sqlite) && $ok;
        }
        if ($dump) {
            $ok = $this->restaurarPostgres($dump) && $ok;
        }
        if ($ok) {
            $this->info('Restauração concluída. Rode "php artisan migrate" para aplicar migrações mais novas que a cópia, se houver.');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    /** Arquivo mais novo (pelo nome, que tem a data) que casa com $padrao em <dir>/db. */
    public static function maisNovo(?string $dir, string $padrao): ?string
    {
        $dir = rtrim((string) $dir, '\\/');
        if ($dir === '') {
            return null;
        }
        $arquivos = glob($dir . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . $padrao) ?: [];
        sort($arquivos);

        return $arquivos ? end($arquivos) : null;
    }

    private function restaurarSqlite(string $origem): bool
    {
        $cfg = config('database.connections.' . config('database.default'));
        if (($cfg['driver'] ?? null) !== 'sqlite' || ($cfg['database'] ?? ':memory:') === ':memory:') {
            $this->error('O banco principal não é um arquivo SQLite; nada a fazer.');

            return false;
        }
        if (file_get_contents($origem, false, null, 0, 16) !== "SQLite format 3\0") {
            $this->error("{$origem} não é um arquivo SQLite.");

            return false;
        }
        $destino = $cfg['database'];
        DB::disconnect();

        if (is_file($destino)) {
            $guarda = dirname($destino) . DIRECTORY_SEPARATOR . 'database.antes-restaurar-' . now()->format('Ymd_His') . '.sqlite';
            File::copy($destino, $guarda);
            $this->line("  Banco atual guardado em {$guarda}");
        }
        // Copia para um temporário ao lado e troca de uma vez; os arquivos -wal/-shm são do banco antigo.
        $temp = $destino . '.restaurando';
        File::copy($origem, $temp);
        foreach (['-wal', '-shm'] as $sufixo) {
            @unlink($destino . $sufixo);
        }
        if (!@rename($temp, $destino)) {
            @unlink($temp);
            $this->error("Não consegui substituir {$destino}. O servidor do app ainda está aberto?");

            return false;
        }
        $n = DB::table('sqlite_master')->where('type', 'table')->count();
        $this->info("  Banco principal restaurado ({$n} tabelas).");

        return true;
    }

    private function restaurarPostgres(string $dump): bool
    {
        $cfg = config('database.connections.' . config('concursos.connection'));
        if (($cfg['driver'] ?? null) !== 'pgsql') {
            $this->error('A conexão de concursos não é Postgres; nada a fazer.');

            return false;
        }
        $bin = rtrim((string) config('concursos.backup.pg_bin'), '\\/');
        $exe = fn (string $nome) => $bin !== '' ? $bin . DIRECTORY_SEPARATOR . $nome : $nome;
        $conexao = ['--host=' . $cfg['host'], '--port=' . $cfg['port'], '--username=' . $cfg['username']];
        $env = ['PGPASSWORD' => (string) $cfg['password']];

        // Guarda o que existe (num PC novo o banco está vazio e o dump sai pequeno).
        $pasta = storage_path('app' . DIRECTORY_SEPARATOR . 'antes-restaurar');
        File::ensureDirectoryExists($pasta);
        $guarda = $pasta . DIRECTORY_SEPARATOR . 'concursos_' . now()->format('Ymd_His') . '.dump';
        $p = new Process([$exe('pg_dump'), '--format=custom', '--no-owner', '--file=' . $guarda, ...$conexao, $cfg['database']], null, $env, null, 900);
        $p->run();
        if (!$p->isSuccessful()) {
            $this->error('Não consegui guardar o banco de concursos atual (o banco "' . $cfg['database'] . '" existe? Rode scripts\\concursos-setup.ps1 antes): ' . trim($p->getErrorOutput()));

            return false;
        }
        $this->line("  Banco de concursos atual guardado em {$guarda}");

        $p = new Process([$exe('pg_restore'), '--clean', '--if-exists', '--no-owner', '--no-privileges', '--single-transaction',
            ...$conexao, '--dbname=' . $cfg['database'], $dump], null, $env, null, 1800);
        $p->run();
        if (!$p->isSuccessful()) {
            $this->error('pg_restore falhou (nada foi alterado, a restauração é numa transação só): ' . trim($p->getErrorOutput()));

            return false;
        }
        DB::purge(config('concursos.connection'));
        $n = count(DB::connection(config('concursos.connection'))->select('select 1 from pg_tables where schemaname = current_schema()'));
        $this->info("  Banco de concursos restaurado ({$n} tabelas).");

        return true;
    }
}
