<?php

namespace App\Services\Concursos;

/**
 * Lê o texto de um caderno do TEC Concursos impresso pelo Chrome ("Salvar como PDF")
 * e devolve as questões estruturadas. Não usa IA: o layout do caderno é fixo.
 *
 * Formato esperado (por questão):
 *   www.tecconcursos.com.br/questoes/3796866          <- âncora (id_externo)
 *   FGV - ADTND (AMAZUL)/AMAZUL/Advogado/2026         <- banca - prova/órgão/cargo.../ano
 *   Direito Civil - Prescrição e Decadência (...)     <- disciplina - assunto (1+ linhas)
 *   1) Enunciado...                                   <- número sequencial
 *   a) ... e)  |  Certo / Errado                      <- alternativas
 * e, no fim do caderno, a página "Gabarito" com "1) D 2) E ... 26) Errado".
 *
 * Regra de segurança: qualquer inconsistência entre questões e gabarito recusa o caderno
 * inteiro (TecParserException), porque um gabarito desalinhado contamina todos os índices.
 */
class TecCadernoParser
{
    private const RE_ANCORA = '~^(?:https?://)?www\.tecconcursos\.com\.br/questoes/(\d+)\s*$~u';
    private const RE_RODAPE = '~^https?://www\.tecconcursos\.com\.br/questoes/cadernos/(\d+)/imprimir\s+\d+/\d+\s*$~u';
    private const RE_TOPO_PAGINA = '~^\d{2}/\d{2}/\d{4}, \d{2}:\d{2} ~u';
    private const RE_URL_CURTA = '~^https?://www\.tecconcursos\.com\.br/s/\S+$~u';
    private const RE_CABECALHO_PROVA = '~^(.+?) - (.+)/(\d{4})$~u';
    private const RE_NUMERO = '~^(\d+)\)\s*(.*)$~u';
    private const RE_ALTERNATIVA = '~^([a-e])\)\s*(.*)$~u';
    private const RE_GABARITO = '~(\d+)\)\s*(Certo|Errado|Anulada|Anulado|[A-E])(?=\s|\d|$)~u';

    /**
     * @param  string[]  $paginas  texto de cada página (TecPdfExtractor)
     * @param  int[]  $imagens  nº de imagens por página (opcional, só para avisos)
     * @return array{caderno: array, questoes: array<int, array>, avisos: string[]}
     */
    public function parse(array $paginas, array $imagens = []): array
    {
        [$linhas, $cadernoId] = $this->limparLinhas($paginas);

        $iGabarito = $this->indiceGabarito($linhas);
        $corpo = array_slice($linhas, 0, $iGabarito);
        $gabarito = $this->lerGabarito(array_slice($linhas, $iGabarito + 1));

        $blocos = $this->separarQuestoes($corpo);
        $caderno = $this->lerCabecalhoCaderno($blocos['antes'], $cadernoId);

        $problemas = [];
        $questoes = [];
        foreach ($blocos['questoes'] as $ordem => [$id, $bloco]) {
            try {
                $questoes[] = $this->lerQuestao($id, $bloco, $caderno['titulo']);
            } catch (TecParserException $e) {
                $problemas[] = "Questão {$id} (posição " . ($ordem + 1) . '): ' . $e->getMessage();
            }
        }

        $problemas = array_merge($problemas, $this->conferir($questoes, $gabarito));
        if ($problemas) {
            throw new TecParserException('Caderno recusado: as questões não batem com o formato ou com o gabarito.', $problemas);
        }

        foreach ($questoes as &$q) {
            $resposta = $gabarito[$q['numero']];
            $q['anulada'] = in_array($resposta, ['Anulada', 'Anulado'], true);
            $q['gabarito'] = $q['anulada'] ? null : $resposta;
        }
        unset($q);

        $avisos = [];
        foreach ($imagens as $i => $n) {
            // A página 1 traz o logotipo do TEC.
            if ($n > ($i === 0 ? 1 : 0)) {
                $avisos[] = 'A página ' . ($i + 1) . ' tem imagem: confira se alguma questão dela depende de figura ou tabela.';
            }
        }

        return ['caderno' => $caderno, 'questoes' => $questoes, 'avisos' => $avisos];
    }

    /** Remove topo e rodapé de cada página e junta tudo numa lista de linhas não vazias. */
    private function limparLinhas(array $paginas): array
    {
        $linhas = [];
        $cadernoId = null;

        foreach ($paginas as $texto) {
            foreach (preg_split('/\R/u', (string) $texto) as $j => $linha) {
                $linha = trim($linha);
                if ($linha === '') {
                    continue;
                }
                if ($j === 0 && preg_match(self::RE_TOPO_PAGINA, $linha)) {
                    continue;
                }
                if (preg_match(self::RE_RODAPE, $linha, $m)) {
                    $cadernoId ??= $m[1];
                    continue;
                }
                $linhas[] = $linha;
            }
        }

        return [$linhas, $cadernoId];
    }

    private function indiceGabarito(array $linhas): int
    {
        for ($i = count($linhas) - 1; $i >= 0; $i--) {
            if ($linhas[$i] === 'Gabarito') {
                return $i;
            }
        }

        throw new TecParserException('Página "Gabarito" não encontrada. Exporte o caderno com o gabarito no final.');
    }

    /** @return array<int, string> número da questão => resposta */
    private function lerGabarito(array $linhas): array
    {
        preg_match_all(self::RE_GABARITO, implode(' ', $linhas), $m, PREG_SET_ORDER);

        $gabarito = [];
        $repetidos = [];
        foreach ($m as [, $numero, $resposta]) {
            $numero = (int) $numero;
            if (isset($gabarito[$numero])) {
                $repetidos[] = $numero;
            }
            $gabarito[$numero] = $resposta;
        }

        if (!$gabarito) {
            throw new TecParserException('A página "Gabarito" está vazia ou em formato desconhecido.');
        }
        if ($repetidos) {
            throw new TecParserException('Gabarito com números repetidos: ' . implode(', ', array_unique($repetidos)));
        }

        return $gabarito;
    }

    /** @return array{antes: string[], questoes: array<int, array{0: string, 1: string[]}>} */
    private function separarQuestoes(array $corpo): array
    {
        $antes = [];
        $questoes = [];
        $atual = null;

        foreach ($corpo as $linha) {
            if (preg_match(self::RE_ANCORA, $linha, $m)) {
                if ($atual) {
                    $questoes[] = $atual;
                }
                $atual = [$m[1], []];
                continue;
            }
            if ($atual) {
                $atual[1][] = $linha;
            } else {
                $antes[] = $linha;
            }
        }
        if ($atual) {
            $questoes[] = $atual;
        }

        if (!$questoes) {
            throw new TecParserException('Nenhuma questão encontrada (nenhum link www.tecconcursos.com.br/questoes/NÚMERO).');
        }

        return ['antes' => $antes, 'questoes' => $questoes];
    }

    private function lerCabecalhoCaderno(array $linhas, ?string $cadernoId): array
    {
        $titulo = null;
        $url = null;
        $ordenacao = null;

        foreach ($linhas as $linha) {
            if (preg_match(self::RE_URL_CURTA, $linha)) {
                $url = $linha;
            } elseif (str_starts_with($linha, 'Ordenação:')) {
                $ordenacao = trim(substr($linha, strlen('Ordenação:')));
            } elseif ($titulo === null) {
                $titulo = $linha;
            }
        }

        return ['id' => $cadernoId, 'titulo' => $titulo, 'url' => $url, 'ordenacao' => $ordenacao];
    }

    private function lerQuestao(string $id, array $linhas, ?string $tituloCaderno): array
    {
        if (count($linhas) < 3) {
            throw new TecParserException('bloco incompleto.');
        }

        $cabecalho = array_shift($linhas);
        if (!preg_match(self::RE_CABECALHO_PROVA, $cabecalho, $m)) {
            throw new TecParserException("cabeçalho da prova em formato desconhecido: \"{$cabecalho}\".");
        }
        $banca = trim($m[1]);
        $partes = explode('/', $m[2]);
        $ano = (int) $m[3];

        // Linhas de classificação (disciplina - assunto) até a linha "N) ...". Nome de assunto longo
        // quebra de linha ("...(Lei nº" / "9.784/1999)"): linha sem " - " continua a anterior.
        $brutas = [];
        while ($linhas && !preg_match(self::RE_NUMERO, $linhas[0])) {
            $linha = array_shift($linhas);
            if ($brutas && !str_contains($linha, ' - ')) {
                $brutas[count($brutas) - 1] .= ' ' . $linha;
            } else {
                $brutas[] = $linha;
            }
        }
        $assuntos = array_map(fn ($l) => $this->lerAssunto($l, $tituloCaderno), $brutas);
        if (!$assuntos) {
            throw new TecParserException('linha de disciplina/assunto ausente.');
        }
        if (!$linhas) {
            throw new TecParserException('número da questão ("N)") não encontrado.');
        }

        preg_match(self::RE_NUMERO, array_shift($linhas), $mn);
        $numero = (int) $mn[1];
        array_unshift($linhas, $mn[2]);

        [$tipo, $enunciado, $alternativas] = $this->lerCorpo($linhas);

        return [
            'id_externo' => $id,
            'numero' => $numero,
            'banca' => $banca,
            'banca_sigla' => trim(preg_replace('/\s*\(.*\)$/u', '', $banca)),
            'prova' => trim($partes[0]),
            'orgao' => isset($partes[1]) ? trim($partes[1]) : null,
            'cargo' => count($partes) > 2 ? trim(implode('/', array_slice($partes, 2))) : null,
            'ano' => $ano,
            'cabecalho' => $cabecalho,
            'assuntos' => $assuntos,
            'tipo' => $tipo,
            'enunciado' => $enunciado,
            'alternativas' => $alternativas,
        ];
    }

    /**
     * "Disciplina - Assunto". O assunto pode conter " - " (ex.: "Disposições Gerais - Recursos"):
     * se a linha termina com o título do caderno, o corte é feito ali; senão, no primeiro " - ".
     */
    private function lerAssunto(string $linha, ?string $tituloCaderno): array
    {
        if ($tituloCaderno && str_ends_with($linha, ' - ' . $tituloCaderno)) {
            $disciplina = substr($linha, 0, -strlen(' - ' . $tituloCaderno));

            return ['disciplina' => $disciplina, 'assunto' => $tituloCaderno, 'linha' => $linha];
        }

        $pos = strpos($linha, ' - ');
        if ($pos === false) {
            throw new TecParserException("linha de disciplina/assunto sem \" - \": \"{$linha}\".");
        }

        return ['disciplina' => substr($linha, 0, $pos), 'assunto' => substr($linha, $pos + 3), 'linha' => $linha];
    }

    /** @return array{0: string, 1: string, 2: array<int, array{letra: string, texto: string}>} */
    private function lerCorpo(array $linhas): array
    {
        $n = count($linhas);

        if ($n >= 3 && $linhas[$n - 2] === 'Certo' && $linhas[$n - 1] === 'Errado') {
            return [
                'CE',
                $this->juntar(array_slice($linhas, 0, $n - 2)),
                [['letra' => 'Certo', 'texto' => 'Certo'], ['letra' => 'Errado', 'texto' => 'Errado']],
            ];
        }

        // Alternativas: a ÚLTIMA linha "a)" seguida de b), c)... em ordem.
        $inicio = null;
        for ($i = $n - 1; $i >= 0; $i--) {
            if (preg_match('~^a\)~u', $linhas[$i])) {
                $inicio = $i;
                break;
            }
        }
        if ($inicio === null) {
            throw new TecParserException('alternativas (a) ... ou Certo/Errado) não encontradas.');
        }

        $alternativas = [];
        $esperada = 'a';
        $atual = null;
        foreach (array_slice($linhas, $inicio) as $linha) {
            if (preg_match(self::RE_ALTERNATIVA, $linha, $m) && $m[1] === $esperada) {
                if ($atual) {
                    $alternativas[] = $atual;
                }
                $atual = ['letra' => strtoupper($m[1]), 'linhas' => [$m[2]]];
                $esperada = chr(ord($esperada) + 1);
                continue;
            }
            $atual['linhas'][] = $linha;
        }
        $alternativas[] = $atual;

        if (count($alternativas) < 2) {
            throw new TecParserException('menos de duas alternativas encontradas.');
        }

        $alternativas = array_map(fn ($a) => ['letra' => $a['letra'], 'texto' => $this->juntar($a['linhas'])], $alternativas);

        return ['ME', $this->juntar(array_slice($linhas, 0, $inicio)), $alternativas];
    }

    /**
     * Junta as linhas quebradas pela largura da página. Abre parágrafo novo quando a linha
     * anterior termina em pontuação forte e a nova começa com maiúscula, número romano ou item.
     */
    private function juntar(array $linhas): string
    {
        $texto = '';
        foreach ($linhas as $linha) {
            if ($texto === '') {
                $texto = $linha;
                continue;
            }
            $fimDeFrase = preg_match('~[.:;?!)]$~u', $texto);
            $comecoDeBloco = preg_match('~^([A-ZÀ-Ý"“(]|[IVX]+[.)\s]|\d+[.)]\s|[a-z]\)\s)~u', $linha);
            $texto .= ($fimDeFrase && $comecoDeBloco ? "\n" : ' ') . $linha;
        }

        return trim(preg_replace('/[ \t]{2,}/u', ' ', $texto));
    }

    /** @return string[] problemas encontrados */
    private function conferir(array $questoes, array $gabarito): array
    {
        $problemas = [];

        // Cadernos grandes são impressos em partes (1-50, 51-100...): a numeração pode começar em
        // qualquer número, mas tem de ser consecutiva e cada número precisa estar no gabarito.
        $primeiro = $questoes[0]['numero'] ?? 1;
        foreach ($questoes as $i => $q) {
            if ($q['numero'] !== $primeiro + $i) {
                $problemas[] = "Questão {$q['id_externo']}: número impresso {$q['numero']}, esperado " . ($primeiro + $i) . ' (ordem quebrada).';
            }
        }
        $sobrando = array_diff(array_keys($gabarito), array_column($questoes, 'numero'));
        if ($sobrando && count($questoes) === count($gabarito)) {
            $problemas[] = 'O gabarito tem números que não correspondem a nenhuma questão: ' . implode(', ', $sobrando) . '.';
        }

        $ids = array_column($questoes, 'id_externo');
        foreach (array_unique(array_diff_assoc($ids, array_unique($ids))) as $dup) {
            $problemas[] = "Questão {$dup} aparece mais de uma vez no caderno.";
        }

        if (count($questoes) !== count($gabarito)) {
            $problemas[] = count($questoes) . ' questões lidas, mas o gabarito tem ' . count($gabarito) . ' respostas.';
        }

        foreach ($questoes as $q) {
            $resposta = $gabarito[$q['numero']] ?? null;
            if ($resposta === null) {
                $problemas[] = "Questão {$q['numero']} ({$q['id_externo']}) sem resposta no gabarito.";
                continue;
            }
            if (in_array($resposta, ['Anulada', 'Anulado'], true)) {
                continue;
            }
            $letras = array_column($q['alternativas'], 'letra');
            if (!in_array($resposta, $letras, true)) {
                $problemas[] = "Questão {$q['numero']} ({$q['id_externo']}): gabarito \"{$resposta}\" não é uma alternativa válida ("
                    . implode('/', $letras) . ').';
            }
        }

        return $problemas;
    }
}
