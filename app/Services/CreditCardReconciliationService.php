<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cartao;
use App\Models\CartaoCompra;
use App\Models\CartaoParcela;
use App\Services\Ofx\OfxParserService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CreditCardReconciliationService
{
    /** Diferença máxima aceita entre fatura e sistema: o maior entre R$ 0,20 e 0,5% do valor. */
    public const TOLERANCIA_MINIMA = 0.20;
    public const TOLERANCIA_PERCENTUAL = 0.005;

    /** O lite respondeu mais rápido e, com o texto do PDF, completo; a validação pelo total cobre os demais. */
    protected const GEMINI_MODELOS = ['gemini-3.5-flash-lite', 'gemini-3.5-flash', 'gemini-3.6-flash', 'gemini-3.8-flash'];

    /** Incrementar ao mudar prompt/formato dos itens, para invalidar extrações em cache. */
    protected const CACHE_EXTRACAO_VERSAO = 'v1';

    protected OfxParserService $ofxParser;
    protected StatementImportService $statementImporter;

    public function __construct(
        ?OfxParserService $ofxParser = null,
        ?StatementImportService $statementImporter = null
    ) {
        $this->ofxParser = $ofxParser ?? new OfxParserService();
        $this->statementImporter = $statementImporter ?? new StatementImportService();
    }

    /**
     * Executa a conciliação completa entre o arquivo da fatura e os lançamentos do sistema.
     */
    public function reconcile(UploadedFile|string $file, Cartao $cartao, int $mes, int $ano): array
    {
        // 1. Extrair itens da fatura do arquivo enviado
        $faturaItens = $this->extractItemsFromFile($file);

        if (empty($faturaItens)) {
            return [
                'sucesso'  => false,
                'mensagem' => 'Não foi possível extrair lançamentos do arquivo da fatura. Verifique se o formato é válido (PDF, XLSX, CSV ou OFX).',
                'erros'    => ['Arquivo sem lançamentos legíveis'],
            ];
        }

        return $this->reconcileItems($faturaItens, $cartao, $mes, $ano);
    }

    /**
     * Reconcilia uma lista de itens já extraídos da fatura contra as parcelas do cartão.
     *
     * @param array<int, array{data: string, descricao: string, valor: float, tipo?: string, parcela?: string|null}> $faturaItens
     */
    public function reconcileItems(array $faturaItens, Cartao $cartao, int $mes, int $ano): array
    {
        // 2. Buscar parcelas do cartão no sistema para o mês/ano de vencimento
        $sistemaParcelas = CartaoParcela::whereHas('compra', function ($q) use ($cartao) {
                $q->where('cartao_id', $cartao->id);
            })
            ->whereMonth('data_vencimento', $mes)
            ->whereYear('data_vencimento', $ano)
            ->with('compra')
            ->get();

        // 3. Separar itens da fatura em despesas vs pagamentos/estornos
        $faturaDespesas = [];
        $faturaPagamentos = [];

        foreach ($faturaItens as $item) {
            if ($this->isCreditOrPayment($item['descricao'], (float)$item['valor'], $item['tipo'] ?? 'despesa')) {
                $faturaPagamentos[] = $item;
            } else {
                $faturaDespesas[] = $item;
            }
        }

        // 4. Cruzamento Bipartite Inteligente
        $matchedInvoiceIndices = [];
        $matchedSystemParcelaIds = [];

        $conciliados = [];
        $divergenciasValor = [];

        // PASSO 1: Match 100% Certeiro (Valor idêntico + Data compra próxima <= 4 dias + Similaridade de nome >= 25%)
        foreach ($faturaDespesas as $idx => $inv) {
            $invValor = (float) $inv['valor'];
            $invData = !empty($inv['data']) ? Carbon::parse($inv['data']) : null;
            $invDescNorm = $this->normalizeDescription($inv['descricao']);

            foreach ($sistemaParcelas as $parcela) {
                if (in_array($parcela->id, $matchedSystemParcelaIds, true)) {
                    continue;
                }

                $sysValor = (float) $parcela->valor_parcela;
                if (abs($invValor - $sysValor) > 0.01) {
                    continue;
                }

                $sysData = $parcela->compra ? Carbon::parse($parcela->compra->data_compra) : null;
                $diffDias = ($invData && $sysData) ? abs($invData->diffInDays($sysData, false)) : 999;

                $sysDescNorm = $this->normalizeDescription($parcela->compra?->descricao ?? '');
                $simScore = $this->calculateSimilarity($invDescNorm, $sysDescNorm);

                if ($diffDias <= 4 && $simScore >= 0.25) {
                    $matchedInvoiceIndices[] = $idx;
                    $matchedSystemParcelaIds[] = $parcela->id;

                    $conciliados[] = [
                        'status'         => 'certeiro',
                        'confianca'      => 100,
                        'fatura_item'    => $inv,
                        'sistema_item'   => $this->formatSystemParcela($parcela),
                        'diferenca_dias' => $diffDias,
                    ];
                    break;
                }
            }
        }

        // PASSO 2: Match Provável por Valor Exato e Período (mesmo valor exato, data razoável no mês)
        foreach ($faturaDespesas as $idx => $inv) {
            if (in_array($idx, $matchedInvoiceIndices, true)) {
                continue;
            }

            $invValor = (float) $inv['valor'];
            $invData = !empty($inv['data']) ? Carbon::parse($inv['data']) : null;
            $invDescNorm = $this->normalizeDescription($inv['descricao']);

            $bestCandidate = null;
            $bestScore = -INF;

            foreach ($sistemaParcelas as $parcela) {
                if (in_array($parcela->id, $matchedSystemParcelaIds, true)) {
                    continue;
                }

                $sysValor = (float) $parcela->valor_parcela;
                if (abs($invValor - $sysValor) > 0.01) {
                    continue;
                }

                $sysData = $parcela->compra ? Carbon::parse($parcela->compra->data_compra) : null;
                $diffDias = ($invData && $sysData) ? abs($invData->diffInDays($sysData, false)) : 999;
                $sysDescNorm = $this->normalizeDescription($parcela->compra?->descricao ?? '');
                $simScore = $this->calculateSimilarity($invDescNorm, $sysDescNorm);
                $parcelaOk = $this->parcelaCompativel($inv['parcela_info'] ?? null, $parcela);

                // Data próxima, alguma similaridade de texto ou mesmo nº de parcelas restantes
                if ($diffDias <= 12 || $simScore >= 0.35 || $parcelaOk) {
                    $score = $simScore * 100 + ($parcelaOk ? 60 : 0) - (min($diffDias, 60) * 2);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestCandidate = [
                            'parcela'  => $parcela,
                            'diffDias' => $diffDias,
                            'simScore' => $simScore,
                        ];
                    }
                }
            }

            if ($bestCandidate) {
                $matchedInvoiceIndices[] = $idx;
                $matchedSystemParcelaIds[] = $bestCandidate['parcela']->id;

                $confianca = (int) max(60, min(95, 70 + ($bestCandidate['simScore'] * 25) - ($bestCandidate['diffDias'] * 2)));

                $conciliados[] = [
                    'status'         => 'provavel',
                    'confianca'      => $confianca,
                    'fatura_item'    => $inv,
                    'sistema_item'   => $this->formatSystemParcela($bestCandidate['parcela']),
                    'diferenca_dias' => $bestCandidate['diffDias'],
                ];
            }
        }

        // PASSO 2.5: Tolerância de centavos (arredondamento de parcelas, IOF) desde que haja outra evidência:
        // mesmo nº de parcelas restantes, nome parecido ou data próxima
        foreach ($faturaDespesas as $idx => $inv) {
            if (in_array($idx, $matchedInvoiceIndices, true)) {
                continue;
            }

            $invValor = (float) $inv['valor'];
            $invData = !empty($inv['data']) ? Carbon::parse($inv['data']) : null;
            $invDescNorm = $this->normalizeDescription($inv['descricao']);
            $tolerancia = max(self::TOLERANCIA_MINIMA, $invValor * self::TOLERANCIA_PERCENTUAL);

            $bestCandidate = null;
            $bestScore = -INF;

            foreach ($sistemaParcelas as $parcela) {
                if (in_array($parcela->id, $matchedSystemParcelaIds, true)) {
                    continue;
                }

                $diffValor = $invValor - (float) $parcela->valor_parcela;
                if (abs($diffValor) > $tolerancia) {
                    continue;
                }

                $sysData = $parcela->compra ? Carbon::parse($parcela->compra->data_compra) : null;
                $diffDias = ($invData && $sysData) ? abs($invData->diffInDays($sysData, false)) : 999;
                $simScore = $this->calculateSimilarity($invDescNorm, $this->normalizeDescription($parcela->compra?->descricao ?? ''));
                $parcelaOk = $this->parcelaCompativel($inv['parcela_info'] ?? null, $parcela);

                if (!$parcelaOk && $simScore < 0.35 && $diffDias > 4) {
                    continue;
                }

                $score = $simScore * 100 + ($parcelaOk ? 60 : 0) - (min($diffDias, 60) * 2) - (abs($diffValor) / $tolerancia * 30);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestCandidate = compact('parcela', 'diffDias', 'simScore', 'parcelaOk', 'diffValor');
                }
            }

            if ($bestCandidate) {
                $matchedInvoiceIndices[] = $idx;
                $matchedSystemParcelaIds[] = $bestCandidate['parcela']->id;

                $evidencias = ($bestCandidate['parcelaOk'] ? 1 : 0) + ($bestCandidate['simScore'] >= 0.35 ? 1 : 0) + ($bestCandidate['diffDias'] <= 4 ? 1 : 0);

                $conciliados[] = [
                    'status'          => 'tolerancia',
                    'confianca'       => [1 => 65, 2 => 80, 3 => 90][$evidencias],
                    'fatura_item'     => $inv,
                    'sistema_item'    => $this->formatSystemParcela($bestCandidate['parcela']),
                    'diferenca_dias'  => $bestCandidate['diffDias'],
                    'diferenca_valor' => round($bestCandidate['diffValor'], 2),
                ];
            }
        }

        // PASSO 3: Divergência de Valor (Alta similaridade de nome e data próxima, mas valor diferente)
        foreach ($faturaDespesas as $idx => $inv) {
            if (in_array($idx, $matchedInvoiceIndices, true)) {
                continue;
            }

            $invValor = (float) $inv['valor'];
            $invData = !empty($inv['data']) ? Carbon::parse($inv['data']) : null;
            $invDescNorm = $this->normalizeDescription($inv['descricao']);

            foreach ($sistemaParcelas as $parcela) {
                if (in_array($parcela->id, $matchedSystemParcelaIds, true)) {
                    continue;
                }

                $sysData = $parcela->compra ? Carbon::parse($parcela->compra->data_compra) : null;
                $diffDias = ($invData && $sysData) ? abs($invData->diffInDays($sysData, false)) : 999;
                $sysDescNorm = $this->normalizeDescription($parcela->compra?->descricao ?? '');
                $simScore = $this->calculateSimilarity($invDescNorm, $sysDescNorm);

                if ($simScore >= 0.55 && $diffDias <= 5) {
                    $matchedInvoiceIndices[] = $idx;
                    $matchedSystemParcelaIds[] = $parcela->id;

                    $sysValor = (float) $parcela->valor_parcela;
                    $divergencia = $invValor - $sysValor;

                    $divergenciasValor[] = [
                        'fatura_item'    => $inv,
                        'sistema_item'   => $this->formatSystemParcela($parcela),
                        'valor_fatura'   => $invValor,
                        'valor_sistema'  => $sysValor,
                        'diferenca'      => $divergencia,
                        'similaridade'   => round($simScore * 100),
                        'diferenca_dias' => $diffDias,
                    ];
                    break;
                }
            }
        }

        // PASSO 4: Itens Faltando no Sistema (Na fatura, mas ausentes no sistema)
        $faltandoNoSistema = [];
        foreach ($faturaDespesas as $idx => $inv) {
            if (!in_array($idx, $matchedInvoiceIndices, true)) {
                $faltandoNoSistema[] = $inv;
            }
        }

        // PASSO 5: Itens Sobrando no Sistema (No sistema para este mês, mas não cobrados na fatura)
        $sobrandoNoSistema = [];
        foreach ($sistemaParcelas as $parcela) {
            if (!in_array($parcela->id, $matchedSystemParcelaIds, true)) {
                $sobrandoNoSistema[] = $this->formatSystemParcela($parcela);
            }
        }

        // 5. Cálculos Financeiros
        $totalFaturaDespesas = array_sum(array_column($faturaDespesas, 'valor'));
        $totalFaturaPagamentos = array_sum(array_column($faturaPagamentos, 'valor'));
        $totalFaturaLiquido = $totalFaturaDespesas - $totalFaturaPagamentos;

        $totalSistema = (float) $sistemaParcelas->sum('valor_parcela');
        $diferencaTotal = $totalFaturaDespesas - $totalSistema;

        $isAlinhado = abs($diferencaTotal) < 0.01 && empty($faltandoNoSistema) && empty($sobrandoNoSistema) && empty($divergenciasValor);

        $nomeMes = Carbon::create($ano, $mes, 1)->translatedFormat('F/Y');

        $aviso = null;
        if (count($conciliados) < count($faturaDespesas) * 0.2) {
            $aviso = $this->suggestOtherCard($cartao, $faturaDespesas, $mes, $ano, count($conciliados));
        }

        return [
            'sucesso'  => true,
            'mensagem' => 'Conciliação realizada com sucesso.',
            'aviso'    => $aviso,
            'cartao'   => [
                'id'              => $cartao->id,
                'nome'            => $cartao->nome,
                'bandeira'        => $cartao->bandeira ?? 'VISA',
                'limite'          => (float) $cartao->limite,
                'dia_fechamento'  => (int) $cartao->dia_fechamento,
                'dia_vencimento'  => (int) $cartao->dia_vencimento,
            ],
            'periodo'  => [
                'mes'      => $mes,
                'ano'      => $ano,
                'nome_mes' => ucfirst($nomeMes),
            ],
            'totais'   => [
                'fatura_despesas'   => round($totalFaturaDespesas, 2),
                'fatura_pagamentos' => round($totalFaturaPagamentos, 2),
                'fatura_liquido'    => round($totalFaturaLiquido, 2),
                'sistema'           => round($totalSistema, 2),
                'diferenca'         => round($diferencaTotal, 2),
                'is_alinhado'       => $isAlinhado,
            ],
            'contadores' => [
                'conciliados'         => count($conciliados),
                'divergencias_valor'  => count($divergenciasValor),
                'faltando_no_sistema' => count($faltandoNoSistema),
                'sobrando_no_sistema' => count($sobrandoNoSistema),
                'pagamentos_estornos' => count($faturaPagamentos),
                'total_fatura'        => count($faturaItens),
                'total_sistema'       => $sistemaParcelas->count(),
            ],
            'grupos'   => [
                'faltando_no_sistema' => $faltandoNoSistema,
                'sobrando_no_sistema' => $sobrandoNoSistema,
                'divergencias_valor'  => $divergenciasValor,
                'conciliados'         => $conciliados,
                'pagamentos_estornos' => $faturaPagamentos,
            ],
            'itens_extraidos' => $faturaItens,
        ];
    }

    /**
     * Extrai os lançamentos brutos do arquivo da fatura (PDF, XLSX, CSV, OFX).
     *
     * @return array<int, array{data: string, descricao: string, valor: float, tipo: string, parcela?: string|null}>
     */
    public function extractItemsFromFile(UploadedFile|string $file): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $originalName = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [];
        }

        // 1. OFX
        if ($ext === 'ofx') {
            return $this->extractFromOfx($filePath);
        }

        // 2. CSV
        if (in_array($ext, ['csv', 'txt'])) {
            $content = file_get_contents($filePath);
            $rows = $this->statementImporter->parseCsv($content);
            return $this->extractFromTabularRows($rows);
        }

        // 3. EXCEL (.xlsx)
        if ($ext === 'xlsx') {
            try {
                $rows = $this->statementImporter->parseXlsx($filePath);
                return $this->extractFromTabularRows($rows);
            } catch (Throwable $e) {
                Log::warning('Falha ao processar XLSX nativo de fatura: ' . $e->getMessage());
                return $this->extractWithGemini($filePath, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            }
        }

        // 4. PDF (.pdf)
        if ($ext === 'pdf') {
            return $this->extractFromPdf($filePath);
        }

        return [];
    }

    /**
     * Extrai transações de fatura a partir de arquivo OFX.
     */
    protected function extractFromOfx(string $filePath): array
    {
        try {
            $dto = $this->ofxParser->parse($filePath);
            $items = [];

            foreach ($dto->transactions as $tx) {
                $valor = abs($tx->amount);
                if ($valor <= 0) continue;

                $tipo = ($tx->amount > 0 || $tx->type === 'CREDIT') ? 'receita' : 'despesa';
                $data = $tx->date ? $tx->date->format('Y-m-d') : now()->toDateString();
                $desc = trim($tx->memo ?: 'Lançamento');

                $items[] = [
                    'data'         => $data,
                    'descricao'    => $desc,
                    'valor'        => $valor,
                    'tipo'         => $tipo,
                    'fitid'        => $tx->fitid,
                    'parcela_info' => $this->detectParcelaInfo($desc),
                ];
            }

            return $items;
        } catch (Throwable $e) {
            Log::error('Erro ao extrair OFX na conciliação: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Extrai transações a partir de linhas tabulares de CSV ou Excel.
     */
    protected function extractFromTabularRows(array $rows): array
    {
        if (count($rows) < 2) {
            return [];
        }

        $headerIdx = -1;
        $colDate = null;
        $colDesc = null;
        $colValue = null;
        $colType = null;

        foreach ($rows as $idx => $row) {
            if ($idx > 15) break;

            $dateCol = null;
            $descCol = null;
            $valCol = null;
            $typeCol = null;

            foreach ($row as $cIdx => $cell) {
                $clean = strtolower(trim((string)$cell));
                if (in_array($clean, ['data', 'dt', 'data transacao', 'data compra', 'data lancamento', 'data do lancamento'])) {
                    $dateCol = $cIdx;
                } elseif (in_array($clean, ['descricao', 'historico', 'estabelecimento', 'detalhe', 'transacao', 'lancamento', 'compra'])) {
                    $descCol = $cIdx;
                } elseif (in_array($clean, ['valor', 'valor (r$)', 'valor r$', 'valor da compra', 'quantia', 'total'])) {
                    $valCol = $cIdx;
                } elseif (in_array($clean, ['tipo', 'debito/credito', 'd/c', 'natureza'])) {
                    $typeCol = $cIdx;
                }
            }

            if ($dateCol !== null && $valCol !== null) {
                $headerIdx = $idx;
                $colDate = $dateCol;
                $colDesc = $descCol ?? ($dateCol === 0 ? 1 : 0);
                $colValue = $valCol;
                $colType = $typeCol;
                break;
            }
        }

        $items = [];
        $dataRows = ($headerIdx >= 0) ? array_slice($rows, $headerIdx + 1) : $rows;

        foreach ($dataRows as $r) {
            if (empty(array_filter($r, fn($v) => trim((string)$v) !== ''))) {
                continue;
            }

            $dateRaw = $colDate !== null ? ($r[$colDate] ?? '') : '';
            $descRaw = $colDesc !== null ? ($r[$colDesc] ?? '') : '';
            $valRaw  = $colValue !== null ? ($r[$colValue] ?? '') : '';

            $data = $this->parseDate(trim((string)$dateRaw));
            $valor = $this->parseCurrency(trim((string)$valRaw));

            if (!$data || $valor === null || abs($valor) <= 0) {
                continue;
            }

            $absValor = abs($valor);
            $tipo = ($valor > 0 && ($r[$colType] ?? '') === 'C') ? 'receita' : ($valor < 0 ? 'despesa' : 'despesa');

            $items[] = [
                'data'         => $data,
                'descricao'    => trim((string)$descRaw) ?: 'Compra no Cartão',
                'valor'        => $absValor,
                'tipo'         => $tipo,
                'parcela_info' => $this->detectParcelaInfo(trim((string)$descRaw)),
            ];
        }

        return $items;
    }

    /**
     * Extrai lançamentos de PDF (priorizando Gemini multimodal se disponível, com fallback FlateDecode local).
     */
    protected function extractFromPdf(string $filePath): array
    {
        $text = $this->extractPdfText($filePath);

        $apiKey = env('GEMINI_API_KEY', '');
        if (!empty($apiKey)) {
            $geminiItems = $this->extractWithGemini($filePath, 'application/pdf', $text);
            if (!empty($geminiItems)) {
                return $geminiItems;
            }
        }

        // Fallback local: Extração nativa de texto do PDF via streams FlateDecode
        if (!empty($text) && strlen($text) > 80) {
            $parsed = $this->parsePdfText($text);
            if (count($parsed) >= 2) {
                return $parsed;
            }
        }

        return [];
    }

    /**
     * Extrai texto bruto de um PDF via descompressão FlateDecode nativa.
     */
    public function extractPdfText(string $filePath): string
    {
        $content = @file_get_contents($filePath);
        if (!$content) return '';

        $text = '';
        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                $decompressed = @gzuncompress($stream);
                if (!$decompressed) $decompressed = $stream;

                if (preg_match_all('/\((.*?)\)\s*Tj/s', $decompressed, $tjs)) {
                    $text .= implode(' ', $tjs[1]) . "\n";
                }
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $decompressed, $tjs)) {
                    foreach ($tjs[1] as $tjBlock) {
                        if (preg_match_all('/\((.*?)\)/s', $tjBlock, $sub)) {
                            $text .= implode('', $sub[1]) . ' ';
                        }
                    }
                    $text .= "\n";
                }
            }
        }

        return $text;
    }

    /**
     * Parser de texto bruto de faturas PDF em português.
     */
    protected function parsePdfText(string $text): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $text);
        $items = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || strlen($trimmed) < 8) continue;

            // Padrão comum: DD/MM (ou DD/MM/AAAA) Estabelecimento R$ 123,45
            if (preg_match('/^(\d{2}[-\/.]\d{2}(?:[-\/.]\d{2,4})?)\s+(.+?)\s+(-?R?\$?\s*[\d.]*,\d{2})$/u', $trimmed, $m)) {
                $data = $this->parseDate($m[1]);
                $desc = trim($m[2]);
                $valor = $this->parseCurrency($m[3]);

                if ($data && $valor !== null && abs($valor) > 0) {
                    $items[] = [
                        'data'         => $data,
                        'descricao'    => $desc,
                        'valor'        => abs($valor),
                        'tipo'         => $valor < 0 ? 'despesa' : 'despesa',
                        'parcela_info' => $this->detectParcelaInfo($desc),
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Extração com IA Gemini para interpretar PDFs e planilhas de fatura com visão multimodal.
     */
    protected function extractWithGemini(string $filePath, string $mimeType, ?string $pdfText = null): array
    {
        $apiKey = env('GEMINI_API_KEY', '');
        if (empty($apiKey)) {
            Log::warning('GEMINI_API_KEY não configurada para interpretação de fatura.');
            return [];
        }

        // Reenviar a mesma fatura (outro cartão/mês) reaproveita a extração anterior
        $cacheKey = 'fatura_extracao:' . self::CACHE_EXTRACAO_VERSAO . ':' . hash_file('sha256', $filePath);
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached)) {
            Log::info('Extração da fatura reaproveitada do cache (' . count($cached) . ' itens).');
            return $cached;
        }

        // Faturas longas levam mais que os 30s padrão do PHP para o Gemini responder
        @set_time_limit(420);

        $prompt = "Você é um especialista em faturas de cartão de crédito brasileiras (Nubank, Santander, Inter, Itaú, Vuon, Bradesco, C6, etc.).
Analise o documento da fatura em anexo e extraia a lista completa de TODOS os lançamentos de compras, despesas, tarifas, anuidade, estornos e pagamentos.
Importante:
1. Se a data estiver em formato simplificado (ex: '15 OUT' ou '23/10'), infira a data no formato 'YYYY-MM-DD'.
2. Identifique se o lançamento é 'despesa', 'pagamento' ou 'estorno'.
3. Se houver anotação de parcela (ex: '02/05' ou '2/5'), preencha o campo 'parcela'.
4. Não omita nenhum lançamento: percorra todas as páginas e todas as seções (compras, parcelamentos, despesas, créditos) e todos os cartões/adicionais.
5. Preencha 'total_fatura' com o valor total desta fatura impresso no documento (ex: 'Total desta Fatura' ou 'Pagamento Total').
Retorne APENAS um JSON estrito (sem formatação markdown, sem comentários) com este formato:
{
    \"total_fatura\": 1234.56,
    \"itens\": [
        {
            \"data\": \"YYYY-MM-DD\",
            \"descricao\": \"Nome do estabelecimento ou lançamento\",
            \"valor\": 123.45,
            \"tipo\": \"despesa\",
            \"parcela\": \"02/05\"
        }
    ]
}";

        // O texto extraído localmente é menor que o PDF e rendeu extração mais completa; o arquivo fica como fallback
        $entradas = [];
        $textoLimpo = $pdfText !== null ? $this->prepararTextoPdf($pdfText) : '';
        if ($this->textoPdfUtilizavel($textoLimpo)) {
            $entradas['texto'] = [['text' => $prompt . "\n\nTexto extraído do PDF da fatura:\n" . $textoLimpo]];
        }
        $entradas['arquivo'] = [
            ['inlineData' => ['mimeType' => $mimeType, 'data' => base64_encode(file_get_contents($filePath))]],
            ['text' => $prompt],
        ];

        $rows = [];
        $melhorDiferenca = INF;

        foreach ($entradas as $entrada => $parts) {
            foreach (self::GEMINI_MODELOS as $model) {
                if (Cache::has("gemini_indisponivel:{$model}")) {
                    continue;
                }

                try {
                    $response = Http::withoutVerifying()
                        ->connectTimeout(10)
                        ->timeout(90)
                        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                            'contents' => [['parts' => $parts]],
                            'generationConfig' => [
                                'temperature'      => 0.1,
                                'maxOutputTokens'  => 65536,
                                'responseMimeType' => 'application/json',
                            ],
                        ]);
                } catch (Throwable $e) {
                    Log::warning("Gemini ({$model}) exceção na conciliação de fatura: " . $e->getMessage());
                    $this->marcarModeloIndisponivel($model, 0);
                    continue;
                }

                if (!$response->successful()) {
                    Log::warning("Gemini ({$model}) falhou na conciliação de fatura: status {$response->status()} " . substr($response->body(), 0, 200));
                    $this->marcarModeloIndisponivel($model, $response->status());
                    continue;
                }

                $rawText = '';
                foreach ($response->json('candidates.0.content.parts') ?? [] as $part) {
                    $rawText .= $part['text'] ?? '';
                }
                $rawText = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
                $rawText = preg_replace('/\s*```$/i', '', trim($rawText));

                $data = json_decode($rawText, true);
                $candidatos = $data['itens'] ?? $data['transacoes'] ?? $data['compras'] ?? (is_array($data) && isset($data[0]) ? $data : []);

                if (empty($candidatos)) {
                    Log::warning("Gemini ({$model}/{$entrada}) não retornou itens na conciliação de fatura.");
                    continue;
                }

                // Valida a extração contra o total impresso na fatura; se não bater, tenta o próximo modelo
                $totalFatura = abs((float) ($data['total_fatura'] ?? 0));
                $diferenca = $totalFatura > 0 ? abs($this->sumNetItems($candidatos) - $totalFatura) : 0.0;

                if ($diferenca < $melhorDiferenca) {
                    $melhorDiferenca = $diferenca;
                    $rows = $candidatos;
                }

                if ($diferenca <= 1.00) {
                    Log::info("Gemini ({$model}/{$entrada}) extraiu " . count($candidatos) . ' itens da fatura (total confere).');
                    break 2;
                }
                Log::warning("Gemini ({$model}/{$entrada}) extraiu " . count($candidatos) . " itens, mas a soma difere do total da fatura (R$ {$totalFatura}) em R$ " . round($diferenca, 2) . '.');
            }
        }

        if (empty($rows)) {
            return [];
        }

        try {
            $items = [];
            foreach ($rows as $row) {
                $valor = (float) abs($row['valor'] ?? 0);
                if ($valor <= 0) continue;

                $dataItem = !empty($row['data']) ? Carbon::parse($row['data'])->toDateString() : now()->toDateString();
                $desc = trim($row['descricao'] ?? 'Compra');

                $items[] = [
                    'data'         => $dataItem,
                    'descricao'    => $desc,
                    'valor'        => $valor,
                    'tipo'         => strtolower($row['tipo'] ?? 'despesa'),
                    'parcela_info' => $row['parcela'] ?? $this->detectParcelaInfo($desc),
                ];
            }

            // Só guarda extrações que bateram com o total, para não perpetuar uma leitura incompleta
            if ($melhorDiferenca <= 1.00 && !empty($items)) {
                Cache::put($cacheKey, $items, now()->addDays(30));
            }

            return $items;
        } catch (Throwable $e) {
            Log::error('Falha Gemini na conciliação: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Evita esperar por modelos sobrecarregados (503), sem cota (429) ou removidos (404) nas próximas chamadas.
     */
    protected function marcarModeloIndisponivel(string $model, int $status): void
    {
        $minutos = match (true) {
            $status === 404                 => 24 * 60,
            $status === 429                 => 2,
            $status === 0, $status >= 500   => 5,
            default                         => 0,
        };

        if ($minutos > 0) {
            Cache::put("gemini_indisponivel:{$model}", $status, now()->addMinutes($minutos));
        }
    }

    /**
     * Converte o texto cru dos operadores Tj/TJ (escapes octais WinAnsi, parênteses escapados) em UTF-8 legível.
     */
    protected function prepararTextoPdf(string $text): string
    {
        $text = preg_replace_callback('/\\\\([0-7]{1,3})/', fn($m) => chr(octdec($m[1]) & 0xFF), $text);
        $text = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $text);

        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }

        return trim(preg_replace('/[ \t]+/', ' ', $text));
    }

    /**
     * O texto só é útil se tiver volume e vários valores monetários (PDFs escaneados não têm camada de texto).
     */
    protected function textoPdfUtilizavel(string $text): bool
    {
        return strlen($text) >= 500 && preg_match_all('/\d,\d{2}\b/', $text) >= 5;
    }

    /**
     * Quando quase nada concilia, verifica se a fatura pertence a outro cartão do usuário
     * contando quantos valores da fatura batem com as parcelas de cada cartão no mesmo mês.
     */
    protected function suggestOtherCard(Cartao $cartao, array $faturaDespesas, int $mes, int $ano, int $conciliadosAtual): string
    {
        $valoresFatura = array_map(fn($i) => round((float) $i['valor'], 2), $faturaDespesas);
        $melhor = null;
        $melhorMatches = $conciliadosAtual;

        $outros = Cartao::where('user_id', $cartao->user_id)->where('id', '!=', $cartao->id)->get();
        foreach ($outros as $outro) {
            $valoresSistema = CartaoParcela::whereHas('compra', fn($q) => $q->where('cartao_id', $outro->id))
                ->whereMonth('data_vencimento', $mes)
                ->whereYear('data_vencimento', $ano)
                ->pluck('valor_parcela')
                ->map(fn($v) => round((float) $v, 2))
                ->all();

            $matches = 0;
            foreach ($valoresFatura as $v) {
                $pos = array_search($v, $valoresSistema, true);
                if ($pos !== false) {
                    $matches++;
                    unset($valoresSistema[$pos]);
                }
            }

            if ($matches > $melhorMatches) {
                $melhorMatches = $matches;
                $melhor = $outro;
            }
        }

        if ($melhor) {
            return "Poucos lançamentos conciliaram com o cartão {$cartao->nome}. Esta fatura parece ser do cartão {$melhor->nome} "
                . "({$melhorMatches} de " . count($faturaDespesas) . " valores batem). Refaça a conciliação selecionando o cartão {$melhor->nome}.";
        }

        return "Poucos lançamentos conciliaram com o cartão {$cartao->nome} neste mês. Verifique se o cartão e o mês de vencimento selecionados correspondem à fatura enviada.";
    }

    /**
     * Soma líquida dos itens brutos do Gemini (despesas - estornos/créditos), ignorando pagamentos de faturas anteriores.
     */
    protected function sumNetItems(array $rows): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $valor = abs((float) ($row['valor'] ?? 0));
            $tipo = strtolower($row['tipo'] ?? 'despesa');
            $norm = strtoupper(Str::ascii($row['descricao'] ?? ''));

            if ($tipo === 'pagamento' || str_contains($norm, 'PAGAMENTO')) {
                continue;
            }
            $total += $this->isCreditOrPayment($row['descricao'] ?? '', $valor, $tipo) ? -$valor : $valor;
        }
        return $total;
    }

    /**
     * Verifica se a linha é um pagamento de fatura anterior ou crédito/estorno.
     */
    public function isCreditOrPayment(string $desc, float $valor, string $tipo = 'despesa'): bool
    {
        if (in_array(strtolower($tipo), ['pagamento', 'estorno', 'receita', 'credito'])) {
            return true;
        }

        $norm = strtoupper(Str::ascii($desc));

        $keywords = [
            'PAGAMENTO RECEBIDO',
            'PAGAMENTO DE FATURA',
            'PAGTO FICHA COMPENSACAO',
            'PAGAMENTO TITULO',
            'PAGAMENTO VIA PIX',
            'PAGTO DEBITO CONTA',
            'CREDITO DE SALDO',
            'ESTORNO DE ANUIDADE',
            'ESTORNO ANUIDADE',
            'ESTORNO',
            'REVERSAO',
            'PAGAMENTO FATURA',
            'AUTOPAGAMENTO',
        ];

        foreach ($keywords as $kw) {
            if (str_contains($norm, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normaliza a descrição do estabelecimento para permitir matching robusto.
     */
    public function normalizeDescription(string $desc): string
    {
        $clean = strtoupper(Str::ascii($desc));

        // Remover prefixos comuns de maquininhas e adquirentes
        $clean = preg_replace('/^(PAG\*|MP\*|IFD\*|UBER\*|AMAZON\.COM\.BR\*|PAYPAL\*|STRIPE\*|SUMUP\*|STONE\*|CIELO\*|REDE\*|ZAP\*)/i', '', $clean);

        // Remover sufixos de parcelas (ex: "02/05", "PARC 2/5", "2/5")
        $clean = preg_replace('/\s+(?:PARC(?:ELA)?\s*)?0?\d{1,2}\s*\/\s*0?\d{1,2}$/i', '', $clean);

        // Remover caracteres especiais e múltiplos espaços
        $clean = preg_replace('/[^A-Z0-9\s]/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', trim($clean));

        return $clean;
    }

    /**
     * Calcula a similaridade entre duas descrições normalizadas (0.0 a 1.0).
     */
    public function calculateSimilarity(string $s1, string $s2): float
    {
        if ($s1 === '' || $s2 === '') {
            return 0.0;
        }

        if ($s1 === $s2) {
            return 1.0;
        }

        // Substring direta (ex: "NETFLIX" em "NETFLIX COM BR")
        if (str_contains($s1, $s2) || str_contains($s2, $s1)) {
            $minLen = min(strlen($s1), strlen($s2));
            $maxLen = max(strlen($s1), strlen($s2));
            return max(0.80, $minLen / $maxLen);
        }

        similar_text($s1, $s2, $percent);
        return $percent / 100.0;
    }

    /**
     * Detecta notação de parcela no texto (ex: "02/05" -> "2/5").
     */
    public function detectParcelaInfo(string $desc): ?string
    {
        if (preg_match('/(?:PARC(?:ELA)?\s*)?(0?[1-9]\d?)\s*\/\s*(0?[1-9]\d?)/i', $desc, $m)) {
            return (int)$m[1] . '/' . (int)$m[2];
        }
        return null;
    }

    /**
     * Compara a parcela da fatura (ex: "12/12") com a do sistema (ex: 7/7) pelo nº de parcelas restantes,
     * pois compras cadastradas no meio do parcelamento recebem numeração própria.
     */
    public function parcelaCompativel(?string $parcelaFatura, CartaoParcela $parcela): bool
    {
        if (!$parcelaFatura || !preg_match('/(\d{1,2})\s*\/\s*(\d{1,2})/', $parcelaFatura, $m)) {
            return false;
        }

        $compra = $parcela->compra;
        $totalSistema = (int) ($compra?->numero_parcelas ?? 1);
        if ($compra?->tipo !== 'parcelada' || $totalSistema < 2 || (int) $m[2] < 2) {
            return false;
        }

        return ((int) $m[2] - (int) $m[1]) === ($totalSistema - (int) $parcela->numero_parcela);
    }

    /**
     * Formata um modelo CartaoParcela para o retorno da conciliação.
     */
    protected function formatSystemParcela(CartaoParcela $parcela): array
    {
        $compra = $parcela->compra;

        $parcelaTexto = 'À vista';
        if ($compra?->tipo === 'parcelada') {
            $parcelaTexto = "{$parcela->numero_parcela}/{$compra->numero_parcelas}";
        } elseif ($compra?->tipo === 'recorrente') {
            $parcelaTexto = 'Recorrente';
        }

        return [
            'id'              => $parcela->id,
            'compra_id'       => $compra?->id,
            'data_compra'     => $compra?->data_compra ? Carbon::parse($compra->data_compra)->format('Y-m-d') : null,
            'data_vencimento' => $parcela->data_vencimento ? Carbon::parse($parcela->data_vencimento)->format('Y-m-d') : null,
            'descricao'       => $compra?->descricao ?? 'Sem descrição',
            'categoria'       => $compra?->categoria ?? 'Geral',
            'numero_parcela'  => $parcela->numero_parcela,
            'total_parcelas'  => $compra?->numero_parcelas ?? 1,
            'parcela_texto'   => $parcelaTexto,
            'valor_parcela'   => (float) $parcela->valor_parcela,
            'status'          => $parcela->status,
        ];
    }

    protected function parseCurrency(string $val): ?float
    {
        $val = trim($val);
        if ($val === '') return null;

        $negative = str_starts_with($val, '-') || str_ends_with($val, '-');
        $clean = preg_replace('/[^\d,.]/', '', $val);
        if ($clean === '') return null;

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            if (strrpos($clean, ',') > strrpos($clean, '.')) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                $clean = str_replace(',', '', $clean);
            }
        } elseif (str_contains($clean, ',')) {
            $clean = str_replace(',', '.', $clean);
        }

        $num = (float) $clean;
        return $negative ? -$num : $num;
    }

    protected function parseDate(string $val): ?string
    {
        $val = trim($val);
        if ($val === '') return null;

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                return Carbon::createFromFormat('Y-m-d', $val)->toDateString();
            }
            if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $val, $m)) {
                return Carbon::createFromDate((int)$m[3], (int)$m[2], (int)$m[1])->toDateString();
            }
            if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})$/', $val, $m)) {
                return Carbon::createFromDate(now()->year, (int)$m[2], (int)$m[1])->toDateString();
            }
        } catch (Throwable) {
            // Ignorar erro de parse
        }

        return null;
    }
}
