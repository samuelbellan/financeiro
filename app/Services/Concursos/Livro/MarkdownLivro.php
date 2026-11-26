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
        '\\times' => '×', '\\cdot' => '·', '\\to' => '→', '\\rightarrow' => '→', '\\Rightarrow' => '⇒', '\\%' => '%', '\\,' => ' ',
        '\\approx' => '≈', '\\div' => '÷', '\\pm' => '±', '\\infty' => '∞', '\\Delta' => 'Δ', '\\sum' => 'Σ', '\\dots' => '…',
        '\\ldots' => '…', '\\cdots' => '⋯', '\\leftrightarrow' => '↔', '\\Leftrightarrow' => '⇔', '\\lceil' => '⌈', '\\rceil' => '⌉',
        '\\lfloor' => '⌊', '\\rfloor' => '⌋', '\\ln' => 'ln', '\\log' => 'log', '\\quad' => ' ', '\\qquad' => ' ', '\\ ' => ' ',
        '\\left' => '', '\\right' => '', '\\_' => '_'];

    private const SOBRESCRITO = ['0' => '⁰', '1' => '¹', '2' => '²', '3' => '³', '4' => '⁴', '5' => '⁵', '6' => '⁶', '7' => '⁷', '8' => '⁸',
        '9' => '⁹', '+' => '⁺', '-' => '⁻', '(' => '⁽', ')' => '⁾', 'n' => 'ⁿ', 'i' => 'ⁱ', 't' => 'ᵗ', 'k' => 'ᵏ', 'm' => 'ᵐ', 'x' => 'ˣ'];

    private const SUBSCRITO = ['0' => '₀', '1' => '₁', '2' => '₂', '3' => '₃', '4' => '₄', '5' => '₅', '6' => '₆', '7' => '₇', '8' => '₈',
        '9' => '₉', '+' => '₊', '-' => '₋', 'a' => 'ₐ', 'e' => 'ₑ', 'o' => 'ₒ', 'x' => 'ₓ', 'i' => 'ᵢ', 'j' => 'ⱼ', 'k' => 'ₖ', 'n' => 'ₙ',
        'm' => 'ₘ', 't' => 'ₜ', 'p' => 'ₚ', 's' => 'ₛ', 'r' => 'ᵣ', 'h' => 'ₕ', 'l' => 'ₗ', 'u' => 'ᵤ', 'v' => 'ᵥ'];

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
            $f = $this->fracoes($f);
            $f = $this->indices($f);
            $f = strtr($f, self::SIMBOLOS);
            $f = preg_replace('/\\\\[a-zA-Z]+/u', '', $f);

            return trim(str_replace(['{', '}'], '', $f));
        }, $md);

        return str_replace("\u{E000}", '\\$', $md);
    }

    /** \frac{a}{b} → a/(b) e \sqrt{x} → √(x), de dentro para fora; parênteses só quando precisa. */
    private function fracoes(string $f): string
    {
        $grupo = '\{((?:[^{}]|\{(?-1)\})*)\}';
        for ($i = 0; $i < 20 && preg_match('/\\\\(?:frac|sqrt)/', $f); $i++) {
            $antes = $f;
            $f = preg_replace_callback("/\\\\frac\\s*{$grupo}\\s*{$grupo}/u",
                fn ($m) => $this->atomo($this->fracoes($m[1])) . '/' . $this->atomo($this->fracoes($m[2])), $f);
            $f = preg_replace_callback("/\\\\sqrt\\s*(?:\\[[^\\]]*\\])?\\s*{$grupo}/u",
                fn ($m) => '√' . $this->atomo($this->fracoes($m[1])), $f);
            if ($f === $antes) {
                break;
            }
        }

        return $f;
    }

    /** Envolve em parênteses o que não é um termo só ("1 + i" → "(1 + i)"; "VF" e "(1 + i)" ficam). */
    private function atomo(string $s): string
    {
        $s = trim($s);
        $t = preg_replace('/[\^_](?:\{[^{}]*\}|[\pL\pN])/u', '', $s); // índices não contam
        if (preg_match('/^[\pL\pN.,%]+$/u', $t) || preg_match('/^\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)$/u', $t)) {
            return $s;
        }

        return '(' . $s . ')';
    }

    /** x^{n} → xⁿ e VP_{1} → VP₁ quando há o caractere Unicode; senão x^(n+1) / VP_liq. */
    private function indices(string $f): string
    {
        $troca = function (string $txt, array $mapa, string $sinal): string {
            $txt = trim(str_replace(['{', '}'], '', $txt));
            $chars = mb_str_split($txt);
            // Palavra inteira no índice (P_{final}) fica legível como P_final; só número ou letra solta vira Unicode.
            $ok = $chars && !array_diff($chars, array_keys($mapa)) && (count($chars) === 1 || preg_match('/^[\pN+\-()]+$/u', $txt));

            return $ok ? implode('', array_map(fn ($c) => $mapa[$c], $chars))
                : $sinal . (mb_strlen($txt) > 1 && $sinal === '^' ? '(' . $txt . ')' : $txt);
        };
        $grupo = '\{((?:[^{}]|\{(?-1)\})*)\}';
        $f = str_replace(['\\ ', '\\_'], [' ', "\u{E001}"], $f);
        $f = preg_replace_callback("/\\^(?:{$grupo}|([\\pL\\pN]))/u", fn ($m) => $troca($m[1] !== '' ? $m[1] : ($m[2] ?? ''), self::SOBRESCRITO, '^'), $f);

        $f = preg_replace_callback("/_(?:{$grupo}|([\\pL\\pN]))/u", fn ($m) => $troca($m[1] !== '' ? $m[1] : ($m[2] ?? ''), self::SUBSCRITO, '_'), $f);

        return str_replace("\u{E001}", '_', $f); // "\_" literal (TOTAL\_ENTRADAS) não é índice
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
