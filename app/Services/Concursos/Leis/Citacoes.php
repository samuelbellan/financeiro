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

        // O resto da citação já vem escapado pelo Markdown: volta ao texto puro e é escapado uma vez só no link.
        return preg_replace_callback(self::PADRAO, function ($m) {
            $link = $this->resolver($m[1], $m[2], html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($link === null) {
                return e(trim($m[0], '[]'));
            }
            $classe = $link['juris'] ? 'lei juris' : 'lei';

            return '<a class="' . $classe . '" href="' . e($link['href']) . '" data-json="' . e($link['json']) . '">' . e($link['rotulo']) . '</a>';
        }, $html);
    }

    /**
     * Uma citação avulsa ("ctn 98", "[[cf 155 §6, III]]") resolvida para o link: rótulo, página e JSON.
     * Usado pelo livro, que guarda a citação como um nó e pede o link ao servidor.
     *
     * @return array{rotulo: string, href: string, json: string, juris: bool}|null
     */
    public function link(string $ref): ?array
    {
        $ref = '[[' . trim(trim($ref), '[]') . ']]';
        if (!preg_match(self::PADRAO, $ref, $m) || $m[0] !== $ref) {
            return null;
        }

        return $this->resolver($m[1], $m[2], $m[3]);
    }

    /** @return array{rotulo: string, href: string, json: string, juris: bool}|null */
    private function resolver(string $sigla, string $num, string $resto): ?array
    {
        $resto = trim($resto);
        $cauda = $resto !== '' ? (str_starts_with($resto, ',') ? '' : ', ') . $resto : '';

        if ($this->juris->existe($sigla) && ctype_digit($num)) {
            $href = route('concursos.juris.show', [$sigla, $num]);

            return ['rotulo' => $this->juris->rotulo($sigla, $num) . $cauda, 'href' => $href, 'json' => $href, 'juris' => true];
        }

        $info = $this->acervo->info($sigla);
        if ($info === null) {
            return null;
        }
        $num = Str::upper($num);

        return [
            'rotulo' => $info['sigla'] . ', art. ' . $num . $cauda,
            'href' => route('concursos.leis.show', $sigla) . '#' . $this->acervo->ancora($sigla, $num),
            'json' => route('concursos.leis.artigo', [$sigla, $num]),
            'juris' => false,
        ];
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
