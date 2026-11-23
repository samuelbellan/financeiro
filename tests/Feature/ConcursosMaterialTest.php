<?php

namespace Tests\Feature;

use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialPonto;
use App\Models\User;
use App\Services\Concursos\Leis\AcervoLeis;
use App\Services\Concursos\MaterialImportador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Leis e material inventados: o repositório é público. */
class ConcursosMaterialTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private const LEI = <<<'MD'
---
titulo: "Lei Inventada nº 1"
ementa: "Dispõe sobre coisas inventadas."
fonte: "https://exemplo.test/lei-1"
---
# Lei Inventada nº 1

### TÍTULO I — DISPOSIÇÕES

**Art. 1º** Tudo aqui é inventado.

**Art. 2º** O prazo é de cinco dias.

**Parágrafo único.** Salvo <script>alert(1)</script> disposição em contrário.

**Art. 2-A.** Artigo incluído depois.

# ATO DAS DISPOSIÇÕES CONSTITUCIONAIS TRANSITÓRIAS

**Art. 1º** Regra transitória inventada.
MD;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['concursos.enabled' => true]);
        $this->setUpBancoConcursos();
        $this->user = User::factory()->create();

        $dir = storage_path('framework/testing/leis-' . uniqid());
        config([
            'concursos.leis.dir' => $dir,
            'concursos.leis.raw' => 'https://leis.test/',
            'concursos.leis.catalogo' => [
                'li' => ['sigla' => 'LI', 'nome' => 'Lei Inventada', 'path' => 'li.md'],
                'adct' => ['sigla' => 'ADCT', 'nome' => 'Transitórias', 'path' => 'li.md', 'de' => 'li', 'prefixo' => 'adct-'],
            ],
        ]);
        Http::fake(['leis.test/*' => Http::response(self::LEI)]);
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($dir));
    }

    private function importar(?int $userId = null): Material
    {
        return app(MaterialImportador::class)->importar([
            'slug' => 'inventado',
            'titulo' => 'Direito Inventado',
            'secoes' => [[
                'chave' => 'c01',
                'titulo' => 'C01 · Prazos',
                'intro' => 'Padrão: **trocar dias**.',
                'pontos' => [
                    ['chave' => 'q1', 'titulo' => 'Prazo de cinco dias', 'armadilha' => 'A banca diz dez.', 'regra' => 'São cinco dias, [[li 2]].', 'base_legal' => '[[li 2, p.ú.]] e [[adct 1]]', 'questoes' => [111111]],
                    ['chave' => 'q2', 'titulo' => 'Artigo letrado', 'regra' => 'Ver [[li 2-a]] e [[xx 9]].'],
                ],
            ]],
        ], $userId ?? $this->user->id)['material'];
    }

    public function test_analisa_artigos_com_ancoras_e_adct(): void
    {
        $r = AcervoLeis::analisar(self::LEI);
        $ancoras = array_values(array_filter(array_column($r['blocos'], 'ancora')));

        $this->assertSame('Dispõe sobre coisas inventadas.', $r['meta']['ementa']);
        $this->assertSame(['art-1', 'art-2', 'art-2-a', 'adct-art-1'], $ancoras);
        $art2 = collect($r['blocos'])->firstWhere('ancora', 'art-2');
        $this->assertStringContainsString('Parágrafo único', $art2['md']);
    }

    public function test_reimportar_atualiza_texto_sem_perder_o_progresso(): void
    {
        $material = $this->importar();
        $ponto = MaterialPonto::where('chave', 'q1')->first();
        $ponto->update(['status' => 'dominado', 'vezes_errado' => 4]);

        $r = app(MaterialImportador::class)->importar([
            'slug' => 'inventado', 'titulo' => 'Direito Inventado 2',
            'secoes' => [['chave' => 'c01', 'titulo' => 'C01', 'pontos' => [['chave' => 'q1', 'titulo' => 'Novo título', 'regra' => 'Nova regra.']]]],
        ], $this->user->id);

        $ponto->refresh();
        $this->assertSame([0, 1], [$r['novos'], $r['atualizados']]);
        $this->assertSame(['Novo título', 'dominado', 4], [$ponto->titulo, $ponto->status, $ponto->vezes_errado]);
        $this->assertSame(1, Material::count());
        $this->assertSame($material->id, $ponto->secao->material_id);
    }

    public function test_material_mostra_citacoes_como_links_para_a_lei(): void
    {
        $material = $this->importar();

        $this->actingAs($this->user)->get(route('concursos.material.show', $material))
            ->assertOk()
            ->assertSee('Prazo de cinco dias')
            ->assertSee('href="' . route('concursos.leis.show', 'li') . '#art-2"', false)
            ->assertSee('LI, art. 2, p.ú.')
            ->assertSee('href="' . route('concursos.leis.show', 'adct') . '#adct-art-1"', false)
            ->assertSee('#art-2-a"', false)
            ->assertSee('xx 9') // sigla fora do catálogo fica como texto
            ->assertSee('tecconcursos.com.br/questoes/111111', false);
    }

    public function test_material_e_so_do_proprio_aluno(): void
    {
        $outro = User::factory()->create();
        $material = $this->importar($outro->id);
        $ponto = MaterialPonto::first();

        $this->actingAs($this->user)->get(route('concursos.material.show', $material))->assertNotFound();
        $this->actingAs($this->user)->postJson(route('concursos.material.pontos.errei', $ponto))->assertNotFound();
        $this->actingAs($this->user)->get(route('concursos.material.index'))->assertDontSee('Direito Inventado');
    }

    public function test_errei_de_novo_e_status(): void
    {
        $this->importar();
        $ponto = MaterialPonto::where('chave', 'q1')->first();
        $ponto->update(['status' => 'dominado']);

        $this->actingAs($this->user)->postJson(route('concursos.material.pontos.errei', $ponto))
            ->assertOk()->assertJson(['status' => 'errando', 'vezes_errado' => 2]);

        $this->actingAs($this->user)->postJson(route('concursos.material.pontos.status', $ponto), ['status' => 'revisando'])
            ->assertOk()->assertJson(['status' => 'revisando']);
        $this->actingAs($this->user)->postJson(route('concursos.material.pontos.status', $ponto), ['status' => 'xyz'])
            ->assertUnprocessable();
    }

    public function test_cria_secao_e_ponto_pela_tela(): void
    {
        $material = $this->importar();
        $this->actingAs($this->user)->post(route('concursos.material.secoes.store', $material), ['titulo' => 'C02 · Novo'])->assertRedirect();
        $secao = $material->secoes()->where('titulo', 'C02 · Novo')->firstOrFail();

        $this->actingAs($this->user)->post(route('concursos.material.pontos.store', $secao), [
            'titulo' => 'Ponto novo', 'regra' => 'Regra nova [[li 1]].', 'questoes' => 'Q 222222, 333333',
        ])->assertRedirect();

        $p = MaterialPonto::where('titulo', 'Ponto novo')->firstOrFail();
        $this->assertSame(['222222', '333333'], $p->questoes);
        $this->assertSame(['manual', 'errando'], [$p->origem, $p->status]);
    }

    public function test_lei_tem_ancoras_escapa_html_e_aponta_o_resumo(): void
    {
        $this->importar();

        $this->actingAs($this->user)->get(route('concursos.leis.show', 'li'))
            ->assertOk()
            ->assertSee('id="art-2"', false)
            ->assertSee('id="art-2-a"', false)
            ->assertDontSee('id="adct-art-1"', false) // o ADCT tem página própria
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('No resumo:', false)
            ->assertSee('Prazo de cinco dias');

        $this->actingAs($this->user)->get(route('concursos.leis.show', 'adct'))
            ->assertOk()->assertSee('id="adct-art-1"', false)->assertDontSee('id="art-2"', false);

        Http::assertSentCount(1); // CF e ADCT dividem o mesmo arquivo baixado
    }

    public function test_artigo_em_json(): void
    {
        $this->actingAs($this->user)->getJson(route('concursos.leis.artigo', ['li', '2-a']))
            ->assertOk()->assertJson(['lei' => 'LI', 'rotulo' => 'Art. 2-A']);
        $this->actingAs($this->user)->getJson(route('concursos.leis.artigo', ['li', '99']))->assertNotFound();
        $this->actingAs($this->user)->get('/concursos/legislacao/nao')->assertNotFound();
    }

    public function test_sem_plano_ve_so_resumos_e_leis(): void
    {
        $this->user->forceFill(['concursos_plano' => false])->save();

        $this->actingAs($this->user)->get('/concursos')->assertRedirect(route('concursos.material.index'));
        foreach (['/concursos/meta', '/concursos/questoes', '/concursos/painel', '/concursos/simulados', '/concursos/perfil'] as $url) {
            $this->actingAs($this->user)->get($url)->assertRedirect(route('concursos.material.index'));
        }
        $this->actingAs($this->user)->get(route('concursos.material.index'))
            ->assertOk()
            ->assertSee('>Resumos<', false)
            ->assertSee('>Leis<', false)
            ->assertDontSee('Meta do dia')
            ->assertDontSee('>Simulados<', false);
    }

    public function test_com_plano_ve_o_planejamento_no_menu(): void
    {
        $this->assertTrue($this->user->fresh()->temPlanoConcursos()); // padrão para alunos novos

        $this->actingAs($this->user)->get(route('concursos.material.index'))
            ->assertOk()->assertSee('Meta do dia')->assertSee('>Simulados<', false)->assertSee('>Resumos<', false);
    }

    public function test_falha_ao_baixar_mostra_aviso(): void
    {
        config(['concursos.leis.raw' => 'https://fora-do-ar.test/']);
        Http::fake(['fora-do-ar.test/*' => Http::response('', 500)]);
        $this->actingAs($this->user)->get(route('concursos.leis.show', 'li'))
            ->assertOk()->assertSee('Não consegui baixar');
    }
}
