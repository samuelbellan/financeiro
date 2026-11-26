<?php

namespace Tests\Unit;

use App\Services\Concursos\Leis\Reancorador;
use PHPUnit\Framework\TestCase;

/** Reancoragem do grifo da lei seca quando o texto do artigo muda. Textos inventados. */
class ReancoradorTest extends TestCase
{
    private Reancorador $r;

    protected function setUp(): void
    {
        $this->r = new Reancorador();
    }

    public function test_acha_o_trecho_no_mesmo_lugar(): void
    {
        $texto = 'Art. 1º O prazo é de dez dias, contado da ciência.';
        $this->assertSame(['inicio' => 16, 'fim' => 29], $this->r->localizar($texto, 'é de dez dias', 'Art. 1º O prazo ', ', contado'));
        $this->assertSame('é de dez dias', mb_substr($texto, 16, 13));
    }

    public function test_acha_o_trecho_depois_que_o_resto_do_artigo_mudou(): void
    {
        $novo = 'Art. 1º (Redação nova) O prazo, salvo exceção, é de dez dias, contado da ciência.';
        $p = $this->r->localizar($novo, 'é de dez dias', 'Art. 1º O prazo ', ', contado');
        $this->assertNotNull($p);
        $this->assertSame('é de dez dias', mb_substr($novo, $p['inicio'], $p['fim'] - $p['inicio']));
    }

    public function test_ocorrencia_repetida_desempata_pelo_contexto(): void
    {
        $texto = 'I - a União, no prazo legal; II - o Estado, no prazo legal; III - o Município.';
        $segunda = mb_strpos($texto, 'no prazo legal', 20);

        $p = $this->r->localizar($texto, 'no prazo legal', 'II - o Estado, ', '; III - o Mun');
        $this->assertSame($segunda, $p['inicio']);

        // Sem contexto que ajude: fica a primeira.
        $p = $this->r->localizar($texto, 'no prazo legal');
        $this->assertSame(mb_strpos($texto, 'no prazo legal'), $p['inicio']);
    }

    public function test_espacos_e_quebras_de_linha_nao_perdem_o_grifo(): void
    {
        $texto = "Art. 2º A autoridade\ncompetente decidirá  em trinta dias.";
        $p = $this->r->localizar($texto, 'autoridade competente decidirá em', '', '');
        $this->assertSame(mb_strpos($texto, 'autoridade'), $p['inicio']);
        $this->assertSame(mb_strpos($texto, ' trinta'), $p['fim']);
    }

    public function test_trecho_que_sumiu_vira_orfao(): void
    {
        $this->assertNull($this->r->localizar('Art. 1º O prazo é de quinze dias.', 'é de dez dias', 'O prazo ', '.'));
        $this->assertNull($this->r->localizar('Qualquer texto.', '   '));
    }

    public function test_contexto_guarda_trinta_caracteres_de_cada_lado(): void
    {
        $texto = str_repeat('a', 40) . 'ALVO' . str_repeat('b', 40);
        $c = $this->r->contexto($texto, 40, 44);
        $this->assertSame(str_repeat('a', 30), $c['antes']);
        $this->assertSame(str_repeat('b', 30), $c['depois']);
        $this->assertSame(['antes' => '', 'depois' => 'xy'], $this->r->contexto('ALVOxy', 0, 4));
    }

    public function test_texto_do_html_bate_com_o_textcontent(): void
    {
        $this->assertSame("§ 1º Prazo de 5 dias & \"meio\".\nII - outro", Reancorador::textoDoHtml("<p><strong>§ 1º</strong> Prazo de 5 dias &amp; &quot;meio&quot;.</p>\n<p>II - outro</p>"));
    }
}
