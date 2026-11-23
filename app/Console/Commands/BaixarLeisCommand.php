<?php

namespace App\Console\Commands;

use App\Services\Concursos\Leis\AcervoLeis;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Baixa as leis do catálogo (config concursos.leis.catalogo) do projeto ctresb/leis.
 *   php artisan concursos:baixar-leis            (todas as que faltam)
 *   php artisan concursos:baixar-leis ctn cf --atualizar
 */
class BaixarLeisCommand extends Command
{
    protected $signature = 'concursos:baixar-leis {leis?* : siglas do catálogo (padrão: todas)} {--atualizar : baixa de novo mesmo se já existir}';

    protected $description = 'Baixa as leis em Markdown (github.com/ctresb/leis) usadas pelo material de resumo.';

    public function handle(AcervoLeis $acervo): int
    {
        $leis = $this->argument('leis') ?: array_keys($acervo->catalogo());
        $falhas = 0;
        $feitos = [];
        foreach ($leis as $lei) {
            if (!$acervo->existe($lei)) {
                $this->error("{$lei}: não está no catálogo.");
                $falhas++;
                continue;
            }
            $arquivo = $acervo->arquivoDe($lei);
            if (isset($feitos[$arquivo])) {
                continue;
            }
            $feitos[$arquivo] = true;
            try {
                $bytes = $acervo->baixar($lei, (bool) $this->option('atualizar'));
                $this->info(sprintf('OK %s (%s KB)', $arquivo, number_format($bytes / 1024, 0, ',', '.')));
            } catch (RuntimeException $e) {
                $this->error($e->getMessage());
                $falhas++;
            }
        }

        return $falhas ? self::FAILURE : self::SUCCESS;
    }
}
