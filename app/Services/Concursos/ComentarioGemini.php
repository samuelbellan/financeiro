<?php

namespace App\Services\Concursos;

use App\Models\Concursos\MaterialQuestao;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Comentário da questão escrito pelo Gemini, para quando o caderno do TEC não trouxe comentário de
 * professor (só "No TEC você marcou X; gabarito Y" e o assunto). Gerado uma vez e guardado em
 * comentario_html (fonte 'gemini'); o aluno pode editar depois. Sem chave ou com falha, devolve null.
 */
class ComentarioGemini
{
    protected const MODELS = ['gemini-3.5-flash', 'gemini-3.5-flash-lite', 'gemini-3.1-flash-lite', 'gemini-flash-lite-latest'];

    protected const BUDGET_SECONDS = 50;

    /** Linhas que o conversor do TEC põe no comentário e que não explicam nada. */
    public static function semComentarioReal(?string $comentario): bool
    {
        $resto = collect(preg_split('/\R/u', (string) $comentario))
            ->map(fn ($l) => trim($l))
            ->reject(fn ($l) => $l === '' || preg_match('/^(No TEC você marcou|Assunto:)/u', $l))
            ->implode(' ');

        return trim($resto) === '';
    }

    /** HTML gerado e salvo na questão, ou null se o Gemini não respondeu. */
    public function gerar(MaterialQuestao $q): ?string
    {
        $md = $this->perguntar($q);
        if ($md === null) {
            return null;
        }
        $html = HtmlComentario::deTexto($q->comentario)
            . '<h4>Comentário (gerado pelo Gemini)</h4>'
            . HtmlComentario::limpar(Str::markdown($md, ['html_input' => 'strip', 'allow_unsafe_links' => false, 'renderer' => ['soft_break' => "<br>\n"]]));
        $q->forceFill(['comentario_html' => $html, 'comentario_fonte' => 'gemini'])->save();

        return $html;
    }

    private function perguntar(MaterialQuestao $q): ?string
    {
        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return null;
        }

        if ($q->tipo === 'CE') {
            $nome = $q->gabarito === 'C' ? 'CERTO' : 'ERRADO';
            $prompt = "Questão de concurso no formato Certo/Errado. O gabarito oficial é {$nome} (não discuta o gabarito).\n\n"
                . "ITEM:\n{$q->enunciado}\n";
        } else {
            $alts = collect($q->alternativas ?? [])->map(fn ($a) => "{$a['letra']}) {$a['texto']}")->implode("\n");
            $prompt = "Questão de concurso de múltipla escolha. O gabarito oficial é {$q->gabarito} (não discuta o gabarito).\n\n"
                . "ENUNCIADO:\n{$q->enunciado}\n\nALTERNATIVAS:\n{$alts}\n";
        }
        if ($q->origem) {
            $prompt .= "\nPROVA: {$q->origem}\n";
        }
        if ($q->comentario) {
            $prompt .= "\nINFORMAÇÕES DO CADERNO:\n" . mb_substr($q->comentario, 0, 1500) . "\n";
        }
        $prompt .= "\nEscreva o comentário da questão como um professor de cursinho, em português, para quem está revisando:\n"
            . "- comece explicando em 1 a 3 frases por que o gabarito está certo, citando a base legal (artigo, lei, súmula) quando houver;\n"
            . ($q->tipo === 'CE' ? '' : "- depois, uma linha por alternativa errada, no formato **B)** motivo curto;\n")
            . "- termine com uma linha **Macete:** com um jeito de lembrar a regra, se houver um bom.\n"
            . "Use Markdown simples (negrito, listas). Seja direto: no máximo 250 palavras. Não repita o enunciado. Não invente número de artigo se não tiver certeza.";

        $deadline = microtime(true) + self::BUDGET_SECONDS;
        foreach (self::MODELS as $model) {
            $left = (int) floor($deadline - microtime(true));
            if ($left < 3) {
                break;
            }
            try {
                $resp = Http::timeout(min(30, $left))
                    ->withQueryParameters(['key' => $apiKey])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 4096],
                    ]);
                if (!$resp->successful()) {
                    Log::warning("Gemini ({$model}) falhou no comentário da questão: status {$resp->status()}");
                    continue;
                }
                $txt = '';
                foreach ($resp->json('candidates.0.content.parts') ?? [] as $part) {
                    if (empty($part['thought'])) {
                        $txt .= $part['text'] ?? '';
                    }
                }
                if (trim($txt) !== '') {
                    return trim($txt);
                }
                Log::warning("Gemini ({$model}) devolveu comentário vazio");
            } catch (Throwable $e) {
                Log::warning("Gemini ({$model}) exceção no comentário da questão: " . $e->getMessage());
            }
        }

        return null;
    }
}
