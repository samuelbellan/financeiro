<?php

namespace App\Console\Commands;

use App\Support\Dados;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Backup cruzado entre o PC e o Google Drive (agendado de hora em hora).
 *  1. Banco: o SQLite em uso fica no PC. Uma cópia consistente (VACUUM INTO) vai para
 *     <BACKUP_DRIVE_DIR>/db/financeiro_AAAAMMDD_HHMMSS.sqlite, com retenção por idade.
 *  2. Arquivos: a pasta principal fica no Drive (DADOS_DIR). Ela é espelhada para o PC
 *     (BACKUP_LOCAL_DIR), copiando só o que falta ou mudou; nada é apagado no espelho.
 * O banco de concursos (Postgres) tem o próprio backup: concursos:backup.
 */
class BackupDriveCommand extends Command
{
    protected $signature = 'backup:drive
                            {--dir= : Pasta do Drive para as cópias do banco (padrão: BACKUP_DRIVE_DIR)}
                            {--local= : Pasta do PC para o espelho dos arquivos (padrão: BACKUP_LOCAL_DIR)}';

    protected $description = 'Copia o banco para o Google Drive e espelha os arquivos do Drive no PC.';

    public function handle(): int
    {
        $dir = rtrim((string) ($this->option('dir') ?: config('backup.dir')), '\\/');
        if ($dir === '') {
            $this->error('Defina BACKUP_DRIVE_DIR no .env (pasta do Google Drive).');
            return self::FAILURE;
        }
        if (!is_dir(dirname($dir))) {
            $this->error("A pasta do Google Drive não está acessível ({$dir}). O Google Drive para desktop está aberto?");
            return self::FAILURE;
        }

        $conexao = DB::connection();
        if ($conexao->getDriverName() !== 'sqlite') {
            $this->error('O banco principal não é SQLite; este backup só sabe copiar SQLite.');
            return self::FAILURE;
        }

        $pastaDb = $dir . DIRECTORY_SEPARATOR . 'db';
        File::ensureDirectoryExists($pastaDb);
        $arquivo = $pastaDb . DIRECTORY_SEPARATOR . 'financeiro_' . now()->format('Ymd_His') . '.sqlite';

        // A cópia é gerada no PC e só depois vai ao Drive, para o Drive nunca subir um arquivo pela metade.
        $temp = storage_path('framework' . DIRECTORY_SEPARATOR . 'backup-drive-' . getmypid() . '.sqlite');
        @unlink($temp);
        try {
            $conexao->statement('VACUUM INTO ?', [$temp]);
            if (!is_file($temp) || filesize($temp) === 0) {
                throw new \RuntimeException('cópia vazia');
            }
            File::copy($temp, $arquivo);
        } catch (\Throwable $e) {
            @unlink($arquivo);
            $this->error('Falha ao copiar o banco: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            @unlink($temp);
        }

        $removidos = self::aplicarRetencao(
            glob($pastaDb . DIRECTORY_SEPARATOR . 'financeiro_*.sqlite') ?: [],
            config('backup.recentes'),
            config('backup.diarios'),
            config('backup.semanais'),
        );
        foreach ($removidos as $velho) {
            @unlink($velho);
        }

        $mb = number_format(filesize($arquivo) / 1048576, 1, ',', '.');
        $this->info("Banco salvo no Drive: {$arquivo} ({$mb} MB). Cópias antigas removidas: " . count($removidos) . '.');

        // Arquivos: Drive -> PC. Sem DADOS_DIR os arquivos já estão no PC e não há o que espelhar.
        $origem = Dados::path();
        $local = rtrim((string) ($this->option('local') ?: config('backup.local')), '\\/');
        if (rtrim(storage_path(), '\\/') === $origem) {
            $this->line('DADOS_DIR não definido: arquivos ficam no PC, nada a espelhar.');
            return self::SUCCESS;
        }
        if (!Dados::disponivel()) {
            $this->error("A pasta principal dos arquivos não está acessível ({$origem}). O Google Drive para desktop está aberto?");
            return self::FAILURE;
        }

        $copiados = self::espelhar($origem, $local);
        $this->info("Arquivos do Drive espelhados em {$local}: {$copiados} copiados.");

        return self::SUCCESS;
    }

    /**
     * Copia a pasta $origem para $destino, recursivamente, só o que falta lá ou mudou
     * (tamanho diferente ou origem mais nova). Nunca apaga nada no destino.
     */
    public static function espelhar(string $origem, string $destino): int
    {
        if (!is_dir($origem)) {
            return 0;
        }

        $n = 0;
        foreach (File::allFiles($origem, true) as $f) {
            $de = $f->getPathname();
            $para = $destino . DIRECTORY_SEPARATOR . $f->getRelativePathname();
            if (is_file($para) && filesize($para) === $f->getSize() && filemtime($para) >= $f->getMTime()) {
                continue;
            }
            File::ensureDirectoryExists(dirname($para));
            File::copy($de, $para);
            $n++;
        }

        return $n;
    }

    /**
     * Decide quais cópias do banco apagar. Mantém as $recentes mais novas e, além delas, a mais
     * nova de cada um dos $diarios dias e de cada uma das $semanais semanas ISO mais recentes.
     *
     * @param  string[]  $arquivos  caminhos com nome financeiro_AAAAMMDD_HHMMSS.sqlite
     * @return string[]  caminhos a remover
     */
    public static function aplicarRetencao(array $arquivos, int $recentes, int $diarios, int $semanais): array
    {
        $datados = [];
        foreach ($arquivos as $a) {
            if (preg_match('/financeiro_(\d{8})_(\d{6})\.sqlite$/', $a, $m)) {
                $datados[$a] = \DateTimeImmutable::createFromFormat('YmdHis', $m[1] . $m[2]);
            }
        }
        arsort($datados); // mais recente primeiro

        $manter = array_slice(array_keys($datados), 0, max(1, $recentes));
        foreach ([['Y-m-d', $diarios], ['o-W', $semanais]] as [$formato, $limite]) {
            $vistos = [];
            foreach ($datados as $arq => $data) {
                $chave = $data->format($formato);
                if (isset($vistos[$chave])) {
                    continue;
                }
                if (count($vistos) >= $limite) {
                    break;
                }
                $vistos[$chave] = true;
                $manter[] = $arq;
            }
        }

        return array_values(array_diff(array_keys($datados), $manter));
    }
}
