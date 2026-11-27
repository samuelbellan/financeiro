<?php

namespace App\Console\Commands;

use App\Services\AvisoBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Backup do banco do módulo de concursos para a pasta do Google Drive. Roda de hora em hora,
 * chamado pelo backup:drive (tarefa "Financeiro - Backup Drive"); falha avisa no Telegram.
 *  1. pg_dump (formato custom, compactado) -> <dir>/db/concursos_AAAAMMDD_HHMMSS.dump
 *  2. Espelha storage/concursos/imports (PDFs e JSONs de origem) -> <dir>/imports (só copia o que falta)
 *  3. Retenção: os N mais recentes + 1 por dia por D dias + 1 por semana por S semanas
 *     (mesma regra do backup:drive, BackupDriveCommand::aplicarRetencao).
 */
class BackupConcursosCommand extends Command
{
    protected $signature = 'concursos:backup {--dir= : Pasta de destino (padrão: CONCURSOS_BACKUP_DIR)}';

    protected $description = 'Faz o pg_dump do banco de concursos e copia os arquivos de origem para o Google Drive.';

    public const NOME = 'banco de concursos (resumos, notas, questões)';

    private AvisoBackup $aviso;

    public function handle(AvisoBackup $aviso): int
    {
        $this->aviso = $aviso;
        $dir = $this->option('dir') ?: config('concursos.backup.dir');
        if (!$dir) {
            return $this->falha('Defina CONCURSOS_BACKUP_DIR no .env (pasta do Google Drive).');
        }
        if (!is_dir(dirname($dir))) {
            return $this->falha("A pasta do Google Drive não está acessível ({$dir}). O Google Drive para desktop está aberto?");
        }

        $cfg = config('database.connections.' . config('concursos.connection'));
        if (($cfg['driver'] ?? null) !== 'pgsql') {
            return $this->falha('A conexão concursos_pgsql não é Postgres; nada a fazer.');
        }

        File::ensureDirectoryExists($dir . DIRECTORY_SEPARATOR . 'db');
        $arquivo = $dir . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'concursos_' . now()->format('Ymd_His') . '.dump';

        $bin = rtrim((string) config('concursos.backup.pg_bin'), '\\/');
        $pgDump = $bin !== '' ? $bin . DIRECTORY_SEPARATOR . 'pg_dump' : 'pg_dump';

        $process = new Process(
            [$pgDump, '--format=custom', '--no-owner', '--file=' . $arquivo,
             '--host=' . $cfg['host'], '--port=' . $cfg['port'], '--username=' . $cfg['username'], $cfg['database']],
            null,
            ['PGPASSWORD' => (string) $cfg['password']],
            null,
            900
        );
        $process->run();

        if (!$process->isSuccessful() || !is_file($arquivo) || filesize($arquivo) === 0) {
            @unlink($arquivo);

            return $this->falha('pg_dump falhou: ' . trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        $copiados = $this->espelharImports($dir . DIRECTORY_SEPARATOR . 'imports');
        $removidos = self::aplicarRetencao(glob($dir . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'concursos_*.dump') ?: []);
        foreach ($removidos as $velho) {
            @unlink($velho);
        }

        $mb = number_format(filesize($arquivo) / 1048576, 1, ',', '.');
        $this->info("Backup salvo: {$arquivo} ({$mb} MB). Imports copiados: {$copiados}. Antigos removidos: " . count($removidos) . '.');
        $aviso->ok(self::NOME);

        return self::SUCCESS;
    }

    /** Copia para o Drive os arquivos de origem que ainda não estão lá (nunca apaga nada no destino). */
    private function espelharImports(string $destino): int
    {
        $origem = \App\Support\Dados::path('concursos/imports');
        if (!is_dir($origem)) {
            return 0;
        }

        $n = 0;
        foreach (File::allFiles($origem) as $f) {
            $alvo = $destino . DIRECTORY_SEPARATOR . $f->getRelativePathname();
            if (is_file($alvo) && filesize($alvo) === $f->getSize()) {
                continue;
            }
            File::ensureDirectoryExists(dirname($alvo));
            File::copy($f->getPathname(), $alvo);
            $n++;
        }

        return $n;
    }

    private function falha(string $motivo): int
    {
        $this->error($motivo);
        $this->aviso->falhou(self::NOME, $motivo);

        return self::FAILURE;
    }

    /**
     * Dumps a apagar: os N mais recentes ficam, mais 1 por dia por D dias e 1 por semana por S semanas.
     *
     * @param  string[]  $arquivos  caminhos com nome concursos_AAAAMMDD_HHMMSS.dump
     * @return string[]  caminhos a remover
     */
    public static function aplicarRetencao(array $arquivos): array
    {
        return BackupDriveCommand::aplicarRetencao(
            $arquivos,
            (int) config('concursos.backup.recentes'),
            (int) config('concursos.backup.diarios'),
            (int) config('concursos.backup.semanais'),
            'concursos',
            'dump',
        );
    }
}
