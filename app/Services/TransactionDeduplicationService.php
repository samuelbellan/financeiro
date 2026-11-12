<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transacao;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionDeduplicationService
{
    /**
     * Termos e expressões regulares que identificam lançamentos padrão/genéricos de extratos bancários.
     */
    protected const GENERIC_BANK_PATTERNS = [
        '/\b(pix\s+(enviado|recebido|emitido|transf|open\s+finance|pagamento|agendado|chave|qr|troco))\b/i',
        '/\b(transf(er[eê]ncia)?\s+(enviada|recebida|mesma\s+titularidade|outros\s+bancos|ted|doc|pix))\b/i',
        '/\b(ted\b|doc\b|transfer[eê]ncia\s+eletr[oô]nica|ted\s+enviad[ao]|doc\s+enviad[ao])/i',
        '/\b(pagamento\s+(de\s+)?(t[ií]tulo|boleto|conta|fatura|servi[cç]o|concession[aá]ria|tributo|gps|darf))\b/i',
        '/\b(compra\s+(com\s+cart[aã]o\s+)?(no\s+)?d[eé]bito|cartao\s+debito|debito\s+elo|debito\s+visa|debito\s+master)\b/i',
        '/\b(saque\s+(banco24horas|terminal|caixa|eletr[oô]nico))\b/i',
        '/\b(tarifa\s+(banc[aá]ria|mensal|pacote|extrato|servi[cç]o))\b/i',
        '/\b(dep[oó]sito\s+(em\s+dinheiro|envelope|cheque|identificado))\b/i',
        '/\b(resgate\s+autom[aá]tico|aplica[cç][aã]o\s+autom[aá]tica)\b/i',
    ];

    /**
     * Verifica se uma descrição é um texto genérico emitido pelo extrato bancário.
     */
    public static function isGenericBankDescription(string $desc): bool
    {
        $d = trim(mb_strtolower($desc));
        if ($d === '' || $d === '-' || $d === 'pix' || $d === 'transferencia' || $d === 'ted' || $d === 'doc') {
            return true;
        }

        foreach (self::GENERIC_BANK_PATTERNS as $pattern) {
            if (preg_match($pattern, $d)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica compatibilidade entre duas descrições.
     */
    public static function areDescriptionsCompatible(string $descA, string $descB): bool
    {
        $dA = trim(mb_strtolower($descA));
        $dB = trim(mb_strtolower($descB));

        if ($dA === $dB) {
            return true;
        }

        // Se uma das duas é genérica do extrato bancário (ex: "Pix enviado" vs "Pix Marcelo")
        $aIsGeneric = self::isGenericBankDescription($dA);
        $bIsGeneric = self::isGenericBankDescription($dB);

        if ($aIsGeneric || $bIsGeneric) {
            // Se ambas mencionam "pix" ou "transf" ou "ted", compatibilidade alta
            $hasPixA = str_contains($dA, 'pix');
            $hasPixB = str_contains($dB, 'pix');
            if ($hasPixA && $hasPixB) {
                return true;
            }

            $hasTransfA = str_contains($dA, 'transf') || str_contains($dA, 'ted') || str_contains($dA, 'doc');
            $hasTransfB = str_contains($dB, 'transf') || str_contains($dB, 'ted') || str_contains($dB, 'doc');
            if ($hasTransfA && $hasTransfB) {
                return true;
            }

            // Se uma é genérica, ela é um candidato natural a ser a versão bancária da outra
            return true;
        }

        // Ambas não são genéricas: verificar tokens em comum relevantes (mais de 3 letras)
        $tokensA = array_filter(preg_split('/[\s,\.\-\(\)\/]+/', $dA), fn($t) => mb_strlen($t) > 3);
        $tokensB = array_filter(preg_split('/[\s,\.\-\(\)\/]+/', $dB), fn($t) => mb_strlen($t) > 3);

        $common = array_intersect($tokensA, $tokensB);
        if (!empty($common)) {
            return true;
        }

        // Similaridade textual relativa
        similar_text($dA, $dB, $percent);
        return $percent >= 60;
    }

    /**
     * Busca um lançamento manual pré-existente compatível durante a importação de extrato.
     * Evita a duplicação se o usuário já tiver lançado manualmente no app.
     */
    public static function findDuplicateForImport(
        int $userId,
        float $valor,
        string $tipo,
        string $dataStr,
        string $descricao,
        int $maxDays = 3,
        array $fitidsDoArquivo = [],
        array $idsJaConciliados = [],
        array $chavesDoArquivo = []
    ): ?Transacao {
        $targetDate = Carbon::parse($dataStr);
        $startDate = $targetDate->copy()->subDays($maxDays)->toDateString();
        $endDate = $targetDate->copy()->addDays($maxDays)->toDateString();

        $valMin = round($valor, 2) - 0.009;
        $valMax = round($valor, 2) + 0.009;

        // Candidatos do usuário com mesmo tipo e valor exato dentro do intervalo de dias:
        // manuais (sem fitid) ou com fitid que não corresponde a nenhuma linha do arquivo
        // (ex.: fitid legado gerado por versão antiga do importador, ou lançamento mesclado).
        // whereDate: a coluna `data` pode estar como "Y-m-d" ou "Y-m-d 00:00:00".
        $candidates = Transacao::where('user_id', $userId)
            ->where('tipo', $tipo)
            ->whereBetween('valor', [$valMin, $valMax])
            ->whereDate('data', '>=', $startDate)
            ->whereDate('data', '<=', $endDate)
            ->when(!empty($idsJaConciliados), fn($q) => $q->whereNotIn('id', $idsJaConciliados))
            ->where(function ($q) use ($fitidsDoArquivo) {
                $q->whereNull('fitid')->orWhere('fitid', '');
                if (!empty($fitidsDoArquivo)) {
                    $q->orWhereNotIn('fitid', $fitidsDoArquivo);
                }
            })
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $bestCandidate = null;
        $bestScore = -1;

        foreach ($candidates as $cand) {
            $candDate = Carbon::parse($cand->data);
            $diffDays = abs($targetDate->diffInDays($candDate, false));

            // Já é a cópia exata de outra linha do arquivo: pertence àquela linha
            $chaveCand = self::chaveImportacao($candDate->toDateString(), (float) $cand->valor, $cand->tipo, (string) $cand->descricao);
            if (isset($chavesDoArquivo[$chaveCand])) {
                continue;
            }

            if (!self::areDescriptionsCompatible($descricao, $cand->descricao)) {
                // Se as descrições não têm nada a ver e nenhuma é genérica, ignora
                continue;
            }

            // Cálculo de score de afinidade
            $score = 100 - ($diffDays * 15);

            // Bônus se tiver categoria cadastrada manualmente
            if (!empty($cand->categoria)) {
                $score += 15;
            }

            // Bônus se ambas mencionarem "pix"
            $hasPix1 = str_contains(mb_strtolower($descricao), 'pix');
            $hasPix2 = str_contains(mb_strtolower($cand->descricao), 'pix');
            if ($hasPix1 && $hasPix2) {
                $score += 10;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCandidate = $cand;
            }
        }

        return $bestCandidate;
    }

    /**
     * Chave de correspondência exata entre um lançamento e uma linha de extrato.
     */
    public static function chaveImportacao(string $data, float $valor, string $tipo, string $descricao): string
    {
        return $data . '|' . number_format($valor, 2, '.', '') . '|' . $tipo . '|' . trim($descricao);
    }

    /**
     * Detecta duplicidades em Conta Corrente entre lançamentos no período informado.
     */
    public static function detectDuplicates(
        int $userId,
        ?int $mes = null,
        ?int $ano = null,
        ?string $dataInicio = null,
        ?string $dataFim = null
    ): array {
        if (!$dataInicio || !$dataFim) {
            $mes = $mes ?? now()->month;
            $ano = $ano ?? now()->year;
            $dataInicio = Carbon::create($ano, $mes, 1)->startOfMonth()->subDays(4)->toDateString();
            $dataFim = Carbon::create($ano, $mes, 1)->endOfMonth()->addDays(4)->toDateString();
        } else {
            $dataInicio = Carbon::parse($dataInicio)->subDays(4)->toDateString();
            $dataFim = Carbon::parse($dataFim)->addDays(4)->toDateString();
        }

        $transacoes = Transacao::where('user_id', $userId)
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->with('conta')
            ->orderBy('data', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $duplicidades = [];
        $ignoredPairs = self::getIgnoredPairs($userId);
        $total = count($transacoes);

        for ($i = 0; $i < $total; $i++) {
            $tA = $transacoes[$i];

            for ($j = $i + 1; $j < $total; $j++) {
                $tB = $transacoes[$j];

                // 1. Mesmo tipo
                if ($tA->tipo !== $tB->tipo) {
                    continue;
                }

                // 2. Mesmo valor exato
                if (abs((float)$tA->valor - (float)$tB->valor) >= 0.01) {
                    continue;
                }

                // 3. Diferença de dias até 3 dias (ex: sábado a segunda-feira)
                $dateA = Carbon::parse($tA->data);
                $dateB = Carbon::parse($tB->data);
                $diffDays = abs($dateA->diffInDays($dateB, false));
                if ($diffDays > 3) {
                    continue;
                }

                // 4. Par ignorado pelo usuário
                $pairKey = self::makePairKey($tA->id, $tB->id);
                if (in_array($pairKey, $ignoredPairs, true)) {
                    continue;
                }

                // 5. Análise de descrições
                $aIsGeneric = self::isGenericBankDescription($tA->descricao);
                $bIsGeneric = self::isGenericBankDescription($tB->descricao);
                $areCompatible = self::areDescriptionsCompatible($tA->descricao, $tB->descricao);

                // Se ambas as descrições forem diferentes e NENHUMA for genérica do banco, não são duplicatas
                if (!$areCompatible && !$aIsGeneric && !$bIsGeneric) {
                    continue;
                }

                // 6. Decidir quem é a transação manual enriquecida e quem é o extrato genérico
                $scoreA = 0;
                $scoreB = 0;

                if (!empty($tA->categoria)) $scoreA += 25;
                if (!empty($tB->categoria)) $scoreB += 25;
                if (!empty($tA->subcategoria)) $scoreA += 10;
                if (!empty($tB->subcategoria)) $scoreB += 10;

                if (!$aIsGeneric && !empty($tA->descricao)) $scoreA += 20;
                if (!$bIsGeneric && !empty($tB->descricao)) $scoreB += 20;

                if (!empty($tA->fitid)) $scoreB += 15; // B ganha ponto de "manter a outra enriquecida"
                if (!empty($tB->fitid)) $scoreA += 15;

                // Transação sugerida para manter vs sugerida para excluir
                if ($scoreA >= $scoreB) {
                    $manter = $tA;
                    $excluir = $tB;
                } else {
                    $manter = $tB;
                    $excluir = $tA;
                }

                // Cálculo do score de confiança (0-100%)
                $confidence = 85;
                if ($diffDays === 0) $confidence += 10;
                elseif ($diffDays === 1) $confidence += 5;
                elseif ($diffDays >= 2) $confidence -= 5;

                if ($aIsGeneric || $bIsGeneric) $confidence += 5;
                if (str_contains(mb_strtolower($tA->descricao), 'pix') && str_contains(mb_strtolower($tB->descricao), 'pix')) {
                    $confidence += 5;
                }
                $confidence = min(99, max(60, $confidence));

                // Explicação textual amigável
                $diasTexto = $diffDays === 0
                    ? 'na mesma data'
                    : ($diffDays === 1 ? 'com 1 dia de intervalo' : "com {$diffDays} dias de intervalo (fim de semana/compensação)");

                $motivo = "Mesmo valor (R$ " . number_format((float)$tA->valor, 2, ',', '.') . "), {$diasTexto}";
                if ($aIsGeneric || $bIsGeneric) {
                    $motivo .= " e correspondência entre lançamento manual e extrato bancário";
                }

                $duplicidades[] = [
                    'par_key'        => $pairKey,
                    'score'          => $confidence,
                    'dias_diferenca' => $diffDays,
                    'motivo'         => $motivo,
                    't1'             => [
                        'id'          => $tA->id,
                        'data'        => $dateA->format('d/m/Y'),
                        'data_iso'    => $dateA->format('Y-m-d'),
                        'descricao'   => $tA->descricao,
                        'categoria'   => $tA->categoria ?? '-',
                        'subcategoria'=> $tA->subcategoria ?? '',
                        'valor'       => (float)$tA->valor,
                        'tipo'        => $tA->tipo,
                        'conta_nome'  => $tA->conta?->nome ?? 'Manual',
                        'origem'      => !empty($tA->fitid) ? 'Extrato Bancário' : 'Manual',
                        'fitid'       => $tA->fitid,
                        'is_generic'  => $aIsGeneric,
                    ],
                    't2'             => [
                        'id'          => $tB->id,
                        'data'        => $dateB->format('d/m/Y'),
                        'data_iso'    => $dateB->format('Y-m-d'),
                        'descricao'   => $tB->descricao,
                        'categoria'   => $tB->categoria ?? '-',
                        'subcategoria'=> $tB->subcategoria ?? '',
                        'valor'       => (float)$tB->valor,
                        'tipo'        => $tB->tipo,
                        'conta_nome'  => $tB->conta?->nome ?? 'Manual',
                        'origem'      => !empty($tB->fitid) ? 'Extrato Bancário' : 'Manual',
                        'fitid'       => $tB->fitid,
                        'is_generic'  => $bIsGeneric,
                    ],
                    'sugestao'       => [
                        'manter_id'   => $manter->id,
                        'excluir_id'  => $excluir->id,
                        'acao'        => 'mesclar',
                        'resumo'      => "Mesclar mantendo '{$manter->descricao}'" . ($manter->categoria ? " ({$manter->categoria})" : ''),
                    ],
                ];
            }
        }

        return $duplicidades;
    }

    /**
     * Mescla duas transações: preserva os dados enriquecidos (descrição e categoria),
     * vincula dados bancários do extrato (conta, fitid, memo) e remove a duplicata.
     */
    public static function mergeTransactions(int $keepId, int $duplicateId, int $userId): array
    {
        return DB::transaction(function () use ($keepId, $duplicateId, $userId) {
            $keep = Transacao::where('user_id', $userId)->findOrFail($keepId);
            $duplicate = Transacao::where('user_id', $userId)->findOrFail($duplicateId);

            // 1. Preservar categoria se a que vai ficar não tiver
            if (empty($keep->categoria) && !empty($duplicate->categoria)) {
                $keep->categoria = $duplicate->categoria;
                $keep->subcategoria = $duplicate->subcategoria;
                $keep->categoria_id = $duplicate->categoria_id;
                $keep->subcategoria_id = $duplicate->subcategoria_id;
            }

            // 2. Se a que vai ficar tem descrição genérica ("Pix enviado") e a outra tem específica ("Pix Marcelo"), preservar a específica!
            if (self::isGenericBankDescription($keep->descricao) && !self::isGenericBankDescription($duplicate->descricao)) {
                $keep->descricao = $duplicate->descricao;
            }

            // 3. Preparar dados bancários de extrato da duplicata para transferir
            $copyContaId = (!$keep->conta_id && $duplicate->conta_id) ? $duplicate->conta_id : null;
            $copyFitid = (empty($keep->fitid) && !empty($duplicate->fitid)) ? $duplicate->fitid : null;
            $copyTipoOfx = (empty($keep->tipo_transacao_ofx) && !empty($duplicate->tipo_transacao_ofx)) ? $duplicate->tipo_transacao_ofx : null;
            $copyDoc = (empty($keep->numero_documento) && !empty($duplicate->numero_documento)) ? $duplicate->numero_documento : null;
            $copyDataHora = (empty($keep->data_hora) && !empty($duplicate->data_hora)) ? $duplicate->data_hora : null;
            $copyValorOfx = (empty($keep->valor_original_ofx) && !empty($duplicate->valor_original_ofx)) ? $duplicate->valor_original_ofx : null;
            $memoDuplicata = "Mesclado com lançamento duplicado (ID #{$duplicate->id}: '{$duplicate->descricao}' de {$duplicate->data->format('d/m/Y')})";

            // 4. Excluir o lançamento duplicado primeiro para liberar índices UNIQUE (ex: uq_conta_fitid)
            $duplicate->delete();

            // 5. Aplicar os dados bancários na transação que fica
            if ($copyContaId) $keep->conta_id = $copyContaId;
            if ($copyFitid) $keep->fitid = $copyFitid;
            if ($copyTipoOfx) $keep->tipo_transacao_ofx = $copyTipoOfx;
            if ($copyDoc) $keep->numero_documento = $copyDoc;
            if ($copyDataHora) $keep->data_hora = $copyDataHora;
            if ($copyValorOfx) $keep->valor_original_ofx = $copyValorOfx;

            $keep->memo = $keep->memo ? "{$keep->memo} | {$memoDuplicata}" : $memoDuplicata;
            $keep->save();

            // 6. Remover da lista de ignorados se estava lá
            self::removeIgnoredPair($keepId, $duplicateId, $userId);

            return [
                'sucesso'   => true,
                'mensagem'  => "Lançamentos mesclados com sucesso! O lançamento duplicado foi removido.",
                'transacao' => $keep,
            ];
        });
    }

    /**
     * Mescla todas as duplicidades sugeridas de uma só vez.
     */
    public static function mergeAllSuggested(array $pairs, int $userId): array
    {
        $resolvidos = 0;
        $erros = [];

        foreach ($pairs as $p) {
            $manterId = (int)($p['sugestao']['manter_id'] ?? $p['manter_id'] ?? 0);
            $excluirId = (int)($p['sugestao']['excluir_id'] ?? $p['excluir_id'] ?? 0);

            if (!$manterId || !$excluirId) {
                continue;
            }

            try {
                self::mergeTransactions($manterId, $excluirId, $userId);
                $resolvidos++;
            } catch (\Throwable $e) {
                $erros[] = "Erro ao mesclar par {$manterId}-{$excluirId}: " . $e->getMessage();
            }
        }

        return [
            'sucesso'    => count($erros) === 0,
            'resolvidos' => $resolvidos,
            'erros'      => $erros,
            'mensagem'   => "{$resolvidos} pares de duplicidades mesclados com sucesso!",
        ];
    }

    /**
     * Ignora um par de transações para que não apareçam mais como suspeitas.
     */
    public static function ignorePair(int $idA, int $idB, int $userId): void
    {
        $pairs = self::getIgnoredPairs($userId);
        $pairKey = self::makePairKey($idA, $idB);

        if (!in_array($pairKey, $pairs, true)) {
            $pairs[] = $pairKey;
            Cache::put("duplicatas_ignoradas_{$userId}", $pairs, now()->addYear());
        }
    }

    /**
     * Remove um par da lista de ignorados.
     */
    public static function removeIgnoredPair(int $idA, int $idB, int $userId): void
    {
        $pairs = self::getIgnoredPairs($userId);
        $pairKey = self::makePairKey($idA, $idB);
        $pairs = array_values(array_filter($pairs, fn($k) => $k !== $pairKey));
        Cache::put("duplicatas_ignoradas_{$userId}", $pairs, now()->addYear());
    }

    /**
     * Retorna lista de chaves de pares ignorados.
     */
    protected static function getIgnoredPairs(int $userId): array
    {
        return Cache::get("duplicatas_ignoradas_{$userId}", []);
    }

    /**
     * Gera chave unificada para o par (sempre menor id primeiro).
     */
    protected static function makePairKey(int $idA, int $idB): string
    {
        $min = min($idA, $idB);
        $max = max($idA, $idB);
        return "{$min}_{$max}";
    }
}
