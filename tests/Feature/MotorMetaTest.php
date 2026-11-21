<?php

namespace Tests\Feature;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\EditalCargo;
use App\Models\Concursos\EditalTopico;
use App\Models\Concursos\Meta;
use App\Models\Concursos\MetaAtividade;
use App\Models\Concursos\Perfil;
use App\Models\Concursos\Questao;
use App\Models\Concursos\Tentativa;
use App\Models\User;
use App\Services\Concursos\EditalImportador;
use App\Services\Concursos\MotorMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/**
 * Motor da meta sobre o edital de exemplo (estrutura real, pública) com questões inventadas.
 * Específicos vale 20 × 2 pt; Legislação 10 × 2 pt.
 */
class MotorMetaTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private User $user;

    private Perfil $perfil;

    private int $seq = 5000;

    protected function setUp(): void
    {
        parent::setUp();
        config(['concursos.enabled' => true]);
        $this->setUpBancoConcursos();
        Carbon::setTestNow('2026-11-23 08:00:00'); // segunda-feira
        $d = json_decode(file_get_contents(resource_path('schemas/concursos/exemplos/edital-ufms-152-2026-cargo-523.json')), true);
        app(EditalImportador::class)->gravar($d, 'edital.json', ['caminho' => 'x', 'sha256' => str_repeat('d', 64)]);
        $this->user = User::factory()->create();
        $this->perfil = Perfil::create(['user_id' => $this->user->id, 'cargo_id' => EditalCargo::value('id'),
            'minutos_semana' => ['1' => 120, '2' => 120, '3' => 120, '4' => 120, '5' => 120, '6' => 0, '7' => 60]]);
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

    /** Cria $n questões inventadas num assunto novo ligado ao tópico. @return list<Questao> */
    private function questoes(EditalTopico $t, int $n): array
    {
        $disc = Disciplina::firstOrCreate(['nome' => 'Disciplina Inventada']);
        $a = Assunto::create(['disciplina_id' => $disc->id, 'nome' => "Assunto do tópico {$t->id}-" . $this->seq]);
        DB::connection(config('concursos.connection'))->table('edital_topico_assunto')->insert(['topico_id' => $t->id, 'assunto_id' => $a->id]);
        $saida = [];
        for ($i = 0; $i < $n; $i++) {
            $q = Questao::create(['fonte' => 'tec', 'id_externo' => (string) $this->seq++, 'banca' => 'BANCA X', 'banca_sigla' => 'BANCA X',
                'ano' => 2024, 'tipo' => 'ME', 'enunciado' => 'Enunciado inventado.', 'gabarito' => 'A']);
            foreach (['A', 'B', 'C', 'D', 'E'] as $o => $l) {
                $q->alternativas()->create(['letra' => $l, 'texto' => "alt {$l}", 'ordem' => $o + 1]);
            }
            $q->assuntos()->attach($a->id, ['principal' => true]);
            $saida[] = $q;
        }

        return $saida;
    }

    private function tentar(Questao $q, bool $correta, string $certeza, string $quando): void
    {
        $t = Tentativa::create(['user_id' => $this->user->id, 'questao_id' => $q->id, 'resposta' => $correta ? 'A' : 'B',
            'gabarito' => 'A', 'correta' => $correta, 'certeza' => $certeza]);
        $t->forceFill(['created_at' => Carbon::parse($quando)])->save();
    }

    private function planejar(?string $dia = null): array
    {
        return app(MotorMeta::class)->planejar($this->perfil->fresh(), Carbon::parse($dia ?? 'today'));
    }

    public function test_nao_vi_poe_teoria_antes_das_questoes_e_respeita_o_tempo(): void
    {
        $licit = $this->topico('Conhecimentos Específicos', '8.1');
        $this->questoes($licit, 30);
        $this->perfil->declarar($licit->id, 'nao_vi');

        $plano = $this->planejar();
        $this->assertSame(['teoria', 'questoes'], array_slice(array_column($plano, 'tipo'), 0, 2));
        $this->assertSame([$licit->id, $licit->id], array_slice(array_column($plano, 'topico_id'), 0, 2));
        $this->assertCount(10, $plano[1]['questao_ids']);
        $this->assertStringContainsString('Conhecimentos Específicos', $plano[1]['motivo']);
        $this->assertLessThanOrEqual(120, array_sum(array_column($plano, 'minutos')));
    }

    public function test_folga_nao_gera_atividade(): void
    {
        $this->questoes($this->topico('Conhecimentos Específicos', '8.1'), 30);
        $this->assertSame([], $this->planejar('2026-11-28')); // sábado = 0 min
    }

    public function test_dominio_alto_derruba_a_prioridade_e_area_que_vale_mais_sobe(): void
    {
        $a = $this->topico('Conhecimentos Específicos', '8.1');
        $b = $this->topico('Conhecimentos Específicos', '8.2');
        $qa = $this->questoes($a, 20);
        $this->questoes($b, 20);
        foreach (array_slice($qa, 0, 10) as $q) {
            $this->tentar($q, true, 'sei', '2026-11-10');
        }

        $ix = app(MotorMeta::class)->indices($this->perfil, today())->keyBy(fn ($i) => $i['topico']->id);
        $this->assertEqualsWithDelta($ix[$a->id]['valor'], $ix[$b->id]['valor'], 0.001); // mesma incidência
        $this->assertGreaterThan($ix[$b->id]['dominio'], $ix[$a->id]['dominio']);
        $this->assertGreaterThan($ix[$a->id]['prioridade'], $ix[$b->id]['prioridade']);

        // Mesmo número de questões: um tópico de Inglês (5 × 1 pt, 2 tópicos) vale menos que um de Específicos.
        $ing = $this->topico('Língua Inglesa', '1');
        $this->questoes($ing, 20);
        $ix = app(MotorMeta::class)->indices($this->perfil, today())->keyBy(fn ($i) => $i['topico']->id);
        $this->assertGreaterThan($ix[$ing->id]['valor'], $ix[$b->id]['valor']);
    }

    public function test_contagem_do_tec_define_o_peso_mesmo_com_poucas_questoes_baixadas(): void
    {
        $a = $this->topico('Conhecimentos Específicos', '8.1');
        $b = $this->topico('Conhecimentos Específicos', '8.2');
        $this->questoes($a, 30); // muitas baixadas, mas pouco cobrado no TEC
        $this->questoes($b, 2);  // poucas baixadas, muito cobrado no TEC

        $disc = Disciplina::firstOrCreate(['nome' => 'Disciplina Inventada']);
        $pai = Assunto::create(['disciplina_id' => $disc->id, 'nome' => 'Pai Inventado']);
        $filho = Assunto::create(['disciplina_id' => $disc->id, 'nome' => 'Filho Inventado', 'parent_id' => $pai->id]);
        $db = DB::connection(config('concursos.connection'));
        // b liga o pai E o filho: a contagem do pai (200) já inclui a do filho (150), não soma 350.
        $db->table('edital_topico_assunto')->insert([['topico_id' => $b->id, 'assunto_id' => $pai->id], ['topico_id' => $b->id, 'assunto_id' => $filho->id]]);
        $db->table('assunto_incidencias')->insert([
            ['assunto_id' => $pai->id, 'caderno' => 'especificos', 'questoes' => 200],
            ['assunto_id' => $filho->id, 'caderno' => 'especificos', 'questoes' => 150],
        ]);

        $motor = app(MotorMeta::class);
        $this->assertSame([$b->id => 200], $motor->incidenciaTec([$a->id, $b->id]));
        $ix = $motor->indices($this->perfil, today())->keyBy(fn ($i) => $i['topico']->id);
        $this->assertGreaterThan($ix[$a->id]['valor'] * 5, $ix[$b->id]['valor']);
        $this->assertSame(200, $ix[$b->id]['questoes_tec']);
    }

    public function test_acerto_no_chute_e_erro_entram_na_revisao_com_intervalos_crescentes(): void
    {
        [$q1, $q2, $q3] = $this->questoes($this->topico('Conhecimentos Específicos', '8.1'), 3);
        $this->tentar($q1, false, 'sei', '2026-11-22 10:00');   // erro ontem → vence hoje (1 dia)
        $this->tentar($q2, true, 'chute', '2026-11-22 10:00');  // chute ontem → vence hoje
        $this->tentar($q3, false, 'sei', '2026-11-21 10:00');   // erro anteontem...
        $this->tentar($q3, true, 'sei', '2026-11-22 10:00');    // ...acertou ontem → próximo em 3 dias

        $motor = app(MotorMeta::class);
        $this->assertEqualsCanonicalizing([$q1->id, $q2->id], $motor->revisoesVencidas($this->user->id, today()));
        $this->assertContains($q3->id, $motor->revisoesVencidas($this->user->id, Carbon::parse('2026-11-25')));

        $plano = $this->planejar();
        $this->assertSame('revisao', $plano[0]['tipo']);
        $this->assertEqualsCanonicalizing([$q1->id, $q2->id], $plano[0]['questao_ids']);
    }

    public function test_rede_de_seguranca_sugere_teoria_com_acerto_baixo(): void
    {
        $t = $this->topico('Conhecimentos Específicos', '8.1');
        $qs = $this->questoes($t, 30);
        $this->perfil->declarar($t->id, 'vi_tudo');
        foreach (array_slice($qs, 0, 12) as $i => $q) {
            $this->tentar($q, $i < 4, 'sei', '2026-11-15');
        }

        $teorias = array_filter($this->planejar(), fn ($a) => $a['tipo'] === 'teoria' && $a['topico_id'] === $t->id);
        $this->assertCount(1, $teorias);
        $this->assertStringContainsString('abaixo de 50%', array_values($teorias)[0]['motivo']);
    }

    public function test_meta_e_congelada_e_adiada_volta_no_dia_seguinte(): void
    {
        $this->questoes($this->topico('Conhecimentos Específicos', '8.1'), 30);
        $motor = app(MotorMeta::class);
        $meta = $motor->metaDoDia($this->perfil, today());
        $ids = $meta->atividades->pluck('id')->all();
        $this->questoes($this->topico('Legislação', '1'), 30);
        $this->assertSame($ids, $motor->metaDoDia($this->perfil, today())->atividades->pluck('id')->all());

        $primeira = $meta->atividades->first();
        $primeira->update(['status' => 'adiada', 'adiamentos' => 1]);
        $amanha = $motor->metaDoDia($this->perfil, Carbon::parse('2026-11-24'));
        $this->assertSame($primeira->questao_ids, $amanha->atividades->first()->questao_ids);
        $this->assertStringStartsWith('Adiada 1 vez.', $amanha->atividades->first()->motivo);
        $this->assertSame(1, Meta::whereDate('data', '2026-11-24')->count());
    }

    public function test_fluxo_completo_perfil_meta_e_resolucao(): void
    {
        $this->perfil->delete();
        $t = $this->topico('Conhecimentos Específicos', '8.1');
        $qs = $this->questoes($t, 12);

        $this->actingAs($this->user)->get('/concursos/meta')->assertRedirect(route('concursos.perfil'));
        $this->actingAs($this->user)->get('/concursos/perfil')->assertOk()->assertSee('O que você já estudou?')->assertSee($t->texto);
        $this->actingAs($this->user)->post('/concursos/perfil', [
            'cargo_id' => EditalCargo::value('id'), 'minutos' => [1 => 30, 2 => 30, 3 => 30, 4 => 30, 5 => 30, 6 => 0, 7 => 0],
            'topicos' => [$t->id => 'vi_tudo'],
        ])->assertRedirect(route('concursos.meta'));

        $this->actingAs($this->user)->get('/concursos/meta')->assertOk()->assertSee('Começar');
        $a = MetaAtividade::where('tipo', 'questoes')->firstOrFail();
        $this->assertCount(10, $a->questao_ids);

        $this->actingAs($this->user)->get("/concursos/questoes/resolver?atividade={$a->id}")
            ->assertRedirect(route('concursos.questoes.show', ['questao' => $a->questao_ids[0], 'filtro' => 'nao', 'atividade' => $a->id]));
        foreach ($a->questao_ids as $qid) {
            $this->actingAs($this->user)->post("/concursos/questoes/{$qid}/responder", ['resposta' => 'A', 'certeza' => 'sei', 'atividade' => $a->id]);
        }
        $this->assertSame(10, Tentativa::where('origem', 'meta')->count());
        $this->actingAs($this->user)->get("/concursos/questoes/resolver?atividade={$a->id}&depois={$a->questao_ids[9]}")
            ->assertRedirect(route('concursos.meta'));
        $this->assertSame('feita', $a->fresh()->status);

        // Atividade de outro aluno não abre.
        $outro = User::factory()->create();
        $this->actingAs($outro)->get("/concursos/questoes/resolver?atividade={$a->id}")->assertNotFound();
        $this->actingAs($outro)->post("/concursos/meta/atividades/{$a->id}/adiar")->assertNotFound();
    }
}
