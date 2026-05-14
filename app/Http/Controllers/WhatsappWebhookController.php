<?php

namespace App\Http\Controllers;

use App\Models\Transacao;
use App\Models\User;
use App\Models\WhatsappLog;
use App\Services\WhatsappMessageParser;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function __construct(
        protected WhatsappMessageParser $parser,
        protected WhatsappService $whatsapp
    ) {}

    /**
     * Recebe o webhook da Evolution API.
     * POST /webhook/whatsapp
     */
    public function receive(Request $request)
    {
        // ── 1. Validar token de segurança (header ou query param) ──────────────
        $token = $request->header('x-webhook-token') ?? $request->query('token');
        if ($token !== config('whatsapp.webhook_token')) {
            Log::warning('[WhatsApp Webhook] Token inválido.', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // ── 2. Extrair dados do payload da Evolution API ───────────────────────
        $body = $request->all();

        // Ignora eventos que não sejam mensagens recebidas
        $event = $body['event'] ?? $body['type'] ?? null;
        if (!in_array($event, ['messages.upsert', 'MESSAGES_UPSERT', 'message'])) {
            return response()->json(['ok' => true, 'skipped' => 'not a message event']);
        }

        // Suporte a múltiplos formatos de payload da Evolution API v1/v2
        $msgData = $body['data'] ?? $body['message'] ?? null;
        $numero  = $this->extractNumber($msgData ?? $body);
        $texto   = $this->extractText($msgData ?? $body);

        if (!$numero || !$texto) {
            return response()->json(['ok' => true, 'skipped' => 'no number or text']);
        }

        // Ignorar mensagens enviadas pelo próprio bot
        $fromMe = ($msgData['key']['fromMe'] ?? $body['fromMe'] ?? false);
        if ($fromMe) {
            return response()->json(['ok' => true, 'skipped' => 'own message']);
        }

        Log::info('[WhatsApp Webhook] Mensagem recebida.', ['numero' => $numero, 'texto' => $texto]);

        // ── 3. Validar número autorizado ───────────────────────────────────────
        $allowedNumber = config('whatsapp.allowed_number');
        $numeroLimpo   = preg_replace('/\D/', '', $numero);
        $allowedLimpo  = preg_replace('/\D/', '', $allowedNumber);

        if ($allowedLimpo && $numeroLimpo !== $allowedLimpo) {
            Log::warning('[WhatsApp Webhook] Número não autorizado.', ['numero' => $numero]);
            return response()->json(['ok' => true, 'skipped' => 'unauthorized number']);
        }

        // ── 4. Encontrar usuário dono do sistema ───────────────────────────────
        $user = User::first(); // sistema single-user; ajuste se multi-usuário
        if (!$user) {
            return response()->json(['error' => 'No user found'], 500);
        }

        // ── 5. Parsear mensagem ────────────────────────────────────────────────
        $parsed = $this->parser->parse($texto, $user->id);

        // ── 6. Processar resultado ─────────────────────────────────────────────
        if ($parsed['tipo'] === 'comando') {
            $resposta = $this->handleComando($parsed['comando'], $user);
            $this->whatsapp->sendText($numero, $resposta);

            WhatsappLog::create([
                'numero'            => $numero,
                'mensagem_original' => $texto,
                'status'            => 'ok',
                'resposta'          => $resposta,
            ]);

            return response()->json(['ok' => true]);
        }

        if ($parsed['tipo'] === 'invalido') {
            $resposta = '❌ ' . $parsed['erro'];
            $this->whatsapp->sendText($numero, $resposta);

            WhatsappLog::create([
                'numero'            => $numero,
                'mensagem_original' => $texto,
                'status'            => 'ignorado',
                'resposta'          => $resposta,
            ]);

            return response()->json(['ok' => true]);
        }

        // ── 7. Criar transação ─────────────────────────────────────────────────
        try {
            $transacao = Transacao::create([
                'user_id'     => $user->id,
                'descricao'   => ucfirst($parsed['descricao']),
                'valor'       => $parsed['valor'],
                'tipo'        => $parsed['transacao_tipo'],
                'categoria'   => $parsed['categoria'],
                'subcategoria'=> $parsed['subcategoria'],
                'data'        => now()->toDateString(),
            ]);

            $emoji    = $parsed['transacao_tipo'] === 'receita' ? '✅' : '💸';
            $sinal    = $parsed['transacao_tipo'] === 'receita' ? '+' : '-';
            $cat      = $parsed['categoria'] ? " | _{$parsed['categoria']}_" : '';
            $resposta = "{$emoji} *Lançado com sucesso!*\n"
                . "{$sinal} R\$ " . number_format($parsed['valor'], 2, ',', '.') . "\n"
                . "📝 " . ucfirst($parsed['descricao'])
                . $cat;

            $this->whatsapp->sendText($numero, $resposta);

            WhatsappLog::create([
                'numero'            => $numero,
                'mensagem_original' => $texto,
                'transacao_id'      => $transacao->id,
                'status'            => 'ok',
                'resposta'          => $resposta,
            ]);
        } catch (\Throwable $e) {
            $resposta = '❌ Erro ao salvar: ' . $e->getMessage();
            $this->whatsapp->sendText($numero, $resposta);

            WhatsappLog::create([
                'numero'            => $numero,
                'mensagem_original' => $texto,
                'status'            => 'erro',
                'resposta'          => $resposta,
                'erro_detalhes'     => $e->getMessage(),
            ]);

            Log::error('[WhatsApp Webhook] Erro ao criar transação.', ['erro' => $e->getMessage()]);
        }

        return response()->json(['ok' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Handlers de comandos especiais
    // ──────────────────────────────────────────────────────────────────────────

    protected function handleComando(string $comando, User $user): string
    {
        return match ($comando) {
            'saldo'  => $this->cmdSaldo($user),
            'listar' => $this->cmdListar($user),
            'ajuda'  => $this->cmdAjuda(),
            default  => 'Comando não reconhecido.',
        };
    }

    protected function cmdSaldo(User $user): string
    {
        $mes = now()->month;
        $ano = now()->year;

        $receitas = Transacao::where('user_id', $user->id)
            ->where('tipo', 'receita')
            ->whereMonth('data', $mes)->whereYear('data', $ano)
            ->sum('valor');

        $despesas = Transacao::where('user_id', $user->id)
            ->where('tipo', 'despesa')
            ->whereMonth('data', $mes)->whereYear('data', $ano)
            ->sum('valor');

        $saldo = $receitas - $despesas;
        $emoji = $saldo >= 0 ? '💚' : '🔴';

        return "{$emoji} *Saldo de " . Carbon::now()->translatedFormat('F/Y') . "*\n"
            . "📈 Receitas: R\$ " . number_format($receitas, 2, ',', '.') . "\n"
            . "📉 Despesas: R\$ " . number_format($despesas, 2, ',', '.') . "\n"
            . "💰 Saldo: R\$ " . number_format($saldo, 2, ',', '.');
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
        return "🤖 *Comandos disponíveis:*\n\n"
            . "💸 *despesa* _valor_ _descrição_ [categoria]\n"
            . "Exemplo: `despesa 45.90 mercado alimentação`\n\n"
            . "💰 *receita* _valor_ _descrição_ [categoria]\n"
            . "Exemplo: `receita 3200 salário`\n\n"
            . "📊 *saldo* — ver saldo do mês atual\n"
            . "📋 *listar* — últimas 5 transações\n"
            . "❓ *ajuda* — esta mensagem";
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Helpers para extrair número e texto do payload
    // ──────────────────────────────────────────────────────────────────────────

    protected function extractNumber(array $data): ?string
    {
        // Evolution API v2
        if (isset($data['key']['remoteJid'])) {
            return preg_replace('/@.*/', '', $data['key']['remoteJid']);
        }
        // Fallback campos comuns
        foreach (['from', 'numero', 'phone', 'remoteJid'] as $field) {
            if (!empty($data[$field])) {
                return preg_replace('/@.*/', '', $data[$field]);
            }
        }
        return null;
    }

    protected function extractText(array $data): ?string
    {
        // Evolution API v2
        if (isset($data['message']['conversation'])) {
            return trim($data['message']['conversation']);
        }
        if (isset($data['message']['extendedTextMessage']['text'])) {
            return trim($data['message']['extendedTextMessage']['text']);
        }
        // Campos genéricos
        foreach (['body', 'text', 'mensagem', 'message'] as $field) {
            if (is_string($data[$field] ?? null) && !empty($data[$field])) {
                return trim($data[$field]);
            }
        }
        return null;
    }
}
