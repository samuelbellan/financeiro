<?php

namespace Tests\Unit;

use App\Services\Concursos\TecCadernoParser;
use App\Services\Concursos\TecParserException;
use PHPUnit\Framework\TestCase;

/**
 * Golden file com um caderno SINTÉTICO (questões inventadas, mesmo layout do TEC).
 * O repositório é público: questões reais do TEC não entram nas fixtures.
 */
class TecCadernoParserTest extends TestCase
{
    private function fixture(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../Fixtures/tec/caderno-sintetico.json'), true);
    }

    private function parse(?array $paginas = null): array
    {
        $f = $this->fixture();

        return (new TecCadernoParser)->parse($paginas ?? $f['paginas'], $paginas ? [] : $f['imagens']);
    }

    public function test_le_caderno_e_metadados(): void
    {
        $r = $this->parse();

        $this->assertSame([
            'id' => '999',
            'titulo' => 'Disposições Gerais - Recursos (arts. 994 a 1.008)',
            'url' => 'https://www.tecconcursos.com.br/s/AbC123',
            'ordenacao' => 'Por Matéria e Assunto',
        ], $r['caderno']);
        $this->assertCount(4, $r['questoes']);
        $this->assertSame([], $r['avisos'], 'o logotipo da página 1 não gera aviso');
    }

    public function test_questao_multipla_escolha_com_linhas_quebradas(): void
    {
        $q = $this->parse()['questoes'][0];

        $this->assertSame('1000001', $q['id_externo']);
        $this->assertSame(1, $q['numero']);
        $this->assertSame('BANCA X', $q['banca']);
        $this->assertSame('Ana Jud (TRT 99)', $q['prova']);
        $this->assertSame('TRT 99', $q['orgao']);
        $this->assertSame('Judiciária/Direito', $q['cargo']);
        $this->assertSame(2025, $q['ano']);
        $this->assertSame('ME', $q['tipo']);
        $this->assertSame('B', $q['gabarito']);
        $this->assertFalse($q['anulada']);
        $this->assertSame(
            "Texto inventado de uma questão que ocupa mais de uma linha porque o enunciado é longo e a página do caderno quebra a linha no meio da frase.\nAssinale a alternativa correta.",
            $q['enunciado']
        );
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], array_column($q['alternativas'], 'letra'));
        $this->assertSame('segunda alternativa que também quebra em duas linhas', $q['alternativas'][1]['texto']);
    }

    public function test_assunto_com_hifen_usa_o_titulo_do_caderno(): void
    {
        $a = $this->parse()['questoes'][0]['assuntos'][0];

        $this->assertSame('Direito Processual Civil', $a['disciplina']);
        $this->assertSame('Disposições Gerais - Recursos (arts. 994 a 1.008)', $a['assunto']);
    }

    public function test_cabecalho_e_assunto_em_paginas_diferentes_e_quatro_alternativas(): void
    {
        $q = $this->parse()['questoes'][1];

        $this->assertSame('BANCA Y (SIGLA)', $q['banca']);
        $this->assertSame('BANCA Y', $q['banca_sigla']);
        $this->assertNull($q['cargo']);
        $this->assertSame("Considere as afirmativas inventadas:\nI. Primeira afirmativa.\nII. Segunda afirmativa.\nEstá correto o que se afirma em", $q['enunciado']);
        $this->assertSame(['A', 'B', 'C', 'D'], array_column($q['alternativas'], 'letra'));
        $this->assertSame('C', $q['gabarito']);
    }

    public function test_certo_errado_e_anulada(): void
    {
        [, , $ce, $anulada] = $this->parse()['questoes'];

        $this->assertSame('CE', $ce['tipo']);
        $this->assertSame('Errado', $ce['gabarito']);
        $this->assertSame(['Certo', 'Errado'], array_column($ce['alternativas'], 'letra'));
        $this->assertSame("Julgue o item a seguir, inventado para o teste.\nO item inventado afirma algo que pode estar certo.", $ce['enunciado']);

        $this->assertTrue($anulada['anulada']);
        $this->assertNull($anulada['gabarito']);
    }

    public function test_gabarito_colado_sem_espaco_e_aceito(): void
    {
        $p = $this->fixture()['paginas'];
        $p[2] = str_replace("1) B 2) C 3) Errado", "1) B2) C3) Errado", $p[2]);

        $this->assertSame('C', $this->parse($p)['questoes'][1]['gabarito']);
    }

    /** Cadernos grandes são impressos em partes: a 2ª parte começa na questão 51. */
    private function comoSegundaParte(array $p): array
    {
        $p[0] = str_replace(['1) Texto inventado'], ['51) Texto inventado'], $p[0]);
        $p[1] = str_replace(['2) Considere', '3) Julgue', '4) Questão'], ['52) Considere', '53) Julgue', '54) Questão'], $p[1]);
        $p[2] = str_replace(['1) B 2) C 3) Errado', '4) Anulada'], ['51) B 52) C 53) Errado', '54) Anulada'], $p[2]);

        return $p;
    }

    public function test_aceita_parte_do_caderno_que_comeca_em_51(): void
    {
        $q = $this->parse($this->comoSegundaParte($this->fixture()['paginas']))['questoes'];

        $this->assertSame([51, 52, 53, 54], array_column($q, 'numero'));
        $this->assertSame(['B', 'C', 'Errado', null], array_column($q, 'gabarito'));
    }

    public function test_parte_que_comeca_em_51_ainda_exige_sequencia(): void
    {
        $p = $this->comoSegundaParte($this->fixture()['paginas']);
        $p[1] = str_replace('53) Julgue', '57) Julgue', $p[1]);

        $this->expectException(TecParserException::class);
        $this->expectExceptionMessageMatches('/número impresso 57, esperado 53/');
        $this->parse($p);
    }

    public function test_cabecalho_da_prova_quebrado_em_duas_linhas(): void
    {
        $p = $this->fixture()['paginas'];
        $p[0] = str_replace('BANCA X - Ana Jud (TRT 99)/TRT 99/Judiciária/Direito/2025', "BANCA X - Ana Jud (TRT 99)/TRT 99/Judiciária e Fiscalização de Entidades\nPrestadoras/Direito/2025", $p[0]);

        $q = $this->parse($p)['questoes'][0];

        $this->assertSame(2025, $q['ano']);
        $this->assertSame('Judiciária e Fiscalização de Entidades Prestadoras/Direito', $q['cargo']);
    }

    public function test_nome_de_assunto_quebrado_em_duas_linhas(): void
    {
        $p = $this->fixture()['paginas'];
        $p[0] = preg_replace(
            '/Direito Processual Civil - Disposições Gerais - Recursos \(arts\. 994 a 1\.008\)/u',
            "Direito Processual Civil - Disposições Gerais - Recursos (arts. 994 a\n1.008)",
            $p[0],
            1
        );

        $a = $this->parse($p)['questoes'][0]['assuntos'];

        $this->assertCount(1, $a);
        $this->assertSame('Disposições Gerais - Recursos (arts. 994 a 1.008)', $a[0]['assunto']);
    }

    public function test_recusa_quando_gabarito_tem_menos_respostas(): void
    {
        $p = $this->fixture()['paginas'];
        $p[2] = str_replace('4) Anulada', '', $p[2]);

        $this->expectException(TecParserException::class);
        $this->expectExceptionMessageMatches('/4 questões lidas, mas o gabarito tem 3/');
        $this->parse($p);
    }

    public function test_recusa_letra_de_gabarito_inexistente(): void
    {
        $p = $this->fixture()['paginas'];
        $p[2] = str_replace('2) C', '2) E', $p[2]); // a questão 2 só vai até D

        $this->expectException(TecParserException::class);
        $this->expectExceptionMessageMatches('/gabarito "E" não é uma alternativa válida/');
        $this->parse($p);
    }

    public function test_recusa_numeracao_fora_de_ordem(): void
    {
        $p = $this->fixture()['paginas'];
        $p[1] = str_replace('3) Julgue', '7) Julgue', $p[1]);

        $this->expectException(TecParserException::class);
        $this->expectExceptionMessageMatches('/número impresso 7, esperado 3/');
        $this->parse($p);
    }

    public function test_recusa_caderno_sem_pagina_de_gabarito(): void
    {
        $p = $this->fixture()['paginas'];
        unset($p[2]);

        $this->expectException(TecParserException::class);
        $this->expectExceptionMessageMatches('/Gabarito/');
        $this->parse(array_values($p));
    }
}
