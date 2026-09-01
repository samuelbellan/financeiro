<?php

declare(strict_types=1);

namespace App\DTOs;

class QualificacaoPermanenteDTO
{
    public function __construct(
        public string $nome,
        public float $percentual,
        public string $mes_inicio // YYYY-MM
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nome: $data['nome'] ?? 'Curso Permanente',
            percentual: (float) ($data['percentual'] ?? 2.0),
            mes_inicio: $data['mes_inicio'] ?? date('Y-m')
        );
    }
}
