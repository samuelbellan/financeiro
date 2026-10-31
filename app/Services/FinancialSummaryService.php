<?php

namespace App\Services;

use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoParcela;
use App\Models\CartaoPrevisao;
use App\Models\Categoria;
use App\Models\Transacao;
use App\Models\TransacaoPrevisao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class FinancialSummaryService
{
    public const WHATSAPP_NUMBERS = [
        [
            'label' => 'WhatsApp 1',
            'phone' => '5567984376473',
            'formatted' => '+55 (67) 98437-6473',
        ],
        [
            'label' => 'WhatsApp 2',
            'phone' => '5567984456378',
            'formatted' => '+55 (67) 98445-6378',
        ],
    ];

    /**
     * Gera o relatório financeiro consolidado e a mensagem formatada para WhatsApp.
     */
    public static function generateSummary(User $user, int $mes, int $ano, ?string $phone = null): array
    {
        $nomeMes = Carbon::create($ano, $mes, 1)->translatedFormat('F');
        $nomeMesCap = ucfirst($nomeMes);

        // 1. CONTA CORRENTE - DADOS REALIZADOS E SALDO
        // Saldo bancário atual acumulado de todas as transações
        $saldoCCAtual = (float) (Transacao::where('user_id', $user->id)
            ->selectRaw("SUM(CASE WHEN tipo = 'receita' THEN valor ELSE -valor END) as total")
            ->value('total') ?? 0.0);

        // Transações do mês corrente
        $transacoesMes = Transacao::where('user_id', $user->id)
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->get();

        $entradasRealizadas = (float) $transacoesMes->where('tipo', 'receita')->sum('valor');
        $saidasRealizadas = (float) $transacoesMes->where('tipo', 'despesa')->sum('valor');

        // Previsões de receitas e despesas da conta planejadas para o mês
        $previsoesMes = TransacaoPrevisao::where('user_id', $user->id)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->get();

        $previsoesReceita = $previsoesMes->where('tipo', 'receita');
        $previsoesDespesa = $previsoesMes->where('tipo', 'despesa');

        // Entradas que faltam realizar (receitas previstas que ainda não entraram)
        $entradasQueFaltam = 0.0;
        foreach ($previsoesReceita as $p) {
            $consumoReceita = $transacoesMes->filter(function($t) use ($p) {
                if ($t->tipo !== 'receita') return false;
                if (!CategorySanitizer::isMatch($p->categoria, $t->categoria)) return false;
                if ($p->subcategoria) {
                    return CategorySanitizer::isMatch($p->subcategoria, $t->subcategoria);
                }
                return empty($t->subcategoria);
            })->sum('valor');

            $restante = max(0, $p->valor_previsto - $consumoReceita);
            $entradasQueFaltam += $restante;
        }

        // Faturas de cartão de crédito no mês (cartões ativos)
        $faturasMes = CartaoParcela::whereHas('compra.cartao', function($q) use ($user) {
                $q->where('user_id', $user->id)->where('ativo', true);
            })
            ->whereMonth('data_vencimento', $mes)
            ->whereYear('data_vencimento', $ano)
            ->with('compra.cartao')
            ->get();

        $faturasAbertas = (float) $faturasMes->where('status', '!=', 'paga')->sum('valor_parcela');

        // Saídas que faltam realizar (despesas previstas da CC restantes)
        $saidasPrevisoesFaltam = 0.0;
        foreach ($previsoesDespesa as $p) {
            $consumoDespesa = $transacoesMes->filter(function($t) use ($p) {
                if ($t->tipo !== 'despesa') return false;
                if (!CategorySanitizer::isMatch($p->categoria, $t->categoria)) return false;
                if ($p->subcategoria) {
                    return CategorySanitizer::isMatch($p->subcategoria, $t->subcategoria);
                }
                return empty($t->subcategoria);
            })->sum('valor');

            $restante = max(0, $p->valor_previsto - $consumoDespesa);
            $saidasPrevisoesFaltam += $restante;
        }

        // Previsões de categorias cadastradas nos cartões ativos
        $cartaoPrevisoes = CartaoPrevisao::whereHas('cartao', function($q) use ($user) {
                $q->where('user_id', $user->id)->where('ativo', true);
            })
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->get();

        // Comparativo com limites/previsões de gastos por categoria dos cartões (apurado pelas faturas do mês)
        $categoriasLimitesInfo = [];
        $cartaoPrevisoesRestante = 0.0;

        foreach ($cartaoPrevisoes as $cp) {
            $consumo = (float) $faturasMes->filter(function($fatura) use ($cp) {
                return $fatura->compra 
                    && $fatura->compra->cartao_id == $cp->cartao_id 
                    && CategorySanitizer::isMatch($fatura->compra->categoria, $cp->categoria);
            })->sum('valor_parcela');

            $limite = (float) $cp->valor_previsto;
            $faltaParaLimite = max(0, $limite - $consumo);
            $ultrapassou = $consumo > $limite ? ($consumo - $limite) : 0;

            $cartaoPrevisoesRestante += $faltaParaLimite;

            $categoriasLimitesInfo[] = [
                'categoria' => $cp->categoria,
                'cartao' => $cp->cartao->nome ?? '',
                'gasto' => $consumo,
                'limite' => $limite,
                'falta' => $faltaParaLimite,
                'ultrapassou' => $ultrapassou,
            ];
        }

        // Saídas que faltam pagar = despesas fixas previstas restantes + faturas de cartão abertas + margem prevista de cartões que ainda falta gastar
        $saidasQueFaltam = (float) ($saidasPrevisoesFaltam + $faturasAbertas + $cartaoPrevisoesRestante);

        // TOTAIS DO MÊS
        $totalEntradasMes = $entradasRealizadas + $entradasQueFaltam;
        $totalSaidasMes = $saidasRealizadas + $saidasQueFaltam;

        // 1º CONCEITO: Resultado Líquido Projetado do Mês = (Entradas Feitas + Que Faltam) - (Saídas Feitas + Que Faltam)
        $resultadoLiquidoMes = $totalEntradasMes - $totalSaidasMes;

        // 2º CONCEITO: Saldo Bancário Final Previsto na Conta = Saldo Atual CC + Entradas que Faltam - Saídas que Faltam
        $saldoFinalContaPrevisto = $saldoCCAtual + $entradasQueFaltam - $saidasQueFaltam;

        // Categorias do usuário para sanitização
        $userCategorias = Categoria::where('user_id', $user->id)->pluck('nome')->toArray();

        // Parcelas da fatura do mês atual
        $gastosAVistaPorCategoria = [];
        $totalAVista = 0.0;
        $totalAPrazo = 0.0;
        $comprasAPrazoLista = [];

        foreach ($faturasMes as $fatura) {
            $compra = $fatura->compra;
            $cat = CategorySanitizer::sanitize($compra->categoria, $userCategorias);
            $valor = (float) $fatura->valor_parcela;

            $isAVista = in_array($compra->tipo, ['avista', 'recorrente']);

            if ($isAVista) {
                $totalAVista += $valor;
                if (!isset($gastosAVistaPorCategoria[$cat])) {
                    $gastosAVistaPorCategoria[$cat] = 0.0;
                }
                $gastosAVistaPorCategoria[$cat] += $valor;
            } else {
                $totalAPrazo += $valor;
                $comprasAPrazoLista[] = [
                    'descricao' => $compra->descricao,
                    'cartao' => $compra->cartao->nome ?? 'Cartão',
                    'parcela' => "{$fatura->numero_parcela}/{$compra->numero_parcelas}",
                    'valor' => $valor,
                    'categoria' => $cat,
                ];
            }
        }

        // 3. DICAS PARA POUPAR
        $dicas = [];
        if ($saldoFinalContaPrevisto < 0) {
            $defict = abs($saldoFinalContaPrevisto);
            $dicas[] = "⚠️ *Atenção ao Fluxo:* O saldo final projetado na conta está negativo em R$ " . number_format($defict, 2, ',', '.') . ". Reavalie despesas não essenciais ou antecipe entradas.";
        } elseif ($resultadoLiquidoMes > 0) {
            $dicas[] = "💡 *Capacidade de Poupança:* O resultado líquido do mês é positivo (+R$ " . number_format($resultadoLiquidoMes, 2, ',', '.') . "). Separe ao menos 10% a 20% para sua reserva de emergência ou metas financeiras.";
        } else {
            $dicas[] = "⚖️ *Orçamento Justo:* As saídas estão quase equivalentes às entradas. Evite novas compras parceladas este mês para não comprometer o próximo.";
        }

        $estouradas = array_filter($categoriasLimitesInfo, fn($i) => $i['ultrapassou'] > 0);
        if (!empty($estouradas)) {
            $nomesEstouradas = array_map(fn($e) => "{$e['categoria']} (+R$ " . number_format($e['ultrapassou'], 2, ',', '.') . ")", $estouradas);
            $dicas[] = "🎯 *Limite de Categorias:* As categorias " . implode(', ', $nomesEstouradas) . " ultrapassaram o teto estipulado. Reduza gastos imediatos nelas.";
        }

        if ($totalAPrazo > 0 && ($totalAPrazo > $totalAVista)) {
            $dicas[] = "💳 *Parcelamentos:* Suas compras a prazo (R$ " . number_format($totalAPrazo, 2, ',', '.') . ") superam as à vista. Priorize quitar parcelas para liberar margem no orçamento.";
        }

        if (empty($dicas) || count($dicas) < 2) {
            $dicas[] = "🛒 *Compras Inteligentes:* Ao comprar em mercados ou aplicativos, utilize listas prévias e aproveite promoções para manter a meta de despesas em dia.";
        }

        // Link seguro para visualização/download do PDF completo
        $pdfUrl = URL::signedRoute('financas.relatorio.pdf', [
            'mes' => $mes,
            'ano' => $ano,
            'user_id' => $user->id,
        ]);

        // Formatação da Mensagem para WhatsApp
        $msg = self::buildWhatsAppText(
            nomeMesCap: $nomeMesCap,
            ano: $ano,
            saldoCCAtual: $saldoCCAtual,
            entradasRealizadas: $entradasRealizadas,
            entradasQueFaltam: $entradasQueFaltam,
            totalEntradasMes: $totalEntradasMes,
            saidasRealizadas: $saidasRealizadas,
            saidasQueFaltam: $saidasQueFaltam,
            totalSaidasMes: $totalSaidasMes,
            resultadoLiquidoMes: $resultadoLiquidoMes,
            saldoFinalContaPrevisto: $saldoFinalContaPrevisto,
            gastosAVistaPorCategoria: $gastosAVistaPorCategoria,
            categoriasLimitesInfo: $categoriasLimitesInfo,
            comprasAPrazoLista: $comprasAPrazoLista,
            totalAPrazo: $totalAPrazo,
            totalAVista: $totalAVista,
            dicas: $dicas,
            faturasAbertas: $faturasAbertas,
            saidasPrevisoesFaltam: $saidasPrevisoesFaltam,
            cartaoPrevisoesRestante: $cartaoPrevisoesRestante,
            pdfUrl: $pdfUrl
        );

        // Links de WhatsApp para os números cadastrados
        $selectedPhone = $phone ?: self::WHATSAPP_NUMBERS[0]['phone'];
        $cleanPhone = preg_replace('/\D/', '', $selectedPhone);

        $whatsappUrls = [];
        foreach (self::WHATSAPP_NUMBERS as $num) {
            $whatsappUrls[$num['phone']] = 'https://api.whatsapp.com/send?phone=' . $num['phone'] . '&text=' . rawurlencode($msg);
        }

        return [
            'mes' => $mes,
            'ano' => $ano,
            'saldo_cc' => $saldoCCAtual,
            'entradas_realizadas' => $entradasRealizadas,
            'entradas_faltam' => $entradasQueFaltam,
            'total_entradas' => $totalEntradasMes,
            'saidas_realizadas' => $saidasRealizadas,
            'saidas_faltam' => $saidasQueFaltam,
            'faturas_abertas' => $faturasAbertas,
            'saidas_previsoes_faltam' => $saidasPrevisoesFaltam,
            'cartao_previsoes_restante' => $cartaoPrevisoesRestante,
            'total_saidas' => $totalSaidasMes,
            'resultado_liquido_mes' => $resultadoLiquidoMes,
            'previsao_fim_mes' => $saldoFinalContaPrevisto,
            'saldo_final_conta' => $saldoFinalContaPrevisto,
            'gastos_avista_categoria' => $gastosAVistaPorCategoria,
            'limites_categoria' => $categoriasLimitesInfo,
            'compras_a_prazo' => $comprasAPrazoLista,
            'total_a_prazo' => $totalAPrazo,
            'total_a_vista' => $totalAVista,
            'dicas' => $dicas,
            'pdf_url' => $pdfUrl,
            'pdf_download_url' => $pdfUrl . '&download=1',
            'whatsapp_text' => $msg,
            'whatsapp_url' => 'https://api.whatsapp.com/send?phone=' . $cleanPhone . '&text=' . rawurlencode($msg),
            'whatsapp_urls' => $whatsappUrls,
            'whatsapp_contacts' => self::WHATSAPP_NUMBERS,
        ];
    }

    private static function buildWhatsAppText(
        string $nomeMesCap,
        int $ano,
        float $saldoCCAtual,
        float $entradasRealizadas,
        float $entradasQueFaltam,
        float $totalEntradasMes,
        float $saidasRealizadas,
        float $saidasQueFaltam,
        float $totalSaidasMes,
        float $resultadoLiquidoMes,
        float $saldoFinalContaPrevisto,
        array $gastosAVistaPorCategoria,
        array $categoriasLimitesInfo,
        array $comprasAPrazoLista,
        float $totalAPrazo,
        float $totalAVista,
        array $dicas,
        float $faturasAbertas = 0.0,
        float $saidasPrevisoesFaltam = 0.0,
        float $cartaoPrevisoesRestante = 0.0,
        ?string $pdfUrl = null
    ): string {
        $lines = [];
        $lines[] = "📊 *RESUMO FINANCEIRO — {$nomeMesCap}/{$ano}*";
        $lines[] = "────────────────────────────";
        
        // CONTA CORRENTE
        $lines[] = "🏦 *CONTA CORRENTE*";
        $lines[] = "• Saldo Atual em Conta: *R$ " . number_format($saldoCCAtual, 2, ',', '.') . "*";
        $lines[] = "";
        $lines[] = "📈 *Entradas do Mês (Total: R$ " . number_format($totalEntradasMes, 2, ',', '.') . "):*";
        $lines[] = "  ▫️ Já Recebidas: *R$ " . number_format($entradasRealizadas, 2, ',', '.') . "*";
        $lines[] = "  ▫️ Faltam Receber: *R$ " . number_format($entradasQueFaltam, 2, ',', '.') . "*";
        $lines[] = "";
        $lines[] = "📉 *Saídas do Mês (Total: R$ " . number_format($totalSaidasMes, 2, ',', '.') . "):*";
        $lines[] = "  ▫️ Já Pagas: *R$ " . number_format($saidasRealizadas, 2, ',', '.') . "*";
        $lines[] = "  ▫️ Faltam Pagar: *R$ " . number_format($saidasQueFaltam, 2, ',', '.') . "*";
        if ($faturasAbertas > 0 || $cartaoPrevisoesRestante > 0) {
            $detalhesSaidas = [];
            if ($saidasPrevisoesFaltam > 0) $detalhesSaidas[] = "Contas: R$ " . number_format($saidasPrevisoesFaltam, 2, ',', '.');
            if ($faturasAbertas > 0) $detalhesSaidas[] = "Faturas: R$ " . number_format($faturasAbertas, 2, ',', '.');
            if ($cartaoPrevisoesRestante > 0) $detalhesSaidas[] = "Margem prevista cartões: R$ " . number_format($cartaoPrevisoesRestante, 2, ',', '.');
            $lines[] = "     _(" . implode(' | ', $detalhesSaidas) . ")_";
        }
        $lines[] = "";

        // AS 2 PROJEÇÕES COM EXPLICAÇÃO BREVE
        $emojiLiq = $resultadoLiquidoMes >= 0 ? "🟢" : "🔴";
        $emojiFinal = $saldoFinalContaPrevisto >= 0 ? "🏦" : "⚠️";

        $lines[] = "🔮 *Previsões de Fim de Mês:*";
        $lines[] = "• {$emojiLiq} *Resultado Líquido do Mês:* R$ " . number_format($resultadoLiquidoMes, 2, ',', '.');
        $lines[] = "  _(Todas Entradas: R$ " . number_format($totalEntradasMes, 2, ',', '.') . " - Todas Saídas: R$ " . number_format($totalSaidasMes, 2, ',', '.') . ")_";
        $lines[] = "• {$emojiFinal} *Saldo Bancário Final Previsto:* R$ " . number_format($saldoFinalContaPrevisto, 2, ',', '.');
        $lines[] = "  _(Saldo Atual em Conta + Entradas a cair - Saídas a pagar)_";
        $lines[] = "";

        // CARTÃO DE CRÉDITO
        $lines[] = "💳 *CARTÃO DE CRÉDITO*";
        $lines[] = "💵 *Gastos à Vista por Categoria:*";
        if (empty($gastosAVistaPorCategoria)) {
            $lines[] = "  _Nenhuma compra à vista neste mês._";
        } else {
            foreach ($gastosAVistaPorCategoria as $cat => $val) {
                $lines[] = "  ▫️ {$cat}: R$ " . number_format($val, 2, ',', '.');
            }
        }
        $lines[] = "  *Total À Vista: R$ " . number_format($totalAVista, 2, ',', '.') . "*";
        $lines[] = "";

        // LIMITES / PREVISÕES DE CATEGORIAS
        $lines[] = "🎯 *Limite / Previsão das Categorias:*";
        if (empty($categoriasLimitesInfo)) {
            $lines[] = "  _Nenhum limite de categoria definido para o mês._";
        } else {
            foreach ($categoriasLimitesInfo as $lim) {
                $statusTxt = $lim['ultrapassou'] > 0
                    ? "⚠️ Estourou por R$ " . number_format($lim['ultrapassou'], 2, ',', '.')
                    : "Faltam R$ " . number_format($lim['falta'], 2, ',', '.') . " para o limite";
                $cartaoTxt = !empty($lim['cartao']) ? " ({$lim['cartao']})" : "";
                $lines[] = "  ▫️ *{$lim['categoria']}*{$cartaoTxt}: Gasto R$ " . number_format($lim['gasto'], 2, ',', '.') . " / Limite R$ " . number_format($lim['limite'], 2, ',', '.') . " → {$statusTxt}";
            }
        }
        $lines[] = "";

        // COMPRAS A PRAZO
        $lines[] = "📅 *Compras a Prazo (Fatura Atual):*";
        if (empty($comprasAPrazoLista)) {
            $lines[] = "  _Nenhuma compra parcelada nesta fatura._";
        } else {
            $count = count($comprasAPrazoLista);
            $exibir = array_slice($comprasAPrazoLista, 0, 10);
            foreach ($exibir as $cp) {
                $lines[] = "  ▫️ {$cp['descricao']} ({$cp['parcela']}) - R$ " . number_format($cp['valor'], 2, ',', '.');
            }
            if ($count > 10) {
                $resto = $count - 10;
                $lines[] = "  _... e mais {$resto} compras parceladas._";
            }
        }
        $lines[] = "  *Total a Prazo: R$ " . number_format($totalAPrazo, 2, ',', '.') . "*";
        $lines[] = "";

        // RESUMO GERAL DO CARTÃO
        $totalGeralCartao = $totalAVista + $totalAPrazo;
        $lines[] = "🧾 *Total Geral da Fatura:* R$ " . number_format($totalGeralCartao, 2, ',', '.');
        $lines[] = "";

        // DICAS PARA POUPAR
        $lines[] = "💡 *DICAS PARA POUPAR:*";
        foreach ($dicas as $dica) {
            $lines[] = "{$dica}";
        }

        if (!empty($pdfUrl)) {
            $lines[] = "";
            $lines[] = "📄 *RELATÓRIO DETALHADO EM PDF:*";
            $lines[] = "Acesse o extrato e compras completas da conta e cartões:";
            $lines[] = "👉 " . $pdfUrl;
        }

        return implode("\n", $lines);
    }
}
