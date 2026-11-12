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
        public int $reajuste_mes = 5,
        public float $reajuste_padrao_pct = 0.0,
        public int $mes_ferias = 0,
        public bool $incluir_13 = true,
        // Premissas de cenário
        public float $irrf_correcao_pct = 0.0,      // correção anual da tabela do IRRF a partir de 2027
        public float $teto_rgps_reajuste_pct = 3.9, // reajuste anual do teto do RGPS (INPC) a partir de 2027
        public int $aq_temp_renovacao_qtd = 0,      // ações educacionais renovadas por ano
        public float $aq_temp_renovacao_pct = 1.0,
        public int $aq_temp_renovacao_mes = 6,
        public int $aq_temp_renovacao_ano = 0,      // 0 = ano seguinte ao último curso informado
        // Preenchidos mês a mês pela projeção (CASSEMS datada e retroativo de AQ permanente)
        public ?float $cassems_aliquota = null,
        public ?float $cassems_fixo = null,
        public float $aq_permanente_retroativo = 0.0,
        /** @var array<int, int> ano => mês das férias, quando difere de $mes_ferias */
        public array $ferias_excecoes = []
    ) {}

    /**
     * Lê "2026-04, 2027-01" como [2026 => 4, 2027 => 1]; ignora o que não for AAAA-MM válido.
     */
    public static function parseFeriasExcecoes(mixed $valor): array
    {
        $excecoes = [];
        foreach (preg_split('/[\s,;]+/', (string) $valor, -1, PREG_SPLIT_NO_EMPTY) as $item) {
            if (preg_match('/^(\d{4})-(\d{2})$/', $item, $m) && (int) $m[2] >= 1 && (int) $m[2] <= 12) {
                $excecoes[(int) $m[1]] = (int) $m[2];
            }
        }
        ksort($excecoes);

        return $excecoes;
    }

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
            reajuste_mes: (int) ($data['reajuste_mes'] ?? 5),
            reajuste_padrao_pct: (float) ($data['reajuste_padrao_pct'] ?? 0.0),
            mes_ferias: max(0, min(12, (int) ($data['mes_ferias'] ?? 0))),
            incluir_13: isset($data['incluir_13']) ? (bool) $data['incluir_13'] : true,
            irrf_correcao_pct: (float) ($data['irrf_correcao_pct'] ?? 0.0),
            teto_rgps_reajuste_pct: (float) ($data['teto_rgps_reajuste_pct'] ?? 3.9),
            aq_temp_renovacao_qtd: max(0, min(20, (int) ($data['aq_temp_renovacao_qtd'] ?? 0))),
            aq_temp_renovacao_pct: (float) ($data['aq_temp_renovacao_pct'] ?? 1.0),
            aq_temp_renovacao_mes: max(1, min(12, (int) ($data['aq_temp_renovacao_mes'] ?? 6))),
            aq_temp_renovacao_ano: max(0, (int) ($data['aq_temp_renovacao_ano'] ?? 0)),
            ferias_excecoes: self::parseFeriasExcecoes($data['ferias_excecoes'] ?? '')
        );
    }
}
