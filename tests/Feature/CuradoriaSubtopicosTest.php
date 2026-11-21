<?php

namespace Tests\Feature;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\Questao;
use App\Models\Concursos\Subtopico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Curadoria dos subtópicos propostos pelo Gemini. Dados inventados. */
class CuradoriaSubtopicosTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private User $curador;

    private Assunto $a;

    private Assunto $irmao;

    protected function setUp(): void
    {
        parent::setUp();
        config(['concursos.enabled' => true]);
        $this->setUpBancoConcursos();
        $this->curador = User::factory()->create();
        $this->curador->forceFill(['is_admin' => true])->save();
        $d = Disciplina::create(['nome' => 'Gestão Inventada']);
        $this->a = Assunto::create(['disciplina_id' => $d->id, 'nome' => 'Noções Inventadas']);
        $this->irmao = Assunto::create(['disciplina_id' => $d->id, 'nome' => 'Tipos Inventados']);
    }

    private function sub(Assunto $a, string $nome, string $status = 'pendente'): Subtopico
    {
        return Subtopico::create(['assunto_id' => $a->id, 'slug' => \Illuminate\Support\Str::slug($nome), 'nome' => $nome, 'status' => $status]);
    }

    /** @param array<int, bool> $subs subtopico_id => principal */
    private function questao(array $subs): Questao
    {
        static $n = 100;
        $q = Questao::create(['fonte' => 'tec', 'id_externo' => (string) $n++, 'banca' => 'X', 'banca_sigla' => 'X', 'ano' => 2024,
            'tipo' => 'ME', 'enunciado' => 'Inventada.', 'gabarito' => 'A']);
        foreach ($subs as $sid => $principal) {
            $q->subtopicos()->attach($sid, ['principal' => $principal]);
        }

        return $q;
    }

    private function vinculos(Questao $q): array
    {
        return DB::connection(config('concursos.connection'))->table('questao_subtopico')->where('questao_id', $q->id)
            ->orderBy('subtopico_id')->pluck('principal', 'subtopico_id')->map(fn ($p) => (bool) $p)->all();
    }

    public function test_so_o_curador_acessa(): void
    {
        $aluno = User::factory()->create();
        $this->actingAs($aluno)->get('/concursos/curadoria')->assertForbidden();
        $this->actingAs($this->curador)->get('/concursos/curadoria')->assertOk();
    }

    public function test_lista_mostra_pendentes_e_pagina_do_assunto_sugere_parecidos(): void
    {
        $abc = $this->sub($this->a, 'Classificação ABC (critério de valor)');
        $this->sub($this->irmao, 'Classificação ABC de materiais', 'aprovado');
        $this->sub($this->a, 'Matriz GUT');

        $this->actingAs($this->curador)->get('/concursos/curadoria')->assertOk()->assertSee('Noções Inventadas')->assertSee('2 pendente(s)');
        $this->actingAs($this->curador)->get("/concursos/curadoria/assuntos/{$this->a->id}")->assertOk()
            ->assertSee('Parece com')->assertSee('Classificação ABC de materiais')->assertSee('Tipos Inventados');
    }

    public function test_aprovar_com_nome_editado(): void
    {
        $s = $this->sub($this->a, 'Nome ruim');
        $this->actingAs($this->curador)->post("/concursos/curadoria/subtopicos/{$s->id}", ['acao' => 'aprovar', 'nome' => 'Nome bom', 'descricao' => 'O que cai.'])
            ->assertRedirect();
        $s->refresh();
        $this->assertSame(['aprovado', 'Nome bom', 'O que cai.', 'nome-ruim'], [$s->status, $s->nome, $s->descricao, $s->slug]);
    }

    public function test_juntar_move_questoes_mantem_principal_e_apaga_origem(): void
    {
        $origem = $this->sub($this->a, 'ABC repetido');
        $destino = $this->sub($this->irmao, 'Curva ABC', 'aprovado');
        $outro = $this->sub($this->a, 'Outro');
        $q1 = $this->questao([$origem->id => true, $outro->id => false]);
        $q2 = $this->questao([$destino->id => false, $origem->id => true]); // já estava no destino

        $this->actingAs($this->curador)->post("/concursos/curadoria/subtopicos/{$origem->id}", ['acao' => 'juntar', 'destino' => $destino->id])
            ->assertRedirect()->assertSessionHas('status');

        $this->assertNull(Subtopico::find($origem->id));
        $this->assertSame([$destino->id => true, $outro->id => false], $this->vinculos($q1));
        $this->assertSame([$destino->id => true], $this->vinculos($q2));
        $this->assertSame('aprovado', $destino->fresh()->status);
    }

    public function test_nao_junta_entre_disciplinas_nem_consigo_mesmo(): void
    {
        $s = $this->sub($this->a, 'Um');
        $fora = $this->sub(Assunto::create(['disciplina_id' => Disciplina::create(['nome' => 'Outra'])->id, 'nome' => 'X']), 'Dois');

        $this->actingAs($this->curador)->from("/concursos/curadoria/assuntos/{$this->a->id}")
            ->post("/concursos/curadoria/subtopicos/{$s->id}", ['acao' => 'juntar', 'destino' => $fora->id])
            ->assertSessionHasErrors('acao');
        $this->actingAs($this->curador)->post("/concursos/curadoria/subtopicos/{$s->id}", ['acao' => 'juntar', 'destino' => $s->id])
            ->assertSessionHasErrors('acao');
        $this->assertNotNull($s->fresh());
    }

    public function test_excluir_promove_o_proximo_subtopico_a_principal(): void
    {
        $s = $this->sub($this->a, 'Some');
        $fica = $this->sub($this->a, 'Fica');
        $q = $this->questao([$s->id => true, $fica->id => false]);

        $this->actingAs($this->curador)->post("/concursos/curadoria/subtopicos/{$s->id}", ['acao' => 'excluir'])->assertRedirect();

        $this->assertNull(Subtopico::find($s->id));
        $this->assertSame([$fica->id => true], $this->vinculos($q));
    }

    public function test_aprovar_todos_e_aprovado_vai_no_proximo_pacote(): void
    {
        $this->sub($this->a, 'Um');
        $this->sub($this->a, 'Dois');
        $this->actingAs($this->curador)->post("/concursos/curadoria/assuntos/{$this->a->id}/aprovar-todos")->assertRedirect();
        $this->assertSame(0, Subtopico::where('status', 'pendente')->count());

        $ctx = (new \App\Services\Concursos\PacoteGerador)->contexto($this->a->load('disciplina'), 1, []);
        $this->assertSame(['dois', 'um'], collect($ctx['subtopicos_aprovados'])->pluck('id')->sort()->values()->all());
    }
}
