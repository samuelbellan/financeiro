<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudyRoomsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function addSession(User $user, string $inicio, ?string $fim, array $extra = []): void
    {
        $subject = (string) Str::uuid();
        DB::table('study_subjects')->insert(['id' => $subject, 'user_id' => $user->id, 'nome' => 'X', 'cor' => '#4F86F7', 'ordem' => 0]);
        DB::table('study_sessions')->insert(array_merge([
            'id' => (string) Str::uuid(), 'user_id' => $user->id, 'subject_id' => $subject,
            'inicio' => $inicio, 'fim' => $fim, 'origem' => 'timer', 'status' => 'ok',
        ], $extra));
    }

    public function test_create_join_and_see_members_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-01T15:00:00Z')); // 12:00 em São Paulo
        $ana = User::factory()->create(['is_admin' => true]);
        $bia = User::factory()->create();

        $code = $this->actingAs($ana)->postJson(route('estudos.salas.store'), ['nome' => 'Concursos e afins'])
            ->assertCreated()
            ->assertJsonPath('rooms.0.nome', 'Concursos e afins')
            ->assertJsonPath('rooms.0.dono', true)
            ->json('rooms.0.codigo');

        // Bia: 1h ontem (fica fora), 1h hoje e uma sessão aberta desde 14:30Z.
        $this->addSession($bia, '2026-10-31 13:00:00.000', '2026-10-31 14:00:00.000');
        $this->addSession($bia, '2026-11-01 12:00:00.000', '2026-11-01 13:00:00.000');
        $this->addSession($bia, '2026-11-01 14:30:00.000', null);
        // Excluída: não conta.
        $this->addSession($bia, '2026-11-01 10:00:00.000', '2026-11-01 11:00:00.000', ['deleted_at' => '2026-11-01 11:30:00.000']);

        $response = $this->actingAs($bia)->postJson(route('estudos.salas.join'), ['codigo' => strtolower($code)])->assertOk();
        $members = collect($response->json('rooms.0.membros'))->keyBy('nome');

        $this->assertCount(2, $members);
        $this->assertSame(90 * 60_000, $members[$bia->name]['hoje_ms']);
        $this->assertSame('2026-11-01T14:30:00.000Z', $members[$bia->name]['estudando_desde']);
        $this->assertTrue($members[$bia->name]['eu']);
        $this->assertSame(0, $members[$ana->name]['hoje_ms']);
        $this->assertNull($members[$ana->name]['estudando_desde']);
        $this->assertArrayNotHasKey('subject_id', $members[$bia->name]);
    }

    public function test_today_follows_each_members_timezone_and_day_turnover(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-01T05:30:00Z')); // 01:30 em Cuiabá
        $user = User::factory()->create(['is_admin' => true]);
        DB::table('study_settings')->insert(['user_id' => $user->id, 'timezone' => 'America/Cuiaba', 'virada_hora' => 3]);
        $this->addSession($user, '2026-11-01 04:00:00.000', '2026-11-01 05:00:00.000'); // 00:00–01:00: antes da virada (3h)

        $this->actingAs($user)->postJson(route('estudos.salas.store'), ['nome' => 'Sala'])->assertCreated()
            ->assertJsonPath('rooms.0.membros.0.hoje_ms', 3_600_000); // ainda é o dia 31/10
    }

    public function test_unknown_code_and_leaving(): void
    {
        $ana = User::factory()->create(['is_admin' => true]);
        $bia = User::factory()->create();
        $this->actingAs($ana)->postJson(route('estudos.salas.join'), ['codigo' => 'NOPE99'])->assertUnprocessable();

        $room = $this->actingAs($ana)->postJson(route('estudos.salas.store'), ['nome' => 'Sala'])->json('rooms.0');
        $this->actingAs($bia)->postJson(route('estudos.salas.join'), ['codigo' => $room['codigo']])->assertOk();

        // Dono sai: Bia assume a sala.
        $this->actingAs($ana)->postJson(route('estudos.salas.leave', $room['id']))->assertOk()->assertJsonPath('rooms', []);
        $this->actingAs($bia)->getJson(route('estudos.salas'))->assertJsonPath('rooms.0.dono', true);

        // Último a sair apaga a sala.
        $this->actingAs($bia)->postJson(route('estudos.salas.leave', $room['id']))->assertOk();
        $this->assertDatabaseCount('study_rooms', 0);
    }

    public function test_only_managers_create_and_manage_rooms(): void
    {
        $gestor = User::factory()->create(['is_admin' => true]);
        [$bia, $ze] = User::factory()->count(2)->create();

        $this->actingAs($bia)->postJson(route('estudos.salas.store'), ['nome' => 'Minha sala'])->assertForbidden();
        $this->actingAs($bia)->getJson(route('estudos.salas'))->assertJsonPath('pode_gerenciar', false);

        $room = $this->actingAs($gestor)->postJson(route('estudos.salas.store'), ['nome' => 'Sala'])
            ->assertJsonPath('pode_gerenciar', true)->json('rooms.0');
        foreach ([$bia, $ze] as $user) {
            $this->actingAs($user)->postJson(route('estudos.salas.join'), ['codigo' => $room['codigo']])->assertOk();
        }

        // Membro comum não remove ninguém nem exclui a sala.
        $this->actingAs($bia)->postJson(route('estudos.salas.remove', [$room['id'], $ze->id]))->assertForbidden();
        $this->actingAs($bia)->deleteJson(route('estudos.salas.destroy', $room['id']))->assertForbidden();

        // Gestor remove o Zé e depois exclui a sala.
        $this->actingAs($gestor)->postJson(route('estudos.salas.remove', [$room['id'], $ze->id]))->assertOk()
            ->assertJsonCount(2, 'rooms.0.membros');
        $this->actingAs($gestor)->deleteJson(route('estudos.salas.destroy', $room['id']))->assertOk()->assertJsonPath('rooms', []);
        $this->assertDatabaseCount('study_rooms', 0);
        $this->assertDatabaseCount('study_room_members', 0);
    }

    public function test_managers_only_manage_rooms_they_are_in(): void
    {
        [$ana, $outro] = User::factory()->count(2)->create(['is_admin' => true]);
        $room = $this->actingAs($ana)->postJson(route('estudos.salas.store'), ['nome' => 'Sala'])->json('rooms.0');

        $this->actingAs($outro)->deleteJson(route('estudos.salas.destroy', $room['id']))->assertNotFound();
    }

    public function test_guests_cannot_see_rooms(): void
    {
        $this->getJson(route('estudos.salas'))->assertUnauthorized();
    }
}
