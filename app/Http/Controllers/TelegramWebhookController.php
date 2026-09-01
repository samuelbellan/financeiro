<?php

namespace App\Http\Controllers;

use App\Models\Transacao;
use App\Models\User;
use App\Models\WhatsappLog;
use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoParcela;
use App\Models\NotaFiscalItem;
use App\Models\NotaFiscal;
use App\Services\WhatsappMessageParser;
use App\Services\TelegramService;
use App\Services\GeminiService;
use App\Services\CreditCardService;
use App\Services\FiscalTelegramNotifierService;
use App\Services\FiscalConcursoDataService;
use App\Models\FiscalConcurso;
use App\Models\FiscalNoticia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected WhatsappMessageParser $parser,
        protected TelegramService $telegram,
        protected GeminiService $gemini,
        protected FiscalTelegramNotifierService $fiscalNotifier,
        protected FiscalConcursoDataService $fiscalData
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

        // ── 2.1. Trava de Idempotência (Anti-Loop por Retentativa do Telegram) ─
        $updateId = $body['update_id'] ?? null;
        if ($updateId) {
            $lockKey = "tg_update_lock_{$updateId}";
            if (!Cache::add($lockKey, true, 600)) {
                Log::info('[Telegram Webhook] Update duplicado ignorado (anti-loop retentativa).', ['update_id' => $updateId]);
                return response()->json(['ok' => true, 'skipped' => 'duplicate update']);
            }
        }

        $chatId = $message['chat']['id'] ?? null;
        $texto  = $message['text'] ?? $message['caption'] ?? null;
        $photos = $message['photo'] ?? null;

        if (!$chatId || (!$texto && !$photos)) {
            return response()->json(['ok' => true, 'skipped' => 'no chat_id, text, or photo']);
        }

        Log::info('[Telegram Webhook] Mensagem recebida.', [
            'chat_id'  => $chatId,
            'texto'    => $texto,
            'has_photo' => !empty($photos),
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

        // ── 5. Processar foto de Nota Fiscal ──────────────────────────────────
        if ($photos && is_array($photos)) {
            @set_time_limit(180);

            $largestPhoto = end($photos);
            $fileId = $largestPhoto['file_id'] ?? null;
            if ($fileId) {
                // Notificar o usuário no Telegram instantaneamente
                $this->telegram->sendMessage($chatId, "📸 *Foto recebida com sucesso!*\n\n⏳ _Analisando imagem e lendo itens da nota com IA, aguarde alguns segundos..._");

                $fileInfo = $this->telegram->getFile($fileId);
                if ($fileInfo && isset($fileInfo['file_path'])) {
                    $fileBytes = $this->telegram->downloadFile($fileInfo['file_path']);
                    if ($fileBytes) {
                        [$base64Image, $mimeType] = $this->compressImageForOcr($fileBytes);

                        // Salvar imagem da nota fiscal no storage público para visualização no sistema web
                        $fotoPath = null;
                        try {
                            $filename = 'nf_' . uniqid() . '_' . time() . '.jpg';
                            $fotoPath = 'notas_fiscais/' . $filename;
                            \Illuminate\Support\Facades\Storage::disk('public')->put($fotoPath, base64_decode($base64Image));
                        } catch (\Throwable $e) {
                            Log::warning('[Telegram Webhook] Não foi possível salvar a imagem da NF no disco: ' . $e->getMessage());
                        }

                        $parsed = $this->gemini->parseReceiptImage($base64Image, $mimeType, $user->id, $texto);
                        if (isset($parsed['tipo']) && $parsed['tipo'] === 'nota_fiscal') {
                            $resposta = $this->handleNotaFiscalPhoto($user, $chatId, $parsed, $fotoPath);
                            return response()->json(['ok' => true, 'processed' => 'nota_fiscal_photo']);
                        } else {
                            $erroMsg = $parsed['erro'] ?? 'Não consegui ler a foto da nota fiscal.';
                            $this->telegram->sendMessage($chatId, "⚠️ {$erroMsg}");
                            return response()->json(['ok' => true, 'error' => $erroMsg]);
                        }
                    }
                }
            }
        }

        // ── 5.5. Interceptar Comandos do Módulo Fiscal & Concursos ─────────────
        $fiscalResponse = $this->handleFiscalTelegramCommand($texto ?? '', $chatId);
        if ($fiscalResponse !== null) {
            $this->telegram->sendMessage($chatId, $fiscalResponse);
            WhatsappLog::create([
                'numero'            => "tg:{$chatId}",
                'mensagem_original' => $texto,
                'status'            => 'ok',
                'resposta'          => $fiscalResponse,
            ]);
            return response()->json(['ok' => true, 'processed' => 'fiscal_command']);
        }

        // ── 6. Parsear mensagem de texto (OmniRoute / Gemini IA com fallback para Regex) ──────
        $parsed = $this->gemini->parseMessage($texto ?? '', $user->id);
        if (isset($parsed['tipo']) && $parsed['tipo'] === 'invalido') {
            $parsed = $this->parser->parse($texto ?? '', $user->id);
            if (empty($parsed['transacao_destino'])) {
                $parsed['transacao_destino'] = 'casa';
            }
        }

        // ── 7. Processar resultado de texto ────────────────────────────────────
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
            $vencimento = CreditCardService::calcularVencimentoParcela($cartao, $dataCompra, $i);
            
            CartaoParcela::create([
                'cartao_compra_id' => $compra->id,
                'numero_parcela'   => $i,
                'valor_parcela'    => $valorParcela,
                'data_vencimento'  => $vencimento,
                'status'           => 'aberta',
            ]);

            if ($compra->tipo === 'recorrente' && $i === 1) {
                for ($j = 2; $j <= 12; $j++) {
                    $vencRecorrente = CreditCardService::calcularVencimentoParcela($cartao, $dataCompra->copy()->addMonths($j - 1), 1);
                    CartaoParcela::create([
                        'cartao_compra_id' => $compra->id,
                        'numero_parcela'   => $j,
                        'valor_parcela'    => $valorParcela,
                        'data_vencimento'  => $vencRecorrente,
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
        return "🤖 *Assistente Financeiro & Radar Fiscal:*\n\n"
            . "💳 *Finanças e Cartões:*\n"
            . "• `lancei 35 de uber no cartao visa`\n"
            . "• `despesa de 50 de mercado no cartao vuon`\n"
            . "• `saldo` — ver saldo consolidado do mês\n"
            . "• `fatura visa pdf` — exportar fatura em PDF\n\n"
            . "🏛️ *Radar de Concursos Fiscais:*\n"
            . "• `/fiscal` ou `/concursos` — panorama dos editais quentes\n"
            . "• `/sefaz sp` — raio-x e remuneração da SEFAZ (ou qualquer UF)\n"
            . "• `/iss sp` — raio-x e remuneração do ISS (ou qualquer cidade)\n"
            . "• `/receita` — detalhes de Auditor e Analista da Receita Federal\n"
            . "• `/noticias_fiscal` — últimas notícias fiscais monitoradas\n"
            . "• `/ajuda` — ver esta mensagem";
    }

    /**
     * Trata comandos específicos do Radar Fiscal e Remunerações.
     */
    protected function handleFiscalTelegramCommand(string $texto, int|string $chatId): ?string
    {
        $limpo = trim($texto);
        $termo = strtolower($limpo);

        // 1. Comando Geral de Concursos Fiscais
        if (in_array($termo, ['/fiscal', '/concursos', '/concurso', '/radar', 'concursos fiscais', 'concursos'])) {
            return $this->fiscalNotifier->formatHotContestsSummary();
        }

        // 2. Notícias Fiscais Recentes
        if (in_array($termo, ['/noticias_fiscal', '/noticias', '/noticia', 'noticias fiscais', 'noticias fiscal'])) {
            $noticias = FiscalNoticia::recentes(5)->get();
            if ($noticias->isEmpty()) {
                return "📭 Nenhuma notícia fiscal cadastrada no momento. Execute a busca no sistema web.";
            }

            $msg = "📰 *ÚLTIMAS NOTÍCIAS DOS CONCURSOS FISCAIS* 📰\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";
            foreach ($noticias as $n) {
                $emoji = match ($n->esfera) {
                    'federal'   => '🏛️',
                    'estadual'  => '🗺️',
                    'municipal' => '🏙️',
                    default     => '📜',
                };
                $data = $n->publicado_em ? $n->publicado_em->format('d/m/Y') : '';
                $msg .= "{$emoji} *{$n->titulo}*\n";
                $msg .= "📅 {$data} | Fonte: {$n->fonte}\n";
                $msg .= "🔗 [Ver Notícia]({$n->url})\n";
                $msg .= "──────────────────────\n";
            }
            $msg .= "\n💡 _Para ver a análise salarial de um órgão, use /sefaz <uf> ou /iss <cidade>_";
            return $msg;
        }

        // 3. Receita Federal Direta
        if (in_array($termo, ['/receita', '/rfb', '/receita_federal', 'receita federal', 'auditor receita', 'analista receita'])) {
            $auditor = FiscalConcurso::where('sigla', 'RFB - Auditor')->first();
            $analista = FiscalConcurso::where('sigla', 'RFB - Analista')->first();

            $msg = "🏛️ *RECEITA FEDERAL DO BRASIL (RFB)* 🏛️\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";

            if ($auditor) {
                $msg .= "👑 *AUDITOR-FISCAL (AFRFB)*\n";
                $msg .= "💵 Inicial: `{$auditor->remuneracao_inicial_formatada}`\n";
                $msg .= "▫️ Base: R$ " . number_format((float)$auditor->vencimento_basico, 2, ',', '.') . " + Bônus Eficiência (até R$ 11.500)\n";
                $msg .= "💎 Real Transparência: `{$auditor->remuneracao_real_formatada}`\n";
                $msg .= "🏆 Teto: `{$auditor->remuneracao_teto_formatada}`\n";
                $msg .= "🎓 Requisito: {$auditor->requisito_escolaridade}\n\n";
            }

            if ($analista) {
                $msg .= "🛡️ *ANALISTA-TRIBUTÁRIO (ATRFB)*\n";
                $msg .= "💵 Inicial: `{$analista->remuneracao_inicial_formatada}`\n";
                $msg .= "▫️ Base: R$ " . number_format((float)$analista->vencimento_basico, 2, ',', '.') . " + Bônus Eficiência\n";
                $msg .= "💎 Real Transparência: `{$analista->remuneracao_real_formatada}`\n";
                $msg .= "🏆 Teto: `{$analista->remuneracao_teto_formatada}`\n";
                $msg .= "🎓 Requisito: {$analista->requisito_escolaridade}\n\n";
            }

            $msg .= "💡 _Novo pedido com mais de 2.000 vagas em tramitação no MGI._";
            return $msg;
        }

        // 4. SEFAZ Estadual (/sefaz sp, /sefaz mg, etc.)
        if (str_starts_with($termo, '/sefaz') || str_starts_with($termo, 'sefaz ')) {
            $param = trim(preg_replace('/^\/?sefaz\s*/i', '', $limpo));
            if (empty($param)) {
                return "ℹ️ *Uso do comando SEFAZ:*\nEnvie `/sefaz <UF>` para pesquisar a remuneração detalhada.\nExemplo: `/sefaz sp`, `/sefaz rj`, `/sefaz mg`, `/sefaz sc`, `/sefaz pr`.";
            }

            $concurso = $this->fiscalData->search("SEFAZ " . $param) ?? $this->fiscalData->search($param);
            if ($concurso) {
                return $this->fiscalNotifier->formatConcursoProfileMessage($concurso);
            }

            return "❌ Não encontrei dados para a SEFAZ informada ('{$param}'). Tente a sigla do estado (ex: `/sefaz sp`).";
        }

        // 5. ISS Municipal (/iss sp, /iss curitiba, /iss bh, etc.)
        if (str_starts_with($termo, '/iss') || str_starts_with($termo, 'iss ')) {
            $param = trim(preg_replace('/^\/?iss\s*/i', '', $limpo));
            if (empty($param)) {
                return "ℹ️ *Uso do comando ISS:*\nEnvie `/iss <cidade ou sigla>` para pesquisar a remuneração detalhada.\nExemplo: `/iss sp`, `/iss curitiba`, `/iss rio`, `/iss bh`, `/iss campinas`, `/iss osasco`.";
            }

            $concurso = $this->fiscalData->search("ISS " . $param) ?? $this->fiscalData->search($param);
            if ($concurso) {
                return $this->fiscalNotifier->formatConcursoProfileMessage($concurso);
            }

            return "❌ Não encontrei dados para o ISS informado ('{$param}'). Tente o nome da cidade ou sigla (ex: `/iss curitiba`, `/iss sp`).";
        }

        // 6. Pesquisa livre de remuneração (/remuneracao <termo>)
        if (str_starts_with($termo, '/remuneracao') || str_starts_with($termo, 'salario ')) {
            $param = trim(preg_replace('/^\/?(remuneracao|salario)\s*/i', '', $limpo));
            if (!empty($param)) {
                $concurso = $this->fiscalData->search($param);
                if ($concurso) {
                    return $this->fiscalNotifier->formatConcursoProfileMessage($concurso);
                }
            }
        }

        return null;
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

    private function handleNotaFiscalPhoto(User $user, int|string $chatId, array $parsed, ?string $fotoPath = null): string
    {
        $estabelecimento = $parsed['estabelecimento'] ?? 'Supermercado';
        $valorTotal = (float)($parsed['valor_total'] ?? 0);
        $transacaoDestino = $parsed['transacao_destino'] ?? 'casa';
        $cartaoId = $parsed['cartao_id'] ?? null;
        $itens = $parsed['itens'] ?? [];
        $dataCompra = !empty($parsed['data_compra']) ? Carbon::parse($parsed['data_compra']) : Carbon::now();

        if ($valorTotal <= 0) {
            $this->telegram->sendMessage($chatId, "⚠️ Não consegui identificar o valor total na nota fiscal.");
            return "Valor inválido";
        }

        $transacaoObj = null;
        $cartaoCompraObj = null;
        $cartao = null;

        if ($transacaoDestino === 'cartao') {
            if ($cartaoId) {
                $cartao = Cartao::where('user_id', $user->id)->where('id', $cartaoId)->first();
            } else {
                $cartao = Cartao::where('user_id', $user->id)->where('ativo', true)->first();
            }

            if ($cartao) {
                $cartaoCompraObj = CartaoCompra::create([
                    'cartao_id' => $cartao->id,
                    'descricao' => "Compra em {$estabelecimento} (Nota Fiscal)",
                    'valor_total' => $valorTotal,
                    'tipo' => 'avista',
                    'numero_parcelas' => 1,
                    'categoria' => 'Alimentação',
                    'data_compra' => $dataCompra,
                ]);

                $this->gerarParcelasCartao($cartaoCompraObj);
            }
        }

        if (!$cartaoCompraObj) {
            $transacaoObj = Transacao::create([
                'user_id' => $user->id,
                'descricao' => "Mercado em {$estabelecimento} (Nota Fiscal)",
                'valor' => $valorTotal,
                'tipo' => 'despesa',
                'categoria' => 'Alimentação',
                'subcategoria' => 'Mercado',
                'data' => $dataCompra,
            ]);
        }

        // Criar registro da Nota Fiscal principal
        $notaFiscal = NotaFiscal::create([
            'user_id'          => $user->id,
            'transacao_id'     => $transacaoObj?->id,
            'cartao_compra_id' => $cartaoCompraObj?->id,
            'estabelecimento'  => $estabelecimento,
            'data_compra'      => $dataCompra,
            'valor_total'      => $valorTotal,
            'foto_path'        => $fotoPath,
            'forma_pagamento'  => $cartaoCompraObj ? 'cartao' : 'casa',
            'cartao_nome'      => $cartao?->nome,
            'observacoes'      => 'Registrado via Telegram Bot com IA OCR',
        ]);

        $resumoCategorias = [];
        foreach ($itens as $item) {
            $nomeItem = $item['nome'] ?? 'Produto';
            $catItem = $item['categoria_item'] ?? 'Outros';
            $qtd = (float)($item['quantidade'] ?? 1);
            $vUnit = (float)($item['valor_unitario'] ?? $item['valor_total'] ?? 0);
            $vTotal = (float)($item['valor_total'] ?? ($qtd * $vUnit));

            NotaFiscalItem::create([
                'user_id'          => $user->id,
                'nota_fiscal_id'   => $notaFiscal->id,
                'transacao_id'     => $transacaoObj?->id,
                'cartao_compra_id' => $cartaoCompraObj?->id,
                'estabelecimento'  => $estabelecimento,
                'data_compra'      => $dataCompra,
                'nome_item'        => $nomeItem,
                'categoria_item'   => $catItem,
                'quantidade'       => $qtd,
                'valor_unitario'   => $vUnit,
                'valor_total'      => $vTotal,
            ]);

            if (!isset($resumoCategorias[$catItem])) {
                $resumoCategorias[$catItem] = ['total' => 0.0, 'qtd' => 0];
            }
            $resumoCategorias[$catItem]['total'] += $vTotal;
            $resumoCategorias[$catItem]['qtd'] += 1;
        }

        $emojis = [
            'Carnes' => '🥩',
            'Hortifruti' => '🥬',
            'Laticínios' => '🥛',
            'Padaria' => '🍞',
            'Limpeza' => '🧹',
            'Higiene' => '🧴',
            'Bebidas' => '🥤',
            'Mercearia' => '🌾',
            'Outros' => '📦'
        ];

        $totalFmt = number_format($valorTotal, 2, ',', '.');
        $destinoTxt = $cartaoCompraObj ? "Cartão " . ($cartao->nome ?? 'Crédito') : "Contas da Casa / Caixa";

        $msg = "📄 *Nota Fiscal Processada com Sucesso!*\n\n";
        $msg .= "🏬 *Estabelecimento:* {$estabelecimento}\n";
        $msg .= "📅 *Data:* " . $dataCompra->format('d/m/Y') . "\n";
        $msg .= "💰 *Valor Total:* R$ {$totalFmt} ({$destinoTxt})\n\n";
        
        if (!empty($resumoCategorias)) {
            $msg .= "🛒 *Resumo dos Itens por Categoria:*\n";
            foreach ($resumoCategorias as $cat => $info) {
                $e = $emojis[$cat] ?? '📦';
                $tFmt = number_format($info['total'], 2, ',', '.');
                $msg .= "{$e} *{$cat}:* R$ {$tFmt} ({$info['qtd']} " . ($info['qtd'] > 1 ? 'itens' : 'item') . ")\n";
            }
        }

        $msg .= "\n✅ Lançamento registrado e " . count($itens) . " itens salvos no seu histórico de mercado!";

        $this->telegram->sendMessage($chatId, $msg);
        return $msg;
    }

    /**
     * Otimiza e redimensiona imagens grandes enviadas pelo Telegram para velocidade máxima na leitura OCR pela IA.
     */
    private function compressImageForOcr(string $imageBytes, int $maxDimension = 1200, int $quality = 75): array
    {
        if (!extension_loaded('gd')) {
            return [base64_encode($imageBytes), 'image/jpeg'];
        }

        $img = @imagecreatefromstring($imageBytes);
        if (!$img) {
            return [base64_encode($imageBytes), 'image/jpeg'];
        }

        $width = imagesx($img);
        $height = imagesy($img);

        if ($width > $maxDimension || $height > $maxDimension) {
            if ($width > $height) {
                $newWidth = $maxDimension;
                $newHeight = (int)($height * ($maxDimension / $width));
            } else {
                $newHeight = $maxDimension;
                $newWidth = (int)($width * ($maxDimension / $height));
            }

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($img);
            $img = $resized;
        }

        ob_start();
        imagejpeg($img, null, $quality);
        $compressedBytes = ob_get_clean();
        imagedestroy($img);

        return [base64_encode($compressedBytes), 'image/jpeg'];
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
