<?php

namespace App\Services;

use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoFaturaFechamento;
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

    /**
     * Calcula a data estimada de fechamento da fatura com vencimento no mês/ano informados.
     */
    public static function getDataFechamentoFatura(Cartao $cartao, int $mes, int $ano): Carbon
    {
        $fechamento = (int) ($cartao->dia_fechamento ?: 1);
        $vencimento = (int) ($cartao->dia_vencimento ?: 10);

        if ($vencimento > $fechamento) {
            $dataTarget = Carbon::create($ano, $mes, 1);
        } else {
            $dataTarget = Carbon::create($ano, $mes, 1)->subMonth();
        }

        $dia = min($fechamento, $dataTarget->daysInMonth);
        return $dataTarget->day($dia)->endOfDay();
    }

    /**
     * Determina se a fatura de um determinado cartão e mês/ano já está fechada.
     * Considera primeiro o fechamento manual explícito pelo usuário;
     * caso não haja, avalia se o mês já passou ou se a data de fechamento do cartão já foi atingida.
     */
    public static function isFaturaFechada(Cartao $cartao, int $mes, int $ano): bool
    {
        // 1. Registro manual explícito
        $manual = CartaoFaturaFechamento::where('cartao_id', $cartao->id)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->first();

        if ($manual !== null) {
            return (bool) $manual->fechada;
        }

        // 2. Meses anteriores ao mês atual já estão fechados
        $hoje = Carbon::now();
        $mesFaturaInicio = Carbon::create($ano, $mes, 1)->startOfMonth();
        if ($mesFaturaInicio->copy()->addMonth()->isPast()) {
            return true;
        }

        // 3. Verificação automática com base na data de fechamento configurada no cartão
        $dataFechamento = self::getDataFechamentoFatura($cartao, $mes, $ano);
        return $hoje->greaterThanOrEqualTo($dataFechamento);
    }

    /**
     * Define ou alterna o status de fechamento manual da fatura de um cartão em um mês/ano.
     */
    public static function setFaturaFechada(Cartao $cartao, int $mes, int $ano, ?bool $fechada = null, ?int $userId = null): CartaoFaturaFechamento
    {
        if ($fechada === null) {
            $fechada = !self::isFaturaFechada($cartao, $mes, $ano);
        }

        return CartaoFaturaFechamento::updateOrCreate(
            [
                'cartao_id' => $cartao->id,
                'mes'       => $mes,
                'ano'       => $ano,
            ],
            [
                'user_id'    => $userId ?? $cartao->user_id,
                'fechada'    => $fechada,
                'fechada_em' => $fechada ? Carbon::now() : null,
            ]
        );
    }
}
