<?php

namespace Tests\Feature;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\Importacao;
use App\Models\Concursos\Questao;
use App\Services\Concursos\TecCadernoParser;
use App\Services\Concursos\TecImportador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

class TecImportadorTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBancoConcursos();
    }

    private function lido(?callable $alterar = null): array
    {
        $f = json_decode(file_get_contents(base_path('tests/Fixtures/tec/caderno-sintetico.json')), true);
        $paginas = $alterar ? $alterar($f['paginas']) : $f['paginas'];

        return (new TecCadernoParser)->parse($paginas, $f['imagens']);
    }

    private function gravar(array $lido): array
    {
        return app(TecImportador::class)->gravar($lido, 'caderno.pdf', ['caminho' => 'x', 'sha256' => str_repeat('a', 64)]);
    }

    public function test_importa_questoes_alternativas_e_taxonomia(): void
    {
        $r = $this->gravar($this->lido());

        $this->assertSame(4, $r['resumo']['novas']);
        $this->assertSame(['Direito Processual Civil - Disposições Gerais - Recursos (arts. 994 a 1.008)'], $r['resumo']['assuntos_criados']);
        $this->assertSame(1, Disciplina::count());
        $this->assertSame(1, Assunto::count());

        $q = Questao::where('id_externo', '1000001')->firstOrFail();
        $this->assertSame('B', $q->gabarito);
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $q->alternativas->pluck('letra')->all());
        $this->assertSame('https://www.tecconcursos.com.br/questoes/1000001', $q->urlTec());
        $this->assertTrue((bool) $q->assuntos->first()->pivot->principal);

        $anulada = Questao::where('id_externo', '1000004')->first();
        $this->assertTrue($anulada->anulada);
        $this->assertNull($anulada->gabarito);
        $this->assertSame('ok', Importacao::find($r['importacao_id'])->status);
    }

    public function test_reimportar_nao_duplica_e_avisa_gabarito_alterado(): void
    {
        $this->gravar($this->lido());

        $r = $this->gravar($this->lido(function ($p) {
            $p[2] = str_replace('1) B', '1) D', $p[2]);
            $p[0] = str_replace('c) terceira', 'c) terceira revisada', $p[0]);

            return $p;
        }));

        $this->assertSame(0, $r['resumo']['novas']);
        $this->assertSame(4, $r['resumo']['atualizadas']);
        $this->assertSame([['id_externo' => '1000001', 'antes' => 'B', 'agora' => 'D']], $r['resumo']['gabarito_alterado']);
        $this->assertSame([], $r['resumo']['assuntos_criados']);
        $this->assertSame(4, Questao::count());

        $q = Questao::where('id_externo', '1000001')->first();
        $this->assertSame('D', $q->gabarito);
        $this->assertSame(5, $q->alternativas()->count());
        $this->assertSame('terceira revisada', $q->alternativas()->where('letra', 'C')->value('texto'));
    }

    public function test_assunto_unido_na_curadoria_recebe_as_questoes(): void
    {
        $d = Disciplina::create(['nome' => 'Direito Processual Civil']);
        $destino = Assunto::create(['disciplina_id' => $d->id, 'nome' => 'Recursos (nome novo)']);
        Assunto::create(['disciplina_id' => $d->id, 'nome' => 'Disposições Gerais - Recursos (arts. 994 a 1.008)', 'unido_em_id' => $destino->id]);

        $this->gravar($this->lido());

        $this->assertSame(4, $destino->questoes()->count());
    }

    public function test_comando_recusa_quando_modulo_desligado(): void
    {
        config(['concursos.enabled' => false]);

        $this->artisan('concursos:importar-pdf', ['arquivos' => ['x.pdf']])->assertFailed();
    }
}
