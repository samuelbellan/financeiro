<?php

namespace App\Http\Controllers;

use App\Models\Cartao;
use App\Models\CartaoParcela;
use App\Models\Transacao;
use App\Models\TransacaoPrevisao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $transacoes = Transacao::where('user_id', $user->id)
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->get();

        $previsoes = TransacaoPrevisao::where('user_id', $user->id)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->get();

        $filename = "orcamento_{$mes}_{$ano}";

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.orcamento_pdf', compact('transacoes', 'previsoes', 'mes', 'ano'));
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
            $sql = "-- Export Orcamento ({$mes}/{$ano})\n";
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

        $transacoes = Transacao::where('user_id', $user->id)
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->get();

        $previsoes = TransacaoPrevisao::where('user_id', $user->id)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->get();

        $filename = "orcamento_{$mes}_{$ano}";
        
        $chart1 = $request->get('chart1');
        $chart2 = $request->get('chart2');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.orcamento_pdf', compact('transacoes', 'previsoes', 'mes', 'ano', 'chart1', 'chart2'));
            return $pdf->download("{$filename}.pdf");
        }

        return abort(404);
    }
}
