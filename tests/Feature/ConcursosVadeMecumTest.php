<?php

namespace Tests\Feature;

use App\Models\Concursos\LeiGrifo;
use App\Models\Concursos\LeiMarca;
use App\Models\Concursos\LeiNota;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialCapitulo;
use App\Models\Concursos\MaterialPonto;
use App\Models\Concursos\Questao;
use App\Models\Concursos\QuestaoClassificacao;
use App\Models\Concursos\Tentativa;
use App\Models\User;
use App\Services\Concursos\Leis\IncidenciaLeis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Vade Mecum anotável: marcador, nota, "cai na prova" e "Levar ao material". Conteúdo inventado. */
class ConcursosVadeMecumTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

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
            'concursos.leis.local' => null,
            // O arquivo tem nome de norma federal para o dispositivo "lei-9999-1990" cair nesta sigla.
            'concursos.leis.catalogo' => ['li' => ['sigla' => 'LI', 'nome' => 'Lei Inventada', 'path' => 'federal/leis-ordinarias/1990/lei-9999-1990-01-01.md']],
        ]);
        Http::fake(['leis.test/*' => Http::response("# Lei Inventada\n\n**Art. 1º** Texto inventado.\n\n**Art. 2º** Outro texto.\n\n§ 1º Parágrafo inventado.\n\n**Art. 3º-A** Artigo com letra.")]);
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($dir));
    }

    private function questao(string $id, array $dispositivos): Questao
    {
        $q = Questao::create(['fonte' => 'tec', 'id_externo' => $id, 'banca' => 'B', 'banca_sigla' => 'B', 'ano' => 2024,
            'tipo' => 'ME', 'enunciado' => "Enunciado inventado {$id}.", 'gabarito' => 'A']);
        QuestaoClassificacao::create(['questao_id' => $q->id, 'tipologia' => 'literal', 'dificuldade' => 'M',
            'ponto_cobrado' => 'inventado', 'dispositivos' => $dispositivos]);

        return $q;
    }

    private function capitulo(?int $userId = null): MaterialCapitulo
    {
        $m = Material::create(['user_id' => $userId ?? $this->user->id, 'slug' => 'inv' . uniqid(), 'titulo' => 'Material Inventado']);

        return MaterialCapitulo::create(['material_id' => $m->id, 'titulo' => 'Capítulo Inventado', 'ordem' => 1,
            'conteudo' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Texto que já existia.']]]]]]);
    }

    public function test_resolver_converte_slug_do_dispositivo_em_sigla_e_ancora(): void
    {
        $i = app(IncidenciaLeis::class);

        $this->assertSame(['lei' => 'li', 'ancora' => 'art-2', 'parte' => '§ 1'], $i->resolver('lei-9999-1990/art-2/par-1'));
        $this->assertSame(['lei' => 'cf', 'ancora' => 'art-37', 'parte' => '§ 5, inc. II, al. e'], $i->resolver('cf-1988/art-37/par-5/inc-II/ali-e'));
        $this->assertSame(['lei' => 'lei8112', 'ancora' => 'art-69-a', 'parte' => 'caput'], $i->resolver('lei-8112-1990/art-69-A/caput'));
        $this->assertSame('lcp123', $i->resolver('lc-123-2006/art-3')['lei']);
        $this->assertNull($i->resolver('nbc-tg-03/item-12'));
        $this->assertNull($i->resolver('lei-9999-1990'));
    }

    public function test_pagina_mostra_incidencia_do_banco_e_erros_do_aluno(): void
    {
        $q1 = $this->questao('1', ['lei-9999-1990/art-2/par-1', 'lei-9999-1990/art-2/caput']);
        $q2 = $this->questao('2', ['lei-9999-1990/art-2/par-1', 'cf-1988/art-5']);
        $this->questao('3', ['lei-9999-1990/art-3-A']);
        Tentativa::create(['user_id' => $this->user->id, 'questao_id' => $q2->id, 'resposta' => 'B', 'gabarito' => 'A', 'correta' => false, 'certeza' => 'sei']);
        // Erro de outro aluno não conta para este.
        Tentativa::create(['user_id' => $this->user->id + 1, 'questao_id' => $q1->id, 'resposta' => 'B', 'gabarito' => 'A', 'correta' => false, 'certeza' => 'sei']);

        $inc = app(IncidenciaLeis::class)->daLei('li', $this->user->id);
        $this->assertSame(2, $inc['art-2']['total']); // q1 conta uma vez, mesmo citando dois dispositivos
        $this->assertSame(['§ 1' => 2, 'caput' => 1], $inc['art-2']['partes']);
        $this->assertSame(1, $inc['art-2']['errei']);
        $this->assertSame(1, $inc['art-3-a']['total']);
        $this->assertArrayNotHasKey('art-1', $inc);

        $this->actingAs($this->user)->get(route('concursos.leis.show', 'li'))
            ->assertOk()
            ->assertSee('2 questões')
            ->assertSee('você errou 1')
            ->assertSee(route('concursos.questoes.show', $q1->id), false)
            ->assertSee('Caem na prova (2)');
    }

    public function test_marcador_e_nota_por_aluno(): void
    {
        $url = route('concursos.leis.marcar', ['li', 'art-2']);

        $this->actingAs($this->user)->postJson($url, ['marcado' => true])->assertOk()->assertJson(['marcado' => true, 'nota' => null]);
        $this->actingAs($this->user)->postJson($url, ['nota' => '  A banca troca o prazo.  '])->assertOk()->assertJson(['marcado' => true, 'nota' => 'A banca troca o prazo.']);
        $this->assertSame(1, LeiMarca::count());

        $this->actingAs($this->user)->get(route('concursos.leis.show', 'li'))
            ->assertOk()->assertSee('data-nota="A banca troca o prazo."', false)->assertSee('Marcados (1)');
        // Outro aluno não vê.
        $this->actingAs(User::factory()->create())->get(route('concursos.leis.show', 'li'))
            ->assertOk()->assertDontSee('A banca troca o prazo.');

        // Desmarcar e apagar a nota remove a linha.
        $this->actingAs($this->user)->postJson($url, ['marcado' => false])->assertOk();
        $this->actingAs($this->user)->postJson($url, ['nota' => ''])->assertOk()->assertJson(['marcado' => false, 'nota' => null]);
        $this->assertSame(0, LeiMarca::count());
    }

    public function test_marcar_artigo_inexistente_da_404(): void
    {
        $this->actingAs($this->user)->postJson(route('concursos.leis.marcar', ['li', 'art-99']), ['marcado' => true])->assertNotFound();
        $this->actingAs($this->user)->postJson(route('concursos.leis.marcar', ['nada', 'art-1']), ['marcado' => true])->assertNotFound();
        $this->assertSame(0, LeiMarca::count());
    }

    public function test_levar_nota_ao_material_cria_ponto_revisando_com_citacao(): void
    {
        $cap = $this->capitulo();
        $this->actingAs($this->user)->postJson(route('concursos.leis.marcar', ['li', 'art-3-a']), ['nota' => "Prazo é de **10 dias**.\nNão confundir com 15."])->assertOk();

        $r = $this->actingAs($this->user)->postJson(route('concursos.leis.levar', ['li', 'art-3-a']), ['capitulo_id' => $cap->id])->assertOk();

        $ponto = MaterialPonto::findOrFail($r->json('ponto'));
        $this->assertSame('revisando', $ponto->status);
        $this->assertSame('LI, Art. 3-A', $ponto->titulo);
        $this->assertSame('[[li 3-A]]', $ponto->base_legal);
        $this->assertSame($cap->id, $ponto->capitulo_id);

        $cap->refresh();
        $ultimo = collect($cap->conteudo['content'])->last();
        $this->assertSame('pontoErro', $ultimo['type']);
        $this->assertSame($ponto->id, $ultimo['attrs']['pontoId']);
        $this->assertStringContainsString('Texto que já existia.', $cap->texto);
        $this->assertStringContainsString('Não confundir com 15.', $cap->texto);
        $this->assertStringContainsString(' li 3-A ', $cap->texto);

        // A citação nova aparece de volta na página da lei, no artigo.
        $this->actingAs($this->user)->get(route('concursos.leis.show', 'li'))
            ->assertOk()->assertSee(route('concursos.material.capitulo', [$cap->material_id, $cap->id]) . '#ponto-' . $ponto->id, false);
    }

    public function test_grifo_tira_trecho_e_contexto_do_texto_do_servidor(): void
    {
        // Texto puro do art. 1: "Art. 1º Texto inventado."
        $url = route('concursos.leis.grifar', ['li', 'art-1']);
        $r = $this->actingAs($this->user)->postJson($url, ['inicio' => 8, 'fim' => 13, 'cor' => 'verde'])->assertOk();
        $r->assertJson(['cor' => 'verde', 'trecho' => 'Texto', 'inicio' => 8, 'fim' => 13]);

        $g = LeiGrifo::findOrFail($r->json('id'));
        $this->assertSame('Art. 1º ', $g->antes);
        $this->assertSame(' inventado.', rtrim($g->depois)); // + o "\n" depois do </p>, como no textContent

        // Sobreposição com o grifo existente é recusada; ao lado, não.
        $this->actingAs($this->user)->postJson($url, ['inicio' => 10, 'fim' => 20])->assertStatus(422);
        $this->actingAs($this->user)->postJson($url, ['inicio' => 14, 'fim' => 23])->assertOk()->assertJson(['trecho' => 'inventado', 'cor' => 'amarelo']);

        // Fora do artigo, só espaço, cor inválida.
        $this->actingAs($this->user)->postJson($url, ['inicio' => 0, 'fim' => 999])->assertStatus(422);
        $this->actingAs($this->user)->postJson($url, ['inicio' => 7, 'fim' => 8])->assertStatus(422);
        $this->actingAs($this->user)->postJson($url, ['inicio' => 0, 'fim' => 3, 'cor' => 'roxo'])->assertStatus(422);

        $this->actingAs($this->user)->get(route('concursos.leis.show', 'li'))
            ->assertOk()->assertSee('data-grifos=', false)->assertSee('Com grifo (1)')->assertSee('<div class="corpo">', false);
    }

    public function test_grifo_vira_orfao_quando_o_trecho_some_da_lei(): void
    {
        LeiGrifo::create(['user_id' => $this->user->id, 'lei' => 'li', 'ancora' => 'art-2', 'cor' => 'rosa',
            'trecho' => 'texto que foi revogado', 'antes' => 'Art. 2º ', 'depois' => '.']);
        LeiGrifo::create(['user_id' => $this->user->id, 'lei' => 'li', 'ancora' => 'art-2', 'cor' => 'azul',
            'trecho' => 'Outro texto', 'antes' => 'Art. 2º (nova redação) ', 'depois' => '.']);

        $html = $this->actingAs($this->user)->get(route('concursos.leis.show', 'li'))->assertOk()->getContent();
        preg_match('/id="art-2"[^>]*data-grifos="([^"]+)"/', $html, $m);
        $grifos = json_decode(html_entity_decode($m[1]), true);
        $this->assertNull($grifos[0]['inicio']); // órfão, mas continua listado
        $this->assertSame('texto que foi revogado', $grifos[0]['trecho']);
        $this->assertSame(8, $grifos[1]['inicio']);
        $this->assertSame(2, LeiGrifo::count());
    }

    public function test_nota_num_ponto_guarda_contexto_e_volta_no_lugar(): void
    {
        // Texto puro do art. 1: "Art. 1º Texto inventado." → posição 13 = logo depois de "Texto".
        $url = route('concursos.leis.notas.store', ['li', 'art-1']);
        $r = $this->actingAs($this->user)->postJson($url, ['posicao' => 13, 'texto' => 'Minha nota', 'tracos' => [['c' => 'verde', 'p' => [1, 2, 0.5]]]])
            ->assertOk()->assertJson(['texto' => 'Minha nota', 'posicao' => 13]);
        $n = LeiNota::findOrFail($r->json('id'));
        $this->assertSame('Art. 1º Texto', $n->antes);
        $this->assertSame(' inventado.', rtrim($n->depois));

        // Vazia, fora do artigo, artigo inexistente.
        $this->actingAs($this->user)->postJson($url, ['posicao' => 3, 'texto' => ' '])->assertStatus(422);
        $this->actingAs($this->user)->postJson($url, ['posicao' => 999, 'texto' => 'x'])->assertStatus(422);
        $this->actingAs($this->user)->postJson(route('concursos.leis.notas.store', ['li', 'art-77']), ['posicao' => 1, 'texto' => 'x'])->assertNotFound();

        // O texto mudou na frente: a nota acha o lugar pelo contexto; sem contexto, vai para o fim (null).
        LeiNota::create(['user_id' => $this->user->id, 'lei' => 'li', 'ancora' => 'art-2', 'antes' => 'Art. 2º Outro', 'depois' => ' texto.', 'texto' => 'a']);
        LeiNota::create(['user_id' => $this->user->id, 'lei' => 'li', 'ancora' => 'art-2', 'antes' => 'revogado', 'depois' => 'sumiu', 'texto' => 'b']);
        $html = $this->actingAs($this->user)->get(route('concursos.leis.show', 'li'))->assertOk()->getContent();
        preg_match('/id="art-2"[^>]*data-notas-ponto="([^"]+)"/', $html, $m);
        $notas = json_decode(html_entity_decode($m[1]), true);
        $this->assertSame(13, $notas[0]['posicao']);
        $this->assertNull($notas[1]['posicao']);
        $this->assertStringContainsString('lei-notas', $html);

        // Editar e apagar: só o dono.
        $outro = User::factory()->create();
        $this->actingAs($outro)->putJson(route('concursos.leis.notas.update', $n), ['texto' => 'x'])->assertNotFound();
        $this->actingAs($outro)->deleteJson(route('concursos.leis.notas.destroy', $n))->assertNotFound();
        $this->actingAs($this->user)->putJson(route('concursos.leis.notas.update', $n), ['texto' => 'Outra', 'tracos' => []])->assertOk()->assertJson(['texto' => 'Outra', 'tracos' => []]);
        $this->actingAs($this->user)->deleteJson(route('concursos.leis.notas.destroy', $n))->assertOk();
        $this->assertSame(2, LeiNota::count());
    }

    public function test_trocar_cor_e_apagar_grifo_so_do_dono(): void
    {
        $g = LeiGrifo::create(['user_id' => $this->user->id, 'lei' => 'li', 'ancora' => 'art-1', 'trecho' => 'Texto']);
        $outro = User::factory()->create();

        $this->actingAs($outro)->patchJson(route('concursos.leis.grifos.update', $g), ['cor' => 'azul'])->assertNotFound();
        $this->actingAs($outro)->deleteJson(route('concursos.leis.grifos.destroy', $g))->assertNotFound();

        $this->actingAs($this->user)->patchJson(route('concursos.leis.grifos.update', $g), ['cor' => 'azul'])->assertOk()->assertJson(['cor' => 'azul']);
        $this->actingAs($this->user)->deleteJson(route('concursos.leis.grifos.destroy', $g))->assertOk();
        $this->assertSame(0, LeiGrifo::count());
    }

    public function test_levar_exige_nota_e_capitulo_do_proprio_aluno(): void
    {
        $meu = $this->capitulo();
        $alheio = $this->capitulo($this->user->id + 1);
        $url = route('concursos.leis.levar', ['li', 'art-1']);

        $this->actingAs($this->user)->postJson($url, ['capitulo_id' => $meu->id])->assertStatus(422);

        $this->actingAs($this->user)->postJson(route('concursos.leis.marcar', ['li', 'art-1']), ['nota' => 'Nota.'])->assertOk();
        $this->actingAs($this->user)->postJson($url, ['capitulo_id' => $alheio->id])->assertNotFound();
        $this->assertSame(0, MaterialPonto::count());
    }

    public function test_indice_de_leis_agrupa_e_mostra_grifos_e_notas_do_aluno(): void
    {
        LeiGrifo::create(['user_id' => $this->user->id, 'lei' => 'li', 'ancora' => 'art-1', 'trecho' => 'Texto']);
        LeiGrifo::create(['user_id' => $this->user->id, 'lei' => 'li', 'ancora' => 'art-2', 'trecho' => 'Outro']);
        LeiMarca::create(['user_id' => $this->user->id, 'lei' => 'li', 'ancora' => 'art-1', 'marcado' => true, 'nota' => 'Atenção']);
        // Anotações de outro aluno não aparecem.
        LeiGrifo::create(['user_id' => $this->user->id + 1, 'lei' => 'li', 'ancora' => 'art-1', 'trecho' => 'Alheio']);

        $this->actingAs($this->user)->get(route('concursos.leis.index'))
            ->assertOk()
            ->assertSee('Outras Normas')
            ->assertSee('Lei Inventada')
            ->assertSee('2 grifos · 1 nota');
    }
}
