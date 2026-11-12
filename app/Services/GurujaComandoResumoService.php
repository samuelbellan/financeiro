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
    protected const MODELS = ['gemini-3.5-flash', 'gemini-3.5-flash-lite'];

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

        foreach (self::MODELS as $model) {
            try {
                $response = Http::timeout(25)
                    ->withQueryParameters(['key' => $apiKey])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 400],
                    ]);
                if (!$response->successful()) {
                    Log::warning("Gemini ({$model}) falhou no resumo de comando: status {$response->status()}");
                    continue;
                }
                $out = '';
                foreach ($response->json('candidates.0.content.parts') ?? [] as $part) {
                    $out .= $part['text'] ?? '';
                }
                $out = trim(preg_replace('/\*\*(.+?)\*\*/', '$1', $out));
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
