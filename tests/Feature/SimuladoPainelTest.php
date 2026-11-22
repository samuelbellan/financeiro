<?php

namespace Tests\Feature;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\EditalCargo;
use App\Models\Concursos\EditalTopico;
use App\Models\Concursos\Perfil;
use App\Models\Concursos\Questao;
use App\Models\Concursos\Simulado;
use App\Models\Concursos\Tentativa;
use App\Models\User;
use App\Services\Concursos\EditalImportador;
use App\Services\Concursos\MotorMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Painel de desempenho e simulado, sobre o edital de exemplo com questões inventadas. */
class SimuladoPainelTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private User $user;

    private Perfil $perfil;

    private int $seq = 7000;

    protected function setUp(): void
    {
        parent::setUp();
        config(['concursos.enabled' => true]);
        $this->setUpBancoConcursos();
        Carbon::setTestNow('2026-11-23 08:00:00');
        $d = json_decode(file_get_contents(resource_path('schemas/concursos/exemplos/edital-ufms-152-2026-cargo-523.json')), true);
        app(EditalImportador::class)->gravar($d, 'edital.json', ['caminho' => 'x', 'sha256' => str_repeat('d', 64)]);
        $this->user = User::factory()->create();
        $this->perfil = Perfil::create(['user_id' => $this->user->id, 'cargo_id' => EditalCargo::value('id'),
            'minutos_semana' => array_fill_keys(['1', '2', '3', '4', '5', '6', '7'], 60)]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function topico(string $area, string $ordem): EditalTopico
    {
        return EditalTopico::where('ordem', $ordem)->whereHas('disciplina.area', fn ($q) => $q->where('nome', $area))->firstOrFail();
    }

    /** @return list<Questao> */
    private function questoes(EditalTopico $t, int $n): array
    {
        $disc = Disciplina::firstOrCreate(['nome' => 'Disciplina Inventada']);
        $a = Assunto::create(['disciplina_id' => $disc->id, 'nome' => "Assunto {$t->id}-{$this->seq}"]);
        DB::connection(config('concursos.connection'))->table('edital_topico_assunto')->insert(['topico_id' => $t->id, 'assunto_id' => $a->id]);
        $saida = [];
        for ($i = 0; $i < $n; $i++) {
            $q = Questao::create(['fonte' => 'tec', 'id_externo' => (string) $this->seq++, 'banca' => 'X', 'banca_sigla' => 'X',
                'ano' => 2024, 'tipo' => 'ME', 'enunciado' => 'Enunciado inventado.', 'gabarito' => 'A']);
            foreach (['A', 'B', 'C', 'D', 'E'] as $o => $l) {
                $q->alternativas()->create(['letra' => $l, 'texto' => "alt {$l}", 'ordem' => $o + 1]);
            }
            $q->assuntos()->attach($a->id, ['principal' => true]);
            $saida[] = $q;
        }

        return $saida;
    }

    public function test_simulado_segue_o_quadro_de_provas_e_avisa_area_sem_questoes(): void
    {
        $this->questoes($this->topico('Conhecimentos Específicos', '8.1'), 30);
        $this->questoes($this->topico('Conhecimentos Específicos', '7.1'), 30);
        $this->questoes($this->topico('Legislação', '2'), 15);

        $this->actingAs($this->user)->post('/concursos/simulados', ['tipo' => 'completo'])->assertRedirect();
        $s = Simulado::sole();
        $comp = collect($s->composicao)->keyBy('area');
        $this->assertCount(20, $comp['Conhecimentos Específicos']['questao_ids']);
        $this->assertCount(10, $comp['Legislação']['questao_ids']);
        $this->assertSame([], $comp['Língua Portuguesa']['questao_ids']);
        $this->assertSame(15, $comp['Língua Portuguesa']['previstas']);
        $this->assertCount(30, array_unique($s->questao_ids));
        $this->assertSame(120, $s->duracao_min); // 30 das 60 questões entraram → metade das 4 h

        // Já há um em andamento: não cria outro.
        $this->actingAs($this->user)->post('/concursos/simulados', ['tipo' => 'rapido'])->assertRedirect(route('concursos.simulados.show', $s));
        $this->assertSame(1, Simulado::count());

        $this->actingAs($this->user)->get("/concursos/simulados/{$s->id}")->assertOk()
            ->assertSee('Entregar')->assertSee('Sem questões no banco para esta área')->assertDontSee('alt A →');
    }

    public function test_autosave_entrega_corrige_por_area_e_gera_tentativas(): void
    {
        $this->questoes($this->topico('Conhecimentos Específicos', '8.1'), 20);
        $this->questoes($this->topico('Legislação', '2'), 10);
        $s = app(\App\Services\Concursos\SimuladoService::class)->criar($this->perfil, 'completo');
        [$q1, $q2, $q3] = array_slice($s->questao_ids, 0, 3);

        $this->actingAs($this->user)->postJson("/concursos/simulados/{$s->id}/respostas", ['respostas' => [
            $q1 => ['r' => 'A'], $q2 => ['r' => 'B', 'chute' => true], 999999 => ['r' => 'A'], $q3 => ['r' => 'Z'],
        ]])->assertOk()->assertJson(['salvas' => 2]);

        $this->actingAs($this->user)->post("/concursos/simulados/{$s->id}/entregar", ['respostas' => [$q3 => ['r' => 'A']]])
            ->assertRedirect(route('concursos.simulados.show', $s));

        $s->refresh();
        $this->assertNotNull($s->finalizado_em);
        $this->assertSame(2, $s->acertos);
        $pontos = collect($s->resultado)->keyBy('area');
        $this->assertEquals($pontos->sum('pontos'), $s->nota_total);
        $this->assertSame(4.0, (float) $s->nota_total); // 2 acertos × 2 pt
        $this->assertSame(3, Tentativa::where('simulado_id', $s->id)->count());
        $this->assertSame('chute', Tentativa::where('questao_id', $q2)->value('certeza'));

        // O erro (chute) entra na revisão do dia seguinte.
        $this->assertContains($q2, app(MotorMeta::class)->revisoesVencidas($this->user->id, Carbon::parse('2026-11-24')));

        $this->actingAs($this->user)->get("/concursos/simulados/{$s->id}")->assertOk()->assertSee('Gabarito')->assertSee('em branco');
        $this->actingAs($this->user)->postJson("/concursos/simulados/{$s->id}/respostas", ['respostas' => []])->assertStatus(409);
    }

    public function test_prazo_esgotado_entrega_sozinho_e_outro_aluno_nao_ve(): void
    {
        $this->questoes($this->topico('Conhecimentos Específicos', '8.1'), 10);
        $s = app(\App\Services\Concursos\SimuladoService::class)->criar($this->perfil, 'rapido');
        $this->assertSame(28, $s->duracao_min); // 7 das 20 do rápido → 7/20 de 80 min

        $outro = User::factory()->create();
        $this->actingAs($outro)->get("/concursos/simulados/{$s->id}")->assertNotFound();

        Carbon::setTestNow('2026-11-23 09:30:00');
        $this->actingAs($this->user)->get("/concursos/simulados/{$s->id}")->assertOk()->assertSee('Gabarito');
        $this->assertNotNull($s->fresh()->finalizado_em);
    }

    public function test_painel_mostra_nota_estimada_e_curador_ve_aluno(): void
    {
        $qs = $this->questoes($this->topico('Conhecimentos Específicos', '8.1'), 12);
        foreach ($qs as $i => $q) {
            Tentativa::create(['user_id' => $this->user->id, 'questao_id' => $q->id, 'resposta' => $i < 9 ? 'A' : 'B', 'gabarito' => 'A',
                'correta' => $i < 9, 'certeza' => $i < 10 ? 'sei' : 'chute', 'motivo_erro' => $i >= 9 ? 'confundi' : null]);
        }

        $this->actingAs($this->user)->get('/concursos/painel')->assertOk()
            ->assertSee('nota estimada')->assertSee('Conhecimentos Específicos')->assertSee('Confundi conceitos parecidos')
            ->assertSee('sem questões no banco');

        $curador = User::factory()->create();
        $curador->forceFill(['is_admin' => true])->save();
        Perfil::create(['user_id' => $curador->id, 'cargo_id' => EditalCargo::value('id'), 'minutos_semana' => ['1' => 60]]);
        $this->actingAs($curador)->get("/concursos/painel?aluno={$this->user->id}")->assertOk()->assertSee('Desempenho de ' . $this->user->name);

        // Aluno comum não espia os outros: o parâmetro é ignorado.
        $this->actingAs($this->user)->get("/concursos/painel?aluno={$curador->id}")->assertOk()->assertDontSee('Desempenho de');
    }
}
