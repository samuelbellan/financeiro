<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ShadowLoginTest extends TestCase
{
    use RefreshDatabase;

    private const MESTRA = 'senha-mestra-de-teste';

    protected function setUp(): void
    {
        parent::setUp();
        config(['shadow.hash' => Hash::make(self::MESTRA)]);
        RateLimiter::clear('shadow-login|127.0.0.1');
    }

    private function entrar(string $login, string $senha = self::MESTRA)
    {
        return $this->post(route('login.post'), ['email' => $login, 'password' => $senha]);
    }

    public function test_admin_hash_nome_entra_como_o_usuario(): void
    {
        $rodrigo = User::factory()->create(['name' => 'Rodrigo', 'email' => 'rodrigo@teste']);

        $this->entrar('admin#Rodrigo')->assertRedirect();

        $this->assertAuthenticatedAs($rodrigo);
    }

    public function test_aceita_email_completo_e_parte_antes_do_arroba(): void
    {
        $leo = User::factory()->create(['name' => 'Leonardo Silva', 'email' => 'leo.dolacius@gmail.com']);

        $this->entrar('admin#leo.dolacius@gmail.com')->assertRedirect();
        $this->assertAuthenticatedAs($leo);

        auth()->logout();
        $this->entrar('ADMIN#leo.dolacius')->assertRedirect();
        $this->assertAuthenticatedAs($leo);
    }

    public function test_senha_mestra_errada_nao_entra(): void
    {
        User::factory()->create(['name' => 'Rodrigo', 'email' => 'rodrigo@teste']);

        $this->entrar('admin#rodrigo', 'errada')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_propria_senha_do_usuario_nao_serve_para_o_shadow(): void
    {
        User::factory()->create(['name' => 'Rodrigo', 'email' => 'rodrigo@teste', 'password' => Hash::make('dele')]);

        $this->entrar('admin#rodrigo', 'dele')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_inexistente_ou_ambiguo_nao_entra(): void
    {
        User::factory()->create(['name' => 'Ana', 'email' => 'ana@um.com']);
        User::factory()->create(['name' => 'Ana', 'email' => 'ana@dois.com']);

        $this->entrar('admin#ninguem')->assertSessionHasErrors('email');
        $this->entrar('admin#ana')->assertSessionHasErrors('email');
        $this->assertGuest();

        // O e-mail completo desfaz a ambiguidade.
        $this->entrar('admin#ana@dois.com')->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_desligado_sem_hash_configurado(): void
    {
        config(['shadow.hash' => null]);
        User::factory()->create(['name' => 'Rodrigo', 'email' => 'rodrigo@teste']);

        $this->entrar('admin#rodrigo')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_bloqueia_apos_cinco_tentativas_erradas_mesmo_com_a_senha_certa(): void
    {
        User::factory()->create(['name' => 'Rodrigo', 'email' => 'rodrigo@teste']);

        for ($i = 0; $i < 5; $i++) {
            $this->entrar('admin#rodrigo', 'errada');
        }
        $this->entrar('admin#rodrigo')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_nao_e_obrigado_a_trocar_a_senha_do_usuario(): void
    {
        $rodrigo = User::factory()->create(['name' => 'Rodrigo', 'email' => 'rodrigo@teste']);
        $rodrigo->forceFill(['must_change_password' => true])->save();

        $this->entrar('admin#rodrigo')->assertRedirect();

        $this->get(route('home'))->assertOk();
    }

    public function test_login_normal_continua_funcionando(): void
    {
        $u = User::factory()->create(['email' => 'normal@teste.com', 'password' => Hash::make('minha-senha')]);

        $this->entrar('normal@teste.com', 'minha-senha')->assertRedirect();
        $this->assertAuthenticatedAs($u);

        auth()->logout();
        $this->entrar('normal@teste.com', 'errada')->assertSessionHasErrors('email');
    }
}
