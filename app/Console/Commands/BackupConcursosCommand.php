<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Backup diário do banco do módulo de concursos para a pasta do Google Drive.
 *  1. pg_dump (formato custom, compactado) -> <dir>/db/concursos_AAAAMMDD_HHMMSS.dump
 *  2. Espelha storage/concursos/imports (PDFs e JSONs de origem) -> <dir>/imports (só copia o que falta)
 *  3. Retenção: os N diários mais recentes + 1 por semana (o mais recente de cada semana) até M semanas.
 * Agendado no Agendador de Tarefas do Windows (scripts/concursos-setup.ps1).
 */
class BackupConcursosCommand extends Command
{
    protected $signature = 'concursos:backup {--dir= : Pasta de destino (padrão: CONCURSOS_BACKUP_DIR)}';

    protected $description = 'Faz o pg_dump do banco de concursos e copia os arquivos de origem para o Google Drive.';

    public function handle(): int
    {
        $dir = $this->option('dir') ?: config('concursos.backup.dir');
        if (!$dir) {
            $this->error('Defina CONCURSOS_BACKUP_DIR no .env (pasta do Google Drive).');
            return self::FAILURE;
        }

        $cfg = config('database.connections.' . config('concursos.connection'));
        if (($cfg['driver'] ?? null) !== 'pgsql') {
            $this->error('A conexão concursos_pgsql não é Postgres; nada a fazer.');
            return self::FAILURE;
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
            $this->error('pg_dump falhou: ' . trim($process->getErrorOutput() ?: $process->getOutput()));
            return self::FAILURE;
        }

        $copiados = $this->espelharImports($dir . DIRECTORY_SEPARATOR . 'imports');
        $removidos = self::aplicarRetencao(
            glob($dir . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'concursos_*.dump') ?: [],
            config('concursos.backup.diarios'),
            config('concursos.backup.semanais'),
        );
        foreach ($removidos as $velho) {
            @unlink($velho);
        }

        $mb = number_format(filesize($arquivo) / 1048576, 1, ',', '.');
        $this->info("Backup salvo: {$arquivo} ({$mb} MB). Imports copiados: {$copiados}. Antigos removidos: " . count($removidos) . '.');

        return self::SUCCESS;
    }

    /** Copia para o Drive os arquivos de origem que ainda não estão lá (nunca apaga nada no destino). */
    private function espelharImports(string $destino): int
    {
        $origem = storage_path('concursos/imports');
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

    /**
     * Decide quais dumps apagar. Mantém os $diarios mais recentes e, além deles,
     * o mais recente de cada uma das $semanais semanas ISO mais recentes.
     *
     * @param  string[]  $arquivos  caminhos com nome concursos_AAAAMMDD_HHMMSS.dump
     * @return string[]  caminhos a remover
     */
    public static function aplicarRetencao(array $arquivos, int $diarios, int $semanais): array
    {
        $datados = [];
        foreach ($arquivos as $a) {
            if (preg_match('/concursos_(\d{8})_(\d{6})\.dump$/', $a, $m)) {
                $datados[$a] = \DateTimeImmutable::createFromFormat('YmdHis', $m[1] . $m[2]);
            }
        }
        arsort($datados); // mais recente primeiro

        $manter = array_slice(array_keys($datados), 0, max(1, $diarios));

        $semanasVistas = [];
        foreach ($datados as $arquivo => $data) {
            $semana = $data->format('o-W');
            if (isset($semanasVistas[$semana])) {
                continue;
            }
            if (count($semanasVistas) >= $semanais) {
                break;
            }
            $semanasVistas[$semana] = true;
            $manter[] = $arquivo;
        }

        return array_values(array_diff(array_keys($datados), $manter));
    }
}
