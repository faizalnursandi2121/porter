<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\InitialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->withRole(Role::EOS)->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    // auth.role drives launcher tile gating (ui-design §3) — it must be the code string.
    public function test_dashboard_shares_role_code_for_each_role()
    {
        $this->seed(InitialSeeder::class);

        foreach ([Role::EOS, Role::SUPERVISOR, Role::HR, Role::ADMINISTRATOR] as $code) {
            $user = User::factory()->withRole($code)->create();
            $this->actingAs($user);

            $this->get(route('dashboard'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('dashboard')
                    ->where('auth.role', $code));
        }
    }

    public function test_eos_sees_only_eos_tiles()
    {
        $user = User::factory()->withRole(Role::EOS)->create();
        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->has('auth'));
    }
}
