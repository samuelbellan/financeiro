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

    public function test_periodo_traz_um_resumo_e_uma_linha_por_dia(): void
    {
        $outra = $this->subject($this->user, 'D. Tributário');
        $this->addSession('2026-11-03 08:00:00', '2026-11-03 14:00:00'); // 360 min: bate a meta (300)
        $this->addSession('2026-11-05 07:37:00', '2026-11-05 08:22:00', ['tipo' => 'questoes'], null, $outra); // 45
        $this->addSession('2026-11-06 04:30:00', '2026-11-06 05:30:00'); // cruza a virada: 30 em 05/11, 30 em 06/11
        $this->addSession('2026-11-04 10:00:00', '2026-11-04 11:00:00', ['deleted_at' => '2026-11-04 12:00:00']); // excluída

        $json = $this->getJson('/api/estudos/periodo?desde=2026-11-03&ate=2026-11-06', ['Authorization' => 'Bearer ' . $this->token()])
            ->assertOk()->json();

        $this->assertCount(4, $json['dias']);
        $this->assertSame(['2026-11-03', '2026-11-04', '2026-11-05', '2026-11-06'], array_column($json['dias'], 'data'));
        $this->assertSame('ter', $json['dias'][0]['dia_semana']);
        $this->assertEquals([360, 0, 75, 30], array_column($json['dias'], 'total_min'));
        $this->assertSame(1, $json['dias'][1]['sessoes_excluidas']);
        $this->assertArrayNotHasKey('sessoes', $json['dias'][0]);

        $r = $json['resumo'];
        $this->assertEquals(465, $r['total_min']);
        $this->assertSame('7h45', $r['total_horas']);
        $this->assertSame(3, $r['dias_estudados']);
        $this->assertSame(1, $r['dias_sem_estudo']);
        $this->assertEquals(116.3, $r['media_por_dia_min']);
        $this->assertSame(1, $r['dias_meta_atingida']);
        $this->assertEquals(360, $r['max_foco_min']);
        $this->assertEquals(['D. Civil' => 420, 'D. Tributário' => 45], $json['por_materia']);
    }

    public function test_periodo_com_sessoes_e_limite_de_31_dias(): void
    {
        $this->addSession('2026-11-05 07:37:00', '2026-11-05 08:22:00');
        $token = $this->token();

        $json = $this->getJson('/api/estudos/periodo?desde=2026-11-05&ate=2026-11-05&sessoes=1', ['Authorization' => 'Bearer ' . $token])
            ->assertOk()->json();
        $this->assertCount(1, $json['dias'][0]['sessoes']);
        $this->assertSame([], $json['dias'][0]['sessoes_excluidas']);

        $this->getJson('/api/estudos/periodo?desde=2026-10-01&ate=2026-10-31', ['Authorization' => 'Bearer ' . $token])->assertOk();
        $this->getJson('/api/estudos/periodo?desde=2026-10-01&ate=2026-11-01', ['Authorization' => 'Bearer ' . $token])->assertStatus(422);
        $this->getJson('/api/estudos/periodo?desde=2026-11-05&ate=2026-11-01', ['Authorization' => 'Bearer ' . $token])->assertStatus(422);
        $this->getJson('/api/estudos/periodo?desde=2026-11-01&ate=2026-11-05')->assertUnauthorized();
    }

    public function test_periodo_conta_so_dias_ja_decorridos_na_media(): void
    {
        // Agora é 08/11; o período vai até 10/11: só 06, 07 e 08 contam para a média.
        $this->addSession('2026-11-07 08:00:00', '2026-11-07 09:30:00'); // 90
        $json = $this->getJson('/api/estudos/periodo?desde=2026-11-06&ate=2026-11-10', ['Authorization' => 'Bearer ' . $this->token()])->json();
        $this->assertSame(5, $json['resumo']['dias']);
        $this->assertSame(3, $json['resumo']['dias_decorridos']);
        $this->assertSame(2, $json['resumo']['dias_sem_estudo']);
        $this->assertEquals(30, $json['resumo']['media_por_dia_min']);
    }

    public function test_periodo_com_hora_corta_no_instante_exato(): void
    {
        // 02/11 em Cuiabá: sessão 06:00–08:00 local (10:00Z–12:00Z). Meta começou 08:01 de Brasília = 07:01 Cuiabá.
        $this->addSession('2026-11-02 10:00:00', '2026-11-02 12:00:00');
        $this->addSession('2026-11-03 10:00:00', '2026-11-03 11:00:00'); // 60 min, dia inteiro
        $this->addSession('2026-11-04 10:00:00', '2026-11-04 11:00:00'); // 06:00–07:00 Cuiabá, depois do fim
        $token = $this->token();

        $json = $this->getJson('/api/estudos/periodo?desde=2026-11-02T08:01-03:00&ate=2026-11-04T05:30', ['Authorization' => 'Bearer ' . $token])
            ->assertOk()->json();

        $this->assertSame('2026-11-02T11:01:00.000Z', $json['janela']['inicio']);
        $this->assertSame('2026-11-02 07:01', $json['janela']['inicio_local']);
        $this->assertSame('2026-11-04T09:30:00.000Z', $json['janela']['fim']); // 05:30 no fuso do app (Cuiabá)
        $this->assertSame(['2026-11-02', '2026-11-03', '2026-11-04'], array_column($json['dias'], 'data'));
        $this->assertEquals([59, 60, 0], array_column($json['dias'], 'total_min'));
        $this->assertSame([true, false, true], array_column($json['dias'], 'dia_parcial'));
        $this->assertSame('2026-11-02 07:01', $json['dias'][0]['recorte_local']['inicio']);
        $this->assertEquals(119, $json['resumo']['total_min']);

        // Só data continua pegando o dia inteiro.
        $dia = $this->getJson('/api/estudos/periodo?desde=2026-11-02&ate=2026-11-02', ['Authorization' => 'Bearer ' . $token])->json();
        $this->assertEquals(120, $dia['dias'][0]['total_min']);
        $this->assertFalse($dia['dias'][0]['dia_parcial']);

        $this->getJson('/api/estudos/periodo?desde=2026-11-04T05:30&ate=2026-11-04T05:00', ['Authorization' => 'Bearer ' . $token])->assertStatus(422);
        $this->getJson('/api/estudos/periodo?desde=ontem&ate=2026-11-04', ['Authorization' => 'Bearer ' . $token])->assertStatus(422);
    }
}
