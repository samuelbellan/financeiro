<?php

namespace App\Services\Concursos\Leis;

/**
 * Acha de novo um grifo no texto de um artigo, que pode ter mudado desde que o grifo foi feito
 * (lei baixada de novo, emenda). O grifo guarda o trecho e ~30 caracteres antes e depois.
 *
 * - O trecho é procurado com os espaços flexíveis (quebra de linha ou espaço duplo não o perdem).
 * - Várias ocorrências: vence a que tem mais contexto em comum (sufixo do "antes" + prefixo do "depois");
 *   empate fica com a primeira.
 * - Nenhuma: null, e o grifo vira órfão (mostrado no artigo, nunca apagado).
 *
 * Posições em caracteres (code points), contadas sobre o texto puro do artigo como o navegador o
 * vê (textContent do HTML renderizado).
 */
class Reancorador
{
    public const CONTEXTO = 30;

    /** Texto puro do HTML de um artigo, igual ao textContent do navegador. */
    public static function textoDoHtml(string $html): string
    {
        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** @return array{inicio: int, fim: int}|null */
    public function localizar(string $texto, string $trecho, string $antes = '', string $depois = ''): ?array
    {
        $palavras = preg_split('/\s+/u', trim($trecho), -1, PREG_SPLIT_NO_EMPTY);
        if (!$palavras) {
            return null;
        }
        $padrao = '/' . implode('\s+', array_map(fn ($p) => preg_quote($p, '/'), $palavras)) . '/u';
        if (!preg_match_all($padrao, $texto, $mm, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $melhor = null;
        $nota = -1;
        foreach ($mm[0] as [$achado, $byte]) {
            $inicio = mb_strlen(substr($texto, 0, $byte));
            $fim = $inicio + mb_strlen($achado);
            $pontos = $this->sufixoComum($antes, mb_substr($texto, max(0, $inicio - self::CONTEXTO), min($inicio, self::CONTEXTO)))
                + $this->prefixoComum($depois, mb_substr($texto, $fim, self::CONTEXTO));
            if ($pontos > $nota) {
                $nota = $pontos;
                $melhor = ['inicio' => $inicio, 'fim' => $fim];
            }
        }

        return $melhor;
    }

    /**
     * Posição atual de cada grifo de um artigo; os que não se acham mais saem com inicio/fim null (órfãos).
     *
     * @param  iterable<\App\Models\Concursos\LeiGrifo>  $grifos
     * @return list<array{id: int, cor: string, trecho: string, inicio: ?int, fim: ?int}>
     */
    public function posicionar(string $texto, iterable $grifos): array
    {
        $saida = [];
        foreach ($grifos as $g) {
            $p = $this->localizar($texto, $g->trecho, $g->antes, $g->depois);
            $saida[] = ['id' => $g->id, 'cor' => $g->cor, 'trecho' => $g->trecho, 'inicio' => $p['inicio'] ?? null, 'fim' => $p['fim'] ?? null];
        }

        return $saida;
    }

    /**
     * Posição de um ponto (nota) pelo contexto: o fim do "antes" colado ao começo do "depois", com
     * espaços flexíveis; sem isso, só o "antes" (o ponto fica no fim dele) ou só o "depois".
     * null quando nenhum dos dois aparece mais.
     */
    public function localizarPonto(string $texto, string $antes, string $depois): ?int
    {
        $pa = preg_split('/\s+/u', trim($antes), -1, PREG_SPLIT_NO_EMPTY);
        $pd = preg_split('/\s+/u', trim($depois), -1, PREG_SPLIT_NO_EMPTY);
        $q = fn (array $ps) => implode('\s+', array_map(fn ($p) => preg_quote($p, '/'), $ps));
        $tentativas = [];
        if ($pa && $pd) {
            $meio = preg_match('/\s$/u', $antes) || preg_match('/^\s/u', $depois) ? '\s+' : '';
            $tentativas[] = ['/(' . $q($pa) . ')' . $meio . $q($pd) . '/u', 'fim'];
        }
        if ($pa) {
            $tentativas[] = ['/(' . $q($pa) . ')/u', 'fim'];
        }
        if ($pd) {
            $tentativas[] = ['/(' . $q($pd) . ')/u', 'inicio'];
        }
        foreach ($tentativas as [$padrao, $lado]) {
            if (preg_match($padrao, $texto, $m, PREG_OFFSET_CAPTURE)) {
                $byte = $lado === 'fim' ? $m[1][1] + strlen($m[1][0]) : $m[1][1];

                return mb_strlen(substr($texto, 0, $byte));
            }
        }

        return $pa || $pd ? null : 0;
    }

    /** Contexto em volta de um intervalo, para guardar junto do grifo. @return array{antes: string, depois: string} */
    public function contexto(string $texto, int $inicio, int $fim): array
    {
        return [
            'antes' => mb_substr($texto, max(0, $inicio - self::CONTEXTO), min($inicio, self::CONTEXTO)),
            'depois' => mb_substr($texto, $fim, self::CONTEXTO),
        ];
    }

    private function sufixoComum(string $a, string $b): int
    {
        $a = $this->normalizar($a);
        $b = $this->normalizar($b);
        $n = 0;
        $la = mb_strlen($a);
        $lb = mb_strlen($b);
        while ($n < $la && $n < $lb && mb_substr($a, $la - $n - 1, 1) === mb_substr($b, $lb - $n - 1, 1)) {
            $n++;
        }

        return $n;
    }

    private function prefixoComum(string $a, string $b): int
    {
        $a = $this->normalizar($a);
        $b = $this->normalizar($b);
        $n = 0;
        $max = min(mb_strlen($a), mb_strlen($b));
        while ($n < $max && mb_substr($a, $n, 1) === mb_substr($b, $n, 1)) {
            $n++;
        }

        return $n;
    }

    private function normalizar(string $s): string
    {
        return preg_replace('/\s+/u', ' ', $s);
    }
}
