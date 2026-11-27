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

        $this->actingAs($this->user)->get($this->capitulo($material))
            ->assertOk()
            ->assertSee('Prazo de cinco dias')
            ->assertSee('href="' . route('concursos.leis.show', 'li') . '#art-2"', false)
            ->assertSee('LI, art. 2, p.ú.')
            ->assertSee('href="' . route('concursos.leis.show', 'adct') . '#adct-art-1"', false)
            ->assertSee('#art-2-a"', false)
            ->assertSee('xx 9') // sigla fora do catálogo fica como texto
            ->assertSee('Questões do TEC: 111111');
    }

    /** Abre o livro (que converte as seções em capítulos) e devolve a URL do primeiro capítulo. */
    private function capitulo(Material $material): string
    {
        $this->actingAs($this->user)->get(route('concursos.material.show', $material))->assertOk();

        return route('concursos.material.capitulo', [$material, $material->capitulos()->firstOrFail()]);
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

    /** Monta uma "cópia local" mínima do repositório numa pasta temporária. */
    private function copiaLocal(): string
    {
        $raiz = storage_path('framework/testing/acervo-' . uniqid());
        File::put(tap($raiz . '/li.md', fn ($f) => File::ensureDirectoryExists(dirname($f))), self::LEI);
        $lei = $raiz . '/federal/leis-ordinarias/1999/lei-9999-1999-01-02.md';
        File::ensureDirectoryExists(dirname($lei));
        File::put($lei, "---\ntitulo: \"Lei nº 9.999\"\n---\n# Lei 9.999\n\n**Art. 1º** Regra inventada da lei 9.999.\n");
        $sv = $raiz . '/jurisprudencia/stf/sumulas-vinculantes/sumula-vinculante-7.md';
        File::ensureDirectoryExists(dirname($sv));
        File::put($sv, "---\ntitulo: \"Súmula vinculante nº 7 do STF\"\n---\n\n# Súmula vinculante nº 7 do STF\n\n## Questão\n\nEnunciado inventado da súmula sete.\n\n## Tese\n\nnão informado\n\n## Registro original do CNJ\n\n```json\n{\"bruto\": true}\n```\n");
        File::put($raiz . '/jurisprudencia/nota.md', "# Nota\n\nTexto solto do acervo.\n");
        config(['concursos.leis.local' => $raiz]);
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($raiz));

        return $raiz;
    }

    public function test_com_copia_local_le_do_disco_e_aceita_qualquer_lei_federal(): void
    {
        $this->copiaLocal();

        $this->actingAs($this->user)->get(route('concursos.leis.show', 'li'))->assertOk()->assertSee('id="art-2"', false);
        $this->actingAs($this->user)->get(route('concursos.leis.show', 'lei9999'))->assertOk()->assertSee('Regra inventada da lei 9.999');
        $this->actingAs($this->user)->getJson(route('concursos.leis.artigo', ['lei9999', '1']))->assertOk()->assertJson(['titulo' => 'Lei 9.999/1999, Art. 1']);
        $this->actingAs($this->user)->get(route('concursos.leis.show', 'lei1234'))->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_sumula_vinculante_em_pagina_e_json_sem_o_registro_bruto(): void
    {
        $this->copiaLocal();

        $this->actingAs($this->user)->get(route('concursos.juris.show', ['sv', 7]))
            ->assertOk()->assertSee('Enunciado inventado da súmula sete')->assertDontSee('bruto')->assertDontSee('não informado');
        $this->actingAs($this->user)->getJson(route('concursos.juris.show', ['sv', 7]))
            ->assertOk()->assertJson(['titulo' => 'SV 7']);
        $this->actingAs($this->user)->getJson(route('concursos.juris.show', ['sv', 99]))->assertNotFound();
        $this->actingAs($this->user)->get('/concursos/jurisprudencia/xyz/1')->assertNotFound();
    }

    public function test_citacoes_de_jurisprudencia_e_lei_dinamica_no_material(): void
    {
        $this->copiaLocal();
        $material = $this->importar();
        MaterialPonto::where('chave', 'q2')->first()->update(['base_legal' => '[[sv 7]] · [[sumstj 1234]] · [[lei9999 1]]']);

        $this->actingAs($this->user)->get($this->capitulo($material))
            ->assertOk()
            ->assertSee('>SV 7</a>', false)
            ->assertSee('>Súmula 1.234/STJ</a>', false)
            ->assertSee('>Lei 9.999/1999, art. 1</a>', false)
            ->assertSee('data-json="' . route('concursos.juris.show', ['sv', 7]) . '"', false);
    }

    public function test_navega_no_acervo_e_bloqueia_saida_da_pasta(): void
    {
        $this->copiaLocal();

        $this->actingAs($this->user)->get(route('concursos.leis.acervo'))->assertOk()->assertSee('jurisprudencia')->assertSee('federal');
        $this->actingAs($this->user)->get(route('concursos.leis.acervo', ['p' => 'jurisprudencia/nota.md']))->assertOk()->assertSee('Texto solto do acervo');
        $this->actingAs($this->user)->get(route('concursos.leis.acervo', ['p' => 'federal/leis-ordinarias/1999/lei-9999-1999-01-02.md']))
            ->assertRedirect(route('concursos.leis.show', 'lei9999'));
        $this->actingAs($this->user)->get(route('concursos.leis.acervo', ['p' => '../']))->assertNotFound();
        $this->actingAs($this->user)->get(route('concursos.leis.acervo', ['p' => 'C:/Windows']))->assertNotFound();

        $this->actingAs($this->user)->get(route('concursos.leis.buscar', ['tipo' => 'sv', 'numero' => '7']))->assertRedirect(route('concursos.juris.show', ['sv', 7]));
        $this->actingAs($this->user)->get(route('concursos.leis.buscar', ['tipo' => 'lei', 'numero' => '9999']))->assertRedirect(route('concursos.leis.show', 'lei9999'));
    }

    public function test_sem_copia_local_o_acervo_nao_existe(): void
    {
        $this->actingAs($this->user)->get(route('concursos.leis.acervo'))->assertNotFound();
        $this->actingAs($this->user)->get(route('concursos.leis.show', 'lei9999'))->assertNotFound();
    }

    private function importarQuestoes(): void
    {
        $this->importar();
        $r = app(\App\Services\Concursos\MaterialQuestoesImportador::class)->importar([
            'material' => 'inventado',
            'secoes' => [['secao' => 'c01', 'questoes' => [
                ['chave' => 'q111111', 'id' => '111111', 'origem' => 'BANCA X', 'tipo' => 'ME', 'enunciado' => 'Qual o prazo inventado? I. um II. dois',
                    'alternativas' => [['letra' => 'A', 'texto' => 'dez dias'], ['letra' => 'B', 'texto' => 'cinco dias']], 'gabarito' => 'B', 'comentario' => 'Comentário inventado.'],
                ['chave' => 'q222222', 'id' => '222222', 'tipo' => 'CE', 'enunciado' => 'Item inventado.', 'alternativas' => [], 'gabarito' => 'E'],
                ['chave' => 'q333333', 'id' => '333333', 'tipo' => 'ME', 'enunciado' => 'Sem alternativas.', 'alternativas' => [], 'gabarito' => 'D', 'incompleta' => true],
            ]]],
        ], $this->user->id);
        $this->assertSame([2, 1, ['q222222']], [$r['novas'], $r['puladas'], $r['sem_ponto']]);
    }

    public function test_questoes_filtra_responde_move_de_caixa_e_conta_o_erro_no_ponto(): void
    {
        $this->importarQuestoes();
        $material = Material::first();
        $q1 = \App\Models\Concursos\MaterialQuestao::where('chave', 'q111111')->first();
        $ponto = MaterialPonto::where('chave', 'q1')->first();
        $this->assertSame($ponto->id, $q1->ponto_id); // ligada pelo nº do TEC na lista do ponto

        $this->actingAs($this->user)->get(route('concursos.material.show', $material))
            ->assertSee('Questões (2 para hoje)');
        $this->actingAs($this->user)->get(route('concursos.material.capitulo', [$material, $q1->fresh()->capitulo_id]))
            ->assertSee('Refazer as que ainda erro');

        $tela = $this->actingAs($this->user)->get(route('concursos.material.refazer', [$material, 'iniciar' => 1]))->assertOk();
        $tela->assertSee('Qual o prazo inventado?')->assertSee('Item inventado.')
            ->assertDontSee('Comentário inventado.'); // gabarito e comentário só depois de responder
        $this->assertStringContainsString('inventado?\\nI. um\\nII. dois', $tela->getContent()); // itens em linhas

        $this->actingAs($this->user)->postJson(route('concursos.material.questoes.responder', $q1), ['resposta' => 'A'])
            ->assertOk()->assertJson(['acertou' => false, 'gabarito' => 'B', 'comentario' => 'Comentário inventado.', 'caixa' => 'Caixa 1: volta amanhã.', 'pontos' => [['titulo' => 'Prazo de cinco dias']]]);
        $this->assertSame(2, $ponto->fresh()->vezes_errado);
        $this->actingAs($this->user)->postJson(route('concursos.material.questoes.responder', $q1), ['resposta' => 'Z'])->assertUnprocessable();
        $this->actingAs($this->user)->postJson(route('concursos.material.questoes.responder', $q1), ['resposta' => 'B'])->assertOk()->assertJson(['acertou' => true]);

        // Acertou na última: sai de "ainda erro", continua em "todas".
        $this->actingAs($this->user)->get(route('concursos.material.refazer', [$material, 'iniciar' => 1, 'situacao' => 'errando']))
            ->assertDontSee('Qual o prazo inventado?')->assertSee('Item inventado.');
        $this->actingAs($this->user)->get(route('concursos.material.refazer', [$material, 'iniciar' => 1, 'situacao' => 'todas', 'ponto' => $ponto->id]))
            ->assertSee('Qual o prazo inventado?')->assertDontSee('Item inventado.');
    }

    public function test_trechos_errados_vem_do_gemini_uma_vez_e_so_depois_de_responder(): void
    {
        config(['services.gemini.key' => 'chave-teste']);
        $this->importarQuestoes();
        $q1 = \App\Models\Concursos\MaterialQuestao::where('chave', 'q111111')->first();
        $resposta = ['itens' => [
            ['letra' => 'A', 'trechos' => ['DEZ  dias', 'inexistente'], 'motivo' => 'São cinco dias.'],
            ['letra' => 'B', 'trechos' => ['cinco'], 'motivo' => 'É o gabarito, não entra.'],
        ]];
        $gemini = fn ($json) => ['candidates' => [['content' => ['parts' => [['text' => json_encode($json)]]]]]];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()->push($gemini($resposta))
            ->push($gemini(['itens' => [['letra' => 'E', 'trechos' => ['inventado'], 'motivo' => 'x']]]))]);

        $this->actingAs($this->user)->getJson(route('concursos.material.questoes.trechos', $q1))->assertForbidden();
        $this->actingAs($this->user)->postJson(route('concursos.material.questoes.responder', $q1), ['resposta' => 'A'])->assertOk();
        $esperado = ['trechos' => ['A' => ['trechos' => ['DEZ  dias'], 'motivo' => 'São cinco dias.']]];
        $this->actingAs($this->user)->getJson(route('concursos.material.questoes.trechos', $q1))->assertOk()->assertExactJson($esperado);
        $this->actingAs($this->user)->getJson(route('concursos.material.questoes.trechos', $q1))->assertOk()->assertExactJson($esperado);
        Http::assertSentCount(1); // a segunda vem do banco

        // Certo/Errado com gabarito E: o grifo vai no enunciado.
        $q2 = \App\Models\Concursos\MaterialQuestao::where('chave', 'q222222')->first();
        $this->actingAs($this->user)->postJson(route('concursos.material.questoes.responder', $q2), ['resposta' => 'C'])->assertOk();
        $this->actingAs($this->user)->getJson(route('concursos.material.questoes.trechos', $q2))->assertOk()
            ->assertJsonPath('trechos.enunciado.trechos', ['inventado']);

        $outro = User::factory()->create();
        $this->actingAs($outro)->getJson(route('concursos.material.questoes.trechos', $q1))->assertNotFound();
    }

    public function test_comentario_sem_professor_vem_do_gemini_e_pode_ser_editado_com_imagem(): void
    {
        config(['services.gemini.key' => 'chave-teste']);
        $this->importarQuestoes();
        $q1 = \App\Models\Concursos\MaterialQuestao::where('chave', 'q111111')->first();
        $q1->forceFill(['comentario' => "No TEC você marcou A; gabarito B.\n\nAssunto: Prazos"])->save();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [
            ['text' => "O prazo é de **cinco dias** (art. 5º).\n\n<script>alert(1)</script>\n- **A)** dez dias está errado."],
        ]]]]])]);

        $this->actingAs($this->user)->getJson(route('concursos.material.questoes.comentario', $q1))->assertForbidden();
        $this->actingAs($this->user)->postJson(route('concursos.material.questoes.responder', $q1), ['resposta' => 'A'])
            ->assertOk()->assertJson(['gerar_comentario' => true, 'comentario_html' => null]);
        $html = $this->actingAs($this->user)->getJson(route('concursos.material.questoes.comentario', $q1))
            ->assertOk()->assertJsonPath('fonte', 'gemini')->json('html');
        $this->assertStringContainsString('<strong>cinco dias</strong>', $html);
        $this->assertStringContainsString('No TEC você marcou A; gabarito B.', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->actingAs($this->user)->getJson(route('concursos.material.questoes.comentario', $q1))->assertOk();
        Http::assertSentCount(1); // a segunda vem do banco
        $this->actingAs($this->user)->postJson(route('concursos.material.questoes.responder', $q1), ['resposta' => 'B'])
            ->assertJson(['gerar_comentario' => false, 'comentario_fonte' => 'gemini']);

        // Edição: limpa scripts, atributos e imagens de fora; mantém as anexadas ao material.
        $this->actingAs($this->user)->putJson(route('concursos.material.questoes.comentario.salvar', $q1), [
            'html' => '<p onclick="x()">Meu <b>texto</b></p><img src="http://localhost/concursos/material/anexos/7" onerror="x"><img src="https://mal.com/a.png"><script>alert(1)</script><a href="javascript:x">link</a>',
        ])->assertOk()->assertJsonPath('fonte', 'aluno')
            ->assertJsonPath('html', '<p>Meu <b>texto</b></p><img src="/concursos/material/anexos/7" alt=""><a>link</a>');

        // Vazio volta ao comentário do caderno.
        $this->actingAs($this->user)->putJson(route('concursos.material.questoes.comentario.salvar', $q1), ['html' => '<p> </p>'])
            ->assertOk()->assertJsonPath('html', null)->assertJsonPath('fonte', null);

        // Questão com comentário de professor não chama o Gemini.
        $this->assertFalse(\App\Services\Concursos\ComentarioGemini::semComentarioReal("No TEC você marcou A.\n\nComentário do prof. Fulano:\nO prazo é..."));

        $outro = User::factory()->create();
        $this->actingAs($outro)->putJson(route('concursos.material.questoes.comentario.salvar', $q1), ['html' => 'x'])->assertNotFound();
    }

    public function test_pacotes_e_importacao_de_trechos_errados_pelo_chat(): void
    {
        $this->importarQuestoes();
        $this->user->forceFill(['is_owner' => true])->save();
        // Pasta temporária: não pode apagar os pacotes de verdade em storage/concursos/trechos.
        $base = storage_path('framework/testing/trechos-' . uniqid());
        $real = $this->app->storagePath();
        $this->app->useStoragePath($base);
        $this->beforeApplicationDestroyed(function () use ($base, $real) {
            $this->app->useStoragePath($real);
            File::deleteDirectory($base);
        });
        $dir = storage_path('concursos/trechos');
        $q1 = \App\Models\Concursos\MaterialQuestao::where('chave', 'q111111')->first();
        $q2 = \App\Models\Concursos\MaterialQuestao::where('chave', 'q222222')->first();

        $this->artisan('concursos:trechos-pacotes', ['--lote' => 1])->assertSuccessful();
        $pacotes = File::glob($dir . '/trechos-lote-*.json');
        $this->assertCount(2, $pacotes);
        $p1 = json_decode(File::get($pacotes[0]), true)['questoes'][0];
        $this->assertSame(['id' => $q1->id, 'tipo' => 'ME', 'gabarito' => 'B', 'enunciado' => $q1->enunciado,
            'alternativas' => [['letra' => 'A', 'texto' => 'dez dias'], ['letra' => 'B', 'texto' => 'cinco dias']], 'erradas' => ['A']], $p1);
        $this->assertSame('Errado', json_decode(File::get($pacotes[1]), true)['questoes'][0]['gabarito']);

        File::put($dir . '/respostas/r1.json', "```json\n" . json_encode(['lote' => 1, 'questoes' => [
            ['id' => $q1->id, 'itens' => [['letra' => 'A', 'trechos' => ['dez', 'vinte'], 'motivo' => 'São cinco.']]],
            ['id' => $q2->id, 'itens' => [['letra' => 'E', 'trechos' => ['Item'], 'motivo' => 'x']]],
            ['id' => 999999, 'itens' => []],
        ]]) . "\n```");
        $this->artisan('concursos:trechos-importar')->assertSuccessful()
            ->expectsOutputToContain('2 questões marcadas; 2 de 3 trechos conferem');
        $this->assertSame(['A' => ['trechos' => ['dez'], 'motivo' => 'São cinco.']], $q1->fresh()->trechos_errados);
        $this->assertSame(['Item'], $q2->fresh()->trechos_errados['enunciado']['trechos']);

        $this->artisan('concursos:trechos-pacotes')->assertSuccessful()->expectsOutputToContain('Nenhuma questão sem marcação.');
    }

    public function test_refazer_e_so_do_proprio_aluno(): void
    {
        $this->importarQuestoes();
        $outro = User::factory()->create();
        $q = \App\Models\Concursos\MaterialQuestao::first();

        $this->actingAs($outro)->get(route('concursos.material.refazer', Material::first()))->assertNotFound();
        $this->actingAs($outro)->postJson(route('concursos.material.questoes.responder', $q), ['resposta' => 'A'])->assertNotFound();
    }

    public function test_falha_ao_baixar_mostra_aviso(): void
    {
        config(['concursos.leis.raw' => 'https://fora-do-ar.test/']);
        Http::fake(['fora-do-ar.test/*' => Http::response('', 500)]);
        $this->actingAs($this->user)->get(route('concursos.leis.show', 'li'))
            ->assertOk()->assertSee('Não consegui baixar');
    }
}
