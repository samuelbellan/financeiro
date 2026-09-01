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
    public function getBaseSalary(string $cargo, string $ref, string $mesAno, array $reajustes = [], int $reajusteMes = 5): float
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
                $pct = $reajustes[$year] ?? 0.0;
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
    public function calculateIrrf(float $base, int $year): float
    {
        if ($base <= 0.0) {
            return 0.0;
        }

        if ($year <= 2025) {
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
        $baseSalary = $this->getBaseSalary($servidor->cargo, $level, $mesAno, $reajustes, $servidor->reajuste_mes);

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
        $irrf = $this->calculateIrrf($irrfBase, $year);

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
        string $startMesAno = null,
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
            if ($monthVal === $servidor->reajuste_mes) {
                $reajustePct = $reajustes[$year] ?? 0.0;
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
