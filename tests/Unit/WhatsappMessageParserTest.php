<?php

namespace Tests\Unit;

use App\Services\WhatsappMessageParser;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WhatsappMessageParserTest extends TestCase
{
    use RefreshDatabase;

    protected WhatsappMessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new WhatsappMessageParser();
    }

    public function test_parses_saldo_conta_corrente_variation(): void
    {
        $result = $this->parser->parse('Saldo conta corrente', 1);

        $this->assertEquals('comando', $result['tipo']);
        $this->assertEquals('saldo', $result['comando']);
        $this->assertEquals('casa', $result['saldo_destino']);
    }

    public function test_parses_direct_saldo_command(): void
    {
        $result = $this->parser->parse('saldo', 1);

        $this->assertEquals('comando', $result['tipo']);
        $this->assertEquals('saldo', $result['comando']);
    }

    public function test_parses_slash_and_natural_saldo_questions(): void
    {
        $variacoes = [
            'saldo conta corrente',
            'saldo da conta corrente',
            'saldo em conta corrente',
            'qual o saldo da conta corrente',
            'qual o saldo conta corrente',
            'qual o saldo',
            'qual meu saldo',
            'quanto tenho na conta',
            '/saldo',
            '/saldo conta corrente',
            'ver saldo',
            'consultar saldo',
        ];

        foreach ($variacoes as $v) {
            $result = $this->parser->parse($v, 1);
            $this->assertEquals('comando', $result['tipo'], "Falhou para: {$v}");
            $this->assertEquals('saldo', $result['comando'], "Comando incorreto para: {$v}");
            $this->assertEquals('casa', $result['saldo_destino'], "Destino incorreto para: {$v}");
        }
    }

    public function test_parses_listar_and_ajuda_variations(): void
    {
        $listar = $this->parser->parse('ultimos lancamentos', 1);
        $this->assertEquals('comando', $listar['tipo']);
        $this->assertEquals('listar', $listar['comando']);

        $listarSlash = $this->parser->parse('/listar', 1);
        $this->assertEquals('comando', $listarSlash['tipo']);
        $this->assertEquals('listar', $listarSlash['comando']);

        $ajuda = $this->parser->parse('como usar', 1);
        $this->assertEquals('comando', $ajuda['tipo']);
        $this->assertEquals('ajuda', $ajuda['comando']);

        $ajudaSlash = $this->parser->parse('/ajuda', 1);
        $this->assertEquals('comando', $ajudaSlash['tipo']);
        $this->assertEquals('ajuda', $ajudaSlash['comando']);
    }
}
