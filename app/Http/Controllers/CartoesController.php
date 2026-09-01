<?php

namespace App\Http\Controllers;

use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoParcela;
use App\Models\CartaoPrevisao;
use App\Models\Categoria;
use App\Services\CategorySanitizer;
use App\Services\CreditCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CartoesController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $cartoes = Cartao::where('user_id', $user->id)->get();
        
        $currentMonth = $request->get('mes', now()->month);
        $currentYear = $request->get('ano', now()->year);

        // Parcelas do período selecionado (apenas cartões ativos)
        $faturas = CartaoParcela::whereHas('compra.cartao', function($query) use ($user) {
                $query->where('user_id', $user->id)->where('ativo', true);
            })
            ->whereMonth('data_vencimento', $currentMonth)
            ->whereYear('data_vencimento', $currentYear)
            ->with('compra.cartao')
            ->get();

        // Agrupar parcelas por cartão
        $faturasPorCartao = $faturas->groupBy('compra.cartao_id');

        $userCategorias = Categoria::where('user_id', $user->id)->pluck('nome')->toArray();

        // Análise de gastos por categoria (sanitizado e agrupado por nome canônico)
        $gastosPorCategoria = $faturas->groupBy(function($fatura) use ($userCategorias) {
            return CategorySanitizer::sanitize($fatura->compra->categoria, $userCategorias);
        })->map(function($parcelas) {
            return $parcelas->sum('valor_parcela');
        });

        // Previsões do período selecionado (apenas cartões ativos)
        $previsoes = CartaoPrevisao::whereHas('cartao', function($query) use ($user) {
                $query->where('user_id', $user->id)->where('ativo', true);
            })
            ->where('mes', $currentMonth)
            ->where('ano', $currentYear)
            ->with('cartao')
            ->get();

        // Buscar todas as compras do mês dos cartões ativos para bater com as previsões (sanitizado)
        $comprasMes = CartaoCompra::whereHas('cartao', function($q) use ($user) {
                $q->where('user_id', $user->id)->where('ativo', true);
            })
            ->whereMonth('data_compra', $currentMonth)
            ->whereYear('data_compra', $currentYear)
            ->get();

        // Calcular consumo real para cada previsão
        foreach ($previsoes as $p) {
            $consumoReal = $comprasMes->filter(function($compra) use ($p) {
                return $compra->cartao_id == $p->cartao_id && CategorySanitizer::isMatch($compra->categoria, $p->categoria);
            })->sum('valor_total');
            
            $p->consumo_real = $consumoReal;
            $p->restante = max(0, $p->valor_previsto - $consumoReal);
            $p->porcentagem = $p->valor_previsto > 0 ? min(100, ($consumoReal / $p->valor_previsto) * 100) : 0;
        }

        // Gastos do período para os gráficos (agrupados por categoria sanitizada)
        $gastosChartAVista = [];
        $gastosChartAPrazo = [];
        
        $diasNoMes = \Carbon\Carbon::create($currentYear, $currentMonth, 1)->daysInMonth;
        // Inicializar todos os dias com zero
        $gastosDiariosAVista = array_fill(1, $diasNoMes, 0);

        foreach ($faturas as $fatura) {
            $compra = $fatura->compra;
            $cat = CategorySanitizer::sanitize($compra->categoria, $userCategorias);
            $isAVista = in_array($compra->tipo, ['avista', 'recorrente']);
            
            if ($isAVista) {
                if (!isset($gastosChartAVista[$cat])) {
                    $gastosChartAVista[$cat] = 0;
                }
                $gastosChartAVista[$cat] += abs($fatura->valor_parcela);
                
                // Extrair o dia da compra para a análise diária
                $diaCompra = \Carbon\Carbon::parse($compra->data_compra)->day;
                if ($diaCompra >= 1 && $diaCompra <= $diasNoMes) {
                    $gastosDiariosAVista[$diaCompra] += abs($fatura->valor_parcela);
                }
            } else {
                if (!isset($gastosChartAPrazo[$cat])) {
                    $gastosChartAPrazo[$cat] = 0;
                }
                $gastosChartAPrazo[$cat] += abs($fatura->valor_parcela);
            }
        }

        $totalAVista = array_sum($gastosDiariosAVista);
        $mediaDiaria = $diasNoMes > 0 ? $totalAVista / $diasNoMes : 0;

        $comprasAVistaDetalhado = [];
        foreach ($faturas as $fatura) {
            $compra = $fatura->compra;
            $isAVista = in_array($compra->tipo, ['avista', 'recorrente']);
            if ($isAVista) {
                $diaCompra = \Carbon\Carbon::parse($compra->data_compra)->day;
                $comprasAVistaDetalhado[] = [
                    'dia' => $diaCompra,
                    'descricao' => $compra->descricao,
                    'valor' => (float)$fatura->valor_parcela,
                    'cartao' => $compra->cartao->nome,
                    'categoria' => CategorySanitizer::sanitize($compra->categoria, $userCategorias),
                    'tipo' => $compra->tipo
                ];
            }
        }

        return view('financas.cartoes', compact(
            'cartoes', 'faturasPorCartao', 'previsoes', 'gastosPorCategoria', 
            'currentMonth', 'currentYear', 'gastosChartAVista', 'gastosChartAPrazo',
            'gastosDiariosAVista', 'mediaDiaria', 'diasNoMes', 'comprasAVistaDetalhado'
        ));
    }

    public function storePrevisao(Request $request)
    {
        $validated = $request->validate([
            'cartao_id' => 'required|exists:cartoes,id',
            'categoria' => 'required|string|max:255',
            'valor_previsto' => 'required|numeric|min:0',
        ]);

        $validated['mes'] = now()->month;
        $validated['ano'] = now()->year;

        CartaoPrevisao::updateOrCreate(
            [
                'cartao_id' => $validated['cartao_id'],
                'categoria' => $validated['categoria'],
                'mes' => $validated['mes'],
                'ano' => $validated['ano'],
            ],
            ['valor_previsto' => $validated['valor_previsto']]
        );

        return redirect()->back()->with('success', 'Previsão atualizada com sucesso!');
    }

    public function updatePrevisao(Request $request, CartaoPrevisao $previsao)
    {
        if ($previsao->cartao->user_id !== Auth::id()) abort(403);

        $validated = $request->validate([
            'categoria' => 'required|string|max:255',
            'valor_previsto' => 'required|numeric|min:0',
        ]);

        $previsao->update($validated);

        return redirect()->back()->with('success', 'Previsão de cartão atualizada!');
    }

    public function destroyPrevisao(CartaoPrevisao $previsao)
    {
        if ($previsao->cartao->user_id !== Auth::id()) abort(403);
        $previsao->delete();
        return redirect()->back()->with('success', 'Previsão removida!');
    }

    public function store(Request $request)
    {
        if ($request->has('limite')) {
            $limite = str_replace(['R$', ' ', '.'], '', $request->input('limite'));
            $limite = str_replace(',', '.', $limite);
            $request->merge(['limite' => $limite]);
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'cor' => 'required|string|max:7',
            'limite' => 'required|numeric|min:0',
            'bandeira' => 'nullable|string|max:50',
            'dia_fechamento' => 'required|integer|min:1|max:31',
            'dia_vencimento' => 'required|integer|min:1|max:31',
        ]);

        $validated['user_id'] = Auth::id();
        
        try {
            Cartao::create($validated);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['db_error' => $e->getMessage()])->withInput();
        }

        return redirect()->back()->with('success', 'Cartão adicionado com sucesso!');
    }

    public function storeCompra(Request $request)
    {
        $validated = $request->validate([
            'cartao_id' => 'required|exists:cartoes,id',
            'descricao' => 'required|string|max:255',
            'valor_total' => 'required|numeric',
            'tipo' => 'required|in:avista,parcelada,recorrente',
            'numero_parcelas' => 'required_if:tipo,parcelada|integer|min:1',
            'data_compra' => 'required|date',
            'categoria' => 'nullable|string|max:255',
        ]);

        if ($request->has('is_estorno') && $request->is_estorno) {
            $validated['valor_total'] = -abs($validated['valor_total']);
        } else {
            $validated['valor_total'] = abs($validated['valor_total']);
        }

        $compra = CartaoCompra::create($validated);
        $this->gerarParcelas($compra);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Compra registrada com sucesso!',
                'compra' => [
                    'descricao' => $compra->descricao,
                    'valor_total' => (float)$compra->valor_total,
                    'tipo' => $compra->tipo,
                    'data_compra' => $compra->data_compra,
                    'categoria' => $compra->categoria,
                ]
            ]);
        }

        return redirect()->back()->with('success', 'Compra registrada com sucesso!');
    }

    public function updateCompra(Request $request, CartaoCompra $compra)
    {
        if ($compra->cartao->user_id !== Auth::id()) abort(403);

        $validated = $request->validate([
            'cartao_id' => 'required|exists:cartoes,id',
            'descricao' => 'required|string|max:255',
            'valor_total' => 'required|numeric',
            'tipo' => 'required|in:avista,parcelada,recorrente',
            'numero_parcelas' => 'required_if:tipo,parcelada|integer|min:1',
            'data_compra' => 'required|date',
            'categoria' => 'nullable|string|max:255',
        ]);

        if ($request->has('is_estorno') && $request->is_estorno) {
            $validated['valor_total'] = -abs($validated['valor_total']);
        } else {
            $validated['valor_total'] = abs($validated['valor_total']);
        }

        $compra->update($validated);
        
        // Remover parcelas antigas e gerar novas
        $compra->parcelas()->delete();
        $this->gerarParcelas($compra);

        return redirect()->back()->with('success', 'Compra atualizada com sucesso!');
    }

    public function destroyCompra(CartaoCompra $compra)
    {
        if ($compra->cartao->user_id !== Auth::id()) abort(403);
        $compra->delete(); // Cascade delete handles parcelas
        return redirect()->back()->with('success', 'Compra excluída!');
    }

    protected function gerarParcelas(CartaoCompra $compra)
    {
        $numParcelas = $compra->tipo === 'parcelada' ? $compra->numero_parcelas : 1;
        $valorParcela = $compra->valor_total / $numParcelas;
        $dataCompra = Carbon::parse($compra->data_compra);
        $cartao = $compra->cartao;

        for ($i = 1; $i <= $numParcelas; $i++) {
            $vencimento = CreditCardService::calcularVencimentoParcela($cartao, $dataCompra, $i);
            
            CartaoParcela::create([
                'cartao_compra_id' => $compra->id,
                'numero_parcela' => $i,
                'valor_parcela' => $valorParcela,
                'data_vencimento' => $vencimento,
                'status' => 'aberta',
            ]);

            if ($compra->tipo === 'recorrente' && $i === 1) {
                for ($j = 2; $j <= 12; $j++) {
                    $vencRecorrente = CreditCardService::calcularVencimentoParcela($cartao, $dataCompra->copy()->addMonths($j - 1), 1);
                    CartaoParcela::create([
                        'cartao_compra_id' => $compra->id,
                        'numero_parcela' => $j,
                        'valor_parcela' => $valorParcela,
                        'data_vencimento' => $vencRecorrente,
                        'status' => 'aberta',
                    ]);
                }
            }
        }
    }

    public function update(Request $request, Cartao $cartao)
    {
        if ($cartao->user_id !== Auth::id()) abort(403);

        if ($request->has('limite')) {
            $limite = str_replace(['R$', ' ', '.'], '', $request->input('limite'));
            $limite = str_replace(',', '.', $limite);
            $request->merge(['limite' => $limite]);
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'cor' => 'required|string|max:7',
            'limite' => 'required|numeric|min:0',
            'bandeira' => 'nullable|string|max:50',
            'dia_fechamento' => 'required|integer|min:1|max:31',
            'dia_vencimento' => 'required|integer|min:1|max:31',
        ]);

        $datesChanged = ($cartao->dia_fechamento != $validated['dia_fechamento'] || $cartao->dia_vencimento != $validated['dia_vencimento']);
        $cartao->update($validated);

        $recalculadas = 0;
        if ($datesChanged || $request->has('recalcular_parcelas')) {
            $recalculadas = CreditCardService::recalcularParcelasCartao($cartao, true);
        }

        $msg = 'Cartão atualizado com sucesso!';
        if ($recalculadas > 0) {
            $msg .= " ({$recalculadas} parcelas abertas tiveram vencimento recalculado).";
        }

        return redirect()->back()->with('success', $msg);
    }

    public function recalcular(Request $request, Cartao $cartao)
    {
        if ($cartao->user_id !== Auth::id()) abort(403);

        $count = CreditCardService::recalcularParcelasCartao($cartao, true);
        return redirect()->back()->with('success', "Faturas e parcelas recalculadas com sucesso! ({$count} parcelas ajustadas).");
    }

    public function updateParcela(Request $request, CartaoParcela $parcela)
    {
        if ($parcela->compra->cartao->user_id !== Auth::id()) abort(403);

        $validated = $request->validate([
            'data_vencimento' => 'required|date',
            'valor_parcela' => 'nullable|numeric',
        ]);

        $parcela->update($validated);

        return redirect()->back()->with('success', 'Vencimento da parcela flexibilizado/atualizado com sucesso!');
    }

    public function destroy(Cartao $cartao)
    {
        if ($cartao->user_id !== Auth::id()) abort(403);
        $cartao->delete();
        return redirect()->back()->with('success', 'Cartão excluído!');
    }

    public function toggleStatus(Cartao $cartao)
    {
        if ($cartao->user_id !== Auth::id()) abort(403);
        
        $cartao->ativo = !$cartao->ativo;
        $cartao->save();

        $msg = $cartao->ativo ? "Cartão \"{$cartao->nome}\" habilitado com sucesso!" : "Cartão \"{$cartao->nome}\" desabilitado com sucesso!";
        return redirect()->back()->with('success', $msg);
    }
}
