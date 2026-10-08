<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Tests\TestCase;

class PasswordResetEdgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_email_gets_same_generic_status_as_known_email(): void
    {
        // FR-4a: no account-existence oracle — the failed branch must render
        // the success status verbatim.
        $expected = trans('passwords.sent');

        $known = User::factory()->create();

        $this->post(route('password.email'), ['email' => $known->email])
            ->assertSessionHas('status', $expected);

        $this->post(route('password.email'), ['email' => 'missing@example.com'])
            ->assertSessionHas('status', $expected)
            ->assertSessionHasNoErrors();
    }

    public function test_no_reset_link_is_sent_for_unknown_email(): void
    {
        Notification::fake();

        User::factory()->create();

        $this->post(route('password.email'), ['email' => 'missing@example.com']);

        Notification::assertNothingSent();
    }

    public function test_reset_link_is_sent_for_registered_email(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_stamps_password_changed_at(): void
    {
        Notification::fake();

        $user = User::factory()->withTemporaryPassword()->create();
        $this->assertNull($user->password_changed_at);

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])->assertSessionHasNoErrors();

            // FR-4a: a self-service reset proves mailbox ownership, so the
            // forced-change marker is stamped — no forced-change loop.
            $this->assertNotNull($user->refresh()->password_changed_at);

            return true;
        });
    }

    public function test_login_with_new_password_reaches_dashboard_without_forced_change(): void
    {
        Notification::fake();

        $user = User::factory()->withTemporaryPassword()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

            $this->post(route('logout'));
            $this->assertGuest();

            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'new-password-123',
            ]);

            $this->assertAuthenticated();

            // FR-2: no forced-change loop after a self-service reset.
            $this->get(route('dashboard'))->assertOk();

            return true;
        });
    }

    public function test_old_password_is_rejected_after_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

            $this->post(route('logout'));
            $this->assertGuest();

            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $this->assertGuest();

            return true;
        });
    }

    public function test_reset_with_invalid_token_changes_nothing(): void
    {
        $user = User::factory()->withTemporaryPassword()->create();

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        // FR-4a: a failed reset must not stamp the forced-change marker.
        $this->assertNull($user->refresh()->password_changed_at);
        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_reset_link_requests_are_rate_limited(): void
    {
        // FR-4a: app-side burst limit on the link route (Fortify registers
        // none) — requests beyond 5/minute from one IP get a 429.
        foreach (range(1, 5) as $attempt) {
            $this->post(route('password.email'), ['email' => "user{$attempt}@example.com"]);
        }

        $this->post(route('password.email'), ['email' => 'sixth@example.com'])
            ->assertTooManyRequests();
    }

    public function test_repeated_request_for_same_user_stays_generic(): void
    {
        Notification::fake();

        // FR-4a: the broker's per-user throttle (config/auth.php) kicks in
        // on a second request — its failure must render the same status as
        // success, never a differentiator.
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', trans('passwords.sent'));

        Notification::assertSentTimes(ResetPassword::class, 1);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', trans('passwords.sent'))
            ->assertSessionHasNoErrors();

        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_registration_feature_is_not_exposed(): void
    {
        // FR-2: self-registration must not exist anywhere — no routes, and
        // the Fortify feature stays disabled.
        $this->assertFalse(
            Route::getRoutes()->hasNamedRoute('register'),
            'The register route must not exist.',
        );
        $this->assertFalse(
            Route::getRoutes()->hasNamedRoute('register.store'),
            'The register.store route must not exist.',
        );
        $this->assertFalse(
            Features::enabled(Features::registration()),
            'Self-registration is prohibited by FR-2.',
        );
    }
}
