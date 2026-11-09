<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EstudosApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $subjectId;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-08T15:00:00Z');
        $this->user = User::factory()->create();
        DB::table('study_settings')->insert([
            'user_id' => $this->user->id, 'virada_hora' => 1, 'limite_suspeita_min' => 180,
            'meta_diaria_min' => 300, 'timezone' => 'America/Cuiaba', 'updated_at' => now(),
        ]);
        $this->subjectId = $this->subject($this->user, 'D. Civil');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function subject(User $user, string $nome): string
    {
        $id = (string) Str::uuid();
        DB::table('study_subjects')->insert(['id' => $id, 'user_id' => $user->id, 'nome' => $nome, 'cor' => '#123456', 'ordem' => 0]);

        return $id;
    }

    protected function addSession(string $inicio, ?string $fim, array $extra = [], ?User $user = null, ?string $subjectId = null): string
    {
        $id = (string) Str::uuid();
        DB::table('study_sessions')->insert(array_merge([
            'id' => $id, 'user_id' => ($user ?? $this->user)->id, 'subject_id' => $subjectId ?? $this->subjectId,
            'inicio' => $inicio, 'fim' => $fim, 'origem' => 'timer', 'status' => 'ok', 'tipo' => 'teoria',
        ], $extra));

        return $id;
    }

    protected function token(?User $user = null): string
    {
        return $this->actingAs($user ?? $this->user)->postJson(route('estudos.api-tokens.store'))
            ->assertCreated()->json('token');
    }

    public function test_sem_token_ou_com_token_invalido_recusa(): void
    {
        $this->getJson('/api/estudos/dia')->assertUnauthorized();
        $this->getJson('/api/estudos/dia', ['Authorization' => 'Bearer errado'])->assertUnauthorized();
    }

    public function test_dia_respeita_fuso_e_virada_e_soma_como_o_app(): void
    {
        // 05/11 em Cuiabá (UTC-4), virada à 1h: de 05/11 05:00Z até 06/11 05:00Z.
        $this->addSession('2026-11-05 07:37:00', '2026-11-05 08:22:00'); // 45 min, cronômetro
        $this->addSession('2026-11-05 16:00:00', '2026-11-05 16:25:00', ['origem' => 'manual', 'tipo' => 'revisao']); // 25
        $this->addSession('2026-11-06 04:30:00', '2026-11-06 05:30:00'); // cruza a virada: 30 min no dia
        $this->addSession('2026-11-05 04:00:00', '2026-11-05 04:50:00'); // dia anterior: fora
        $this->addSession('2026-11-05 10:00:00', '2026-11-05 11:00:00', ['deleted_at' => '2026-11-05 12:00:00']); // excluída
        $this->addSession('2026-11-05 22:37:00', null, ['status' => 'suspeita']); // aberta e suspeita: não conta

        $json = $this->getJson('/api/estudos/dia?data=2026-11-05', ['Authorization' => 'Bearer ' . $this->token()])
            ->assertOk()->json();

        $this->assertSame('America/Cuiaba', $json['fuso']);
        $this->assertSame('2026-11-05T05:00:00.000Z', $json['periodo']['inicio']);
        $this->assertEquals(100, $json['totais']['total_min']);
        $this->assertSame('1h40', $json['totais']['total_horas']);
        $this->assertEquals(75, $json['totais']['cronometro_min']);
        $this->assertEquals(25, $json['totais']['manual_min']);
        $this->assertEquals(45, $json['totais']['max_foco_min']);
        $this->assertSame('03:37', $json['totais']['primeiro_inicio_local']);
        $this->assertFalse($json['totais']['meta_atingida']);
        $this->assertSame(1, $json['totais']['sessoes_excluidas']);
        $this->assertCount(4, $json['sessoes']);
        $this->assertCount(1, $json['sessoes_excluidas']);
        $this->assertTrue($json['sessoes_excluidas'][0]['excluida']);
        $this->assertFalse(collect($json['sessoes'])->contains('excluida', true));
        $this->assertEquals(['D. Civil' => 100], $json['por_materia']);
        $this->assertEquals(['teoria' => 75, 'revisao' => 25], $json['por_tipo']);
    }

    public function test_sem_data_usa_o_dia_de_estudo_atual(): void
    {
        $json = $this->getJson('/api/estudos/dia', ['Authorization' => 'Bearer ' . $this->token()])->assertOk()->json();
        $this->assertSame('2026-11-08', $json['data']);
    }

    public function test_so_ve_os_proprios_dados(): void
    {
        $outro = User::factory()->create();
        $this->addSession('2026-11-05 08:00:00', '2026-11-05 09:00:00', [], $outro, $this->subject($outro, 'Outra'));

        $json = $this->getJson('/api/estudos/dia?data=2026-11-05', ['Authorization' => 'Bearer ' . $this->token()])->json();
        $this->assertSame([], $json['sessoes']);
    }

    public function test_token_guardado_so_como_hash_e_revogavel(): void
    {
        $token = $this->token();
        $this->assertDatabaseMissing('study_api_tokens', ['token_hash' => $token]);
        $this->assertDatabaseHas('study_api_tokens', ['token_hash' => hash('sha256', $token)]);

        $this->actingAs($this->user)->deleteJson(route('estudos.api-tokens.destroy'))->assertOk();
        $this->getJson('/api/estudos/dia', ['Authorization' => 'Bearer ' . $token])->assertUnauthorized();
    }

    public function test_api_nao_aceita_escrita(): void
    {
        $this->postJson('/api/estudos/dia', [], ['Authorization' => 'Bearer ' . $this->token()])->assertStatus(405);
    }

    public function test_app_recebe_as_urls_da_api(): void
    {
        $this->actingAs($this->user)->get(route('estudos.index'))
            ->assertOk()
            ->assertSee('data-api-tokens-url="/estudos/api-tokens"', false)
            ->assertSee('/api/estudos/dia', false);
    }

    public function test_excluida_sem_fim_conta_ate_a_exclusao_e_nao_aparece_nos_dias_seguintes(): void
    {
        // Cronômetro esquecido em 05/11 18:37 (Cuiabá) e descartado em 06/11 03:34.
        $this->addSession('2026-11-05 22:37:00', null, ['status' => 'suspeita', 'deleted_at' => '2026-11-06 07:34:00']);
        // Outro esquecido em 02/11 e descartado no mesmo dia: não pode aparecer em 05/11.
        $this->addSession('2026-11-02 23:53:00', null, ['deleted_at' => '2026-11-03 01:00:00']);
        $token = $this->token();

        $dia5 = $this->getJson('/api/estudos/dia?data=2026-11-05', ['Authorization' => 'Bearer ' . $token])->json();
        $this->assertCount(1, $dia5['sessoes_excluidas']);
        $this->assertEquals(383, $dia5['sessoes_excluidas'][0]['minutos_no_dia']); // até a virada (06/11 05:00Z)
        $this->assertFalse($dia5['sessoes_excluidas'][0]['em_andamento']);
        $this->assertEquals(0, $dia5['totais']['total_min']);

        $dia6 = $this->getJson('/api/estudos/dia?data=2026-11-06', ['Authorization' => 'Bearer ' . $token])->json();
        $this->assertEquals(154, $dia6['sessoes_excluidas'][0]['minutos_no_dia']); // 05:00Z até 07:34Z
    }
}
