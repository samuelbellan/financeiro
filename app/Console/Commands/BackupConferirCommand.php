<?php

namespace App\Console\Commands;

use App\Services\AvisoBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Rede de segurança do backup de hora em hora (tarefa própria, a cada 3 h). Se a cópia mais nova
 * de algum banco no Drive passou do limite, roda o backup:drive na hora. Atraso isolado (PC
 * dormindo, por exemplo) só é corrigido; atraso em duas conferências seguidas quer dizer que a
 * tarefa de hora em hora não está rodando, e aí avisa no Telegram.
 */
class BackupConferirCommand extends Command
{
    protected $signature = 'backup:conferir {--limite= : Horas sem backup que contam como atraso (padrão: BACKUP_ATRASO_HORAS)}';

    protected $description = 'Confere se os backups no Drive estão em dia; se não, roda o backup e avisa no Telegram.';

    public const CHAVE_ATRASOS = 'backup-conferir:atrasos';

    public function handle(AvisoBackup $aviso): int
    {
        $limite = (float) ($this->option('limite') ?: config('backup.atraso_horas'));
        $atrasados = [];
        foreach (self::situacao() as $b) {
            $idade = $b['horas'] === null ? 'nenhuma cópia' : 'última há ' . self::horas($b['horas']);
            $this->line("{$b['nome']}: {$idade}");
            if ($b['horas'] === null || $b['horas'] > $limite) {
                $atrasados[] = "{$b['nome']} ({$idade})";
            }
        }

        if (!$atrasados) {
            Cache::forget(self::CHAVE_ATRASOS);
            $this->info('Backups em dia.');

            return self::SUCCESS;
        }

        $vezes = (int) Cache::get(self::CHAVE_ATRASOS, 0) + 1;
        Cache::forever(self::CHAVE_ATRASOS, $vezes);
        $this->warn('Atrasado: ' . implode('; ', $atrasados) . '. Rodando o backup agora…');
        // Falha do backup em si já avisa no Telegram, pelo próprio backup:drive.
        $ok = $this->call('backup:drive') === self::SUCCESS;

        if ($vezes >= 2) {
            $aviso->avisar("⚠️ O backup de hora em hora não está rodando sozinho ({$vezes} conferências seguidas com atraso).\n"
                . implode("\n", $atrasados) . "\n"
                . ($ok ? 'Rodei o backup agora e deu certo, mas confira a tarefa "Financeiro - Backup Drive" no Agendador de Tarefas.' : 'Tentei rodar agora e falhou.'));
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Idade da cópia mais nova de cada banco no Drive.
     *
     * @return list<array{nome: string, pasta: string, horas: ?float}>
     */
    public static function situacao(): array
    {
        $lista = [];
        if ($dir = rtrim((string) config('backup.dir'), '\\/')) {
            $lista[] = ['nome' => 'Banco principal', 'pasta' => $dir . DIRECTORY_SEPARATOR . 'db', 'padrao' => 'financeiro_*.sqlite'];
        }
        if (BackupDriveCommand::concursosConfigurado()) {
            $lista[] = ['nome' => 'Banco de concursos', 'pasta' => rtrim(config('concursos.backup.dir'), '\\/') . DIRECTORY_SEPARATOR . 'db', 'padrao' => 'concursos_*.dump'];
        }

        return array_map(function ($b) {
            $mais = 0;
            foreach (glob($b['pasta'] . DIRECTORY_SEPARATOR . $b['padrao']) ?: [] as $f) {
                $mais = max($mais, (int) @filemtime($f));
            }

            return ['nome' => $b['nome'], 'pasta' => $b['pasta'], 'horas' => $mais ? round((time() - $mais) / 3600, 1) : null];
        }, $lista);
    }

    public static function horas(float $h): string
    {
        return $h < 1 ? round($h * 60) . ' min' : ($h < 48 ? str_replace('.', ',', (string) round($h, 1)) . ' h' : round($h / 24) . ' dias');
    }
}
