<?php

namespace App\Services;

use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoParcela;
use Carbon\Carbon;

class CreditCardService
{
    /**
     * Calcula o vencimento exato de uma parcela considerando o dia de fechamento e vencimento do cartão.
     */
    public static function calcularVencimentoParcela(Cartao $cartao, Carbon $dataCompra, int $numeroParcela = 1): Carbon
    {
        $day = $dataCompra->day;
        $fechamento = (int) $cartao->dia_fechamento;
        $vencimento = (int) $cartao->dia_vencimento;

        $baseOffset = ($vencimento > $fechamento)
            ? ($day > $fechamento ? 1 : 0)
            : ($day > $fechamento ? 2 : 1);

        $mesOffset = ($numeroParcela - 1) + $baseOffset;

        $targetDate = $dataCompra->copy()->addMonths($mesOffset);
        $maxDays = $targetDate->daysInMonth;
        $dueDay = min($vencimento, $maxDays);

        return $targetDate->day($dueDay);
    }

    /**
     * Recalcula a data de vencimento das parcelas de um cartão com base nas regras atuais de fechamento/vencimento.
     */
    public static function recalcularParcelasCartao(Cartao $cartao, bool $apenasAbertas = true): int
    {
        $compras = CartaoCompra::where('cartao_id', $cartao->id)->get();
        $updatedCount = 0;

        foreach ($compras as $compra) {
            $dataCompra = Carbon::parse($compra->data_compra);
            $query = $compra->parcelas();
            if ($apenasAbertas) {
                $query->where('status', '!=', 'paga');
            }

            foreach ($query->get() as $parcela) {
                $novoVencimento = self::calcularVencimentoParcela($cartao, $dataCompra, $parcela->numero_parcela);
                if (Carbon::parse($parcela->data_vencimento)->format('Y-m-d') !== $novoVencimento->format('Y-m-d')) {
                    $parcela->data_vencimento = $novoVencimento;
                    $parcela->save();
                    $updatedCount++;
                }
            }
        }

        return $updatedCount;
    }
}
