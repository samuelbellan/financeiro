<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
        ]);
    }

    public function test_sync_status_requires_authentication(): void
    {
        $response = $this->getJson(route('sync.status'));
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_check_sync_status(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('sync.status'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'cloud_online',
                'local_driver',
            ],
        ]);
    }

    public function test_sync_run_validates_direction(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('sync.run'), [
            'direction' => 'invalid_direction',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Direção de sincronização inválida.',
        ]);
    }

    public function test_sync_command_is_registered_and_shows_help(): void
    {
        $this->artisan('db:sync', ['--help' => true])
            ->assertSuccessful();
    }
}
