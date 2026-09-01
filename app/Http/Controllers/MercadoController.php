<?php

namespace App\Http\Controllers;

use App\Models\NotaFiscal;
use App\Models\NotaFiscalItem;
use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoParcela;
use App\Models\Transacao;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MercadoController extends Controller
{
    public function __construct(
        protected GeminiService $gemini
    ) {}

    /**
     * Exibe o painel analítico de gastos de supermercado & notas fiscais.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $user = Auth::user();

        // ── 1. Tratamento de Período ─────────────────────────────────────────
        $preset = $request->query('preset', 'mes_atual');
        $dataInicio = $request->query('data_inicio');
        $dataFim = $request->query('data_fim');
        $mes = (int)($request->query('mes', Carbon::now()->month));
        $ano = (int)($request->query('ano', Carbon::now()->year));

        $filtroTexto = $request->query('busca');
        $filtroCategoria = $request->query('categoria');
        $filtroEstabelecimento = $request->query('estabelecimento');

        $start = null;
        $end = null;
        $periodoLabel = '';

        if (!empty($dataInicio) && !empty($dataFim)) {
            $start = Carbon::parse($dataInicio)->startOfDay();
            $end = Carbon::parse($dataFim)->endOfDay();
            $periodoLabel = $start->format('d/m/Y') . ' até ' . $end->format('d/m/Y');
            $preset = 'personalizado';
        } else {
            switch ($preset) {
                case 'mes_anterior':
                    $dt = Carbon::now()->subMonth();
                    $mes = $dt->month;
                    $ano = $dt->year;
                    $start = Carbon::create($ano, $mes, 1)->startOfMonth();
                    $end = Carbon::create($ano, $mes, 1)->endOfMonth();
                    $periodoLabel = $start->translatedFormat('F / Y');
                    break;
                case 'ultimos_3_meses':
                    $start = Carbon::now()->subMonths(2)->startOfMonth();
                    $end = Carbon::now()->endOfMonth();
                    $periodoLabel = 'Últimos 3 meses (' . $start->translatedFormat('M/y') . ' - ' . $end->translatedFormat('M/y') . ')';
                    break;
                case 'ano_atual':
                    $start = Carbon::now()->startOfYear();
                    $end = Carbon::now()->endOfYear();
                    $periodoLabel = 'Ano de ' . Carbon::now()->year;
                    break;
                case 'todos':
                    $start = Carbon::create(2020, 1, 1);
                    $end = Carbon::now()->addYear()->endOfYear();
                    $periodoLabel = 'Todo o Histórico';
                    break;
                case 'mes_atual':
                default:
                    $start = Carbon::create($ano, $mes, 1)->startOfMonth();
                    $end = Carbon::create($ano, $mes, 1)->endOfMonth();
                    $periodoLabel = $start->translatedFormat('F / Y');
                    break;
            }
        }

        // ── 2. Consulta de Notas Fiscais no Período ───────────────────────────
        $notasQuery = NotaFiscal::where('user_id', $userId)
            ->whereBetween('data_compra', [$start, $end])
            ->with(['itens', 'transacao', 'cartaoCompra.cartao'])
            ->orderBy('data_compra', 'desc');

        if (!empty($filtroEstabelecimento)) {
            $notasQuery->where('estabelecimento', 'like', "%{$filtroEstabelecimento}%");
        }

        if (!empty($filtroTexto)) {
            $notasQuery->where(function ($q) use ($filtroTexto) {
                $q->where('estabelecimento', 'like', "%{$filtroTexto}%")
                  ->orWhereHas('itens', function ($iq) use ($filtroTexto) {
                      $iq->where('nome_item', 'like', "%{$filtroTexto}%");
                  });
            });
        }

        if (!empty($filtroCategoria)) {
            $notasQuery->whereHas('itens', function ($iq) use ($filtroCategoria) {
                $iq->where('categoria_item', $filtroCategoria);
            });
        }

        $notasFiscais = $notasQuery->get();

        // ── 3. Métricas Principais (KPIs) ────────────────────────────────────
        $totalGasto = $notasFiscais->sum('valor_total');
        $qtdNotas = $notasFiscais->count();
        $ticketMedio = $qtdNotas > 0 ? $totalGasto / $qtdNotas : 0;

        // ── 4. Agrupamento por Categoria de Item ──────────────────────────────
        $itensQuery = NotaFiscalItem::where('user_id', $userId)
            ->whereBetween('data_compra', [$start, $end]);

        if (!empty($filtroEstabelecimento)) {
            $itensQuery->where('estabelecimento', 'like', "%{$filtroEstabelecimento}%");
        }

        if (!empty($filtroTexto)) {
            $itensQuery->where('nome_item', 'like', "%{$filtroTexto}%");
        }

        $todosItens = $itensQuery->get();
        $totalItensQtd = $todosItens->sum('quantidade');

        $gastosPorCategoria = [];
        $categoriaCores = [
            'Carnes'     => '#ef4444', // Vermelho
            'Hortifruti' => '#10b981', // Verde
            'Laticínios' => '#3b82f6', // Azul
            'Padaria'    => '#f59e0b', // Âmbar
            'Limpeza'    => '#8b5cf6', // Roxo
            'Higiene'    => '#ec4899', // Rosa
            'Bebidas'    => '#06b6d4', // Ciano
            'Mercearia'  => '#d97706', // Marrom/Dourado
            'Outros'     => '#6b7280', // Cinza
        ];

        foreach ($todosItens as $it) {
            $cat = $it->categoria_item ?: 'Outros';
            if (!isset($gastosPorCategoria[$cat])) {
                $gastosPorCategoria[$cat] = [
                    'categoria' => $cat,
                    'total'     => 0.0,
                    'qtd_itens' => 0,
                    'cor'       => $categoriaCores[$cat] ?? '#6b7280',
                ];
            }
            $gastosPorCategoria[$cat]['total'] += (float)$it->valor_total;
            $gastosPorCategoria[$cat]['qtd_itens'] += (float)$it->quantidade;
        }

        // Ordenar categorias pelo maior valor gasto
        uasort($gastosPorCategoria, fn($a, $b) => $b['total'] <=> $a['total']);

        $categoriaCampeao = !empty($gastosPorCategoria) ? reset($gastosPorCategoria) : null;
        $categoriaCampeaoPercent = ($totalGasto > 0 && $categoriaCampeao) 
            ? round(($categoriaCampeao['total'] / $totalGasto) * 100, 1) 
            : 0;

        // ── 5. Histórico de Gastos por Mês (Para Gráfico de Barras) ───────────
        $ultimosMesesNotas = NotaFiscal::where('user_id', $userId)
            ->where('data_compra', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->get();

        $historicoMesesRaw = [];
        foreach ($ultimosMesesNotas as $nota) {
            if ($nota->data_compra) {
                $mKey = Carbon::parse($nota->data_compra)->format('Y-m');
                $historicoMesesRaw[$mKey] = ($historicoMesesRaw[$mKey] ?? 0.0) + (float)$nota->valor_total;
            }
        }

        $historicoLabels = [];
        $historicoValores = [];
        for ($i = 5; $i >= 0; $i--) {
            $mesKey = Carbon::now()->subMonths($i)->format('Y-m');
            $label = Carbon::now()->subMonths($i)->translatedFormat('M/y');
            $historicoLabels[] = ucfirst($label);
            $historicoValores[] = (float)($historicoMesesRaw[$mesKey] ?? 0);
        }

        // ── 6. Itens Mais Comprados / Top Gastos ─────────────────────────────
        $topItens = $todosItens->groupBy('nome_item')->map(function ($grupo) {
            return [
                'nome'            => $grupo->first()->nome_item,
                'categoria'       => $grupo->first()->categoria_item,
                'quantidade_total'=> $grupo->sum('quantidade'),
                'valor_total'     => $grupo->sum('valor_total'),
                'compras_count'   => $grupo->count(),
            ];
        })->sortByDesc('valor_total')->take(10)->values();

        // ── 7. Agrupamento completo de Itens por Categoria ───────────────────
        $itensPorCategoria = $todosItens->groupBy(function($item) {
            return $item->categoria_item ?: 'Outros';
        })->map(function ($items, $catName) use ($totalGasto, $categoriaCores) {
            $catTotal = (float)$items->sum('valor_total');
            $pct = $totalGasto > 0 ? round(($catTotal / $totalGasto) * 100, 1) : 0;
            
            // Produtos consolidados nesta categoria
            $produtosConsolidados = $items->groupBy('nome_item')->map(function ($prodGroup) use ($catTotal) {
                $prodTotal = (float)$prodGroup->sum('valor_total');
                $prodQtd = (float)$prodGroup->sum('quantidade');
                return [
                    'nome'           => $prodGroup->first()->nome_item,
                    'quantidade'     => $prodQtd,
                    'preco_medio'    => $prodQtd > 0 ? ($prodTotal / $prodQtd) : 0,
                    'valor_total'    => $prodTotal,
                    'compras_count'  => $prodGroup->count(),
                    'pct_categoria'  => $catTotal > 0 ? round(($prodTotal / $catTotal) * 100, 1) : 0,
                    'registros'      => $prodGroup->sortByDesc('data_compra')->values(),
                ];
            })->sortByDesc('valor_total')->values();

            return [
                'categoria'      => $catName,
                'cor'            => $categoriaCores[$catName] ?? '#6b7280',
                'total'          => $catTotal,
                'qtd_itens'      => (float)$items->sum('quantidade'),
                'compras_count'  => $items->count(),
                'pct_total'      => $pct,
                'produtos'       => $produtosConsolidados,
                'itens_raw'      => $items->sortByDesc('data_compra')->values(),
            ];
        })->sortByDesc('total');

        // ── 8. Lista de Estabelecimentos únicos para filtro ──────────────────
        $estabelecimentos = NotaFiscal::where('user_id', $userId)
            ->whereNotNull('estabelecimento')
            ->distinct()
            ->pluck('estabelecimento')
            ->toArray();

        // Cartões do usuário para o modal de upload manual
        $cartoes = Cartao::where('user_id', $userId)->where('ativo', true)->get();

        return view('financas.mercado', compact(
            'notasFiscais',
            'totalGasto',
            'qtdNotas',
            'ticketMedio',
            'totalItensQtd',
            'todosItens',
            'gastosPorCategoria',
            'itensPorCategoria',
            'categoriaCampeao',
            'categoriaCampeaoPercent',
            'historicoLabels',
            'historicoValores',
            'topItens',
            'estabelecimentos',
            'cartoes',
            'mes',
            'ano',
            'dataInicio',
            'dataFim',
            'preset',
            'periodoLabel',
            'filtroTexto',
            'filtroCategoria',
            'filtroEstabelecimento'
        ));
    }

    /**
     * Realiza o upload manual de uma foto de nota fiscal pelo navegador com IA OCR.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'foto_nf'           => 'required|image|max:15360', // até 15MB
            'transacao_destino' => 'nullable|in:casa,cartao',
            'cartao_id'         => 'nullable|exists:cartoes,id',
            'legenda'           => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $file = $request->file('foto_nf');
        $fileBytes = file_get_contents($file->getRealPath());

        if (!$fileBytes) {
            return redirect()->back()->with('error', 'Não foi possível ler a imagem enviada.');
        }

        // Otimizar imagem
        [$base64Image, $mimeType] = $this->compressImageForOcr($fileBytes);

        // Salvar imagem no storage público
        $filename = 'nf_' . uniqid() . '_' . time() . '.jpg';
        $fotoPath = 'notas_fiscais/' . $filename;
        Storage::disk('public')->put($fotoPath, base64_decode($base64Image));

        // Enviar para OCR no Gemini
        $caption = $request->input('legenda');
        if ($request->input('transacao_destino') === 'cartao' && $request->input('cartao_id')) {
            $cartao = Cartao::find($request->input('cartao_id'));
            if ($cartao) {
                $caption = ($caption ? $caption . ' - ' : '') . "Pago no cartão {$cartao->nome}";
            }
        }

        $parsed = $this->gemini->parseReceiptImage($base64Image, $mimeType, $user->id, $caption);

        if (!isset($parsed['tipo']) || $parsed['tipo'] !== 'nota_fiscal') {
            return redirect()->back()->with('error', $parsed['erro'] ?? 'A IA não conseguiu reconhecer os dados da nota fiscal.');
        }

        $estabelecimento = $parsed['estabelecimento'] ?? 'Supermercado';
        $valorTotal = (float)($parsed['valor_total'] ?? 0);
        $dataCompra = !empty($parsed['data_compra']) ? Carbon::parse($parsed['data_compra']) : Carbon::now();
        $itens = $parsed['itens'] ?? [];

        if ($valorTotal <= 0) {
            return redirect()->back()->with('error', 'Valor total da nota fiscal não identificado.');
        }

        $transacaoDestino = $request->input('transacao_destino') ?? $parsed['transacao_destino'] ?? 'casa';
        $cartaoId = $request->input('cartao_id') ?? $parsed['cartao_id'] ?? null;

        $transacaoObj = null;
        $cartaoCompraObj = null;
        $cartao = null;

        if ($transacaoDestino === 'cartao') {
            if ($cartaoId) {
                $cartao = Cartao::where('user_id', $user->id)->find($cartaoId);
            } else {
                $cartao = Cartao::where('user_id', $user->id)->where('ativo', true)->first();
            }

            if ($cartao) {
                $cartaoCompraObj = CartaoCompra::create([
                    'cartao_id'       => $cartao->id,
                    'descricao'       => "Compra em {$estabelecimento} (Nota Fiscal)",
                    'valor_total'     => $valorTotal,
                    'tipo'            => 'avista',
                    'numero_parcelas' => 1,
                    'categoria'       => 'Alimentação',
                    'data_compra'     => $dataCompra,
                ]);

                $vencimento = \App\Services\CreditCardService::calcularVencimentoParcela($cartao, $dataCompra, 1);
                CartaoParcela::create([
                    'cartao_compra_id' => $cartaoCompraObj->id,
                    'numero_parcela'   => 1,
                    'valor_parcela'    => $valorTotal,
                    'data_vencimento'  => $vencimento,
                    'status'           => 'aberta',
                ]);
            }
        }

        if (!$cartaoCompraObj) {
            $transacaoObj = Transacao::create([
                'user_id'      => $user->id,
                'descricao'    => "Mercado em {$estabelecimento} (Nota Fiscal)",
                'valor'        => $valorTotal,
                'tipo'         => 'despesa',
                'categoria'    => 'Alimentação',
                'subcategoria' => 'Mercado',
                'data'         => $dataCompra,
            ]);
        }

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
            'observacoes'      => 'Upload manual via painel web',
        ]);

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
        }

        return redirect()->route('financas.mercado.index')->with('success', "Nota Fiscal de {$estabelecimento} (R$ " . number_format($valorTotal, 2, ',', '.') . ") processada com " . count($itens) . " itens!");
    }

    /**
     * Exclui uma Nota Fiscal e seus itens.
     */
    public function destroy(NotaFiscal $notaFiscal)
    {
        if ($notaFiscal->user_id !== Auth::id()) {
            abort(403);
        }

        // Deletar foto do storage se existir
        if ($notaFiscal->foto_path && Storage::disk('public')->exists($notaFiscal->foto_path)) {
            Storage::disk('public')->delete($notaFiscal->foto_path);
        }

        // Deletar a transação ou compra de cartão vinculada se existir
        if ($notaFiscal->transacao) {
            $notaFiscal->transacao->delete();
        }
        if ($notaFiscal->cartaoCompra) {
            $notaFiscal->cartaoCompra->delete();
        }

        $notaFiscal->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Nota Fiscal excluída com sucesso.');
    }

    /**
     * Exclui um item específico de uma Nota Fiscal.
     */
    public function destroyItem(NotaFiscalItem $item)
    {
        if ($item->user_id !== Auth::id()) {
            abort(403);
        }

        $notaFiscal = $item->notaFiscal;
        $item->delete();

        // Recalcular valor total da nota fiscal
        if ($notaFiscal) {
            $novoTotal = $notaFiscal->itens()->sum('valor_total');
            $notaFiscal->update(['valor_total' => $novoTotal]);

            if ($notaFiscal->transacao) {
                $notaFiscal->transacao->update(['valor' => $novoTotal]);
            }
            if ($notaFiscal->cartaoCompra) {
                $notaFiscal->cartaoCompra->update(['valor_total' => $novoTotal]);
                $notaFiscal->cartaoCompra->parcelas()->update(['valor_parcela' => $novoTotal]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Otimiza a imagem para envio ao OCR.
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
}

