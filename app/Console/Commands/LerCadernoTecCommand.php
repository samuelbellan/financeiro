<?php

namespace App\Console\Commands;

use App\Services\Concursos\TecCadernoParser;
use App\Services\Concursos\TecParserException;
use App\Services\Concursos\TecPdfExtractor;
use Illuminate\Console\Command;

/**
 * Lê um PDF de caderno do TEC e mostra o resultado, sem gravar nada.
 * Serve para conferir um PDF antes da importação e para gerar o JSON das questões.
 *   php artisan concursos:ler-pdf "C:\caminho\caderno.pdf"
 *   php artisan concursos:ler-pdf caderno.pdf --json=questoes.json
 */
class LerCadernoTecCommand extends Command
{
    protected $signature = 'concursos:ler-pdf
                            {arquivo : PDF do caderno (Chrome: "Salvar como PDF", com gabarito)}
                            {--json= : Salva as questões lidas neste arquivo JSON}';

    protected $description = 'Lê um caderno de questões do TEC em PDF e confere questões x gabarito (não grava no banco).';

    public function handle(TecPdfExtractor $extrator, TecCadernoParser $parser): int
    {
        try {
            $x = $extrator->extrair($this->argument('arquivo'));
            $r = $parser->parse($x['paginas'], $x['imagens']);
        } catch (TecParserException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Caderno: {$r['caderno']['titulo']} (id {$r['caderno']['id']})");

        $q = collect($r['questoes']);
        $this->table(['#', 'id TEC', 'Banca', 'Ano', 'Tipo', 'Gabarito', 'Assunto'], $q->map(fn ($x) => [
            $x['numero'], $x['id_externo'], $x['banca_sigla'], $x['ano'], $x['tipo'],
            $x['anulada'] ? 'ANULADA' : $x['gabarito'], $x['assuntos'][0]['disciplina'] . ' - ' . $x['assuntos'][0]['assunto'],
        ]));

        $this->line(sprintf(
            'Total: %d questões (%d múltipla escolha, %d certo/errado, %d anuladas). Gabarito conferido.',
            $q->count(), $q->where('tipo', 'ME')->count(), $q->where('tipo', 'CE')->count(), $q->where('anulada', true)->count()
        ));
        foreach ($r['avisos'] as $aviso) {
            $this->warn($aviso);
        }

        if ($destino = $this->option('json')) {
            file_put_contents($destino, json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->info("JSON salvo em {$destino}");
        }

        return self::SUCCESS;
    }
}
