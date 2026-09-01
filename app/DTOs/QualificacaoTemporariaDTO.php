<?php

declare(strict_types=1);

namespace App\DTOs;

class QualificacaoTemporariaDTO
{
    public function __construct(
        public string $nome,
        public float $percentual,
        public string $mes_inicio, // YYYY-MM
        public string $mes_fim // YYYY-MM
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nome: $data['nome'] ?? 'Curso',
            percentual: (float) ($data['percentual'] ?? 1.0),
            mes_inicio: $data['mes_inicio'] ?? date('Y-m'),
            mes_fim: $data['mes_fim'] ?? date('Y-m')
        );
    }
}
