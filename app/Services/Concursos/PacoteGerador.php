<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Lote;
use App\Models\Concursos\Questao;
use App\Models\Concursos\Subtopico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Monta os pacotes de engenharia reversa (contexto.json) a partir das questões do banco.
 *
 * Regra de corte, percorrendo a árvore de assuntos do TEC de cima para baixo:
 *  - se o assunto (com seus sub-assuntos) tem até $max questões pendentes, vira um lote só;
 *  - senão, cada sub-assunto com mais de $min questões é tratado separadamente, e os
 *    sub-assuntos pequenos (mais as questões ligadas direto ao assunto) são agrupados em
 *    lote(s) do próprio assunto, divididos em partes iguais de até $max.
 * Ex.: "Lei 8.112" (386) → "Formas de Provimento" (67) vira 2 lotes de 34; "Férias" (5),
 * "Remoção" (6)… entram juntos num lote de "Lei 8.112".
 *
 * Pendente = questão não anulada, cujo assunto PRINCIPAL está na subárvore, que ainda não
 * entrou em nenhum lote. Assim PDFs importados depois geram só lotes novos.
 */
class PacoteGerador
{
    /** @var array<int, list<int>> assunto_id => questões pendentes ligadas direto a ele */
    private array $pendentes = [];

    /** @var array<int, list<int>> */
    private array $filhos = [];

    private array $contagem = [];

    /**
     * @param  int  $minimoLote  lotes menores ficam pendentes (esperando mais questões do assunto)
     * @param  bool  $soEdital  só assuntos cobertos por algum tópico de edital (com sub-assuntos)
     */
    public function __construct(
        private int $max = 40,
        private int $min = 10,
        private int $minimoLote = 5,
        private bool $soEdital = true,
    ) {}

    /**
     * Planeja os lotes sem gravar nada.
     *
     * @param  list<int>|null  $raizes  ids de assunto; null = todas as raízes da árvore
     * @return list<array{assunto_id: int, questoes: list<int>}>
     */
    public function planejar(?array $raizes = null): array
    {
        $this->carregar();
        $raizes ??= Assunto::whereNull('parent_id')->whereNull('unido_em_id')->orderBy('disciplina_id')->orderBy('id')->pluck('id')->all();

        $plano = [];
        foreach ($raizes as $id) {
            array_push($plano, ...$this->unidades($id));
        }

        return array_values(array_filter($plano, fn ($u) => count($u['questoes']) >= $this->minimoLote));
    }

    /**
     * Grava os lotes planejados e escreve um contexto.json por lote em $pasta.
     *
     * @return list<array{lote: Lote, arquivo: string, total: int, assunto: string}>
     */
    public function gerar(array $plano, string $pasta): array
    {
        if (!is_dir($pasta)) {
            mkdir($pasta, 0775, true);
        }

        return DB::connection(config('concursos.connection'))->transaction(function () use ($plano, $pasta) {
            $saida = [];
            foreach ($plano as $u) {
                $assunto = Assunto::with('disciplina')->findOrFail($u['assunto_id']);
                $numero = (int) Lote::where('assunto_id', $assunto->id)->max('numero') + 1;
                $nome = Str::limit(Str::slug($assunto->nome), 60, '') . "-contexto-lote-{$numero}.json";
                $dir = $pasta . DIRECTORY_SEPARATOR . Str::limit(Str::slug($assunto->disciplina->nome), 40, '');
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $arquivo = $dir . DIRECTORY_SEPARATOR . $nome;

                $lote = Lote::create(['assunto_id' => $assunto->id, 'numero' => $numero, 'arquivo' => $arquivo]);
                $lote->questoes()->attach(collect($u['questoes'])->values()->mapWithKeys(fn ($qid, $i) => [$qid => ['ordem' => $i + 1]])->all());

                file_put_contents($arquivo, json_encode($this->contexto($assunto, $numero, $u['questoes']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $saida[] = ['lote' => $lote, 'arquivo' => $arquivo, 'total' => count($u['questoes']),
                    'assunto' => "{$assunto->disciplina->nome} - {$assunto->nome}"];
            }

            return $saida;
        });
    }

    /** O contexto.json de um lote (contexto.schema.json). */
    public function contexto(Assunto $assunto, int $numero, array $questaoIds): array
    {
        $questoes = Questao::with(['alternativas', 'assuntos' => fn ($q) => $q->wherePivot('principal', true)])
            ->whereIn('id', $questaoIds)->get()->sortBy(fn ($q) => array_search($q->id, $questaoIds));

        return [
            'schema_versao' => '1',
            'gerado_em' => now()->toIso8601String(),
            'assunto' => ['disciplina' => $assunto->disciplina->nome, 'assunto' => $assunto->nome],
            'lote' => ['numero' => $numero, 'total_questoes' => count($questaoIds)],
            'subtopicos_aprovados' => Subtopico::where('assunto_id', $assunto->id)->where('status', 'aprovado')
                ->orderBy('ordem_estudo')->orderBy('id')->get()
                ->map(fn ($s) => array_filter(['id' => $s->slug, 'nome' => $s->nome, 'descricao' => $s->descricao]))->values()->all(),
            'questoes' => $questoes->map(function (Questao $q) use ($assunto) {
                $folha = $q->assuntos->first();

                return array_filter([
                    'id_externo' => $q->id_externo,
                    'banca' => $q->banca,
                    'orgao' => $q->orgao,
                    'cargo' => $q->cargo,
                    'ano' => $q->ano,
                    'tipo' => $q->tipo,
                    'gabarito' => $q->gabarito,
                    'assunto_tec' => $folha && $folha->id !== $assunto->id ? $folha->nome : null,
                    'enunciado' => $q->enunciado,
                    'alternativas' => $q->alternativas->map(fn ($a) => ['letra' => $a->letra, 'texto' => $a->texto])->all(),
                ], fn ($v) => $v !== null);
            })->values()->all(),
        ];
    }

    private function carregar(): void
    {
        $this->filhos = [];
        foreach (Assunto::whereNotNull('parent_id')->whereNull('unido_em_id')->orderBy('id')->get(['id', 'parent_id']) as $a) {
            $this->filhos[$a->parent_id][] = $a->id;
        }

        $this->pendentes = [];
        DB::connection(config('concursos.connection'))->table('questao_assunto as qa')
            ->join('questoes as q', 'q.id', '=', 'qa.questao_id')
            ->leftJoin('lote_questao as lq', 'lq.questao_id', '=', 'q.id')
            ->where('qa.principal', true)->where('q.anulada', false)->whereNull('lq.questao_id')
            ->orderBy('q.id')
            ->get(['qa.assunto_id', 'q.id'])
            ->each(function ($r) {
                $this->pendentes[$r->assunto_id][] = $r->id;
            });
        if ($this->soEdital) {
            $cobertos = Assunto::comDescendentes(
                DB::connection(config('concursos.connection'))->table('edital_topico_assunto')->distinct()->pluck('assunto_id')->all()
            );
            $this->pendentes = array_intersect_key($this->pendentes, array_flip($cobertos));
        }
        $this->contagem = [];
    }

    private function contar(int $id): int
    {
        return $this->contagem[$id] ??= count($this->pendentes[$id] ?? [])
            + array_sum(array_map(fn ($f) => $this->contar($f), $this->filhos[$id] ?? []));
    }

    /** @return list<int> */
    private function todas(int $id): array
    {
        $ids = $this->pendentes[$id] ?? [];
        foreach ($this->filhos[$id] ?? [] as $f) {
            array_push($ids, ...$this->todas($f));
        }
        sort($ids);

        return $ids;
    }

    private function unidades(int $id): array
    {
        $n = $this->contar($id);
        if ($n === 0) {
            return [];
        }
        if ($n <= $this->max) {
            return [['assunto_id' => $id, 'questoes' => $this->todas($id)]];
        }

        $saida = [];
        $agrupadas = $this->pendentes[$id] ?? [];
        foreach ($this->filhos[$id] ?? [] as $f) {
            if ($this->contar($f) > $this->min) {
                array_push($saida, ...$this->unidades($f));
            } else {
                array_push($agrupadas, ...$this->todas($f));
            }
        }
        sort($agrupadas);

        if ($agrupadas) {
            $partes = (int) ceil(count($agrupadas) / $this->max);
            foreach (array_chunk($agrupadas, (int) ceil(count($agrupadas) / $partes)) as $pedaco) {
                $saida[] = ['assunto_id' => $id, 'questoes' => $pedaco];
            }
        }

        return $saida;
    }
}
