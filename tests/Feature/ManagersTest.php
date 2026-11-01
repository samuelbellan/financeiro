<?php

namespace Tests\Feature;

use App\Models\AllowedGoogleEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagersTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $owner->forceFill(['is_owner' => true])->save();

        return $owner;
    }

    public function test_only_the_owner_delegates_the_manager_role(): void
    {
        $owner = $this->owner();
        $gestor = User::factory()->create(['is_admin' => true]);
        $bia = User::factory()->create();

        $this->actingAs($gestor)->post(route('admin.usuarios.gestor', $bia))->assertForbidden();
        $this->assertFalse($bia->fresh()->is_admin);

        $this->actingAs($owner)->post(route('admin.usuarios.gestor', $bia))->assertRedirect();
        $this->assertTrue($bia->fresh()->is_admin);

        $this->actingAs($owner)->post(route('admin.usuarios.gestor', $bia))->assertRedirect();
        $this->assertFalse($bia->fresh()->is_admin);

        // O dono continua gestor.
        $this->actingAs($owner)->post(route('admin.usuarios.gestor', $owner))->assertRedirect();
        $this->assertTrue($owner->fresh()->is_admin);
    }

    public function test_managers_cannot_revoke_the_owner_or_other_managers(): void
    {
        $owner = $this->owner();
        $gestor = User::factory()->create(['is_admin' => true]);
        $outro = User::factory()->create(['is_admin' => true]);
        $comum = User::factory()->create();
        $ids = collect([$owner, $outro, $comum])->mapWithKeys(fn ($u) => [
            $u->id => AllowedGoogleEmail::create(['email' => strtolower($u->email), 'created_by' => $owner->id])->id,
        ]);

        $this->actingAs($gestor)->delete(route('admin.convites.destroy', $ids[$owner->id]))->assertForbidden();
        $this->actingAs($gestor)->delete(route('admin.convites.destroy', $ids[$outro->id]))->assertForbidden();
        $this->actingAs($gestor)->delete(route('admin.convites.destroy', $ids[$comum->id]))->assertRedirect();
        $this->actingAs($owner)->delete(route('admin.convites.destroy', $ids[$outro->id]))->assertRedirect();
    }

    public function test_regular_users_cannot_open_user_management(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.convites'))->assertForbidden();
    }
}
