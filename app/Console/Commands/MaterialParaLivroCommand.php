<?php

namespace App\Console\Commands;

use App\Models\Concursos\Material;
use App\Services\Concursos\Livro\ConversorLivro;
use Illuminate\Console\Command;

/**
 * Monta o livro (capítulos por assunto) dos materiais de resumo que ainda estão em seções de caderno.
 *   php artisan concursos:material-livro          (todos)
 *   php artisan concursos:material-livro ctbga    (um, pelo slug ou id)
 * Não é obrigatório: abrir o livro na tela faz a mesma conversão. Pode rodar de novo sem duplicar.
 */
class MaterialParaLivroCommand extends Command
{
    protected $signature = 'concursos:material-livro {material? : slug ou id do material}';

    protected $description = 'Converte os materiais de resumo (seções e pontos) para o formato de livro com capítulos.';

    public function handle(ConversorLivro $conversor): int
    {
        if (!config('concursos.enabled')) {
            $this->error('Módulo de concursos desligado (CONCURSOS_ENABLED=false).');
            return self::FAILURE;
        }

        $qual = $this->argument('material');
        $materiais = Material::query()
            ->when($qual !== null, fn ($q) => $q->where(ctype_digit((string) $qual) ? 'id' : 'slug', $qual))
            ->orderBy('id')->get();
        if ($materiais->isEmpty()) {
            $this->error('Nenhum material encontrado.');
            return self::FAILURE;
        }

        foreach ($materiais as $m) {
            $r = $conversor->converter($m);
            $this->info("{$m->titulo}: {$r['capitulos']} capítulos novos, {$r['pontos']} pontos e {$r['questoes']} questões encaixados.");
        }

        return self::SUCCESS;
    }
}
