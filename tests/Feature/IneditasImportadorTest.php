<?php

namespace Tests\Feature;

use App\Models\Concursos\EditalCargo;
use App\Models\Concursos\EditalTopico;
use App\Models\Concursos\Perfil;
use App\Models\Concursos\Questao;
use App\Models\User;
use App\Services\Concursos\EditalImportador;
use App\Services\Concursos\IneditasImportador;
use App\Services\Concursos\MotorMeta;
use App\Services\Concursos\PacoteGerador;
use App\Services\Concursos\TecParserException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Questões inéditas para normas sem questões no TEC. Texto inventado. */
class IneditasImportadorTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    protected function setUp(): void
    {
        parent::setUp();
        config(['concursos.enabled' => true]);
        $this->setUpBancoConcursos();
        $d = json_decode(file_get_contents(resource_path('schemas/concursos/exemplos/edital-ufms-152-2026-cargo-523.json')), true);
        app(EditalImportador::class)->gravar($d, 'edital.json', ['caminho' => 'x', 'sha256' => str_repeat('d', 64)]);
    }

    private function questao(int $n, string $gab = 'C'): array
    {
        return [
            'enunciado' => "De acordo com a norma inventada, a regra número {$n} determina que:",
            'alternativas' => array_map(fn ($l) => ['letra' => $l, 'texto' => "opção {$l} da regra {$n}"], ['A', 'B', 'C', 'D', 'E']),
            'gabarito' => $gab, 'dificuldade' => 'M', 'ponto_cobrado' => "Regra inventada número {$n} da norma.",
            'dispositivos' => ["norma-inventada/art-{$n}"],
            'resolucao' => ['resumo' => 'Resumo inventado da questão.',
                'alternativas' => array_map(fn ($l) => ['letra' => $l, 'correta' => $l === $gab, 'explicacao' => "Explica {$l}."], ['A', 'B', 'C', 'D', 'E'])],
        ];
    }

    private function dados(array $questoes, string $area = 'Legislação', string $ordem = '5'): array
    {
        return ['schema_versao' => '1', 'topico' => ['area' => $area, 'ordem' => $ordem],
            'norma' => ['codigo' => 'norma-inventada', 'titulo' => 'Norma Inventada nº 1'], 'questoes' => $questoes];
    }

    private function gravar(array $dados): array
    {
        return app(IneditasImportador::class)->gravar($dados, 'ineditas.txt', ['caminho' => 'x', 'sha256' => str_repeat('e', 64)]);
    }

    public function test_importa_liga_ao_topico_e_entra_na_meta(): void
    {
        $r = $this->gravar($this->dados([$this->questao(1), $this->questao(2, 'A')]));
        $this->assertSame(['norma' => 'Norma Inventada nº 1', 'topicos' => 1, 'questoes' => 2, 'novas' => 2, 'total_da_norma' => 2], $r);

        $q = Questao::where('fonte', 'inedita')->orderBy('id')->first();
        $this->assertSame('C', $q->gabarito);
        $this->assertSame('Explica C.', $q->resolucao->explicacao('C'));
        $this->assertSame('literalidade_norma', $q->classificacao->tipologia);

        $topico = EditalTopico::where('ordem', '5')->whereHas('disciplina.area', fn ($a) => $a->where('nome', 'Legislação'))->first();
        $this->assertTrue($topico->sem_questoes_tec);
        $this->assertSame(2, $topico->questoesQuery()->count());

        $user = User::factory()->create();
        $perfil = Perfil::create(['user_id' => $user->id, 'cargo_id' => EditalCargo::value('id'), 'minutos_semana' => ['1' => 60]]);
        $ix = app(MotorMeta::class)->indices($perfil, today())->first(fn ($i) => $i['topico']->id === $topico->id);
        $this->assertCount(2, $ix['questoes']);

        // Não vão para os pacotes do Gemini (já vêm classificadas e resolvidas).
        $this->assertSame([], (new PacoteGerador(minimoLote: 1))->planejar());

        $this->actingAs($user)->get("/concursos/questoes/{$q->id}")->assertOk()->assertSee('Questão inédita')->assertDontSee('ver no TEC');
    }

    public function test_reimportar_nao_duplica(): void
    {
        $this->gravar($this->dados([$this->questao(1)]));
        $r = $this->gravar($this->dados([$this->questao(1), $this->questao(3)]));
        $this->assertSame(1, $r['novas']);
        $this->assertSame(2, Questao::where('fonte', 'inedita')->count());
    }

    public function test_recusa_gabarito_incoerente_repetida_e_topico_inexistente(): void
    {
        $ruim = $this->questao(1, 'B');
        $ruim['resolucao']['alternativas'][2]['correta'] = true; // B e C marcadas
        try {
            $this->gravar($this->dados([$ruim, $this->questao(2), $this->questao(2)]));
            $this->fail('Deveria recusar.');
        } catch (TecParserException $e) {
            $this->assertStringContainsString('marca B,C como correta', $e->getMessage());
            $this->assertStringContainsString('repete a questão 2', $e->getMessage());
        }
        $this->assertSame(0, Questao::count());

        $this->expectException(TecParserException::class);
        $this->gravar($this->dados([$this->questao(1)], 'Legislação', '99'));
    }
}
