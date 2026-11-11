<?php

declare(strict_types=1);

namespace App\DTOs;

class QualificacaoPermanenteDTO
{
    public function __construct(
        public string $nome,
        public float $percentual,
        public string $mes_inicio, // YYYY-MM: mês de vigência
        public ?string $mes_pagamento = null // YYYY-MM: folha em que entra, com o retroativo desde a vigência
    ) {}

    public static function fromArray(array $data): self
    {
        $inicio = $data['mes_inicio'] ?? date('Y-m');
        $pagamento = !empty($data['mes_pagamento']) ? $data['mes_pagamento'] : null;

        return new self(
            nome: $data['nome'] ?? 'Curso Permanente',
            percentual: (float) ($data['percentual'] ?? 2.0),
            mes_inicio: $inicio,
            // Pagamento antes da vigência não faz sentido: passa a valer na própria vigência
            mes_pagamento: ($pagamento !== null && $pagamento > $inicio) ? $pagamento : null
        );
    }
}
