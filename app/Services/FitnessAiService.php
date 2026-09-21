<?php

namespace App\Services;

use App\Models\DailyLog;
use App\Models\Measurement;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FitnessAiService
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
    }

    /**
     * Analisa um desvio na alimentação (ex: almoço fora da dieta / refeição livre)
     * e gera um plano metabólico de compensação inteligente (jantar, água, cardio).
     */
    public function analyzeMealDeviation(string $descricaoRefeicao, ?DailyLog $dailyLog = null, ?Measurement $latestMeasurement = null): array
    {
        $contexto = $this->buildUserContext($dailyLog, $latestMeasurement);

        $systemPrompt = "Você é um Nutricionista Esportivo e Coach de Recomposição Corporal de alto nível, fundamentado em Dieta Flexível, Balanço Calórico e Fisiologia Metabólica.
O usuário teve uma refeição fora do padrão da dieta (ex: almoço livre, sobremesa, pizza, hambúrguer, etc.).
Sua missão:
1. Fazer uma estimativa realista do impacto calórico e perfil de macronutrientes do que foi ingerido.
2. Fornecer uma estratégia de compensação INTELIGENTE e SAUDÁVEL para o restante do dia (lanche e jantar), SEM prescrever dietas restritivas perigosas, jejuns punitivos ou culpa.
3. Indicar um volume adicional de hidratação (água em ml) para eliminar a retenção causada por excesso de sódio.
4. Sugerir, se conveniente, uma atividade física compensatória opcional (ex: caminhada pós-prandial ou cardio leve) indicando minutos e queima aproximada.
5. Manter um tom encorajador, focado em adesão a longo prazo e consistência.

Responda ESTRITAMENTE em formato JSON VÁLIDO sem formatação markdown em torno (sem ```json), com a seguinte estrutura:
{
  \"calorias_estimadas\": int (ex: 850),
  \"resumo_refeicao\": string (ex: \"Refeição rica em carboidratos refinados e sódio\"),
  \"impacto_metabolico\": string (explicação breve de 1-2 frases sobre digestão e retenção),
  \"ajuste_jantar\": string (orientação específica e prática para o jantar, ex: \"Foque em 150g de proteína magra como frango ou ovos, com salada verde volumosa e corte os carboidratos pesados\"),
  \"ajuste_lanche\": string (dica para o lanche da tarde se aplicável, ou 'Manter apenas hidratação ou chá'),
  \"agua_extra_ml\": int (ex: 500 a 1000 ml adicionais para diluir o sódio),
  \"cardio_compensatorio_sugestao\": string (ex: \"30 minutos de caminhada rápida ou esteira (~180 kcal) são suficientes para estabilizar a glicose\"),
  \"dica_mental\": string (uma frase motivacional realista),
  \"resumo_para_notas\": string (um resumo em 2-3 linhas pronto para salvar no diário do usuário)
}";

        $userPrompt = "Contexto do usuário: {$contexto}\n\nO que comi no desvio alimentar:\n\"{$descricaoRefeicao}\"";

        $aiResponse = $this->callGemini($systemPrompt, $userPrompt);

        if ($aiResponse && isset($aiResponse['calorias_estimadas'])) {
            return $aiResponse;
        }

        return $this->fallbackMealDeviation($descricaoRefeicao);
    }

    /**
     * Analisa um exercício físico extra ou dia com volume de treino acima do padrão
     * e orienta a reposição calórica, aporte de proteínas, eletrólitos e periodização.
     */
    public function analyzeExtraWorkout(string $descricaoTreino, ?DailyLog $dailyLog = null, ?Measurement $latestMeasurement = null): array
    {
        $contexto = $this->buildUserContext($dailyLog, $latestMeasurement);

        $systemPrompt = "Você é um Fisiologista do Exercício e Treinador de Recomposição Corporal de elite.
O usuário realizou uma sessão de exercícios extra ou um volume atípico de treino no dia (ex: corrida longa de 8km, futebol intenso de 1h30, cardio duplo além da musculação).
Sua missão:
1. Estimar o gasto calórico adicional gerado por essa atividade extra.
2. Indicar se há necessidade de aporte adicional de calorias, carboidratos ou proteínas para evitar catabolismo muscular e queda na recuperação.
3. Prescrever ajuste de hidratação e reposição eletrolítica.
4. Dar recomendações cruciais de descanso ou adaptação para o treino do dia seguinte (prevenção de lesões e overtraining).

Responda ESTRITAMENTE em formato JSON VÁLIDO sem markdown em torno (sem ```json), com a seguinte estrutura:
{
  \"calorias_gastas_estimadas\": int (ex: 480),
  \"intensidade\": \"moderada\" | \"alta\" | \"muito_alta\",
  \"ajuste_nutricional\": string (orientação do que ingerir no pós-treino ou na próxima refeição para reparar fibras musculares e repor glicogênio),
  \"agua_adicional_ml\": int (ex: 750 a 1200 ml para repor o suor),
  \"recomendacao_dia_seguinte\": string (ex: \"Priorize membros superiores amanhã ou descanso ativo, evitando sobrecarga nos joelhos e panturrilhas\"),
  \"tempo_recuperacao_horas\": int (ex: 24 ou 48),
  \"dica_performance\": string (feedback esportivo positivo),
  \"resumo_para_notas\": string (resumo pronto para salvar no diário do dia)
}";

        $userPrompt = "Contexto do usuário: {$contexto}\n\nExercício extra realizado:\n\"{$descricaoTreino}\"";

        $aiResponse = $this->callGemini($systemPrompt, $userPrompt);

        if ($aiResponse && isset($aiResponse['calorias_gastas_estimadas'])) {
            return $aiResponse;
        }

        return $this->fallbackExtraWorkout($descricaoTreino);
    }

    /**
     * Avalia de forma holística os hábitos do dia com parecer do Coach IA.
     */
    public function evaluateDailyProgress(DailyLog $dailyLog, ?Measurement $latestMeasurement = null): array
    {
        $contexto = $this->buildUserContext($dailyLog, $latestMeasurement);

        $systemPrompt = "Você é um Coach de Recomposição Corporal e Hábitos Saudáveis.
Avalie o dia do usuário com base nos hábitos registrados: café sem açúcar, almoço limpo, lanche proteico, jantar leve e treino.
Dê um feedback construtivo, rápido e estimulante.

Responda ESTRITAMENTE em formato JSON VÁLIDO sem markdown:
{
  \"score_geral\": int (0 a 100),
  \"destaque_positivo\": string,
  \"ponto_de_atencao\": string,
  \"proximo_passo\": string,
  \"frase_do_dia\": string
}";

        $aiResponse = $this->callGemini($systemPrompt, "Dados do dia: {$contexto}");

        if ($aiResponse && isset($aiResponse['score_geral'])) {
            return $aiResponse;
        }

        return [
            'score_geral' => $dailyLog->adherence_score ?? 80,
            'destaque_positivo' => ($dailyLog->workout_done ? 'Treino cumprido com determinação!' : 'Consistência e atenção à rotina ativa.'),
            'ponto_de_atencao' => 'Mantenha o jantar leve para um sono reparador.',
            'proximo_passo' => 'Descansar bem e manter a regularidade no dia seguinte.',
            'frase_do_dia' => 'A consistência diária vence qualquer motivação passageira.',
        ];
    }

    /**
     * Monta o resumo de contexto do usuário para enriquecer o prompt da IA.
     */
    protected function buildUserContext(?DailyLog $dailyLog, ?Measurement $latestMeasurement): string
    {
        $parts = [];

        if ($latestMeasurement) {
            $parts[] = "Peso atual: {$latestMeasurement->weight_kg}kg";
            if ($latestMeasurement->waist_narrow_cm) {
                $parts[] = "Cintura: {$latestMeasurement->waist_narrow_cm}cm";
            }
            if ($latestMeasurement->abdomen_umbilical_cm) {
                $parts[] = "Abdômen: {$latestMeasurement->abdomen_umbilical_cm}cm";
            }
        }

        if ($dailyLog) {
            $parts[] = "Data analisada: " . ($dailyLog->date ? $dailyLog->date->format('d/m/Y') : now()->format('d/m/Y'));
            $parts[] = "Treino hoje: " . ($dailyLog->workout_done ? "Sim ({$dailyLog->workout_type}, {$dailyLog->workout_duration_min} min)" : "Ainda não");
            $parts[] = "Alimentação hoje: Café=" . ($dailyLog->breakfast_clean ? 'Limpo' : 'Desvio') .
                ", Almoço=" . ($dailyLog->lunch_clean ? 'Equilibrado' : 'Livre/Desvio') .
                ", Lanche=" . ($dailyLog->snack_done ? 'Proteico' : 'Não') .
                ", Jantar=" . ($dailyLog->dinner_clean ? 'Leve' : 'Pendente/Livre');
            if ($dailyLog->notes) {
                $parts[] = "Notas do dia: {$dailyLog->notes}";
            }
        }

        return implode(' | ', $parts);
    }

    /**
     * Executa a chamada à API do Gemini com rotação de modelos e suporte a timeout.
     */
    protected function callGemini(string $systemInstruction, string $prompt): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('[FitnessAiService] GEMINI_API_KEY não está configurada no .env.');
            return null;
        }

        // Modelos suportados pela API v1beta do Google Gemini
        $models = [
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-1.5-flash',
        ];

        foreach ($models as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}";

                $response = Http::timeout(12)->withoutVerifying()->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemInstruction . "\n\n" . $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'responseMimeType' => 'application/json',
                    ]
                ]);

                if ($response->successful()) {
                    $body = $response->json();
                    $rawText = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $cleanJson = $this->cleanJsonString($rawText);
                    $decoded = json_decode($cleanJson, true);

                    if (is_array($decoded)) {
                        Log::info("[FitnessAiService] Sucesso na resposta da IA usando modelo {$model}.");
                        return $decoded;
                    }
                } else {
                    Log::warning("[FitnessAiService] Modelo {$model} retornou status {$response->status()}: " . substr($response->body(), 0, 150));
                }
            } catch (\Throwable $e) {
                Log::warning("[FitnessAiService] Exceção ao chamar modelo {$model}: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Limpa markdown em torno de strings JSON.
     */
    protected function cleanJsonString(string $text): string
    {
        $text = trim($text);
        if (str_starts_with($text, '```json')) {
            $text = substr($text, 7);
        } elseif (str_starts_with($text, '```')) {
            $text = substr($text, 3);
        }
        if (str_ends_with($text, '```')) {
            $text = substr($text, 0, -3);
        }
        return trim($text);
    }

    /**
     * Fallback heurístico em caso de indisponibilidade momentânea da API.
     */
    protected function fallbackMealDeviation(string $descricao): array
    {
        return [
            'calorias_estimadas' => 750,
            'resumo_refeicao' => 'Refeição livre calórica ou com maior teor de sódio/carboidrato',
            'impacto_metabolico' => 'Gera elevação temporária de glicose e retenção transitória de água pelo sódio ingerido.',
            'ajuste_jantar' => 'Mantenha um jantar leve focado em proteínas magras (frango, peixe ou ovos) e salada de folhas verdes, cortando carboidratos simples.',
            'ajuste_lanche' => 'Opte por um chá digestivo ou lanche puramente proteico (iogurte natural ou ovos cozidos).',
            'agua_extra_ml' => 750,
            'cardio_compensatorio_sugestao' => '30 minutos de caminhada acelerada ou pedalada leve (~180 a 220 kcal) já ajudam a estabilizar o perfil glicêmico.',
            'dica_mental' => 'Uma refeição fora não destrói uma semana de foco. O segredo da recomposição é retomar o plano na refeição seguinte.',
            'resumo_para_notas' => "Refeição livre registrada: {$descricao}. Compensação: jantar focado em proteína magra + salada e caminhada leve.",
        ];
    }

    /**
     * Fallback heurístico para treino extra.
     */
    protected function fallbackExtraWorkout(string $descricao): array
    {
        return [
            'calorias_gastas_estimadas' => 450,
            'intensidade' => 'alta',
            'ajuste_nutricional' => 'Garanta reposição proteica (30-40g) e carboidratos complexos no pós-treino para evitar catabolismo e fadiga precoce.',
            'agua_adicional_ml' => 800,
            'recomendacao_dia_seguinte' => 'Monitore a recuperação muscular; se sentir peso nas pernas, priorize membros superiores ou descanso ativo amanhã.',
            'tempo_recuperacao_horas' => 24,
            'dica_performance' => 'Excelente dedicação! Ajuste o descanso noturno para maximizar os ganhos da sobrecarga planejada.',
            'resumo_para_notas' => "Treino extra: {$descricao} (~450 kcal gastas). Reposição: proteína de qualidade e descanso focado.",
        ];
    }
}
