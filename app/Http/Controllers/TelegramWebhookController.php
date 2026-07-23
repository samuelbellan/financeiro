<?php

namespace App\Http\Controllers;

use App\Models\Transacao;
use App\Models\User;
use App\Models\WhatsappLog;
use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoParcela;
use App\Services\WhatsappMessageParser;
use App\Services\TelegramService;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected WhatsappMessageParser $parser,
        protected TelegramService $telegram,
        protected GeminiService $gemini
    ) {}

    /**
     * Recebe o webhook do Telegram Bot API.
     * POST /webhook/telegram
     */
    public function receive(Request $request)
    {
        // ── 1. Validar secret token (header enviado pelo Telegram) ────────────
        $secret = config('telegram.webhook_secret');
        if (!empty($secret)) {
            $headerSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');
            if ($headerSecret !== $secret) {
                Log::warning('[Telegram Webhook] Secret token inválido.', ['ip' => $request->ip()]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        // ── 2. Extrair dados do payload do Telegram ───────────────────────────
        $body    = $request->all();
        $message = $body['message'] ?? null;

        if (!$message) {
            return response()->json(['ok' => true, 'skipped' => 'no message']);
        }

        $chatId = $message['chat']['id'] ?? null;
        $texto  = $message['text'] ?? null;

        if (!$chatId || !$texto) {
            return response()->json(['ok' => true, 'skipped' => 'no chat_id or text']);
        }

        Log::info('[Telegram Webhook] Mensagem recebida.', [
            'chat_id' => $chatId,
            'texto'   => $texto,
        ]);

        // ── 3. Validar chat autorizado ────────────────────────────────────────
        $allowedChatId = config('telegram.allowed_chat_id');
        $matchChat = false;
        if (!empty($allowedChatId)) {
            $allowedChatIds = explode(',', $allowedChatId);
            foreach ($allowedChatIds as $id) {
                if ((string)$chatId === trim((string)$id)) {
                    $matchChat = true;
                    break;
                }
            }
        } else {
            $matchChat = true;
        }

        if (!$matchChat) {
            Log::warning('[Telegram Webhook] Chat não autorizado.', ['chat_id' => $chatId]);
            return response()->json(['ok' => true, 'skipped' => 'unauthorized chat']);
        }

        // ── 4. Encontrar usuário dono do sistema ──────────────────────────────
        $userId = config('telegram.user_id');
        $user = User::find($userId) ?? User::first();
        if (!$user) {
            return response()->json(['error' => 'No user found'], 500);
        }

        // ── 5. Parsear mensagem (Gemini com fallback para Regex) ──────────────
        $isGemini = !empty(env('GEMINI_API_KEY'));
        if ($isGemini) {
            $parsed = $this->gemini->parseMessage($texto, $user->id);
        } else {
            $parsed = $this->parser->parse($texto, $user->id);
            // Fallback não tem destino de cartão por padrão no parser regex antigo
            $parsed['transacao_destino'] = 'casa'; 
        }

        // ── 6. Processar resultado ────────────────────────────────────────────
        if ($parsed['tipo'] === 'comando') {
            $comando = $parsed['comando'] ?? '';
            if ($comando === 'fatura_pdf') {
                $resposta = $this->handleFaturaPdf($user, $chatId, $parsed);
            } else {
                $resposta = $this->handleComando($comando, $user, $parsed);
                $this->telegram->sendMessage($chatId, $resposta);
            }

            WhatsappLog::create([
                'numero'            => "tg:{$chatId}",
                'mensagem_original' => $texto,
                'status'            => 'ok',
                'resposta'          => $resposta,
            ]);

            return response()->json(['ok' => true]);
        }

        if ($parsed['tipo'] === 'invalido') {
            $resposta = '❌ ' . ($parsed['erro'] ?? $parsed['resposta_texto'] ?? 'Não entendi seu comando.');
            $this->telegram->sendMessage($chatId, $resposta);

            WhatsappLog::create([
                'numero'            => "tg:{$chatId}",
                'mensagem_original' => $texto,
                'status'            => 'ignorado',
                'resposta'          => $resposta,
            ]);

            return response()->json(['ok' => true]);
        }

        // ── 7. Criar transação/compra no Cartão ou Casa ─────────────────────────
        try {
            $destino = $parsed['transacao_destino'] ?? 'casa';

            if ($destino === 'cartao' && !empty($parsed['cartao_id'])) {
                // Registrar no Cartão de Crédito
                $cartao = Cartao::where('user_id', $user->id)->find($parsed['cartao_id']);
                
                if (!$cartao) {
                    throw new \Exception('Cartão de crédito não encontrado no seu perfil.');
                }

                $compra = CartaoCompra::create([
                    'cartao_id'       => $cartao->id,
                    'descricao'       => ucfirst($parsed['descricao']),
                    'valor_total'     => abs($parsed['valor']),
                    'tipo'            => $parsed['cartao_tipo_compra'] ?? 'avista',
                    'numero_parcelas' => $parsed['numero_parcelas'] ?? 1,
                    'data_compra'     => now()->toDateString(),
                    'categoria'       => $parsed['categoria'] ?? null,
                ]);

                $this->gerarParcelasCartao($compra);

                $tipoCompraStr = '';
                if ($compra->tipo === 'parcelada') {
                    $tipoCompraStr = " (Parcelado em {$compra->numero_parcelas}x)";
                } elseif ($compra->tipo === 'recorrente') {
                    $tipoCompraStr = " (Assinatura Recorrente)";
                }

                $resposta = $parsed['resposta_texto'] ?? "💳 *Compra em Cartão lançada!*\n"
                    . "💳 Cartão: *{$cartao->nome}*\n"
                    . "💸 R\$ " . number_format($compra->valor_total, 2, ',', '.') . $tipoCompraStr . "\n"
                    . "📝 " . $compra->descricao
                    . ($compra->categoria ? " | _{$compra->categoria}_" : '');

                $this->telegram->sendMessage($chatId, $resposta);

                WhatsappLog::create([
                    'numero'            => "tg:{$chatId}",
                    'mensagem_original' => $texto,
                    'status'            => 'ok',
                    'resposta'          => $resposta,
                ]);

            } else {
                // Registrar nas Contas de Casa gerais (Transações)
                $transacao = Transacao::create([
                    'user_id'      => $user->id,
                    'descricao'    => ucfirst($parsed['descricao']),
                    'valor'        => $parsed['valor'],
                    'tipo'         => $parsed['transacao_tipo'] ?? 'despesa',
                    'categoria'    => $parsed['categoria'] ?? null,
                    'subcategoria' => $parsed['subcategoria'] ?? null,
                    'data'         => now()->toDateString(),
                ]);

                $emoji    = $transacao->tipo === 'receita' ? '✅' : '💸';
                $sinal    = $transacao->tipo === 'receita' ? '+' : '-';
                $cat      = $transacao->categoria ? " | _{$transacao->categoria}_" : '';
                
                $resposta = $parsed['resposta_texto'] ?? "{$emoji} *Lançado com sucesso!*\n"
                    . "{$sinal} R\$ " . number_format($transacao->valor, 2, ',', '.') . "\n"
                    . "📝 " . $transacao->descricao
                    . $cat;

                $this->telegram->sendMessage($chatId, $resposta);

                WhatsappLog::create([
                    'numero'            => "tg:{$chatId}",
                    'mensagem_original' => $texto,
                    'transacao_id'      => $transacao->id,
                    'status'            => 'ok',
                    'resposta'          => $resposta,
                ]);
            }

        } catch (\Throwable $e) {
            $resposta = '❌ Erro ao salvar: ' . $e->getMessage();
            $this->telegram->sendMessage($chatId, $resposta);

            WhatsappLog::create([
                'numero'            => "tg:{$chatId}",
                'mensagem_original' => $texto,
                'status'            => 'erro',
                'resposta'          => $resposta,
                'erro_detalhes'     => $e->getMessage(),
            ]);

            Log::error('[Telegram Webhook] Erro ao registrar transação/compra.', ['erro' => $e->getMessage()]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Auxiliar para gerar parcelas de compras do cartão.
     */
    protected function gerarParcelasCartao(CartaoCompra $compra)
    {
        $numParcelas = $compra->tipo === 'parcelada' ? $compra->numero_parcelas : 1;
        $valorParcela = $compra->valor_total / $numParcelas;
        $dataCompra = Carbon::parse($compra->data_compra);
        $cartao = $compra->cartao;

        for ($i = 1; $i <= $numParcelas; $i++) {
            $mesOffset = ($dataCompra->day > $cartao->dia_fechamento) ? $i + 1 : $i;
            $vencimento = $dataCompra->copy()->addMonths($mesOffset)->day($cartao->dia_vencimento);
            
            CartaoParcela::create([
                'cartao_compra_id' => $compra->id,
                'numero_parcela'   => $i,
                'valor_parcela'    => $valorParcela,
                'data_vencimento'  => $vencimento,
                'status'           => 'aberta',
            ]);

            if ($compra->tipo === 'recorrente' && $i === 1) {
                for ($j = 2; $j <= 12; $j++) {
                    CartaoParcela::create([
                        'cartao_compra_id' => $compra->id,
                        'numero_parcela'   => $j,
                        'valor_parcela'    => $valorParcela,
                        'data_vencimento'  => $vencimento->copy()->addMonths($j-1),
                        'status'           => 'aberta',
                    ]);
                }
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Handlers de comandos especiais
    // ──────────────────────────────────────────────────────────────────────────

    protected function handleComando(string $comando, User $user, array $parsed = []): string
    {
        return match ($comando) {
            'saldo'  => $this->cmdSaldo($user, $parsed),
            'listar' => $this->cmdListar($user),
            'ajuda'  => $this->cmdAjuda(),
            default  => 'Comando não reconhecido.',
        };
    }

    protected function cmdSaldo(User $user, array $parsed = []): string
    {
        $mes = $parsed['mes'] ?? now()->month;
        $ano = $parsed['ano'] ?? now()->year;
        $destino = $parsed['saldo_destino'] ?? 'casa';

        if ($destino === 'cartao' && !empty($parsed['cartao_id'])) {
            $cartao = \App\Models\Cartao::where('user_id', $user->id)->find($parsed['cartao_id']);
            if (!$cartao) {
                return "❌ Cartão de crédito não encontrado.";
            }

            // Soma das parcelas do cartão com vencimento no mês/ano
            $valorFatura = \App\Models\CartaoParcela::whereHas('compra', function($q) use ($cartao) {
                    $q->where('cartao_id', $cartao->id);
                })
                ->whereMonth('data_vencimento', $mes)
                ->whereYear('data_vencimento', $ano)
                ->sum('valor_parcela');

            $nomeMes = Carbon::create($ano, $mes, 1)->translatedFormat('F/Y');

            return "💳 *Fatura de {$cartao->nome} - {$nomeMes}*\n"
                . "💰 Valor total: R\$ " . number_format($valorFatura, 2, ',', '.');
        }

        // Saldo Geral em Conta (Acumulado de todas as transações)
        $saldoGeral = Transacao::where('user_id', $user->id)
            ->selectRaw("SUM(CASE WHEN tipo = 'receita' THEN valor ELSE -valor END) as total")
            ->value('total') ?? 0;

        $receitas = Transacao::where('user_id', $user->id)
            ->where('tipo', 'receita')
            ->whereMonth('data', $mes)->whereYear('data', $ano)
            ->sum('valor');

        $despesas = Transacao::where('user_id', $user->id)
            ->where('tipo', 'despesa')
            ->whereMonth('data', $mes)->whereYear('data', $ano)
            ->sum('valor');

        $saldoMes = $receitas - $despesas;
        $emoji = $saldoGeral >= 0 ? '💚' : '🔴';
        $nomeMes = Carbon::create($ano, $mes, 1)->translatedFormat('F/Y');

        return "{$emoji} *Saldo Geral em Conta:* R\$ " . number_format($saldoGeral, 2, ',', '.') . "\n\n"
            . "📊 *Movimentação de " . $nomeMes . ":*\n"
            . "📈 Receitas: R\$ " . number_format($receitas, 2, ',', '.') . "\n"
            . "📉 Despesas: R\$ " . number_format($despesas, 2, ',', '.') . "\n"
            . "💰 Saldo do Mês: R\$ " . number_format($saldoMes, 2, ',', '.');
    }

    protected function cmdListar(User $user): string
    {
        $transacoes = Transacao::where('user_id', $user->id)
            ->whereMonth('data', now()->month)
            ->whereYear('data', now()->year)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($transacoes->isEmpty()) {
            return '📭 Nenhuma transação este mês.';
        }

        $linhas = ["📋 *Últimos 5 lançamentos:*\n"];
        foreach ($transacoes as $t) {
            $sinal = $t->tipo === 'receita' ? '+' : '-';
            $data  = Carbon::parse($t->data)->format('d/m');
            $valor = number_format($t->valor, 2, ',', '.');
            $linhas[] = "{$data} | {$sinal}R\${$valor} | {$t->descricao}";
        }

        return implode("\n", $linhas);
    }

    protected function cmdAjuda(): string
    {
        return "🤖 *Comandos de Inteligência Artificial:*\n\n"
            . "Você pode conversar comigo livremente! Exemplo:\n"
            . "• `lancei 35 de uber no cartao visa`\n"
            . "• `despesa de 50 de mercado no cartao vuon`\n"
            . "• `recebi 3000 de pix do salario`\n\n"
            . "📊 *Comandos rápidos:*\n"
            . "• `saldo` — ver saldo consolidado do mês\n"
            . "• `listar` — ver últimos 5 lançamentos\n"
            . "• `ajuda` — ver esta mensagem";
    }

    /**
     * Exclui um log específico do Telegram.
     */
    public function destroyLog(WhatsappLog $log)
    {
        $log->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Gera e envia o PDF da fatura do cartão de crédito ou do caixa.
     */
    protected function handleFaturaPdf(\App\Models\User $user, int|string $chatId, array $parsed): string
    {
        $mes = $parsed['mes'] ?? now()->month;
        $ano = $parsed['ano'] ?? now()->year;
        $destino = $parsed['fatura_destino'] ?? $parsed['saldo_destino'] ?? 'casa';

        $nomeMes = Carbon::create($ano, $mes, 1)->translatedFormat('F/Y');

        if ($destino === 'cartao') {
            $cartaoId = $parsed['cartao_id'] ?? null;
            if (!$cartaoId) {
                $cartoes = Cartao::where('user_id', $user->id)->get();
                if ($cartoes->isEmpty()) {
                    return "❌ Você não possui nenhum cartão de crédito cadastrado.";
                }
                if ($cartoes->count() === 1) {
                    $cartao = $cartoes->first();
                } else {
                    $lista = [];
                    foreach ($cartoes as $c) {
                        $lista[] = "• `fatura {$c->nome} " . Carbon::create(null, $mes)->translatedFormat('F') . "`";
                    }
                    return "❌ Por favor, especifique qual cartão deseja. Exemplo:\n" . implode("\n", $lista);
                }
            } else {
                $cartao = Cartao::where('user_id', $user->id)->find($cartaoId);
                if (!$cartao) {
                    return "❌ Cartão de crédito não encontrado.";
                }
            }

            // Busca as faturas do cartão
            $faturas = CartaoParcela::whereHas('compra', function($query) use ($cartao) {
                    $query->where('cartao_id', $cartao->id);
                })
                ->whereMonth('data_vencimento', $mes)
                ->whereYear('data_vencimento', $ano)
                ->with('compra')
                ->get();

            if ($faturas->isEmpty()) {
                return "📭 Nenhuma compra encontrada para o cartão *{$cartao->nome}* no mês de *{$nomeMes}*.";
            }

            try {
                $pdf = Pdf::loadView('exports.fatura_pdf', compact('cartao', 'faturas', 'mes', 'ano'));
                $pdfContent = $pdf->output();
                $filename = "fatura_{$cartao->nome}_{$mes}_{$ano}.pdf";

                $enviado = $this->telegram->sendDocument(
                    $chatId, 
                    $pdfContent, 
                    $filename, 
                    "📄 Fatura do cartão *{$cartao->nome}* referente a *{$nomeMes}*."
                );

                if ($enviado) {
                    return "📄 Fatura de *{$cartao->nome}* ({$nomeMes}) enviada com sucesso em PDF!";
                } else {
                    return "❌ Ocorreu um erro ao enviar o arquivo PDF da fatura.";
                }
            } catch (\Throwable $e) {
                Log::error('[Telegram Webhook] Erro ao gerar PDF do cartão.', ['erro' => $e->getMessage()]);
                return "❌ Erro ao gerar o PDF da fatura: " . $e->getMessage();
            }
        } else {
            // Caixa / Geral (Orçamento)
            $transacoes = Transacao::where('user_id', $user->id)
                ->whereMonth('data', $mes)
                ->whereYear('data', $ano)
                ->get();

            $previsoes = \App\Models\TransacaoPrevisao::where('user_id', $user->id)
                ->where('mes', $mes)
                ->where('ano', $ano)
                ->get();

            if ($transacoes->isEmpty() && $previsoes->isEmpty()) {
                return "📭 Nenhuma transação ou previsão encontrada para o caixa no mês de *{$nomeMes}*.";
            }

            try {
                $pdf = Pdf::loadView('exports.orcamento_pdf', [
                    'transacoes' => $transacoes,
                    'previsoes'  => $previsoes,
                    'mes'        => $mes,
                    'ano'        => $ano,
                    'dataInicio' => null,
                    'dataFim'    => null,
                    'chart1'     => null,
                    'chart2'     => null,
                ]);
                $pdfContent = $pdf->output();
                $filename = "caixa_{$mes}_{$ano}.pdf";

                $enviado = $this->telegram->sendDocument(
                    $chatId, 
                    $pdfContent, 
                    $filename, 
                    "📄 Relatório do caixa referente a *{$nomeMes}*."
                );

                if ($enviado) {
                    return "📄 Relatório do caixa ({$nomeMes}) enviado com sucesso em PDF!";
                } else {
                    return "❌ Ocorreu um erro ao enviar o arquivo PDF do caixa.";
                }
            } catch (\Throwable $e) {
                Log::error('[Telegram Webhook] Erro ao gerar PDF do caixa.', ['erro' => $e->getMessage()]);
                return "❌ Erro ao gerar o PDF do caixa: " . $e->getMessage();
            }
        }
    }

    /**
     * Limpa todos os logs do Telegram.
     */
    public function clearLogs()
    {
        WhatsappLog::where('numero', 'like', 'tg:%')->delete();
        return response()->json(['success' => true]);
    }
}
