<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Baixa as tabelas do módulo de estudos da nuvem (fonte única da verdade) para um JSON no PC.
 * Somente leitura na nuvem. Mantém os N backups mais recentes.
 */
class BackupEstudosCommand extends Command
{
    protected $signature = 'estudos:backup
                            {--connection=pgsql_cloud : Conexão de origem dos dados}
                            {--keep=30 : Quantos backups manter}
                            {--dir= : Pasta de destino (padrão: storage/app/backups/estudos)}';

    protected $description = 'Faz backup local (JSON) dos dados do módulo de estudos que ficam na nuvem.';

    public const TABLES = ['study_subjects', 'study_sessions', 'study_settings', 'study_ddays'];

    public function handle(): int
    {
        $connection = $this->option('connection');

        // Sem conexão com a nuvem configurada no .env, não há de onde copiar: sai sem erro
        // (o iniciar.bat chama este comando a cada inicialização).
        if ($connection === 'pgsql_cloud' && empty(config('database.connections.pgsql_cloud.url'))) {
            $this->warn('CLOUD_DATABASE_URL não configurada no .env: backup automático ignorado. Use "Exportar backup" no app de estudos.');
            return self::SUCCESS;
        }

        try {
            $db = DB::connection($connection);
            $data = [];
            foreach (self::TABLES as $table) {
                $data[$table] = $db->table($table)->get()->map(fn ($row) => (array) $row)->all();
            }
        } catch (\Throwable $e) {
            $this->error('Não foi possível ler os estudos da nuvem: ' . $e->getMessage());
            return self::FAILURE;
        }

        $dir = $this->option('dir') ?: storage_path('app/backups/estudos');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = $dir . '/estudos_' . now()->format('Ymd_His') . '.json';
        file_put_contents($file, json_encode([
            'gerado_em' => now()->toIso8601String(),
            'origem' => $connection,
            'tabelas' => $data,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->pruneOldBackups($dir, max(1, (int) $this->option('keep')));

        $counts = collect($data)->map(fn ($rows, $table) => "{$table}: " . count($rows))->implode(', ');
        $this->info("Backup salvo em {$file} ({$counts})");

        return self::SUCCESS;
    }

    protected function pruneOldBackups(string $dir, int $keep): void
    {
        $files = glob($dir . '/estudos_*.json') ?: [];
        rsort($files);
        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }
    }
}
