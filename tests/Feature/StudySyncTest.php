<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudySyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $device;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->device = (string) Str::uuid();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function sync(array $changes = [], ?string $cursor = null, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->user)->postJson(route('estudos.sync'), [
            'device_id' => $this->device,
            'cursor' => $cursor,
            'changes' => $changes,
        ]);
    }

    protected function makeSubject(array $overrides = []): array
    {
        return array_merge([
            'id' => (string) Str::uuid(),
            'nome' => 'Direito Constitucional',
            'cor' => '#4f86f7',
            'ordem' => 0,
            'arquivada_em' => null,
            'deleted_at' => null,
        ], $overrides);
    }

    protected function makeSession(string $subjectId, array $overrides = []): array
    {
        return array_merge([
            'id' => (string) Str::uuid(),
            'subject_id' => $subjectId,
            'inicio' => '2026-10-30T13:00:00.000Z',
            'fim' => '2026-10-30T14:30:00.000Z',
            'origem' => 'timer',
            'status' => 'ok',
            'deleted_at' => null,
        ], $overrides);
    }

    public function test_sync_requires_authentication(): void
    {
        $this->postJson(route('estudos.sync'), ['device_id' => $this->device])->assertStatus(401);
    }

    public function test_shell_page_loads_for_authenticated_user(): void
    {
        $this->actingAs($this->user)->get(route('estudos.index'))->assertOk();
    }

    public function test_first_sync_creates_records_and_returns_them_with_server_timestamps(): void
    {
        $subject = $this->makeSubject();
        $session = $this->makeSession($subject['id']);

        $response = $this->sync(['subjects' => [$subject], 'sessions' => [$session]])->assertOk();

        $response->assertJsonPath('rejected', []);
        $response->assertJsonPath('changes.subjects.0.id', $subject['id']);
        $response->assertJsonPath('changes.subjects.0.cor', '#4F86F7');
        $response->assertJsonPath('changes.sessions.0.inicio', '2026-10-30T13:00:00.000Z');
        $response->assertJsonPath('changes.sessions.0.device_id', $this->device);
        $this->assertNotNull($response->json('changes.sessions.0.updated_at'));
        $this->assertNotNull($response->json('cursor'));
    }

    public function test_last_sync_wins(): void
    {
        $subject = $this->makeSubject();
        $this->sync(['subjects' => [$subject]])->assertOk();

        $this->sync(['subjects' => [array_merge($subject, ['nome' => 'Versão A'])]])->assertOk();
        $this->sync(['subjects' => [array_merge($subject, ['nome' => 'Versão B'])]])->assertOk();

        $this->assertSame('Versão B', DB::table('study_subjects')->where('id', $subject['id'])->value('nome'));
    }

    public function test_deleted_records_do_not_resurrect(): void
    {
        $subject = $this->makeSubject();
        $session = $this->makeSession($subject['id']);
        $this->sync(['subjects' => [$subject], 'sessions' => [$session]])->assertOk();

        $first = $this->sync(['sessions' => [array_merge($session, ['deleted_at' => '2026-10-31T10:00:00.000Z'])]]);
        $cursor = $first->json('cursor');

        // Outro aparelho, offline, edita a sessão já excluída.
        $response = $this->sync(['sessions' => [array_merge($session, ['fim' => '2026-10-30T15:00:00.000Z'])]], $cursor)->assertOk();

        $row = DB::table('study_sessions')->where('id', $session['id'])->first();
        $this->assertNotNull($row->deleted_at);
        $this->assertStringStartsWith('2026-10-30 14:30:00', $row->fim);
        // O servidor devolve o tombstone para o aparelho que tentou editar.
        $this->assertNotNull(collect($response->json('changes.sessions'))->firstWhere('id', $session['id'])['deleted_at']);
    }

    public function test_cursor_returns_only_recent_changes(): void
    {
        Carbon::setTestNow('2026-10-31 10:00:00');
        $old = $this->makeSubject(['nome' => 'Antiga']);
        $this->sync(['subjects' => [$old]])->assertOk();

        Carbon::setTestNow('2026-10-31 11:00:00');
        $cursor = $this->sync()->json('cursor');

        Carbon::setTestNow('2026-10-31 12:00:00');
        $new = $this->makeSubject(['nome' => 'Nova']);
        $this->sync(['subjects' => [$new]], null, null);

        $ids = collect($this->sync([], $cursor)->json('changes.subjects'))->pluck('id');
        $this->assertTrue($ids->contains($new['id']));
        $this->assertFalse($ids->contains($old['id']));
    }

    public function test_first_sync_omits_deleted_records(): void
    {
        $subject = $this->makeSubject(['deleted_at' => '2026-10-31T10:00:00.000Z']);
        $this->sync(['subjects' => [$subject]])->assertOk();

        $this->assertSame([], $this->sync()->json('changes.subjects'));
    }

    public function test_user_cannot_read_or_write_other_users_records(): void
    {
        $other = User::factory()->create();
        $subject = $this->makeSubject(['nome' => 'Do outro']);
        $this->sync(['subjects' => [$subject]], null, $other)->assertOk();

        $response = $this->sync(['subjects' => [array_merge($subject, ['nome' => 'Invadido'])]])->assertOk();

        $response->assertJsonPath('rejected.0.id', $subject['id']);
        $response->assertJsonPath('changes.subjects', []);
        $this->assertSame('Do outro', DB::table('study_subjects')->where('id', $subject['id'])->value('nome'));
    }

    public function test_session_cannot_reference_other_users_subject(): void
    {
        $other = User::factory()->create();
        $subject = $this->makeSubject();
        $this->sync(['subjects' => [$subject]], null, $other)->assertOk();

        $response = $this->sync(['sessions' => [$this->makeSession($subject['id'])]])->assertOk();

        $response->assertJsonPath('rejected.0.motivo', 'Matéria inexistente.');
        $this->assertSame(0, DB::table('study_sessions')->where('user_id', $this->user->id)->count());
    }

    public function test_invalid_items_are_rejected_without_blocking_valid_ones(): void
    {
        $subject = $this->makeSubject();
        $badColor = $this->makeSubject(['cor' => 'azul']);

        $response = $this->sync([
            'subjects' => [$subject, $badColor],
            'sessions' => [
                $this->makeSession($subject['id'], ['fim' => '2026-10-30T12:00:00.000Z']),
                $this->makeSession($subject['id'], ['inicio' => '2026-10-29T00:00:00.000Z', 'fim' => '2026-10-30T06:00:00.000Z']),
                $this->makeSession($subject['id']),
            ],
        ])->assertOk();

        $this->assertCount(3, $response->json('rejected'));
        $this->assertSame(1, DB::table('study_subjects')->count());
        $this->assertSame(1, DB::table('study_sessions')->count());
    }

    public function test_overlapping_sessions_are_reported(): void
    {
        $subject = $this->makeSubject();
        $a = $this->makeSession($subject['id']);
        $this->sync(['subjects' => [$subject], 'sessions' => [$a]])->assertOk();

        $b = $this->makeSession($subject['id'], ['inicio' => '2026-10-30T14:00:00.000Z', 'fim' => '2026-10-30T15:00:00.000Z']);
        $response = $this->sync(['sessions' => [$b]])->assertOk();

        $response->assertJsonPath('conflicts.0.session_id', $b['id']);
        $response->assertJsonPath('conflicts.0.overlaps_with', $a['id']);
    }

    public function test_adjacent_sessions_do_not_conflict(): void
    {
        $subject = $this->makeSubject();
        $a = $this->makeSession($subject['id']);
        $b = $this->makeSession($subject['id'], ['inicio' => '2026-10-30T14:30:00.000Z', 'fim' => '2026-10-30T15:00:00.000Z']);

        $this->sync(['subjects' => [$subject], 'sessions' => [$a, $b]])->assertOk()->assertJsonPath('conflicts', []);
    }

    public function test_settings_are_saved_and_validated(): void
    {
        $this->sync(['settings' => ['virada_hora' => 3, 'limite_suspeita_min' => 180, 'meta_diaria_min' => 360, 'timezone' => 'America/Sao_Paulo']])
            ->assertOk()
            ->assertJsonPath('changes.settings.virada_hora', 3)
            ->assertJsonPath('changes.settings.meta_diaria_min', 360);

        $this->sync(['settings' => ['virada_hora' => 20, 'limite_suspeita_min' => 180, 'timezone' => 'America/Sao_Paulo']])
            ->assertOk()
            ->assertJsonPath('rejected.0.type', 'settings');
    }

    public function test_ddays_sync(): void
    {
        $dday = ['id' => (string) Str::uuid(), 'titulo' => 'Prova TRF', 'data' => '2027-03-14', 'deleted_at' => null];

        $this->sync(['ddays' => [$dday]])->assertOk()->assertJsonPath('changes.ddays.0.data', '2027-03-14');
    }

    public function test_token_endpoint_returns_fresh_csrf_token(): void
    {
        $this->getJson(route('estudos.token'))->assertStatus(401);

        $this->actingAs($this->user)->getJson(route('estudos.token'))
            ->assertOk()
            ->assertJsonPath('user_id', $this->user->id)
            ->assertJsonStructure(['token']);
    }

    public function test_imported_sessions_are_accepted_and_never_conflict(): void
    {
        $subject = $this->makeSubject();
        $real = $this->makeSession($subject['id']);
        $imported = $this->makeSession($subject['id'], ['origem' => 'importado']);

        $this->sync(['subjects' => [$subject], 'sessions' => [$real, $imported]])
            ->assertOk()
            ->assertJsonPath('rejected', [])
            ->assertJsonPath('conflicts', []);

        $this->assertSame('importado', DB::table('study_sessions')->where('id', $imported['id'])->value('origem'));
    }

    public function test_settings_store_avatar_and_reject_invalid_ids(): void
    {
        $base = ['virada_hora' => 1, 'limite_suspeita_min' => 180, 'timezone' => 'America/Cuiaba'];

        $this->sync(['settings' => $base])->assertOk()->assertJsonPath('changes.settings.avatar', 'bichinho');
        $this->sync(['settings' => $base + ['avatar' => 'planta']])->assertOk()->assertJsonPath('changes.settings.avatar', 'planta');
        $this->sync(['settings' => $base + ['avatar' => '<script>']])->assertOk()->assertJsonPath('rejected.0.type', 'settings');
    }
}
