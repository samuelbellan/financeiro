<?php

namespace App\Services;

use App\Models\FiscalConcurso;
use App\Models\FiscalNoticia;
use App\Models\FiscalTelegramConfig;
use Illuminate\Support\Facades\Log;

class FiscalTelegramNotifierService
{
    public function __construct(
        protected TelegramService $telegram,
        protected FiscalConcursoDataService $dataService
    ) {}

    /**
     * Formata uma mensagem completa e aprofundada de alerta fiscal para o Telegram.
     */
    public function formatNewsMessage(FiscalNoticia $noticia, ?FiscalConcurso $concurso = null): string
    {
        $concurso = $concurso ?? $noticia->concurso;

        $esferaEmoji = match ($noticia->esfera) {
            'federal'   => '🏛️ *FEDERAL*',
            'estadual'  => '🗺️ *ESTADUAL*',
            'municipal' => '🏙️ *MUNICIPAL (ISS)*',
            default     => '📜 *FISCAL GERAL*',
        };

        $ufBadge = $noticia->uf ? " [{$noticia->uf}]" : '';
        $tituloLimpo = str_replace(['*', '_', '`', '['], '', $noticia->titulo);
        $resumoLimpo = str_replace(['*', '_', '`', '['], '', $noticia->resumo ?? '');

        $msg = "🚨 *RADAR FISCAL | NOVO ALERTA* 🚨\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "{$esferaEmoji}{$ufBadge}\n";
        $msg .= "📢 *{$tituloLimpo}*\n\n";

        if (!empty($noticia->status_detectado)) {
            $msg .= "📌 *Status:* `{$noticia->status_detectado}`\n";
        }
        $msg .= "📰 *Fonte:* {$noticia->fonte}\n\n";

        // Se houver concurso associado, incluir o Raio-X detalhado de Remuneração
        if ($concurso) {
            $msg .= "💰 *RAIO-X DE REMUNERAÇÃO (PESQUISA APROFUNDADA)*\n";
            $msg .= "──────────────────────\n";
            $msg .= "💼 *Cargo:* {$concurso->cargo_principal}\n";
            $msg .= "💵 *Inicial Total Bruto:* `{$concurso->remuneracao_inicial_formatada}`\n";
            $msg .= "▫️ *Vencimento Base:* R$ " . number_format((float)$concurso->vencimento_basico, 2, ',', '.') . "\n";
            $msg .= "▫️ *Produtividade / Bônus:* R$ " . number_format((float)$concurso->produtividade_estimada, 2, ',', '.') . "\n";

            if (!empty($concurso->produtividade_detalhes)) {
                $detalhesLimpos = str_replace(['*', '_', '`', '['], '', $concurso->produtividade_detalhes);
                $msg .= "  ↳ _{$detalhesLimpos}_\n";
            }

            if ($concurso->beneficios_estimados > 0) {
                $msg .= "▫️ *Auxílios / Benefícios:* R$ " . number_format((float)$concurso->beneficios_estimados, 2, ',', '.') . "\n";
            }

            $msg .= "💎 *Média Real na Transparência:* `{$concurso->remuneracao_real_formatada}`\n";
            $msg .= "🏆 *Final de Carreira / Teto:* `{$concurso->remuneracao_teto_formatada}`\n\n";

            $msg .= "🎓 *Escolaridade:* {$concurso->requisito_escolaridade}\n";
            if (!empty($concurso->banca)) {
                $msg .= "🏢 *Banca Prevista:* {$concurso->banca}\n";
            }
            if (!empty($concurso->vagas_previstas)) {
                $msg .= "👥 *Vagas Previstas:* {$concurso->vagas_previstas}\n";
            }
            $msg .= "\n";
        } elseif (!empty($noticia->dados_remuneracao_snapshot)) {
            $snap = $noticia->dados_remuneracao_snapshot;
            $msg .= "💰 *REMUNERAÇÃO ESTIMADA:* R$ " . number_format((float)($snap['inicial_bruto'] ?? 0), 2, ',', '.') . "\n\n";
        }

        if (!empty($resumoLimpo)) {
            $msg .= "📝 *Resumo:*\n{$resumoLimpo}\n\n";
        }

        $msg .= "🔗 [Ler Matéria Completa]({$noticia->url})\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "⚡ _Radar de Concursos Fiscais Automatizado_";

        return $msg;
    }

    /**
     * Formata mensagem de Raio-X Salarial direto para um concurso (para bot do Telegram).
     */
    public function formatConcursoProfileMessage(FiscalConcurso $concurso): string
    {
        $esferaEmoji = match ($concurso->esfera) {
            'federal'   => '🏛️ *FEDERAL*',
            'estadual'  => '🗺️ *ESTADUAL*',
            'municipal' => '🏙️ *MUNICIPAL (ISS)*',
            default     => '📜 *FISCAL*',
        };

        $msg = "📊 *RAIO-X DO CONCURSO FISCAL*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "{$esferaEmoji} • *{$concurso->sigla}*\n";
        $msg .= "🏛️ *Órgão:* {$concurso->nome_orgao}\n";
        $msg .= "💼 *Cargo:* {$concurso->cargo_principal}\n";
        $msg .= "📍 *Localidade:* {$concurso->municipio}" . ($concurso->uf ? " ({$concurso->uf})" : '') . "\n";
        $msg .= "🚦 *Status Atual:* `{$concurso->status_formatado}`\n\n";

        $msg .= "💰 *ESTRUTURA REMUNERATÓRIA APROFUNDADA*\n";
        $msg .= "──────────────────────\n";
        $msg .= "💵 *Remuneração Inicial Bruta:* `{$concurso->remuneracao_inicial_formatada}`\n";
        $msg .= "▫️ *Vencimento Básico:* R$ " . number_format((float)$concurso->vencimento_basico, 2, ',', '.') . "\n";
        $msg .= "▫️ *Produtividade / Gratificação:* R$ " . number_format((float)$concurso->produtividade_estimada, 2, ',', '.') . "\n";

        if (!empty($concurso->produtividade_detalhes)) {
            $msg .= "  ↳ _{$concurso->produtividade_detalhes}_\n";
        }

        if ($concurso->beneficios_estimados > 0) {
            $msg .= "▫️ *Auxílios / Benefícios:* R$ " . number_format((float)$concurso->beneficios_estimados, 2, ',', '.') . "\n";
            if (!empty($concurso->beneficios_detalhes)) {
                $msg .= "  ↳ _{$concurso->beneficios_detalhes}_\n";
            }
        }

        $msg .= "💎 *Remuneração Real (Transparência):* `{$concurso->remuneracao_real_formatada}`\n";
        $msg .= "🏆 *Final de Carreira (Teto):* `{$concurso->remuneracao_teto_formatada}`\n\n";

        $msg .= "ℹ️ *DETALHES DO CERTAME*\n";
        $msg .= "──────────────────────\n";
        $msg .= "🎓 *Requisito:* {$concurso->requisito_escolaridade}\n";
        $msg .= "🏢 *Banca:* " . ($concurso->banca ?? 'Em definição') . "\n";
        $msg .= "👥 *Vagas Previstas:* " . ($concurso->vagas_previstas ?? 'A definir') . "\n";
        $msg .= "📜 *Lei da Carreira:* " . ($concurso->lei_carreira ?? 'Regulamentação estadual/municipal') . "\n";

        if ($concurso->ultimo_concurso_ano) {
            $msg .= "📅 *Último Edital:* {$concurso->ultimo_concurso_ano} (" . ($concurso->ultimo_concurso_banca ?? 'Banca') . ")\n";
        }

        if (!empty($concurso->observacoes_estrategicas)) {
            $msg .= "\n💡 *Dica Estratégica:* {$concurso->observacoes_estrategicas}\n";
        }

        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "⚡ _Consulte outros com /sefaz <uf>, /iss <cidade> ou /receita_";

        return $msg;
    }

    /**
     * Envia uma notícia para o Telegram.
     */
    public function sendNewsAlert(FiscalNoticia $noticia, ?string $targetChatId = null): bool
    {
        $chatId = $targetChatId ?? config('telegram.allowed_chat_id');
        if (empty($chatId)) {
            Log::warning('[FiscalTelegramNotifier] Nenhum chat_id configurado para disparo.');
            return false;
        }

        // Se houver múltiplos IDs separados por vírgula
        $chatIds = explode(',', $chatId);
        $message = $this->formatNewsMessage($noticia);
        $allSuccess = true;

        foreach ($chatIds as $cid) {
            $cleanId = trim($cid);
            if (!empty($cleanId)) {
                $enviado = $this->telegram->sendMessage($cleanId, $message);
                if (!$enviado) {
                    $allSuccess = false;
                }
            }
        }

        if ($allSuccess) {
            $noticia->update([
                'notificado_telegram' => true,
                'notificado_em'       => now(),
            ]);
        }

        return $allSuccess;
    }

    /**
     * Dispara em lote todas as notícias pendentes respeitando filtros.
     */
    public function notifyPendingNews(): int
    {
        $noticias = FiscalNoticia::naoNotificadas()
            ->apenasFuturosOuAbertos()
            ->with('concurso')
            ->orderBy('publicado_em', 'asc')
            ->get();
        $count = 0;

        foreach ($noticias as $noticia) {
            $enviado = $this->sendNewsAlert($noticia);
            if ($enviado) {
                $count++;
                // Pequena pausa para respeitar rate limit do Telegram Bot API
                usleep(300000); // 300ms
            }
        }

        Log::info("[FiscalTelegramNotifier] {$count} notícias fiscais notificadas no Telegram.");
        return $count;
    }

    /**
     * Formata um resumo geral dos concursos fiscais mais quentes (ainda não realizados).
     */
    public function formatHotContestsSummary(): string
    {
        $concursos = FiscalConcurso::apenasFuturosOuAbertos()
            ->emDestaque()
            ->orderBy('remuneracao_inicial_bruta', 'desc')
            ->take(10)
            ->get();

        $msg = "🔥 *RADAR FISCAL | CONCURSOS MAIS QUENTES* 🔥\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";

        foreach ($concursos as $c) {
            $emoji = match ($c->esfera) {
                'federal'   => '🏛️',
                'estadual'  => '🗺️',
                'municipal' => '🏙️',
            };

            $msg .= "{$emoji} *{$c->sigla}* ({$c->status_formatado})\n";
            $msg .= "💵 Inicial: `{$c->remuneracao_inicial_formatada}` | Real: `{$c->remuneracao_real_formatada}`\n";
            $msg .= "👥 Vagas: " . ($c->vagas_previstas ?? 'Em definição') . " | Banca: " . ($c->banca ?? 'A definir') . "\n";
            $msg .= "──────────────────────\n";
        }

        $msg .= "\n💡 _Para ver o raio-x completo de um órgão, envie:_\n";
        $msg .= "• `/sefaz sp` (ou qualquer estado)\n";
        $msg .= "• `/iss curitiba` (ou qualquer cidade)\n";
        $msg .= "• `/receita` (Receita Federal)\n";
        $msg .= "• `/noticias_fiscal` (Últimas notícias)";

        return $msg;
    }
}
