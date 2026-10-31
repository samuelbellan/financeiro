<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Ofx\OfxImportService;

class ImportOfxCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ofx:import 
                            {path? : Caminho do arquivo .ofx ou pasta (padrão: pasta extratos/)}
                            {--user=1 : ID do usuário proprietário dos registros}
                            {--keep : Manter o arquivo na pasta original sem mover para a pasta de processados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa extratos bancários no formato .OFX e atualiza as contas e transações';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("========================================================");
        $this->info("       IMPORTAÇÃO DE EXTRATOS BANCÁRIOS (.OFX)          ");
        $this->info("========================================================");

        $targetPath = $this->argument('path') ?: base_path('extratos');
        $userId = (int) $this->option('user');
        $keep = (bool) $this->option('keep');

        if (!file_exists($targetPath)) {
            $this->error("O caminho informado não existe: {$targetPath}");
            return self::FAILURE;
        }

        $filesToProcess = [];
        if (is_dir($targetPath)) {
            $files = glob(rtrim($targetPath, '/\\') . '/*.{ofx,OFX}', GLOB_BRACE);
            $filesToProcess = $files ?: [];
        } elseif (is_file($targetPath)) {
            $filesToProcess[] = $targetPath;
        }

        if (empty($filesToProcess)) {
            $this->warn("Nenhum arquivo .ofx encontrado em: {$targetPath}");
            $this->line("Dica: Cole seus arquivos .ofx na pasta 'extratos/' e execute novamente.");
            return self::SUCCESS;
        }

        $this->info("Arquivos encontrados para processamento: " . count($filesToProcess));
        $this->newLine();

        $processedDir = is_dir($targetPath) 
            ? rtrim($targetPath, '/\\') . DIRECTORY_SEPARATOR . 'processados'
            : dirname($targetPath) . DIRECTORY_SEPARATOR . 'processados';

        if (!$keep && !is_dir($processedDir)) {
            @mkdir($processedDir, 0755, true);
        }

        $service = new OfxImportService(DB::connection()->getPdo());
        $totalImportados = 0;
        $totalNovos = 0;
        $totalDuplicados = 0;

        foreach ($filesToProcess as $file) {
            $fileName = basename($file);
            $this->line("⏳ Processando: <comment>{$fileName}</comment>...");

            try {
                $result = $service->import($file, $userId);

                if ($result->sucesso) {
                    $this->info("  ✔ Sucesso: Conta [{$result->contaNome}] (Tipo: {$result->tipoConta})");
                    $this->line("    • Lançamentos encontrados: <comment>{$result->totalEncontrados}</comment>");
                    $this->line("    • Novos inseridos: <info>{$result->inseridos}</info>");
                    $this->line("    • Duplicados ignorados: <comment>{$result->duplicadosIgnorados}</comment>");
                    $this->line("    • Entradas (+): R$ " . number_format($result->totalCreditos, 2, ',', '.'));
                    $this->line("    • Saídas (-): R$ " . number_format($result->totalDebitos, 2, ',', '.'));
                    if ($result->saldoFinal !== null) {
                        $this->line("    • Saldo final no extrato: R$ " . number_format($result->saldoFinal, 2, ',', '.'));
                    }

                    $totalImportados++;
                    $totalNovos += $result->inseridos;
                    $totalDuplicados += $result->duplicadosIgnorados;

                    // Mover para processados
                    if (!$keep && is_dir($processedDir)) {
                        $dest = $processedDir . DIRECTORY_SEPARATOR . date('Ymd_His_') . $fileName;
                        @rename($file, $dest);
                        $this->line("    • Arquivo arquivado em: processados/");
                    }
                } else {
                    $this->error("  ✖ Falha: {$result->mensagem}");
                }
            } catch (\Throwable $e) {
                $this->error("  ✖ Erro inesperado: " . $e->getMessage());
            }

            $this->line("--------------------------------------------------------");
        }

        $this->newLine();
        $this->info("Resumo Geral da Importação:");
        $this->line("  • Arquivos processados com sucesso: {$totalImportados} de " . count($filesToProcess));
        $this->line("  • Total de novos lançamentos cadastrados: {$totalNovos}");
        $this->line("  • Lançamentos já existentes (ignorados): {$totalDuplicados}");

        return self::SUCCESS;
    }
}
