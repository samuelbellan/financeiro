<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Despesa fixa paga fora do holerite (ex.: boleto da CASSEMS do cônjuge).
 * Não altera o líquido da folha, só o líquido disponível.
 */
class DespesaForaFolhaDTO
{
    public function __construct(
        public string $descricao,
        public float $valor,
        public string $mes_inicio, // YYYY-MM
        public ?string $mes_fim = null // YYYY-MM
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            descricao: $data['descricao'] ?? 'Despesa',
            valor: (float) ($data['valor'] ?? 0.0),
            mes_inicio: $data['mes_inicio'] ?? date('Y-m'),
            mes_fim: !empty($data['mes_fim']) ? $data['mes_fim'] : null
        );
    }
}
