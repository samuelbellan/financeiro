<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Mensalidade da CASSEMS em folha a partir de um mês: alíquota sobre a base e parte fixa.
 * Quando há eventos, eles substituem a regra pelo número de dependentes.
 */
class CassemsEventoDTO
{
    public function __construct(
        public string $mes_inicio, // YYYY-MM
        public float $aliquota_pct,
        public float $fixo
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            mes_inicio: $data['mes_inicio'] ?? date('Y-m'),
            aliquota_pct: (float) ($data['aliquota_pct'] ?? 7.5),
            fixo: (float) ($data['fixo'] ?? 0.0)
        );
    }
}
