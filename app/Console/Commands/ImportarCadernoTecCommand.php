<?php

namespace App\Console\Commands;

use App\Services\Concursos\TecImportador;
use App\Services\Concursos\TecParserException;
use Illuminate\Console\Command;

/**
 * Importa um ou mais cadernos do TEC (PDF) para o banco de concursos.
 *   php artisan concursos:importar-pdf "C:\cadernos\prescricao-1.pdf" "C:\cadernos\prescricao-2.pdf"
 * Cada PDF é tudo-ou-nada: se o gabarito não bater, nada daquele PDF é gravado.
 */
class ImportarCadernoTecCommand extends Command
{
    protected $signature = 'concursos:importar-pdf {arquivos* : Um ou mais PDFs de caderno do TEC}';

    protected $description = 'Importa cadernos de questões do TEC (PDF) para o banco de concursos.';

    public function handle(TecImportador $importador): int
    {
        if (!config('concursos.enabled')) {
            $this->error('Módulo de concursos desligado (CONCURSOS_ENABLED=false).');
            return self::FAILURE;
        }

        $falhas = 0;
        foreach ($this->argument('arquivos') as $arquivo) {
            $this->line("<options=bold>{$arquivo}</>");
            try {
                $r = $importador->importarPdf($arquivo);
            } catch (TecParserException $e) {
                $this->error('  RECUSADO: ' . $e->getMessage());
                $falhas++;
                continue;
            }

            $s = $r['resumo'];
            $this->info("  OK: {$s['caderno']['titulo']}: {$s['novas']} novas, {$s['atualizadas']} atualizadas (importação #{$r['importacao_id']}).");
            foreach ($s['assuntos_criados'] as $a) {
                $this->line("  + assunto novo: {$a}");
            }
            foreach ($s['assuntos_adotados'] ?? [] as $a) {
                $this->line("  = assunto do edital agora com disciplina: {$a}");
            }
            foreach ($s['gabarito_alterado'] as $g) {
                $this->warn("  ! gabarito mudou na questão {$g['id_externo']}: " . ($g['antes'] ?? 'anulada') . ' -> ' . ($g['agora'] ?? 'anulada') . ' (revise ledger/resolução)');
            }
            foreach ($s['avisos'] as $aviso) {
                $this->warn("  ! {$aviso}");
            }
        }

        return $falhas ? self::FAILURE : self::SUCCESS;
    }
}
