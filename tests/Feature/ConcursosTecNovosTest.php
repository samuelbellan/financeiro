<?php

namespace Tests\Feature;

use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialPonto;
use App\Models\Concursos\MaterialQuestao;
use App\Models\User;
use App\Services\Concursos\MaterialImportador;
use App\Services\Concursos\MaterialQuestoesImportador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Caderno do TEC que chega depois: seção no lugar certo e pontos sem reconstruir o material. Dados inventados. */
class ConcursosTecNovosTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['concursos.enabled' => true]);
        $this->setUpBancoConcursos();
        $this->user = User::factory()->create();
        app(MaterialImportador::class)->importar(['slug' => 'inv', 'titulo' => 'Matéria Inventada', 'secoes' => [
            ['chave' => 'c03', 'titulo' => 'C03', 'pontos' => [['chave' => 'p-antigo', 'titulo' => 'Antigo', 'regra' => 'Regra antiga.', 'questoes' => ['1']]]],
            ['chave' => 'c05', 'titulo' => 'C05'],
            ['chave' => 'tc01', 'titulo' => 'TC01'],
        ]], $this->user->id);
        // O grupo "t" começa em 101, como no Tributário.
        Material::where('slug', 'inv')->first()->secoes()->where('chave', 'tc01')->update(['ordem' => 101]);
    }

    private function questao(string $id, string $resultado): array
    {
        return ['chave' => "q{$id}", 'id' => $id, 'tipo' => 'CE', 'enunciado' => "Enunciado {$id}", 'alternativas' => [],
            'gabarito' => 'C', 'assunto' => 'Assunto inventado', 'resultado' => $resultado];
    }

    public function test_secao_nova_entra_entre_as_irmas_com_titulo(): void
    {
        $imp = app(MaterialQuestoesImportador::class);
        $imp->importarTodas(['material' => 'inv', 'secoes' => [
            ['secao' => 'c04', 'titulo' => 'C04 · Assunto inventado', 'origem' => '1 caderno(s)', 'questoes' => [$this->questao('10', 'errada'), $this->questao('11', 'certa')]],
        ]], $this->user->id);
        $imp->importarTodas(['material' => 'inv', 'secoes' => [['secao' => 'c02', 'questoes' => [$this->questao('12', 'certa')]]]], $this->user->id, null, 't');

        $ordem = Material::where('slug', 'inv')->first()->secoes()->orderBy('ordem')->get();
        $this->assertSame(['c03', 'c04', 'c05', 'tc01', 'tc02'], $ordem->pluck('chave')->all());
        $this->assertSame([1, 2, 3, 101, 102], $ordem->pluck('ordem')->all());
        $this->assertSame('C04 · Assunto inventado', $ordem[1]->titulo);
        $this->assertSame('1 caderno(s)', $ordem[1]->origem);
        $this->assertSame(2, MaterialQuestao::where('secao_id', $ordem[1]->id)->count());
    }

    public function test_importar_pontos_mexe_so_na_secao_e_liga_as_questoes(): void
    {
        app(MaterialQuestoesImportador::class)->importarTodas(['material' => 'inv', 'secoes' => [
            ['secao' => 'c03', 'questoes' => [$this->questao('20', 'errada'), $this->questao('21', 'errada')]],
        ]], $this->user->id);

        $this->artisan('concursos:importar-pontos', ['material' => 'inv', '--user' => $this->user->id, 'arquivo' => $this->arquivo([
            ['chave' => 'c03', 'pontos' => [['chave' => 'p-novo', 'titulo' => 'Novo', 'armadilha' => 'Pegadinha.', 'regra' => 'Regra nova.', 'questoes' => ['20', '21']]]],
        ])])->assertSuccessful()->expectsOutputToContain('1 pontos novos, 0 atualizados, 2 questões ligadas');

        $secao = Material::where('slug', 'inv')->first()->secoes()->where('chave', 'c03')->first();
        $pontos = MaterialPonto::where('secao_id', $secao->id)->orderBy('ordem')->get();
        $this->assertSame(['p-antigo', 'p-novo'], $pontos->pluck('chave')->all(), 'o ponto antigo fica e o novo vai para o fim');
        $this->assertSame('C03', $secao->titulo, 'sem título no arquivo, o da seção fica');
        $this->assertSame(2, MaterialQuestao::where('ponto_id', $pontos[1]->id)->count());

        $this->artisan('concursos:importar-pontos', ['material' => 'inv', '--user' => $this->user->id, 'arquivo' => $this->arquivo([['chave' => 'c99', 'pontos' => []]])])
            ->assertFailed()->expectsOutputToContain('Seção "c99" não existe');
    }

    private function arquivo(array $dados): string
    {
        $f = storage_path('framework/testing/pontos-' . uniqid() . '.json');
        @mkdir(dirname($f), 0777, true);
        file_put_contents($f, json_encode($dados));

        return $f;
    }
}
