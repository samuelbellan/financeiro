<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ServidorDTO;
use App\DTOs\QualificacaoTemporariaDTO;
use App\DTOs\EventoAuxilioDTO;
use App\DTOs\ConsignadoDTO;
use App\DTOs\QualificacaoPermanenteDTO;
use App\DTOs\FilhoDTO;

class SalaryCalculatorService
{
    /** Tabela de vencimento-base mais recente publicada (Res. CNJ 102, Anexo III-a, vigência 01/04/2026). */
    private const TABELA_VIGENTE = '2026-04';

    /** Última referência da carreira (Lei 3.687, Anexo III). */
    private const REFERENCIA_FINAL = 19;

    private array $salaryStructures = [];

    public function __construct()
    {
        $path = storage_path('app/salary_structures.json');
        if (file_exists($path)) {
            $this->salaryStructures = json_decode(file_get_contents($path), true) ?? [];
        }
    }

    /**
     * Look up base salary from CNJ structure tables or apply custom reajustes.
     */
    public function getBaseSalary(string $cargo, string $ref, string $mesAno, array $reajustes = [], int $reajusteMes = 5, float $reajustePadrao = 0.0): float
    {
        $simulatedDate = $mesAno . '-01'; // YYYY-MM-DD
        
        $structureKey = '2026-04'; // Default/latest structure
        if ($simulatedDate < '2025-03-01') {
            $structureKey = '2025-01';
        } elseif ($simulatedDate < '2026-01-01') {
            $structureKey = '2025-03';
        } elseif ($simulatedDate < '2026-04-01') {
            $structureKey = '2026-01';
        }

        $fullRef = $cargo . '-' . $ref;
        $baseSalary = $this->salaryStructures[$structureKey][$fullRef] ?? 0.0;

        // Fallback to latest structure if not found in target
        if ($baseSalary === 0.0) {
            $baseSalary = $this->salaryStructures['2026-04'][$fullRef] ?? 0.0;
        }

        // Apply future reajustes (for years >= 2027)
        $simulatedYear = (int) substr($mesAno, 0, 4);
        $simulatedMonth = (int) substr($mesAno, 5, 2);
        
        $lastAppliedYear = $simulatedMonth < $reajusteMes ? $simulatedYear - 1 : $simulatedYear;

        if ($lastAppliedYear >= 2027) {
            $multiplier = 1.0;
            for ($year = 2027; $year <= $lastAppliedYear; $year++) {
                // Ano sem percentual informado usa a estimativa padrão da data-base (Lei 3.687, art. 37-A)
                $pct = $reajustes[$year] ?? $reajustePadrao;
                $multiplier *= (1.0 + ($pct / 100.0));
            }
            $baseSalary *= $multiplier;
        }

        return round($baseSalary, 2);
    }

    /**
     * Calculate biennial functional progression level suffix.
     */
    public function getProgressionLevel(string $dtExercicio, string $referenciaInicial, string $mesAno): string
    {
        $startDate = new \DateTime($dtExercicio);
        $simDate = new \DateTime($mesAno . '-01');

        $startYear = (int) $startDate->format('Y');
        $startMonth = (int) $startDate->format('m');
        $simYear = (int) $simDate->format('Y');
        $simMonth = (int) $simDate->format('m');

        $totalMonths = ($simYear - $startYear) * 12 + ($simMonth - $startMonth);

        if ($totalMonths <= 0) {
            return $referenciaInicial;
        }

        $bienios = (int) floor($totalMonths / 24);
        
        $initialRefNum = (int) $referenciaInicial;
        $currentRefNum = $initialRefNum + $bienios;
        
        if ($currentRefNum > 19) {
            $currentRefNum = 19; // Cap at reference 19
        }

        return (string) $currentRefNum;
    }

    /**
     * Calculate ATS (quinquênio) percentage.
     */
    public function getAtsPercentage(string $dtExercicio, string $mesAno): float
    {
        $startDate = new \DateTime($dtExercicio);
        $simDate = new \DateTime($mesAno . '-01');

        $startYear = (int) $startDate->format('Y');
        $startMonth = (int) $startDate->format('m');
        $simYear = (int) $simDate->format('Y');
        $simMonth = (int) $simDate->format('m');

        $totalMonths = ($simYear - $startYear) * 12 + ($simMonth - $startMonth);

        if ($totalMonths <= 0) {
            return 0.0;
        }

        $yearsOfService = (int) floor($totalMonths / 12);

        if ($yearsOfService < 5) {
            return 0.0;
        }

        $quinquenios = (int) floor($yearsOfService / 5);
        $ats = 10.0 + 5.0 * ($quinquenios - 1);

        if ($ats > 40.0) {
            $ats = 40.0; // Max cap 40%
        }

        return $ats;
    }

    /**
     * Resolve RGPS/INSS pension ceiling.
     */
    public function getRgpsCeiling(int $year): float
    {
        if ($year <= 2025) {
            return 8157.41;
        }
        
        $baseCeiling = 8475.55; // 2026 ceiling
        if ($year > 2026) {
            for ($y = 2027; $y <= $year; $y++) {
                $baseCeiling *= 1.045; // 4.5% annual compound reajuste
            }
        }
        return round($baseCeiling, 2);
    }

    /**
     * Resolve CASSEMS rate based on number of dependents.
     */
    public function getCassemsRate(int $dependents): float
    {
        if ($dependents === 0) {
            return 0.06;
        } elseif ($dependents === 1) {
            return 0.07;
        } elseif ($dependents === 2) {
            return 0.0725;
        }
        return 0.075;
    }

    /**
     * Calculate IRRF using progressive tax brackets.
     */
    public function calculateIrrf(float $base, int $year, int $month = 12): float
    {
        if ($base <= 0.0) {
            return 0.0;
        }

        // A tabela com parcela a deduzir de 908,73 vale desde maio/2025 (conferido nos holerites de 05/2025 em diante)
        if ($year < 2025 || ($year === 2025 && $month < 5)) {
            if ($base <= 2259.20) {
                return 0.0;
            } elseif ($base <= 2828.65) {
                return round(($base * 0.075) - 169.44, 2);
            } elseif ($base <= 3751.05) {
                return round(($base * 0.15) - 381.59, 2);
            } elseif ($base <= 4664.68) {
                return round(($base * 0.225) - 662.92, 2);
            } else {
                return round(($base * 0.275) - 896.00, 2);
            }
        } else {
            if ($base <= 2428.80) {
                return 0.0;
            } elseif ($base <= 2826.65) {
                return round(($base * 0.075) - 182.16, 2);
            } elseif ($base <= 3751.05) {
                return round(($base * 0.15) - 394.16, 2);
            } elseif ($base <= 4664.68) {
                return round(($base * 0.225) - 675.49, 2);
            } else {
                return round(($base * 0.275) - 908.73, 2);
            }
        }
    }

    /**
     * Meses completos de exercício até o mês simulado.
     */
    public function getMesesDeExercicio(string $dtExercicio, string $mesAno): int
    {
        $start = new \DateTime($dtExercicio);
        $sim = new \DateTime($mesAno . '-01');

        return ((int) $sim->format('Y') - (int) $start->format('Y')) * 12
            + ((int) $sim->format('m') - (int) $start->format('m'));
    }

    /**
     * Avos da gratificação natalina no ano (Lei 3.310, arts. 89 e 90):
     * 1/12 por mês de exercício; fração de 15 dias ou mais conta como mês integral.
     * $ateMes limita a contagem (6 = avos adquiridos até junho, usados no adiantamento).
     */
    public function getAvosGratificacaoNatalina(string $dtExercicio, int $year, int $ateMes = 12): int
    {
        $start = new \DateTime($dtExercicio);
        $startYear = (int) $start->format('Y');

        if ($year < $startYear) {
            return 0;
        }
        if ($year > $startYear) {
            return $ateMes;
        }

        $startMonth = (int) $start->format('m');
        $diasNoMesDeEntrada = (int) $start->format('t') - (int) $start->format('d') + 1;

        return max(0, ($ateMes - $startMonth) + ($diasNoMesDeEntrada >= 15 ? 1 : 0));
    }

    /**
     * Gratificação natalina como o TJMS paga (conferido nos holerites de 2025 e 2026):
     *  - 1ª parcela em junho: avos adquiridos até junho sobre vencimento + adicionais permanentes
     *    (rubrica 406, com MS-PREV) e metade do AQ temporário (rubrica 460), sem IRRF;
     *  - 2ª parcela em novembro: o restante, com MS-PREV sobre a diferença e o IRRF
     *    de tributação exclusiva sobre o 13º inteiro.
     * O AQ temporário entra pelo valor cheio do mês, sem proporção de avos. A substituição
     * fica de fora por ser eventual. Não há desconto de CASSEMS sobre o 13º.
     */
    public function calcularGratificacaoNatalina(
        array $holerite,
        ServidorDTO $servidor,
        int $avos,
        int $year,
        int $parcela = 2,
        ?array $adiantamento = null
    ): array {
        $fracao = $avos / 12;
        $funcao = $holerite['proventos']['funcao_comissao'];

        $comPrevidencia = round($holerite['cassems_base'] * $fracao, 2);
        $teto = $servidor->teto_rgps ? $this->getRgpsCeiling($year) : INF;

        if ($parcela === 1) {
            // Metade de cada curso, truncada no centavo como na folha
            $semPrevidencia = round($funcao * $fracao, 2);
            foreach ($holerite['aq_temp_details'] as $aq) {
                $semPrevidencia += floor($aq['valor'] * 50) / 100;
            }
            $semPrevidencia = round($semPrevidencia, 2);
            $bruto = round($comPrevidencia + $semPrevidencia, 2);
            $msprev = round(min($comPrevidencia, $teto) * 0.14, 2);

            return [
                'parcela' => 1,
                'avos' => $avos,
                'com_previdencia' => $comPrevidencia,
                'sem_previdencia' => $semPrevidencia,
                'bruto' => $bruto,
                'msprev' => $msprev,
                'irrf' => 0.0,
                'liquido' => round($bruto - $msprev, 2),
            ];
        }

        $semPrevidencia = round($holerite['proventos']['aq_temporario'] + $funcao * $fracao, 2);
        $totalBruto = round($comPrevidencia + $semPrevidencia, 2);
        $totalMsprev = round(min($comPrevidencia, $teto) * 0.14, 2);

        $irrfBase = max(0.0, round($totalBruto - $totalMsprev - ($servidor->dependentes_irrf * 189.59), 2));
        $irrf = $this->calculateIrrf($irrfBase, $year, 11);

        $jaPagoComPrev = $adiantamento['com_previdencia'] ?? 0.0;
        $jaPagoSemPrev = $adiantamento['sem_previdencia'] ?? 0.0;
        $jaPagoMsprev = $adiantamento['msprev'] ?? 0.0;

        $bruto = round($totalBruto - $jaPagoComPrev - $jaPagoSemPrev, 2);
        $msprev = round(max(0.0, $totalMsprev - $jaPagoMsprev), 2);

        return [
            'parcela' => 2,
            'avos' => $avos,
            'com_previdencia' => round($comPrevidencia - $jaPagoComPrev, 2),
            'sem_previdencia' => round($semPrevidencia - $jaPagoSemPrev, 2),
            'bruto' => $bruto,
            'msprev' => $msprev,
            'irrf' => $irrf,
            'liquido' => round($bruto - $msprev - $irrf, 2),
            'total_bruto' => $totalBruto,
        ];
    }

    /**
     * Adicional de férias: um terço da remuneração do mês (Lei 3.310, art. 101), nas
     * rubricas 249 (vencimento + adicionais permanentes) e 296 (cargo/função/atividade).
     * Sem contribuição previdenciária; o IRRF é o acréscimo sobre o imposto do mês.
     */
    public function calcularAdicionalFerias(array $holerite, int $year, int $month = 12): array
    {
        $tercoPermanente = round($holerite['cassems_base'] / 3, 2);
        $tercoAtividade = round($holerite['proventos']['funcao_comissao'] / 3, 2);
        foreach ($holerite['aq_temp_details'] as $aq) {
            $tercoAtividade += round($aq['valor'] / 3, 2);
        }
        $bruto = round($tercoPermanente + $tercoAtividade, 2);

        $irrfComFerias = $this->calculateIrrf(round($holerite['irrf_base'] + $bruto, 2), $year, $month);
        $irrf = round(max(0.0, $irrfComFerias - $holerite['descontos']['irrf']), 2);

        return [
            'bruto' => $bruto,
            'irrf' => $irrf,
            'liquido' => round($bruto - $irrf, 2),
        ];
    }

    /**
     * Mapa da carreira: data em que cada referência e cada quinquênio são atingidos
     * (Lei 3.687, arts. 23 e 24; Lei 3.310, art. 95), com valores da tabela vigente.
     */
    public function gerarMapaCarreira(ServidorDTO $servidor, ?string $hojeMesAno = null): array
    {
        $hojeMesAno ??= date('Y-m');
        $tabela = $this->salaryStructures[self::TABELA_VIGENTE] ?? [];
        $refInicial = max(1, min(self::REFERENCIA_FINAL, (int) $servidor->referencia_inicial));
        $refAtual = (int) $this->getProgressionLevel($servidor->dt_exercicio, (string) $refInicial, $hojeMesAno);
        $baseInicial = (float) ($tabela[$servidor->cargo . '-' . $refInicial] ?? 0.0);

        $referencias = [];
        $anterior = null;
        for ($ref = $refInicial; $ref <= self::REFERENCIA_FINAL; $ref++) {
            $valor = (float) ($tabela[$servidor->cargo . '-' . $ref] ?? 0.0);
            $data = (new \DateTime($servidor->dt_exercicio))->modify('+' . (2 * ($ref - $refInicial)) . ' years');
            $referencias[] = [
                'ref' => $ref,
                'codigo' => $servidor->cargo . '-' . $ref,
                'data' => $data->format('Y-m-d'),
                'anos_de_casa' => 2 * ($ref - $refInicial),
                'vencimento' => $valor,
                'degrau_pct' => $anterior ? round(($valor / $anterior - 1) * 100, 2) : 0.0,
                'acumulado_pct' => $baseInicial > 0 ? round(($valor / $baseInicial - 1) * 100, 2) : 0.0,
                'status' => $ref < $refAtual ? 'atingida' : ($ref === $refAtual ? 'atual' : 'futura'),
            ];
            $anterior = $valor > 0 ? $valor : null;
        }

        $atsAtual = $this->getAtsPercentage($servidor->dt_exercicio, $hojeMesAno);
        $ats = [];
        for ($quinquenio = 1; $quinquenio <= 7; $quinquenio++) {
            $pct = 10.0 + 5.0 * ($quinquenio - 1);
            $data = (new \DateTime($servidor->dt_exercicio))->modify('+' . (5 * $quinquenio) . ' years');
            $ats[] = [
                'quinquenio' => $quinquenio,
                'pct' => $pct,
                'data' => $data->format('Y-m-d'),
                'status' => $pct <= $atsAtual ? 'atingida' : 'futura',
            ];
        }

        $proximaRef = null;
        foreach ($referencias as $r) {
            if ($r['status'] === 'futura') {
                $proximaRef = $r;
                break;
            }
        }
        $proximoAts = null;
        foreach ($ats as $a) {
            if ($a['status'] === 'futura') {
                $proximoAts = $a;
                break;
            }
        }

        $vencimentoAtual = (float) ($tabela[$servidor->cargo . '-' . $refAtual] ?? 0.0);
        $mesesAte = fn (string $data): int => max(0, $this->getMesesDeExercicio($hojeMesAno . '-01', substr($data, 0, 7)));

        return [
            'cargo' => $servidor->cargo,
            'tabela_vigente' => self::TABELA_VIGENTE,
            'referencia_atual' => $refAtual,
            'vencimento_atual' => $vencimentoAtual,
            'ats_atual_pct' => $atsAtual,
            'proxima_progressao' => $proximaRef ? [
                'codigo' => $proximaRef['codigo'],
                'data' => $proximaRef['data'],
                'meses_restantes' => $mesesAte($proximaRef['data']),
                'ganho' => round($proximaRef['vencimento'] - $vencimentoAtual, 2),
            ] : null,
            'proximo_ats' => $proximoAts ? [
                'pct' => $proximoAts['pct'],
                'data' => $proximoAts['data'],
                'meses_restantes' => $mesesAte($proximoAts['data']),
            ] : null,
            'topo' => $referencias[count($referencias) - 1],
            'referencias' => $referencias,
            'ats' => $ats,
        ];
    }

    /**
     * Compute a single-month holerite/payslip.
     */
    public function calcularHolerite(
        ServidorDTO $servidor,
        string $mesAno,
        array $auxiliosAtivos,
        array $aqTempAtivos,
        array $reajustes = []
    ): array {
        $year = (int) substr($mesAno, 0, 4);

        // 1. Resolve Progression Level & ATS
        $level = $this->getProgressionLevel($servidor->dt_exercicio, $servidor->referencia_inicial, $mesAno);
        $atsPct = $this->getAtsPercentage($servidor->dt_exercicio, $mesAno);

        // 2. Base Salary Lookup
        $baseSalary = $this->getBaseSalary($servidor->cargo, $level, $mesAno, $reajustes, $servidor->reajuste_mes, $servidor->reajuste_padrao_pct);

        // 3. Proventos (Tributáveis)
        $vencimento = $baseSalary;
        $substituicao = $servidor->salario_substituicao;
        $ats = round($baseSalary * ($atsPct / 100.0), 2);
        $aqPermanente = round($baseSalary * ($servidor->aq_permanente_pct / 100.0), 2);
        
        $aqTemporario = 0.0;
        $aqTempDetails = [];
        foreach ($aqTempAtivos as $aq) {
            $val = round($baseSalary * ($aq->percentual / 100.0), 2);
            $aqTemporario += $val;
            $aqTempDetails[] = [
                'nome' => $aq->nome,
                'percentual' => $aq->percentual,
                'valor' => $val
            ];
        }
        $aqTemporario = round($aqTemporario, 2);

        $tempoIntegral = $servidor->regime_integral ? round($baseSalary * 0.20, 2) : 0.0;
        $outrosAdicionais = round($baseSalary * ($servidor->outros_adicionais_pct / 100.0), 2);
        $funcaoComissao = $servidor->funcao_comissao_valor;

        $totalTributavel = round(
            $vencimento + $substituicao + $ats + $aqPermanente + $aqTemporario + $tempoIntegral + $outrosAdicionais + $funcaoComissao,
            2
        );

        // 4. Descontos Compulsórios
        // MS-PREV base = Vencimento Básico + ATS + AQ Permanente + Tempo Integral + Outros Adicionais (Excludes Substitution and Temp Qualification)
        $msprevBase = round($vencimento + $ats + $aqPermanente + $tempoIntegral + $outrosAdicionais, 2);
        if ($servidor->teto_rgps) {
            $ceiling = $this->getRgpsCeiling($year);
            if ($msprevBase > $ceiling) {
                $msprevBase = $ceiling;
            }
        }
        $msprev = round($msprevBase * 0.14, 2);

        // CASSEMS base = same as uncapped pension base
        $cassemsBase = round($vencimento + $ats + $aqPermanente + $tempoIntegral + $outrosAdicionais, 2);
        $cassemsRate = $this->getCassemsRate($servidor->dependentes_cassems);
        $cassemsPct = round($cassemsBase * $cassemsRate, 2);
        
        $fixedSpouse = $servidor->tem_conjuge ? 450.00 : 0.00;
        $fixedDeps = $servidor->dependentes_cassems * 35.00;
        $cassemsFixo = round($fixedSpouse + $fixedDeps, 2);
        $cassemsTotal = round($cassemsPct + $cassemsFixo, 2);

        // IRRF base = Gross (Total Tributável) - MS-PREV - Dependents Deduction (R$ 189.59 each)
        $irrfBase = round($totalTributavel - $msprev - ($servidor->dependentes_irrf * 189.59), 2);
        if ($irrfBase < 0.0) {
            $irrfBase = 0.0;
        }
        $irrf = $this->calculateIrrf($irrfBase, $year, (int) substr($mesAno, 5, 2));

        // Deduções particulares
        $consignados = $servidor->consignados;

        $totalDescontos = round($msprev + $irrf + $cassemsTotal + $consignados, 2);

        // 5. Auxílios (Isentos de impostos)
        $totalIsento = 0.0;
        foreach ($auxiliosAtivos as $tipo => $valor) {
            $totalIsento += $valor;
        }
        $totalIsento = round($totalIsento, 2);

        // 6. Líquido
        $salarioLiquido = round($totalTributavel - $totalDescontos + $totalIsento, 2);

        return [
            'mesAno' => $mesAno,
            'level' => $level,
            'atsPct' => $atsPct,
            'baseSalary' => $baseSalary,
            'proventos' => [
                'vencimento' => $vencimento,
                'substituicao' => $substituicao,
                'ats' => $ats,
                'aq_permanente' => $aqPermanente,
                'aq_temporario' => $aqTemporario,
                'tempo_integral' => $tempoIntegral,
                'outros_adicionais' => $outrosAdicionais,
                'funcao_comissao' => $funcaoComissao,
                'total_tributavel' => $totalTributavel
            ],
            'descontos' => [
                'msprev' => $msprev,
                'irrf' => $irrf,
                'cassems_pct' => $cassemsPct,
                'cassems_fixo' => $cassemsFixo,
                'cassems_total' => $cassemsTotal,
                'consignados' => $consignados,
                'total_descontos' => $totalDescontos
            ],
            'auxilios' => $auxiliosAtivos,
            'total_isento' => $totalIsento,
            'salario_liquido' => $salarioLiquido,
            'msprev_base' => $msprevBase,
            'cassems_base' => $cassemsBase,
            'irrf_base' => $irrfBase,
            'aq_temp_details' => $aqTempDetails
        ];
    }

    /**
     * Generate chronological salary projection over N years.
     */
    public function gerarProjecao(
        ServidorDTO $servidor,
        array $aqTempList,
        array $eventosAuxilios,
        array $reajustes,
        int $anos = 10,
        ?string $startMesAno = null,
        array $consignadosList = [],
        array $aqPermanenteList = [],
        array $filhosList = [],
        float $valorUnitarioCreche = 558.78
    ): array {
        // Start month defaults to current month or entry month
        if ($startMesAno === null) {
            $startMesAno = date('Y-m');
        }

        $startDate = new \DateTime($startMesAno . '-01');
        $totalMonths = $anos * 12;
        $projection = [];

        $prevLevel = null;
        $prevAtsPct = null;
        $prevAqPermPct = null;
        $prevActiveChildren = [];
        $prevActiveAqTemps = [];
        $prevYear = null;
        $adiantamentos13 = [];

        for ($m = 0; $m < $totalMonths; $m++) {
            $currentDate = clone $startDate;
            $currentDate->modify("+$m months");
            $mesAnoStr = $currentDate->format('Y-m');
            $year = (int) $currentDate->format('Y');

            // 1. Resolve Active Auxílios at this point in time
            $activeAuxilios = [];
            foreach ($eventosAuxilios as $evento) {
                if ($evento->mes_ano_inicio <= $mesAnoStr) {
                    if ($evento->mes_ano_fim !== null && $evento->mes_ano_fim < $mesAnoStr) {
                        continue;
                    }
                    if ($evento->acao === 'CRIAR' || $evento->acao === 'ALTERAR_VALOR') {
                        $activeAuxilios[$evento->tipo_auxilio] = $evento->valor;
                    } elseif ($evento->acao === 'CANCELAR' || $evento->acao === 'RETIRAR') {
                        unset($activeAuxilios[$evento->tipo_auxilio]);
                    }
                }
            }

            // Automate Auxílio Creche based on children if filhosList is provided
            $activeChildrenNames = [];
            if (!empty($filhosList)) {
                $eligibleCrecheCount = 0;
                foreach ($filhosList as $filho) {
                    $birthDate = new \DateTime($filho->dt_nascimento);
                    $projDate = new \DateTime($mesAnoStr . '-01');
                    
                    // Diff in months
                    $diffMonths = ((int)$projDate->format('Y') - (int)$birthDate->format('Y')) * 12 + 
                                  ((int)$projDate->format('m') - (int)$birthDate->format('m'));
                    
                    if ($diffMonths >= ($filho->idade_escola * 12) && $diffMonths < (6 * 12)) {
                        $activeChildrenNames[] = $filho->nome;
                        $eligibleCrecheCount++;
                    }
                }

                if ($eligibleCrecheCount > 0) {
                    $activeAuxilios['AUXILIO_CRECHE'] = round($eligibleCrecheCount * $valorUnitarioCreche, 2);
                } else {
                    unset($activeAuxilios['AUXILIO_CRECHE']);
                }
            } else {
                // If filhosList is empty, check if there is an active AUXILIO_CRECHE from timeline events
                // to include it in the active children list (so it shows in events or we can track it generic)
                if (isset($activeAuxilios['AUXILIO_CRECHE'])) {
                    $activeChildrenNames[] = "Dependente";
                }
            }

            // Apply annual reajuste to auxilios
            if ($servidor->reajuste_auxilio_pct > 0.0) {
                $startYear = (int) $startDate->format('Y');
                $yearsDiff = $year - $startYear;
                if ($yearsDiff > 0) {
                    $auxMultiplier = pow(1.0 + ($servidor->reajuste_auxilio_pct / 100.0), $yearsDiff);
                    foreach ($activeAuxilios as $tipo => $valor) {
                        $activeAuxilios[$tipo] = round($valor * $auxMultiplier, 2);
                    }
                }
            }

            // 2. Resolve Active Temporary Qualifications at this point in time
            $activeAqTemp = [];
            foreach ($aqTempList as $aq) {
                if ($aq->mes_inicio <= $mesAnoStr && $aq->mes_fim >= $mesAnoStr) {
                    $activeAqTemp[] = $aq;
                }
            }

            // Resolve Active AQ Permanente percentage
            $aqPermPct = $servidor->aq_permanente_pct;
            foreach ($aqPermanenteList as $aq) {
                if ($aq->mes_inicio <= $mesAnoStr) {
                    $aqPermPct += $aq->percentual;
                }
            }
            if ($aqPermPct > 20.0) {
                $aqPermPct = 20.0;
            }

            // Resolve Active Consignados
            $monthlyConsignados = 0.0;
            if (!empty($consignadosList)) {
                foreach ($consignadosList as $cons) {
                    if ($cons->mes_inicio <= $mesAnoStr) {
                        if ($cons->mes_fim === null || $cons->mes_fim >= $mesAnoStr) {
                            $monthlyConsignados += $cons->valor;
                        }
                    }
                }
            } else {
                $monthlyConsignados = $servidor->consignados;
            }

            // Clone and override servidor values for this month
            $servidorClone = clone $servidor;
            $servidorClone->aq_permanente_pct = $aqPermPct;
            $servidorClone->consignados = $monthlyConsignados;

            // 3. Compute single month holerite
            $holerite = $this->calcularHolerite(
                $servidorClone,
                $mesAnoStr,
                $activeAuxilios,
                $activeAqTemp,
                $reajustes
            );

            // 4. Generate events/flags
            $monthlyEvents = [];

            // Progression check
            $currLevel = $holerite['level'];
            if ($prevLevel !== null && $currLevel !== $prevLevel) {
                $monthlyEvents[] = [
                    'tipo' => 'progressao',
                    'descricao' => "⚡ Progressão para " . $servidor->cargo . "-" . $currLevel,
                    'cor' => 'purple'
                ];
            }

            // ATS check
            $currAtsPct = $holerite['atsPct'];
            if ($prevAtsPct !== null && $currAtsPct !== $prevAtsPct) {
                $monthlyEvents[] = [
                    'tipo' => 'ats',
                    'descricao' => "📈 ATS aumentou para " . number_format($currAtsPct, 0) . "%",
                    'cor' => 'blue'
                ];
            }

            // AQ Permanente check
            if ($prevAqPermPct !== null && $aqPermPct !== $prevAqPermPct) {
                $monthlyEvents[] = [
                    'tipo' => 'aq_permanente',
                    'descricao' => "🎓 AQ Permanente vai para " . number_format($aqPermPct, 1) . "%",
                    'cor' => 'indigo'
                ];
            }

            // Children Entry/Exit check
            foreach ($activeChildrenNames as $childName) {
                if (!in_array($childName, $prevActiveChildren)) {
                    $monthlyEvents[] = [
                        'tipo' => 'filho_entrada',
                        'descricao' => "👶 " . $childName . " inicia no Auxílio Creche",
                        'cor' => 'green'
                    ];
                }
            }
            foreach ($prevActiveChildren as $childName) {
                if (!in_array($childName, $activeChildrenNames)) {
                    $monthlyEvents[] = [
                        'tipo' => 'filho_saida',
                        'descricao' => "🚫 " . $childName . " sai do Auxílio Creche (6 anos)",
                        'cor' => 'red'
                    ];
                }
            }

            // AQ Temporario check
            $currActiveAqNames = array_map(fn($aq) => $aq->nome, $activeAqTemp);
            foreach ($currActiveAqNames as $aqName) {
                if (!in_array($aqName, $prevActiveAqTemps)) {
                    $monthlyEvents[] = [
                        'tipo' => 'aq_temp_inicio',
                        'descricao' => "🎓 Início AQ Temp: " . $aqName,
                        'cor' => 'emerald'
                    ];
                }
            }
            foreach ($prevActiveAqTemps as $aqName) {
                if (!in_array($aqName, $currActiveAqNames)) {
                    $monthlyEvents[] = [
                        'tipo' => 'aq_temp_fim',
                        'descricao' => "📉 Fim AQ Temp: " . $aqName,
                        'cor' => 'orange'
                    ];
                }
            }

            // Reajuste check
            $monthVal = (int) $currentDate->format('m');
            if ($monthVal === $servidor->reajuste_mes && $year >= 2027) {
                $reajustePct = $reajustes[$year] ?? $servidor->reajuste_padrao_pct;
                if ($reajustePct > 0) {
                    $monthlyEvents[] = [
                        'tipo' => 'reajuste',
                        'descricao' => "💵 Reajuste Geral: +" . number_format($reajustePct, 1) . "%",
                        'cor' => 'yellow'
                    ];
                }
            }

            // Auxilio Reajuste check (happens in January of each new year)
            if ($prevYear !== null && $year !== $prevYear) {
                if ($servidor->reajuste_auxilio_pct > 0.0) {
                    $monthlyEvents[] = [
                        'tipo' => 'reajuste_auxilio',
                        'descricao' => "📈 Auxílios Reajustados: +" . number_format($servidor->reajuste_auxilio_pct, 1) . "%",
                        'cor' => 'blue'
                    ];
                }
            }

            // Gratificação natalina (junho e novembro) e adicional de férias (mês escolhido)
            $extras = ['gratificacao_natalina' => null, 'adicional_ferias' => null];
            $liquidoExtras = 0.0;

            if ($servidor->incluir_13 && ($monthVal === 6 || $monthVal === 11)) {
                $avosAno = $this->getAvosGratificacaoNatalina($servidor->dt_exercicio, $year);
                $avosJunho = $this->getAvosGratificacaoNatalina($servidor->dt_exercicio, $year, 6);
                $decimo = null;

                if ($monthVal === 6 && $avosJunho > 0) {
                    $decimo = $this->calcularGratificacaoNatalina($holerite, $servidorClone, $avosJunho, $year, 1);
                    $adiantamentos13[$year] = $decimo;
                } elseif ($monthVal === 11 && $avosAno > 0) {
                    // Projeção iniciada depois de junho: estima o adiantamento com os valores de novembro
                    $adiantamento = $adiantamentos13[$year]
                        ?? ($avosJunho > 0 ? $this->calcularGratificacaoNatalina($holerite, $servidorClone, $avosJunho, $year, 1) : null);
                    $decimo = $this->calcularGratificacaoNatalina($holerite, $servidorClone, $avosAno, $year, 2, $adiantamento);
                }

                if ($decimo !== null) {
                    $extras['gratificacao_natalina'] = $decimo;
                    $liquidoExtras += $decimo['liquido'];
                    $monthlyEvents[] = [
                        'tipo' => 'decimo_terceiro',
                        'descricao' => "🎄 13º " . $decimo['parcela'] . "ª parcela (" . $decimo['avos'] . "/12): +R$ " . number_format($decimo['liquido'], 2, ',', '.') . " líq.",
                        'cor' => 'green'
                    ];
                }
            }

            // O terço sai na folha anterior ao gozo; por isso já aparece com onze meses de exercício
            if ($servidor->mes_ferias > 0 && $monthVal === $servidor->mes_ferias
                && $this->getMesesDeExercicio($servidor->dt_exercicio, $mesAnoStr) >= 11) {
                $ferias = $this->calcularAdicionalFerias($holerite, $year, $monthVal);
                $extras['adicional_ferias'] = $ferias;
                $liquidoExtras += $ferias['liquido'];
                $monthlyEvents[] = [
                    'tipo' => 'ferias',
                    'descricao' => "🏖️ 1/3 de férias: +R$ " . number_format($ferias['liquido'], 2, ',', '.') . " líq.",
                    'cor' => 'emerald'
                ];
            }

            $holerite['extras'] = $extras;
            $holerite['liquido_extras'] = round($liquidoExtras, 2);
            $holerite['liquido_total'] = round($holerite['salario_liquido'] + $liquidoExtras, 2);

            // Store values for next month comparison
            $prevLevel = $currLevel;
            $prevAtsPct = $currAtsPct;
            $prevAqPermPct = $aqPermPct;
            $prevActiveChildren = $activeChildrenNames;
            $prevActiveAqTemps = $currActiveAqNames;
            $prevYear = $year;

            $holerite['eventos'] = $monthlyEvents;
            $projection[] = $holerite;
        }

        return $projection;
    }
}
