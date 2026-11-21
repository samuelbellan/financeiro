<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulosAccessTest extends TestCase
{
    use RefreshDatabase;

    private function convidado(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['modulos' => ['estudos', 'concursos']])->save();

        return $u;
    }

    public function test_convidado_nao_acessa_modulos_fora_da_lista(): void
    {
        $u = $this->convidado();

        foreach (['/financas', '/financas/mercado', '/salario', '/treinos', '/api/tracker/daily-log/today'] as $url) {
            $this->actingAs($u)->get($url)->assertForbidden();
        }
    }

    public function test_convidado_acessa_modulos_liberados_e_home(): void
    {
        $u = $this->convidado();

        $this->actingAs($u)->get('/home')->assertOk();
        $this->actingAs($u)->get('/estudos')->assertOk();
    }

    public function test_home_esconde_links_de_modulos_bloqueados(): void
    {
        $u = $this->convidado();

        $this->actingAs($u)->get('/home')
            ->assertOk()
            ->assertDontSee(route('financas.index'), false)
            ->assertDontSee(route('salary.index'), false)
            ->assertDontSee(route('treinos.index'), false)
            ->assertSee(route('estudos.index'), false);
    }

    public function test_modulos_null_libera_tudo(): void
    {
        $u = User::factory()->create();

        $this->assertNull($u->modulos);
        $this->actingAs($u)->get('/financas')->assertOk();
    }

    public function test_dono_acessa_tudo_mesmo_com_lista_restrita(): void
    {
        $dono = User::factory()->create();
        $dono->forceFill(['is_owner' => true, 'is_admin' => true, 'modulos' => ['estudos']])->save();

        $this->actingAs($dono)->get('/financas')->assertOk();
    }

    public function test_comando_define_modulos(): void
    {
        $u = User::factory()->create(['email' => 'amigo@example.com']);

        $this->artisan('usuarios:modulos', ['email' => 'amigo@example.com', '--set' => 'estudos,concursos'])->assertSuccessful();
        $this->assertSame(['estudos', 'concursos'], $u->fresh()->modulos);

        $this->artisan('usuarios:modulos', ['email' => 'amigo@example.com', '--set' => 'estudos,xyz'])->assertFailed();

        $this->artisan('usuarios:modulos', ['email' => 'amigo@example.com', '--todos' => true])->assertSuccessful();
        $this->assertNull($u->fresh()->modulos);
    }
}
