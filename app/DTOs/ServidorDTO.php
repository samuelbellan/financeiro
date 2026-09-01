<?php

declare(strict_types=1);

namespace App\DTOs;

class ServidorDTO
{
    public function __construct(
        public string $dt_exercicio,
        public string $cargo,
        public string $referencia_inicial,
        public float $aq_permanente_pct = 0.0,
        public bool $regime_integral = false,
        public float $outros_adicionais_pct = 0.0,
        public int $dependentes_irrf = 0,
        public int $dependentes_cassems = 0,
        public bool $tem_conjuge = false,
        public float $consignados = 0.0,
        public bool $teto_rgps = true,
        public float $salario_substituicao = 0.0,
        public float $funcao_comissao_valor = 0.0,
        public float $reajuste_auxilio_pct = 0.0,
        public int $reajuste_mes = 5
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            dt_exercicio: $data['dt_exercicio'] ?? date('Y-m-d'),
            cargo: $data['cargo'] ?? 'TNSU',
            referencia_inicial: $data['referencia_inicial'] ?? '1',
            aq_permanente_pct: (float) ($data['aq_permanente_pct'] ?? 0.0),
            regime_integral: (bool) ($data['regime_integral'] ?? false),
            outros_adicionais_pct: (float) ($data['outros_adicionais_pct'] ?? 0.0),
            dependentes_irrf: (int) ($data['dependentes_irrf'] ?? 0),
            dependentes_cassems: (int) ($data['dependentes_cassems'] ?? 0),
            tem_conjuge: (bool) ($data['tem_conjuge'] ?? false),
            consignados: (float) ($data['consignados'] ?? 0.0),
            teto_rgps: isset($data['teto_rgps']) ? (bool)$data['teto_rgps'] : true,
            salario_substituicao: (float) ($data['salario_substituicao'] ?? 0.0),
            funcao_comissao_valor: (float) ($data['funcao_comissao_valor'] ?? 0.0),
            reajuste_auxilio_pct: (float) ($data['reajuste_auxilio_pct'] ?? 0.0),
            reajuste_mes: (int) ($data['reajuste_mes'] ?? 5)
        );
    }
}
