<?php

declare(strict_types=1);

namespace App\Services\Ofx\DTO;

/**
 * DTO que representa um lançamento individual extraído do extrato OFX.
 */
class OfxTransactionDTO
{
    public function __construct(
        public readonly string $fitid,
        public readonly string $tipo, // 'receita' ou 'despesa'
        public readonly string $trntype, // 'DEBIT', 'CREDIT', 'PAYMENT', 'POS', 'OTHER', etc.
        public readonly string $data, // 'YYYY-MM-DD HH:MM:SS'
        public readonly float $valor, // Valor absoluto positivo
        public readonly float $valorOriginal, // Valor original com sinal do OFX
        public readonly string $descricao, // Descrição limpa (MEMO ou NAME)
        public readonly ?string $memo = null, // Conteúdo bruto higienizado de <MEMO>
        public readonly ?string $name = null, // Conteúdo de <NAME>
        public readonly ?string $checknum = null, // Número do documento / cheque / PIX
        public readonly ?string $refnum = null, // Número de referência se houver
        public readonly ?string $categoria = null,
        public readonly ?string $subcategoria = null
    ) {
    }

    /**
     * Converte o DTO em array associativo para serialização ou inserção em banco.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'fitid'              => $this->fitid,
            'tipo'               => $this->tipo,
            'trntype'            => $this->trntype,
            'data'               => $this->data,
            'valor'              => $this->valor,
            'valor_original'     => $this->valorOriginal,
            'descricao'          => $this->descricao,
            'memo'               => $this->memo,
            'name'               => $this->name,
            'checknum'           => $this->checknum,
            'refnum'             => $this->refnum,
            'categoria'          => $this->categoria,
            'subcategoria'       => $this->subcategoria,
        ];
    }
}
