<?php

namespace Tests\Feature;

use App\Models\Concursos\Disciplina;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialAnotacao;
use App\Models\Concursos\MaterialCapitulo;
use App\Models\Concursos\MaterialGrifoQuestao;
use App\Models\Concursos\MaterialQuestao;
use App\Models\Concursos\MaterialSecao;
use App\Models\Concursos\MaterialTentativa;
use App\Models\User;
use App\Services\Concursos\Livro\GrifosQuestoes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Fase 2: grifos por trecho ligados às questões, cor pela última tentativa. Conteúdo inventado. */
class ConcursosGrifosQuestoesTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private User $user;

    private Material $material;

    private MaterialCapitulo $cap;

    protected function setUp(): void
    {
        parent::setUp();
        config(['concursos.enabled' => true]);
        $this->setUpBancoConcursos();
        $this->user = User::factory()->create();
        $disc = Disciplina::firstOrCreate(['nome' => 'Direito Inventado']);
        $this->material = Material::create(['user_id' => $this->user->id, 'disciplina_id' => $disc->id, 'slug' => 'dinv', 'titulo' => 'Direito Inventado']);
        $p = fn (array $filhos) => ['type' => 'paragraph', 'content' => $filhos];
        $this->cap = MaterialCapitulo::create(['material_id' => $this->material->id, 'titulo' => 'Tema', 'ordem' => 1, 'conteudo' => ['type' => 'doc', 'content' => [
            $p([['type' => 'text', 'text' => 'O prazo inventado é de '], ['type' => 'text', 'text' => 'trinta dias', 'marks' => [['type' => 'bold']]], ['type' => 'text', 'text' => ' contados da ciência.']]),
            $p([['type' => 'text', 'text' => 'Grifo antigo automático', 'marks' => [['type' => 'grifo', 'attrs' => ['cor' => 'rosa', 'id' => 'q0123456789ab']]]]]),
            $p([['type' => 'text', 'text' => 'Meu grifo feito à mão', 'marks' => [['type' => 'grifo', 'attrs' => ['cor' => 'azul', 'id' => 'nmeu1']]]]]),
            $p([['type' => 'text', 'text' => 'O montante é M = C × (1 + i)ᵗ no regime composto.']]),
        ]]]);
        MaterialAnotacao::create(['capitulo_id' => $this->cap->id, 'marca' => 'q0123456789ab', 'cor' => 'rosa', 'nota' => '✘ Q1 — velho']);
        MaterialAnotacao::create(['capitulo_id' => $this->cap->id, 'marca' => 'nmeu1', 'cor' => 'azul', 'nota' => 'Minha nota']);
    }

    private function questao(string $tec, string $resultado): MaterialQuestao
    {
        $secao = MaterialSecao::firstOrCreate(['material_id' => $this->material->id, 'chave' => 'c01'], ['titulo' => 'C01']);

        return MaterialQuestao::create(['material_id' => $this->material->id, 'secao_id' => $secao->id, 'chave' => 'q' . $tec, 'id_externo' => $tec,
            'tipo' => 'CE', 'enunciado' => 'Item inventado.', 'gabarito' => 'C', 'resultado_tec' => $resultado, 'ordem' => (int) $tec]);
    }

    private function importar(array $linhas): array
    {
        return app(GrifosQuestoes::class)->importar($this->material, $linhas);
    }

    public function test_importa_trecho_em_markdown_cru_e_apaga_so_os_grifos_automaticos(): void
    {
        $errada = $this->questao('111', 'errada');
        $certa = $this->questao('222', 'certa');
        $r = $this->importar([
            ['id' => '111', 'trechos' => [['arquivo' => 'x.md', 'trecho' => 'O prazo inventado é de **trinta dias** contados da ciência.', 'letra' => null, 'por_que' => 'A banca trocou para 60.']]],
            ['id' => '222', 'trechos' => [['arquivo' => 'x.md', 'trecho' => 'O prazo inventado é de **trinta dias** contados da ciência.', 'letra' => null, 'por_que' => 'Igual.']]],
            ['id' => '222', 'trechos' => []], // repetida: vale a primeira
            ['id' => '333', 'trechos' => [['arquivo' => 'x.md', 'trecho' => 'qualquer']]], // fora do material
            ['id' => '111', 'trechos' => []],
        ]);

        $this->assertSame(2, $r['questoes']);
        $this->assertSame(['333'], $r['sem_questao']);
        $this->assertSame(1, $r['grifos']);
        $this->assertSame([], $r['nao_achados']);
        $this->assertEqualsCanonicalizing([$errada->id, $certa->id], MaterialGrifoQuestao::pluck('questao_id')->all());

        $json = json_encode($this->cap->fresh()->conteudo, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('q0123456789ab', $json);
        $this->assertStringContainsString('nmeu1', $json);
        $this->assertSame(['nmeu1', MaterialGrifoQuestao::value('marca')], MaterialAnotacao::orderBy('id')->pluck('marca')->all());
        $nota = MaterialAnotacao::where('marca', MaterialGrifoQuestao::value('marca'))->first();
        $this->assertSame('rosa', $nota->cor);
        $this->assertStringContainsString('✘ Q111', $nota->nota);
        $this->assertStringContainsString('✔ Q222', $nota->nota);
        $this->assertStringContainsString('→ A banca trocou para 60.', $nota->nota);

        // Rodar de novo não duplica.
        $this->importar([['id' => '111', 'trechos' => [['arquivo' => 'x.md', 'trecho' => 'O prazo inventado é de **trinta dias** contados da ciência.']]]]);
        $this->assertSame(1, MaterialGrifoQuestao::count());
    }

    public function test_formula_latex_e_cor_pela_ultima_tentativa(): void
    {
        $q = $this->questao('444', 'errada');
        $this->importar([['id' => '444', 'trechos' => [['arquivo' => 'x.md', 'trecho' => 'O montante é $M = C \times (1 + i)^t$ no regime composto.']]]]);
        $marca = MaterialGrifoQuestao::value('marca');
        $this->assertNotNull($marca);
        $cor = fn () => app(GrifosQuestoes::class)->aplicar($this->cap->fresh(), $this->cap->fresh()->conteudo, collect())[2][$marca]['cor'];

        $this->assertSame('rosa', $cor());
        MaterialTentativa::create(['user_id' => $this->user->id, 'questao_id' => $q->id, 'resposta' => 'C', 'acertou' => true]);
        $this->assertSame('amarelo', $cor()); // errou no TEC, acertou a última

        $q->update(['resultado_tec' => 'certa']);
        $this->assertSame('verde', $cor());
        MaterialTentativa::create(['user_id' => $this->user->id, 'questao_id' => $q->id, 'resposta' => 'E', 'acertou' => false]);
        $this->assertSame('rosa', $cor());
    }

    public function test_capitulo_mostra_atalho_e_refazer_filtra_pelas_questoes_do_grifo(): void
    {
        $q = $this->questao('555', 'errada');
        $this->questao('666', 'errada');
        $this->importar([['id' => '555', 'trechos' => [['arquivo' => 'x.md', 'trecho' => 'trinta dias contados da ciência']]]]);

        $resp = $this->actingAs($this->user)->get(route('concursos.material.capitulo', [$this->material, $this->cap]))->assertOk();
        $resp->assertSee('grifosQ', false);
        // O sumário conta as questões ligadas pelos grifos (o capítulo de teoria não tem capitulo_id nelas).
        $this->actingAs($this->user)->get(route('concursos.material.show', $this->material))->assertOk()
            ->assertSee('data-erros="1" data-questoes="1"', false);
        $url = route('concursos.material.refazer', $this->material) . '?' . http_build_query(['ids' => (string) $q->id, 'situacao' => 'todas', 'iniciar' => 1, 'qtd' => 200]);
        $this->actingAs($this->user)->get($url)->assertOk()->assertViewHas('fila', fn ($fila) => array_column($fila, 'id') === [$q->id]);

        // Tirar o grifo no editor apaga a ligação.
        $doc = $this->cap->fresh()->conteudo;
        $doc['content'][0]['content'] = [['type' => 'text', 'text' => 'Sem grifo.']];
        $this->actingAs($this->user)->putJson(route('concursos.material.capitulos.conteudo', $this->cap), ['conteudo' => $doc])->assertOk();
        $this->assertSame(0, MaterialGrifoQuestao::count());
    }
}
