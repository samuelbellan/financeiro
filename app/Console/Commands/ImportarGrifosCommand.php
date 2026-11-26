<?php

namespace App\Console\Commands;

use App\Models\Concursos\Material;
use App\Services\Concursos\Livro\GrifosQuestoes;
use Illuminate\Console\Command;

/**
 * Importa os grifos questão → trecho (storage/concursos/grifos/<materia>/*.jsonl) para o livro:
 *   php artisan concursos:importar-grifos dpen
 * Apaga antes os grifos automáticos da matéria (os antigos verde/rosa/azul e os desta importação);
 * os grifos feitos à mão ficam. Pode rodar de novo.
 */
class ImportarGrifosCommand extends Command
{
    protected $signature = 'concursos:importar-grifos {materia : slug do material (dpen, mfin…)}
        {--pasta= : pasta dos .jsonl (padrão storage/concursos/grifos/<materia>)}
        {--listar : mostra todos os trechos não achados}';

    protected $description = 'Grifa no resumo os trechos ligados às questões resolvidas (cor pela última tentativa).';

    public function handle(GrifosQuestoes $grifos): int
    {
        if (! config('concursos.enabled')) {
            $this->error('Módulo de concursos desligado (CONCURSOS_ENABLED=false).');

            return self::FAILURE;
        }
        $slug = (string) $this->argument('materia');
        $material = Material::where('slug', $slug)->first();
        if (! $material) {
            $this->error("Material não encontrado: {$slug}");

            return self::FAILURE;
        }
        $pasta = $this->option('pasta') ?: storage_path("concursos/grifos/{$slug}");
        [$linhas, $porArquivo] = GrifosQuestoes::lerPasta($material, $pasta, storage_path('concursos/materiais'));
        if (! $linhas) {
            $this->error("Nenhuma linha .jsonl em {$pasta}");

            return self::FAILURE;
        }

        $r = $grifos->importar($material, $linhas, $porArquivo);
        $this->info("{$material->titulo}: {$r['questoes']} questões, {$r['trechos']} trechos → {$r['grifos']} grifos em {$r['capitulos']} capítulos; {$r['sem_trecho']} sem trecho (não coberto/anulada).");
        if ($r['sem_questao']) {
            $this->warn(count($r['sem_questao']).' questão(ões) do .jsonl não estão no material: '.implode(', ', array_slice($r['sem_questao'], 0, 15)));
        }
        if ($r['nao_achados']) {
            $this->warn(count($r['nao_achados']).' trecho(s) não achado(s) no livro:');
            foreach ($this->option('listar') ? $r['nao_achados'] : array_slice($r['nao_achados'], 0, 10) as $s) {
                $this->line("  {$s}");
            }
        }

        return self::SUCCESS;
    }
}
