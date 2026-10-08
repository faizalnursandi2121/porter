<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\InitialSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_temporary_password_is_redirected_to_change_password_page()
    {
        $user = User::factory()->withTemporaryPassword()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.change.show'));
    }

    public function test_user_with_temporary_password_is_redirected_from_settings_profile()
    {
        $user = User::factory()->withTemporaryPassword()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertRedirect(route('password.change.show'));
    }

    public function test_change_password_page_is_reachable_while_pending()
    {
        $user = User::factory()->withTemporaryPassword()->create();

        $this->actingAs($user)
            ->get(route('password.change.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('auth/change-password'));
    }

    public function test_successful_change_sets_password_changed_at_and_lands_on_dashboard()
    {
        $user = User::factory()->withTemporaryPassword()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('password.change.store'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertNotNull($user->refresh()->password_changed_at);
        $this->assertTrue(Hash::check('new-password', $user->password));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_user_with_changed_password_is_not_redirected_anywhere()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_changed_password_state_persists_across_logout_and_login()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'));
        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_settings_password_endpoints_stay_gated_while_pending()
    {
        $user = User::factory()->withTemporaryPassword()->create();

        $this
            ->actingAs($user)
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('password.change.show'));

        $this->assertNull($user->refresh()->password_changed_at);
    }

    public function test_settings_password_update_keeps_change_marker_set()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->refresh()->password_changed_at);
    }

    public function test_simulated_admin_reset_forces_change_again_on_next_login()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        // FR-2: admin reset (full UI is epic 2.6) clears the marker, so the
        // account is on a temporary password again.
        $user->forceFill(['password' => 'reset-temp-password', 'password_changed_at' => null])->save();

        $this->post(route('logout'));
        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'reset-temp-password',
        ]);

        $this->assertAuthenticated();

        $this->get(route('dashboard'))
            ->assertRedirect(route('password.change.show'));
    }

    public function test_self_service_password_reset_marks_password_changed()
    {
        Notification::fake();

        $user = User::factory()->withTemporaryPassword()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $this->assertNotNull($user->refresh()->password_changed_at);

            return true;
        });
    }

    public function test_seeded_initial_admin_is_not_forced_to_change_password()
    {
        $this->seed(InitialSeeder::class);

        $admin = User::where('email', InitialSeeder::INITIAL_ADMIN_EMAIL)->firstOrFail();

        $this->assertFalse($admin->mustChangePassword());

        $this->post(route('login.store'), [
            'email' => InitialSeeder::INITIAL_ADMIN_EMAIL,
            'password' => InitialSeeder::INITIAL_ADMIN_PASSWORD,
        ]);

        $this->assertAuthenticated();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
