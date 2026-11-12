<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conta;
use App\Models\Transacao;
use App\Services\Ofx\OfxImportService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;
use ZipArchive;

class StatementImportService
{
    protected ?OfxImportService $ofxService = null;

    public function __construct()
    {
        try {
            $this->ofxService = new OfxImportService(DB::connection()->getPdo());
        } catch (Throwable) {
            // Ignorar em ambientes sem DB conectado de imediato
        }
    }

    /**
     * Importa qualquer extrato bancário (PDF, XLSX, CSV, OFX).
     *
     * @param UploadedFile|string $file Arquivo recebido do upload ou caminho no disco
     * @param int $userId ID do usuário
     * @param int|null $forcedContaId Conta pré-selecionada manualmente (opcional)
     * @return array Resultado estruturado com estatísticas da importação
     */
    public function import(UploadedFile|string $file, int $userId, ?int $forcedContaId = null): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $originalName = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'sucesso'  => false,
                'mensagem' => 'Não foi possível ler o arquivo enviado.',
                'erros'    => ['Arquivo ilegível'],
            ];
        }

        // 1. OFX
        if ($ext === 'ofx') {
            return $this->importOfx($filePath, $userId, $forcedContaId);
        }

        // 2. CSV
        if (in_array($ext, ['csv', 'txt'])) {
            $content = file_get_contents($filePath);
            $rows = $this->parseCsv($content);
            return $this->processTabularRows($rows, $userId, $forcedContaId, 'CSV', $originalName);
        }

        // 3. EXCEL (.xlsx)
        if ($ext === 'xlsx') {
            try {
                $rows = $this->parseXlsx($filePath);
                return $this->processTabularRows($rows, $userId, $forcedContaId, 'EXCEL', $originalName);
            } catch (Throwable $e) {
                // Tenta fallback com Gemini se for planilha complexa
                return $this->importWithGemini($filePath, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $userId, $forcedContaId, 'EXCEL', $originalName, $e->getMessage());
            }
        }

        // 4. PDF (.pdf)
        if ($ext === 'pdf') {
            return $this->importPdf($filePath, $userId, $forcedContaId, $originalName);
        }

        return [
            'sucesso'  => false,
            'mensagem' => "Formato de arquivo '.{$ext}' não suportado. Utilize PDF, Excel (.xlsx), CSV ou OFX.",
            'erros'    => ['Formato inválido'],
        ];
    }

    /**
     * Importação de arquivo OFX delegada ao OfxImportService.
     */
    protected function importOfx(string $filePath, int $userId, ?int $forcedContaId): array
    {
        $service = $this->ofxService ?? new OfxImportService(DB::connection()->getPdo());
        $result = $service->import($filePath, $userId, $forcedContaId);

        return [
            'sucesso'             => $result->sucesso,
            'mensagem'            => $result->mensagem,
            'formato'             => 'OFX',
            'banco'               => $result->contaNome,
            'conta'               => $result->contaNome,
            'total_lidos'         => $result->totalEncontrados,
            'inseridos'           => $result->inseridos,
            'duplicados'          => $result->duplicadosIgnorados,
            'total_entradas'      => $result->totalCreditos,
            'total_saidas'        => $result->totalDebitos,
            'saldo_final'         => $result->saldoFinal,
            'erros'               => $result->erros,
        ];
    }

    /**
     * Parse de conteúdo CSV com detecção de delimitador e encoding.
     */
    public function parseCsv(string $content): array
    {
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $lines = preg_split("/\r\n|\n|\r/", $content);
        $firstLines = array_slice(array_filter($lines, fn($l) => trim($l) !== ''), 0, 8);

        $delimiters = [';', ',', "\t", '|'];
        $bestDelimiter = ';';
        $maxCount = 0;
        foreach ($delimiters as $delim) {
            $count = 0;
            foreach ($firstLines as $fl) {
                $count += substr_count($fl, $delim);
            }
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        $rows = [];
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        while (($data = fgetcsv($stream, 0, $bestDelimiter)) !== false) {
            if (!empty(array_filter($data, fn($v) => trim((string)$v) !== ''))) {
                $rows[] = array_map(fn($v) => trim((string)$v), $data);
            }
        }
        fclose($stream);

        return $rows;
    }

    /**
     * Parse nativo de arquivo Excel (.xlsx) usando ZipArchive e SimpleXML.
     */
    public function parseXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException("Não foi possível descompactar a planilha Excel.");
        }

        $sharedStrings = [];
        $sharedXmlContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXmlContent !== false) {
            $xml = @simplexml_load_string($sharedXmlContent);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $t = '';
                        foreach ($si->r as $r) {
                            $t .= (string)$r->t;
                        }
                        $sharedStrings[] = $t;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetContent === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                    $sheetContent = $zip->getFromIndex($i);
                    break;
                }
            }
        }

        $zip->close();

        if ($sheetContent === false) {
            throw new \RuntimeException("Nenhuma planilha válida encontrada no arquivo Excel.");
        }

        $sheetXml = @simplexml_load_string($sheetContent);
        if (!$sheetXml || !isset($sheetXml->sheetData)) {
            throw new \RuntimeException("Conteúdo da planilha vazio ou não reconhecido.");
        }

        $rows = [];
        foreach ($sheetXml->sheetData->row as $row) {
            $rowArray = [];
            foreach ($row->c as $cell) {
                $type = (string) $cell['t'];
                $val = (string) $cell->v;
                if ($type === 's' && isset($sharedStrings[(int)$val])) {
                    $cellValue = $sharedStrings[(int)$val];
                } else {
                    $cellValue = $val;
                }
                $rowArray[] = trim($cellValue);
            }
            if (!empty(array_filter($rowArray, fn($v) => $v !== ''))) {
                $rows[] = $rowArray;
            }
        }

        return $rows;
    }

    /**
     * Processa linhas tabulares (provenientes de CSV ou Excel).
     */
    public function processTabularRows(array $rows, int $userId, ?int $forcedContaId, string $formato, string $nomeArquivo): array
    {
        if (empty($rows)) {
            return [
                'sucesso'  => false,
                'mensagem' => 'O arquivo está vazio ou não contém dados legíveis.',
                'erros'    => ['Arquivo sem linhas válidas'],
            ];
        }

        // Identificar linha de cabeçalho
        $headerIndex = -1;
        $dateCol = null;
        $descCol = null;
        $amountCol = null;
        $typeCol = null;
        $docCol = null;

        foreach ($rows as $idx => $row) {
            $normalizedRow = array_map(fn($v) => strtolower($this->removeAccents((string)$v)), $row);

            $dCol = null; $dsCol = null; $aCol = null; $tCol = null; $dcCol = null;
            foreach ($normalizedRow as $cIdx => $cell) {
                if ($dCol === null && preg_match('/^(data|date|dt|data lancamento|data transacao|data movimento)$/i', $cell)) {
                    $dCol = $cIdx;
                }
                if ($dsCol === null && preg_match('/^(descricao|descrição|historico|histórico|memo|detalhe|lancamento|estabelecimento|titulo|identificacao)$/i', $cell)) {
                    $dsCol = $cIdx;
                }
                if ($aCol === null && preg_match('/^(valor|amount|quantia|total|valor r\$|valor \(r\$\))$/i', $cell)) {
                    $aCol = $cIdx;
                }
                if ($tCol === null && preg_match('/^(tipo|type|natureza|c\/d|operacao|operacao)$/i', $cell)) {
                    $tCol = $cIdx;
                }
                if ($dcCol === null && preg_match('/^(doc|documento|numero documento|num doc|fitid|id|identificador)$/i', $cell)) {
                    $dcCol = $cIdx;
                }
            }

            if ($dCol !== null && ($dsCol !== null || $aCol !== null)) {
                $headerIndex = $idx;
                $dateCol = $dCol;
                $descCol = $dsCol;
                $amountCol = $aCol;
                $typeCol = $tCol;
                $docCol = $dcCol;
                break;
            }
        }

        // Se não achou por nomes exatos, faz inferência pelas primeiras linhas
        if ($headerIndex === -1) {
            $headerIndex = 0;
            $sampleRow = $rows[0];
            foreach ($sampleRow as $cIdx => $val) {
                if ($dateCol === null && $this->isValidDateString($val)) {
                    $dateCol = $cIdx;
                } elseif ($amountCol === null && $this->isValidAmountString($val)) {
                    $amountCol = $cIdx;
                } elseif ($descCol === null && strlen($val) > 3 && !is_numeric($val)) {
                    $descCol = $cIdx;
                }
            }
        }

        if ($dateCol === null || $amountCol === null) {
            return [
                'sucesso'  => false,
                'mensagem' => 'Não foi possível identificar as colunas de Data e Valor no arquivo. Verifique o cabeçalho.',
                'erros'    => ['Colunas não identificadas'],
            ];
        }

        // Fallback para descrição se não mapeada
        if ($descCol === null) {
            foreach ($rows[$headerIndex] ?? [] as $cIdx => $v) {
                if ($cIdx !== $dateCol && $cIdx !== $amountCol) {
                    $descCol = $cIdx;
                    break;
                }
            }
            if ($descCol === null) $descCol = $dateCol;
        }

        $contaId = $this->resolveConta($userId, $forcedContaId, $nomeArquivo);
        $transacoes = [];
        $fitidSeen = [];

        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rawDate = $row[$dateCol] ?? '';
            $rawDesc = $row[$descCol] ?? '';
            $rawAmount = $row[$amountCol] ?? '';
            $rawType = $typeCol !== null ? ($row[$typeCol] ?? '') : '';
            $rawDoc = $docCol !== null ? ($row[$docCol] ?? '') : '';

            $parsedDate = $this->normalizeDate($rawDate);
            if (!$parsedDate) {
                continue;
            }

            $parsedAmountInfo = $this->normalizeAmountAndType($rawAmount, $rawType);
            if ($parsedAmountInfo['valor'] <= 0) {
                continue;
            }

            $descricao = trim($rawDesc) ?: 'Lançamento ' . $parsedDate;
            $fitid = $rawDoc ?: $this->stableFitid($parsedDate, $descricao, (float) $parsedAmountInfo['valor'], $parsedAmountInfo['tipo'], $fitidSeen);

            $transacoes[] = [
                'data'             => $parsedDate,
                'descricao'        => $descricao,
                'valor'            => $parsedAmountInfo['valor'],
                'tipo'             => $parsedAmountInfo['tipo'],
                'fitid'            => $fitid,
                'numero_documento' => $rawDoc,
            ];
        }

        return $this->persistTransactions($transacoes, $userId, $contaId, $formato);
    }

    /**
     * Importação de PDF bancário com IA Gemini e fallback local.
     */
    public function importPdf(string $filePath, int $userId, ?int $forcedContaId, string $nomeArquivo): array
    {
        $apiKey = env('GEMINI_API_KEY');

        // Se houver chave do Gemini configurada, usar visão multimodal da IA
        if (!empty($apiKey)) {
            $pdfBytes = file_get_contents($filePath);
            $base64 = base64_encode($pdfBytes);
            return $this->importWithGemini($base64, 'application/pdf', $userId, $forcedContaId, 'PDF', $nomeArquivo);
        }

        // Fallback: Extração nativa de texto do PDF via streams FlateDecode
        $text = $this->extractPdfText($filePath);
        if (!empty($text)) {
            $transacoes = $this->parseTextStatement($text);
            if (!empty($transacoes)) {
                $contaId = $this->resolveConta($userId, $forcedContaId, $nomeArquivo);
                return $this->persistTransactions($transacoes, $userId, $contaId, 'PDF');
            }
        }

        return [
            'sucesso'  => false,
            'mensagem' => 'Não foi possível extrair transações deste arquivo PDF. Dica: Para PDFs digitalizados ou protegidos, a chave GEMINI_API_KEY deve estar configurada no .env.',
            'erros'    => ['Falha na extração do PDF'],
        ];
    }

    /**
     * Envia documento para o Gemini Flash extrair todas as transações em JSON estruturado.
     */
    protected function importWithGemini(string $base64OrPath, string $mimeType, int $userId, ?int $forcedContaId, string $formato, string $nomeArquivo, ?string $fallbackError = null): array
    {
        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            return [
                'sucesso'  => false,
                'mensagem' => $fallbackError ?: 'Configuração do Gemini não encontrada para processar este documento.',
            ];
        }

        // Extratos longos levam mais que os 30s padrão do PHP para o Gemini responder
        @set_time_limit(300);

        $base64 = (str_starts_with($base64OrPath, '/') || preg_match('/^[A-Za-z]:/', $base64OrPath))
            ? base64_encode(file_get_contents($base64OrPath))
            : $base64OrPath;

        $prompt = "Você é um extrator financeiro especialista em extratos bancários e faturas de cartão brasileiros.
Analise o documento em anexo e extraia a lista completa de todas as transações financeiras.
Retorne APENAS um JSON estrito (sem markdown, sem explicações adicionais) com este formato exato:
{
    \"banco\": \"Nome do banco ou emissor (ex: Santander, Nubank, Itaú)\",
    \"conta\": \"Identificação da conta se visível\",
    \"saldo_final\": 0.00,
    \"transacoes\": [
        {
            \"data\": \"YYYY-MM-DD\",
            \"descricao\": \"Descrição limpa do lançamento\",
            \"valor\": 123.45,
            \"tipo\": \"despesa\" ou \"receita\",
            \"fitid\": \"número de documento / ID único se houver, ou null\"
        }
    ]
}";

        $models = ['gemini-3.5-flash', 'gemini-3.5-flash-lite', 'gemini-3.6-flash', 'gemini-3.8-flash'];
        $data = null;
        $algumaResposta = false;

        foreach ($models as $model) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(90)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'inlineData' => [
                                        'mimeType' => $mimeType,
                                        'data'     => $base64,
                                    ],
                                ],
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature'     => 0.1,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

                if (!$response->successful()) {
                    Log::warning("Gemini ({$model}) falhou na importação de extrato: status {$response->status()} " . substr($response->body(), 0, 200));
                    continue;
                }

                $algumaResposta = true;
                $rawText = '';
                foreach ($response->json('candidates.0.content.parts') ?? [] as $part) {
                    $rawText .= $part['text'] ?? '';
                }
                $rawText = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($rawText));
                $data = json_decode($rawText, true);

                if ($data && isset($data['transacoes'])) {
                    break;
                }
            } catch (Throwable $e) {
                Log::warning("Gemini ({$model}) exceção na importação de extrato: " . $e->getMessage());
            }
        }

        if (!$algumaResposta) {
            Log::error('Nenhum modelo Gemini disponível para importação de extrato.');
            return [
                'sucesso'  => false,
                'mensagem' => 'A IA não conseguiu processar o documento. Verifique se o arquivo não está corrompido.',
            ];
        }

        try {
            if (!$data || !isset($data['transacoes'])) {
                return [
                    'sucesso'  => false,
                    'mensagem' => 'Nenhuma transação foi detectada no documento.',
                ];
            }

            $bancoIdentificado = $data['banco'] ?? 'Extrato ' . $formato;
            $contaId = $this->resolveConta($userId, $forcedContaId, $bancoIdentificado);
            $parsedTrx = [];
            $fitidSeen = [];

            foreach ($data['transacoes'] as $idx => $t) {
                $valor = (float) abs($t['valor'] ?? 0);
                if ($valor <= 0) continue;

                $tipo = strtolower($t['tipo'] ?? 'despesa') === 'receita' ? 'receita' : 'despesa';
                $dt = !empty($t['data']) ? Carbon::parse($t['data'])->toDateString() : now()->toDateString();
                $desc = trim($t['descricao'] ?? 'Lançamento ' . $dt);
                $fitid = $t['fitid'] ?? $this->stableFitid($dt, $desc, $valor, $tipo, $fitidSeen);

                $parsedTrx[] = [
                    'data'             => $dt,
                    'descricao'        => $desc,
                    'valor'            => $valor,
                    'tipo'             => $tipo,
                    'fitid'            => $fitid,
                    'numero_documento' => $t['fitid'] ?? null,
                ];
            }

            $result = $this->persistTransactions($parsedTrx, $userId, $contaId, $formato);
            if (isset($data['saldo_final']) && is_numeric($data['saldo_final'])) {
                $result['saldo_final'] = (float) $data['saldo_final'];
            }
            return $result;

        } catch (Throwable $e) {
            Log::error('Exceção ao processar com Gemini: ' . $e->getMessage());
            return [
                'sucesso'  => false,
                'mensagem' => 'Erro na integração com a IA: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Persiste as transações normalizadas no banco de dados com deduplicação.
     */
    protected function persistTransactions(array $transacoes, int $userId, int $contaId, string $formato): array
    {
        $total = count($transacoes);
        if ($total === 0) {
            return [
                'sucesso'             => true,
                'mensagem'            => 'Nenhuma transação identificada para importação.',
                'formato'             => $formato,
                'total_lidos'         => 0,
                'inseridos'           => 0,
                'duplicados'          => 0,
                'total_entradas'      => 0.0,
                'total_saidas'        => 0.0,
                'saldo_final'         => null,
            ];
        }

        $conta = Conta::find($contaId);
        $contaNome = $conta ? $conta->nome : 'Conta Principal';

        $inseridos = 0;
        $duplicados = 0;
        $entradas = 0.0;
        $saidas = 0.0;

        $ocorrenciaNoArquivo = [];
        $inseridosPorChave = [];
        $fitidsDoArquivo = array_values(array_filter(array_column($transacoes, 'fitid')));
        $idsConciliados = [];
        $chavesDoArquivo = [];
        foreach ($transacoes as $t) {
            $chavesDoArquivo[\App\Services\TransactionDeduplicationService::chaveImportacao(
                (string) $t['data'], (float) $t['valor'], $t['tipo'], (string) $t['descricao']
            )] = true;
        }

        DB::beginTransaction();
        try {
            foreach ($transacoes as $t) {
                $chave = null;
                // Checagem 1: Por conta_id e fitid
                $exists = Transacao::where('conta_id', $contaId)
                    ->where('fitid', $t['fitid'])
                    ->exists();

                // Checagem 2: data + valor + tipo + descrição exata no mesmo usuário.
                // whereDate: a coluna `data` é gravada como "Y-m-d 00:00:00" no SQLite.
                // Conta ocorrências para não descartar lançamentos idênticos legítimos
                // no mesmo dia (ex.: dois Pix de mesmo valor) nem reimportar os já existentes.
                if (!$exists) {
                    $chave = "{$t['data']}|{$t['valor']}|{$t['tipo']}|{$t['descricao']}";
                    $ocorrenciaNoArquivo[$chave] = ($ocorrenciaNoArquivo[$chave] ?? 0) + 1;

                    $jaExistentes = Transacao::where('user_id', $userId)
                        ->whereDate('data', $t['data'])
                        ->where('valor', $t['valor'])
                        ->where('tipo', $t['tipo'])
                        ->where('descricao', $t['descricao'])
                        ->count() - ($inseridosPorChave[$chave] ?? 0);

                    $exists = $jaExistentes >= $ocorrenciaNoArquivo[$chave];
                }

                // Checagem 3: Deduplicação inteligente com lançamento manual pré-existente
                if (!$exists) {
                    $matchManual = \App\Services\TransactionDeduplicationService::findDuplicateForImport(
                        userId: $userId,
                        valor: (float) $t['valor'],
                        tipo: $t['tipo'],
                        dataStr: (string) $t['data'],
                        descricao: (string) $t['descricao'],
                        fitidsDoArquivo: $fitidsDoArquivo,
                        idsJaConciliados: $idsConciliados,
                        chavesDoArquivo: $chavesDoArquivo
                    );

                    if ($matchManual) {
                        $idsConciliados[] = $matchManual->id;
                        // Vincula e enriquece a transação existente com dados do extrato bancário
                        if (!$matchManual->conta_id && $contaId) {
                            $matchManual->conta_id = $contaId;
                        }
                        // O candidato nunca tem fitid presente no arquivo: adota o fitid atual
                        // para que próximas importações já casem pela checagem 1.
                        if (!empty($t['fitid'])) {
                            $matchManual->fitid = $t['fitid'];
                        }
                        if (empty($matchManual->numero_documento) && !empty($t['numero_documento'])) {
                            $matchManual->numero_documento = $t['numero_documento'];
                        }
                        if (empty($matchManual->tipo_transacao_ofx) && !empty($formato)) {
                            $matchManual->tipo_transacao_ofx = $formato;
                        }
                        if (empty($matchManual->memo)) {
                            $matchManual->memo = "Conciliado via {$formato} com extrato bancário";
                        }
                        $matchManual->save();

                        $duplicados++;
                        continue;
                    }
                }

                if ($exists) {
                    $duplicados++;
                    continue;
                }

                if (isset($chave)) {
                    $inseridosPorChave[$chave] = ($inseridosPorChave[$chave] ?? 0) + 1;
                }

                Transacao::create([
                    'user_id'            => $userId,
                    'conta_id'           => $contaId,
                    'descricao'          => $t['descricao'],
                    'valor'              => $t['valor'],
                    'tipo'               => $t['tipo'],
                    'data'               => $t['data'],
                    'data_hora'          => $t['data'] . ' 12:00:00',
                    'fitid'              => $t['fitid'],
                    'numero_documento'   => $t['numero_documento'] ?? null,
                    'tipo_transacao_ofx' => $formato,
                    'memo'               => "Importado via {$formato}",
                    'valor_original_ofx' => $t['valor'],
                ]);

                $inseridos++;
                if ($t['tipo'] === 'receita') {
                    $entradas += $t['valor'];
                } else {
                    $saidas += $t['valor'];
                }
            }

            if ($conta) {
                $conta->saldo_atual = (float)$conta->saldo_atual + $entradas - $saidas;
                $conta->data_ultimo_saldo = now();
                $conta->save();
            }

            DB::commit();

            return [
                'sucesso'             => true,
                'mensagem'            => "Extrato {$formato} importado com sucesso! {$inseridos} lançamentos novos cadastrados.",
                'formato'             => $formato,
                'banco'               => $contaNome,
                'conta'               => $contaNome,
                'total_lidos'         => $total,
                'inseridos'           => $inseridos,
                'duplicados'          => $duplicados,
                'total_entradas'      => $entradas,
                'total_saidas'        => $saidas,
                'saldo_final'         => $conta ? (float)$conta->saldo_atual : null,
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Erro ao persistir extrato {$formato}: " . $e->getMessage());
            return [
                'sucesso'  => false,
                'mensagem' => 'Erro ao salvar no banco de dados: ' . $e->getMessage(),
                'erros'    => [$e->getMessage()],
            ];
        }
    }

    /**
     * FITID determinístico para extratos sem identificador do banco.
     * Usa a ocorrência do lançamento no arquivo (1º, 2º Pix idêntico do dia...)
     * em vez da posição da linha, para que extratos com períodos sobrepostos
     * gerem o mesmo FITID para o mesmo lançamento.
     */
    protected function stableFitid(string $data, string $descricao, float $valor, string $tipo, array &$seen): string
    {
        $base = "{$data}_{$descricao}_{$valor}_{$tipo}";
        $seen[$base] = ($seen[$base] ?? 0) + 1;

        return hash('crc32b', "{$base}_{$seen[$base]}");
    }

    /**
     * Resolve a conta bancária existente ou cria uma conta amigável automaticamente.
     */
    protected function resolveConta(int $userId, ?int $forcedContaId, string $nomeIdentificador): int
    {
        if ($forcedContaId) {
            $conta = Conta::where('user_id', $userId)->find($forcedContaId);
            if ($conta) {
                return $conta->id;
            }
        }

        // Tentar encontrar conta padrão ou com nome semelhante
        $cleanName = preg_replace('/[^a-zA-Z0-9\s]/', '', pathinfo($nomeIdentificador, PATHINFO_FILENAME));
        $cleanName = trim($cleanName) ?: 'Conta Principal';

        $conta = Conta::where('user_id', $userId)->where('nome', $cleanName)->first();
        if ($conta) {
            return $conta->id;
        }

        // Buscar qualquer conta existente do usuário
        $conta = Conta::where('user_id', $userId)->first();
        if ($conta) {
            return $conta->id;
        }

        // Criar uma nova conta
        $nova = Conta::create([
            'user_id'     => $userId,
            'nome'        => $cleanName,
            'tipo'        => 'corrente',
            'moeda'       => 'BRL',
            'saldo_atual' => 0.00,
            'ativo'       => true,
        ]);

        return $nova->id;
    }

    /**
     * Normaliza formatos de data (DD/MM/YYYY, YYYY-MM-DD, serial Excel).
     */
    protected function normalizeDate(string|int|float $val): ?string
    {
        $val = trim((string)$val);
        if (empty($val)) return null;

        // Se for número serial do Excel (ex: 45590)
        if (is_numeric($val) && (int)$val > 30000 && (int)$val < 65000) {
            try {
                return Carbon::create(1899, 12, 30)->addDays((int)$val)->toDateString();
            } catch (Throwable) {}
        }

        // DD/MM/YYYY ou DD-MM-YYYY
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})/', $val, $m)) {
            $ano = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
            $mes = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $dia = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            return "{$ano}-{$mes}-{$dia}";
        }

        // YYYY-MM-DD
        if (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/', $val, $m)) {
            $mes = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $dia = str_pad($m[3], 2, '0', STR_PAD_LEFT);
            return "{$m[1]}-{$mes}-{$dia}";
        }

        try {
            return Carbon::parse($val)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Normaliza valor monetário e determina se é receita ou despesa.
     */
    protected function normalizeAmountAndType(string $val, string $rawType = ''): array
    {
        $clean = trim($val);
        $clean = preg_replace('/[^\d\,\.\-\+dDcCrR\$\s\(\)]/', '', $clean);

        $isNegative = str_contains($clean, '-') || str_contains($clean, '(');
        $hasDebitIndicator = preg_match('/\b(d|debito|débito)\b/i', $clean) || preg_match('/\b(d|debito|débito)\b/i', $rawType);
        $hasCreditIndicator = preg_match('/\b(c|credito|crédito)\b/i', $clean) || preg_match('/\b(c|credito|crédito)\b/i', $rawType);

        // Remover letras e caracteres extras
        $numStr = preg_replace('/[^0-9\,\.]/', '', $clean);

        // Se tem vírgula como separador decimal (formato brasileiro 1.234,56)
        if (str_contains($numStr, ',') && str_contains($numStr, '.')) {
            $numStr = str_replace('.', '', $numStr);
            $numStr = str_replace(',', '.', $numStr);
        } elseif (str_contains($numStr, ',')) {
            $numStr = str_replace(',', '.', $numStr);
        }

        $valor = (float) $numStr;

        $tipo = 'despesa';
        if ($hasCreditIndicator) {
            $tipo = 'receita';
        } elseif ($hasDebitIndicator || $isNegative) {
            $tipo = 'despesa';
        } else {
            // Se valor positivo sem indicador explícito, verifica se era positivo
            $tipo = $isNegative ? 'despesa' : 'receita';
        }

        return [
            'valor' => abs($valor),
            'tipo'  => $tipo,
        ];
    }

    protected function isValidDateString(string $val): bool
    {
        return preg_match('/\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}/', $val) === 1
            || preg_match('/\d{4}[\/\-\.]\d{1,2}[\/\-\.]\d{1,2}/', $val) === 1;
    }

    protected function isValidAmountString(string $val): bool
    {
        $clean = preg_replace('/[^\d\,\.]/', '', $val);
        return is_numeric(str_replace(',', '.', $clean)) && (float)str_replace(',', '.', $clean) > 0;
    }

    protected function removeAccents(string $string): string
    {
        return \Illuminate\Support\Str::ascii($string);
    }

    /**
     * Extrai texto de fluxos FlateDecode de um PDF em PHP puro.
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
     * Regex fallback para extratos em texto puro.
     */
    protected function parseTextStatement(string $text): array
    {
        $transacoes = [];
        $fitidSeen = [];
        $lines = explode("\n", $text);

        foreach ($lines as $idx => $line) {
            $line = trim($line);
            // Padrão: DD/MM/YYYY descrição valor D/C
            if (preg_match('/(\d{2}[\/\.]\d{2}[\/\.]\d{4})\s+(.+?)\s+([+-]?\s*[\d\.\,]+,\d{2})\s*([DCdc])?/', $line, $m)) {
                $dt = $this->normalizeDate($m[1]);
                $desc = trim($m[2]);
                $amtInfo = $this->normalizeAmountAndType($m[3], $m[4] ?? '');

                if ($dt && $amtInfo['valor'] > 0) {
                    $transacoes[] = [
                        'data'             => $dt,
                        'descricao'        => $desc,
                        'valor'            => $amtInfo['valor'],
                        'tipo'             => $amtInfo['tipo'],
                        'fitid'            => $this->stableFitid($dt, $desc, (float) $amtInfo['valor'], $amtInfo['tipo'], $fitidSeen),
                        'numero_documento' => null,
                    ];
                }
            }
        }

        return $transacoes;
    }
}
