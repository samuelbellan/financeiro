<?php

declare(strict_types=1);

namespace App\Services\Ofx\DTO;

/**
 * DTO que representa os dados da conta ou cartão identificados no OFX.
 */
class OfxAccountDTO
{
    public function __construct(
        public readonly string $tipoOfx, // 'conta_corrente' ou 'cartao_credito'
        public readonly ?string $bancoId = null, // Código COMPE do banco (ex.: 033, 077, 237)
        public readonly ?string $agencia = null, // Código da agência se houver
        public readonly ?string $numeroConta = null, // Número da conta ou últimos dígitos do cartão
        public readonly string $tipoConta = 'CHECKING', // 'CHECKING', 'SAVINGS', 'CREDITCARD'
        public readonly string $moeda = 'BRL',
        public readonly ?float $saldoFinal = null,
        public readonly ?string $dataSaldo = null,
        public readonly ?string $dataInicio = null,
        public readonly ?string $dataFim = null
    ) {
    }

    /**
     * Retorna o nome amigável sugerido para a instituição bancária com base no bancoId.
     */
    public function getNomeInstituicao(): string
    {
        $map = [
            '001' => 'Banco do Brasil',
            '1'   => 'Banco do Brasil',
            '033' => 'Santander',
            '33'  => 'Santander',
            '077' => 'Banco Inter',
            '77'  => 'Banco Inter',
            '104' => 'Caixa Econômica Federal',
            '237' => 'Bradesco',
            '260' => 'Nubank',
            '341' => 'Itaú Unibanco',
            '422' => 'Banco Safra',
            '655' => 'Banco Votorantim / Neon',
            '336' => 'C6 Bank',
        ];

        $code = ltrim($this->bancoId ?? '', '0');
        if (!empty($code) && isset($map[$code])) {
            return $map[$code];
        }

        if (!empty($this->bancoId) && isset($map[$this->bancoId])) {
            return $map[$this->bancoId];
        }

        return $this->tipoOfx === 'cartao_credito' ? 'Cartão de Crédito' : 'Conta Bancária';
    }

    /**
     * Retorna um título amigável para exibição ou identificação da conta.
     */
    public function getIdentificadorAmigavel(): string
    {
        $instituicao = $this->getNomeInstituicao();
        
        if ($this->tipoOfx === 'cartao_credito') {
            $sufixo = !empty($this->numeroConta) ? " (Final " . substr($this->numeroConta, -4) . ")" : '';
            return "{$instituicao} - Fatura{$sufixo}";
        }

        $detalhes = [];
        if (!empty($this->agencia)) {
            $detalhes[] = "Ag: {$this->agencia}";
        }
        if (!empty($this->numeroConta)) {
            $detalhes[] = "Cc: {$this->numeroConta}";
        }

        $detalhesStr = !empty($detalhes) ? ' (' . implode(' / ', $detalhes) . ')' : '';
        return "{$instituicao}{$detalhesStr}";
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tipo_ofx'         => $this->tipoOfx,
            'banco_id'         => $this->bancoId,
            'agencia'          => $this->agencia,
            'numero_conta'     => $this->numeroConta,
            'tipo_conta'       => $this->tipoConta,
            'moeda'            => $this->moeda,
            'saldo_final'      => $this->saldoFinal,
            'data_saldo'       => $this->dataSaldo,
            'data_inicio'      => $this->dataInicio,
            'data_fim'         => $this->dataFim,
            'nome_instituicao' => $this->getNomeInstituicao(),
            'nome_amigavel'    => $this->getIdentificadorAmigavel(),
        ];
    }
}
