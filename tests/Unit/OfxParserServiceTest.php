<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Ofx\OfxParserService;
use PHPUnit\Framework\TestCase;

class OfxParserServiceTest extends TestCase
{
    protected OfxParserService $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new OfxParserService();
    }

    public function test_parses_santander_sgml_checking_account_with_unclosed_tags(): void
    {
        $santanderOfx = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEVERSION:102
NEWFILEVERSION:200

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20261024120000[-3:BRT]
<LANGUAGE>POR
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTTRNRS>
<TRNUID>1001
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<STMTRS>
<CURDEF>BRL
<BANKACCTFROM>
<BANKID>0033
<BRANCHID>1234
<ACCTID>01099887-1
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20261001
<DTEND>20261024
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261005120000[-3:BRT]
<TRNAMT>-85.50
<FITID>20261005001
<CHECKNUM>00123
<MEMO>SUPERMERCADO DIA
</STMTTRN>
<STMTTRN>
<TRNTYPE>CREDIT
<DTPOSTED>20261010100000[-3:BRT]
<TRNAMT>3500.00
<FITID>20261010002
<NAME>SALARIO EMPRESA XYZ
<MEMO>CREDITO DE SALARIO
</STMTTRN>
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>5420.30
<DTASOF>20261024
</LEDGERBAL>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;

        $statement = $this->parser->parse($santanderOfx);

        $this->assertSame('conta_corrente', $statement->account->tipoOfx);
        $this->assertSame('0033', $statement->account->bancoId);
        $this->assertSame('Santander', $statement->account->getNomeInstituicao());
        $this->assertSame('1234', $statement->account->agencia);
        $this->assertSame('01099887-1', $statement->account->numeroConta);
        $this->assertSame(5420.30, $statement->account->saldoFinal);
        $this->assertSame(2, $statement->count());

        $t1 = $statement->transactions[0];
        $this->assertSame('20261005001', $t1->fitid);
        $this->assertSame('despesa', $t1->tipo);
        $this->assertSame(85.50, $t1->valor);
        $this->assertSame(-85.50, $t1->valorOriginal);
        $this->assertSame('2026-10-05 12:00:00', $t1->data);
        $this->assertSame('SUPERMERCADO DIA', $t1->descricao);
        $this->assertSame('00123', $t1->checknum);

        $t2 = $statement->transactions[1];
        $this->assertSame('20261010002', $t2->fitid);
        $this->assertSame('receita', $t2->tipo);
        $this->assertSame(3500.00, $t2->valor);
        $this->assertSame('2026-10-10 10:00:00', $t2->data);
        $this->assertSame('CREDITO DE SALARIO', $t2->descricao);

        $this->assertSame(3500.00, $statement->getTotalCreditos());
        $this->assertSame(85.50, $statement->getTotalDebitos());
    }

    public function test_parses_credit_card_statement_autonomously(): void
    {
        $creditCardOfx = <<<OFX
<OFX>
<CREDITCARDMSGSRSV1>
<CCSTMTTRNRS>
<TRNUID>1001
<CCSTMTRS>
<CURDEF>BRL
<CCACCTFROM>
<ACCTID>************9876
</CCACCTFROM>
<BANKTRANLIST>
<DTSTART>20260925
<DTEND>20261024
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261012153000[-3:BRT]
<TRNAMT>-120.40
<FITID>CC_2026101201
<MEMO>FARMACIA RAIA
</STMTTRN>
<STMTTRN>
<TRNTYPE>CREDIT
<DTPOSTED>20261014110000[-3:BRT]
<TRNAMT>45.00
<FITID>CC_2026101402
<NAME>ESTORNO COMPRA
<MEMO>ESTORNO COMPRA CANCELADA
</STMTTRN>
</BANKTRANLIST>
</CCSTMTRS>
</CCSTMTTRNRS>
</CREDITCARDMSGSRSV1>
</OFX>
OFX;

        $statement = $this->parser->parse($creditCardOfx);

        $this->assertSame('cartao_credito', $statement->account->tipoOfx);
        $this->assertSame('************9876', $statement->account->numeroConta);
        $this->assertStringContainsString('Fatura', $statement->account->getIdentificadorAmigavel());
        $this->assertSame(2, $statement->count());

        $this->assertSame('despesa', $statement->transactions[0]->tipo);
        $this->assertSame(120.40, $statement->transactions[0]->valor);
        $this->assertSame('FARMACIA RAIA', $statement->transactions[0]->descricao);

        $this->assertSame('receita', $statement->transactions[1]->tipo);
        $this->assertSame(45.00, $statement->transactions[1]->valor);
        $this->assertSame('ESTORNO COMPRA CANCELADA', $statement->transactions[1]->descricao);
    }

    public function test_normalizes_various_ofx_date_formats(): void
    {
        $this->assertSame('2026-10-24 12:00:00', $this->parser->normalizeDate('20261024120000[-3:BRT]'));
        $this->assertSame('2026-10-24 12:00:00', $this->parser->normalizeDate('20261024120000[-03:EST]'));
        $this->assertSame('2026-10-24 12:00:00', $this->parser->normalizeDate('20261024120000.123[-3:BRT]'));
        $this->assertSame('2026-10-24 18:45:30', $this->parser->normalizeDate('20261024184530'));
        $this->assertSame('2026-10-24 00:00:00', $this->parser->normalizeDate('20261024'));
        $this->assertSame('2026-10-24 00:00:00', $this->parser->normalizeDate('2026-10-24'));
    }

    public function test_normalizes_amounts(): void
    {
        $this->assertSame(-150.50, $this->parser->normalizeAmount('-150.50'));
        $this->assertSame(2300.00, $this->parser->normalizeAmount('2300.00'));
        $this->assertSame(2300.00, $this->parser->normalizeAmount('+2300.00'));
        $this->assertSame(-89.90, $this->parser->normalizeAmount('-89,90'));
    }

    public function test_fallback_to_name_when_memo_is_empty(): void
    {
        $ofx = <<<OFX
<OFX>
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261024
<TRNAMT>-30.00
<FITID>TEST001
<NAME>PADARIA CENTRAL
</STMTTRN>
</BANKTRANLIST>
</OFX>
OFX;

        $statement = $this->parser->parse($ofx);
        $this->assertSame('PADARIA CENTRAL', $statement->transactions[0]->descricao);
    }
}
