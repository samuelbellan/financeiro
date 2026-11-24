<?php

namespace Tests\Feature;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\Importacao;
use App\Models\Concursos\Questao;
use App\Models\Concursos\QuestaoResolucao;
use App\Services\Concursos\AssuntoResolver;
use App\Services\Concursos\TecApiImportador;
use App\Services\Concursos\TecParserException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Cadernos inventados: o repositório é público. */
class ConcursosTecApiTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private TecApiImportador $imp;

    protected function setUp(): void
    {
        parent::setUp();
        config(['concursos.enabled' => true]);
        $this->setUpBancoConcursos();
        $this->imp = app(TecApiImportador::class);
    }

    private function caderno(array $mudancas = []): array
    {
        $base = [
            'caderno' => 'Caderno Inventado', 'cadernoId' => '900',
            'questoes' => [
                ['id' => 1001, 'tipo' => 'MULTIPLA_ESCOLHA', 'banca' => 'BANCA INVENTADA (BI)', 'orgao' => 'Órgão X', 'cargo' => 'Cargo Y',
                    'ano' => 2025, 'materia' => 'Matéria Inventada', 'assuntos' => ['Tema Um, com vírgula', 'Tema Dois'],
                    'anulada' => false, 'desatualizada' => false,
                    'enunciado' => '<p>Sobre o tema inventado,&nbsp;assinale:</p><ul><li>item um</li></ul>',
                    'alternativas' => ['<p>opção a</p>', 'opção b', 'opção c'], 'correta' => 2,
                    'comentario' => ['professor' => 'Prof. Fictício', 'texto' => '<p>Linha um.</p><p>Linha dois.</p>']],
                ['id' => 1002, 'tipo' => 'CERTO_ERRADO', 'banca' => 'BANCA INVENTADA (BI)', 'ano' => 2024, 'materia' => 'Matéria Inventada',
                    'assuntos' => ['Tema Dois'], 'anulada' => false, 'enunciado' => 'Afirmação inventada <img src="x.png">.',
                    'alternativas' => [], 'correta' => 2, 'comentario' => null],
            ],
        ];
        foreach ($mudancas as $i => $m) {
            $base['questoes'][$i] = array_merge($base['questoes'][$i], $m);
        }

        return $base;
    }

    private function gravar(array $caderno): array
    {
        return $this->imp->gravar($this->imp->converter($caderno), 'teste.json', ['caminho' => 'x', 'sha256' => str_repeat('0', 64)]);
    }

    public function test_importa_questoes_alternativas_gabarito_e_assuntos(): void
    {
        $r = $this->gravar($this->caderno());

        $this->assertSame(2, $r['resumo']['novas']);
        $me = Questao::where('id_externo', '1001')->firstOrFail();
        $this->assertSame('tec', $me->fonte);
        $this->assertSame('ME', $me->tipo);
        $this->assertSame('B', $me->gabarito);
        $this->assertSame('BANCA INVENTADA', $me->banca_sigla);
        $this->assertSame("Sobre o tema inventado, assinale:\n\n• item um", $me->enunciado);
        $this->assertSame(['A' => 'opção a', 'B' => 'opção b', 'C' => 'opção c'], $me->alternativas->pluck('texto', 'letra')->all());
        $this->assertSame('api_tec', Importacao::find($r['importacao_id'])->tipo);

        $assuntos = $me->assuntos()->with('disciplina')->get();
        $this->assertSame(['Tema Um, com vírgula', 'Tema Dois'], $assuntos->pluck('nome')->all());
        $this->assertSame('Matéria Inventada', $assuntos->first()->disciplina->nome);
        $this->assertTrue((bool) $assuntos->firstWhere('nome', 'Tema Um, com vírgula')->pivot->principal);

        $ce = Questao::where('id_externo', '1002')->firstOrFail();
        $this->assertSame('CE', $ce->tipo);
        $this->assertSame('Errado', $ce->gabarito);
        $this->assertSame(['Certo', 'Errado'], $ce->alternativas->pluck('letra')->all());
        $this->assertStringContainsString('veja no TEC', $ce->enunciado);
    }

    public function test_comentario_do_professor_vira_resolucao_e_sem_comentario_fica_o_gabarito(): void
    {
        $r = $this->gravar($this->caderno());

        $res = QuestaoResolucao::whereHas('questao', fn ($q) => $q->where('id_externo', '1001'))->firstOrFail();
        $this->assertSame('tec', $res->fonte);
        $this->assertSame('Prof. Fictício', $res->autor);
        $this->assertSame("Linha um.\n\nLinha dois.", $res->resumo);
        $this->assertSame([], $res->alternativas);

        $min = QuestaoResolucao::whereHas('questao', fn ($q) => $q->where('id_externo', '1002'))->firstOrFail();
        $this->assertStringContainsString('gabarito oficial Errado', $min->resumo);
        $this->assertSame(1, $r['resumo']['resolucoes_tec']);
        $this->assertSame(1, $r['resumo']['resolucoes_minimas']);
    }

    public function test_reimportar_atualiza_sem_duplicar_e_mantem_resolucao_da_ia(): void
    {
        $this->gravar($this->caderno());
        $q = Questao::where('id_externo', '1001')->firstOrFail();
        QuestaoResolucao::where('questao_id', $q->id)->update(['fonte' => 'ia', 'resumo' => 'Resolução da IA.']);

        $r = $this->gravar($this->caderno([0 => ['correta' => 3]]));

        $this->assertSame(2, Questao::count());
        $this->assertSame(2, $r['resumo']['atualizadas']);
        $this->assertSame([['id_externo' => '1001', 'antes' => 'B', 'agora' => 'C']], $r['resumo']['gabarito_alterado']);
        $this->assertSame('Resolução da IA.', $q->resolucao()->first()->resumo);
        $this->assertSame(1, $r['resumo']['resolucoes_ia_mantidas']);
    }

    public function test_adota_assunto_do_edital_a_confirmar_e_segue_uniao(): void
    {
        $provisoria = Disciplina::create(['nome' => AssuntoResolver::DISCIPLINA_A_CONFIRMAR]);
        $doEdital = Assunto::create(['disciplina_id' => $provisoria->id, 'nome' => 'Tema Dois']);

        $this->gravar($this->caderno());

        $this->assertSame('Matéria Inventada', $doEdital->fresh()->disciplina->nome);
        $this->assertTrue(Questao::where('id_externo', '1002')->firstOrFail()->assuntos->contains('id', $doEdital->id));
    }

    public function test_sem_materia_usa_a_disciplina_do_assunto_existente(): void
    {
        $d = Disciplina::create(['nome' => 'Disciplina Já Existente']);
        Assunto::create(['disciplina_id' => $d->id, 'nome' => 'Tema Dois']);

        $this->gravar($this->caderno([1 => ['materia' => '']]));

        $a = Questao::where('id_externo', '1002')->firstOrFail()->assuntos->first();
        $this->assertSame('Disciplina Já Existente', $a->disciplina->nome);
    }

    public function test_gabarito_do_comentario_quando_o_caderno_nao_foi_resolvido(): void
    {
        $c = $this->caderno([
            0 => ['correta' => null, 'comentario' => ['professor' => 'Prof. Fictício', 'texto' => '<p>A letra A erra.</p><p>Gabarito: a alternativa correta é a letra C.</p>']],
            1 => ['correta' => null, 'comentario' => ['professor' => 'Prof. Fictício', 'texto' => 'Item inventado. Gabarito: Certo']],
        ]);
        $c['questoes'][] = array_merge($c['questoes'][0], ['id' => 1003, 'comentario' => null]);
        $c['gabarito'] = 'comentario';

        $r = $this->gravar($c);

        $this->assertSame('C', Questao::where('id_externo', '1001')->value('gabarito'));
        $this->assertSame('Certo', Questao::where('id_externo', '1002')->value('gabarito'));
        $this->assertFalse(Questao::where('id_externo', '1003')->exists());
        $this->assertSame(['1003'], $r['resumo']['puladas']);
    }

    public function test_le_o_gabarito_nas_formas_usuais(): void
    {
        $f = fn (string $t, bool $ce = false) => $this->imp->gabaritoDoComentario($t, $ce, 5);
        $this->assertSame(2, $f('Texto. Gabarito: B'));
        $this->assertSame(5, $f('Gabarito: Letra E.'));
        $this->assertSame(4, $f('Primeiro gabarito preliminar A, depois alterado. Gabarito definitivo: D'));
        $this->assertSame(2, $f('Gabarito: ERRADO', true));
        $this->assertSame(1, $f('Gabarito: C', true));
        $this->assertNull($f('Sem a palavra mágica.'));
        $this->assertNull($f('Gabarito: F'));
    }

    public function test_anulada_fica_sem_gabarito(): void
    {
        $this->gravar($this->caderno([0 => ['anulada' => true]]));

        $q = Questao::where('id_externo', '1001')->firstOrFail();
        $this->assertTrue($q->anulada);
        $this->assertNull($q->gabarito);
    }

    public function test_caderno_incompleto_e_recusado_inteiro(): void
    {
        try {
            $this->gravar($this->caderno([1 => ['enunciado' => '', 'correta' => null]]));
            $this->fail('Deveria recusar.');
        } catch (TecParserException $e) {
            $this->assertCount(1, $e->problemas);
        }
        $this->assertSame(0, Questao::count());
    }

    public function test_comando_importa_pasta(): void
    {
        $dir = storage_path('framework/testing/tec-api-' . uniqid());
        File::ensureDirectoryExists($dir);
        file_put_contents("{$dir}/900-Caderno.json", json_encode($this->caderno()));
        $antes = glob(storage_path('concursos/imports/*/*.json')) ?: [];

        try {
            $this->artisan('concursos:importar-tec-api', ['caminhos' => [$dir]])
                ->expectsOutputToContain('2 novas')
                ->assertSuccessful();
        } finally {
            File::deleteDirectory($dir);
            foreach (array_diff(glob(storage_path('concursos/imports/*/*.json')) ?: [], $antes) as $f) {
                unlink($f);
            }
        }

        $this->assertSame(2, Questao::count());
        $this->assertSame(2, QuestaoResolucao::count());
    }

    public function test_comando_importa_pacote_exportado_do_navegador(): void
    {
        $pacote = 'teste-pacote-' . uniqid();
        $arquivo = storage_path("framework/testing/{$pacote}.json");
        File::ensureDirectoryExists(dirname($arquivo));
        $segundo = $this->caderno();
        $segundo['questoes'] = [array_merge($segundo['questoes'][0], ['id' => 1003])];
        file_put_contents($arquivo, json_encode(['pacote' => $pacote, 'blocos' => ['900-0001' => $this->caderno(), '900-0021' => $segundo]]));
        $antes = glob(storage_path('concursos/imports/*/*.json')) ?: [];

        try {
            $this->artisan('concursos:importar-tec-api', ['caminhos' => [$arquivo]])
                ->expectsOutputToContain('2 blocos')
                ->expectsOutputToContain('Total: 3 novas')
                ->assertSuccessful();
        } finally {
            File::delete($arquivo);
            File::deleteDirectory(storage_path("concursos/tec/{$pacote}"));
            foreach (array_diff(glob(storage_path('concursos/imports/*/*.json')) ?: [], $antes) as $f) {
                unlink($f);
            }
        }

        $this->assertSame(3, Questao::count());
    }
}
