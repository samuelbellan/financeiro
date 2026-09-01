<?php

namespace App\Console\Commands;

use App\Services\FiscalConcursoDataService;
use Illuminate\Console\Command;

class FiscalSeedDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fiscal:seed-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Popula e sincroniza a base de dados de Concursos Fiscais e Remunerações Aprofundadas';

    /**
     * Execute the console command.
     */
    public function handle(FiscalConcursoDataService $service): int
    {
        $this->info('Iniciando sincronização da base de Concursos Fiscais (Federal, Estaduais e Municipais)...');

        $total = $service->syncDatabaseCatalog();

        $this->info("✓ Sucesso! {$total} concursos fiscais e carreiras tributárias sincronizados com remuneração aprofundada.");

        return Command::SUCCESS;
    }
}
