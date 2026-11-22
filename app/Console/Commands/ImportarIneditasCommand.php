<?php

namespace App\Console\Commands;

use App\Services\Concursos\IneditasImportador;
use App\Services\Concursos\TecParserException;
use Illuminate\Console\Command;

/**
 * Importa questões inéditas do Gemini (prompt resources/schemas/concursos/prompts/gemini-ineditas.md).
 *   php artisan concursos:importar-ineditas "C:\...\estatuto-1.txt" "C:\...\estatuto-2.txt"
 * Aceita arquivos ou pastas (.txt/.json/.md). Reimportar o mesmo arquivo não duplica.
 */
class ImportarIneditasCommand extends Command
{
    protected $signature = 'concursos:importar-ineditas {caminhos* : arquivos ou pastas com as respostas do Gemini}';

    protected $description = 'Importa questões inéditas (normas sem questões no TEC) geradas pelo Gemini.';

    public function handle(IneditasImportador $importador): int
    {
        if (!config('concursos.enabled')) {
            $this->error('Módulo de concursos desligado (CONCURSOS_ENABLED=false).');
            return self::FAILURE;
        }

        $arquivos = [];
        foreach ($this->argument('caminhos') as $c) {
            if (is_dir($c)) {
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($c, \FilesystemIterator::SKIP_DOTS)) as $f) {
                    if (in_array(strtolower($f->getExtension()), ['json', 'txt', 'md'], true)) {
                        $arquivos[] = $f->getPathname();
                    }
                }
            } elseif (is_file($c)) {
                $arquivos[] = $c;
            } else {
                $this->error("Não encontrei: {$c}");
                return self::FAILURE;
            }
        }
        sort($arquivos);

        $erros = 0;
        foreach ($arquivos as $arq) {
            if (trim((string) file_get_contents($arq)) === '') {
                $this->warn('VAZIO ' . basename($arq) . ': pulei.');
                continue;
            }
            try {
                $r = $importador->importarArquivo($arq);
                $this->info('OK ' . basename($arq) . ": {$r['questoes']} questões ({$r['novas']} novas) de {$r['norma']}; a norma tem agora {$r['total_da_norma']}.");
            } catch (TecParserException $e) {
                $erros++;
                $this->error('RECUSADO ' . basename($arq) . ': ' . $e->getMessage());
            }
        }

        return $erros ? self::FAILURE : self::SUCCESS;
    }
}
