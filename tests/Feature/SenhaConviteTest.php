<?php

namespace Tests\Feature;

use App\Models\AllowedGoogleEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SenhaConviteTest extends TestCase
{
    use RefreshDatabase;

    protected function gestor(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    protected function dono(): User
    {
        $dono = User::factory()->create(['is_admin' => true]);
        $dono->forceFill(['is_owner' => true])->save();

        return $dono;
    }

    public function test_admin_cria_usuario_com_senha_que_entra_com_email_e_troca_no_primeiro_acesso(): void
    {
        $this->actingAs($this->gestor())->post(route('admin.usuarios.store'), [
            'name' => 'Rodrigo',
            'email' => 'rodrigo@teste',
            'password' => 'senha-inicial',
            'password_confirmation' => 'senha-inicial',
        ])->assertRedirect();

        $rodrigo = User::where('email', 'rodrigo@teste')->firstOrFail();
        $this->assertTrue($rodrigo->must_change_password);
        $this->assertTrue(Hash::check('senha-inicial', $rodrigo->password));
        $this->assertSame(config('modulos.padrao_convidado'), $rodrigo->modulos);
        // Continua podendo entrar com o Google.
        $this->assertTrue(AllowedGoogleEmail::where('email', 'rodrigo@teste')->exists());

        $this->post(route('login.post'), ['email' => 'rodrigo@teste', 'password' => 'senha-inicial'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($rodrigo);

        // Sem trocar a senha, não passa das telas do sistema.
        $this->get(route('home'))->assertRedirect(route('conta.senha'));
        $this->get(route('conta.senha'))->assertOk();
    }

    public function test_usuario_troca_a_propria_senha_informando_a_atual(): void
    {
        $user = User::factory()->create(['password' => Hash::make('antiga-123')]);
        $user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($user)->post(route('conta.senha.salvar'), [
            'senha_atual' => 'errada-000',
            'password' => 'nova-senha-1',
            'password_confirmation' => 'nova-senha-1',
        ])->assertSessionHasErrors('senha_atual');
        $this->assertTrue($user->fresh()->must_change_password);

        $this->actingAs($user)->post(route('conta.senha.salvar'), [
            'senha_atual' => 'antiga-123',
            'password' => 'nova-senha-1',
            'password_confirmation' => 'nova-senha-1',
        ])->assertRedirect();

        $user->refresh();
        $this->assertTrue(Hash::check('nova-senha-1', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->get(route('home'))->assertOk();
    }

    public function test_quem_entrou_so_pelo_google_define_a_primeira_senha_sem_senha_atual(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)->post(route('conta.senha.salvar'), [
            'password' => 'primeira-senha',
            'password_confirmation' => 'primeira-senha',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('primeira-senha', $user->fresh()->password));
    }

    public function test_so_gestor_cria_usuario(): void
    {
        $comum = User::factory()->create();

        $this->actingAs($comum)->post(route('admin.usuarios.store'), [
            'name' => 'X',
            'email' => 'x@exemplo.com',
            'password' => 'senha-inicial',
            'password_confirmation' => 'senha-inicial',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'x@exemplo.com']);
    }

    public function test_gestor_redefine_senha_de_usuario_comum_mas_nao_de_outro_gestor(): void
    {
        $gestor = $this->gestor();
        $comum = User::factory()->create();
        $outroGestor = $this->gestor();

        $this->actingAs($gestor)->post(route('admin.usuarios.senha', $comum), [
            'password' => 'reset-12345',
            'password_confirmation' => 'reset-12345',
        ])->assertRedirect();
        $comum->refresh();
        $this->assertTrue(Hash::check('reset-12345', $comum->password));
        $this->assertTrue($comum->must_change_password);

        $this->actingAs($gestor)->post(route('admin.usuarios.senha', $outroGestor), [
            'password' => 'reset-12345',
            'password_confirmation' => 'reset-12345',
        ])->assertForbidden();
    }

    public function test_dono_redefine_senha_de_gestor(): void
    {
        $dono = $this->dono();
        $gestor = $this->gestor();

        $this->actingAs($dono)->post(route('admin.usuarios.senha', $gestor), [
            'password' => 'reset-12345',
            'password_confirmation' => 'reset-12345',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('reset-12345', $gestor->fresh()->password));
    }

    public function test_logout_funciona_com_senha_provisoria(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($user)->post(route('logout'))->assertRedirect('/');
    }
}
