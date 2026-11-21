<?php

namespace App\Console\Commands;

use App\Models\Concursos\Lote;
use App\Services\Concursos\GeminiImportador;
use App\Services\Concursos\TecParserException;
use Illuminate\Console\Command;

/**
 * Importa as respostas do Gemini (ledger e resoluções) de um ou mais arquivos ou pastas.
 *   php artisan concursos:importar-gemini "C:\Users\...\Concurso UFMS\Gemini"
 * Em pasta, lê todos os .json/.txt/.md (também nas subpastas). O nome dos arquivos não importa:
 * o tipo e o lote saem do conteúdo. Pacotes (contexto) que estejam na pasta são ignorados.
 */
class ImportarGeminiCommand extends Command
{
    protected $signature = 'concursos:importar-gemini {caminhos* : arquivos ou pastas com as respostas do Gemini}';

    protected $description = 'Importa ledger e resoluções devolvidos pelo Gemini para os lotes.';

    public function handle(GeminiImportador $importador): int
    {
        if (!config('concursos.enabled')) {
            $this->error('Módulo de concursos desligado (CONCURSOS_ENABLED=false).');
            return self::FAILURE;
        }

        $arquivos = [];
        foreach ($this->argument('caminhos') as $c) {
            if (is_dir($c)) {
                $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($c, \FilesystemIterator::SKIP_DOTS));
                foreach ($it as $f) {
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

        $recusados = 0;
        $lotes = [];
        foreach ($arquivos as $arq) {
            $nome = basename(dirname($arq)) . DIRECTORY_SEPARATOR . basename($arq);
            if (trim((string) file_get_contents($arq)) === '') {
                $this->warn("VAZIO {$nome}: pulei (cole a resposta do Gemini e rode de novo).");
                continue;
            }
            if ($importador->ler($arq)['tipo'] === 'contexto') {
                continue;
            }
            try {
                $r = $importador->importarArquivo($arq);
            } catch (TecParserException $e) {
                $recusados++;
                $this->error("RECUSADO {$nome}: {$e->getMessage()}");
                continue;
            }
            $lotes[$r['lote_id']] = true;
            $this->info("OK {$nome}: {$r['tipo']} de {$r['questoes']} questões ({$r['lote']})");
            foreach ($r['subtopicos_novos'] as $s) {
                $this->line("   + subtópico pendente: {$s}");
            }
            foreach ($r['flags'] as $id => $flags) {
                $this->warn("   ! questão {$id}: " . implode(', ', $flags));
            }
        }

        if ($lotes) {
            $this->newLine();
            $this->table(['Lote', 'Questões', 'Situação'], Lote::with('assunto')->withCount('questoes')->findMany(array_keys($lotes))
                ->map(fn ($l) => [mb_strimwidth("{$l->assunto->nome} - lote {$l->numero}", 0, 80, '…'), $l->questoes_count, match ($l->status) {
                    'completo' => 'completo', 'ledger' => 'falta resolução', default => 'falta ledger',
                }]));
        }
        $this->line(count($lotes) . ' lote(s) atualizados, ' . $recusados . ' arquivo(s) recusado(s).');

        return $recusados ? self::FAILURE : self::SUCCESS;
    }
}
