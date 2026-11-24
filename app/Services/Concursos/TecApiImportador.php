<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Importacao;
use App\Models\Concursos\Questao;
use App\Models\Concursos\QuestaoResolucao;

/**
 * Importa um caderno do TEC baixado pela API (um JSON por caderno, gravado pelo receptor local em
 * storage/concursos/tec/<slug>/) para o banco de questões. Reaproveita o TecImportador::gravar, então
 * vale o mesmo: chave (fonte, id_externo), gabarito sempre da fonte, gabarito alterado listado.
 *
 * Formato do arquivo (cada questão):
 *   {id, tipo: MULTIPLA_ESCOLHA|CERTO_ERRADO, banca, orgao, cargo, ano, prova?, materia,
 *    assuntos: [nome, ...] (ou assunto: "nome"), anulada, desatualizada,
 *    enunciado (HTML), alternativas: [HTML, ...], correta (1-based; CE: 1 = Certo),
 *    comentario: {professor, texto (HTML)} | null}
 *
 * O comentário do professor vira a resolução (fonte 'tec') quando a questão não tem resolução da IA;
 * sem comentário, fica uma resolução mínima com o gabarito oficial, para a questão nunca ficar sem.
 */
class TecApiImportador
{
    private const BLOCO = 'p|div|li|tr|h[1-6]|table|ul|ol|blockquote';

    public function __construct(private TecImportador $tec) {}

    /** @return array{importacao_id: int, status: string, resumo: array} */
    public function importarArquivo(string $arquivo, ?string $disciplinaPadrao = null, ?int $userId = null): array
    {
        $salvo = $this->tec->guardarOrigem($arquivo);
        $dados = json_decode((string) file_get_contents($arquivo), true);

        try {
            $lido = $this->converter(is_array($dados) ? $dados : [], $disciplinaPadrao);
        } catch (TecParserException $e) {
            Importacao::create([
                'tipo' => 'api_tec', 'arquivo_original' => $arquivo, 'arquivo_salvo' => $salvo['caminho'],
                'sha256' => $salvo['sha256'], 'status' => 'recusada', 'user_id' => $userId,
                'resumo' => ['erro' => $e->getMessage(), 'problemas' => $e->problemas],
            ]);

            throw $e;
        }

        return $this->gravar($lido, $arquivo, $salvo, $userId);
    }

    /** @param array{caminho: string, sha256: string} $salvo */
    public function gravar(array $lido, string $arquivoOriginal, array $salvo, ?int $userId = null): array
    {
        $contagem = ['resolucoes_tec' => 0, 'resolucoes_minimas' => 0, 'resolucoes_ia_mantidas' => 0];

        $r = $this->tec->gravar($lido, $arquivoOriginal, $salvo, $userId, 'api_tec',
            function (Questao $questao, array $q, Importacao $imp) use (&$contagem) {
                $atual = QuestaoResolucao::where('questao_id', $questao->id)->first();
                if ($atual && $atual->fonte !== 'tec') {
                    $contagem['resolucoes_ia_mantidas']++;

                    return;
                }
                $c = $q['comentario'];
                $contagem[$c ? 'resolucoes_tec' : 'resolucoes_minimas']++;
                QuestaoResolucao::updateOrCreate(['questao_id' => $questao->id], [
                    'fonte' => 'tec', 'autor' => $c['professor'] ?? null, 'importacao_id' => $imp->id, 'lote_id' => null,
                    'resumo' => $c['texto'] ?? $this->resolucaoMinima($q), 'fundamentacao' => null,
                    'dispositivos' => null, 'alternativas' => [],
                ]);
            });

        $r['resumo'] += $contagem + ['puladas' => $lido['puladas'] ?? []];
        Importacao::whereKey($r['importacao_id'])->update(['resumo' => $r['resumo']]);

        return $r;
    }

    /**
     * JSON do caderno -> estrutura do TecCadernoParser (mais desatualizada e comentario).
     * Questão sem enunciado, sem alternativas ou sem gabarito recusa o caderno inteiro: só entra caderno completo.
     */
    public function converter(array $dados, ?string $disciplinaPadrao = null): array
    {
        $problemas = [];
        $questoes = [];
        $avisos = [];
        $puladas = [];
        // Caderno ainda não resolvido: a API não traz a alternativa correta; o gabarito sai do comentário do professor.
        $doComentario = ($dados['gabarito'] ?? null) === 'comentario';

        if (empty($dados['questoes']) || !is_array($dados['questoes'])) {
            throw new TecParserException('Caderno sem questões.');
        }

        foreach ($dados['questoes'] as $n => $q) {
            $id = (string) ($q['id'] ?? '');
            $ref = '#' . ($n + 1) . ($id !== '' ? " ({$id})" : '');
            if (!preg_match('/^\d{1,20}$/', $id)) {
                $problemas[] = "{$ref}: sem id do TEC.";
                continue;
            }

            [$enunciado, $figuras] = $this->texto($q['enunciado'] ?? '');
            if ($enunciado === '' && !$figuras) {
                $problemas[] = "{$ref}: enunciado vazio.";
                continue;
            }
            if ($figuras) {
                $enunciado .= "\n\n(Esta questão tem " . ($figuras === 1 ? 'figura' : "{$figuras} figuras") . ': veja no TEC.)';
                $avisos[] = "{$id}: tem figura.";
            }

            $ce = ($q['tipo'] ?? '') === 'CERTO_ERRADO';
            $anulada = (bool) ($q['anulada'] ?? false);
            $correta = isset($q['correta']) ? (int) $q['correta'] : null;
            $textoComentario = !empty($q['comentario']['texto']) ? $this->texto($q['comentario']['texto'])[0] : '';
            if ($correta === null && $doComentario && !$anulada) {
                $correta = $this->gabaritoDoComentario($textoComentario, $ce, count($q['alternativas'] ?? []));
                if ($correta === null) {
                    $puladas[] = $id;
                    continue;
                }
            }

            if ($ce) {
                $alternativas = [['letra' => 'Certo', 'texto' => 'Certo'], ['letra' => 'Errado', 'texto' => 'Errado']];
                $gabarito = match ($correta) { 1 => 'Certo', 2 => 'Errado', default => null };
            } else {
                $alternativas = [];
                foreach (array_values($q['alternativas'] ?? []) as $i => $html) {
                    $alternativas[] = ['letra' => chr(65 + $i), 'texto' => $this->texto($html)[0]];
                }
                $gabarito = $correta && $correta <= count($alternativas) ? chr(64 + $correta) : null;
                if (count($alternativas) < 2) {
                    $problemas[] = "{$ref}: menos de duas alternativas.";
                    continue;
                }
            }
            if ($anulada) {
                $gabarito = null;
            } elseif ($gabarito === null) {
                $problemas[] = "{$ref}: sem gabarito.";
                continue;
            }

            $nomes = $q['assuntos'] ?? (isset($q['assunto']) && $q['assunto'] !== '' ? [$q['assunto']] : []);
            $nomes = array_values(array_unique(array_filter(array_map(fn ($a) => trim((string) $a), (array) $nomes))));
            $disciplina = trim((string) ($q['materia'] ?? '')) ?: $disciplinaPadrao;
            $assuntos = [];
            foreach ($nomes as $nome) {
                $assuntos[] = ['disciplina' => $disciplina ?: $this->disciplinaPorNome($nome), 'assunto' => $nome];
            }
            if (!$assuntos) {
                $avisos[] = "{$id}: sem assunto no TEC.";
            }

            $banca = trim((string) ($q['banca'] ?? '')) ?: '(sem banca)';
            $ano = (int) ($q['ano'] ?? 0);
            $partes = array_filter([$banca, $ano ?: null, $q['orgao'] ?? null, $q['cargo'] ?? null]);
            $comentario = $textoComentario === '' ? null
                : ['professor' => trim((string) ($q['comentario']['professor'] ?? '')) ?: null, 'texto' => $textoComentario];

            $questoes[] = [
                'id_externo' => $id,
                'banca' => mb_substr($banca, 0, 120),
                'banca_sigla' => mb_substr(trim(preg_replace('/\s*\(.*\)$/u', '', $banca)), 0, 60),
                'prova' => isset($q['prova']) ? mb_substr((string) $q['prova'], 0, 200) : null,
                'orgao' => isset($q['orgao']) ? mb_substr((string) $q['orgao'], 0, 150) : null,
                'cargo' => isset($q['cargo']) ? mb_substr((string) $q['cargo'], 0, 250) : null,
                'ano' => $ano,
                'cabecalho' => mb_substr(implode(' - ', $partes), 0, 400),
                'tipo' => $ce ? 'CE' : 'ME',
                'enunciado' => $enunciado,
                'gabarito' => $gabarito,
                'anulada' => $anulada,
                'desatualizada' => (bool) ($q['desatualizada'] ?? false),
                'alternativas' => $alternativas,
                'assuntos' => $assuntos,
                'comentario' => $comentario,
            ];
        }

        if ($problemas) {
            throw new TecParserException('Caderno incompleto: ' . count($problemas) . ' questão(ões) com problema.', $problemas);
        }

        return [
            'caderno' => ['titulo' => (string) ($dados['caderno'] ?? 'Caderno TEC'), 'id' => $dados['cadernoId'] ?? null, 'total' => count($questoes)],
            'questoes' => $questoes,
            'avisos' => $avisos,
            'puladas' => $puladas,
        ];
    }

    /**
     * "Gabarito: Letra B", "Gabarito: Certo", "Gabarito oficial: errado"... (a última menção vale).
     * Devolve a posição 1-based (CE: 1 = Certo, 2 = Errado) ou null se não houver gabarito claro.
     */
    public function gabaritoDoComentario(string $texto, bool $ce, int $alternativas): ?int
    {
        if (!preg_match_all('/gabarito/iu', $texto, $ocorrencias, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $v = null;
        // Da última menção para trás: "Gabarito: B", "Gabarito: Certo", "gabarito ... é a letra C".
        foreach (array_reverse($ocorrencias[0]) as [$palavra, $offset]) {
            $trecho = substr($texto, $offset + strlen($palavra), 120);
            if (preg_match('/^[^.\n]{0,80}?\b(?:letra|alternativa|item|op[cç][aã]o)\s*["“(]?\s*([A-E])(?![\p{L}\d])/iu', $trecho, $m)
                || preg_match('/^\s*(?:oficial|definitivo|preliminar|da banca)?\s*(?:[:\-–=]|é|foi)?\s*["“(]?\s*'
                    . '(certo|errado|correto|correta|incorreto|incorreta|falso|verdadeiro|[A-E])(?![\p{L}\d])/iu', $trecho, $m)) {
                $v = mb_strtolower($m[1]);
                break;
            }
        }
        if ($v === null) {
            return null;
        }

        if ($ce) {
            return match ($v) {
                'certo', 'correto', 'correta', 'verdadeiro', 'c' => 1,
                'errado', 'incorreto', 'incorreta', 'falso', 'e' => 2,
                default => null,
            };
        }
        if (mb_strlen($v) !== 1) {
            return null;
        }
        $pos = ord(strtoupper($v)) - 64;

        return $pos >= 1 && $pos <= $alternativas ? $pos : null;
    }

    /** Sem a matéria no arquivo: a disciplina do único assunto com esse nome; senão, "a confirmar". */
    private function disciplinaPorNome(string $nome): string
    {
        $achados = Assunto::with('disciplina')->where('nome', $nome)->get();

        return $achados->count() === 1 ? $achados->first()->disciplina->nome : AssuntoResolver::DISCIPLINA_A_CONFIRMAR;
    }

    private function resolucaoMinima(array $q): string
    {
        $gab = $q['anulada'] ? 'questão anulada pela banca' : 'gabarito oficial ' . $q['gabarito'];

        return "Resposta: {$gab}. Esta questão ainda não tem comentário de professor no TEC; use o link \"ver no TEC\" para acompanhar a discussão.";
    }

    /** HTML do TEC para texto corrido (parágrafos, itens e linhas de tabela). @return array{0: string, 1: int} */
    public function texto(?string $html): array
    {
        $html = (string) $html;
        $figuras = preg_match_all('/<img\b/i', $html);
        $t = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $t = preg_replace('/<li\b[^>]*>/i', "\n• ", $t);
        $t = preg_replace('/<\/?(?:' . self::BLOCO . ')\b[^>]*>/i', "\n", $t);
        $t = preg_replace('/<t[dh]\b[^>]*>/i', ' | ', $t);
        $t = preg_replace('/<img\b[^>]*>/i', ' [figura] ', $t);
        $t = preg_replace('/<(script|style)\b.*?<\/\1>/is', '', $t);
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = str_replace("\u{a0}", ' ', $t);
        $t = preg_replace('/[ \t]+/u', ' ', $t);
        $t = preg_replace('/ *\n */u', "\n", $t);
        $t = preg_replace('/\n\| /u', "\n", $t);
        $t = preg_replace('/\n{3,}/u', "\n\n", $t);

        return [trim($t), $figuras];
    }
}
