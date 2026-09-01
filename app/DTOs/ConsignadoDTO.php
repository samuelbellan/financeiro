<?php

declare(strict_types=1);

namespace App\DTOs;

class ConsignadoDTO
{
    public function __construct(
        public float $valor,
        public string $mes_inicio, // YYYY-MM
        public ?string $mes_fim = null // YYYY-MM
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            valor: (float) ($data['valor'] ?? 0.0),
            mes_inicio: $data['mes_inicio'] ?? date('Y-m'),
            mes_fim: !empty($data['mes_fim']) ? $data['mes_fim'] : null
        );
    }
}
