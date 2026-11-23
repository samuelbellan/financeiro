<?php

namespace Tests\Feature;

use App\Models\ConcursoState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConcursosTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/concursos')->assertRedirect('/login');
        $this->get('/concursos/inicio.html')->assertRedirect('/login');
        $this->get('/concursos/__estado')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_all_pages_and_gets_csrf_meta(): void
    {
        $user = User::factory()->create();

        // Módulo novo ligado: quem segue o planejamento entra na meta do dia.
        $this->actingAs($user)
            ->get('/concursos')
            ->assertRedirect(route('concursos.meta'));

        // Desligado (nuvem): continua no app estático.
        config(['concursos.enabled' => false]);
        $this->actingAs($user)
            ->get('/concursos')
            ->assertRedirect(route('concursos.page', ['page' => 'inicio.html']));

        foreach (['inicio.html', 'caderno.html', 'simulados.html', 'flashcards.html', 'refazer.html', 'resumos.html', 'leis.html'] as $page) {
            $response = $this->actingAs($user)->get("/concursos/{$page}");
            $response->assertOk();
            $response->assertHeader('Content-Type', 'text/html; charset=utf-8');
            $response->assertSee('<meta name="csrf-token"', false);
        }

        $this->actingAs($user)
            ->get('/concursos/nao-existe.html')
            ->assertNotFound();
    }

    public function test_state_endpoint_persists_deletes_and_isolates_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Empty state returns JSON object {}
        $initial = $this->actingAs($userA)->getJson('/concursos/__estado');
        $initial->assertOk();
        $this->assertSame('{}', $initial->getContent());

        // Save keys for User A
        $this->actingAs($userA)
            ->postJson('/concursos/__estado', [
                'set' => [
                    'simulados_resp' => '{"S1-01":"C"}',
                    'edital_done' => '["cf-1"]',
                ],
                'del' => [],
            ])
            ->assertOk()
            ->assertExactJson(['ok' => true]);

        // User A reads saved state
        $this->actingAs($userA)
            ->getJson('/concursos/__estado')
            ->assertOk()
            ->assertExactJson([
                'simulados_resp' => '{"S1-01":"C"}',
                'edital_done' => '["cf-1"]',
            ]);

        // User B does not see User A's progress
        $this->actingAs($userB)
            ->getJson('/concursos/__estado')
            ->assertOk();
        $this->assertSame('{}', $this->actingAs($userB)->getJson('/concursos/__estado')->getContent());

        // User A updates one key and deletes another
        $this->actingAs($userA)
            ->postJson('/concursos/__estado', [
                'set' => [
                    'simulados_resp' => '{"S1-01":"C","S1-02":"A"}',
                ],
                'del' => ['edital_done'],
            ])
            ->assertOk()
            ->assertExactJson(['ok' => true]);

        $this->actingAs($userA)
            ->getJson('/concursos/__estado')
            ->assertOk()
            ->assertExactJson([
                'simulados_resp' => '{"S1-01":"C","S1-02":"A"}',
            ]);

        $this->assertDatabaseCount('concursos_states', 1);
        $this->assertDatabaseHas('concursos_states', [
            'user_id' => $userA->id,
            'chave' => 'simulados_resp',
        ]);
    }

    public function test_home_page_displays_estudo_para_concursos_link(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/home')
            ->assertOk()
            ->assertSee('Estudo para Concursos')
            ->assertSee(route('concursos.index'), false);
    }

    public function test_assets_are_served_with_proper_mime_and_protected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/concursos/css/nav.css')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=utf-8');

        $this->actingAs($user)
            ->get('/concursos/js/armazenamento.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8');

        $this->actingAs($user)
            ->get('/concursos/dados/simulados.json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8');

        $this->actingAs($user)
            ->get('/concursos/icone.svg')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');

        $this->actingAs($user)
            ->get('/concursos/css/../../.env')
            ->assertNotFound();
    }
}
