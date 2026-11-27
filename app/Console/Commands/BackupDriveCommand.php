<?php

namespace App\Console\Commands;

use App\Services\AvisoBackup;
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
 *  3. Banco de concursos (Postgres): roda o concursos:backup (pg_dump) na mesma hora.
 *  4. Cofre de configuração: .env, .claude/launch.json e as tarefas agendadas "Financeiro - *"
 *     vão para <BACKUP_DRIVE_DIR>/config, só quando mudam (10 versões de cada). Nunca para o git.
 * Falha de qualquer parte avisa no Telegram (AvisoBackup); uma parte falhar não impede as outras.
 */
class BackupDriveCommand extends Command
{
    protected $signature = 'backup:drive
                            {--dir= : Pasta do Drive para as cópias do banco (padrão: BACKUP_DRIVE_DIR)}
                            {--local= : Pasta do PC para o espelho dos arquivos (padrão: BACKUP_LOCAL_DIR)}
                            {--sem-concursos : Não roda o backup do banco de concursos}
                            {--sem-cofre : Não copia .env e configurações para o Drive}';

    protected $description = 'Copia o banco para o Google Drive e espelha os arquivos do Drive no PC.';

    private AvisoBackup $aviso;

    public function handle(AvisoBackup $aviso): int
    {
        $this->aviso = $aviso;
        $principal = $this->principal();
        if ($principal === self::SUCCESS) {
            $aviso->ok(self::NOME);
        }

        $concursos = self::SUCCESS;
        if (!$this->option('sem-concursos') && self::concursosConfigurado()) {
            $concursos = $this->call('concursos:backup');
        }

        $cofre = $this->option('sem-cofre') ? self::SUCCESS : $this->cofre($aviso);

        return $principal === self::SUCCESS && $concursos === self::SUCCESS && $cofre === self::SUCCESS ? self::SUCCESS : self::FAILURE;
    }

    public const NOME_COFRE = 'cofre de configuração (.env e tarefas)';
    public const VERSOES_COFRE = 10;

    /** Copia para o Drive o que não está em banco nem no git e é preciso para remontar o PC. */
    private function cofre(AvisoBackup $aviso): int
    {
        $dir = rtrim((string) config('backup.dir'), '\/');
        if ($dir === '' || !is_dir(dirname($dir))) {
            return self::SUCCESS; // o banco principal já avisou que o Drive não está acessível
        }
        $pasta = $dir . DIRECTORY_SEPARATOR . 'config';
        try {
            $itens = [];
            if (is_file($env = base_path('.env'))) {
                $itens['env'] = ['txt', file_get_contents($env)];
            }
            if (is_file($launch = base_path('.claude/launch.json'))) {
                $itens['launch'] = ['json', file_get_contents($launch)];
            }
            if (($tarefas = self::exportarTarefas()) !== null) {
                $itens['tarefas'] = ['xml', $tarefas];
            }
            $novos = 0;
            foreach ($itens as $nome => [$ext, $conteudo]) {
                $novos += (int) self::guardarSeMudou($pasta, $nome, $ext, $conteudo, self::VERSOES_COFRE);
            }
        } catch (\Throwable $e) {
            $this->error('Cofre: ' . $e->getMessage());
            $aviso->falhou(self::NOME_COFRE, $e->getMessage());

            return self::FAILURE;
        }
        $aviso->ok(self::NOME_COFRE);
        $this->info("Cofre de configuração em {$pasta}: {$novos} versão(ões) nova(s).");

        return self::SUCCESS;
    }

    /**
     * Grava <pasta>/<nome>_AAAAMMDD_HHMMSS.<ext> se o conteúdo for diferente da versão mais nova,
     * e apaga as mais antigas além de $manter. Devolve se gravou.
     */
    public static function guardarSeMudou(string $pasta, string $nome, string $ext, string $conteudo, int $manter): bool
    {
        File::ensureDirectoryExists($pasta);
        $versoes = glob($pasta . DIRECTORY_SEPARATOR . "{$nome}_*.{$ext}") ?: [];
        sort($versoes); // o nome tem a data: ordem alfabética = cronológica
        $ultima = end($versoes);
        if ($ultima && hash_file('sha256', $ultima) === hash('sha256', $conteudo)) {
            return false;
        }
        $arquivo = $pasta . DIRECTORY_SEPARATOR . "{$nome}_" . now()->format('Ymd_His') . ".{$ext}";
        file_put_contents($arquivo, $conteudo);
        $versoes[] = $arquivo;
        foreach (array_slice($versoes, 0, max(0, count($versoes) - $manter)) as $velha) {
            @unlink($velha);
        }

        return true;
    }

    /** XML das tarefas agendadas "Financeiro - *" (só no Windows), para recriá-las no PC novo. */
    public static function exportarTarefas(): ?string
    {
        if (PHP_OS_FAMILY !== 'Windows' || app()->runningUnitTests()) {
            return null;
        }
        $ps = "Get-ScheduledTask -TaskName 'Financeiro - *' | ForEach-Object { '<!-- ' + \$_.TaskName + ' -->'; Export-ScheduledTask -TaskName \$_.TaskName -TaskPath \$_.TaskPath }";
        $p = new \Symfony\Component\Process\Process(['powershell', '-NoProfile', '-NonInteractive', '-Command', "[Console]::OutputEncoding = [Text.Encoding]::UTF8; {$ps}"], null, null, null, 60);
        $p->run();
        $saida = trim($p->getOutput());

        return $p->isSuccessful() && $saida !== '' ? "<tarefas>\n{$saida}\n</tarefas>\n" : null;
    }

    public const NOME = 'banco principal e arquivos';

    /** O backup do Postgres de concursos só roda quando há pasta e a conexão é Postgres. */
    public static function concursosConfigurado(): bool
    {
        return (bool) config('concursos.backup.dir')
            && (config('database.connections.' . config('concursos.connection') . '.driver')) === 'pgsql';
    }

    private function falha(string $motivo): int
    {
        $this->error($motivo);
        $this->aviso->falhou(self::NOME, $motivo);

        return self::FAILURE;
    }

    private function principal(): int
    {
        $dir = rtrim((string) ($this->option('dir') ?: config('backup.dir')), '\\/');
        if ($dir === '') {
            return $this->falha('Defina BACKUP_DRIVE_DIR no .env (pasta do Google Drive).');
        }
        if (!is_dir(dirname($dir))) {
            return $this->falha("A pasta do Google Drive não está acessível ({$dir}). O Google Drive para desktop está aberto?");
        }

        $conexao = DB::connection();
        if ($conexao->getDriverName() !== 'sqlite') {
            return $this->falha('O banco principal não é SQLite; este backup só sabe copiar SQLite.');
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
            return $this->falha('Falha ao copiar o banco: ' . $e->getMessage());
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
            return $this->falha("A pasta principal dos arquivos não está acessível ({$origem}). O Google Drive para desktop está aberto?");
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
     * Usada também pelo concursos:backup ($nome = 'concursos', $extensao = 'dump').
     *
     * @param  string[]  $arquivos  caminhos com nome <nome>_AAAAMMDD_HHMMSS.<extensao>
     * @return string[]  caminhos a remover
     */
    public static function aplicarRetencao(array $arquivos, int $recentes, int $diarios, int $semanais, string $nome = 'financeiro', string $extensao = 'sqlite'): array
    {
        $padrao = '/' . preg_quote($nome, '/') . '_(\d{8})_(\d{6})\.' . preg_quote($extensao, '/') . '$/';
        $datados = [];
        foreach ($arquivos as $a) {
            if (preg_match($padrao, $a, $m)) {
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
