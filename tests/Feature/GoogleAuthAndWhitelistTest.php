<?php

namespace Tests\Feature;

use App\Models\AllowedGoogleEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthAndWhitelistTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_google_redirect_warns_when_credentials_missing(): void
    {
        config(['services.google.client_id' => null]);

        $response = $this->get(route('auth.google'));
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('google');
    }

    public function test_unauthorized_google_email_is_rejected(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-secret',
        ]);

        $mockUser = Mockery::mock(SocialiteUserContract::class);
        $mockUser->shouldReceive('getId')->andReturn('google-123');
        $mockUser->shouldReceive('getEmail')->andReturn('estranho@gmail.com');
        $mockUser->shouldReceive('getName')->andReturn('Estranho');
        $mockUser->shouldReceive('getAvatar')->andReturn('https://avatar.url/pic.jpg');

        $provider = Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('user')->andReturn($mockUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'estranho@gmail.com']);
    }

    public function test_whitelisted_google_email_creates_user_and_redirects_to_estudos(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-secret',
        ]);

        AllowedGoogleEmail::create([
            'email' => 'amigo@gmail.com',
            'nome' => 'Amigo Convidado',
        ]);

        $mockUser = Mockery::mock(SocialiteUserContract::class);
        $mockUser->shouldReceive('getId')->andReturn('google-999');
        $mockUser->shouldReceive('getEmail')->andReturn('amigo@gmail.com');
        $mockUser->shouldReceive('getName')->andReturn('Amigo Convidado');
        $mockUser->shouldReceive('getAvatar')->andReturn('https://avatar.url/amigo.jpg');

        $provider = Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('user')->andReturn($mockUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('estudos.index'));
        $this->assertAuthenticated();

        $user = User::where('email', 'amigo@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('google-999', $user->google_id);
        $this->assertFalse($user->is_admin);
    }

    public function test_existing_admin_email_logs_in_and_links_google_id(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-secret',
        ]);

        $admin = User::factory()->create([
            'email' => 'admin@gmail.com',
            'is_admin' => true,
            'google_id' => null,
        ]);

        $mockUser = Mockery::mock(SocialiteUserContract::class);
        $mockUser->shouldReceive('getId')->andReturn('google-admin-1');
        $mockUser->shouldReceive('getEmail')->andReturn('admin@gmail.com');
        $mockUser->shouldReceive('getName')->andReturn('Admin Master');
        $mockUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock(SocialiteProvider::class);
        $provider->shouldReceive('user')->andReturn($mockUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('estudos.index'));
        $this->assertAuthenticatedAs($admin);

        $admin->refresh();
        $this->assertSame('google-admin-1', $admin->google_id);
    }

    public function test_non_admin_cannot_access_convites_management(): void
    {
        $regularUser = User::factory()->create(['is_admin' => false]);

        $this->actingAs($regularUser)
            ->get(route('admin.convites'))
            ->assertForbidden();

        $this->actingAs($regularUser)
            ->post(route('admin.convites.store'), ['email' => 'alvo@gmail.com'])
            ->assertForbidden();
    }

    public function test_admin_can_manage_allowed_emails(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // List
        $this->actingAs($admin)
            ->get(route('admin.convites'))
            ->assertOk();

        // Create
        $this->actingAs($admin)
            ->post(route('admin.convites.store'), [
                'email' => 'NOVO_CONVIDADO@GMAIL.COM',
                'nome' => 'Estudante Novo',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('allowed_google_emails', [
            'email' => 'novo_convidado@gmail.com',
            'nome' => 'Estudante Novo',
        ]);

        $record = AllowedGoogleEmail::where('email', 'novo_convidado@gmail.com')->first();

        // Delete
        $this->actingAs($admin)
            ->delete(route('admin.convites.destroy', $record->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('allowed_google_emails', [
            'id' => $record->id,
        ]);
    }
}
