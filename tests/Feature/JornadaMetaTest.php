<?php

namespace Tests\Feature;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\EditalCargo;
use App\Models\Concursos\EditalTopico;
use App\Models\Concursos\Meta;
use App\Models\Concursos\Perfil;
use App\Models\Concursos\Questao;
use App\Models\Concursos\Tentativa;
use App\Models\User;
use App\Services\Concursos\EditalImportador;
use App\Services\Concursos\JornadaMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Calendário da semana, sequência, caminho até a prova e trilha do edital, com questões inventadas. */
class JornadaMetaTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private User $user;

    private Perfil $perfil;

    protected function setUp(): void
    {
        parent::setUp();
        config(['concursos.enabled' => true]);
        $this->setUpBancoConcursos();
        Carbon::setTestNow('2026-11-25 10:00:00'); // quarta-feira
        $d = json_decode(file_get_contents(resource_path('schemas/concursos/exemplos/edital-ufms-152-2026-cargo-523.json')), true);
        app(EditalImportador::class)->gravar($d, 'edital.json', ['caminho' => 'x', 'sha256' => str_repeat('d', 64)]);
        $this->user = User::factory()->create();
        $this->perfil = Perfil::create(['user_id' => $this->user->id, 'cargo_id' => EditalCargo::value('id'),
            'minutos_semana' => ['1' => 60, '2' => 60, '3' => 60, '4' => 60, '5' => 60, '6' => 0, '7' => 30]]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @return list<int> */
    private function questoes(int $n): array
    {
        $t = EditalTopico::where('ordem', '8.1')->whereHas('disciplina.area', fn ($q) => $q->where('nome', 'Conhecimentos Específicos'))->firstOrFail();
        $a = Assunto::create(['disciplina_id' => Disciplina::firstOrCreate(['nome' => 'Inventada'])->id, 'nome' => 'Assunto inventado']);
        DB::connection(config('concursos.connection'))->table('edital_topico_assunto')->insert(['topico_id' => $t->id, 'assunto_id' => $a->id]);
        $ids = [];
        for ($i = 0; $i < $n; $i++) {
            $q = Questao::create(['fonte' => 'tec', 'id_externo' => (string) (9000 + $i), 'banca' => 'X', 'banca_sigla' => 'X', 'ano' => 2024,
                'tipo' => 'ME', 'enunciado' => 'Inventado.', 'gabarito' => 'A']);
            $q->assuntos()->attach($a->id, ['principal' => true]);
            $ids[] = $q->id;
        }

        return $ids;
    }

    private function responder(array $ids, string $quando): void
    {
        foreach ($ids as $q) {
            $t = Tentativa::create(['user_id' => $this->user->id, 'questao_id' => $q, 'resposta' => 'A', 'gabarito' => 'A', 'correta' => true, 'certeza' => 'sei']);
            $t->forceFill(['created_at' => $quando])->save();
        }
    }

    public function test_semana_sequencia_e_trilha(): void
    {
        $ids = $this->questoes(10);
        // Segunda: meta completa (teoria feita + 4/4 questões). Terça: metade das questões.
        $seg = Meta::create(['user_id' => $this->user->id, 'data' => '2026-11-23', 'minutos_orcamento' => 60]);
        $seg->atividades()->create(['tipo' => 'teoria', 'minutos' => 20, 'status' => 'feita', 'ordem' => 1, 'motivo' => 'x']);
        $seg->atividades()->create(['tipo' => 'questoes', 'minutos' => 40, 'status' => 'feita', 'ordem' => 2, 'motivo' => 'x', 'questao_ids' => array_slice($ids, 0, 4)]);
        $this->responder(array_slice($ids, 0, 4), '2026-11-23 20:00:00');
        $ter = Meta::create(['user_id' => $this->user->id, 'data' => '2026-11-24', 'minutos_orcamento' => 60]);
        $ter->atividades()->create(['tipo' => 'questoes', 'minutos' => 60, 'status' => 'pendente', 'ordem' => 1, 'motivo' => 'x', 'questao_ids' => array_slice($ids, 4, 6)]);
        $this->responder(array_slice($ids, 4, 3), '2026-11-24 21:00:00');

        $j = app(JornadaMeta::class)->calcular($this->perfil->load('cargo.edital'), today());
        $sem = collect($j['semana'])->keyBy(fn ($d) => $d['data']->toDateString());
        $this->assertCount(7, $j['semana']);
        $this->assertSame('completo', $sem['2026-11-23']['estado']);
        $this->assertSame(30, $sem['2026-11-24']['feito']);
        $this->assertSame('parcial', $sem['2026-11-24']['estado']);
        $this->assertSame('folga', $sem['2026-11-28']['estado']);
        $this->assertSame('futuro', $sem['2026-11-29']['estado']);
        $this->assertSame(2, $j['sequencia']); // hoje ainda sem estudo não quebra
        $this->assertSame(90, $j['semana_total']['feito']);
        $this->assertSame(1, $j['jornada']['semana_atual']);
        $this->assertGreaterThan(0, $j['trilha']['trabalhado']);

        $this->actingAs($this->user)->get('/concursos/meta')->assertOk()
            ->assertSee('Sua semana')->assertSee('Caminho até a prova')->assertSee('Trilha do edital')->assertSee('Atividades de hoje');
        $this->actingAs($this->user)->get('/concursos/meta?dia=2026-11-24')->assertOk()
            ->assertSee('Terça, 24/11')->assertSee('3 de 6 questões')->assertSee('Voltar para hoje');
        // Dia futuro não abre: mostra hoje.
        $this->actingAs($this->user)->get('/concursos/meta?dia=2026-11-27')->assertOk()->assertSee('Atividades de hoje');
    }
}
