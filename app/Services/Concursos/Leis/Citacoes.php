<?php

namespace App\Services\Concursos\Leis;

use Illuminate\Support\Str;

/**
 * Texto do material de resumo (Markdown) → HTML, trocando as citações [[sigla artigo resto]] por
 * links para o artigo na lei seca. Exemplos: [[ctn 98]], [[ctn 106, II, "a"]], [[cf 155 §6, III]],
 * [[adct 136]]. Sigla fora do catálogo fica como texto.
 */
class Citacoes
{
    public const PADRAO = '/\[\[([a-z][a-z0-9]*)\s+(\d+(?:-[A-Za-z])?)([^\]]*)\]\]/u';

    public function __construct(private readonly AcervoLeis $acervo)
    {
    }

    public function html(?string $md): string
    {
        if ($md === null || trim($md) === '') {
            return '';
        }

        $html = Str::markdown($md, ['html_input' => 'escape', 'allow_unsafe_links' => false]);

        return preg_replace_callback(self::PADRAO, function ($m) {
            [$tudo, $lei, $art, $resto] = $m;
            $catalogo = $this->acervo->catalogo();
            if (!isset($catalogo[$lei])) {
                return e(trim($tudo, '[]'));
            }
            $art = Str::upper($art);
            $resto = trim($resto);
            $rotulo = $catalogo[$lei]['sigla'] . ', art. ' . $art . ($resto !== '' ? (str_starts_with($resto, ',') ? '' : ', ') . $resto : '');
            $href = route('concursos.leis.show', $lei) . '#' . $this->acervo->ancora($lei, $art);

            // $resto já vem escapado pelo Markdown; o resto é montado de partes validadas pela regex.
            return '<a class="lei" href="' . e($href) . '" data-lei="' . e($lei) . '" data-art="' . e($art) . '">' . $rotulo . '</a>';
        }, $html);
    }

    /**
     * Citações de um texto, para o índice reverso (lei → pontos que citam cada artigo).
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
            if ($this->acervo->existe($m[1])) {
                $saida[] = ['lei' => $m[1], 'ancora' => $this->acervo->ancora($m[1], Str::upper($m[2]))];
            }
        }

        return $saida;
    }
}
