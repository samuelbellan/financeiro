<?php

namespace App\Services\Concursos;

use App\Models\Concursos\MaterialQuestao;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * O que torna cada alternativa errada: trechos copiados literalmente do texto da alternativa (para
 * grifar em vermelho) e um motivo curto. Gerado pelo Gemini uma vez por questão e guardado em
 * material_questoes.trechos_errados. Em Certo/Errado com gabarito E, os trechos são do enunciado.
 * Sem chave ou com falha, devolve null e tenta de novo no próximo erro.
 */
class TrechosErrados
{
    protected const MODELS = ['gemini-3.5-flash', 'gemini-3.5-flash-lite', 'gemini-3.1-flash-lite', 'gemini-flash-lite-latest'];

    protected const BUDGET_SECONDS = 40;

    /** @return array<string, array{trechos: list<string>, motivo: string}>|null */
    public function para(MaterialQuestao $q): ?array
    {
        if ($q->trechos_errados !== null) {
            return $q->trechos_errados;
        }
        $textos = self::textosErrados($q);
        if (!$textos) {
            return $q->trechos_errados = [];
        }
        $itens = $this->perguntar($q, $textos);

        return $itens === null ? null : self::aplicar($q, $itens);
    }

    /**
     * Valida e guarda a resposta do Gemini (da API ou de um lote importado):
     * [{letra, trechos: [...], motivo}]. Devolve o que ficou salvo.
     */
    public static function aplicar(MaterialQuestao $q, array $itens): array
    {
        $textos = self::textosErrados($q);
        $saida = [];
        foreach ($itens as $item) {
            if (!is_array($item)) {
                continue;
            }
            $chave =$q->tipo === 'CE' ? 'enunciado' : strtoupper(trim((string) ($item['letra'] ?? '')));
            if (!isset($textos[$chave])) {
                continue;
            }
            // Só fica o trecho que existe mesmo no texto: senão não haveria o que grifar.
            $trechos = array_values(array_unique(array_filter(
                array_map(fn ($t) => trim((string) $t), (array) ($item['trechos'] ?? [])),
                fn ($t) => $t !== '' && self::contem($textos[$chave], $t),
            )));
            $saida[$chave] = ['trechos' => $trechos, 'motivo' => mb_substr(trim((string) ($item['motivo'] ?? '')), 0, 500)];
        }
        $q->forceFill(['trechos_errados' => $saida])->save();

        return $saida;
    }

    /** Textos que estão errados: as alternativas fora do gabarito, ou o item de C/E com gabarito E. */
    public static function textosErrados(MaterialQuestao $q): array
    {
        if ($q->tipo === 'CE') {
            return $q->gabarito === 'E' ? ['enunciado' => $q->enunciado] : [];
        }
        $out = [];
        foreach ($q->alternativas ?? [] as $a) {
            if ($a['letra'] !== $q->gabarito && trim($a['texto'] ?? '') !== '') {
                $out[$a['letra']] = $a['texto'];
            }
        }

        return $out;
    }

    /** O trecho aparece no texto, sem diferenciar maiúsculas nem espaços repetidos. */
    public static function contem(string $texto, string $trecho): bool
    {
        $norm = fn ($s) => mb_strtolower(preg_replace('/\s+/u', ' ', $s));

        return mb_strpos($norm($texto), $norm($trecho)) !== false;
    }

    private function perguntar(MaterialQuestao $q, array $textos): ?array
    {
        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return null;
        }

        if ($q->tipo === 'CE') {
            $prompt = "Questão de concurso no formato Certo/Errado. O gabarito oficial é ERRADO.\n"
                . "Aponte as palavras ou frases do ITEM que o tornam errado (o que a banca trocou, acrescentou, generalizou ou inverteu).\n"
                . "Responda com um único elemento em \"itens\", letra \"E\".\n\n"
                . "ITEM:\n" . $q->enunciado . "\n";
        } else {
            $alts = collect($q->alternativas ?? [])->map(fn ($a) => "{$a['letra']}) {$a['texto']}")->implode("\n");
            $prompt = "Questão de concurso de múltipla escolha. O gabarito oficial é {$q->gabarito} (não discuta o gabarito).\n"
                . 'Para CADA alternativa errada (' . implode(', ', array_keys($textos)) . "), aponte as palavras ou frases dela que a tornam errada "
                . "(o que a banca trocou, acrescentou, omitiu ou inverteu em relação à regra correta).\n\n"
                . "ENUNCIADO:\n" . $q->enunciado . "\n\nALTERNATIVAS:\n{$alts}\n";
        }
        if ($q->comentario) {
            $prompt .= "\nCOMENTÁRIO DE APOIO (pode ajudar):\n" . mb_substr($q->comentario, 0, 3000) . "\n";
        }
        $prompt .= "\nRegras para \"trechos\": copie LITERALMENTE do texto, sem mudar nenhuma letra, acento ou pontuação; "
            . 'use o menor trecho que carrega o erro (uma palavra ou expressão curta, não a alternativa inteira), salvo quando o erro '
            . "for a alternativa toda (ex.: ordem invertida); no máximo 3 trechos por alternativa.\n"
            . "\"motivo\": uma frase curta, em português, dizendo qual seria o certo.";

        $schema = [
            'type' => 'OBJECT',
            'properties' => ['itens' => ['type' => 'ARRAY', 'items' => [
                'type' => 'OBJECT',
                'properties' => [
                    'letra' => ['type' => 'STRING'],
                    'trechos' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    'motivo' => ['type' => 'STRING'],
                ],
                'required' => ['letra', 'trechos', 'motivo'],
            ]]],
            'required' => ['itens'],
        ];

        $deadline = microtime(true) + self::BUDGET_SECONDS;
        foreach (self::MODELS as $model) {
            $left = (int) floor($deadline - microtime(true));
            if ($left < 3) {
                break;
            }
            try {
                $resp = Http::timeout(min(20, $left))
                    ->withQueryParameters(['key' => $apiKey])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => [
                            'temperature' => 0.1,
                            'maxOutputTokens' => 4096,
                            'responseMimeType' => 'application/json',
                            'responseSchema' => $schema,
                        ],
                    ]);
                if (!$resp->successful()) {
                    Log::warning("Gemini ({$model}) falhou nos trechos errados: status {$resp->status()}");
                    continue;
                }
                $txt = '';
                foreach ($resp->json('candidates.0.content.parts') ?? [] as $part) {
                    if (empty($part['thought'])) {
                        $txt .= $part['text'] ?? '';
                    }
                }
                $json = json_decode($txt, true);
                if (is_array($json['itens'] ?? null)) {
                    return $json['itens'];
                }
                Log::warning("Gemini ({$model}) devolveu JSON inválido nos trechos errados");
            } catch (Throwable $e) {
                Log::warning("Gemini ({$model}) exceção nos trechos errados: " . $e->getMessage());
            }
        }

        return null;
    }
}
