<?php

namespace App\Console\Commands;

use App\Services\FiscalNewsCrawlerService;
use App\Services\FiscalTelegramNotifierService;
use Illuminate\Console\Command;

class FiscalCrawlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fiscal:check-news {--notify : Disparar alertas automaticamente no Telegram para novas notícias}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rastreia notícias de concursos fiscais em tempo real e opcionalmente notifica no Telegram';

    /**
     * Execute the console command.
     */
    public function handle(
        FiscalNewsCrawlerService $crawler,
        FiscalTelegramNotifierService $notifier
    ): int {
        $this->info('Iniciando rastreamento de notícias de concursos fiscais...');

        $novas = $crawler->crawlAll();
        $totalNovas = count($novas);

        $this->info("✓ Rastreamento concluído. {$totalNovas} novas notícias encontradas.");

        if ($this->option('notify') && $totalNovas > 0) {
            $this->info('Disparando notificações para o Telegram...');
            $enviadas = $notifier->notifyPendingNews();
            $this->info("✓ {$enviadas} notificações enviadas ao Telegram.");
        }

        return Command::SUCCESS;
    }
}
