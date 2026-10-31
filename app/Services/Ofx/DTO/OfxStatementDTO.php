<?php

declare(strict_types=1);

namespace App\Services\Ofx\DTO;

/**
 * DTO que encapsula todo o extrato bancário parseado (conta e lançamentos).
 */
class OfxStatementDTO
{
    /**
     * @param OfxAccountDTO $account
     * @param OfxTransactionDTO[] $transactions
     */
    public function __construct(
        public readonly OfxAccountDTO $account,
        public readonly array $transactions
    ) {
    }

    /**
     * Retorna o total de créditos (receitas).
     */
    public function getTotalCreditos(): float
    {
        $total = 0.0;
        foreach ($this->transactions as $t) {
            if ($t->tipo === 'receita') {
                $total += $t->valor;
            }
        }
        return round($total, 2);
    }

    /**
     * Retorna o total de débitos (despesas).
     */
    public function getTotalDebitos(): float
    {
        $total = 0.0;
        foreach ($this->transactions as $t) {
            if ($t->tipo === 'despesa') {
                $total += $t->valor;
            }
        }
        return round($total, 2);
    }

    /**
     * Retorna a quantidade total de lançamentos.
     */
    public function count(): int
    {
        return count($this->transactions);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account'      => $this->account->toArray(),
            'total_itens'  => $this->count(),
            'creditos'     => $this->getTotalCreditos(),
            'debitos'      => $this->getTotalDebitos(),
            'transactions' => array_map(fn (OfxTransactionDTO $t) => $t->toArray(), $this->transactions),
        ];
    }
}
