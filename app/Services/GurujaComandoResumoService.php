<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Resume o comando de uma atividade da Guruja (texto longo do portal) em poucos tópicos, com o Gemini.
 * Sem chave ou sem resposta, devolve null: o app mostra o comando completo.
 */
class GurujaComandoResumoService
{
    /** Em ordem de preferência; os de reserva entram quando os 3.5 estão sobrecarregados (503/timeout). */
    protected const MODELS = ['gemini-3.5-flash', 'gemini-3.5-flash-lite', 'gemini-3.1-flash-lite', 'gemini-flash-lite-latest'];

    /** Tempo total (s) para todas as tentativas: o navegador fica esperando a resposta do POST. */
    protected const BUDGET_SECONDS = 40;

    public function resumir(string $texto, ?string $titulo = null): ?string
    {
        $apiKey = config('services.gemini.key');
        if (!$apiKey || trim($texto) === '') {
            return null;
        }

        $prompt = "Você ajuda um concurseiro a saber rapidamente o que fazer numa atividade de estudo.\n"
            . 'Resuma o COMANDO abaixo em no máximo 4 tópicos curtos, começando cada um com "• ". '
            . 'Diga só o que fazer: material/aula, páginas ou trechos (início e fim), o que pular, '
            . 'questões ou tarefas e qualquer condição importante. Mantenha números de aula e página exatos. '
            . "Sem introdução, sem conclusão, sem markdown além dos tópicos.\n\n"
            . ($titulo ? "ATIVIDADE: {$titulo}\n" : '')
            . "COMANDO:\n" . mb_substr($texto, 0, 9000);

        $deadline = microtime(true) + self::BUDGET_SECONDS;
        foreach (self::MODELS as $model) {
            $left = (int) floor($deadline - microtime(true));
            if ($left < 3) {
                Log::warning('Gemini: tempo esgotado no resumo de comando antes de tentar ' . $model);
                break;
            }
            try {
                $response = Http::timeout(min(15, $left))
                    ->withQueryParameters(['key' => $apiKey])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        // Os modelos 3.5 "pensam" antes de responder e esse raciocínio conta no limite:
                        // com 400 tokens o resumo saía cortado no primeiro tópico.
                        'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 2048],
                    ]);
                if (!$response->successful()) {
                    Log::warning("Gemini ({$model}) falhou no resumo de comando: status {$response->status()}");
                    continue;
                }
                if ($response->json('candidates.0.finishReason') === 'MAX_TOKENS') {
                    Log::warning("Gemini ({$model}) cortou o resumo de comando (MAX_TOKENS)");
                    continue;
                }
                $out = '';
                foreach ($response->json('candidates.0.content.parts') ?? [] as $part) {
                    if (empty($part['thought'])) {
                        $out .= $part['text'] ?? '';
                    }
                }
                $out = preg_replace('/\*\*(.+?)\*\*/', '$1', $out);
                // Descarta introdução do tipo "Aqui está o resumo:" antes do primeiro tópico.
                $first = mb_strpos($out, '•');
                $out = trim($first === false ? $out : mb_substr($out, $first));
                if ($out !== '') {
                    return mb_substr($out, 0, 2000);
                }
            } catch (Throwable $e) {
                Log::warning("Gemini ({$model}) exceção no resumo de comando: " . $e->getMessage());
            }
        }

        return null;
    }
}
