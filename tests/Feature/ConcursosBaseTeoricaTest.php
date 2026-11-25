<?php

namespace Tests\Feature;

use App\Models\Concursos\Disciplina;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialAnotacao;
use App\Models\Concursos\MaterialCapitulo;
use App\Models\Concursos\MaterialQuestao;
use App\Models\Concursos\MaterialSecao;
use App\Models\User;
use App\Services\Concursos\Livro\BaseTeorica;
use App\Services\Concursos\Livro\Documento;
use App\Services\Concursos\Livro\MarkdownLivro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Resumo em Markdown no livro + grifos das alternativas das questões erradas. Conteúdo inventado. */
class ConcursosBaseTeoricaTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private const MD = <<<'MD'
    # Resumo Inventado

    ## Sumário

    * [Tema Um](#tema-um)

    ## Tema Um

    ### Regra Base

    A regra inventada diz que o **saldo sobe** com débito.

    * **Exceção:** contas redutoras funcionam ao contrário.
      * Exemplo aninhado: redutora de teste.

    > Citação inventada com R\$ 10,00 e fórmula $\text{A} = \text{P} + \text{PL}$.

    ### Patrimônio

    Seção com título repetido, de nível 3.

    ## Patrimônio

    O patrimônio inventado tem bens e direitos.

    ## Tema Dois

    Texto do tema dois.
    MD;

    private Material $material;

    private MaterialCapitulo $cap1;

    private MaterialCapitulo $cap2;

    protected function setUp(): void
    {
        parent::setUp();
        config(['concursos.enabled' => true]);
        $this->setUpBancoConcursos();
        $user = User::factory()->create();
        $disc = Disciplina::firstOrCreate(['nome' => 'Contabilidade Inventada']);
        $this->material = Material::create(['user_id' => $user->id, 'disciplina_id' => $disc->id, 'slug' => 'ctb', 'titulo' => 'Contabilidade Inventada']);
        $this->cap1 = MaterialCapitulo::create(['material_id' => $this->material->id, 'titulo' => 'Conceito', 'ordem' => 1,
            'conteudo' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Ponto antigo do caderno.']]]]]]);
        $this->cap2 = MaterialCapitulo::create(['material_id' => $this->material->id, 'titulo' => 'Outro', 'ordem' => 2]);
    }

    private function questao(array $dados): MaterialQuestao
    {
        $secao = MaterialSecao::firstOrCreate(['material_id' => $this->material->id, 'chave' => 'c01'], ['titulo' => 'C01']);

        return MaterialQuestao::create($dados + ['material_id' => $this->material->id, 'secao_id' => $secao->id, 'chave' => 'q' . uniqid(), 'ordem' => 0]);
    }

    private function plano(array $destaques): array
    {
        return [
            'material' => $this->material->id,
            'fonte' => 'Resumo Inventado',
            'secoes' => [
                ['titulo' => 'Tema Um', 'capitulo' => $this->cap1->id],
                ['titulo' => 'Patrimônio', 'capitulo' => $this->cap1->id],
                ['titulo' => 'Tema Dois', 'capitulo' => $this->cap2->id],
            ],
            'destaques' => $destaques,
        ];
    }

    public function test_markdown_vira_nos_do_editor_com_listas_citacao_e_sem_latex(): void
    {
        $nos = app(MarkdownLivro::class)->nos(self::MD);
        $doc = app(Documento::class)->limpar(['type' => 'doc', 'content' => $nos]);
        $texto = app(Documento::class)->texto($doc);

        $this->assertStringContainsString('A = P + PL', $texto);
        $this->assertStringContainsString('R$ 10,00', $texto);
        $this->assertStringNotContainsString('\\text', $texto);
        $tipos = array_column($doc['content'], 'type');
        $this->assertContains('blockquote', $tipos);
        $lista = collect($doc['content'])->first(fn ($n) => $n['type'] === 'bulletList' && str_contains(json_encode($n, JSON_UNESCAPED_UNICODE), 'Exceção'));
        $this->assertSame('bulletList', $lista['content'][0]['content'][1]['type'] ?? null, 'sublista fica aninhada no item');
    }

    public function test_formulas_de_matematica_financeira_viram_texto_legivel(): void
    {
        $m = app(MarkdownLivro::class);

        $this->assertSame('VP = VF/(1 + i)ᵗ', $m->semLatex('$VP = \frac{VF}{(1 + i)^{t}}$'));
        $this->assertSame('VP = 3.900/(1 + 0,40)', $m->semLatex('$VP = \frac{3.900}{1 + 0,40}$'));
        $this->assertSame('VF = 2.000 × [((1 + 0,02)⁶ - 1)/0,02]', $m->semLatex('$VF = 2.000 \times \left[\frac{(1 + 0,02)^{6} - 1}{0,02}\right]$'));
        $this->assertSame('M = C × e^(i × t) ≈ √1,21', $m->semLatex('$M = C \times e^{i \times t} \approx \sqrt{1,21}$'));
        $this->assertSame('P_final = J₁ + VF_TOTAL_ENTRADAS', $m->semLatex('$P_{final} = J_{1} + VF_{TOTAL\_ENTRADAS}$'));
    }

    public function test_coloca_o_resumo_no_topo_e_grifa_as_alternativas_com_nota(): void
    {
        $me = $this->questao(['tipo' => 'ME', 'id_externo' => '900001', 'origem' => 'BANCA X · 2024 · Órgão Y · Cargo', 'enunciado' => 'Questão inventada.',
            'alternativas' => [['letra' => 'A', 'texto' => 'Saldo sobe com crédito.'], ['letra' => 'B', 'texto' => 'Saldo sobe com débito.']],
            'gabarito' => 'B', 'comentario' => "No TEC você marcou A; gabarito B.\n\nAssunto: Tema"]);
        $ce = $this->questao(['tipo' => 'CE', 'id_externo' => '900002', 'enunciado' => "Julgue o item.\n\nRedutoras funcionam igual ao grupo.", 'gabarito' => 'E',
            'comentario' => 'No TEC você marcou C; gabarito E.']);

        $r = app(BaseTeorica::class)->aplicar($this->plano([
            ['q' => $me->id, 'letra' => 'B', 'trecho' => 'saldo sobe com débito', 'por_que' => 'Ativo aumenta a débito.'],
            ['q' => $me->id, 'letra' => 'A', 'trecho' => 'saldo sobe com débito', 'por_que' => 'Crédito diminui o ativo.'],
            ['q' => $ce->id, 'trecho' => 'contas redutoras funcionam ao contrário', 'por_que' => 'É o inverso do grupo.'],
            ['q' => $me->id, 'letra' => 'B', 'trecho' => 'texto que não existe', 'por_que' => 'x'],
        ]), self::MD);

        $this->assertSame(['capitulos' => 2, 'secoes' => 3, 'destaques' => 3], array_slice($r, 0, 3));
        $this->assertCount(1, $r['sem_trecho']);

        $cap1 = $this->cap1->fresh();
        $this->assertStringStartsWith('Base teórica: Resumo Inventado', $cap1->texto);
        $this->assertStringContainsString('Seção com título repetido', $cap1->texto, 'o ### Patrimônio vai junto com o ## Tema Um');
        $this->assertStringContainsString('O patrimônio inventado', $cap1->texto, 'o ## Patrimônio também entra');
        $this->assertStringContainsString('Meus pontos de erro', $cap1->texto);
        $this->assertGreaterThan(strpos($cap1->texto, 'Meus pontos de erro'), strpos($cap1->texto, 'Ponto antigo do caderno.'));
        $this->assertStringNotContainsString('Tema Dois', $cap1->texto);
        $this->assertStringContainsString('Texto do tema dois', $this->cap2->fresh()->texto);

        $notas = MaterialAnotacao::where('capitulo_id', $cap1->id)->get()->keyBy('trecho');
        $this->assertCount(2, $notas);
        $saldo = $notas['saldo sobe com débito'];
        $this->assertSame('rosa', $saldo->cor, 'a alternativa marcada pinta de rosa');
        $this->assertStringContainsString('✔ Q900001 (BANCA X 2024 · Órgão Y) — alternativa B é o gabarito', $saldo->nota);
        $this->assertStringContainsString('✘ Q900001 (BANCA X 2024 · Órgão Y) — você marcou A (gabarito B)', $saldo->nota);
        $this->assertStringContainsString('→ Crédito diminui o ativo.', $saldo->nota);
        $redutora = $notas['contas redutoras funcionam ao contrário'];
        $this->assertSame('rosa', $redutora->cor);
        $this->assertStringContainsString('item ERRADO (você marcou C): “Redutoras funcionam igual ao grupo.”', $redutora->nota);

        $json = json_encode($cap1->conteudo, JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('"text":"saldo sobe","marks":[{"type":"bold"},{"type":"grifo","attrs":{"cor":"rosa","id":"' . $saldo->marca . '"}}]', $json, 'o grifo atravessa o negrito sem perdê-lo');

        // Rodar de novo não duplica texto nem notas.
        app(BaseTeorica::class)->aplicar($this->plano([
            ['q' => $me->id, 'letra' => 'B', 'trecho' => 'saldo sobe com débito', 'por_que' => 'Ativo aumenta a débito.'],
        ]), self::MD);
        $cap1 = $this->cap1->fresh();
        $this->assertSame(1, substr_count($cap1->texto, 'Base teórica: Resumo Inventado'));
        $this->assertSame(2, MaterialAnotacao::where('capitulo_id', $cap1->id)->count());
        $nota = MaterialAnotacao::where('marca', $saldo->marca)->first();
        $this->assertSame(1, substr_count($nota->nota, 'alternativa B é o gabarito'), 'a mesma alternativa é trocada, não repetida');
        $this->assertStringContainsString('você marcou A', $nota->nota, 'linha de outra rodada fica');
        $this->assertSame('rosa', $nota->cor);
    }

    public function test_segundo_resumo_entra_depois_do_primeiro_e_cria_capitulo_novo(): void
    {
        $q = $this->questao(['tipo' => 'CE', 'id_externo' => '900003', 'enunciado' => 'Item.', 'gabarito' => 'C']);
        app(BaseTeorica::class)->aplicar($this->plano([]), self::MD);

        $md2 = "## Tema Novo\n\nTexto do segundo resumo sobre o débito.\n\n## Tema Sem Capítulo\n\nAnálise inventada de índices.\n";
        app(BaseTeorica::class)->aplicar([
            'material' => $this->material->id, 'fonte' => 'Resumo Dois',
            'secoes' => [['titulo' => 'Tema Novo', 'capitulo' => $this->cap1->id], ['titulo' => 'Tema Sem Capítulo', 'capitulo' => 'Análise Inventada']],
        ], $md2);
        $texto = $this->cap1->fresh()->texto;
        $this->assertSame(1, substr_count($texto, 'Meus pontos de erro'));
        $this->assertTrue(strpos($texto, 'Base teórica: Resumo Inventado') < strpos($texto, 'Base teórica: Resumo Dois'));
        $this->assertTrue(strpos($texto, 'Texto do segundo resumo') < strpos($texto, 'Meus pontos de erro'));
        $novo = MaterialCapitulo::where('material_id', $this->material->id)->where('titulo', 'Análise Inventada')->firstOrFail();
        $this->assertStringContainsString('Análise inventada de índices', $novo->texto);
        $this->assertSame(3, $novo->ordem);

        // Plano só de destaques procura no livro inteiro.
        $r = app(BaseTeorica::class)->aplicar(['material' => $this->material->id, 'destaques' => [
            ['q' => $q->id, 'trecho' => 'Análise inventada de índices', 'por_que' => 'Está no capítulo novo.'],
        ]], '');
        $this->assertSame(1, $r['destaques']);
        $this->assertSame('verde', MaterialAnotacao::where('capitulo_id', $novo->id)->value('cor'));
    }

    public function test_cifrao_sem_escape_e_dinheiro_e_refazer_troca_o_texto_no_lugar(): void
    {
        $texto = app(Documento::class)->texto(['type' => 'doc', 'content' => app(MarkdownLivro::class)->nos("Dispensa abaixo de R$ 2 milhões; limite de R$2.000 e fórmula \$\\text{A} \\ge 0\$.")]);
        $this->assertStringContainsString('R$ 2 milhões', $texto);
        $this->assertStringContainsString('R$2.000', $texto);
        $this->assertStringContainsString('A ≥ 0', $texto);

        app(BaseTeorica::class)->aplicar($this->plano([]), self::MD);
        $md2 = "## Tema Novo\n\nVersão antiga.\n";
        $plano2 = ['material' => $this->material->id, 'fonte' => 'Resumo Dois', 'secoes' => [['titulo' => 'Tema Novo', 'capitulo' => $this->cap1->id]]];
        app(BaseTeorica::class)->aplicar($plano2, $md2);
        app(BaseTeorica::class)->aplicar($plano2, str_replace('antiga', 'corrigida', $md2), true);

        $t = $this->cap1->fresh()->texto;
        $this->assertStringNotContainsString('Versão antiga', $t);
        $this->assertSame(1, substr_count($t, 'Base teórica: Resumo Dois'));
        $this->assertTrue(strpos($t, 'Versão corrigida') < strpos($t, 'Meus pontos de erro'));
        $this->assertTrue(strpos($t, 'Base teórica: Resumo Inventado') < strpos($t, 'Versão corrigida'));
        $this->assertStringContainsString('Ponto antigo do caderno.', $t);
    }

    public function test_nota_escrita_a_mao_no_mesmo_trecho_fica_em_cima(): void
    {
        $q = $this->questao(['tipo' => 'CE', 'enunciado' => 'Item.', 'gabarito' => 'C']);
        app(BaseTeorica::class)->aplicar($this->plano([]), self::MD);
        // Você grifou e anotou o trecho antes.
        $cap = $this->cap1->fresh();
        [$doc] = app(\App\Services\Concursos\Livro\Grifador::class)->grifar($cap->conteudo, 'patrimônio inventado', 'minha-nota', 'amarelo');
        $cap->update(['conteudo' => $doc]);
        MaterialAnotacao::create(['capitulo_id' => $cap->id, 'marca' => 'minha-nota', 'cor' => 'amarelo', 'trecho' => 'patrimônio inventado', 'nota' => 'Minha anotação.']);

        app(BaseTeorica::class)->aplicar($this->plano([['q' => $q->id, 'trecho' => 'O patrimônio inventado tem bens', 'por_que' => 'Porque sim.']]), self::MD);

        $nota = MaterialAnotacao::where('marca', 'minha-nota')->value('nota');
        $this->assertStringStartsWith('Minha anotação.', $nota);
        $this->assertStringContainsString('→ Porque sim.', $nota);
    }

    public function test_comando_le_o_plano_e_o_markdown_ao_lado(): void
    {
        $dir = storage_path('framework/testing/base-' . uniqid());
        File::ensureDirectoryExists($dir);
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($dir));
        File::put("{$dir}/resumo.md", self::MD);
        File::put("{$dir}/plano.json", json_encode(['arquivo' => 'resumo.md'] + $this->plano([])));

        $this->artisan('concursos:material-base', ['plano' => "{$dir}/plano.json"])
            ->expectsOutputToContain('3 seções em 2 capítulos novos')
            ->assertSuccessful();
        $this->artisan('concursos:material-base', ['plano' => "{$dir}/nada.json"])->assertFailed();
    }
}
