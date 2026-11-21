<?php

namespace Tests\Feature;

use App\Models\Concursos\EditalCargo;
use App\Models\Concursos\Meta;
use App\Models\Concursos\MetaAtividade;
use App\Models\Concursos\Perfil;
use App\Models\User;
use App\Services\Concursos\EditalImportador;
use App\Services\Concursos\MotorMeta;
use App\Services\Concursos\TempoEstudosSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Ligação meta ↔ cronômetro do /estudos: o tempo real calibra o tamanho da meta, nunca o domínio. */
class TempoEstudosTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['concursos.enabled' => true, 'concursos.estudos.token' => 'tok', 'services.estudos.cloud_url' => 'https://nuvem.test']);
        $this->setUpBancoConcursos();
        Carbon::setTestNow('2026-11-23 08:00:00');
        Cache::flush();
        $d = json_decode(file_get_contents(resource_path('schemas/concursos/exemplos/edital-ufms-152-2026-cargo-523.json')), true);
        app(EditalImportador::class)->gravar($d, 'edital.json', ['caminho' => 'x', 'sha256' => str_repeat('d', 64)]);
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Atividade de questões já feita num dia passado, com N questões e tempo real. */
    private function atividade(string $dia, int $questoes, ?float $minutosReais, string $status = 'feita'): MetaAtividade
    {
        $meta = Meta::where('user_id', $this->user->id)->whereDate('data', $dia)->first()
            ?? Meta::create(['user_id' => $this->user->id, 'data' => $dia, 'minutos_orcamento' => 120]);

        return $meta->atividades()->create(['ordem' => $meta->atividades()->count() + 1, 'tipo' => 'questoes',
            'questao_ids' => range(1, $questoes), 'minutos' => $questoes * 3, 'motivo' => 'x',
            'status' => $status, 'minutos_reais' => $minutosReais]);
    }

    private function dia(array $sessoes): array
    {
        return ['dias' => [['data' => '2026-11-22', 'sessoes' => $sessoes]]];
    }

    public function test_sincroniza_minutos_das_sessoes_ligadas_a_atividades(): void
    {
        $a = $this->atividade('2026-11-22', 10, null);
        $b = $this->atividade('2026-11-22', 10, null);
        Http::fake(['nuvem.test/api/estudos/periodo*' => Http::response($this->dia([
            ['atividade_ref' => "concursos:{$a->id}", 'minutos_no_dia' => 20.5, 'conta_no_total' => true],
            ['atividade_ref' => "concursos:{$a->id}", 'minutos_no_dia' => 10, 'conta_no_total' => true],   // pausa e volta
            ['atividade_ref' => "concursos:{$b->id}", 'minutos_no_dia' => 99, 'conta_no_total' => false],  // excluída
            ['atividade_ref' => null, 'minutos_no_dia' => 50, 'conta_no_total' => true],                  // estudo avulso
        ]))]);

        $r = app(TempoEstudosSync::class)->sincronizar();

        $this->assertSame(['atividades' => 1, 'minutos' => 30.5], $r);
        $this->assertEquals(30.5, $a->fresh()->minutos_reais);
        $this->assertNull($b->fresh()->minutos_reais);
        Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer tok') && str_contains($req->url(), 'sessoes=1'));
    }

    public function test_pagina_sincroniza_no_maximo_a_cada_15_minutos_e_ignora_falha(): void
    {
        Http::fake(['*' => Http::response(['message' => 'fora do ar'], 503)]);
        $sync = app(TempoEstudosSync::class);

        $sync->talvez();
        $sync->talvez();
        Http::assertSentCount(1);

        Carbon::setTestNow('2026-11-23 08:20:00');
        $sync->talvez();
        Http::assertSentCount(2);
    }

    public function test_ritmo_real_calibra_a_meta_com_tres_atividades_ou_mais(): void
    {
        $motor = app(MotorMeta::class);
        $this->atividade('2026-11-20', 10, 50);  // 5,0 min/questão
        $this->atividade('2026-11-21', 10, 40);  // 4,0
        $this->assertSame(['valor' => 3.0, 'calibrado' => false, 'amostras' => 2], $motor->minutosPorQuestao($this->user->id, today()));

        $this->atividade('2026-11-22', 10, 45);  // 4,5
        $this->atividade('2026-11-22', 10, 200, 'pendente'); // não concluída: não entra
        $this->atividade('2026-10-01', 10, 10);  // fora da janela de 21 dias
        $this->assertSame(['valor' => 4.5, 'calibrado' => true, 'amostras' => 3], $motor->minutosPorQuestao($this->user->id, today()));

        // Com 4,5 min/questão, 30 min de folga cabem 6 questões, não 10.
        $perfil = Perfil::create(['user_id' => $this->user->id, 'cargo_id' => EditalCargo::value('id'),
            'minutos_semana' => ['1' => 30, '2' => 30, '3' => 30, '4' => 30, '5' => 30, '6' => 30, '7' => 30]]);
        $t = \App\Models\Concursos\EditalTopico::where('ordem', '8.1')->firstOrFail();
        $disc = \App\Models\Concursos\Disciplina::create(['nome' => 'Disciplina Inventada']);
        $assunto = \App\Models\Concursos\Assunto::create(['disciplina_id' => $disc->id, 'nome' => 'Assunto Inventado']);
        \Illuminate\Support\Facades\DB::connection(config('concursos.connection'))->table('edital_topico_assunto')->insert(['topico_id' => $t->id, 'assunto_id' => $assunto->id]);
        for ($i = 0; $i < 12; $i++) {
            $q = \App\Models\Concursos\Questao::create(['fonte' => 'tec', 'id_externo' => (string) (9000 + $i), 'banca' => 'X', 'banca_sigla' => 'X',
                'ano' => 2024, 'tipo' => 'ME', 'enunciado' => 'Inventada.', 'gabarito' => 'A']);
            $q->assuntos()->attach($assunto->id, ['principal' => true]);
        }
        $plano = $motor->planejar($perfil, today());
        $this->assertCount(6, $plano[0]['questao_ids']);
        $this->assertSame(27, $plano[0]['minutos']);
    }

    public function test_meta_mostra_link_do_cronometro_com_materia_tipo_atividade_e_prova(): void
    {
        Http::fake(['*' => Http::response(['dias' => []])]);
        Perfil::create(['user_id' => $this->user->id, 'cargo_id' => EditalCargo::value('id'),
            'minutos_semana' => array_fill_keys(['1', '2', '3', '4', '5', '6', '7'], 60)]);
        $this->perfilDeclaraNaoVi();

        $html = $this->actingAs($this->user)->get('/concursos/meta')->assertOk()->assertSee('⏱ Cronometrar')->getContent();
        $a = MetaAtividade::firstOrFail();
        preg_match('~https://nuvem\.test/estudos#concursos=([^"]+)"~', $html, $m);
        $dados = json_decode(rawurldecode(html_entity_decode($m[1])), true);
        $this->assertSame("concursos:{$a->id}", $dados['r']);
        $this->assertSame('teoria', $dados['t']);
        $this->assertSame('2027-01-17', $dados['p']);
        $this->assertSame($a->topico->disciplina->nome, $dados['d']);
    }

    private function perfilDeclaraNaoVi(): void
    {
        $t = \App\Models\Concursos\EditalTopico::where('ordem', '8.1')->firstOrFail();
        Perfil::where('user_id', $this->user->id)->first()->declarar($t->id, 'nao_vi');
    }
}
