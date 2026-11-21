<?php

namespace App\Console\Commands;

use App\Models\Concursos\EditalCargo;
use App\Services\Concursos\EditalImportador;
use App\Services\Concursos\TecParserException;
use Illuminate\Console\Command;

/**
 * Importa um edital verticalizado (JSON no formato edital.schema.json, um cargo por arquivo).
 *   php artisan concursos:importar-edital resources/schemas/concursos/exemplos/edital-ufms-152-2026-cargo-523.json
 * Pode ser reimportado depois de preencher assuntos_sugeridos: atualiza sem duplicar.
 */
class ImportarEditalCommand extends Command
{
    protected $signature = 'concursos:importar-edital {arquivo : JSON do edital (um cargo)}';

    protected $description = 'Importa o edital verticalizado de um cargo para o banco de concursos.';

    public function handle(EditalImportador $importador): int
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

        $s = $r['resumo'];
        $this->info("Edital {$s['edital']} | cargo {$s['cargo']} | prova em {$s['data_prova']}");

        $cargo = EditalCargo::with('areas.disciplinas.topicos.assuntos')->find($r['cargo_id']);
        $this->table(['Área', 'Questões', 'Pontos/questão', 'Pontos', 'Disciplinas', 'Tópicos', 'Com assunto TEC'], $cargo->areas->map(function ($a) {
            $topicos = $a->disciplinas->flatMap->topicos;
            $comAssunto = $topicos->filter(fn ($t) => $t->assuntos->isNotEmpty())->count();

            return [$a->nome, $a->n_questoes, $a->pontos_por_questao, $a->n_questoes * $a->pontos_por_questao,
                $a->disciplinas->count(), $topicos->count(), "{$comAssunto}/{$topicos->count()}"];
        }));

        $this->line("Total: {$s['areas']} áreas, {$s['disciplinas']} disciplinas, {$s['topicos']} tópicos, {$s['pontos_totais']} pontos.");
        $comDisciplina = array_filter($s['assuntos_criados'], fn ($a) => !str_starts_with($a, \App\Services\Concursos\AssuntoResolver::DISCIPLINA_A_CONFIRMAR));
        foreach ($comDisciplina as $a) {
            $this->line("  + assunto novo: {$a}");
        }
        if ($s['assuntos_a_confirmar'] ?? 0) {
            $this->line("  + {$s['assuntos_a_confirmar']} assunto(s) novo(s) só com o nome: a disciplina do TEC é preenchida sozinha quando o primeiro caderno daquele assunto for importado.");
        }
        foreach ($s['assuntos_ambiguos'] ?? [] as $a) {
            $this->warn("  ? \"{$a}\" existe em mais de uma disciplina: informe {disciplina, assunto} no JSON para esse tópico.");
        }
        if ($s['topicos_sem_questoes_tec']) {
            $this->line("  {$s['topicos_sem_questoes_tec']} tópico(s) de norma própria (sem questões no TEC): lei seca e questões inéditas.");
        }
        if ($s['topicos_sem_assunto']) {
            $this->warn("  {$s['topicos_sem_assunto']} tópico(s) ainda sem assunto do TEC: preencha assuntos_sugeridos e reimporte.");
        }

        return self::SUCCESS;
    }
}
