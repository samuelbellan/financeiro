<?php

namespace Tests\Feature;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Edital;
use App\Models\Concursos\EditalCargo;
use App\Models\Concursos\EditalTopico;
use App\Services\Concursos\EditalImportador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

class EditalImportadorTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBancoConcursos();
    }

    private function edital523(): array
    {
        return json_decode(file_get_contents(resource_path('schemas/concursos/exemplos/edital-ufms-152-2026-cargo-523.json')), true);
    }

    private function gravar(array $d): array
    {
        return app(EditalImportador::class)->gravar($d, 'edital.json', ['caminho' => 'x', 'sha256' => str_repeat('b', 64)]);
    }

    public function test_importa_edital_523_com_quadro_de_provas(): void
    {
        $r = $this->gravar($this->edital523());

        $this->assertSame(5, $r['resumo']['areas']);
        $this->assertSame(14, $r['resumo']['disciplinas']);
        $this->assertSame(70, $r['resumo']['topicos']);
        $this->assertEquals(100, $r['resumo']['pontos_totais']);
        $this->assertSame(4, $r['resumo']['topicos_sem_questoes_tec']);

        $cargo = EditalCargo::with('areas')->find($r['cargo_id']);
        $this->assertEquals(100, $cargo->pontosTotais());
        $this->assertSame('2027-01-17', Edital::first()->data_prova->toDateString());
        $esp = $cargo->areas->firstWhere('nome', 'Conhecimentos Específicos');
        $this->assertSame(20, $esp->n_questoes);
        $this->assertEquals(2, $esp->pontos_por_questao);
    }

    public function test_reimportar_atualiza_sem_duplicar_e_remove_o_que_saiu(): void
    {
        $d = $this->edital523();
        $this->gravar($d);

        // Remove o tópico 17 (Pontuação) de Português e muda a data da prova.
        array_pop($d['areas'][0]['disciplinas'][0]['topicos']);
        $d['edital']['data_prova'] = '2027-01-24';
        $this->gravar($d);

        $this->assertSame(1, Edital::count());
        $this->assertSame(1, EditalCargo::count());
        $this->assertSame(69, EditalTopico::count());
        $this->assertSame('2027-01-24', Edital::first()->data_prova->toDateString());
    }

    public function test_assunto_so_com_nome_e_adotado_pela_disciplina_quando_o_caderno_chega(): void
    {
        $d = $this->edital523();
        $d['areas'][4]['disciplinas'][7]['topicos'][0]['assuntos_sugeridos'] = ['Disposições Gerais - Recursos (arts. 994 a 1.008)'];

        $r = $this->gravar($d);
        $this->assertSame(1, $r['resumo']['assuntos_a_confirmar']);
        $assunto = Assunto::with('disciplina')->sole();
        $this->assertSame(\App\Services\Concursos\AssuntoResolver::DISCIPLINA_A_CONFIRMAR, $assunto->disciplina->nome);

        // Chega o caderno do TEC com a disciplina real: o mesmo assunto é adotado, sem duplicar.
        $f = json_decode(file_get_contents(base_path('tests/Fixtures/tec/caderno-sintetico.json')), true);
        $lido = (new \App\Services\Concursos\TecCadernoParser)->parse($f['paginas'], $f['imagens']);
        $imp = app(\App\Services\Concursos\TecImportador::class)->gravar($lido, 'c.pdf', ['caminho' => 'x', 'sha256' => str_repeat('c', 64)]);

        $this->assertSame([], $imp['resumo']['assuntos_criados']);
        $this->assertSame(['Direito Processual Civil - Disposições Gerais - Recursos (arts. 994 a 1.008)'], $imp['resumo']['assuntos_adotados']);
        $assunto = Assunto::with('disciplina')->sole();
        $this->assertSame('Direito Processual Civil', $assunto->disciplina->nome);
        $this->assertSame(4, $assunto->questoes()->count());
        $this->assertSame([$assunto->id], EditalTopico::where('ordem', '8.1')->first()->assuntos->pluck('id')->all());
    }

    public function test_nome_repetido_em_duas_disciplinas_nao_e_vinculado(): void
    {
        foreach (['Direito Administrativo', 'Direito Constitucional'] as $disc) {
            Assunto::create(['disciplina_id' => \App\Models\Concursos\Disciplina::create(['nome' => $disc])->id, 'nome' => 'Princípios']);
        }
        $d = $this->edital523();
        $d['areas'][4]['disciplinas'][7]['topicos'][0]['assuntos_sugeridos'] = ['Princípios'];

        $r = $this->gravar($d);

        $this->assertSame(['Princípios'], $r['resumo']['assuntos_ambiguos']);
        $this->assertCount(0, EditalTopico::where('ordem', '8.1')->first()->assuntos);
    }

    public function test_assuntos_sugeridos_criam_vinculo_e_preservam_confirmado(): void
    {
        $d = $this->edital523();
        $sug = [['disciplina' => 'Direito Administrativo', 'assunto' => 'Licitações (Lei 14.133/2021)']];
        $d['areas'][4]['disciplinas'][7]['topicos'][0]['assuntos_sugeridos'] = $sug; // 8.1 Licitações

        $r = $this->gravar($d);
        $this->assertSame(['Direito Administrativo - Licitações (Lei 14.133/2021)'], $r['resumo']['assuntos_criados']);

        $topico = EditalTopico::where('ordem', '8.1')->first();
        $this->assertSame('sugerido', $topico->assuntos->first()->pivot->status);

        // Curadoria confirma; reimportar não pode voltar para "sugerido".
        $topico->assuntos()->updateExistingPivot($topico->assuntos->first()->id, ['status' => 'confirmado']);
        $this->gravar($d);

        $this->assertSame(1, Assunto::count());
        $this->assertSame('confirmado', $topico->fresh()->assuntos->first()->pivot->status);
    }
}
