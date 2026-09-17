<?php

namespace App\Console\Commands;

use App\Services\DatabaseSyncService;
use Illuminate\Console\Command;

class SyncDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:sync {--direction=both : Direção do sincronismo: both, push, pull}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza os dados entre o banco de dados local e o PostgreSQL da Neon.tech na nuvem';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseSyncService $syncService): int
    {
        $direction = strtolower($this->option('direction') ?: 'both');

        $this->info("Iniciando rotina de sincronização ({$direction})...");
        $this->line("Testando conexão com o banco de dados na nuvem (Neon.tech)...");

        $status = $syncService->testCloudConnection();
        if (!$status['online']) {
            $this->error("Não foi possível conectar à nuvem Neon: " . $status['error']);
            return Command::FAILURE;
        }

        $this->info("Conexão com a nuvem estabelecida com sucesso!");

        if ($direction === 'pull') {
            $this->line("Baixando dados da nuvem para o banco local...");
            $result = $syncService->pull();
        } elseif ($direction === 'push') {
            $this->line("Enviando dados do banco local para a nuvem...");
            $result = $syncService->push();
        } else {
            $this->line("Executando sincronização bidirecional (Pull e Push)...");
            $result = $syncService->sync();
        }

        if (!$result['success']) {
            $this->error($result['message']);
            return Command::FAILURE;
        }

        $this->info($result['message']);

        if (isset($result['tables']) && is_array($result['tables'])) {
            $this->newLine();
            $this->line("<comment>Detalhes por tabela:</comment>");
            foreach ($result['tables'] as $table => $count) {
                if ($count > 0) {
                    $this->line(" - <info>{$table}</info>: {$count} registros");
                }
            }
        }

        return Command::SUCCESS;
    }
}
