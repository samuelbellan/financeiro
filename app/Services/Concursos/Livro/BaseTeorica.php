<?php

namespace App\Services\Concursos\Livro;

use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialAnotacao;
use App\Models\Concursos\MaterialCapitulo;
use App\Models\Concursos\MaterialQuestao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Põe um resumo teórico em Markdown (ex.: módulo de um professor) no topo dos capítulos do livro
 * e grifa nele os trechos que respondem às alternativas das questões erradas, com a explicação na
 * nota da margem. O plano (JSON) diz para onde vai cada seção e quais trechos grifar:
 *
 *   {
 *     "material": 2,
 *     "fonte": "Contabilidade Geral — Módulo 1",
 *     "secoes": [{"titulo": "Princípios de Contabilidade", "capitulo": 28}, ...],
 *     "destaques": [{"q": 359, "letra": "C", "trecho": "texto exato do resumo", "por_que": "..."}]
 *   }
 *
 * "letra" é a alternativa (A–E) ou, em certo/errado, pode faltar. A cor sai sozinha: verde no
 * gabarito (ou item certo), rosa na alternativa que você marcou (ou item errado), azul nas outras.
 * Pode rodar de novo: o texto entra uma vez só por capítulo e os grifos/notas são refeitos.
 */
class BaseTeorica
{
    public const MARCADOR = 'Base teórica: ';

    private const PONTOS = 'Meus pontos de erro';

    private const SEPARADOR = "\n\n— Questões que caem aqui —\n\n";

    public function __construct(
        private readonly Documento $documento,
        private readonly MarkdownLivro $markdown,
        private readonly Grifador $grifador,
    ) {
    }

    /** @return array{capitulos: int, secoes: int, destaques: int, sem_trecho: list<string>} */
    public function aplicar(array $plano, string $md, bool $refazer = false): array
    {
        $material = Material::findOrFail($plano['material'] ?? 0);
        $fonte = trim((string) ($plano['fonte'] ?? '')) ?: 'resumo';
        $partes = collect($this->markdown->secoes($md));

        return DB::connection('concursos_pgsql')->transaction(function () use ($plano, $material, $fonte, $partes, $refazer) {
            $r = ['capitulos' => 0, 'secoes' => 0, 'destaques' => 0, 'sem_trecho' => []];

            // 1. Texto: cada seção pedida, em ordem, no capítulo indicado.
            $indices = [];
            foreach ($plano['secoes'] ?? [] as $k => $s) {
                // Título repetido em níveis diferentes ("## Patrimônio" e "### Patrimônio"): vale o ##, salvo "nivel": 3.
                $titulo = (string) ($s['titulo'] ?? '');
                $nivel = (int) ($s['nivel'] ?? 0);
                $i = $partes->search(fn ($p) => $this->igual($p['titulo'], $titulo) && $p['nivel'] === ($nivel ?: 2));
                if ($i === false && !$nivel) {
                    $i = $partes->search(fn ($p) => $this->igual($p['titulo'], $titulo));
                }
                if ($i === false) {
                    throw new InvalidArgumentException("Seção não encontrada no Markdown: {$titulo}");
                }
                $indices[$k] = $i;
            }
            $porCapitulo = [];
            foreach ($plano['secoes'] ?? [] as $k => $s) {
                $i = $indices[$k];
                $md = $partes[$i]['md'];
                // Um ## leva junto os ### dele que o plano não mandou para outro lugar.
                if ($partes[$i]['nivel'] === 2) {
                    for ($j = $i + 1; $j < $partes->count() && $partes[$j]['nivel'] === 3; $j++) {
                        if (!in_array($j, $indices, true)) {
                            $md .= $partes[$j]['md'];
                        }
                    }
                }
                $porCapitulo[$this->capituloId($material, $s['capitulo'] ?? null)][] = $md;
                $r['secoes']++;
            }
            $capitulos = MaterialCapitulo::where('material_id', $material->id)->whereIn('id', array_keys($porCapitulo))->get()->keyBy('id');
            foreach ($porCapitulo as $capId => $mds) {
                $cap = $capitulos->get($capId) ?? throw new InvalidArgumentException("Capítulo {$capId} não é deste material.");
                $doc = $this->documento->limpar($cap->conteudo);
                $antes = array_values(array_filter($doc['content'], fn ($n) => !(($n['type'] ?? '') === 'paragraph' && empty($n['content']))));
                $ja = $this->posicaoDoResumo($antes, $fonte);
                if ($ja !== null && !$refazer) {
                    continue; // já tem este resumo
                }
                $base = [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => self::MARCADOR . $fonte, 'marks' => [['type' => 'italic']]]]]];
                foreach ($mds as $m) {
                    array_push($base, ...$this->semRegua($this->markdown->nos($m)));
                }
                if ($ja !== null) {
                    // Refazer: troca o bloco antigo pelo novo, no mesmo lugar (os grifos voltam ao rodar os destaques).
                    $fim = $ja + 1;
                    while ($fim < count($antes) && !$this->ehFronteira($antes, $fim)) {
                        $fim++;
                    }
                    $this->salvar($cap, ['type' => 'doc', 'content' => [...array_slice($antes, 0, $ja), ...$base, ...array_slice($antes, $fim)]]);
                    $r['capitulos']++;
                    continue;
                }
                // Já tem outro resumo? O novo entra depois dele, antes de "Meus pontos de erro".
                $corte = $this->inicioDosPontos($antes);
                if ($corte !== null) {
                    $novo = [...array_slice($antes, 0, $corte), ...$base, ...array_slice($antes, $corte)];
                } elseif ($antes) {
                    $novo = [...$base, ['type' => 'horizontalRule'], ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => self::PONTOS]]], ...$antes];
                } else {
                    $novo = $base;
                }
                $this->salvar($cap, ['type' => 'doc', 'content' => $novo]);
                $r['capitulos']++;
            }

            // 2. Grifos: um por trecho, reunindo as questões que caem nele. Plano só de destaques
            // (sem seções) procura no livro inteiro.
            $alvos = $porCapitulo
                ? MaterialCapitulo::whereIn('id', array_keys($porCapitulo))->get()->sortBy(fn ($c) => array_search($c->id, array_keys($porCapitulo)))
                : $material->capitulos()->get();
            $questoes = MaterialQuestao::where('material_id', $material->id)
                ->whereIn('id', collect($plano['destaques'] ?? [])->pluck('q'))->get()->keyBy('id');
            $grupos = []; // capId|marca => [cor, trecho, entradas]
            $docs = $alvos->mapWithKeys(fn ($c) => [$c->id => $this->documento->limpar($c->conteudo)])->all();
            foreach ($plano['destaques'] ?? [] as $d) {
                $q = $questoes->get((int) ($d['q'] ?? 0));
                if (!$q) {
                    $r['sem_trecho'][] = "Q{$d['q']}: questão não é deste material";
                    continue;
                }
                [$cor, $linha] = $this->entrada($q, strtoupper((string) ($d['letra'] ?? '')), trim((string) ($d['por_que'] ?? '')));
                $id = 'q' . substr(md5(Str::lower($d['trecho'])), 0, 12);
                $usado = null;
                foreach ($docs as $capId => $doc) {
                    [$novo, $usado] = $this->grifador->grifar($doc, (string) $d['trecho'], $id, $cor);
                    if ($usado !== null) {
                        $docs[$capId] = $novo;
                        $chave = $capId . '|' . $usado;
                        $grupos[$chave] ??= ['cor' => $cor, 'trecho' => $d['trecho'], 'linhas' => []];
                        $grupos[$chave]['cor'] = $this->maisForte($grupos[$chave]['cor'], $cor);
                        $grupos[$chave]['linhas'][] = $linha;
                        $r['destaques']++;
                        break;
                    }
                }
                if ($usado === null) {
                    $r['sem_trecho'][] = "Q{$q->id} {$d['letra']}: “" . Str::limit($d['trecho'], 60) . '”';
                }
            }
            foreach ($grupos as $chave => $g) {
                [$capId, $marca] = explode('|', $chave, 2);
                $antiga = (string) MaterialAnotacao::where('capitulo_id', $capId)->where('marca', $marca)->value('nota');
                $nossa = preg_match('/^q[0-9a-f]{12}$/', $marca);
                [$sua, $resto] = $nossa ? ['', $antiga] : array_pad(explode(self::SEPARADOR, $antiga, 2), 2, '');
                // Linhas de questões já na nota (de outro plano ou de rodada anterior) ficam; a mesma
                // questão/alternativa é trocada pela versão nova.
                $porChave = [];
                foreach (preg_split('/\n\n(?=[✔✘•] )/u', trim($resto)) as $bloco) {
                    if (preg_match('/^[✔✘•] /u', $bloco)) {
                        $porChave[$this->chaveDaLinha($bloco)] = $bloco;
                    }
                }
                foreach ($g['linhas'] as $l) {
                    $porChave[$this->chaveDaLinha($l)] = $l;
                }
                $linhas = implode("\n\n", $porChave);
                // Grifo que você mesmo fez (id que não é dos destaques): a sua nota fica em cima.
                $sua = trim($sua);
                $nota = $sua !== '' ? $sua . self::SEPARADOR . $linhas : $linhas;
                // A cor vem de todas as linhas da nota: rosa (✘) vence verde (✔), que vence azul (•).
                $cor = 'azul';
                foreach ($porChave as $l) {
                    $cor = $this->maisForte($cor, match (mb_substr($l, 0, 1)) { '✘' => 'rosa', '✔' => 'verde', default => 'azul' });
                }
                $grupos[$chave]['cor'] = $cor;
                MaterialAnotacao::updateOrCreate(
                    ['capitulo_id' => $capId, 'marca' => $marca],
                    ['cor' => $cor, 'trecho' => Str::limit($g['trecho'], 1990, ''), 'nota' => Str::limit($nota, 4990)],
                );
            }
            foreach ($docs as $capId => $doc) {
                $doc = $this->recolorir($doc, $grupos, $capId);
                $cap = $alvos->firstWhere('id', $capId);
                if ($doc != $this->documento->limpar($cap->conteudo)) {
                    $this->salvar($cap, $doc);
                }
            }

            return $r;
        });
    }

    /** "✔ Q123 (…) — alternativa B é o gabarito" → chave "Q123|B" (questão + alternativa/item). */
    private function chaveDaLinha(string $linha): string
    {
        preg_match('/^[✔✘•] (Q\S+)/u', $linha, $q);
        preg_match('/(?:alternativa|marcou) ([A-E])\b|item (CERTO|ERRADO)/u', $linha, $l);

        return ($q[1] ?? $linha) . '|' . ($l[1] ?? '') . ($l[2] ?? '');
    }

    /** Capítulo pelo id ou, se vier um título, um capítulo novo no fim do livro (criado uma vez só). */
    private function capituloId(Material $material, mixed $capitulo): int
    {
        if (is_int($capitulo) || ctype_digit((string) $capitulo)) {
            return (int) $capitulo;
        }
        $titulo = trim((string) $capitulo);
        if ($titulo === '') {
            throw new InvalidArgumentException('Seção sem capítulo no plano.');
        }

        return MaterialCapitulo::firstOrCreate(
            ['material_id' => $material->id, 'chave' => Str::limit('b-' . Str::slug($titulo), 80, '')],
            ['titulo' => $titulo, 'ordem' => (int) $material->capitulos()->whereNull('parent_id')->max('ordem') + 1],
        )->id;
    }

    private function textoDoNo(array $n): string
    {
        return trim(collect($n['content'] ?? [])->pluck('text')->implode(''));
    }

    private function posicaoDoResumo(array $nos, string $fonte): ?int
    {
        foreach ($nos as $i => $n) {
            if (($n['type'] ?? '') === 'paragraph' && $this->textoDoNo($n) === self::MARCADOR . $fonte) {
                return $i;
            }
        }

        return null;
    }

    /** Onde termina um resumo: no marcador do próximo, na régua/título dos pontos de erro. */
    private function ehFronteira(array $nos, int $i): bool
    {
        $n = $nos[$i];
        if (($n['type'] ?? '') === 'paragraph' && str_starts_with($this->textoDoNo($n), self::MARCADOR)) {
            return true;
        }

        return $this->inicioDosPontos(array_slice($nos, $i, 2)) === 0;
    }

    /** Posição da régua antes de "Meus pontos de erro" (ou do próprio título), se houver. */
    private function inicioDosPontos(array $nos): ?int
    {
        foreach ($nos as $i => $n) {
            if (($n['type'] ?? '') === 'heading' && trim(collect($n['content'] ?? [])->pluck('text')->implode('')) === self::PONTOS) {
                return $i > 0 && ($nos[$i - 1]['type'] ?? '') === 'horizontalRule' ? $i - 1 : $i;
            }
        }

        return null;
    }

    /** @return array{0: string, 1: string} [cor, linha da nota] */
    private function entrada(MaterialQuestao $q, string $letra, string $porQue): array
    {
        $marcou = preg_match('/marcou (\S+?);/u', (string) $q->comentario, $m) ? strtoupper($m[1]) : null;
        $quem = 'Q' . ($q->id_externo ?: $q->id) . ($q->origem ? ' (' . $this->origemCurta($q->origem) . ')' : '');

        if ($q->tipo === 'CE') {
            $certo = strtoupper((string) $q->gabarito) === 'C';
            $item = Str::limit($this->ultimoParagrafo((string) $q->enunciado), 220);
            $linha = ($certo ? '✔ ' : '✘ ') . "{$quem} — item " . ($certo ? 'CERTO' : 'ERRADO') . ($marcou ? " (você marcou {$marcou})" : '') . ": “{$item}”";

            return [$certo ? 'verde' : 'rosa', $linha . ($porQue !== '' ? "\n→ {$porQue}" : '')];
        }

        $alt = collect($q->alternativas ?? [])->firstWhere('letra', $letra);
        $texto = $alt ? Str::limit(preg_replace('/\s+/u', ' ', (string) $alt['texto']), 160) : '';
        [$cor, $sinal, $papel] = match (true) {
            $letra === strtoupper((string) $q->gabarito) => ['verde', '✔', "alternativa {$letra} é o gabarito"],
            $letra === $marcou => ['rosa', '✘', "você marcou {$letra} (gabarito " . strtoupper((string) $q->gabarito) . ')'],
            default => ['azul', '•', "alternativa {$letra} (errada)"],
        };

        return [$cor, "{$sinal} {$quem} — {$papel}: “{$texto}”" . ($porQue !== '' ? "\n→ {$porQue}" : '')];
    }

    private function maisForte(string $a, string $b): string
    {
        $ordem = ['rosa' => 3, 'verde' => 2, 'azul' => 1, 'amarelo' => 0];

        return ($ordem[$b] ?? 0) > ($ordem[$a] ?? 0) ? $b : $a;
    }

    /** O grifo no texto fica com a cor final do grupo (rosa vence verde, que vence azul). */
    private function recolorir(array $doc, array $grupos, int $capId): array
    {
        $cores = [];
        foreach ($grupos as $chave => $g) {
            [$c, $marca] = explode('|', $chave, 2);
            if ((int) $c === $capId) {
                $cores[$marca] = $g['cor'];
            }
        }
        $andar = function (array $nos) use (&$andar, $cores) {
            foreach ($nos as $i => $n) {
                foreach ($n['marks'] ?? [] as $k => $m) {
                    if (($m['type'] ?? '') === 'grifo' && isset($cores[$m['attrs']['id'] ?? ''])) {
                        $nos[$i]['marks'][$k]['attrs']['cor'] = $cores[$m['attrs']['id']];
                    }
                }
                if (!empty($n['content'])) {
                    $nos[$i]['content'] = $andar($n['content']);
                }
            }

            return $nos;
        };
        $doc['content'] = $andar($doc['content'] ?? []);

        return $doc;
    }

    private function origemCurta(string $origem): string
    {
        $p = array_map('trim', explode('·', $origem));
        $banca = preg_replace('/\s*\(.*\)$/u', '', $p[0] ?? '');

        return trim($banca . ' ' . ($p[1] ?? '') . (isset($p[2]) ? ' · ' . $p[2] : ''));
    }

    private function ultimoParagrafo(string $t): string
    {
        $partes = array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/u', $t))));

        return preg_replace('/\s+/u', ' ', end($partes) ?: $t);
    }

    /** As réguas (---) entre seções do Markdown viram ruído dentro do capítulo. */
    private function semRegua(array $nos): array
    {
        return array_values(array_filter($nos, fn ($n) => ($n['type'] ?? '') !== 'horizontalRule'));
    }

    private function igual(string $a, string $b): bool
    {
        return Str::lower(Str::ascii(trim($a))) === Str::lower(Str::ascii(trim($b)));
    }

    private function salvar(MaterialCapitulo $cap, array $doc): void
    {
        $doc = $this->documento->limpar($doc);
        $cap->update(['conteudo' => $doc, 'texto' => $this->documento->texto($doc)]);
    }
}
