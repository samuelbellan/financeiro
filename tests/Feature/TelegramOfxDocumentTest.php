<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transacao;
use App\Models\Conta;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class TelegramOfxDocumentTest extends TestCase
{
    use RefreshDatabase;
    public function test_telegram_webhook_processes_ofx_document(): void
    {
        config(['telegram.webhook_secret' => '']);
        config(['telegram.allowed_chat_id' => '12345678']);

        $user = User::first() ?? User::factory()->create();

        $sampleOfx = <<<OFX
<OFX>
<BANKMSGSRSV1>
<STMTTRNRS>
<STMTRS>
<BANKACCTFROM>
<BANKID>0077
<BRANCHID>0001
<ACCTID>998877-6
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261022120000[-3:BRT]
<TRNAMT>-75.00
<FITID>TG_TEST_001
<MEMO>PIX RESTAURANTE
</STMTTRN>
<STMTTRN>
<TRNTYPE>CREDIT
<DTPOSTED>20261023150000[-3:BRT]
<TRNAMT>500.00
<FITID>TG_TEST_002
<MEMO>PIX RECEBIDO CLIENTE
</STMTTRN>
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>1500.00
<DTASOF>20261024
</LEDGERBAL>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;

        $this->mock(TelegramService::class, function (MockInterface $mock) use ($sampleOfx) {
            $mock->shouldReceive('sendMessage')->atLeast()->once()->andReturn(true);
            $mock->shouldReceive('getFile')->once()->with('ofx_doc_id')->andReturn(['file_path' => 'documents/extrato.ofx']);
            $mock->shouldReceive('downloadFile')->once()->with('documents/extrato.ofx')->andReturn($sampleOfx);
        });

        $response = $this->postJson(route('webhook.telegram'), [
            'update_id' => 99991,
            'message' => [
                'chat'     => ['id' => 12345678],
                'document' => [
                    'file_id'   => 'ofx_doc_id',
                    'file_name' => 'extrato_inter.ofx',
                    'mime_type' => 'application/x-ofx',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'ok'        => true,
            'processed' => 'ofx_import',
            'result'    => [
                'sucesso'              => true,
                'total_encontrados'    => 2,
                'inseridos'            => 2,
                'duplicados_ignorados' => 0,
            ],
        ]);

        // Validar no banco de dados
        $transacaoDebito = Transacao::where('fitid', 'TG_TEST_001')->first();
        $this->assertNotNull($transacaoDebito);
        $this->assertEquals(75.00, (float)$transacaoDebito->valor);
        $this->assertSame('despesa', $transacaoDebito->tipo);
        $this->assertSame('PIX RESTAURANTE', $transacaoDebito->descricao);

        // Limpeza de registros criados pelo teste
        Transacao::whereIn('fitid', ['TG_TEST_001', 'TG_TEST_002'])->delete();
        Conta::where('numero_conta', '998877-6')->delete();
    }
}
