<?php

namespace App\Services\Concursos\Leis;

use Illuminate\Support\Str;

/**
 * Texto do material de resumo (Markdown) → HTML, trocando as citações por links:
 * - leis: [[ctn 98]], [[ctn 106, II, "a"]], [[cf 155 §6, III]], [[adct 136]], [[lei8137 1]] (com a cópia local);
 * - precedentes: [[sv 24]], [[sumstf 160]], [[sumstj 392]], [[temastf 1124]], [[temastj 290]], [[adi 5135]].
 * Sigla desconhecida fica como texto. Todo link leva data-json, que a tela usa para abrir o texto num diálogo.
 */
class Citacoes
{
    public const PADRAO = '/\[\[([a-z][a-z0-9]*)\s+(\d+(?:-[A-Za-z])?)([^\]]*)\]\]/u';

    public function __construct(private readonly AcervoLeis $acervo, private readonly Jurisprudencia $juris)
    {
    }

    public function html(?string $md): string
    {
        if ($md === null || trim($md) === '') {
            return '';
        }

        $html = Str::markdown($md, ['html_input' => 'escape', 'allow_unsafe_links' => false]);

        return preg_replace_callback(self::PADRAO, function ($m) {
            [$tudo, $sigla, $num, $resto] = $m;
            $resto = trim($resto);
            $cauda = $resto !== '' ? (str_starts_with($resto, ',') ? '' : ', ') . $resto : '';

            // $resto já vem escapado pelo Markdown; o resto é montado de partes validadas pela regex.
            if ($this->juris->existe($sigla) && ctype_digit($num)) {
                $href = route('concursos.juris.show', [$sigla, $num]);

                return '<a class="lei juris" href="' . e($href) . '" data-json="' . e($href) . '">' . e($this->juris->rotulo($sigla, $num)) . $cauda . '</a>';
            }

            $info = $this->acervo->info($sigla);
            if ($info === null) {
                return e(trim($tudo, '[]'));
            }
            $num = Str::upper($num);
            $href = route('concursos.leis.show', $sigla) . '#' . $this->acervo->ancora($sigla, $num);
            $json = route('concursos.leis.artigo', [$sigla, $num]);

            return '<a class="lei" href="' . e($href) . '" data-json="' . e($json) . '">' . e($info['sigla']) . ', art. ' . $num . $cauda . '</a>';
        }, $html);
    }

    /**
     * Citações de lei de um texto, para o índice reverso (lei → pontos que citam cada artigo).
     *
     * @return list<array{lei: string, ancora: string}>
     */
    public function extrair(?string $md): array
    {
        if ($md === null || !preg_match_all(self::PADRAO, $md, $mm, PREG_SET_ORDER)) {
            return [];
        }
        $saida = [];
        foreach ($mm as $m) {
            if (!$this->juris->existe($m[1]) && $this->acervo->existe($m[1])) {
                $saida[] = ['lei' => $m[1], 'ancora' => $this->acervo->ancora($m[1], Str::upper($m[2]))];
            }
        }

        return $saida;
    }
}
