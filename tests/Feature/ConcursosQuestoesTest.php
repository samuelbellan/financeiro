<?php

namespace Tests\Feature;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\Questao;
use App\Models\Concursos\QuestaoResolucao;
use App\Models\Concursos\Tentativa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Questões inventadas: o repositório é público. */
class ConcursosQuestoesTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private User $user;

    private Assunto $pai;

    private Assunto $folha;

    /** @var array<string, Questao> */
    private array $q = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['concursos.enabled' => true]);
        $this->setUpBancoConcursos();
        $this->user = User::factory()->create();
        $d = Disciplina::create(['nome' => 'Direito Inventado']);
        $this->pai = Assunto::create(['disciplina_id' => $d->id, 'nome' => 'Lei Inventada']);
        $this->folha = Assunto::create(['disciplina_id' => $d->id, 'nome' => 'Capítulo Inventado', 'parent_id' => $this->pai->id]);
        $outro = Assunto::create(['disciplina_id' => $d->id, 'nome' => 'Outro Assunto']);
        foreach (['11' => [$this->folha, 'B'], '12' => [$this->folha, 'C'], '13' => [$outro, 'A'], '14' => [$this->folha, null]] as $id => [$a, $gab]) {
            $q = Questao::create(['fonte' => 'tec', 'id_externo' => $id, 'banca' => 'BANCA X', 'banca_sigla' => 'BANCA X', 'ano' => 2024,
                'tipo' => 'ME', 'enunciado' => "Enunciado inventado {$id}.", 'gabarito' => $gab, 'anulada' => $gab === null]);
            foreach (['A', 'B', 'C', 'D'] as $o => $l) {
                $q->alternativas()->create(['letra' => $l, 'texto' => "alternativa {$l} da {$id}", 'ordem' => $o + 1]);
            }
            $q->assuntos()->attach($a->id, ['principal' => true]);
            $this->q[$id] = $q;
        }
    }

    public function test_some_quando_o_modulo_esta_desligado(): void
    {
        config(['concursos.enabled' => false]);
        $this->actingAs($this->user)->get('/concursos/questoes')->assertNotFound();
    }

    public function test_respeita_o_acesso_por_modulo(): void
    {
        $this->user->forceFill(['modulos' => ['estudos']])->save();
        $this->actingAs($this->user)->get('/concursos/questoes')->assertForbidden();
    }

    public function test_lista_assuntos_com_contagem_da_subarvore(): void
    {
        $this->actingAs($this->user)->get('/concursos/questoes')->assertOk()
            ->assertSee('Direito Inventado')->assertSee('Lei Inventada')->assertSee('Capítulo Inventado')
            ->assertSee('0/2 feitas', false); // anulada fica de fora
    }

    public function test_resolver_vai_para_a_primeira_nao_respondida_do_assunto_e_seus_filhos(): void
    {
        $this->actingAs($this->user)->get("/concursos/questoes/resolver?assunto={$this->pai->id}&filtro=nao")
            ->assertRedirect(route('concursos.questoes.show', ['questao' => $this->q['11']->id, 'filtro' => 'nao', 'assunto' => $this->pai->id]));
    }

    public function test_responder_grava_tentativa_e_mostra_gabarito_e_resolucao(): void
    {
        QuestaoResolucao::create(['questao_id' => $this->q['11']->id, 'resumo' => 'Resumo inventado.',
            'alternativas' => [['letra' => 'B', 'correta' => true, 'explicacao' => 'Explicação inventada da B.']]]);

        $r = $this->actingAs($this->user)->post("/concursos/questoes/{$this->q['11']->id}/responder",
            ['resposta' => 'A', 'certeza' => 'acho', 'tempo' => 42, 'assunto' => $this->pai->id, 'filtro' => 'nao']);
        $t = Tentativa::sole();
        $r->assertRedirect(route('concursos.questoes.show', ['questao' => $this->q['11']->id, 't' => $t->id, 'filtro' => 'nao', 'assunto' => $this->pai->id]));
        $this->assertFalse($t->correta);
        $this->assertSame('B', $t->gabarito);
        $this->assertSame(42, $t->tempo_segundos);

        $this->actingAs($this->user)->get("/concursos/questoes/{$this->q['11']->id}?t={$t->id}")->assertOk()
            ->assertSee('Você errou. O gabarito é B.')->assertSee('Explicação inventada da B.')->assertSee('Por que você errou?');
    }

    public function test_motivo_do_erro_e_proxima_questao(): void
    {
        $t = Tentativa::create(['user_id' => $this->user->id, 'questao_id' => $this->q['11']->id, 'resposta' => 'A',
            'gabarito' => 'B', 'correta' => false, 'certeza' => 'sei']);

        $this->actingAs($this->user)->post("/concursos/questoes/tentativas/{$t->id}/motivo",
            ['motivo' => 'confundi', 'assunto' => $this->pai->id, 'filtro' => 'nao'])
            ->assertRedirect(route('concursos.questoes.resolver', ['depois' => $this->q['11']->id, 'filtro' => 'nao', 'assunto' => $this->pai->id]));
        $this->assertSame('confundi', $t->fresh()->motivo_erro);

        // A próxima não respondida do assunto é a 12 (a 13 é de outro assunto, a 14 é anulada).
        $this->actingAs($this->user)->get("/concursos/questoes/resolver?depois={$this->q['11']->id}&assunto={$this->pai->id}&filtro=nao")
            ->assertRedirect(route('concursos.questoes.show', ['questao' => $this->q['12']->id, 'filtro' => 'nao', 'assunto' => $this->pai->id]));
    }

    public function test_filtro_erradas_inclui_acerto_no_chute(): void
    {
        foreach ([['11', 'B', 'chute'], ['12', 'C', 'sei']] as [$id, $resp, $certeza]) {
            Tentativa::create(['user_id' => $this->user->id, 'questao_id' => $this->q[$id]->id, 'resposta' => $resp,
                'gabarito' => $resp, 'correta' => true, 'certeza' => $certeza]);
        }

        $this->actingAs($this->user)->get("/concursos/questoes/resolver?assunto={$this->pai->id}&filtro=erradas")
            ->assertRedirect(route('concursos.questoes.show', ['questao' => $this->q['11']->id, 'filtro' => 'erradas', 'assunto' => $this->pai->id]));
        // A 12 (acerto com certeza) não entra; depois da 11 não sobra nada → volta à lista.
        $this->actingAs($this->user)->get("/concursos/questoes/resolver?depois={$this->q['11']->id}&assunto={$this->pai->id}&filtro=erradas")
            ->assertRedirect(route('concursos.questoes.index'));
    }

    public function test_valida_resposta_certeza_anulada_e_dono_da_tentativa(): void
    {
        $this->actingAs($this->user)->post("/concursos/questoes/{$this->q['11']->id}/responder", ['resposta' => 'B'])
            ->assertSessionHasErrors('certeza');
        $this->actingAs($this->user)->post("/concursos/questoes/{$this->q['11']->id}/responder", ['resposta' => 'E', 'certeza' => 'sei'])
            ->assertSessionHasErrors('resposta');
        $this->actingAs($this->user)->post("/concursos/questoes/{$this->q['14']->id}/responder", ['resposta' => 'A', 'certeza' => 'sei'])
            ->assertStatus(422);

        $alheia = Tentativa::create(['user_id' => $this->user->id + 99, 'questao_id' => $this->q['11']->id, 'resposta' => 'A',
            'gabarito' => 'B', 'correta' => false, 'certeza' => 'sei']);
        $this->actingAs($this->user)->post("/concursos/questoes/tentativas/{$alheia->id}/motivo", ['motivo' => 'confundi'])->assertNotFound();
        $this->actingAs($this->user)->get("/concursos/questoes/{$this->q['11']->id}?t={$alheia->id}")->assertOk()->assertDontSee('Você errou');
    }
}
