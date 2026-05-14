<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transacao;
use App\Models\Cartao;
use App\Models\TransacaoPrevisao;
use App\Models\Categoria;
use App\Models\CartaoParcela;
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
        
        $mes = $request->get('mes', now()->month);
        $ano = $request->get('ano', now()->year);

        $cartoes = Cartao::where('user_id', $user->id)->get();
        
        $categorias = Categoria::where('user_id', $user->id)
            ->with('subcategorias')
            ->orderBy('nome')
            ->get();
        
        $transacoes = Transacao::where('user_id', $user->id)
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->orderBy('data', 'desc')
            ->get();

        $saldo = Transacao::where('user_id', $user->id)
            ->selectRaw("SUM(CASE WHEN tipo = 'receita' THEN valor ELSE -valor END) as total")
            ->value('total') ?? 0;

        $receitasMes = Transacao::where('user_id', $user->id)
            ->where('tipo', 'receita')
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->sum('valor');

        $despesasMes = Transacao::where('user_id', $user->id)
            ->where('tipo', 'despesa')
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->sum('valor');

        // Previsões de transações (Contas de Casa)
        $previsoes = TransacaoPrevisao::where('user_id', $user->id)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->get();

        $totalPrevistoReceita = $previsoes->where('tipo', 'receita')->sum('valor_previsto');
        $totalPrevistoDespesa = $previsoes->where('tipo', 'despesa')->sum('valor_previsto');

        foreach ($previsoes as $p) {
            $queryConsumo = Transacao::where('user_id', $user->id)
                ->where('tipo', $p->tipo)
                ->where('categoria', 'like', $p->categoria)
                ->whereMonth('data', $mes)
                ->whereYear('data', $ano);

            // Se a previsão tem subcategoria, filtra por ela. 
            // Caso contrário, busca transações que também não tenham subcategoria (ou opcionalmente soma tudo)
            // Para seguir o desejo do usuário de precisão, vamos filtrar por subcategoria exata.
            if ($p->subcategoria) {
                $queryConsumo->where('subcategoria', 'like', $p->subcategoria);
            } else {
                $queryConsumo->where(function($q) {
                    $q->whereNull('subcategoria')->orWhere('subcategoria', '');
                });
            }

            $consumoReal = $queryConsumo->sum('valor');
            
            $p->consumo_real = $consumoReal;
            $p->restante = max(0, $p->valor_previsto - $consumoReal);
            $p->porcentagem = $p->valor_previsto > 0 ? min(100, ($consumoReal / $p->valor_previsto) * 100) : 0;
        }

        // Faturas de Cartão de Crédito
        $faturas = CartaoParcela::whereHas('compra.cartao', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereMonth('data_vencimento', $mes)
            ->whereYear('data_vencimento', $ano)
            ->with('compra.cartao')
            ->get();

        $totalFaturas = $faturas->sum('valor_parcela');
        $faturasPorCartao = $faturas->groupBy('compra.cartao_id');
        
        $totalPrevistoDespesa += $totalFaturas;

        $baseConsolidado = $totalPrevistoReceita - $totalPrevistoDespesa;
        $imprevistos = 0;
        $listaImprevistos = [];

        foreach ($transacoes as $t) {
            if (empty($t->categoria)) continue;

            $matched = false;
            foreach ($previsoes as $p) {
                if ($p->tipo === $t->tipo && $p->categoria === $t->categoria) {
                    if ($p->subcategoria) {
                        if ($p->subcategoria === $t->subcategoria) {
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
                $query->where('user_id', $user->id);
            })->whereYear('data_vencimento', $ano)->get();

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
            $tPrevDespesa += $tFaturas;

            $bConsolidado = $tPrevReceita - $tPrevDespesa;
            $imprev = 0;

            foreach ($transacoesMes as $t) {
                if (empty($t->categoria)) continue;

                $matched = false;
                foreach ($previsoesMes as $p) {
                    if ($p->tipo === $t->tipo && strcasecmp($p->categoria, $t->categoria) == 0) {
                        if ($p->subcategoria) {
                            if (strcasecmp($p->subcategoria, $t->subcategoria) == 0) {
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

        return view('financas.index', compact(
            'transacoes', 'saldo', 'receitasMes', 'despesasMes', 'cartoes', 
            'previsoes', 'mes', 'ano', 'categorias', 'totalPrevistoReceita', 
            'totalPrevistoDespesa', 'faturasPorCartao', 'totalFaturas',
            'consolidadoMaisPrevisao', 'listaImprevistos', 'listaExcessos',
            'consolidadoAnoMeses', 'totalConsolidadoAno'
        ));
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

        Transacao::create($validated);

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
}
