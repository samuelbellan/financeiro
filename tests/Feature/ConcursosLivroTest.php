<?php

namespace Tests\Feature;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialAnotacao;
use App\Models\Concursos\MaterialCapitulo;
use App\Models\Concursos\MaterialPonto;
use App\Models\Concursos\MaterialQuestao;
use App\Models\User;
use App\Services\Concursos\MaterialImportador;
use App\Services\Concursos\MaterialQuestoesImportador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Livro de resumo: capítulos por assunto, editor, notas, imagens. Conteúdo inventado (repositório público). */
class ConcursosLivroTest extends TestCase
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
            'concursos.leis.catalogo' => ['li' => ['sigla' => 'LI', 'nome' => 'Lei Inventada', 'path' => 'li.md']],
        ]);
        Http::fake(['leis.test/*' => Http::response("# Lei Inventada\n\n**Art. 1º** Texto inventado.\n\n**Art. 2º** Outro texto.")]);
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($dir));
    }

    /** Material com duas seções; questões com assunto do TEC (uma na árvore, uma fora). */
    private function material(?int $userId = null): Material
    {
        $userId ??= $this->user->id;
        $disc = Disciplina::firstOrCreate(['nome' => 'Contabilidade Inventada']);
        $raiz = Assunto::firstOrCreate(['disciplina_id' => $disc->id, 'nome' => 'Ativo']);
        Assunto::firstOrCreate(['disciplina_id' => $disc->id, 'nome' => 'Estoques'], ['parent_id' => $raiz->id]);

        $material = app(MaterialImportador::class)->importar([
            'slug' => 'ctb', 'titulo' => 'Contabilidade Inventada', 'disciplina' => 'Contabilidade Inventada',
            'secoes' => [
                ['chave' => 'c01', 'titulo' => 'C01 · Estoques', 'intro' => 'Padrão: **custo médio**.', 'pontos' => [
                    ['chave' => 'p1', 'titulo' => 'PEPS vs média', 'armadilha' => 'A banca troca.', 'regra' => "Linhas:\n- PEPS sobe o lucro\n- média suaviza", 'base_legal' => '[[li 2]]', 'questoes' => ['900001']],
                ]],
                ['chave' => 'c02', 'titulo' => 'C02 · Diversos', 'pontos' => [
                    ['chave' => 'p2', 'titulo' => 'Ponto sem questão', 'regra' => "| A | B |\n|---|---|\n| 1 | 2 |"],
                ]],
            ],
        ], $userId)['material'];

        app(MaterialQuestoesImportador::class)->importar([
            'material' => 'ctb',
            'secoes' => [
                ['secao' => 'c01', 'questoes' => [
                    ['chave' => 'q900001', 'id' => '900001', 'tipo' => 'CE', 'assunto' => 'Estoques, Ativo', 'enunciado' => 'Item inventado sobre estoques.', 'alternativas' => [], 'gabarito' => 'C'],
                ]],
                ['secao' => 'c02', 'questoes' => [
                    ['chave' => 'q900002', 'id' => '900002', 'tipo' => 'ME', 'comentario' => "No TEC você marcou A.\n\nAssunto: Tema Fora da Árvore", 'enunciado' => 'Questão inventada dois.',
                        'alternativas' => [['letra' => 'A', 'texto' => 'um'], ['letra' => 'B', 'texto' => 'dois']], 'gabarito' => 'B'],
                ]],
            ],
        ], $userId);

        return $material;
    }

    private function abrir(Material $material)
    {
        return $this->actingAs($this->user)->get(route('concursos.material.show', $material))->assertOk();
    }

    public function test_abrir_o_livro_monta_capitulos_pela_arvore_de_assuntos(): void
    {
        $material = $this->material();
        $this->abrir($material)->assertSee('Ativo')->assertSee('Estoques')->assertSee('Tema Fora da Árvore');

        $ativo = MaterialCapitulo::where('chave', 'a' . Assunto::where('nome', 'Ativo')->value('id'))->firstOrFail();
        $estoques = MaterialCapitulo::where('titulo', 'Estoques')->firstOrFail();
        $this->assertSame($ativo->id, $estoques->parent_id);

        // A questão e o ponto dela vão para o capítulo do assunto; o ponto sem questão fica no da seção.
        $this->assertSame($estoques->id, MaterialQuestao::where('chave', 'q900001')->value('capitulo_id'));
        $this->assertSame('Estoques, Ativo', MaterialQuestao::where('chave', 'q900001')->value('assunto'));
        $this->assertSame($estoques->id, MaterialPonto::where('chave', 'p1')->value('capitulo_id'));
        $secao2 = MaterialCapitulo::where('titulo', 'C02 · Diversos')->firstOrFail();
        $this->assertSame($secao2->id, MaterialPonto::where('chave', 'p2')->value('capitulo_id'));
        $this->assertSame('Tema Fora da Árvore', MaterialQuestao::where('chave', 'q900002')->first()->capitulo->titulo);

        // O ponto virou bloco com caixas (lista e tabela do Markdown viram nós do editor).
        $doc = $estoques->conteudo;
        $ponto = collect($doc['content'])->firstWhere('type', 'pontoErro');
        $this->assertSame('PEPS vs média', $ponto['attrs']['titulo']);
        $this->assertSame(['pegadinha', 'regra', 'base'], array_column(array_column(array_filter($ponto['content'], fn ($n) => $n['type'] === 'caixa'), 'attrs'), 'tipo'));
        $this->assertStringContainsString('"bulletList"', json_encode($doc));
        $this->assertStringContainsString('"table"', json_encode($secao2->conteudo));

        // Rodar de novo (ou abrir de novo) não duplica nada.
        $total = MaterialCapitulo::count();
        $this->artisan('concursos:material-livro', ['material' => 'ctb'])->assertSuccessful();
        $this->abrir($material);
        $this->assertSame($total, MaterialCapitulo::count());
        $this->assertSame(1, substr_count(json_encode($estoques->fresh()->conteudo), '"pontoErro"'));
    }

    public function test_estante_mostra_fila_de_hoje_e_situacao_calculada_pelas_questoes(): void
    {
        $material = $this->material();
        $this->abrir($material); // monta os capítulos

        // Erradas do caderno entram na caixa 1, vencidas hoje: a matéria aparece em "Para revisar hoje".
        $this->actingAs($this->user)->get(route('concursos.material.index'))->assertOk()
            ->assertSeeInOrder(['Para revisar hoje', 'Contabilidade Inventada', 'Matérias', 'Contabilidade Inventada'])
            ->assertSee('2 ainda erro')
            ->assertSee(route('concursos.material.refazer', [$material, 'situacao' => 'fila', 'iniciar' => 1]));
        $this->abrir($material)->assertSee('Revisar questões (2 hoje)');

        // Acertou as duas no app: saem de "ainda erro" (caixa 2, volta em 7 dias) e a fila de hoje esvazia.
        foreach (MaterialQuestao::all() as $q) {
            $this->actingAs($this->user)->postJson(route('concursos.material.questoes.responder', $q), ['resposta' => $q->gabarito, 'certeza' => 'sei'])
                ->assertOk()->assertJson(['acertou' => true]);
            $this->assertSame(2, $q->fresh()->caixa);
            $this->assertSame('revisando', $q->fresh()->load('ultimaTentativa')->estado());
        }
        $this->actingAs($this->user)->get(route('concursos.material.index'))->assertSee('Nada vencido hoje')->assertSee('2 revisando');
    }

    public function test_capitulo_mostra_texto_links_de_lei_e_questoes(): void
    {
        $material = $this->material();
        $this->abrir($material);
        $estoques = MaterialCapitulo::where('titulo', 'Estoques')->firstOrFail();

        $this->actingAs($this->user)->get(route('concursos.material.capitulo', [$material, $estoques]))
            ->assertOk()
            ->assertSee('PEPS vs média')
            ->assertSee('>LI, art. 2</a>', false)
            ->assertSee('Refazer as que ainda erro')
            ->assertSee('livro-dados', false);

        $outro = Material::create(['user_id' => $this->user->id, 'slug' => 'x', 'titulo' => 'Outro']);
        $this->actingAs($this->user)->get(route('concursos.material.capitulo', [$outro, $estoques]))->assertNotFound();
    }

    public function test_salvar_limpa_o_documento_e_cuida_dos_pontos_e_notas(): void
    {
        $material = $this->material();
        $this->abrir($material);
        $cap = MaterialCapitulo::where('titulo', 'Estoques')->firstOrFail();
        $p1 = MaterialPonto::where('chave', 'p1')->firstOrFail();
        MaterialAnotacao::create(['capitulo_id' => $cap->id, 'marca' => 'velha', 'nota' => 'sai junto com o grifo']);

        $doc = ['type' => 'doc', 'content' => [
            ['type' => 'heading', 'attrs' => ['level' => 9], 'content' => [['type' => 'text', 'text' => 'Resumo']]],
            ['type' => 'paragraph', 'content' => [
                ['type' => 'text', 'text' => 'Grifado', 'marks' => [['type' => 'grifo', 'attrs' => ['cor' => 'verde', 'id' => 'n1']], ['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]],
                ['type' => 'text', 'text' => ' meio '],
                ['type' => 'text', 'text' => '<script>alert(1)</script>'],
                ['type' => 'lei', 'attrs' => ['ref' => 'li 1']],
            ]],
            ['type' => 'iframe', 'attrs' => ['src' => 'https://mal.test']],
            ['type' => 'image', 'attrs' => ['src' => 'https://mal.test/x.png']],
            ['type' => 'pontoErro', 'attrs' => ['pontoId' => $p1->id, 'titulo' => 'PEPS (título novo)'], 'content' => [['type' => 'paragraph']]],
            ['type' => 'pontoErro', 'attrs' => ['pontoId' => 999999, 'titulo' => 'Colado de outro livro'], 'content' => []],
        ]];

        $r = $this->actingAs($this->user)->putJson(route('concursos.material.capitulos.conteudo', $cap), ['conteudo' => $doc])->assertOk();
        $novoId = $r->json('mapa.999999');
        $this->assertNotNull($novoId);

        $salvo = $cap->fresh()->conteudo;
        $json = json_encode($salvo);
        $this->assertSame(4, $salvo['content'][0]['attrs']['level']);
        $this->assertStringNotContainsString('javascript', $json);
        $this->assertStringNotContainsString('iframe', $json);
        $this->assertStringNotContainsString('mal.test', $json);
        $this->assertStringContainsString('"pontoId":' . $novoId, $json);
        $this->assertSame('PEPS (título novo)', $p1->fresh()->titulo);
        $this->assertSame($cap->id, MaterialPonto::find($novoId)->capitulo_id);
        $this->assertStringContainsString('Grifado', $cap->fresh()->texto);
        $this->assertSame(' meio ', $salvo['content'][1]['content'][1]['text']); // espaços entre trechos ficam
        $this->assertFalse(MaterialAnotacao::where('marca', 'velha')->exists());

        // A página escapa o texto do aluno.
        $this->actingAs($this->user)->get(route('concursos.material.capitulo', [$material, $cap]))
            ->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);

        // Ponto que saiu do texto é apagado.
        $this->actingAs($this->user)->putJson(route('concursos.material.capitulos.conteudo', $cap), ['conteudo' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]]])->assertOk();
        $this->assertNull($p1->fresh());
        $this->assertNull(MaterialQuestao::where('chave', 'q900001')->value('ponto_id'));
    }

    public function test_capitulos_criar_renomear_ordenar_e_apagar(): void
    {
        $material = Material::create(['user_id' => $this->user->id, 'slug' => 'livre', 'titulo' => 'Livro Livre']);
        $this->abrir($material)->assertSee('ainda não tem capítulos');

        $a = $this->actingAs($this->user)->postJson(route('concursos.material.capitulos.store', $material), ['titulo' => 'Capítulo A'])->assertOk()->json('id');
        $b = $this->actingAs($this->user)->postJson(route('concursos.material.capitulos.store', $material), ['titulo' => 'Capítulo B', 'parent_id' => $a])->assertOk()->json('id');
        $this->actingAs($this->user)->patchJson(route('concursos.material.capitulos.update', $b), ['titulo' => 'B renomeado'])->assertOk();
        $this->assertSame(['B renomeado', $a], [MaterialCapitulo::find($b)->titulo, MaterialCapitulo::find($b)->parent_id]);

        // B sobe para a raiz e vai antes de A.
        $this->actingAs($this->user)->postJson(route('concursos.material.capitulos.ordem', $material), ['itens' => [
            ['id' => $b, 'parent_id' => null, 'ordem' => 1], ['id' => $a, 'parent_id' => null, 'ordem' => 2],
        ]])->assertOk();
        $this->assertNull(MaterialCapitulo::find($b)->parent_id);

        // Ciclo (A dentro de B e B dentro de A) é recusado.
        $this->actingAs($this->user)->postJson(route('concursos.material.capitulos.ordem', $material), ['itens' => [
            ['id' => $a, 'parent_id' => $b, 'ordem' => 1], ['id' => $b, 'parent_id' => $a, 'ordem' => 1],
        ]])->assertStatus(422);

        // Capítulo de outro aluno não entra na ordem nem é apagado.
        $alheio = MaterialCapitulo::create(['material_id' => $this->material(User::factory()->create()->id)->id, 'titulo' => 'Alheio']);
        $this->actingAs($this->user)->postJson(route('concursos.material.capitulos.ordem', $material), ['itens' => [['id' => $alheio->id, 'parent_id' => null, 'ordem' => 1]]])->assertStatus(422);
        $this->actingAs($this->user)->deleteJson(route('concursos.material.capitulos.destroy', $alheio))->assertNotFound();
        $this->actingAs($this->user)->putJson(route('concursos.material.capitulos.conteudo', $alheio), ['conteudo' => ['type' => 'doc']])->assertNotFound();

        $this->actingAs($this->user)->deleteJson(route('concursos.material.capitulos.destroy', $a))->assertOk();
        $this->assertNull(MaterialCapitulo::find($a));
    }

    public function test_notas_na_margem(): void
    {
        $material = Material::create(['user_id' => $this->user->id, 'slug' => 'n', 'titulo' => 'Notas']);
        $cap = MaterialCapitulo::create(['material_id' => $material->id, 'titulo' => 'Cap']);

        $this->actingAs($this->user)->postJson(route('concursos.material.capitulos.anotacoes', $cap), ['marca' => 'n1', 'cor' => 'azul', 'trecho' => 'trecho', 'nota' => 'Minha nota'])
            ->assertOk()->assertJson(['marca' => 'n1', 'cor' => 'azul', 'nota' => 'Minha nota']);
        $this->actingAs($this->user)->postJson(route('concursos.material.capitulos.anotacoes', $cap), ['marca' => 'n1', 'nota' => 'Editada'])->assertOk();
        $this->assertSame(['Editada'], MaterialAnotacao::pluck('nota')->all());
        $this->actingAs($this->user)->postJson(route('concursos.material.capitulos.anotacoes', $cap), ['marca' => 'N 1!', 'nota' => 'x'])->assertUnprocessable();
        $this->actingAs($this->user)->postJson(route('concursos.material.capitulos.anotacoes', $cap), ['marca' => 'n1', 'nota' => ''])->assertOk()->assertJson(['apagada' => true]);
        $this->assertSame(0, MaterialAnotacao::count());
    }

    public function test_imagem_enviada_so_o_dono_ve(): void
    {
        Storage::fake('local');
        $material = Material::create(['user_id' => $this->user->id, 'slug' => 'i', 'titulo' => 'Imagens']);

        $src = $this->actingAs($this->user)->post(route('concursos.material.anexos.store', $material), ['arquivo' => UploadedFile::fake()->image('esquema.png', 40, 40)], ['Accept' => 'application/json'])
            ->assertOk()->json('src');
        $this->assertMatchesRegularExpression('#^/concursos/material/anexos/\d+$#', $src);
        $this->actingAs($this->user)->get($src)->assertOk()->assertHeader('Content-Type', 'image/png');

        $this->actingAs($this->user)->post(route('concursos.material.anexos.store', $material), ['arquivo' => UploadedFile::fake()->create('x.html', 1, 'text/html')], ['Accept' => 'application/json'])
            ->assertUnprocessable();
        $this->actingAs(User::factory()->create())->get($src)->assertNotFound();
    }

    public function test_citacao_busca_impressao_e_lei_aponta_o_capitulo(): void
    {
        $material = $this->material();
        $this->abrir($material);

        $this->actingAs($this->user)->getJson(route('concursos.material.citacao', ['ref' => 'li 2, I']))
            ->assertOk()->assertJson(['rotulo' => 'LI, art. 2, I', 'juris' => false]);
        $this->actingAs($this->user)->getJson(route('concursos.material.citacao', ['ref' => 'zz 1']))->assertNotFound();

        $this->actingAs($this->user)->getJson(route('concursos.material.buscar', [$material, 'q' => 'peps lucro']))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.titulo', 'Estoques');
        $this->actingAs($this->user)->getJson(route('concursos.material.questoes', [$material, 'q' => '900002']))
            ->assertOk()->assertJsonPath('0.tec', '900002');

        $this->actingAs($this->user)->get(route('concursos.material.imprimir', $material))
            ->assertOk()->assertSee('PEPS vs média')->assertSee('Tema Fora da Árvore');

        $cap = MaterialCapitulo::where('titulo', 'Estoques')->first();
        $p1 = MaterialPonto::where('chave', 'p1')->first();
        $this->actingAs($this->user)->get(route('concursos.leis.show', 'li'))
            ->assertOk()->assertSee(route('concursos.material.capitulo', [$material, $cap]) . '#ponto-' . $p1->id, false);

        $this->actingAs(User::factory()->create())->get(route('concursos.material.imprimir', $material))->assertNotFound();
    }
}
