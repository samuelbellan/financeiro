<?php

namespace Tests\Feature;

use App\Services\Concursos\TecCadernoParser;
use App\Services\Concursos\TecPdfExtractor;
use Tests\TestCase;

/**
 * Golden file com um PDF REAL do TEC, só na máquina local: o arquivo fica em
 * storage/concursos/fixtures/ (ignorado pelo git, o repositório é público).
 * Sem o arquivo ou sem o Python com pdfplumber, o teste é pulado.
 */
class TecCadernoPdfRealTest extends TestCase
{
    public function test_caderno_prescricao_e_decadencia_50_questoes(): void
    {
        $pdf = storage_path('concursos/fixtures/teste.pdf');
        if (!is_file($pdf) || !is_file(config('concursos.python'))) {
            $this->markTestSkipped('PDF real ou .venv-concursos ausente nesta máquina.');
        }

        $x = (new TecPdfExtractor)->extrair($pdf);
        $r = (new TecCadernoParser)->parse($x['paginas'], $x['imagens']);

        $this->assertSame('108624614', $r['caderno']['id']);
        $this->assertCount(50, $r['questoes']);

        // Gabarito da última página do PDF, na ordem.
        $esperado = ['D', 'E', 'B', 'A', 'B', 'C', 'A', 'E', 'A', 'D', 'C', 'A', 'A', 'D', 'A', 'C', 'B', 'E', 'A', 'A',
            'B', 'B', 'B', 'B', 'C', 'Errado', 'Certo', 'Errado', 'B', 'A', 'Certo', 'Errado', 'C', 'D', 'B', 'B', 'B', 'D',
            'Errado', 'B', 'C', 'Errado', 'Errado', 'Certo', 'E', 'D', 'Certo', 'Certo', 'Errado', 'Certo'];
        $this->assertSame($esperado, array_column($r['questoes'], 'gabarito'));

        $this->assertSame('3796866', $r['questoes'][0]['id_externo']);
        $this->assertSame('3319381', $r['questoes'][49]['id_externo']);
        $this->assertSame(13, count(array_filter($r['questoes'], fn ($q) => $q['tipo'] === 'CE')));
        $this->assertSame(['A', 'B', 'C', 'D'], array_column($r['questoes'][29]['alternativas'], 'letra'));
    }
}
