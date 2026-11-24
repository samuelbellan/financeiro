<?php

namespace Tests\Feature;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\Questao;
use App\Models\Concursos\UsoEvento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Telemetria de uso do módulo. Conteúdo inventado: o repositório é público. */
class ConcursosUsoTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private User $aluno;

    private Questao $questao;

    protected function setUp(): void
    {
        parent::setUp();
        config(['concursos.enabled' => true]);
        $this->setUpBancoConcursos();
        $this->aluno = User::factory()->create(['name' => 'Aluno Inventado']);
        $a = Assunto::create(['disciplina_id' => Disciplina::create(['nome' => 'Disciplina X'])->id, 'nome' => 'Assunto X']);
        $this->questao = Questao::create(['fonte' => 'tec', 'id_externo' => '1', 'banca' => 'B', 'banca_sigla' => 'B', 'ano' => 2024,
            'tipo' => 'ME', 'enunciado' => 'Enunciado inventado.', 'gabarito' => 'B', 'anulada' => false]);
        foreach (['A', 'B'] as $o => $l) {
            $this->questao->alternativas()->create(['letra' => $l, 'texto' => "alt {$l}", 'ordem' => $o + 1]);
        }
        $this->questao->assuntos()->attach($a->id, ['principal' => true]);
    }

    private function curador(): User
    {
        return tap(User::factory()->create(['name' => 'Curador']), fn ($u) => $u->forceFill(['is_owner' => true])->save());
    }

    public function test_registra_tela_aberta_com_ids_e_filtros_sem_o_corpo(): void
    {
        $r = $this->actingAs($this->aluno)->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14) Mobile')
            ->get("/concursos/questoes/{$this->questao->id}?filtro=nao")->assertOk();
        $this->actingAs($this->aluno)->post("/concursos/questoes/{$this->questao->id}/responder", ['resposta' => 'A', 'certeza' => 'acho', 'tempo' => 30]);

        [$tela, $acao] = UsoEvento::orderBy('id')->get()->all();
        $this->assertSame($this->aluno->id, $tela->user_id);
        $this->assertSame('concursos.questoes.show', $tela->rota);
        $this->assertSame(['rota' => ['questao' => $this->questao->id], 'url' => ['filtro' => 'nao']], $tela->parametros);
        $this->assertSame(200, $tela->status);
        $this->assertSame('celular', $tela->dispositivo);
        $r->assertSee($tela->uuid);

        $this->assertSame('concursos.questoes.responder', $acao->rota);
        $this->assertSame('POST', $acao->metodo);
        $this->assertStringNotContainsString('acho', json_encode($acao->parametros));
    }

    public function test_beacon_grava_tempo_visivel_so_no_proprio_evento_e_sem_diminuir(): void
    {
        $this->actingAs($this->aluno)->get('/concursos/questoes')->assertOk();
        $ev = UsoEvento::sole();

        $this->actingAs($this->aluno)->post('/concursos/uso/tempo', ['id' => $ev->uuid, 's' => 95])->assertNoContent();
        $this->actingAs($this->aluno)->post('/concursos/uso/tempo', ['id' => $ev->uuid, 's' => 40])->assertNoContent();
        $this->actingAs(User::factory()->create())->post('/concursos/uso/tempo', ['id' => $ev->uuid, 's' => 999])->assertNoContent();

        $this->assertSame(95, $ev->fresh()->visivel_s);
        $this->assertSame(1, UsoEvento::count()); // o beacon não vira evento
    }

    public function test_painel_so_para_curador_e_mostra_resumo_do_aluno(): void
    {
        $this->actingAs($this->aluno)->get('/concursos/questoes')->assertOk();
        $this->actingAs($this->aluno)->post("/concursos/questoes/{$this->questao->id}/responder", ['resposta' => 'B', 'certeza' => 'sei', 'tempo' => 20]);
        UsoEvento::where('rota', 'concursos.questoes.index')->update(['visivel_s' => 600]);

        $this->actingAs($this->aluno)->get('/concursos/uso')->assertForbidden();

        $curador = $this->curador();
        $this->actingAs($curador)->get('/concursos/uso')->assertOk()->assertSee('Aluno Inventado')->assertSee('10 min');
        $this->actingAs($curador)->get("/concursos/uso?aluno={$this->aluno->id}&dias=30")->assertOk()
            ->assertSee('Lista de questões')->assertSee('Respondeu questão')->assertSee('100% de acerto')->assertSee('20s');
    }

    public function test_falha_na_telemetria_nao_derruba_a_tela(): void
    {
        \Illuminate\Support\Facades\Schema::connection('concursos_pgsql')->drop('uso_eventos');

        $this->actingAs($this->aluno)->get('/concursos/questoes')->assertOk();
    }
}
