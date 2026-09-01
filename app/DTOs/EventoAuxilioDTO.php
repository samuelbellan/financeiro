<?php

declare(strict_types=1);

namespace App\DTOs;

class EventoAuxilioDTO
{
    public function __construct(
        public string $mes_ano_inicio, // YYYY-MM
        public ?string $mes_ano_fim, // YYYY-MM
        public string $tipo_auxilio, // e.g. AUXILIO_ALIMENTACAO
        public float $valor,
        public string $acao // CRIAR, ALTERAR_VALOR, CANCELAR
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            mes_ano_inicio: $data['mes_ano_inicio'] ?? date('Y-m'),
            mes_ano_fim: !empty($data['mes_ano_fim']) ? $data['mes_ano_fim'] : null,
            tipo_auxilio: $data['tipo_auxilio'] ?? 'AUXILIO_ALIMENTACAO',
            valor: (float) ($data['valor'] ?? 0.0),
            acao: $data['acao'] ?? 'CRIAR'
        );
    }
}
