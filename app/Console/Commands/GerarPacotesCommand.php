<?php

namespace App\Console\Commands;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Services\Concursos\ContratoJsonValidator;
use App\Services\Concursos\PacoteGerador;
use App\Services\Concursos\TecParserException;
use Illuminate\Console\Command;

/**
 * Gera os pacotes (contexto.json) para a engenharia reversa no Gemini.
 *   php artisan concursos:gerar-pacotes --simular            (só mostra o plano)
 *   php artisan concursos:gerar-pacotes --disciplina="Direito Administrativo (Doutrina e Leis Federais)"
 * Cada questão vai para um lote só: rodar de novo depois de importar mais PDFs gera apenas
 * os lotes das questões novas.
 */
class GerarPacotesCommand extends Command
{
    protected $signature = 'concursos:gerar-pacotes
        {--disciplina= : só esta disciplina (nome exato do TEC)}
        {--assunto=* : só estes assuntos e seus sub-assuntos (nome exato; pode repetir)}
        {--max=40 : questões por lote, no máximo}
        {--min=10 : sub-assunto com até isso de questões é agrupado com os irmãos}
        {--minimo-lote=5 : lote menor que isso fica esperando mais questões}
        {--todos : inclui assuntos que não estão em nenhum edital}
        {--simular : mostra o plano sem gravar}';

    protected $description = 'Gera os pacotes contexto.json (um por lote de assunto) para o Gemini.';

    public function handle(ContratoJsonValidator $validador): int
    {
        if (!config('concursos.enabled')) {
            $this->error('Módulo de concursos desligado (CONCURSOS_ENABLED=false).');
            return self::FAILURE;
        }

        $raizes = null;
        if ($nomes = $this->option('assunto')) {
            $achados = Assunto::whereIn('nome', $nomes)->pluck('nome', 'id');
            if ($faltando = array_diff($nomes, $achados->all())) {
                $this->error('Não encontrei no banco: ' . implode('; ', $faltando));
                return self::FAILURE;
            }
            $raizes = $achados->keys()->all();
        } elseif ($nome = $this->option('disciplina')) {
            $d = Disciplina::where('nome', $nome)->first();
            $raizes = $d ? Assunto::where('disciplina_id', $d->id)->whereNull('parent_id')->pluck('id')->all() : [];
        }
        if ($raizes === []) {
            $this->error("Não encontrei \"{$nome}\" no banco.");
            return self::FAILURE;
        }

        $gerador = new PacoteGerador((int) $this->option('max'), (int) $this->option('min'),
            (int) $this->option('minimo-lote'), !$this->option('todos'));
        $plano = $gerador->planejar($raizes);
        if (!$plano) {
            $this->info('Nenhuma questão pendente: tudo já foi para algum lote.');
            return self::SUCCESS;
        }

        if ($this->option('simular')) {
            $nomes = Assunto::with('disciplina')->findMany(array_column($plano, 'assunto_id'))->keyBy('id');
            $this->table(['Disciplina', 'Assunto do lote', 'Questões'], array_map(fn ($u) => [
                mb_strimwidth($nomes[$u['assunto_id']]->disciplina->nome, 0, 30, '…'),
                mb_strimwidth($nomes[$u['assunto_id']]->nome, 0, 80, '…'), count($u['questoes']),
            ], $plano));
            $this->line(count($plano) . ' lote(s), ' . array_sum(array_map(fn ($u) => count($u['questoes']), $plano)) . ' questões. Nada foi gravado (--simular).');
            return self::SUCCESS;
        }

        $pasta = storage_path('concursos' . DIRECTORY_SEPARATOR . 'pacotes' . DIRECTORY_SEPARATOR . now()->format('Y-m-d_His'));
        $gerados = $gerador->gerar($plano, $pasta);

        $erros = 0;
        foreach ($gerados as $g) {
            try {
                $validador->validar($g['arquivo'], 'contexto');
            } catch (TecParserException $e) {
                $erros++;
                $this->error("Inválido: {$g['arquivo']}: {$e->getMessage()}");
            }
        }

        $this->table(['Lote', 'Assunto', 'Questões'], array_map(fn ($g) => [
            $g['lote']->numero, mb_strimwidth($g['assunto'], 0, 90, '…'), $g['total'],
        ], $gerados));
        $this->info(count($gerados) . " pacote(s) em {$pasta}");
        $this->line('No Gemini: anexe UM pacote por conversa junto com resources/schemas/concursos/prompts/gemini-ledger-lote.md, ledger.schema.json, resolucoes.schema.json e comum.schema.json.');

        return $erros ? self::FAILURE : self::SUCCESS;
    }
}
