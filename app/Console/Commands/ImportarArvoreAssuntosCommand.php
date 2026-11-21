<?php

namespace App\Console\Commands;

use App\Models\Concursos\EditalTopico;
use App\Services\Concursos\ArvoreAssuntosImportador;
use App\Services\Concursos\TecParserException;
use Illuminate\Console\Command;

/**
 * Importa a árvore de assuntos de um caderno do TEC (copiada da página do caderno para um .txt).
 *   php artisan concursos:importar-arvore "C:\...\arvore-legislacao.txt"
 * Depois mostra, para os tópicos do edital afetados, quantas questões cada um passou a alcançar.
 */
class ImportarArvoreAssuntosCommand extends Command
{
    protected $signature = 'concursos:importar-arvore {arquivo : .txt/.md com a árvore de assuntos do caderno}';

    protected $description = 'Importa a hierarquia de assuntos do TEC (disciplina → assunto → sub-assunto).';

    public function handle(ArvoreAssuntosImportador $importador): int
    {
        if (!config('concursos.enabled')) {
            $this->error('Módulo de concursos desligado (CONCURSOS_ENABLED=false).');
            return self::FAILURE;
        }

        try {
            $r = $importador->importarArquivo($this->argument('arquivo'));
        } catch (TecParserException $e) {
            $this->error('RECUSADO: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Árvore importada: ' . implode(', ', $r['disciplinas']) . " | {$r['assuntos']} assuntos, {$r['pai_alterado']} posicionados na hierarquia, {$r['com_contagem']} com contagem do TEC (caderno \"{$r['caderno']}\").");
        foreach ($r['assuntos_adotados'] as $a) {
            $this->line("  = assunto do edital agora com disciplina: {$a}");
        }
        foreach ($r['assuntos_criados'] as $a) {
            $this->line("  + assunto novo (sem questões ainda): {$a}");
        }

        $linhas = EditalTopico::with('disciplina', 'assuntos')->get()
            ->filter(fn ($t) => $t->assuntos->isNotEmpty())
            ->map(fn ($t) => [$t->disciplina->nome, $t->ordem, mb_strimwidth($t->texto, 0, 70, '…'), $t->questoesQuery()->count()])
            ->filter(fn ($l) => $l[3] > 0)
            ->values();
        if ($linhas->isNotEmpty()) {
            $this->table(['Disciplina do edital', 'Tópico', 'Texto', 'Questões'], $linhas);
        }

        return self::SUCCESS;
    }
}
