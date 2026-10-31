<?php

declare(strict_types=1);

namespace App\Services\Ofx\DTO;

/**
 * DTO com o resultado estruturado da importação de um arquivo OFX.
 */
class OfxImportResultDTO
{
    /**
     * @param bool $sucesso
     * @param string $mensagem
     * @param int|null $contaId
     * @param string|null $contaNome
     * @param string $tipoConta // 'conta_corrente' ou 'cartao_credito'
     * @param int $totalEncontrados
     * @param int $inseridos
     * @param int $duplicadosIgnorados
     * @param float $totalCreditos
     * @param float $totalDebitos
     * @param float|null $saldoFinal
     * @param string|null $periodoInicio
     * @param string|null $periodoFim
     * @param array<int, string> $erros
     * @param array<int, array<string, mixed>> $transacoesInseridas
     */
    public function __construct(
        public readonly bool $sucesso,
        public readonly string $mensagem,
        public readonly ?int $contaId = null,
        public readonly ?string $contaNome = null,
        public readonly string $tipoConta = 'conta_corrente',
        public readonly int $totalEncontrados = 0,
        public readonly int $inseridos = 0,
        public readonly int $duplicadosIgnorados = 0,
        public readonly float $totalCreditos = 0.0,
        public readonly float $totalDebitos = 0.0,
        public readonly ?float $saldoFinal = null,
        public readonly ?string $periodoInicio = null,
        public readonly ?string $periodoFim = null,
        public readonly array $erros = [],
        public readonly array $transacoesInseridas = []
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sucesso'              => $this->sucesso,
            'mensagem'             => $this->mensagem,
            'conta_id'             => $this->contaId,
            'conta_nome'           => $this->contaNome,
            'tipo_conta'           => $this->tipoConta,
            'total_encontrados'    => $this->totalEncontrados,
            'inseridos'            => $this->inseridos,
            'duplicados_ignorados' => $this->duplicadosIgnorados,
            'total_creditos'       => $this->totalCreditos,
            'total_debitos'        => $this->totalDebitos,
            'saldo_movimentado'    => round($this->totalCreditos - $this->totalDebitos, 2),
            'saldo_final'          => $this->saldoFinal,
            'periodo_inicio'       => $this->periodoInicio,
            'periodo_fim'          => $this->periodoFim,
            'erros'                => $this->erros,
            'transacoes_inseridas' => $this->transacoesInseridas,
        ];
    }
}
