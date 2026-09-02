<?php

namespace App\Services;

use App\Models\Cartao;
use App\Models\Categoria;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        // Usar gemini-3.6-flash (versão ativa na API v1beta)
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent';
    }

    /**
     * Envia a mensagem livre do usuário para a IA (OmniRoute com fallback para Gemini) interpretar no contexto de finanças.
     * Retorna um array com o JSON parseado.
     */
    public function parseMessage(string $mensagem, int $userId): array
    {
        // Buscar categorias e cartões reais do usuário para alimentar a IA
        $categorias = Categoria::where('user_id', $userId)->with('subcategorias')->get();
        $cartoes = Cartao::where('user_id', $userId)->where('ativo', true)->get();

        $listaCategorias = [];
        foreach ($categorias as $cat) {
            $subNames = $cat->subcategorias->pluck('nome')->toArray();
            $listaCategorias[] = $cat->nome . (count($subNames) > 0 ? ' (Subcategorias: ' . implode(', ', $subNames) . ')' : '');
        }

        $listaCartoes = [];
        foreach ($cartoes as $cartao) {
            $listaCartoes[] = "ID: {$cartao->id} - Nome: {$cartao->nome} - Limite: R$ {$cartao->limite} - Fechamento: dia {$cartao->dia_fechamento} - Vencimento: dia {$cartao->dia_vencimento}";
        }

        $systemInstruction = "Você é um interpretador de mensagens de texto para um sistema de finanças pessoais do usuário.
Você deve analisar a mensagem enviada pelo usuário e extrair os dados estruturados de transações financeiras (receitas, despesas) ou comandos.

Categorias REAIS cadastradas no sistema:
" . implode("\n", $listaCategorias) . "

Cartões de Crédito REAIS cadastrados no sistema:
" . implode("\n", $listaCartoes) . "

Instruções importantes:
1. Identifique se o usuário quer registrar uma despesa nas contas gerais de casa ou em um cartão de crédito.
2. Se o usuário mencionar palavras como 'no cartão', 'no visa', 'no vuon', ou nomes semelhantes aos cartões cadastrados, associe a transação a esse cartão, definindo 'transacao_destino' como 'cartao' e populando o 'cartao_id' correspondente. Se ele apenas disser 'no cartão' e houver mais de um cartão, tente associar pelo nome. Se não souber qual cartão usar mas ele quis cartão, use o primeiro cartão cadastrado.
3. Se for cartão, identifique se a compra é 'avista', 'parcelada' ou 'recorrente'. Se ele disser 'em 3x', o tipo é 'parcelada' e 'numero_parcelas' deve ser 3. Se for assinatura mensal (ex: netflix, spotify), o tipo é 'recorrente'.
4. Se o usuário quiser consultar o saldo, extrato, ajuda ou solicitar a exportação/envio de fatura ou relatório em PDF, classifique 'tipo' como 'comando'. Comandos possíveis: 'saldo', 'listar' (para listar transações), 'ajuda', 'fatura_pdf'.
5. Se for um comando de 'saldo', identifique:
   - Se ele quer saber o saldo do cartão de crédito (ex: se mencionar 'fatura do visa', 'fatura do cartão', 'saldo do vuon', etc.), defina 'saldo_destino' como 'cartao' e populando o 'cartao_id' do cartão mencionado.
   - Se ele quer saber o saldo geral da conta, defina 'saldo_destino' como 'casa'.
   - Identifique o mês e ano solicitados (ex: 'agosto' -> 'mes': 8. Se ele não disser, use o mês e o ano correntes: mês atual de " . now()->month . " e ano atual de " . now()->year . ").
6. Se for um comando de 'fatura_pdf' (pedido de PDF do cartão ou relatório/fatura do caixa), identifique:
   - Se ele quer a fatura de um cartão de crédito (ex: se mencionar 'fatura do visa em pdf', 'pdf da fatura do nubank', etc.), defina 'fatura_destino' como 'cartao' e popule 'cartao_id' do cartão mencionado.
   - Se ele quer o PDF do caixa/geral/orçamento (ex: 'fatura do caixa', 'relatorio de caixa', 'pdf do caixa', etc.), defina 'fatura_destino' como 'casa'.
   - Identifique o mês e ano solicitados (ex: 'maio' -> 'mes': 5. Se ele não disser, use o mês e o ano correntes: mês atual de " . now()->month . " e ano atual de " . now()->year . ").
7. Se for um lançamento, classifique 'tipo' como 'transacao'.
8. Tente encontrar a categoria que melhor se ajusta à descrição do usuário a partir das Categorias REAIS cadastradas acima. Se não bater exatamente com nenhuma, use a que mais se assemelha ou deixe nula se for totalmente diferente. Não invente categorias novas.
9. A resposta DEVE ser estritamente um JSON VÁLIDO no formato especificado, sem markdown de bloco de código (```json), apenas a string JSON pura.

Esquema do JSON esperado:
{
  \"tipo\": \"transacao\" | \"comando\" | \"invalido\",
  \"comando\": \"saldo\" | \"listar\" | \"ajuda\" | \"fatura_pdf\" | null,
  \"saldo_destino\": \"casa\" | \"cartao\" | null,
  \"fatura_destino\": \"casa\" | \"cartao\" | null,
  \"mes\": int | null,
  \"ano\": int | null,
  \"transacao_destino\": \"casa\" | \"cartao\" | null,
  \"cartao_id\": int | null,
  \"cartao_tipo_compra\": \"avista\" | \"parcelada\" | \"recorrente\" | null,
  \"numero_parcelas\": int | null,
  \"transacao_tipo\": \"despesa\" | \"receita\" | null,
  \"valor\": float | null,
  \"descricao\": string | null,
  \"categoria\": string | null,
  \"subcategoria\": string | null,
  \"resposta_texto\": string | null (uma mensagem amigável de confirmação)
}";

        // 1. Tentar OmniRoute AI Router (Local) primeiro (com timeout curto para não travar se offline)
        $omniRouteParsed = $this->parseWithOmniRoute($mensagem, $systemInstruction);
        if ($omniRouteParsed !== null) {
            Log::info('[Telegram Bot] Mensagem interpretada via OmniRoute AI Gateway.');
            return $omniRouteParsed;
        }

        // 2. Fallback para Google Gemini API se a API Key estiver configurada
        if (!empty($this->apiKey)) {
            $geminiParsed = $this->parseWithGeminiApi($mensagem, $systemInstruction);
            if ($geminiParsed !== null) {
                Log::info('[Telegram Bot] Mensagem interpretada via Gemini API.');
                return $geminiParsed;
            }
        }

        return ['tipo' => 'invalido', 'erro' => 'Nenhuma Inteligência Artificial respondeu no momento.'];
    }

    private function parseWithOmniRoute(string $mensagem, string $systemInstruction): ?array
    {
        $endpoints = array_filter(array_unique([
            env('OMNIROUTE_URL'),
            env('OMNIROUTE_BASE_URL'),
            'http://localhost:20128/v1'
        ]));

        if (empty($endpoints)) {
            return null;
        }

        $apiKey = env('OMNIROUTE_API_KEY', 'sk-0a283590febce995-ecd196-29791878');
        $models = ['auto/best-chat', 'oc/hy3-free', 'auto/best-fast'];

        foreach ($endpoints as $endpoint) {
            foreach ($models as $model) {
                try {
                    $response = Http::withToken($apiKey)->timeout(3)->post(rtrim($endpoint, '/') . '/chat/completions', [
                        'model'       => $model,
                        'messages'    => [
                            ['role' => 'system', 'content' => $systemInstruction],
                            ['role' => 'user', 'content' => $mensagem],
                        ],
                        'temperature' => 0.1,
                        'max_tokens'  => 800,
                        'stream'      => false,
                    ]);

                    if ($response->successful()) {
                        $result = $response->json();
                        $text = $result['choices'][0]['message']['content'] ?? null;
                        if (empty($text) && isset($result['choices'][0]['message']['reasoning_content'])) {
                            $text = $result['choices'][0]['message']['reasoning_content'];
                        }

                        if (!empty($text)) {
                            $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text));
                            $parsed = json_decode($cleanJson, true);

                            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed) && isset($parsed['tipo'])) {
                                return $parsed;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // OmniRoute offline ou timeout rápido, prossegue para o Gemini
                    break;
                }
            }
        }

        return null;
    }

    private function parseWithGeminiApi(string $mensagem, string $systemInstruction): ?array
    {
        $models = ['gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-2.5-flash'];

        foreach ($models as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}";
                $request = Http::timeout(10)->withoutVerifying();

                $response = $request->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $mensagem]
                            ]
                        ]
                    ],
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $systemInstruction]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json'
                    ]
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    $parts = $result['candidates'][0]['content']['parts'] ?? [];
                    $text = '';
                    foreach ($parts as $part) {
                        if (isset($part['text'])) {
                            $text .= $part['text'];
                        }
                    }
                    
                    $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text));
                    $parsed = json_decode($cleanJson, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed) && isset($parsed['tipo'])) {
                        return $parsed;
                    }
                } else {
                    Log::warning("[Gemini] Modelo {$model} retornou status {$response->status()}: " . substr($response->body(), 0, 150));
                }
            } catch (\Throwable $e) {
                Log::warning("[Gemini] Falha na chamada do modelo {$model}: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Analisa a foto de um cupom / nota fiscal de mercado e retorna os dados estruturados e itens detalhados.
     */
    public function parseReceiptImage(string $base64Image, string $mimeType, int $userId, ?string $caption = null): array
    {
        $cartoes = Cartao::where('user_id', $userId)->where('ativo', true)->get();

        $listaCartoes = [];
        foreach ($cartoes as $cartao) {
            $listaCartoes[] = "ID: {$cartao->id} - Nome: {$cartao->nome} - Bandeira: {$cartao->bandeira}";
        }

        $captionPrompt = !empty($caption)
            ? "\n\nLegenda e instruções fornecidas pelo usuário junto com a foto: '{$caption}'. Utilize esta legenda com alta prioridade para definir a forma de pagamento (se mencionou cartão específico ou 'credito', defina 'transacao_destino' como 'cartao' e preencha 'cartao_id'; se mencionou débito, pix ou dinheiro, defina como 'casa'), categoria ou informações extras."
            : "";

        $systemInstruction = "Você é um especialista em OCR e análise de Cupons e Notas Fiscais de Supermercado.
Analise a imagem da nota fiscal e extraia todos os dados com máxima precisão.{$captionPrompt}

Cartões de Crédito cadastrados no sistema:
" . implode("\n", $listaCartoes) . "

Categorias de Itens permitidas para cada produto comprado:
- Carnes (Carnes bovinas, suínas, aves, peixes, linguiças, hambúrgueres)
- Hortifruti (Frutas, verduras, legumes, temperos frescos)
- Laticínios (Leite, queijos, iogurtes, manteiga, requeijão, cremes)
- Padaria (Pães, bolos, torrada, salgados)
- Limpeza (Sabão, detergente, amaciante, desinfetante, esponja, papel toalha)
- Higiene (Shampoo, sabonete, creme dental, papel higiênico, fralda)
- Bebidas (Refrigerante, suco, cerveja, água, café, chá)
- Mercearia (Arroz, feijão, óleo, açúcar, macarrão, molho, biscoitos, enlatados)
- Outros (Produtos não classificados acima)

Instruções:
1. Extraia o nome do estabelecimento comercial.
2. Extraia o valor total pago no cupom fiscal.
3. Tente identificar a forma de pagamento (se for em cartão ou se a legenda do usuário indicar cartão de crédito, defina 'transacao_destino' como 'cartao' e popule 'cartao_id'. Caso contrário, use 'casa').
4. Extraia CADA ITEM/PRODUTO listado na nota fiscal com seu nome legível, a categoria de item correspondente, a quantidade, o valor unitário e o valor total do item.
5. Retorne a resposta ESTRITAMENTE em formato JSON VÁLIDO sem markdown.

Esquema JSON esperado:
{
  \"tipo\": \"nota_fiscal\",
  \"estabelecimento\": string | null,
  \"data_compra\": \"YYYY-MM-DD HH:MM:SS\" | null,
  \"valor_total\": float,
  \"transacao_destino\": \"casa\" | \"cartao\",
  \"cartao_id\": int | null,
  \"categoria\": \"Alimentação\",
  \"subcategoria\": \"Mercado\",
  \"itens\": [
    {
      \"nome\": string,
      \"categoria_item\": \"Carnes\" | \"Hortifruti\" | \"Laticínios\" | \"Padaria\" | \"Limpeza\" | \"Higiene\" | \"Bebidas\" | \"Mercearia\" | \"Outros\",
      \"quantidade\": float,
      \"valor_unitario\": float,
      \"valor_total\": float
    }
  ]
}";

        // 1. Tentar Gemini Vision API primeiro se a API Key estiver configurada (rápido e preciso)
        if (!empty($this->apiKey)) {
            $geminiVisionResult = $this->parseVisionWithGeminiApi($base64Image, $mimeType, $systemInstruction);
            if ($geminiVisionResult !== null) {
                Log::info('[GeminiService] Nota fiscal processada via Gemini Vision API.');
                return $geminiVisionResult;
            }
        }

        // 2. Fallback para OmniRoute Vision (se disponível)
        $omniVisionResult = $this->parseVisionWithOmniRoute($base64Image, $mimeType, $systemInstruction);
        if ($omniVisionResult !== null) {
            Log::info('[GeminiService] Nota fiscal processada via OmniRoute Vision.');
            return $omniVisionResult;
        }

        return ['tipo' => 'invalido', 'erro' => 'Não foi possível ler a foto da nota fiscal no momento (o serviço de visão com IA está temporariamente indisponível). Lance os dados via texto como: Compra no visa [valor] [local] alimentação.'];
    }

    private function parseVisionWithOmniRoute(string $base64Image, string $mimeType, string $systemInstruction): ?array
    {
        $primaryEndpoint = env('OMNIROUTE_URL') ?: env('OMNIROUTE_BASE_URL') ?: 'http://localhost:20128/v1';
        $endpoints = array_filter(array_unique([$primaryEndpoint, 'http://localhost:20128/v1']));
        $apiKey = env('OMNIROUTE_API_KEY', 'sk-0a283590febce995-ecd196-29791878');
        $models = ['auto/best-vision', 'opencode/mimo-v2.5-free', 'oc/mimo-v2.5-free'];

        foreach ($endpoints as $endpoint) {
            foreach ($models as $model) {
                try {
                    $response = Http::withToken($apiKey)->timeout(3)->post(rtrim($endpoint, '/') . '/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemInstruction],
                            [
                                'role' => 'user',
                                'content' => [
                                    ['type' => 'text', 'text' => 'Analise a imagem deste cupom/nota fiscal de mercado e extraia os dados e itens em JSON.'],
                                    [
                                        'type' => 'image_url',
                                        'image_url' => [
                                            'url' => "data:{$mimeType};base64,{$base64Image}"
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'temperature' => 0.1,
                    ]);

                    if ($response->successful()) {
                        $result = $response->json();
                        $text = $result['choices'][0]['message']['content'] ?? null;
                        if (empty($text) && isset($result['choices'][0]['message']['reasoning_content'])) {
                            $text = $result['choices'][0]['message']['reasoning_content'];
                        }

                        if (!empty($text)) {
                            $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text));
                            $parsed = json_decode($cleanJson, true);

                            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed) && isset($parsed['tipo'])) {
                                return $parsed;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    break;
                }
            }
        }

        return null;
    }

    private function parseVisionWithGeminiApi(string $base64Image, string $mimeType, string $systemInstruction): ?array
    {
        $visionModels = ['gemini-3.5-flash', 'gemini-3.5-flash-lite', 'gemini-3.6-flash'];

        foreach ($visionModels as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}";
                $request = Http::timeout(45)->withoutVerifying();

                $response = $request->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => "Analise a imagem desta nota fiscal de mercado e extraia os dados em JSON.\n\n" . $systemInstruction],
                                [
                                    'inlineData' => [
                                        'mimeType' => $mimeType,
                                        'data' => $base64Image
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json'
                    ]
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    $parts = $result['candidates'][0]['content']['parts'] ?? [];
                    $text = '';
                    foreach ($parts as $part) {
                        if (isset($part['text'])) {
                            $text .= $part['text'];
                        }
                    }

                    $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text));
                    $parsed = json_decode($cleanJson, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed) && isset($parsed['tipo'])) {
                        Log::info("[GeminiService] Nota fiscal processada com sucesso via modelo {$model}.");
                        return $parsed;
                    }
                } else {
                    Log::warning("[Gemini] Vision modelo {$model} retornou status {$response->status()}: " . substr($response->body(), 0, 150));
                }
            } catch (\Throwable $e) {
                Log::warning("[Gemini] Falha na chamada Vision do modelo {$model}: " . $e->getMessage());
            }
        }

        return null;
    }
}
