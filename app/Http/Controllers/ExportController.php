<?php

namespace App\Http\Controllers;

use App\Models\Cartao;
use App\Models\CartaoParcela;
use App\Models\CartaoPrevisao;
use App\Models\Transacao;
use App\Models\TransacaoPrevisao;
use App\Models\User;
use App\Services\CategorySanitizer;
use App\Services\FinancialSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ExportController extends Controller
{
    public function exportFatura(Request $request, $cartaoId, $format)
    {
        $user = Auth::user();
        $cartao = Cartao::where('user_id', $user->id)->findOrFail($cartaoId);
        $mes = $request->get('mes', now()->month);
        $ano = $request->get('ano', now()->year);

        $faturas = CartaoParcela::whereHas('compra', function($query) use ($cartaoId) {
                $query->where('cartao_id', $cartaoId);
            })
            ->whereMonth('data_vencimento', $mes)
            ->whereYear('data_vencimento', $ano)
            ->with('compra')
            ->get();

        $filename = "fatura_{$cartao->nome}_{$mes}_{$ano}";

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.fatura_pdf', compact('cartao', 'faturas', 'mes', 'ano'));
            return $pdf->download("{$filename}.pdf");
        }

        if ($format === 'csv') {
            return response()->streamDownload(function() use ($faturas) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Vencimento', 'Descricao', 'Parcela', 'Valor', 'Status']);
                foreach ($faturas as $f) {
                    fputcsv($file, [
                        Carbon::parse($f->data_vencimento)->format('d/m/Y'),
                        $f->compra->descricao,
                        $f->numero_parcela . '/' . $f->compra->numero_parcelas,
                        $f->valor_parcela,
                        $f->status
                    ]);
                }
                fclose($file);
            }, "{$filename}.csv");
        }

        if ($format === 'sql') {
            $sql = "-- Export Fatura {$cartao->nome} ({$mes}/{$ano})\n";
            foreach ($faturas as $f) {
                $desc = addslashes($f->compra->descricao);
                $sql .= "INSERT INTO faturas (cartao, descricao, valor, vencimento) VALUES ('{$cartao->nome}', '{$desc}', {$f->valor_parcela}, '{$f->data_vencimento}');\n";
            }
            return response($sql, 200, [
                'Content-Type' => 'application/sql',
                'Content-Disposition' => "attachment; filename={$filename}.sql",
            ]);
        }

        return abort(404);
    }

    public function exportOrcamento(Request $request, $format)
    {
        $user = Auth::user();
        $mes = $request->get('mes', now()->month);
        $ano = $request->get('ano', now()->year);
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        if ($dataInicio && $dataFim) {
            $transacoes = Transacao::where('user_id', $user->id)
                ->whereBetween('data', [$dataInicio, $dataFim])
                ->get();
            $filename = "orcamento_{$dataInicio}_a_{$dataFim}";
        } else {
            $transacoes = Transacao::where('user_id', $user->id)
                ->whereMonth('data', $mes)
                ->whereYear('data', $ano)
                ->get();
            $filename = "orcamento_{$mes}_{$ano}";
        }

        $previsoes = TransacaoPrevisao::where('user_id', $user->id)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.orcamento_pdf', compact('transacoes', 'previsoes', 'mes', 'ano', 'dataInicio', 'dataFim'));
            return $pdf->download("{$filename}.pdf");
        }

        if ($format === 'csv') {
            return response()->streamDownload(function() use ($transacoes) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Data', 'Descricao', 'Categoria', 'Tipo', 'Valor']);
                foreach ($transacoes as $t) {
                    fputcsv($file, [
                        Carbon::parse($t->data)->format('d/m/Y'),
                        $t->descricao,
                        $t->categoria,
                        $t->tipo,
                        $t->valor
                    ]);
                }
                fclose($file);
            }, "{$filename}.csv");
        }

        if ($format === 'sql') {
            $sql = "-- Export Orcamento\n";
            foreach ($transacoes as $t) {
                $desc = addslashes($t->descricao);
                $cat = addslashes($t->categoria);
                $sql .= "INSERT INTO transacoes (data, descricao, categoria, tipo, valor) VALUES ('{$t->data}', '{$desc}', '{$cat}', '{$t->tipo}', {$t->valor});\n";
            }
            return response($sql, 200, [
                'Content-Type' => 'application/sql',
                'Content-Disposition' => "attachment; filename={$filename}.sql",
            ]);
        }

        return abort(404);
    }

    public function exportOrcamentoPost(Request $request, $format)
    {
        $user = Auth::user();
        $mes = $request->get('mes', now()->month);
        $ano = $request->get('ano', now()->year);
        $dataInicio = $request->get('data_inicio');
        $dataFim = $request->get('data_fim');

        if ($dataInicio && $dataFim) {
            $transacoes = Transacao::where('user_id', $user->id)
                ->whereBetween('data', [$dataInicio, $dataFim])
                ->get();
            $filename = "orcamento_{$dataInicio}_a_{$dataFim}";
        } else {
            $transacoes = Transacao::where('user_id', $user->id)
                ->whereMonth('data', $mes)
                ->whereYear('data', $ano)
                ->get();
            $filename = "orcamento_{$mes}_{$ano}";
        }

        $previsoes = TransacaoPrevisao::where('user_id', $user->id)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->get();
        
        $chart1 = $request->get('chart1');
        $chart2 = $request->get('chart2');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.orcamento_pdf', compact('transacoes', 'previsoes', 'mes', 'ano', 'chart1', 'chart2', 'dataInicio', 'dataFim'));
            return $pdf->download("{$filename}.pdf");
        }

        return abort(404);
    }

    /**
     * Exporta o relatório completo e consolidado de Conta Corrente + Cartões em PDF.
     */
    public function exportResumoFinanceiroPdf(Request $request)
    {
        $user = Auth::user();
        if (!$user && $request->has('user_id')) {
            if ($request->hasValidSignature()) {
                $user = User::find($request->get('user_id'));
            }
        }

        if (!$user) {
            abort(403, 'Acesso não autorizado.');
        }

        $mes = (int) $request->get('mes', now()->month);
        $ano = (int) $request->get('ano', now()->year);

        // Resumo financeiro consolidado
        $summary = FinancialSummaryService::generateSummary($user, $mes, $ano);

        // Transações realizadas na conta corrente
        $transacoes = Transacao::where('user_id', $user->id)
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->orderBy('data', 'asc')
            ->get();

        // Previsões de metas da conta corrente
        $previsoesCC = TransacaoPrevisao::where('user_id', $user->id)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->get();

        foreach ($previsoesCC as $p) {
            $consumoReal = $transacoes->filter(function($t) use ($p) {
                if ($p->tipo !== $t->tipo) return false;
                if (!CategorySanitizer::isMatch($p->categoria, $t->categoria)) return false;
                if ($p->subcategoria) {
                    return CategorySanitizer::isMatch($p->subcategoria, $t->subcategoria);
                }
                return empty($t->subcategoria);
            })->sum('valor');
            $p->consumo_real = $consumoReal;
        }

        // Cartões ativos
        $cartoes = Cartao::where('user_id', $user->id)->where('ativo', true)->get();

        // Parcelas da fatura do mês dos cartões ativos
        $faturasParcelas = CartaoParcela::whereHas('compra.cartao', function($q) use ($user) {
                $q->where('user_id', $user->id)->where('ativo', true);
            })
            ->whereMonth('data_vencimento', $mes)
            ->whereYear('data_vencimento', $ano)
            ->with(['compra.cartao'])
            ->orderBy('data_vencimento', 'asc')
            ->get();

        // Previsões de cartões do mês
        $cartaoPrevisoes = CartaoPrevisao::whereIn('cartao_id', $cartoes->pluck('id'))
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->with('cartao')
            ->get();

        foreach ($cartaoPrevisoes as $cp) {
            $consumo = (float) $faturasParcelas->filter(function($fatura) use ($cp) {
                return $fatura->compra 
                    && $fatura->compra->cartao_id == $cp->cartao_id 
                    && CategorySanitizer::isMatch($fatura->compra->categoria, $cp->categoria);
            })->sum('valor_parcela');
            $cp->consumo_real = $consumo;
            $cp->restante = max(0, $cp->valor_previsto - $consumo);
        }

        $filename = "relatorio_financeiro_{$mes}_{$ano}.pdf";

        $pdf = Pdf::loadView('exports.resumo_financeiro_pdf', compact(
            'user', 'mes', 'ano', 'summary', 'transacoes', 'previsoesCC', 
            'cartoes', 'faturasParcelas', 'cartaoPrevisoes'
        ))->setPaper('a4', 'portrait');

        if ($request->has('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}
