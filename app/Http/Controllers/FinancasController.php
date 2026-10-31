<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transacao;
use App\Models\Cartao;
use App\Models\TransacaoPrevisao;
use App\Models\Categoria;
use App\Models\CartaoParcela;
use App\Models\CartaoPrevisao;
use App\Models\CartaoCompra;
use App\Services\CategorySanitizer;
use App\Services\FinancialSummaryService;
use App\Services\TransactionSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancasController extends Controller
{
    /**
     * Display the financial control page.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        if (!$dataInicio || !$dataFim) {
            if ($request->has('period')) {
                $period = $request->get('period'); // YYYY-MM
                $parts = explode('-', $period);
                $ano = (int)$parts[0];
                $mes = (int)$parts[1];
            } else {
                $mes = $request->get('mes', now()->month);
                $ano = $request->get('ano', now()->year);
            }
            
            $dataInicio = \Carbon\Carbon::create($ano, $mes, 1)->startOfMonth()->toDateString();
            $dataFim = \Carbon\Carbon::create($ano, $mes, 1)->endOfMonth()->toDateString();
        } else {
            $carbonFim = \Carbon\Carbon::parse($dataFim);
            $mes = $carbonFim->month;
            $ano = $carbonFim->year;
        }

        $cartoes = Cartao::where('user_id', $user->id)->where('ativo', true)->get();
        $contas = \App\Models\Conta::where('user_id', $user->id)->where('ativo', true)->get();
        
        $categorias = Categoria::where('user_id', $user->id)
            ->with('subcategorias')
            ->orderBy('nome')
            ->get();
        
        $transacoes = Transacao::where('user_id', $user->id)
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->orderBy('data', 'desc')
            ->get();

        $saldo = Transacao::where('user_id', $user->id)
            ->selectRaw("SUM(CASE WHEN tipo = 'receita' THEN valor ELSE -valor END) as total")
            ->value('total') ?? 0;

        $receitasMes = Transacao::where('user_id', $user->id)
            ->where('tipo', 'receita')
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->sum('valor');

        $despesasMes = Transacao::where('user_id', $user->id)
            ->where('tipo', 'despesa')
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->sum('valor');

        // Previsões de transações (Contas de Casa)
        $previsoes = TransacaoPrevisao::where('user_id', $user->id)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->get();

        $totalPrevistoReceita = $previsoes->where('tipo', 'receita')->sum('valor_previsto');
        $totalPrevistoDespesa = $previsoes->where('tipo', 'despesa')->sum('valor_previsto');

        foreach ($previsoes as $p) {
            $consumoReal = $transacoes->filter(function($t) use ($p) {
                if ($p->tipo !== $t->tipo) return false;
                if (!CategorySanitizer::isMatch($p->categoria, $t->categoria)) return false;
                if ($p->subcategoria) {
                    return CategorySanitizer::isMatch($p->subcategoria, $t->subcategoria);
                } else {
                    return empty($t->subcategoria);
                }
            })->sum('valor');
            
            $p->consumo_real = $consumoReal;
            $p->restante = max(0, $p->valor_previsto - $consumoReal);
            $p->porcentagem = $p->valor_previsto > 0 ? min(100, ($consumoReal / $p->valor_previsto) * 100) : 0;
        }

        // Faturas de Cartão de Crédito (apenas cartões ativos)
        $faturas = CartaoParcela::whereHas('compra.cartao', function($query) use ($user) {
                $query->where('user_id', $user->id)->where('ativo', true);
            })
            ->whereBetween('data_vencimento', [$dataInicio, $dataFim])
            ->with('compra.cartao')
            ->get();

        $totalFaturas = $faturas->sum('valor_parcela');
        $faturasPorCartao = $faturas->groupBy('compra.cartao_id');

        // Cartões ativos do usuário
        $cartoesAtivos = Cartao::where('user_id', $user->id)->where('ativo', true)->get();

        // Previsões de categorias cadastradas nos cartões ativos para o mês
        $cartaoPrevisoes = CartaoPrevisao::whereIn('cartao_id', $cartoesAtivos->pluck('id'))
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->with('cartao')
            ->get();

        $totalCartaoPrevisoesRestante = 0.0;
        foreach ($cartaoPrevisoes as $cp) {
            $consumoReal = (float) $faturas->filter(function($fatura) use ($cp) {
                return $fatura->compra 
                    && $fatura->compra->cartao_id == $cp->cartao_id 
                    && CategorySanitizer::isMatch($fatura->compra->categoria, $cp->categoria);
            })->sum('valor_parcela');

            $cp->consumo_real = $consumoReal;
            $cp->restante = max(0, $cp->valor_previsto - $consumoReal);
            $cp->porcentagem = $cp->valor_previsto > 0 ? min(100, ($consumoReal / $cp->valor_previsto) * 100) : 0;
            $totalCartaoPrevisoesRestante += $cp->restante;
        }

        // Planejamento consolidado de cartões para a visualização na tabela do mês
        $cardsPlanejamento = [];
        foreach ($cartoesAtivos as $cartao) {
            $parcelasCartao = $faturasPorCartao->get($cartao->id, collect());
            $faturaValor = (float) $parcelasCartao->sum('valor_parcela');
            $previsoesDoCartao = $cartaoPrevisoes->where('cartao_id', $cartao->id);
            $limitePrevistoTotal = (float) $previsoesDoCartao->sum('valor_previsto');
            $margemRestante = (float) $previsoesDoCartao->sum('restante');

            if ($faturaValor > 0 || $limitePrevistoTotal > 0) {
                $totalPrevistoCartao = $faturaValor + $margemRestante;
                $consumoTotal = (float) $previsoesDoCartao->sum('consumo_real');
                $pct = $limitePrevistoTotal > 0 ? min(100, ($consumoTotal / $limitePrevistoTotal) * 100) : 100;

                $cardsPlanejamento[] = (object)[
                    'cartao' => $cartao,
                    'fatura_valor' => $faturaValor,
                    'limite_previsto' => $limitePrevistoTotal,
                    'margem_restante' => $margemRestante,
                    'total_previsto' => $totalPrevistoCartao,
                    'porcentagem' => $pct,
                    'previsoes' => $previsoesDoCartao,
                ];
            }
        }
        
        $totalPrevistoDespesa += $totalFaturas + $totalCartaoPrevisoesRestante;

        $baseConsolidado = $totalPrevistoReceita - $totalPrevistoDespesa;
        $imprevistos = 0;
        $listaImprevistos = [];

        foreach ($transacoes as $t) {
            if (empty($t->categoria)) continue;

            $matched = false;
            foreach ($previsoes as $p) {
                if ($p->tipo === $t->tipo && CategorySanitizer::isMatch($p->categoria, $t->categoria)) {
                    if ($p->subcategoria) {
                        if (CategorySanitizer::isMatch($p->subcategoria, $t->subcategoria)) {
                            $matched = true; break;
                        }
                    } else {
                        if (!$t->subcategoria || $t->subcategoria === '') {
                            $matched = true; break;
                        }
                    }
                }
            }

            if (!$matched) {
                $listaImprevistos[] = $t;
                if ($t->tipo === 'receita') {
                    $imprevistos += $t->valor;
                } else {
                    $imprevistos -= $t->valor;
                }
            }
        }

        $excesso = 0;
        $listaExcessos = [];
        foreach ($previsoes as $p) {
            if ($p->tipo === 'despesa' && $p->consumo_real > $p->valor_previsto) {
                $diff = $p->consumo_real - $p->valor_previsto;
                $excesso -= $diff;
                $listaExcessos[] = (object)[
                    'tipo' => 'despesa',
                    'categoria' => $p->categoria . ($p->subcategoria ? " - {$p->subcategoria}" : ''),
                    'valor' => $diff
                ];
            } elseif ($p->tipo === 'receita' && $p->consumo_real > $p->valor_previsto) {
                $diff = $p->consumo_real - $p->valor_previsto;
                $excesso += $diff;
                $listaExcessos[] = (object)[
                    'tipo' => 'receita',
                    'categoria' => $p->categoria . ($p->subcategoria ? " - {$p->subcategoria}" : ''),
                    'valor' => $diff
                ];
            }
        }

        $consolidadoMaisPrevisao = $baseConsolidado + $imprevistos + $excesso;

        // --- CÁLCULO CONSOLIDADO + PREVISÃO ANUAL ---
        $todasTransacoesAno = Transacao::where('user_id', $user->id)->whereYear('data', $ano)->get();
        $todasPrevisoesAno = TransacaoPrevisao::where('user_id', $user->id)->where('ano', $ano)->get();
        $todasFaturasAno = CartaoParcela::whereHas('compra.cartao', function($query) use ($user) {
                $query->where('user_id', $user->id)->where('ativo', true);
            })->whereYear('data_vencimento', $ano)->get();
        $todasCartaoPrevisoesAno = CartaoPrevisao::whereHas('cartao', function($query) use ($user) {
                $query->where('user_id', $user->id)->where('ativo', true);
            })->where('ano', $ano)->get();

        $consolidadoAnoMeses = [];
        $totalConsolidadoAno = 0;

        for ($m = 1; $m <= 12; $m++) {
            $transacoesMes = $todasTransacoesAno->filter(function($t) use ($m) {
                return \Carbon\Carbon::parse($t->data)->month == $m;
            });
            $previsoesMes = $todasPrevisoesAno->where('mes', $m);
            $faturasMes = $todasFaturasAno->filter(function($f) use ($m) {
                return \Carbon\Carbon::parse($f->data_vencimento)->month == $m;
            });

            $tPrevReceita = $previsoesMes->where('tipo', 'receita')->sum('valor_previsto');
            $tPrevDespesa = $previsoesMes->where('tipo', 'despesa')->sum('valor_previsto');

            foreach ($previsoesMes as $p) {
                $consumoReal = $transacoesMes->where('tipo', $p->tipo)
                    ->where('categoria', $p->categoria)
                    ->filter(function($t) use ($p) {
                        if ($p->subcategoria) {
                            return strcasecmp($t->subcategoria, $p->subcategoria) == 0;
                        } else {
                            return empty($t->subcategoria);
                        }
                    })->sum('valor');
                
                $p->temp_consumo_real = $consumoReal;
            }

            $tFaturas = $faturasMes->sum('valor_parcela');
            
            $cpMes = $todasCartaoPrevisoesAno->where('mes', $m);
            $restanteCartaoMes = 0;
            foreach ($cpMes as $cp) {
                $cReal = (float) $faturasMes->filter(function($fatura) use ($cp) {
                    return $fatura->compra 
                        && $fatura->compra->cartao_id == $cp->cartao_id 
                        && CategorySanitizer::isMatch($fatura->compra->categoria, $cp->categoria);
                })->sum('valor_parcela');
                $restanteCartaoMes += max(0, $cp->valor_previsto - $cReal);
            }

            $tPrevDespesa += $tFaturas + $restanteCartaoMes;

            $bConsolidado = $tPrevReceita - $tPrevDespesa;
            $imprev = 0;

            foreach ($transacoesMes as $t) {
                if (empty($t->categoria)) continue;

                $matched = false;
                foreach ($previsoesMes as $p) {
                    if ($p->tipo === $t->tipo && CategorySanitizer::isMatch($p->categoria, $t->categoria)) {
                        if ($p->subcategoria) {
                            if (CategorySanitizer::isMatch($p->subcategoria, $t->subcategoria)) {
                                $matched = true; break;
                            }
                        } else {
                            if (empty($t->subcategoria)) {
                                $matched = true; break;
                            }
                        }
                    }
                }

                if (!$matched) {
                    if ($t->tipo === 'receita') {
                        $imprev += $t->valor;
                    } else {
                        $imprev -= $t->valor;
                    }
                }
            }

            $exc = 0;
            foreach ($previsoesMes as $p) {
                if ($p->tipo === 'despesa' && $p->temp_consumo_real > $p->valor_previsto) {
                    $exc -= ($p->temp_consumo_real - $p->valor_previsto);
                } elseif ($p->tipo === 'receita' && $p->temp_consumo_real > $p->valor_previsto) {
                    $exc += ($p->temp_consumo_real - $p->valor_previsto);
                }
            }

            $cMes = $bConsolidado + $imprev + $exc;
            $consolidadoAnoMeses[$m] = $cMes;
            $totalConsolidadoAno += $cMes;
        }

        $start = \Carbon\Carbon::parse($dataInicio);
        $end = \Carbon\Carbon::parse($dataFim);
        $diffInDays = $start->diffInDays($end) + 1;

        $labelsDiarios = [];
        $datesMap = [];
        $dailyExpensesMap = [];
        for ($i = 0; $i < $diffInDays; $i++) {
            $dateObj = $start->copy()->addDays($i);
            $dateStr = $dateObj->toDateString();
            $labelStr = $dateObj->format('d/m');
            
            $labelsDiarios[] = $labelStr;
            $datesMap[] = $dateStr;
            $dailyExpensesMap[$dateStr] = 0;
        }

        $despesasPeriodo = Transacao::where('user_id', $user->id)
            ->where('tipo', 'despesa')
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->get();

        $despesasDetalhado = [];
        foreach ($despesasPeriodo as $t) {
            $dateStr = \Carbon\Carbon::parse($t->data)->toDateString();
            if (isset($dailyExpensesMap[$dateStr])) {
                $dailyExpensesMap[$dateStr] += (float)$t->valor;
            }
            $despesasDetalhado[] = [
                'data' => $dateStr,
                'descricao' => $t->descricao,
                'valor' => (float)$t->valor,
                'categoria' => $t->categoria ?: 'Sem Categoria',
                'subcategoria' => $t->subcategoria ?: ''
            ];
        }
        $despesasDiariasValues = array_values($dailyExpensesMap);

        $totalDespesasPeriodo = array_sum($despesasDiariasValues);
        $mediaDiariaDespesa = $diffInDays > 0 ? $totalDespesasPeriodo / $diffInDays : 0;

        $sugestoesDescricao = TransactionSuggestionService::getSuggestions($user->id, limit: 150);

        return view('financas.index', compact(
            'transacoes', 'saldo', 'receitasMes', 'despesasMes', 'cartoes', 
            'previsoes', 'mes', 'ano', 'categorias', 'totalPrevistoReceita', 
            'totalPrevistoDespesa', 'faturasPorCartao', 'totalFaturas',
            'consolidadoMaisPrevisao', 'listaImprevistos', 'listaExcessos',
            'consolidadoAnoMeses', 'totalConsolidadoAno', 'dataInicio', 'dataFim',
            'labelsDiarios', 'despesasDiariasValues', 'datesMap', 'despesasDetalhado', 'mediaDiariaDespesa',
            'sugestoesDescricao', 'cardsPlanejamento', 'totalCartaoPrevisoesRestante', 'contas'
        ));
    }

    /**
     * Retorna sugestões de lançamentos para autocomplete.
     */
    public function sugestoesDescricao(Request $request)
    {
        $user = Auth::user();
        $q = $request->get('q', '');
        $limit = min(50, max(1, (int)$request->get('limit', 15)));
        $sugestoes = TransactionSuggestionService::getSuggestions($user->id, $q, $limit);
        return response()->json($sugestoes);
    }

    public function storePrevisao(Request $request)
    {
        $validated = $request->validate([
            'categoria' => 'required|string|max:255',
            'tipo' => 'required|in:receita,despesa',
            'valor_previsto' => 'required|numeric|min:0',
            'mes' => 'required|integer|min:1|max:12',
            'ano' => 'required|integer',
            'repetir_meses' => 'nullable|integer|min:1|max:24',
            'observacao' => 'nullable|string|max:1000',
        ]);

        $userId = Auth::id();
        $mesInicial = $validated['mes'];
        $anoInicial = $validated['ano'];
        $repetir = $request->get('repetir_meses', 1);

        for ($i = 0; $i < $repetir; $i++) {
            $currentDate = \Carbon\Carbon::create($anoInicial, $mesInicial, 1)->addMonths($i);
            
            TransacaoPrevisao::create([
                'user_id' => $userId,
                'categoria' => $validated['categoria'],
                'subcategoria' => $request->get('subcategoria'),
                'mes' => $currentDate->month,
                'ano' => $currentDate->year,
                'tipo' => $validated['tipo'],
                'valor_previsto' => $validated['valor_previsto'],
                'observacao' => $validated['observacao'] ?? null,
            ]);
        }

        return redirect()->back()->with('success', 'Previsão(ões) salva(s) com sucesso!');
    }

    public function updatePrevisao(Request $request, TransacaoPrevisao $previsao)
    {
        if ($previsao->user_id !== Auth::id()) abort(403);

        $validated = $request->validate([
            'categoria' => 'required|string|max:255',
            'subcategoria' => 'nullable|string|max:255',
            'tipo' => 'required|in:receita,despesa',
            'valor_previsto' => 'required|numeric|min:0',
            'observacao' => 'nullable|string|max:1000',
        ]);

        $previsao->update($validated);

        return redirect()->back()->with('success', 'Previsão atualizada com sucesso!');
    }

    public function destroyPrevisao(TransacaoPrevisao $previsao)
    {
        if ($previsao->user_id !== Auth::id()) abort(403);
        $previsao->delete();
        return redirect()->back()->with('success', 'Previsão removida!');
    }

    /**
     * Store a new transaction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0',
            'tipo' => 'required|in:receita,despesa',
            'data' => 'required|date',
            'categoria' => 'nullable|string|max:255',
            'subcategoria' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = Auth::id();

        $transacao = Transacao::create($validated);

        if ($request->has('pagar_fatura') && $request->tipo === 'despesa') {
            $cartaoId = $request->input('cartao_id');
            $mesAnoFatura = $request->input('mes_ano_fatura'); // formato YYYY-MM

            if ($cartaoId && $mesAnoFatura) {
                list($anoFatura, $mesFatura) = explode('-', $mesAnoFatura);

                // Marcar como pagas as parcelas do cartão no mês/ano
                \App\Models\CartaoParcela::whereHas('compra', function($q) use ($cartaoId) {
                    $q->where('cartao_id', $cartaoId);
                })
                ->whereMonth('data_vencimento', $mesFatura)
                ->whereYear('data_vencimento', $anoFatura)
                ->update(['status' => 'paga']);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Transação adicionada com sucesso!',
                'transacao' => [
                    'descricao' => $transacao->descricao,
                    'valor' => (float)$transacao->valor,
                    'tipo' => $transacao->tipo,
                    'data' => $transacao->data,
                    'categoria' => $transacao->categoria,
                    'subcategoria' => $transacao->subcategoria,
                ]
            ]);
        }

        return redirect()->route('financas.index')->with('success', 'Transação adicionada com sucesso!');
    }

    /**
     * Update an existing transaction.
     */
    public function update(Request $request, Transacao $transacao)
    {
        if ($transacao->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0',
            'tipo' => 'required|in:receita,despesa',
            'data' => 'required|date',
            'categoria' => 'nullable|string|max:255',
            'subcategoria' => 'nullable|string|max:255',
        ]);

        $transacao->update($validated);

        return redirect()->route('financas.index')->with('success', 'Transação atualizada com sucesso!');
    }

    /**
     * Remove a transaction.
     */
    public function destroy(Transacao $transacao)
    {
        if ($transacao->user_id !== Auth::id()) {
            abort(403);
        }

        $transacao->delete();

        return redirect()->route('financas.index')->with('success', 'Transação excluída com sucesso!');
    }

    /**
     * Retorna o resumo financeiro formatado para envio via WhatsApp ou redireciona diretamente para o WhatsApp.
     */
    public function whatsappResumo(Request $request)
    {
        $user = Auth::user();
        $mes = (int) $request->get('mes', now()->month);
        $ano = (int) $request->get('ano', now()->year);
        $phone = $request->get('phone');

        $summary = FinancialSummaryService::generateSummary($user, $mes, $ano, $phone);

        if ($request->wantsJson()) {
            return response()->json($summary);
        }

        return redirect()->away($summary['whatsapp_url']);
    }

    /**
     * Importa extrato bancário em formato PDF, Excel (.xlsx), CSV ou OFX.
     */
    public function importarExtrato(Request $request, \App\Services\StatementImportService $service)
    {
        $request->validate([
            'extrato'  => 'required|file|max:25600', // até 25MB
            'conta_id' => 'nullable|exists:contas,id',
        ]);

        $user = Auth::user();
        $file = $request->file('extrato');
        $forcedContaId = $request->input('conta_id') ? (int)$request->input('conta_id') : null;

        $result = $service->import($file, $user->id, $forcedContaId);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result, $result['sucesso'] ? 200 : 422);
        }

        if ($result['sucesso']) {
            return redirect()->back()->with('success', $result['mensagem']);
        }

        return redirect()->back()->with('error', $result['mensagem']);
    }
}
