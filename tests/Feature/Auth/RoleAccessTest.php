<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    // FR-1: EOS reaches its own module area only.
    public function test_eos_reaches_eos_area_but_not_supervisi_or_admin(): void
    {
        $eos = User::factory()->withRole(Role::EOS)->create();

        $this->actingAs($eos)->get('/eos/attendance')
            ->assertOk()
            ->assertSee('eos ok');
        $this->actingAs($eos)->get('/supervisi/attendance')->assertForbidden();
        $this->actingAs($eos)->get('/admin/users')->assertForbidden();
    }

    // FR-1: Supervisi reaches its own area; 2.6 opens the account list to it
    // read-only — creating accounts and resetting passwords stay admin-only.
    public function test_supervisi_reaches_supervisi_area_but_not_admin(): void
    {
        $supervisi = User::factory()->withRole(Role::SUPERVISI)->create();

        $this->actingAs($supervisi)->get('/eos/attendance')->assertForbidden();
        $this->actingAs($supervisi)->get('/supervisi/attendance')
            ->assertOk()
            ->assertSee('supervisi ok');
        $this->actingAs($supervisi)->get('/admin/users')->assertOk();
        $this->actingAs($supervisi)->post('/admin/users', [])->assertForbidden();
    }

    // PRD: Administrator has every Supervisi capability plus user, template,
    // master-data and audit management. The superset covers the Supervisi
    // area only — the EOS personal module stays EOS-only (the launcher shows
    // /eos/* and /supervisi/* as separate areas).
    public function test_administrator_reaches_supervisi_and_admin_areas(): void
    {
        $administrator = User::factory()->withRole(Role::ADMINISTRATOR)->create();

        $this->actingAs($administrator)->get('/eos/attendance')->assertForbidden();
        $this->actingAs($administrator)->get('/supervisi/attendance')->assertOk();
        $this->actingAs($administrator)->get('/admin/users')->assertOk();
    }

    // FR-1: HR has no module area of its own yet (HR pages arrive with a
    // later epic), so every stub stays out of reach.
    public function test_hr_is_forbidden_on_every_module_stub(): void
    {
        $hr = User::factory()->withRole(Role::HR)->create();

        $this->actingAs($hr)->get('/eos/attendance')->assertForbidden();
        $this->actingAs($hr)->get('/supervisi/attendance')->assertForbidden();
        $this->actingAs($hr)->get('/admin/users')->assertForbidden();
    }

    // FR-1: the gate is server-side on every request — guests never see a
    // role-gated area.
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/eos/attendance')->assertRedirect(route('login'));
        $this->get('/supervisi/attendance')->assertRedirect(route('login'));
        $this->get('/admin/users')->assertRedirect(route('login'));
    }

    // PRD: the Supervisi-area superset rule — ADMINISTRATOR inherits it,
    // EOS and HR do not.
    public function test_supervisi_access_helper_follows_the_superset_rule(): void
    {
        $this->assertTrue(User::factory()->withRole(Role::SUPERVISI)->make()->hasSupervisiAccess());
        $this->assertTrue(User::factory()->withRole(Role::ADMINISTRATOR)->make()->hasSupervisiAccess());
        $this->assertFalse(User::factory()->withRole(Role::EOS)->make()->hasSupervisiAccess());
        $this->assertFalse(User::factory()->withRole(Role::HR)->make()->hasSupervisiAccess());
    }

    // FR-34: site master data is Administrator-managed; every other role
    // reads only.
    public function test_only_administrator_manages_sites(): void
    {
        $site = Site::factory()->create();

        foreach ([Role::EOS, Role::SUPERVISI, Role::HR] as $code) {
            $user = User::factory()->withRole($code)->create();

            $this->assertTrue($user->can('viewAny', Site::class));
            $this->assertTrue($user->can('view', $site));
            $this->assertFalse($user->can('create', Site::class));
            $this->assertFalse($user->can('update', $site));
            $this->assertFalse($user->can('delete', $site));
        }

        $administrator = User::factory()->withRole(Role::ADMINISTRATOR)->create();

        $this->assertTrue($administrator->can('create', Site::class));
        $this->assertTrue($administrator->can('update', $site));
        $this->assertTrue($administrator->can('delete', $site));
    }
}
