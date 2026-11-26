<?php

namespace App\Services\Concursos\Livro;

use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialAnotacao;
use App\Models\Concursos\MaterialCapitulo;
use App\Models\Concursos\MaterialGrifoQuestao;
use App\Models\Concursos\MaterialQuestao;
use App\Models\Concursos\MaterialTentativa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Fase 2 do plano de matérias (docs/plano-materias-resumo-questoes.md): grifos por trecho ligados
 * às questões resolvidas, vindos dos .jsonl do agente externo (storage/concursos/grifos/<materia>).
 *
 * Cada linha do .jsonl: {"id": "<nº TEC>", "trechos": [{"arquivo", "trecho", "letra", "por_que"}], "sem_trecho"}.
 * Um trecho vira um grifo (marca "g" + 12 hex); questões diferentes com o mesmo trecho caem no
 * mesmo grifo. A cor não é guardada de vez: ao abrir o capítulo, ela sai da última tentativa:
 *   rosa (errando) = errou a última;  amarelo = errou antes e acertou a última;  verde = só acertou.
 * A nota da margem é refeita na mesma hora; o que o aluno escreveu acima da régua fica.
 *
 * Importar de novo apaga os grifos automáticos da matéria (os "q…" antigos do BaseTeorica e os
 * "g…" desta classe) e as notas deles; grifos feitos à mão no app (id "n…") ficam.
 */
class GrifosQuestoes
{
    public const SEPARADOR = "\n\n— Questões que caem aqui —\n\n";

    private const AUTOMATICO = '/^[qg][0-9a-f]{12}$/';

    public function __construct(
        private readonly Documento $documento,
        private readonly Grifador $grifador,
        private readonly MarkdownLivro $markdown,
    ) {}

    /**
     * @param  list<array>  $linhas  linhas já lidas dos .jsonl
     * @param  array<string, list<int>>  $capitulosPorArquivo  "dpen-modulo1.md" => [ids de capítulo onde procurar primeiro]
     * @return array{questoes: int, sem_questao: list<string>, trechos: int, grifos: int, nao_achados: list<string>, sem_trecho: int, capitulos: int}
     */
    public function importar(Material $material, array $linhas, array $capitulosPorArquivo = []): array
    {
        return DB::connection('concursos_pgsql')->transaction(function () use ($material, $linhas, $capitulosPorArquivo) {
            $r = ['questoes' => 0, 'sem_questao' => [], 'trechos' => 0, 'grifos' => 0, 'nao_achados' => [], 'sem_trecho' => 0, 'capitulos' => 0];

            $capitulos = $material->capitulos()->orderBy('ordem')->orderBy('id')->get()->keyBy('id');
            $docs = $capitulos->map(fn ($c) => $this->tirarAutomaticos($this->documento->limpar($c->conteudo)))->all();
            $capIds = $capitulos->keys()->all();
            MaterialGrifoQuestao::whereIn('capitulo_id', $capIds)->delete();
            MaterialAnotacao::whereIn('id', MaterialAnotacao::whereIn('capitulo_id', $capIds)->get(['id', 'marca'])
                ->filter(fn ($a) => preg_match(self::AUTOMATICO, $a->marca))->pluck('id'))->delete();

            $questoes = MaterialQuestao::where('material_id', $material->id)->whereNotNull('id_externo')->get()->keyBy(fn ($q) => (string) $q->id_externo);
            $vistos = [];
            $ligacoes = []; // "capId|marca" => [questao_id => [letra, por_que, trecho]]
            foreach ($linhas as $l) {
                $tec = (string) ($l['id'] ?? '');
                if ($tec === '' || isset($vistos[$tec])) {
                    continue;
                }
                $vistos[$tec] = true;
                $q = $questoes->get($tec);
                if (! $q) {
                    $r['sem_questao'][] = $tec;

                    continue;
                }
                $r['questoes']++;
                if (empty($l['trechos'])) {
                    $r['sem_trecho']++;

                    continue;
                }
                foreach ($l['trechos'] as $t) {
                    $trecho = trim((string) ($t['trecho'] ?? ''));
                    if ($trecho === '') {
                        continue;
                    }
                    $r['trechos']++;
                    $id = 'g'.substr(md5(Str::lower(preg_replace('/\s+/u', ' ', $trecho))), 0, 12);
                    $usado = null;
                    // 1ª tentativa: blocos do markdown; 2ª: cada linha à parte (lei citada logo abaixo do texto, em outro bloco no livro).
                    foreach ([false, true] as $porLinha) {
                        if ($usado !== null) {
                            break;
                        }
                        $pedacos = $this->pedacos($trecho, $porLinha);
                        foreach ($this->ordemDeBusca($capIds, $capitulosPorArquivo[$t['arquivo'] ?? ''] ?? []) as $capId) {
                            // O trecho é markdown cru (fórmula, negrito, lista em várias linhas): cada bloco
                            // convertido é grifado à parte, com a mesma marca; vale o capítulo do 1º que achar.
                            $doc = $docs[$capId];
                            foreach ($pedacos as $p) {
                                [$doc, $u] = $this->grifador->grifar($doc, $p, $usado ?? $id, 'amarelo');
                                $usado ??= $u;
                            }
                            if ($usado !== null) {
                                $docs[$capId] = $doc;
                                $ligacoes["{$capId}|{$usado}"][$q->id] ??= [
                                    strtoupper(substr((string) ($t['letra'] ?? ''), 0, 1)) ?: null,
                                    trim((string) ($t['por_que'] ?? '')) ?: null,
                                    $trecho,
                                ];
                                break;
                            }
                        }
                    }
                    if ($usado === null) {
                        $r['nao_achados'][] = "Q{$tec} ({$t['arquivo']}): “".Str::limit($trecho, 70).'”';
                    }
                }
            }

            $agora = now();
            foreach ($ligacoes as $chave => $qs) {
                [$capId, $marca] = explode('|', $chave, 2);
                $linhasBanco = [];
                foreach ($qs as $qid => [$letra, $porQue]) {
                    $linhasBanco[] = ['capitulo_id' => (int) $capId, 'marca' => $marca, 'questao_id' => $qid, 'letra' => $letra, 'por_que' => $porQue, 'created_at' => $agora, 'updated_at' => $agora];
                }
                MaterialGrifoQuestao::insert($linhasBanco);
                // A nota guarda o trecho; texto e cor são refeitos ao abrir o capítulo (aplicar()).
                $existente = MaterialAnotacao::where('capitulo_id', $capId)->where('marca', $marca)->first();
                MaterialAnotacao::updateOrCreate(
                    ['capitulo_id' => $capId, 'marca' => $marca],
                    ['cor' => $existente->cor ?? 'amarelo', 'trecho' => Str::limit(reset($qs)[2], 1990, ''), 'nota' => $existente->nota ?? '…'],
                );
                $r['grifos']++;
            }

            foreach ($docs as $capId => $doc) {
                $cap = $capitulos->get($capId);
                if ($doc != $this->documento->limpar($cap->conteudo)) {
                    $cap->update(['conteudo' => $doc, 'texto' => $this->documento->texto($doc)]);
                    $r['capitulos']++;
                }
            }
            // Já deixa notas e cores prontas (a tela também refaz na hora).
            foreach ($capitulos as $cap) {
                $cap->refresh();
                $this->aplicar($cap, $this->documento->limpar($cap->conteudo), $cap->anotacoes()->get(), salvar: true);
            }

            return $r;
        });
    }

    /**
     * Pinta os grifos ligados a questões pela situação atual e refaz a nota de cada um.
     *
     * @return array{0: array, 1: Collection, 2: array<string, array{n: int, ids: list<int>, situacao: string}>}
     *                                                                                                           [documento, anotações, resumo por marca]
     */
    public function aplicar(MaterialCapitulo $capitulo, array $doc, Collection $anotacoes, bool $salvar = false): array
    {
        $links = MaterialGrifoQuestao::where('capitulo_id', $capitulo->id)->get();
        if ($links->isEmpty()) {
            return [$doc, $anotacoes, []];
        }
        $questoes = MaterialQuestao::with('ultimaTentativa')->whereIn('id', $links->pluck('questao_id')->unique())->get()->keyBy('id');
        $errouNoApp = MaterialTentativa::whereIn('questao_id', $questoes->keys())->where('acertou', false)->distinct()->pluck('questao_id')->flip();

        $resumo = [];
        foreach ($links->groupBy('marca') as $marca => $grupo) {
            $itens = [];
            foreach ($grupo as $link) {
                $q = $questoes->get($link->questao_id);
                if ($q) {
                    $itens[] = [$q, $this->situacao($q, isset($errouNoApp[$q->id])), $link];
                }
            }
            if (! $itens) {
                continue;
            }
            // Errando primeiro, depois as que já errei, depois as que só acertei.
            usort($itens, fn ($a, $b) => [self::PESO[$b[1]], $a[0]->ordem] <=> [self::PESO[$a[1]], $b[0]->ordem]);
            $pior = $itens[0][1];
            $resumo[$marca] = [
                'n' => count($itens),
                'ids' => array_map(fn ($i) => $i[0]->id, $itens),
                'situacao' => $pior,
                'cor' => self::COR[$pior],
                'linhas' => implode("\n\n", array_map(fn ($i) => $this->linha(...$i), $itens)),
            ];
        }

        $doc = $this->recolorir($doc, $resumo);
        $anotacoes = $anotacoes->map(function (MaterialAnotacao $a) use ($resumo, $salvar) {
            if (! isset($resumo[$a->marca])) {
                return $a;
            }
            $g = $resumo[$a->marca];
            // O que o aluno escreveu fica acima da régua; num grifo automático sem régua, não há texto dele.
            $nota = (string) $a->nota;
            $sua = match (true) {
                str_contains($nota, self::SEPARADOR) => trim(explode(self::SEPARADOR, $nota, 2)[0]),
                (bool) preg_match(self::AUTOMATICO, $a->marca) => '',
                default => trim($nota),
            };
            $a->nota = Str::limit($sua !== '' ? $sua.self::SEPARADOR.$g['linhas'] : $g['linhas'], 4990);
            $a->cor = $g['cor'];
            if ($salvar && $a->isDirty()) {
                $a->save();
            }

            return $a;
        });
        if ($salvar && $doc != $this->documento->limpar($capitulo->conteudo)) {
            $capitulo->update(['conteudo' => $doc]);
        }

        return [$doc, $anotacoes, array_map(fn ($g) => array_diff_key($g, ['linhas' => 1]), $resumo)];
    }

    private const PESO = ['errando' => 3, 'errou_antes' => 2, 'acertando' => 1];

    private const COR = ['errando' => 'rosa', 'errou_antes' => 'amarelo', 'acertando' => 'verde'];

    private function situacao(MaterialQuestao $q, bool $errouNoApp): string
    {
        $acertouUltima = $q->ultimaTentativa ? $q->ultimaTentativa->acertou : $q->resultado_tec !== 'errada';
        if (! $acertouUltima) {
            return 'errando';
        }

        return $errouNoApp || $q->resultado_tec === 'errada' ? 'errou_antes' : 'acertando';
    }

    /** "✘ Q123 (FGV 2024) — errando, alternativa B: “…”\n→ por quê" (o ✘/•/✔ é lido pelo mapa de calor). */
    private function linha(MaterialQuestao $q, string $situacao, MaterialGrifoQuestao $link): string
    {
        $sinal = ['errando' => '✘', 'errou_antes' => '•', 'acertando' => '✔'][$situacao];
        $rotulo = ['errando' => 'errei a última', 'errou_antes' => 'errei antes, acertei a última', 'acertando' => 'acertei'][$situacao];
        $quem = 'Q'.($q->id_externo ?: $q->id).($q->origem ? ' ('.$this->origemCurta($q->origem).')' : '');
        $alvo = '';
        if ($link->letra && $q->tipo !== 'CE') {
            $alt = collect($q->alternativas ?? [])->firstWhere('letra', $link->letra);
            $alvo = ", alternativa {$link->letra}".($alt ? ': “'.Str::limit(preg_replace('/\s+/u', ' ', strip_tags((string) $alt['texto'])), 140).'”' : '');
        } elseif ($q->tipo === 'CE') {
            $alvo = ', item '.(strtoupper((string) $q->gabarito) === 'C' ? 'CERTO' : 'ERRADO');
        }

        return "{$sinal} {$quem} — {$rotulo}{$alvo}".($link->por_que ? "\n→ {$link->por_que}" : '');
    }

    private function origemCurta(string $origem): string
    {
        $p = array_map('trim', explode('·', $origem));
        $banca = preg_replace('/\s*\(.*\)$/u', '', $p[0] ?? '');

        return trim($banca.' '.($p[1] ?? ''));
    }

    /**
     * O trecho como fica no livro: passa pelo mesmo conversor de Markdown (tira $…$, ** e marcadores)
     * e vira um texto por bloco (parágrafo, título, item). Pedaços muito curtos ficam de fora.
     *
     * @return list<string>
     */
    private function pedacos(string $trecho, bool $porLinha = false): array
    {
        $textos = [];
        $andar = function (array $nos) use (&$andar, &$textos) {
            foreach ($nos as $n) {
                if (in_array($n['type'] ?? '', ['paragraph', 'heading'], true)) {
                    $t = trim(preg_replace('/\s+/u', ' ', collect($n['content'] ?? [])->map(fn ($f) => ($f['type'] ?? '') === 'text' ? $f['text'] : ' ')->implode('')));
                    if (mb_strlen($t) >= 12) {
                        $textos[] = rtrim($t, ':');
                    }
                } elseif (! empty($n['content'])) {
                    $andar($n['content']);
                }
            }
        };
        // Linha de tabela solta ("| A | B |") não vira tabela no conversor: cada célula é um pedaço.
        $fontes = str_contains($trecho, '|') ? preg_split('/\s*\|\s*/u', $trecho) : [$trecho];
        if ($porLinha) {
            $fontes = array_merge(...array_map(fn ($f) => preg_split('/\R/u', $f), $fontes));
        }
        foreach ($fontes as $f) {
            try {
                // Sem o recuo: pedaço de lista aninhada recuado 4+ espaços viraria bloco de código.
                $andar($this->markdown->nos(preg_replace('/^[ \t]+/mu', '', str_replace("\u{FEFF}", '', $f))));
            } catch (\Throwable) {
            }
        }

        return $textos ?: [$trecho];
    }

    /** @return list<int> capítulos do arquivo primeiro, depois o resto do livro */
    private function ordemDeBusca(array $todos, array $preferidos): array
    {
        $preferidos = array_values(array_intersect($preferidos, $todos));

        return [...$preferidos, ...array_values(array_diff($todos, $preferidos))];
    }

    private function recolorir(array $doc, array $resumo): array
    {
        $andar = function (array $nos) use (&$andar, $resumo) {
            foreach ($nos as $i => $n) {
                foreach ($n['marks'] ?? [] as $k => $m) {
                    if (($m['type'] ?? '') === 'grifo' && isset($resumo[$m['attrs']['id'] ?? ''])) {
                        $nos[$i]['marks'][$k]['attrs']['cor'] = $resumo[$m['attrs']['id']]['cor'];
                    }
                }
                if (! empty($n['content'])) {
                    $nos[$i]['content'] = $andar($n['content']);
                }
            }

            return $nos;
        };
        $doc['content'] = $andar($doc['content'] ?? []);

        return $doc;
    }

    /** Tira do texto as marcas de grifo automáticas (as feitas à mão ficam). */
    private function tirarAutomaticos(array $doc): array
    {
        $andar = function (array $nos) use (&$andar) {
            foreach ($nos as $i => $n) {
                if (! empty($n['marks'])) {
                    $nos[$i]['marks'] = array_values(array_filter($n['marks'], fn ($m) => ! (($m['type'] ?? '') === 'grifo' && preg_match(self::AUTOMATICO, (string) ($m['attrs']['id'] ?? '')))));
                    if (! $nos[$i]['marks']) {
                        unset($nos[$i]['marks']);
                    }
                }
                if (! empty($n['content'])) {
                    $nos[$i]['content'] = $andar($n['content']);
                }
            }

            return $nos;
        };
        $doc['content'] = $andar($doc['content'] ?? []);

        // Pedaços de texto vizinhos com as mesmas marcas voltam a ser um só (o Grifador procura por bloco, então tanto faz; o limpar() já normaliza).
        return $this->documento->limpar($doc);
    }

    /**
     * Lê os .jsonl da pasta e descobre, pelos planos dos módulos, em que capítulos cada .md foi posto.
     *
     * @return array{0: list<array>, 1: array<string, list<int>>}
     */
    public static function lerPasta(Material $material, string $pasta, string $pastaPlanos): array
    {
        if (! is_dir($pasta)) {
            throw new InvalidArgumentException("Pasta não encontrada: {$pasta}");
        }
        $linhas = [];
        foreach (glob(rtrim($pasta, '/\\').'/*.jsonl') ?: [] as $f) {
            foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
                $o = json_decode($l, true);
                if (is_array($o)) {
                    $linhas[] = $o;
                }
            }
        }
        $porArquivo = [];
        foreach (glob(rtrim($pastaPlanos, '/\\').'/*.plano.json') ?: [] as $f) {
            $p = json_decode((string) file_get_contents($f), true);
            if (! is_array($p) || (int) ($p['material'] ?? 0) !== $material->id || empty($p['arquivo']) || empty($p['secoes'])) {
                continue;
            }
            foreach ($p['secoes'] as $s) {
                $c = $s['capitulo'] ?? null;
                $id = is_int($c) || ctype_digit((string) $c)
                    ? (int) $c
                    : MaterialCapitulo::where('material_id', $material->id)->where('chave', Str::limit('b-'.Str::slug((string) $c), 80, ''))->value('id');
                if ($id && ! in_array($id, $porArquivo[$p['arquivo']] ?? [], true)) {
                    $porArquivo[$p['arquivo']][] = $id;
                }
            }
        }

        return [$linhas, $porArquivo];
    }
}
