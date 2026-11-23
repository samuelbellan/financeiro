<?php

namespace App\Services\Concursos\Livro;

/**
 * Acha um trecho no documento do capítulo e põe nele a marca grifo {cor, id}.
 * O trecho tem de caber num só bloco de texto (parágrafo, título, item de lista, célula).
 * Se o trecho já estiver (no todo ou em parte) num grifo com nota, devolve o id desse grifo,
 * sem mexer: a nota dele passa a reunir as duas coisas.
 */
class Grifador
{
    private const BLOCOS_DE_TEXTO = ['paragraph', 'heading'];

    /** @return array{0: array, 1: ?string} [documento, id do grifo usado ou null se não achou] */
    public function grifar(array $doc, string $trecho, string $id, string $cor): array
    {
        $trecho = $this->espacos($trecho);
        if ($trecho === '') {
            return [$doc, null];
        }
        $achado = null;
        $doc['content'] = $this->andar($doc['content'] ?? [], $trecho, $id, $cor, $achado);

        return [$doc, $achado];
    }

    private function andar(array $nos, string $trecho, string $id, string $cor, ?string &$achado): array
    {
        foreach ($nos as $i => $no) {
            if ($achado !== null) {
                break;
            }
            if (in_array($no['type'] ?? '', self::BLOCOS_DE_TEXTO, true)) {
                [$novo, $usado] = $this->noBloco($no, $trecho, $id, $cor);
                if ($usado !== null) {
                    $nos[$i] = $novo;
                    $achado = $usado;
                }
            } elseif (!empty($no['content']) && is_array($no['content'])) {
                $nos[$i]['content'] = $this->andar($no['content'], $trecho, $id, $cor, $achado);
            }
        }

        return $nos;
    }

    /** @return array{0: array, 1: ?string} */
    private function noBloco(array $bloco, string $trecho, string $id, string $cor): array
    {
        $filhos = $bloco['content'] ?? [];
        // Texto do bloco, com cada nó que não é texto valendo um caractere (não deixa o trecho atravessá-lo).
        $texto = '';
        foreach ($filhos as $f) {
            $texto .= ($f['type'] ?? '') === 'text' ? (string) $f['text'] : "\u{FFFC}";
        }
        $pos = mb_strpos($texto, $trecho);
        if ($pos === false) {
            $pos = mb_stripos($texto, $trecho);
        }
        if ($pos === false) {
            return [$bloco, null];
        }
        $ini = $pos;
        $fim = $pos + mb_strlen($trecho);

        // Já grifado com nota? Reaproveita.
        $cursor = 0;
        foreach ($filhos as $f) {
            $len = ($f['type'] ?? '') === 'text' ? mb_strlen((string) $f['text']) : 1;
            if ($cursor < $fim && $cursor + $len > $ini) {
                foreach ($f['marks'] ?? [] as $m) {
                    if (($m['type'] ?? '') === 'grifo' && !empty($m['attrs']['id'])) {
                        return [$bloco, $m['attrs']['id']];
                    }
                }
            }
            $cursor += $len;
        }

        $novos = [];
        $cursor = 0;
        foreach ($filhos as $f) {
            if (($f['type'] ?? '') !== 'text') {
                $novos[] = $f;
                $cursor++;
                continue;
            }
            $t = (string) $f['text'];
            $len = mb_strlen($t);
            $a = max($ini, $cursor) - $cursor;
            $b = min($fim, $cursor + $len) - $cursor;
            if ($b <= $a) {
                $novos[] = $f;
            } else {
                $marcas = array_values(array_filter($f['marks'] ?? [], fn ($m) => ($m['type'] ?? '') !== 'grifo'));
                foreach ([[0, $a, false], [$a, $b, true], [$b, $len, false]] as [$de, $ate, $grifa]) {
                    if ($ate <= $de) {
                        continue;
                    }
                    $pedaco = ['type' => 'text', 'text' => mb_substr($t, $de, $ate - $de)];
                    $m = $grifa ? [...$marcas, ['type' => 'grifo', 'attrs' => ['cor' => $cor, 'id' => $id]]] : ($f['marks'] ?? []);
                    if ($m) {
                        $pedaco['marks'] = $m;
                    }
                    $novos[] = $pedaco;
                }
            }
            $cursor += $len;
        }
        $bloco['content'] = $novos;

        return [$bloco, $id];
    }

    private function espacos(string $t): string
    {
        return trim(preg_replace('/\s+/u', ' ', $t));
    }
}
