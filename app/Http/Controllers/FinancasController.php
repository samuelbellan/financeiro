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

        return view('financas.index', compact(
            'transacoes', 'saldo', 'receitasMes', 'despesasMes', 'cartoes', 
            'previsoes', 'mes', 'ano', 'categorias', 'totalPrevistoReceita', 
            'totalPrevistoDespesa', 'faturasPorCartao', 'totalFaturas'
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
            
            TransacaoPrevisao::updateOrCreate(
                [
                    'user_id' => $userId,
                    'categoria' => $validated['categoria'],
                    'subcategoria' => $request->get('subcategoria'),
                    'mes' => $currentDate->month,
                    'ano' => $currentDate->year,
                    'tipo' => $validated['tipo'],
                ],
                [
                    'valor_previsto' => $validated['valor_previsto'],
                    'observacao' => $validated['observacao'] ?? null,
                ]
            );
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
