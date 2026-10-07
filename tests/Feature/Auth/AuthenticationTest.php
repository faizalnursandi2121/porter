<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge()
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $response->assertSessionHas('login.id', $user->id);
        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        // FR-4: one generic message — no hint which part failed.
        $response->assertSessionHasErrors(['email' => 'Email or password is incorrect']);
    }

    public function test_unknown_email_gets_the_same_generic_error()
    {
        $response = $this->post(route('login.store'), [
            'email' => 'nobody@example.test',
            'password' => 'whatever',
        ]);

        $this->assertGuest();
        // FR-4: no account-existence oracle.
        $response->assertSessionHasErrors(['email' => 'Email or password is incorrect']);
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_users_are_rate_limited()
    {
        $user = User::factory()->create();

        RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertTooManyRequests();
    }

    // FR-4: five consecutive failures lock the email+IP pair for 15 minutes.
    public function test_five_consecutive_failures_lock_login_for_fifteen_minutes()
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password-'.$attempt,
            ]);
        }

        $this->assertGuest();

        // A correct password inside the lockout window still fails.
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_lockout_expires_after_fifteen_minutes()
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-'.$attempt,
            ]);
        }

        $this->assertGuest();
        $this->travel(16)->minutes();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_failure_count_is_per_email_ip_pair()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-'.$attempt,
            ]);
        }

        // A different account from the same IP is not locked by the first pair's failures.
        $this->post(route('login.store'), [
            'email' => $other->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }
}
