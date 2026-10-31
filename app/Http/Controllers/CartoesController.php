<?php

namespace App\Http\Controllers;

use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoFatura;
use App\Models\CartaoParcela;
use App\Models\CartaoPrevisao;
use App\Models\Categoria;
use App\Services\CategorySanitizer;
use App\Services\CreditCardReconciliationService;
use App\Services\CreditCardService;
use App\Services\TransactionSuggestionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CartoesController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $cartoes = Cartao::where('user_id', $user->id)->get();
        
        $currentMonth = $request->get('mes', now()->month);
        $currentYear = $request->get('ano', now()->year);

        foreach ($cartoes as $c) {
            $c->is_fatura_fechada = CreditCardService::isFaturaFechada($c, (int)$currentMonth, (int)$currentYear);
            $c->data_fechamento_fatura = CreditCardService::getDataFechamentoFatura($c, (int)$currentMonth, (int)$currentYear);
        }

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

        // Calcular consumo real para cada previsão a partir das faturas/parcelas do mês
        foreach ($previsoes as $p) {
            $consumoReal = (float) $faturas->filter(function($fatura) use ($p) {
                return $fatura->compra 
                    && $fatura->compra->cartao_id == $p->cartao_id 
                    && CategorySanitizer::isMatch($fatura->compra->categoria, $p->categoria);
            })->sum('valor_parcela');
            
            $cartaoDaPrevisao = $cartoes->firstWhere('id', $p->cartao_id);
            $isCartaoFechado = $cartaoDaPrevisao ? CreditCardService::isFaturaFechada($cartaoDaPrevisao, (int)$currentMonth, (int)$currentYear) : false;

            $p->is_fechada = $isCartaoFechado;
            $p->consumo_real = $consumoReal;
            // Se fatura está fechada, nenhuma compra a mais pode entrar: restante é zero
            $p->restante = $isCartaoFechado ? 0.0 : max(0, $p->valor_previsto - $consumoReal);
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

        $sugestoesDescricao = TransactionSuggestionService::getSuggestions($user->id, limit: 150);

        $faturasSalvas = CartaoFatura::where('user_id', $user->id)
            ->with('cartao')
            ->orderByDesc('created_at')
            ->get();

        return view('financas.cartoes', compact(
            'cartoes', 'faturasPorCartao', 'previsoes', 'gastosPorCategoria', 
            'currentMonth', 'currentYear', 'gastosChartAVista', 'gastosChartAPrazo',
            'gastosDiariosAVista', 'mediaDiaria', 'diasNoMes', 'comprasAVistaDetalhado',
            'sugestoesDescricao', 'userCategorias', 'faturasSalvas'
        ));
    }

    public function storePrevisao(Request $request)
    {
        if ($request->has('valor_previsto')) {
            $valorPrevisto = $this->parseCurrencyValue($request->input('valor_previsto'));
            $request->merge(['valor_previsto' => $valorPrevisto]);
        }

        $validated = $request->validate([
            'cartao_id' => 'required|exists:cartoes,id',
            'categoria' => 'required|string|max:255',
            'valor_previsto' => 'required|numeric|min:0',
            'mes' => 'nullable|integer|min:1|max:12',
            'ano' => 'nullable|integer|min:2000|max:2100',
        ]);

        $cartao = Cartao::where('user_id', Auth::id())->findOrFail($validated['cartao_id']);

        $mes = $validated['mes'] ?? now()->month;
        $ano = $validated['ano'] ?? now()->year;

        CartaoPrevisao::updateOrCreate(
            [
                'cartao_id' => $cartao->id,
                'categoria' => $validated['categoria'],
                'mes' => $mes,
                'ano' => $ano,
            ],
            ['valor_previsto' => $validated['valor_previsto']]
        );

        return redirect()->back()->with('success', 'Previsão de gastos do cartão atualizada com sucesso!');
    }

    public function updatePrevisao(Request $request, CartaoPrevisao $previsao)
    {
        if ($previsao->cartao->user_id !== Auth::id()) abort(403);

        if ($request->has('valor_previsto')) {
            $valorPrevisto = $this->parseCurrencyValue($request->input('valor_previsto'));
            $request->merge(['valor_previsto' => $valorPrevisto]);
        }

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
        // Se for compra parcelada e o usuário informou valor_parcela em vez de valor_total
        if ($request->tipo === 'parcelada' && $request->filled('valor_parcela') && (int)$request->numero_parcelas > 0) {
            if (!$request->filled('valor_total') || (float)$request->valor_total == 0) {
                $valorParcela = $this->parseCurrencyValue($request->valor_parcela);
                $numParcelas = (int)$request->numero_parcelas;
                $request->merge([
                    'valor_total' => round($valorParcela * $numParcelas, 2),
                ]);
            }
        }

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

        if ($request->tipo === 'parcelada' && $request->filled('valor_parcela') && (int)$request->numero_parcelas > 0) {
            if (!$request->filled('valor_total') || (float)$request->valor_total == 0) {
                $valorParcela = $this->parseCurrencyValue($request->valor_parcela);
                $numParcelas = (int)$request->numero_parcelas;
                $request->merge([
                    'valor_total' => round($valorParcela * $numParcelas, 2),
                ]);
            }
        }

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
        $numParcelas = $compra->tipo === 'parcelada' ? (int)$compra->numero_parcelas : 1;
        $valorTotal = (float)$compra->valor_total;
        $valorBase = round($valorTotal / $numParcelas, 2);
        $diferencaCentavos = round($valorTotal - ($valorBase * $numParcelas), 2);
        $dataCompra = Carbon::parse($compra->data_compra);
        $cartao = $compra->cartao;

        for ($i = 1; $i <= $numParcelas; $i++) {
            $vencimento = CreditCardService::calcularVencimentoParcela($cartao, $dataCompra, $i);
            $valorAtual = $valorBase;
            // Ajusta centavo excedente/faltante na primeira parcela
            if ($i === 1) {
                $valorAtual = round($valorAtual + $diferencaCentavos, 2);
            }
            
            CartaoParcela::create([
                'cartao_compra_id' => $compra->id,
                'numero_parcela' => $i,
                'valor_parcela' => $valorAtual,
                'data_vencimento' => $vencimento,
                'status' => 'aberta',
            ]);

            if ($compra->tipo === 'recorrente' && $i === 1) {
                for ($j = 2; $j <= 12; $j++) {
                    $vencRecorrente = CreditCardService::calcularVencimentoParcela($cartao, $dataCompra->copy()->addMonths($j - 1), 1);
                    CartaoParcela::create([
                        'cartao_compra_id' => $compra->id,
                        'numero_parcela' => $j,
                        'valor_parcela' => $valorAtual,
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
        if ($cartao->user_id !== Auth::id()) {
            abort(403);
        }

        if ($cartao->ativo) {
            return redirect()->back()->with('error', "O cartão \"{$cartao->nome}\" precisa ser desabilitado antes de ser excluído.");
        }

        $nome = $cartao->nome;
        $cartao->delete();

        return redirect()->back()->with('success', "Cartão \"{$nome}\" excluído com sucesso!");
    }

    public function toggleStatus(Cartao $cartao)
    {
        if ($cartao->user_id !== Auth::id()) abort(403);
        
        $cartao->ativo = !$cartao->ativo;
        $cartao->save();

        $msg = $cartao->ativo ? "Cartão \"{$cartao->nome}\" habilitado com sucesso!" : "Cartão \"{$cartao->nome}\" desabilitado com sucesso!";
        return redirect()->back()->with('success', $msg);
    }

    /**
     * Normaliza valores monetários em formatos numéricos, float ou strings formatadas brasileiras (ex: 119,90 ou 1.199,90).
     */
    protected function parseCurrencyValue(mixed $val): float
    {
        if (is_numeric($val)) {
            return (float)$val;
        }

        $val = trim((string)$val);
        if (str_contains($val, ',')) {
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
        }

        return (float)$val;
    }

    /**
     * Processa o upload da fatura fechada e gera o relatório comparativo de conciliação.
     */
    public function conciliarFatura(Request $request, CreditCardReconciliationService $service)
    {
        $request->validate([
            'arquivo'   => 'required|file|max:30720', // até 30MB
            'cartao_id' => 'required|exists:cartoes,id',
            'mes'       => 'required|integer|between:1,12',
            'ano'       => 'required|integer|min:2020|max:2050',
        ]);

        $cartao = Cartao::where('user_id', Auth::id())->findOrFail($request->cartao_id);
        $file = $request->file('arquivo');

        $resultado = $service->reconcile(
            file: $file,
            cartao: $cartao,
            mes: (int) $request->mes,
            ano: (int) $request->ano
        );

        if (!$resultado['sucesso']) {
            return response()->json($resultado, 422);
        }

        // Armazenar arquivo e registrar no histórico de faturas salvas
        $path = $file->store('faturas', 'local');
        $pendenciasCount = count($resultado['grupos']['faltando_no_sistema'] ?? []) +
                           count($resultado['grupos']['sobrando_no_sistema'] ?? []) +
                           count($resultado['grupos']['divergencias_valor'] ?? []);
        $status = ($pendenciasCount === 0 && abs($resultado['totais']['diferenca'] ?? 0) < 0.01) ? 'conciliada' : 'pendente';

        $cartaoFatura = CartaoFatura::create([
            'user_id'               => Auth::id(),
            'cartao_id'             => $cartao->id,
            'mes'                   => (int) $request->mes,
            'ano'                   => (int) $request->ano,
            'arquivo_path'          => $path,
            'arquivo_nome_original' => $file->getClientOriginalName(),
            'total_fatura'          => $resultado['totais']['fatura_despesas'] ?? 0,
            'total_despesas'        => $resultado['totais']['fatura_despesas'] ?? 0,
            'total_pagamentos'      => $resultado['totais']['fatura_pagamentos'] ?? 0,
            'itens_count'           => $resultado['contadores']['total_fatura'] ?? 0,
            'itens_json'            => $resultado['itens_extraidos'] ?? [],
            'status'                => $status,
            'diferenca_total'       => $resultado['totais']['diferenca'] ?? 0,
            'pendencias_count'      => $pendenciasCount,
        ]);

        $resultado['fatura_id'] = $cartaoFatura->id;

        // Salvar na sessão para permitir download imediato do PDF
        session(['ultima_conciliacao_fatura' => $resultado]);

        return response()->json($resultado);
    }

    /**
     * Reexecuta a auditoria de uma fatura salva contra o banco de dados atual.
     */
    public function reconciliarFaturaSalva(CartaoFatura $fatura, CreditCardReconciliationService $service)
    {
        if ($fatura->user_id !== Auth::id()) {
            abort(403);
        }

        $cartao = $fatura->cartao;
        if (!$cartao) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Cartão associado a esta fatura não foi encontrado.',
            ], 404);
        }

        $itens = $fatura->itens_json ?? [];
        if (empty($itens)) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Esta fatura arquivada não possui lançamentos armazenados.',
            ], 422);
        }

        $resultado = $service->reconcileItems(
            faturaItens: $itens,
            cartao: $cartao,
            mes: $fatura->mes,
            ano: $fatura->ano
        );

        if (!$resultado['sucesso']) {
            return response()->json($resultado, 422);
        }

        $pendenciasCount = count($resultado['grupos']['faltando_no_sistema'] ?? []) +
                           count($resultado['grupos']['sobrando_no_sistema'] ?? []) +
                           count($resultado['grupos']['divergencias_valor'] ?? []);
        $status = ($pendenciasCount === 0 && abs($resultado['totais']['diferenca'] ?? 0) < 0.01) ? 'conciliada' : 'pendente';

        $fatura->update([
            'status'           => $status,
            'diferenca_total'  => $resultado['totais']['diferenca'] ?? 0,
            'pendencias_count' => $pendenciasCount,
        ]);

        $resultado['fatura_id'] = $fatura->id;
        session(['ultima_conciliacao_fatura' => $resultado]);

        return response()->json($resultado);
    }

    /**
     * Download do arquivo original da fatura arquivada.
     */
    public function downloadFaturaArquivo(CartaoFatura $fatura)
    {
        if ($fatura->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$fatura->arquivo_path || !Storage::disk('local')->exists($fatura->arquivo_path)) {
            return redirect()->back()->with('error', 'Arquivo da fatura não encontrado no armazenamento.');
        }

        return Storage::disk('local')->download($fatura->arquivo_path, $fatura->arquivo_nome_original ?? 'fatura.pdf');
    }

    /**
     * Remove uma fatura salva do histórico.
     */
    public function destroyFatura(CartaoFatura $fatura)
    {
        if ($fatura->user_id !== Auth::id()) {
            abort(403);
        }

        if ($fatura->arquivo_path && Storage::disk('local')->exists($fatura->arquivo_path)) {
            Storage::disk('local')->delete($fatura->arquivo_path);
        }

        $fatura->delete();

        if (request()->wantsJson()) {
            return response()->json(['sucesso' => true, 'mensagem' => 'Fatura removida do histórico com sucesso.']);
        }

        return redirect()->back()->with('success', 'Fatura removida do histórico com sucesso.');
    }

    /**
     * Lança uma compra faltante identificada na conciliação da fatura via AJAX.
     */
    public function lancarItemFaltante(Request $request)
    {
        $validated = $request->validate([
            'cartao_id'       => 'required|exists:cartoes,id',
            'descricao'       => 'required|string|max:255',
            'valor_total'     => 'required|numeric',
            'data_compra'     => 'required|date',
            'categoria'       => 'nullable|string|max:255',
            'tipo'            => 'nullable|in:avista,parcelada,recorrente',
            'numero_parcelas' => 'nullable|integer|min:1',
            'fatura_id'       => 'nullable|exists:cartao_faturas,id',
        ]);

        $cartao = Cartao::where('user_id', Auth::id())->findOrFail($validated['cartao_id']);

        $tipo = $validated['tipo'] ?? 'avista';
        $numParcelas = (int) ($validated['numero_parcelas'] ?? 1);
        if ($tipo === 'avista' && $numParcelas > 1) {
            $tipo = 'parcelada';
        }

        $compra = CartaoCompra::create([
            'cartao_id'       => $cartao->id,
            'descricao'       => $validated['descricao'],
            'valor_total'     => abs((float) $validated['valor_total']),
            'data_compra'     => $validated['data_compra'],
            'categoria'       => $validated['categoria'] ?: 'Geral',
            'tipo'            => $tipo,
            'numero_parcelas' => $tipo === 'parcelada' ? $numParcelas : 1,
        ]);

        $this->gerarParcelas($compra);

        // Se veio vinculado a uma fatura salva, atualizar status
        $faturaAtualizada = null;
        if (!empty($validated['fatura_id'])) {
            $fatura = CartaoFatura::where('user_id', Auth::id())->find($validated['fatura_id']);
            if ($fatura) {
                // Alinha o vencimento da 1ª parcela ao mês e ano da fatura que a cobrou
                $primeiraParcela = $compra->parcelas()->first();
                if ($primeiraParcela) {
                    $diaVenc = min((int)($cartao->dia_vencimento ?: 10), 28);
                    $vencEsperado = Carbon::create($fatura->ano, $fatura->mes, $diaVenc)->format('Y-m-d');
                    if ($primeiraParcela->data_vencimento !== $vencEsperado) {
                        $primeiraParcela->update(['data_vencimento' => $vencEsperado]);
                    }
                }

                $service = app(CreditCardReconciliationService::class);
                $res = $service->reconcileItems($fatura->itens_json ?? [], $cartao, $fatura->mes, $fatura->ano);
                $pendenciasCount = count($res['grupos']['faltando_no_sistema'] ?? []) +
                                   count($res['grupos']['sobrando_no_sistema'] ?? []) +
                                   count($res['grupos']['divergencias_valor'] ?? []);
                $status = ($pendenciasCount === 0 && abs($res['totais']['diferenca'] ?? 0) < 0.01) ? 'conciliada' : 'pendente';
                $fatura->update([
                    'status'           => $status,
                    'diferenca_total'  => $res['totais']['diferenca'] ?? 0,
                    'pendencias_count' => $pendenciasCount,
                ]);
                $faturaAtualizada = [
                    'id'               => $fatura->id,
                    'status'           => $fatura->status,
                    'pendencias_count' => $fatura->pendencias_count,
                    'diferenca_total'  => (float) $fatura->diferenca_total,
                ];
            }
        }

        return response()->json([
            'sucesso'  => true,
            'mensagem' => 'Lançamento realizado com sucesso!',
            'compra'   => [
                'id'          => $compra->id,
                'descricao'   => $compra->descricao,
                'valor_total' => (float) $compra->valor_total,
                'data_compra' => $compra->data_compra,
                'categoria'   => $compra->categoria,
            ],
            'fatura_atualizada' => $faturaAtualizada,
        ]);
    }

    /**
     * Ajusta centavos ou cria novo lançamento de ajuste de diferença via AJAX.
     */
    public function ajustarCentavos(Request $request)
    {
        $validated = $request->validate([
            'tipo_ajuste' => 'required|in:ajustar_parcela,novo_lancamento',
            'fatura_id'   => 'nullable|exists:cartao_faturas,id',
            // Para ajustar_parcela:
            'parcela_id'  => 'required_if:tipo_ajuste,ajustar_parcela|nullable|exists:cartao_parcelas,id',
            'novo_valor'  => 'required_if:tipo_ajuste,ajustar_parcela|nullable|numeric',
            // Para novo_lancamento:
            'cartao_id'   => 'required_if:tipo_ajuste,novo_lancamento|nullable|exists:cartoes,id',
            'descricao'   => 'required_if:tipo_ajuste,novo_lancamento|nullable|string|max:255',
            'valor'       => 'required_if:tipo_ajuste,novo_lancamento|nullable|numeric',
            'data_compra' => 'required_if:tipo_ajuste,novo_lancamento|nullable|date',
            'categoria'   => 'nullable|string|max:255',
        ]);

        if ($validated['tipo_ajuste'] === 'ajustar_parcela') {
            $parcela = CartaoParcela::with('compra.cartao')->findOrFail($validated['parcela_id']);
            if ($parcela->compra->cartao->user_id !== Auth::id()) {
                abort(403);
            }

            $novoValor = (float) $validated['novo_valor'];
            $parcela->update(['valor_parcela' => $novoValor]);

            if ($parcela->compra->tipo === 'avista' || $parcela->compra->numero_parcelas <= 1) {
                $parcela->compra->update(['valor_total' => $novoValor]);
            }
            $cartao = $parcela->compra->cartao;
            $msg = 'Parcela ajustada com sucesso!';
        } else {
            $cartao = Cartao::where('user_id', Auth::id())->findOrFail($validated['cartao_id']);
            $compra = CartaoCompra::create([
                'cartao_id'       => $cartao->id,
                'descricao'       => $validated['descricao'],
                'valor_total'     => abs((float) $validated['valor']),
                'data_compra'     => $validated['data_compra'],
                'categoria'       => $validated['categoria'] ?: 'Ajuste / Tarifas',
                'tipo'            => 'avista',
                'numero_parcelas' => 1,
            ]);
            $this->gerarParcelas($compra);
            $msg = 'Lançamento de diferença registrado com sucesso!';
        }

        // Se vinculado a uma fatura salva, atualizar status
        $faturaAtualizada = null;
        if (!empty($validated['fatura_id'])) {
            $fatura = CartaoFatura::where('user_id', Auth::id())->find($validated['fatura_id']);
            if ($fatura) {
                if ($validated['tipo_ajuste'] === 'novo_lancamento' && isset($compra)) {
                    $primeiraParcela = $compra->parcelas()->first();
                    if ($primeiraParcela) {
                        $diaVenc = min((int)($cartao->dia_vencimento ?: 10), 28);
                        $vencEsperado = Carbon::create($fatura->ano, $fatura->mes, $diaVenc)->format('Y-m-d');
                        if ($primeiraParcela->data_vencimento !== $vencEsperado) {
                            $primeiraParcela->update(['data_vencimento' => $vencEsperado]);
                        }
                    }
                }

                $service = app(CreditCardReconciliationService::class);
                $res = $service->reconcileItems($fatura->itens_json ?? [], $cartao, $fatura->mes, $fatura->ano);
                $pendenciasCount = count($res['grupos']['faltando_no_sistema'] ?? []) +
                                   count($res['grupos']['sobrando_no_sistema'] ?? []) +
                                   count($res['grupos']['divergencias_valor'] ?? []);
                $status = ($pendenciasCount === 0 && abs($res['totais']['diferenca'] ?? 0) < 0.01) ? 'conciliada' : 'pendente';
                $fatura->update([
                    'status'           => $status,
                    'diferenca_total'  => $res['totais']['diferenca'] ?? 0,
                    'pendencias_count' => $pendenciasCount,
                ]);
                $faturaAtualizada = [
                    'id'               => $fatura->id,
                    'status'           => $fatura->status,
                    'pendencias_count' => $fatura->pendencias_count,
                    'diferenca_total'  => (float) $fatura->diferenca_total,
                ];
            }
        }

        return response()->json([
            'sucesso'           => true,
            'mensagem'          => $msg,
            'fatura_atualizada' => $faturaAtualizada,
        ]);
    }

    /**
     * Exporta o relatório comparativo de conciliação em PDF.
     */
    public function exportarRelatorioConciliacaoPdf(Request $request)
    {
        $dados = session('ultima_conciliacao_fatura');

        if (!$dados) {
            return redirect()->route('cartoes.index')->with('error', 'Nenhum relatório de conciliação recente para download.');
        }

        $pdf = Pdf::loadView('exports.conciliacao_fatura_pdf', $dados);
        $cartaoNome = \Illuminate\Support\Str::slug($dados['cartao']['nome'] ?? 'cartao');
        $mes = $dados['periodo']['mes'] ?? now()->month;
        $ano = $dados['periodo']['ano'] ?? now()->year;
        $filename = "relatorio_conciliacao_{$cartaoNome}_{$mes}_{$ano}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Alterna ou define manualmente o fechamento da fatura de um cartão em determinado mês/ano.
     */
    public function toggleFechamentoFatura(Request $request, Cartao $cartao)
    {
        if ($cartao->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'mes'     => 'required|integer|between:1,12',
            'ano'     => 'required|integer|min:2020|max:2050',
            'fechada' => 'nullable|boolean',
        ]);

        $fechada = $request->has('fechada') ? (bool) $request->fechada : null;
        $registro = CreditCardService::setFaturaFechada(
            cartao: $cartao,
            mes: (int) $validated['mes'],
            ano: (int) $validated['ano'],
            fechada: $fechada,
            userId: Auth::id()
        );

        $statusStr = $registro->fechada ? 'FECHADA' : 'ABERTA';
        $msg = "Fatura de {$cartao->nome} ({$validated['mes']}/{$validated['ano']}) marcada como {$statusStr} com sucesso.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'sucesso'  => true,
                'fechada'  => (bool) $registro->fechada,
                'mensagem' => $msg,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }
}
