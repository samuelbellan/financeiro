<?php

namespace App\Console\Commands;

use App\Services\Concursos\Livro\BaseTeorica;
use Illuminate\Console\Command;

/**
 * Põe um resumo em Markdown no livro e grifa os trechos das questões erradas (ver BaseTeorica).
 *   php artisan concursos:material-base storage/concursos/materiais/ctbg-modulo1.plano.json
 * O plano aponta o Markdown em "arquivo" (caminho relativo ao próprio plano). Pode rodar de novo.
 */
class MaterialBaseTeoricaCommand extends Command
{
    protected $signature = 'concursos:material-base {plano : JSON com material, arquivo, fonte, secoes e destaques}
        {--refazer : troca o texto do resumo que já está nos capítulos pela versão atual do Markdown (rode os destaques de novo depois)}';

    protected $description = 'Coloca um resumo teórico (Markdown) nos capítulos do livro e grifa os trechos das questões erradas.';

    public function handle(BaseTeorica $base): int
    {
        if (!config('concursos.enabled')) {
            $this->error('Módulo de concursos desligado (CONCURSOS_ENABLED=false).');
            return self::FAILURE;
        }

        $caminho = $this->argument('plano');
        $plano = is_file($caminho) ? json_decode((string) file_get_contents($caminho), true) : null;
        if (!is_array($plano)) {
            $this->error("Plano não encontrado ou JSON inválido: {$caminho}");
            return self::FAILURE;
        }
        // Plano só de destaques (sem seções) não precisa de Markdown: grifa o que já está no livro.
        $md = '';
        if (!empty($plano['secoes'])) {
            $arquivo = dirname($caminho) . DIRECTORY_SEPARATOR . ($plano['arquivo'] ?? '');
            if (!is_file($arquivo)) {
                $this->error("Markdown não encontrado: {$arquivo}");
                return self::FAILURE;
            }
            $md = (string) file_get_contents($arquivo);
        }

        $r = $base->aplicar($plano, $md, (bool) $this->option('refazer'));
        $this->info("{$r['secoes']} seções em {$r['capitulos']} capítulos novos; {$r['destaques']} alternativas grifadas.");
        foreach ($r['sem_trecho'] as $s) {
            $this->warn("Trecho não achado: {$s}");
        }

        return self::SUCCESS;
    }
}
