<?php

namespace App\Services\Concursos\Livro;

use App\Models\Concursos\MaterialAnotacao;
use App\Services\Concursos\Leis\Citacoes;

/**
 * Documento do capítulo (JSON do editor TipTap). Só os nós e marcas abaixo são aceitos: o que vem
 * do navegador passa por limpar() antes de ir para o banco, e o HTML (impressão) é montado aqui,
 * escapando todo texto — nunca se guarda nem se devolve HTML cru do aluno.
 *
 * Nós próprios: caixa {tipo}, pontoErro {pontoId, titulo} (com conteúdo), questao {questaoId} e
 * lei {ref} (citação "ctn 98", em linha). Marca própria: grifo {cor, id} (id liga à nota da margem).
 */
class Documento
{
    public const PALAVRAS_POR_MINUTO = 200;

    public const CAIXAS = [
        'pegadinha' => 'Pegadinha',
        'regra' => 'Regra certa',
        'macete' => 'Macete',
        'atencao' => 'Atenção',
        'exemplo' => 'Exemplo',
        'base' => 'Base legal',
        'definicao' => 'Definição',
        'doutrina' => 'Doutrina',
        'jurisprudencia' => 'Jurisprudência',
        'leiseca' => 'Lei seca',
    ];

    private const BLOCOS = ['paragraph', 'heading', 'bulletList', 'orderedList', 'listItem', 'blockquote', 'horizontalRule',
        'image', 'table', 'tableRow', 'tableHeader', 'tableCell', 'caixa', 'pontoErro', 'questao', 'rascunho'];

    private const EM_LINHA = ['text', 'hardBreak', 'lei', 'notaPonto'];

    private const MARCAS = ['bold', 'italic', 'underline', 'strike', 'code', 'link', 'grifo'];

    public static function vazio(): array
    {
        return ['type' => 'doc', 'content' => [['type' => 'paragraph']]];
    }

    /** Mantém só o que o editor sabe mostrar, com atributos validados. */
    public function limpar(mixed $doc): array
    {
        if (!is_array($doc) || ($doc['type'] ?? null) !== 'doc') {
            return self::vazio();
        }
        $conteudo = $this->limparFilhos($doc['content'] ?? []);

        return ['type' => 'doc', 'content' => $conteudo ?: [['type' => 'paragraph']]];
    }

    private function limparFilhos(mixed $nos): array
    {
        $saida = [];
        foreach (is_array($nos) ? $nos : [] as $no) {
            if (is_array($no) && ($limpo = $this->limparNo($no)) !== null) {
                $saida[] = $limpo;
            }
        }

        return $saida;
    }

    private function limparNo(array $no): ?array
    {
        $tipo = $no['type'] ?? null;
        if (!in_array($tipo, [...self::BLOCOS, ...self::EM_LINHA], true)) {
            return null;
        }
        $a = is_array($no['attrs'] ?? null) ? $no['attrs'] : [];
        $saida = ['type' => $tipo];

        $attrs = match ($tipo) {
            'heading' => ['level' => max(2, min(4, (int) ($a['level'] ?? 2)))],
            'orderedList' => ['start' => max(1, (int) ($a['start'] ?? 1))],
            'tableCell', 'tableHeader' => [
                'colspan' => max(1, min(20, (int) ($a['colspan'] ?? 1))),
                'rowspan' => max(1, min(50, (int) ($a['rowspan'] ?? 1))),
            ],
            'image' => $this->srcValido($a['src'] ?? null)
                ? ['src' => $a['src'], 'alt' => mb_substr((string) ($a['alt'] ?? ''), 0, 200)]
                : null,
            'caixa' => ['tipo' => array_key_exists($a['tipo'] ?? '', self::CAIXAS) ? $a['tipo'] : 'atencao'],
            'pontoErro' => ['pontoId' => ($id = (int) ($a['pontoId'] ?? 0)) > 0 ? $id : null, 'titulo' => mb_substr(trim((string) ($a['titulo'] ?? '')), 0, 200) ?: 'Ponto que eu erro'],
            'questao' => ($id = (int) ($a['questaoId'] ?? 0)) > 0 ? ['questaoId' => $id] : null,
            'rascunho' => $this->limparRascunho($a),
            'lei' => ($ref = trim((string) ($a['ref'] ?? ''))) !== '' && mb_strlen($ref) <= 80 ? ['ref' => $ref] : null,
            'notaPonto' => $this->limparNotaPonto($a),
            default => [],
        };
        if ($attrs === null) {
            return null; // nó sem o mínimo (imagem de fora, questão sem id): descartado
        }
        if ($attrs) {
            $saida['attrs'] = $attrs;
        }

        if ($tipo === 'text') {
            $texto = (string) ($no['text'] ?? '');
            if ($texto === '') {
                return null;
            }
            $saida['text'] = mb_substr($texto, 0, 20000);
            $marcas = $this->limparMarcas($no['marks'] ?? []);
            if ($marcas) {
                $saida['marks'] = $marcas;
            }
        } elseif (!in_array($tipo, ['hardBreak', 'lei', 'notaPonto', 'image', 'horizontalRule', 'questao', 'rascunho'], true)) {
            $filhos = $this->limparFilhos($no['content'] ?? []);
            if ($filhos) {
                $saida['content'] = $filhos;
            } elseif (in_array($tipo, ['caixa', 'pontoErro', 'blockquote', 'listItem', 'tableCell', 'tableHeader'], true)) {
                $saida['content'] = [['type' => 'paragraph']];
            } elseif (in_array($tipo, ['bulletList', 'orderedList', 'table', 'tableRow'], true)) {
                return null;
            }
        }

        return $saida;
    }

    private function limparMarcas(mixed $marcas): array
    {
        $saida = [];
        foreach (is_array($marcas) ? $marcas : [] as $m) {
            $tipo = is_array($m) ? ($m['type'] ?? null) : null;
            if (!in_array($tipo, self::MARCAS, true)) {
                continue;
            }
            $a = is_array($m['attrs'] ?? null) ? $m['attrs'] : [];
            if ($tipo === 'link') {
                $href = trim((string) ($a['href'] ?? ''));
                if (!preg_match('#^(https?://|/)#i', $href) || str_starts_with($href, '//')) {
                    continue;
                }
                $saida[] = ['type' => 'link', 'attrs' => ['href' => mb_substr($href, 0, 2000)]];
            } elseif ($tipo === 'grifo') {
                $cor = in_array($a['cor'] ?? '', MaterialAnotacao::CORES, true) ? $a['cor'] : 'amarelo';
                $id = preg_match('/^[a-z0-9-]{1,40}$/', (string) ($a['id'] ?? '')) ? $a['id'] : null;
                $saida[] = ['type' => 'grifo', 'attrs' => ['cor' => $cor, 'id' => $id]];
            } else {
                $saida[] = ['type' => $tipo];
            }
        }

        return $saida;
    }

    /** Cores de tinta do rascunho à mão (iguais às de resources/js/concursos/livro/rascunho.js). */
    private const TINTAS = ['preto' => ['currentColor', 4, 1], 'azul' => ['#2563eb', 4, 1], 'vermelho' => ['#dc2626', 4, 1], 'verde' => ['#16a34a', 4, 1], 'marca' => ['#facc15', 22, 0.45]];

    /**
     * Rascunho à mão: altura (largura fixa 1000) e traços {c: tinta, p: [x, y, pressão, ...], s?: 1}.
     * Limites para o documento não crescer sem controle (o PUT inteiro tem teto de 4 MB).
     */
    private function limparRascunho(array $a): array
    {
        $tracos = [];
        $total = 0;
        foreach (array_slice(is_array($a['tracos'] ?? null) ? $a['tracos'] : [], 0, 3000) as $t) {
            $pts = is_array($t['p'] ?? null) ? array_values($t['p']) : [];
            $n = intdiv(min(count($pts), 9000), 3) * 3;
            if ($n < 3 || ($total += $n) > 450_000) {
                continue;
            }
            $p = [];
            for ($i = 0; $i < $n; $i += 3) {
                if (!is_numeric($pts[$i]) || !is_numeric($pts[$i + 1]) || !is_numeric($pts[$i + 2])) {
                    continue 2;
                }
                array_push($p, round(max(-50, min(1050, (float) $pts[$i])), 1), round(max(-50, min(6050, (float) $pts[$i + 1])), 1), round(max(0, min(1, (float) $pts[$i + 2])), 2));
            }
            $traco = ['c' => array_key_exists($t['c'] ?? '', self::TINTAS) ? $t['c'] : 'preto', 'p' => $p];
            if (!empty($t['s'])) {
                $traco['s'] = 1;
            }
            $tracos[] = $traco;
        }

        return ['altura' => max(160, min(6000, (int) ($a['altura'] ?? 360))), 'tracos' => $tracos];
    }

    /**
     * Nota num ponto do texto: id, texto e tinta (mesmo formato do rascunho). Nota vazia sai do
     * documento (o navegador já a tira ao fechar o modal; isto cobre o caso de a página fechar antes).
     */
    public function limparNotaPonto(array $a): ?array
    {
        $id = preg_match('/^[a-z0-9-]{1,40}$/', (string) ($a['id'] ?? '')) ? $a['id'] : 'n' . bin2hex(random_bytes(5));
        $texto = mb_substr(rtrim(str_replace("\r", '', (string) ($a['texto'] ?? ''))), 0, 5000);
        $tinta = $this->limparRascunho(['tracos' => $a['tracos'] ?? [], 'altura' => $a['altura'] ?? 420]);
        if (trim($texto) === '' && !$tinta['tracos']) {
            return null;
        }

        return ['id' => $id, 'texto' => $texto, 'tracos' => $tinta['tracos'], 'altura' => $tinta['altura']];
    }

    /** SVG do rascunho para leitura sem JS e impressão (traço de espessura fixa, sem pressão). */
    private function htmlRascunho(array $a): string
    {
        $altura = (int) ($a['altura'] ?? 360);
        $paths = '';
        foreach ($a['tracos'] ?? [] as $t) {
            [$cor, $tam, $op] = self::TINTAS[$t['c'] ?? 'preto'] ?? self::TINTAS['preto'];
            $p = $t['p'] ?? [];
            $d = [];
            for ($i = 0; $i + 1 < count($p); $i += 3) {
                $d[] = ($i ? 'L' : 'M') . $p[$i] . ' ' . $p[$i + 1];
            }
            if (count($d) === 1) {
                $d[] = 'L' . $p[0] . ' ' . $p[1];
            }
            $paths .= '<path d="' . implode(' ', $d) . '" stroke="' . $cor . '" stroke-width="' . ($tam * 0.8) . '"' . ($op < 1 ? ' stroke-opacity="' . $op . '"' : '') . '/>';
        }

        return '<figure class="rascunho"><svg viewBox="0 0 1000 ' . $altura . '" width="100%" fill="none" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Rascunho à mão">' . $paths . '</svg></figure>';
    }

    /** Só imagens enviadas para o próprio sistema (rota de anexos). */
    private function srcValido(mixed $src): bool
    {
        return is_string($src) && preg_match('#^(https?://[^/]+)?/concursos/material/anexos/\d+$#', $src) === 1;
    }

    /** Texto corrido do documento (busca e prévia). */
    public function texto(array $doc): string
    {
        $partes = [];
        $andar = function (array $nos) use (&$andar, &$partes) {
            foreach ($nos as $no) {
                match ($no['type'] ?? null) {
                    'text' => $partes[] = $no['text'] ?? '',
                    'lei' => $partes[] = ' ' . ($no['attrs']['ref'] ?? '') . ' ',
                    'notaPonto' => $partes[] = trim($no['attrs']['texto'] ?? '') !== '' ? ' (nota: ' . trim($no['attrs']['texto']) . ') ' : '',
                    'pontoErro' => $partes[] = "\n" . ($no['attrs']['titulo'] ?? '') . "\n",
                    'hardBreak' => $partes[] = "\n",
                    default => null,
                };
                if (!empty($no['content'])) {
                    $andar($no['content']);
                    if (in_array($no['type'], self::BLOCOS, true)) {
                        $partes[] = "\n";
                    }
                }
            }
        };
        $andar($doc['content'] ?? []);

        return trim(preg_replace("/[ \t]+/u", ' ', preg_replace("/\n{2,}/u", "\n", implode('', $partes))));
    }

    /** Total de palavras do documento (ignorando pontos de erro e questões quando $soTeoria é true). */
    public function palavras(mixed $doc, bool $soTeoria = false): int
    {
        if (!is_array($doc)) {
            return 0;
        }
        $total = 0;
        $contar = static function (?string $s) use (&$total): void {
            if ($s !== null && $s !== '') {
                $total += (int) preg_match_all('/[\p{L}\p{N}]+/u', $s);
            }
        };
        $andar = function (array $nos) use (&$andar, $contar, $soTeoria): void {
            foreach ($nos as $no) {
                $tipo = $no['type'] ?? null;
                if ($soTeoria && in_array($tipo, ['pontoErro', 'questao'], true)) {
                    continue;
                }
                match ($tipo) {
                    'text' => $contar($no['text'] ?? null),
                    'lei' => $contar($no['attrs']['ref'] ?? null),
                    'pontoErro' => $contar($no['attrs']['titulo'] ?? null),
                    default => null,
                };
                if (!empty($no['content'])) {
                    $andar($no['content']);
                }
            }
        };
        $andar($doc['content'] ?? []);

        return $total;
    }

    /** Tempo médio de leitura em minutos (200 palavras/min), ou 0 se o capítulo estiver vazio. */
    public function tempoLeitura(mixed $doc, bool $soTeoria = false): int
    {
        $palavras = $this->palavras($doc, $soTeoria);

        return $palavras > 0 ? max(1, (int) round($palavras / self::PALAVRAS_POR_MINUTO)) : 0;
    }

    /** Formata minutos como "8 min", "1h" ou "1h 25min". */
    public static function formatarDuracao(int $minutos): string
    {
        if ($minutos < 60) {
            return "{$minutos} min";
        }
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        return $resto > 0 ? "{$horas}h {$resto}min" : "{$horas}h";
    }

    /** @return list<int> valores de um atributo inteiro em todos os nós de um tipo (ex.: pontoErro.pontoId). */
    public function ids(array $doc, string $tipo, string $attr): array
    {
        $ids = [];
        $andar = function (array $nos) use (&$andar, &$ids, $tipo, $attr) {
            foreach ($nos as $no) {
                if (($no['type'] ?? null) === $tipo && (int) ($no['attrs'][$attr] ?? 0) > 0) {
                    $ids[] = (int) $no['attrs'][$attr];
                }
                $andar($no['content'] ?? []);
            }
        };
        $andar($doc['content'] ?? []);

        return array_values(array_unique($ids));
    }

    /** Troca, em todos os nós de um tipo, um id por outro (ponto recriado ao salvar). */
    public function trocarIds(array $doc, string $tipo, string $attr, array $mapa): array
    {
        $andar = function (array $nos) use (&$andar, $tipo, $attr, $mapa) {
            foreach ($nos as &$no) {
                if (($no['type'] ?? null) === $tipo && isset($mapa[$no['attrs'][$attr] ?? null])) {
                    $no['attrs'][$attr] = $mapa[$no['attrs'][$attr]];
                }
                if (!empty($no['content'])) {
                    $no['content'] = $andar($no['content']);
                }
            }

            return $nos;
        };
        $doc['content'] = $andar($doc['content'] ?? []);

        return $doc;
    }

    /**
     * HTML para leitura e impressão do livro.
     *
     * @param  array{pontos?: \Illuminate\Support\Collection, questoes?: \Illuminate\Support\Collection, notas?: \Illuminate\Support\Collection}  $ctx
     */
    /**
     * O capítulo tem teoria? (algum bloco além de pontos de erro, questões, caixas e parágrafos vazios;
     * caixa sozinha é a introdução "padrões do caderno" que o conversor põe nos capítulos de seção).
     * Capítulos só com pontos/questões são do diário de erros: ficam fora do sumário do Resumo.
     */
    public function temTeoria(mixed $doc): bool
    {
        foreach ((is_array($doc) ? $doc['content'] ?? [] : []) as $no) {
            $tipo = $no['type'] ?? null;
            if (!in_array($tipo, ['pontoErro', 'questao', 'caixa'], true) && !($tipo === 'paragraph' && empty($no['content']))) {
                return true;
            }
        }

        return false;
    }

    public function html(array $doc, Citacoes $citacoes, array $ctx = []): string
    {
        return $this->htmlFilhos($doc['content'] ?? [], $citacoes, $ctx);
    }

    private function htmlFilhos(array $nos, Citacoes $cit, array $ctx): string
    {
        return implode('', array_map(fn ($no) => $this->htmlNo($no, $cit, $ctx), $nos));
    }

    private function htmlNo(array $no, Citacoes $cit, array $ctx): string
    {
        $a = $no['attrs'] ?? [];
        $dentro = fn () => $this->htmlFilhos($no['content'] ?? [], $cit, $ctx);

        switch ($no['type'] ?? null) {
            case 'text':
                return $this->htmlTexto($no, $ctx);
            case 'hardBreak':
                return '<br>';
            case 'paragraph':
                return '<p>' . $dentro() . '</p>';
            case 'heading':
                $n = (int) ($a['level'] ?? 2) + 1; // h1 é o título do capítulo

                return "<h{$n}>" . $dentro() . "</h{$n}>";
            case 'bulletList':
                return '<ul>' . $dentro() . '</ul>';
            case 'orderedList':
                return '<ol start="' . (int) ($a['start'] ?? 1) . '">' . $dentro() . '</ol>';
            case 'listItem':
                return '<li>' . $dentro() . '</li>';
            case 'blockquote':
                return '<blockquote>' . $dentro() . '</blockquote>';
            case 'horizontalRule':
                return '<hr>';
            case 'image':
                return '<figure><img src="' . e($a['src'] ?? '') . '" alt="' . e($a['alt'] ?? '') . '" loading="lazy"></figure>';
            case 'table':
                return '<table>' . $dentro() . '</table>';
            case 'tableRow':
                return '<tr>' . $dentro() . '</tr>';
            case 'tableHeader':
            case 'tableCell':
                $tag = $no['type'] === 'tableHeader' ? 'th' : 'td';
                $span = (($a['colspan'] ?? 1) > 1 ? ' colspan="' . (int) $a['colspan'] . '"' : '') . (($a['rowspan'] ?? 1) > 1 ? ' rowspan="' . (int) $a['rowspan'] . '"' : '');

                return "<{$tag}{$span}>" . $dentro() . "</{$tag}>";
            case 'caixa':
                $tipo = $a['tipo'] ?? 'atencao';

                return '<aside class="caixa ' . e($tipo) . '"><div class="caixa-rotulo">' . e(self::CAIXAS[$tipo] ?? 'Atenção') . '</div>' . $dentro() . '</aside>';
            case 'pontoErro':
                if (!empty($ctx['soTeoria'])) {
                    return ''; // no Resumo os pontos ficam na área de Questões
                }
                $p = ($ctx['pontos'] ?? collect())->get($a['pontoId'] ?? 0);
                $status = $p?->status ?? 'errando';
                $rotulo = \App\Models\Concursos\MaterialPonto::STATUS[$status] ?? '';
                $vezes = $p && $p->vezes_errado > 1 ? ' <span class="tag vezes">errei ' . (int) $p->vezes_errado . '×</span>' : '';

                return '<section class="ponto-erro" data-status="' . e($status) . '"><header><b>' . e($a['titulo'] ?? '') . '</b> <span class="tag ' . e($status) . '">' . e($rotulo) . '</span>' . $vezes . '</header>' . $dentro() . '</section>';
            case 'questao':
                $q = ($ctx['questoes'] ?? collect())->get($a['questaoId'] ?? 0);
                if (!$q || !empty($ctx['soTeoria'])) {
                    return '';
                }
                $alts = $q->tipo === 'ME'
                    ? '<ol class="alts" type="A">' . implode('', array_map(fn ($x) => '<li>' . e($x['texto']) . '</li>', $q->alternativas ?? [])) . '</ol>'
                    : '<p class="alts">( ) Certo ( ) Errado</p>';

                return '<section class="questao-livro"><div class="caixa-rotulo">Questão' . ($q->id_externo ? ' TEC ' . e($q->id_externo) : '') . '</div><p>' . nl2br(e($q->enunciado)) . '</p>' . $alts . '<p class="gab">Gabarito: ' . e($q->gabarito) . '</p></section>';
            case 'rascunho':
                return $this->htmlRascunho($a);
            case 'notaPonto':
                return '<sup class="nota-ponto-imp" title="' . e($a['texto'] ?? '') . '">✎</sup>';
            case 'lei':
                $link = $cit->link($a['ref'] ?? '');

                return $link
                    ? '<a class="' . ($link['juris'] ? 'lei juris' : 'lei') . '" href="' . e($link['href']) . '" data-json="' . e($link['json']) . '">' . e($link['rotulo']) . '</a>'
                    : '<span class="lei-sem">' . e($a['ref'] ?? '') . '</span>';
            default:
                return '';
        }
    }

    private function htmlTexto(array $no, array $ctx): string
    {
        $html = e($no['text'] ?? '');
        foreach ($no['marks'] ?? [] as $m) {
            $html = match ($m['type'] ?? null) {
                'bold' => "<strong>{$html}</strong>",
                'italic' => "<em>{$html}</em>",
                'underline' => "<u>{$html}</u>",
                'strike' => "<s>{$html}</s>",
                'code' => "<code>{$html}</code>",
                'link' => '<a href="' . e($m['attrs']['href'] ?? '#') . '" rel="noopener" target="_blank">' . $html . '</a>',
                'grifo' => '<mark class="grifo ' . e($m['attrs']['cor'] ?? 'amarelo') . '"' . (($id = $m['attrs']['id'] ?? null) ? ' data-nota="' . e($id) . '"' : '') . '>' . $html . '</mark>',
                default => $html,
            };
        }

        return $html;
    }

    /**
     * Markdown simples dos pontos antigos (parágrafos, listas, tabelas, **negrito**, *itálico*,
     * [[citação]]) para nós do editor.
     *
     * @return list<array>
     */
    public function deMarkdown(?string $md): array
    {
        $md = trim(str_replace("\r", '', (string) $md));
        if ($md === '') {
            return [];
        }
        $nos = [];
        foreach (preg_split("/\n\s*\n/u", $md) as $bloco) {
            // Dentro de um bloco, linhas seguidas do mesmo tipo (lista, tabela, texto) andam juntas.
            $grupos = [];
            foreach (array_filter(explode("\n", $bloco), fn ($l) => trim($l) !== '') as $l) {
                $tipo = match (true) {
                    (bool) preg_match('/^\s*[-*]\s+/u', $l) => 'ul',
                    (bool) preg_match('/^\s*\d+[.)]\s+/u', $l) => 'ol',
                    str_starts_with(trim($l), '|') => 'tabela',
                    default => 'texto',
                };
                $ultimo = array_key_last($grupos);
                // Linha recuada logo após um item continua o item.
                if ($ultimo !== null && $tipo === 'texto' && in_array($grupos[$ultimo][0], ['ul', 'ol'], true) && preg_match('/^\s+/u', $l)) {
                    $tipo = $grupos[$ultimo][0];
                }
                if ($ultimo !== null && $grupos[$ultimo][0] === $tipo) {
                    $grupos[$ultimo][1][] = $l;
                } else {
                    $grupos[] = [$tipo, [$l]];
                }
            }
            foreach ($grupos as [$tipo, $linhas]) {
                $nos[] = match ($tipo) {
                    'ul' => $this->lista('bulletList', $linhas, '/^\s*[-*]\s+/u'),
                    'ol' => $this->lista('orderedList', $linhas, '/^\s*\d+[.)]\s+/u'),
                    'tabela' => count($linhas) >= 2 ? $this->tabela($linhas) : $this->paragrafo($linhas),
                    default => count($linhas) === 1 && preg_match('/^(#{1,4})\s+(.+)$/u', trim($linhas[0]), $h)
                        ? ['type' => 'heading', 'attrs' => ['level' => min(4, strlen($h[1]) + 1)], 'content' => $this->emLinha($h[2])]
                        : $this->paragrafo($linhas),
                };
            }
        }

        return $nos;
    }

    private function paragrafo(array $linhas): array
    {
        $conteudo = [];
        foreach (array_values($linhas) as $i => $l) {
            if ($i > 0) {
                $conteudo[] = ['type' => 'hardBreak'];
            }
            array_push($conteudo, ...$this->emLinha(trim($l)));
        }

        return ['type' => 'paragraph', 'content' => $conteudo];
    }

    private function lista(string $tipo, array $linhas, string $marcador): array
    {
        $itens = [];
        foreach ($linhas as $l) {
            if (preg_match($marcador, $l) || !$itens) {
                $itens[] = trim(preg_replace($marcador, '', $l));
            } else {
                $itens[count($itens) - 1] .= ' ' . trim($l); // continuação do item
            }
        }

        return ['type' => $tipo, 'content' => array_map(fn ($t) => ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => $this->emLinha($t)]]], $itens)];
    }

    private function tabela(array $linhas): array
    {
        $linhas = array_values(array_filter($linhas, fn ($l) => !preg_match('/^\s*\|?\s*:?-{2,}/u', $l)));
        $rows = [];
        foreach ($linhas as $i => $l) {
            $celulas = array_map('trim', explode('|', trim(trim($l), '|')));
            $rows[] = ['type' => 'tableRow', 'content' => array_map(fn ($c) => [
                'type' => $i === 0 ? 'tableHeader' : 'tableCell',
                'attrs' => ['colspan' => 1, 'rowspan' => 1],
                'content' => [['type' => 'paragraph', 'content' => $this->emLinha($c)]],
            ], $celulas)];
        }

        return ['type' => 'table', 'content' => $rows];
    }

    /** Texto de uma linha com **negrito**, *itálico* e [[citação]]. */
    public function emLinha(string $t): array
    {
        $saida = [];
        $partes = preg_split('/(\[\[[^\]]+\]\]|\*\*[^*]+\*\*|(?<![*\w])\*[^*\s][^*]*\*(?!\*))/u', $t, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($partes as $p) {
            if (str_starts_with($p, '[[')) {
                $saida[] = ['type' => 'lei', 'attrs' => ['ref' => trim(substr($p, 2, -2))]];
            } elseif (str_starts_with($p, '**') && strlen($p) > 4) {
                $saida[] = ['type' => 'text', 'text' => substr($p, 2, -2), 'marks' => [['type' => 'bold']]];
            } elseif (str_starts_with($p, '*') && strlen($p) > 2) {
                $saida[] = ['type' => 'text', 'text' => substr($p, 1, -1), 'marks' => [['type' => 'italic']]];
            } else {
                $saida[] = ['type' => 'text', 'text' => $p];
            }
        }

        return $saida;
    }
}
