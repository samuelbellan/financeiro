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
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    }

    /**
     * Envia a mensagem livre do usuário para o Gemini interpretar no contexto de finanças.
     * Retorna um array com o JSON parseado.
     */
    public function parseMessage(string $mensagem, int $userId): array
    {
        if (empty($this->apiKey)) {
            Log::warning('[Gemini] Chave de API não configurada.');
            return ['tipo' => 'invalido', 'erro' => 'IA não configurada.'];
        }

        // Buscar categorias e cartões reais do usuário para alimentar a IA
        $categorias = Categoria::where('user_id', $userId)->with('subcategorias')->get();
        $cartoes = Cartao::where('user_id', $userId)->get();

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
7. Se for um lançamento, classifique 'tipo' as 'transacao'.
8. Tente encontrar a categoria que melhor se ajusta à descrição do usuário a partir das Categorias REAIS cadastradas acima. Se não bater exatamente com nenhuma, use a que mais se assemelha ou deixe nula se for totalmente diferente. Não invente categorias novas.
9. A resposta DEVE ser estritamente um JSON no formato especificado, sem markdown de bloco de código (```json), apenas a string JSON.

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
  \"resposta_texto\": string | null (uma mensagem amigável de sucesso ou erro que confirme o que você entendeu e o que foi lançado)
}";

        try {
            $request = Http::timeout(15);
            if (app()->environment('local')) {
                $request = $request->withoutVerifying();
            }

            $response = $request->post("{$this->baseUrl}?key={$this->apiKey}", [
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

            if (!$response->successful()) {
                Log::error('[Gemini] Falha ao consultar API.', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['tipo' => 'invalido', 'erro' => 'Falha na comunicação com a Inteligência Artificial.'];
            }

            $result = $response->json();
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            $parsed = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('[Gemini] Resposta inválida JSON: ' . $text);
                return ['tipo' => 'invalido', 'erro' => 'Não consegui decodificar a resposta da IA.'];
            }

            return $parsed;
        } catch (\Throwable $e) {
            Log::error('[Gemini] Exceção na chamada: ' . $e->getMessage());
            return ['tipo' => 'invalido', 'erro' => 'Erro interno ao processar com a IA.'];
        }
    }
}
