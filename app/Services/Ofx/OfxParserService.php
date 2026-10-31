<?php

declare(strict_types=1);

namespace App\Services\Ofx;

use App\Services\Ofx\DTO\OfxAccountDTO;
use App\Services\Ofx\DTO\OfxStatementDTO;
use App\Services\Ofx\DTO\OfxTransactionDTO;
use InvalidArgumentException;
use RuntimeException;

/**
 * Serviço de parsing de arquivos bancários em formato OFX (Open Financial Exchange).
 *
 * Suporta:
 * - Extratos de Conta Corrente (<BANKACCTFROM>, <BANKTRANLIST>, <STMTTRN>)
 * - Faturas de Cartão de Crédito (<CCACCTFROM>, <CCSTATEMENTDATA>, <CCSTMTRS>)
 * - Padrão SGML (OFX 1.x) com tags abertas (Santander, Bradesco, Inter)
 * - Padrão XML (OFX 2.x) com fechamento estrito
 * - Conversão de codificação (Windows-1252, ISO-8859-1 para UTF-8)
 * - Normalização de datas OFX com fuso horário (ex.: 20261024120000[-3:BRT])
 */
class OfxParserService
{
    /**
     * Faz o parse de um arquivo físico ou conteúdo em string OFX.
     *
     * @param string $contentOrPath Caminho do arquivo no disco ou o conteúdo OFX direto.
     * @return OfxStatementDTO
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function parse(string $contentOrPath): OfxStatementDTO
    {
        $rawContent = $this->loadRawContent($contentOrPath);

        if (trim($rawContent) === '') {
            throw new InvalidArgumentException('O conteúdo do arquivo OFX fornecido está vazio.');
        }

        // 1. Detectar e normalizar codificação para UTF-8
        $utf8Content = $this->normalizeEncoding($rawContent);

        // 2. Extrair cabeçalhos e corpo do OFX
        $body = $this->extractOfxBody($utf8Content);

        // 3. Detectar autonomamente o tipo de extrato (Conta vs Cartão)
        $tipoOfx = $this->detectOfxType($body);

        // 4. Extrair dados da conta / cartão
        $account = $this->parseAccount($body, $tipoOfx);

        // 5. Extrair lista de transações (<STMTTRN>)
        $transactions = $this->parseTransactions($body);

        return new OfxStatementDTO($account, $transactions);
    }

    /**
     * Carrega o conteúdo bruto a partir de um arquivo ou string.
     */
    protected function loadRawContent(string $contentOrPath): string
    {
        // Se for um arquivo existente no sistema de arquivos
        if (strlen($contentOrPath) < 4096 && @file_exists($contentOrPath) && @is_file($contentOrPath)) {
            $content = @file_get_contents($contentOrPath);
            if ($content === false) {
                throw new RuntimeException("Não foi possível ler o arquivo OFX no caminho: {$contentOrPath}");
            }
            return $content;
        }

        return $contentOrPath;
    }

    /**
     * Normaliza a codificação do arquivo para UTF-8.
     * Bancos brasileiros frequentemente utilizam CHARSET:1252 ou ISO-8859-1.
     */
    public function normalizeEncoding(string $rawContent): string
    {
        $encoding = null;

        // Procura no cabeçalho SGML: CHARSET:1252 ou CHARSET:ISO-8859-1 ou ENCODING:UTF-8
        if (preg_match('/CHARSET:(?:CP|WINDOWS-)?(\d+|[A-Z0-9_-]+)/i', $rawContent, $matches)) {
            $charset = strtoupper(trim($matches[1]));
            if ($charset === '1252' || $charset === 'CP1252' || $charset === 'WINDOWS-1252') {
                $encoding = 'Windows-1252';
            } elseif ($charset === 'ISO-8859-1' || $charset === '8859-1') {
                $encoding = 'ISO-8859-1';
            } elseif ($charset === 'UTF-8' || $charset === 'UTF8') {
                $encoding = 'UTF-8';
            }
        }

        if (preg_match('/ENCODING:([A-Z0-9_-]+)/i', $rawContent, $matches)) {
            $enc = strtoupper(trim($matches[1]));
            if ($enc === 'UTF-8' || $enc === 'UTF8') {
                $encoding = 'UTF-8';
            }
        }

        // Se já for UTF-8 válido
        if ($encoding === 'UTF-8' || (empty($encoding) && mb_check_encoding($rawContent, 'UTF-8'))) {
            return $rawContent;
        }

        // Tentar converter de Windows-1252 ou ISO-8859-1
        $sourceEncoding = $encoding ?? 'Windows-1252';
        $converted = @mb_convert_encoding($rawContent, 'UTF-8', $sourceEncoding);

        return ($converted !== false) ? $converted : $rawContent;
    }

    /**
     * Isola o corpo SGML/XML a partir da primeira tag <OFX>.
     */
    protected function extractOfxBody(string $content): string
    {
        $pos = stripos($content, '<OFX>');
        if ($pos !== false) {
            return substr($content, $pos);
        }

        // Se não houver <OFX> explícito (alguns arquivos mal formatados), retorna o conteúdo inteiro
        return $content;
    }

    /**
     * Detecta autonomamente se o extrato é de Conta Corrente ou Cartão de Crédito.
     */
    public function detectOfxType(string $body): string
    {
        $bodyUpper = strtoupper($body);

        // Tags exclusivas de fatura de cartão de crédito
        if (
            str_contains($bodyUpper, '<CCACCTFROM>') ||
            str_contains($bodyUpper, '<CCSTATEMENTDATA>') ||
            str_contains($bodyUpper, '<CCSTMTRS>') ||
            str_contains($bodyUpper, '<CREDITCARDMSGSRSV1>')
        ) {
            return 'cartao_credito';
        }

        // Tags de conta corrente / bancária
        if (
            str_contains($bodyUpper, '<BANKACCTFROM>') ||
            str_contains($bodyUpper, '<BANKTRANLIST>') ||
            str_contains($bodyUpper, '<STMTRS>') ||
            str_contains($bodyUpper, '<BANKMSGSRSV1>')
        ) {
            return 'conta_corrente';
        }

        return 'conta_corrente';
    }

    /**
     * Extrai metadados da conta ou cartão.
     */
    protected function parseAccount(string $body, string $tipoOfx): OfxAccountDTO
    {
        $bancoId = $this->extractTagValue($body, 'BANKID');
        $agencia = $this->extractTagValue($body, 'BRANCHID');
        $numeroConta = $this->extractTagValue($body, 'ACCTID');
        $tipoConta = $this->extractTagValue($body, 'ACCTTYPE') ?? ($tipoOfx === 'cartao_credito' ? 'CREDITCARD' : 'CHECKING');
        $moeda = $this->extractTagValue($body, 'CURDEF') ?? 'BRL';

        // Saldo final (<LEDGERBAL> -> <BALAMT>)
        $saldoFinal = null;
        $dataSaldo = null;
        if (preg_match('/<LEDGERBAL>([\s\S]*?)<\/LEDGERBAL>/i', $body, $ledgerMatch)) {
            $ledgerBlock = $ledgerMatch[1];
            $rawBal = $this->extractTagValue($ledgerBlock, 'BALAMT');
            if ($rawBal !== null) {
                $saldoFinal = $this->normalizeAmount($rawBal);
            }
            $rawDtAsOf = $this->extractTagValue($ledgerBlock, 'DTASOF');
            if ($rawDtAsOf !== null) {
                $dataSaldo = $this->normalizeDate($rawDtAsOf);
            }
        }

        // Período (<DTSTART>, <DTEND>)
        $rawDtStart = $this->extractTagValue($body, 'DTSTART');
        $rawDtEnd = $this->extractTagValue($body, 'DTEND');

        $dataInicio = $rawDtStart ? $this->normalizeDate($rawDtStart) : null;
        $dataFim = $rawDtEnd ? $this->normalizeDate($rawDtEnd) : null;

        return new OfxAccountDTO(
            tipoOfx: $tipoOfx,
            bancoId: $bancoId,
            agencia: $agencia,
            numeroConta: $numeroConta,
            tipoConta: strtoupper($tipoConta),
            moeda: strtoupper($moeda),
            saldoFinal: $saldoFinal,
            dataSaldo: $dataSaldo,
            dataInicio: $dataInicio,
            dataFim: $dataFim
        );
    }

    /**
     * Extrai todas as transações (<STMTTRN>...</STMTTRN>) do corpo OFX.
     *
     * @param string $body
     * @return OfxTransactionDTO[]
     */
    protected function parseTransactions(string $body): array
    {
        $transactions = [];

        // Isola blocos <STMTTRN>...</STMTTRN> (compatível com SGML e XML)
        // Usamos regex robusto para capturar tanto com fechamento quanto unclosed
        if (preg_match_all('/<STMTTRN>([\s\S]*?)(?:<\/STMTTRN>|(?=<STMTTRN>|<\/BANKTRANLIST>|<\/CCSTATEMENTDATA>|<\/CCSTMTRS>|<\/STMTRS>|$))/i', $body, $matches)) {
            foreach ($matches[1] as $index => $trnBlock) {
                $trnBlock = trim($trnBlock);
                if (empty($trnBlock)) {
                    continue;
                }

                $fitid = $this->extractTagValue($trnBlock, 'FITID');
                $rawDate = $this->extractTagValue($trnBlock, 'DTPOSTED');
                $rawAmount = $this->extractTagValue($trnBlock, 'TRNAMT');

                // FITID, DTPOSTED e TRNAMT são indispensáveis
                if (empty($fitid) && empty($rawDate) && empty($rawAmount)) {
                    continue;
                }

                // Fallback de FITID caso algum banco emita vazio (gera hash determinístico)
                if (empty($fitid)) {
                    $fitid = 'GEN_' . md5($rawDate . '_' . $rawAmount . '_' . $index);
                }

                $dtPosted = $this->normalizeDate($rawDate ?? date('Ymd'));
                $valorOriginal = $this->normalizeAmount($rawAmount ?? '0.00');
                $valorAbsoluto = round(abs($valorOriginal), 2);
                $tipo = $valorOriginal >= 0 ? 'receita' : 'despesa';

                $trntype = strtoupper($this->extractTagValue($trnBlock, 'TRNTYPE') ?? ($tipo === 'receita' ? 'CREDIT' : 'DEBIT'));
                $checknum = $this->extractTagValue($trnBlock, 'CHECKNUM');
                $refnum = $this->extractTagValue($trnBlock, 'REFNUM');
                $rawMemo = $this->extractTagValue($trnBlock, 'MEMO');
                $rawName = $this->extractTagValue($trnBlock, 'NAME');

                // Higienização de descrição
                $descricao = $this->resolveDescription($rawMemo, $rawName);

                $transactions[] = new OfxTransactionDTO(
                    fitid: $fitid,
                    tipo: $tipo,
                    trntype: $trntype,
                    data: $dtPosted,
                    valor: $valorAbsoluto,
                    valorOriginal: $valorOriginal,
                    descricao: $descricao,
                    memo: $this->cleanString($rawMemo),
                    name: $this->cleanString($rawName),
                    checknum: $this->cleanString($checknum),
                    refnum: $this->cleanString($refnum)
                );
            }
        }

        return $transactions;
    }

    /**
     * Extrai o valor de uma tag específica, suportando tags fechadas (<TAG>val</TAG>)
     * e tags abertas no estilo SGML (<TAG>val\n<OUTRA_TAG>).
     */
    protected function extractTagValue(string $block, string $tagName): ?string
    {
        // 1. Tenta tag estrita com fechamento: <TAG>valor</TAG>
        if (preg_match('/<' . $tagName . '[^>]*>([^<]+)<\/' . $tagName . '>/i', $block, $matches)) {
            return trim($matches[1]);
        }

        // 2. Tenta tag aberta SGML: <TAG>valor até a próxima quebra ou próximo tag '<'
        if (preg_match('/<' . $tagName . '[^>]*>([^\r\n<]+)/i', $block, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Converte datas do formato OFX para o padrão MySQL 'YYYY-MM-DD HH:MM:SS'.
     *
     * Exemplos de entrada:
     * - "20261024120000[-3:BRT]"
     * - "20261024120000[-03:EST]"
     * - "20261024120000.123[-3:BRT]"
     * - "20261024120000"
     * - "20261024"
     * - "2026-10-24"
     */
    public function normalizeDate(string $rawDate): string
    {
        $cleaned = trim($rawDate);

        // Se já estiver no padrão ISO YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}):(\d{2}))?/', $cleaned, $m)) {
            $h = $m[4] ?? '00';
            $i = $m[5] ?? '00';
            $s = $m[6] ?? '00';
            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', (int)$m[1], (int)$m[2], (int)$m[3], (int)$h, (int)$i, (int)$s);
        }

        // Padrão OFX: YYYYMMDD com horas opcionais
        if (preg_match('/^(\d{4})(\d{2})(\d{2})(?:(\d{2})(\d{2})(\d{2}))?/', $cleaned, $m)) {
            $year  = (int) $m[1];
            $month = (int) $m[2];
            $day   = (int) $m[3];
            $hour  = isset($m[4]) ? (int) $m[4] : 0;
            $min   = isset($m[5]) ? (int) $m[5] : 0;
            $sec   = isset($m[6]) ? (int) $m[6] : 0;

            // Validação de sanidade da data
            if (!checkdate($month, $day, $year)) {
                return date('Y-m-d H:i:s');
            }

            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $min, $sec);
        }

        return date('Y-m-d H:i:s');
    }

    /**
     * Normaliza valores monetários no formato OFX para float.
     * Trata valores negativos, positivos, com ponto ou vírgula.
     */
    public function normalizeAmount(string $rawAmount): float
    {
        $clean = trim($rawAmount);
        // Remove espaços internos
        $clean = str_replace(' ', '', $clean);

        // Substitui vírgula por ponto caso venha formatado incorretamente
        $clean = str_replace(',', '.', $clean);

        return (float) $clean;
    }

    /**
     * Resolve a descrição mais amigável e limpa entre <MEMO> e <NAME>.
     */
    protected function resolveDescription(?string $memo, ?string $name): string
    {
        $cleanMemo = $this->cleanString($memo);
        $cleanName = $this->cleanString($name);

        if (!empty($cleanMemo) && !empty($cleanName)) {
            // Se o MEMO for muito genérico (ex: "COMPRA", "OUTROS") e o NAME tiver mais detalhe
            if (mb_strlen($cleanMemo) <= 4 && mb_strlen($cleanName) > 4) {
                return $cleanName;
            }
            // Se forem idênticos, retorna um só
            if (mb_strtolower($cleanMemo) === mb_strtolower($cleanName)) {
                return $cleanMemo;
            }
            return $cleanMemo;
        }

        if (!empty($cleanMemo)) {
            return $cleanMemo;
        }

        if (!empty($cleanName)) {
            return $cleanName;
        }

        return 'Lançamento sem descrição';
    }

    /**
     * Higieniza strings removendo tags, entidades HTML e espaços repetidos.
     */
    protected function cleanString(?string $str): ?string
    {
        if ($str === null) {
            return null;
        }

        $decoded = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $stripped = strip_tags($decoded);
        $normalizedSpaces = preg_replace('/\s+/', ' ', $stripped);

        $trimmed = trim($normalizedSpaces ?? '');
        return $trimmed !== '' ? $trimmed : null;
    }
}
