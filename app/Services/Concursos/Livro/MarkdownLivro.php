<?php

namespace App\Services\Concursos\Livro;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableCell;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Table\TableRow;
use League\CommonMark\Extension\Table\TableSection;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;

/**
 * Markdown completo (resumo de professor, apostila convertida) para os nós do editor do livro:
 * títulos, listas aninhadas, citações, tabelas, negrito/itálico/código. Fórmulas em LaTeX
 * ($\text{PL} = \text{A} - \text{P}$) viram texto simples, porque o editor não mostra LaTeX.
 *
 * secoes() quebra o arquivo pelos títulos ## e ### para cada parte ir ao capítulo certo.
 */
class MarkdownLivro
{
    private const SIMBOLOS = ['\\ge' => '≥', '\\geq' => '≥', '\\le' => '≤', '\\leq' => '≤', '\\neq' => '≠', '\\Sigma' => 'Σ',
        '\\times' => '×', '\\cdot' => '·', '\\to' => '→', '\\rightarrow' => '→', '\\Rightarrow' => '⇒', '\\%' => '%', '\\,' => ' '];

    /**
     * Partes do arquivo por título: [['nivel' => 2|3, 'titulo' => ..., 'md' => texto com o título]].
     * Um ### fica dentro do ## anterior, a não ser que o plano peça o ### sozinho.
     *
     * @return list<array{nivel: int, titulo: string, md: string}>
     */
    public function secoes(string $md): array
    {
        $partes = [];
        $atual = null;
        $cerca = false;
        foreach (explode("\n", str_replace("\r", '', $md)) as $linha) {
            if (preg_match('/^(```|~~~)/', $linha)) {
                $cerca = !$cerca;
            }
            if (!$cerca && preg_match('/^(#{2,3})\s+(.+?)\s*#*\s*$/u', $linha, $m)) {
                if ($atual) {
                    $partes[] = $atual;
                }
                $atual = ['nivel' => strlen($m[1]), 'titulo' => trim($m[2]), 'md' => $linha . "\n"];
                continue;
            }
            if ($atual) {
                $atual['md'] .= $linha . "\n";
            }
        }
        if ($atual) {
            $partes[] = $atual;
        }

        return $partes;
    }

    /** @return list<array> nós de bloco do editor */
    public function nos(string $md): array
    {
        $env = new Environment([]);
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new TableExtension());
        $doc = (new MarkdownParser($env))->parse($this->semLatex($md));

        $nos = [];
        foreach ($doc->children() as $filho) {
            array_push($nos, ...$this->bloco($filho));
        }

        return $nos;
    }

    /** Tira as fórmulas LaTeX, mantendo o "R\$" (cifrão escapado) como texto. */
    public function semLatex(string $md): string
    {
        $md = str_replace('\\$', "\u{E000}", $md);
        // "R$ 2 milhões" sem escape é dinheiro, não início de fórmula.
        $md = preg_replace('/(?<=R)\$(?=\s?\d)/u', "\u{E000}", $md);
        $md = preg_replace_callback('/\$\$(.+?)\$\$|\$(.+?)\$/su', function ($m) {
            $f = $m[1] !== '' ? $m[1] : $m[2];
            $f = preg_replace('/\\\\(?:text|mathrm|mathbf|textbf)\{([^{}]*)\}/u', '$1', $f);
            $f = strtr($f, self::SIMBOLOS);
            $f = preg_replace('/\\\\[a-zA-Z]+/u', '', $f);

            return trim(str_replace(['{', '}'], '', $f));
        }, $md);

        return str_replace("\u{E000}", '\\$', $md);
    }

    /** @return list<array> */
    private function bloco(Node $n): array
    {
        return match (true) {
            $n instanceof Heading => [['type' => 'heading', 'attrs' => ['level' => max(2, min(4, $n->getLevel()))], 'content' => $this->linha($n)]],
            $n instanceof Paragraph => ($c = $this->linha($n)) ? [['type' => 'paragraph', 'content' => $c]] : [],
            $n instanceof ListBlock => $this->lista($n),
            $n instanceof BlockQuote => [['type' => 'blockquote', 'content' => $this->blocos($n) ?: [['type' => 'paragraph']]]],
            $n instanceof ThematicBreak => [['type' => 'horizontalRule']],
            $n instanceof FencedCode, $n instanceof IndentedCode => ($t = rtrim($n->getLiteral())) !== ''
                ? [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $t, 'marks' => [['type' => 'code']]]]]] : [],
            $n instanceof Table => $this->tabela($n),
            default => $this->blocos($n),
        };
    }

    /** @return list<array> */
    private function blocos(Node $pai): array
    {
        $saida = [];
        foreach ($pai->children() as $f) {
            array_push($saida, ...$this->bloco($f));
        }

        return $saida;
    }

    private function lista(ListBlock $l): array
    {
        $ordenada = $l->getListData()->type === ListBlock::TYPE_ORDERED;
        $itens = [];
        foreach ($l->children() as $item) {
            if ($item instanceof ListItem) {
                $itens[] = ['type' => 'listItem', 'content' => $this->blocos($item) ?: [['type' => 'paragraph']]];
            }
        }
        if (!$itens) {
            return [];
        }
        $no = ['type' => $ordenada ? 'orderedList' : 'bulletList', 'content' => $itens];
        if ($ordenada) {
            $no['attrs'] = ['start' => max(1, (int) ($l->getListData()->start ?? 1))];
        }

        return [$no];
    }

    private function tabela(Table $t): array
    {
        $linhas = [];
        foreach ($t->children() as $secao) {
            foreach ($secao instanceof TableSection ? $secao->children() : [] as $linha) {
                if (!$linha instanceof TableRow) {
                    continue;
                }
                $cabeca = $secao->isHead();
                $linhas[] = ['type' => 'tableRow', 'content' => array_map(fn (TableCell $c) => [
                    'type' => $cabeca ? 'tableHeader' : 'tableCell',
                    'attrs' => ['colspan' => 1, 'rowspan' => 1],
                    'content' => [['type' => 'paragraph', 'content' => $this->linha($c)]],
                ], array_values(array_filter(iterator_to_array($linha->children()), fn ($c) => $c instanceof TableCell)))];
            }
        }

        return $linhas ? [['type' => 'table', 'content' => $linhas]] : [];
    }

    /** Conteúdo em linha (texto com marcas), juntando pedaços vizinhos com as mesmas marcas. */
    private function linha(Node $pai, array $marcas = []): array
    {
        $saida = [];
        foreach ($pai->children() as $n) {
            if ($n instanceof Text) {
                $this->texto($saida, $n->getLiteral(), $marcas);
            } elseif ($n instanceof Code) {
                $this->texto($saida, $n->getLiteral(), [...$marcas, ['type' => 'code']]);
            } elseif ($n instanceof Newline) {
                if ($n->getType() === Newline::HARDBREAK) {
                    $saida[] = ['type' => 'hardBreak'];
                } else {
                    $this->texto($saida, ' ', $marcas);
                }
            } else {
                $extra = match (true) {
                    $n instanceof Strong => [['type' => 'bold']],
                    $n instanceof Emphasis => [['type' => 'italic']],
                    $n instanceof Link && preg_match('#^https?://#i', $n->getUrl()) => [['type' => 'link', 'attrs' => ['href' => $n->getUrl()]]],
                    default => [],
                };
                array_push($saida, ...$this->linha($n, [...$marcas, ...array_filter($extra, fn ($e) => !in_array($e, $marcas, true))]));
            }
        }

        return $this->juntar($saida);
    }

    private function texto(array &$saida, string $t, array $marcas): void
    {
        if ($t === '') {
            return;
        }
        $no = ['type' => 'text', 'text' => $t];
        if ($marcas) {
            $no['marks'] = array_values($marcas);
        }
        $saida[] = $no;
    }

    private function juntar(array $nos): array
    {
        $saida = [];
        foreach ($nos as $n) {
            $ultimo = $saida ? $saida[count($saida) - 1] : null;
            if ($ultimo && $n['type'] === 'text' && $ultimo['type'] === 'text' && ($ultimo['marks'] ?? []) == ($n['marks'] ?? [])) {
                $saida[count($saida) - 1]['text'] .= $n['text'];
            } else {
                $saida[] = $n;
            }
        }
        // Espaço solto no começo ou no fim do bloco não serve para nada.
        if ($saida && $saida[0]['type'] === 'text') {
            $saida[0]['text'] = ltrim($saida[0]['text']);
        }
        $k = count($saida) - 1;
        if ($k >= 0 && $saida[$k]['type'] === 'text') {
            $saida[$k]['text'] = rtrim($saida[$k]['text']);
        }

        return array_values(array_filter($saida, fn ($n) => $n['type'] !== 'text' || $n['text'] !== ''));
    }
}
