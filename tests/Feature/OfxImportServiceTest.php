<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Ofx\OfxImportService;
use PDO;
use PHPUnit\Framework\TestCase;

class OfxImportServiceTest extends TestCase
{
    protected PDO $pdo;
    protected OfxImportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar banco de dados SQLite em memória para testes isolados e ultra rápidos
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Criar tabelas contas e transações idênticas ao schema
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                email TEXT
            );

            INSERT INTO users (id, name, email) VALUES (1, 'Usuário Teste', 'teste@example.com');

            CREATE TABLE contas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                tipo TEXT NOT NULL DEFAULT 'corrente',
                banco_id TEXT,
                agencia TEXT,
                numero_conta TEXT,
                moeda TEXT DEFAULT 'BRL',
                saldo_atual REAL DEFAULT 0.00,
                data_ultimo_saldo TEXT,
                ativo INTEGER DEFAULT 1,
                created_at TEXT,
                updated_at TEXT,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );

            CREATE TABLE transacoes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                conta_id INTEGER,
                descricao TEXT NOT NULL,
                valor REAL NOT NULL,
                tipo TEXT NOT NULL,
                categoria TEXT,
                subcategoria TEXT,
                data TEXT NOT NULL,
                data_hora TEXT,
                fitid TEXT,
                numero_documento TEXT,
                tipo_transacao_ofx TEXT,
                memo TEXT,
                valor_original_ofx REAL,
                created_at TEXT,
                updated_at TEXT,
                UNIQUE (conta_id, fitid),
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (conta_id) REFERENCES contas(id)
            );
        ");

        $this->service = new OfxImportService($this->pdo);
    }

    public function test_imports_ofx_and_creates_account_automatically(): void
    {
        $ofx = <<<OFX
<OFX>
<BANKMSGSRSV1>
<STMTTRNRS>
<STMTRS>
<BANKACCTFROM>
<BANKID>0033
<BRANCHID>9988
<ACCTID>12345-6
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261020120000[-3:BRT]
<TRNAMT>-150.00
<FITID>FITID_001
<MEMO>POSTO IPIRANGA
</STMTTRN>
<STMTTRN>
<TRNTYPE>CREDIT
<DTPOSTED>20261021100000[-3:BRT]
<TRNAMT>2500.00
<FITID>FITID_002
<MEMO>SALARIO
</STMTTRN>
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>4500.00
<DTASOF>20261024
</LEDGERBAL>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;

        $result = $this->service->import($ofx, 1);

        $this->assertTrue($result->sucesso);
        $this->assertSame(2, $result->totalEncontrados);
        $this->assertSame(2, $result->inseridos);
        $this->assertSame(0, $result->duplicadosIgnorados);
        $this->assertSame(2500.00, $result->totalCreditos);
        $this->assertSame(150.00, $result->totalDebitos);
        $this->assertSame(4500.00, $result->saldoFinal);

        // Verificar se a conta foi criada
        $stmt = $this->pdo->query("SELECT * FROM contas WHERE id = {$result->contaId}");
        $conta = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($conta);
        $this->assertSame('0033', $conta['banco_id']);
        $this->assertSame('12345-6', $conta['numero_conta']);
        $this->assertSame('corrente', $conta['tipo']);

        // Verificar transações salvas
        $stmtTrx = $this->pdo->query("SELECT * FROM transacoes WHERE conta_id = {$result->contaId} ORDER BY id ASC");
        $rows = $stmtTrx->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $rows);
        $this->assertSame('FITID_001', $rows[0]['fitid']);
        $this->assertSame('despesa', $rows[0]['tipo']);
        $this->assertSame(150.0, (float)$rows[0]['valor']);
        $this->assertSame('FITID_002', $rows[1]['fitid']);
        $this->assertSame('receita', $rows[1]['tipo']);
    }

    public function test_strict_deduplication_by_fitid_prevents_duplicate_entries(): void
    {
        $ofx = <<<OFX
<OFX>
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261020120000[-3:BRT]
<TRNAMT>-50.00
<FITID>DUPLICATE_KEY_001
<MEMO>ALMOCO RESTAURANTE
</STMTTRN>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261021120000[-3:BRT]
<TRNAMT>-25.00
<FITID>DUPLICATE_KEY_002
<MEMO>FARMACIA
</STMTTRN>
</BANKTRANLIST>
</OFX>
OFX;

        // 1ª importação: 2 novos
        $primeiraImportacao = $this->service->import($ofx, 1);
        $this->assertTrue($primeiraImportacao->sucesso);
        $this->assertSame(2, $primeiraImportacao->inseridos);
        $this->assertSame(0, $primeiraImportacao->duplicadosIgnorados);

        // 2ª importação do mesmo arquivo: 0 inseridos, 2 ignorados por duplicidade sem quebrar
        $segundaImportacao = $this->service->import($ofx, 1);
        $this->assertTrue($segundaImportacao->sucesso);
        $this->assertSame(0, $segundaImportacao->inseridos);
        $this->assertSame(2, $segundaImportacao->duplicadosIgnorados);

        // Garantir que continuam existindo apenas 2 registros no banco
        $stmtCount = $this->pdo->query("SELECT COUNT(*) FROM transacoes");
        $this->assertSame(2, (int) $stmtCount->fetchColumn());
    }

    public function test_overlapping_partial_statements_inserts_only_new_records(): void
    {
        // Extrato da segunda-feira: 2 transações
        $segunda = <<<OFX
<OFX>
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261019
<TRNAMT>-10.00
<FITID>FIT_SEG_01
<MEMO>CAFE
</STMTTRN>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261019
<TRNAMT>-20.00
<FITID>FIT_SEG_02
<MEMO>ESTACIONAMENTO
</STMTTRN>
</BANKTRANLIST>
</OFX>
OFX;

        $resSeg = $this->service->import($segunda, 1);
        $this->assertSame(2, $resSeg->inseridos);

        // Extrato da sexta-feira: com sobreposição (contém FIT_SEG_02 e nova FIT_SEX_03)
        $sexta = <<<OFX
<OFX>
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261019
<TRNAMT>-20.00
<FITID>FIT_SEG_02
<MEMO>ESTACIONAMENTO
</STMTTRN>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261023
<TRNAMT>-120.00
<FITID>FIT_SEX_03
<MEMO>SUPERMERCADO FINAL DE SEMANA
</STMTTRN>
</BANKTRANLIST>
</OFX>
OFX;

        $resSex = $this->service->import($sexta, 1);
        $this->assertTrue($resSex->sucesso);
        $this->assertSame(1, $resSex->inseridos);
        $this->assertSame(1, $resSex->duplicadosIgnorados);

        $stmtCount = $this->pdo->query("SELECT COUNT(*) FROM transacoes");
        $this->assertSame(3, (int) $stmtCount->fetchColumn());
    }
}
