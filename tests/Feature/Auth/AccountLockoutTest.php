<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AccountLockoutTest extends TestCase
{
    use RefreshDatabase;

    // FR-4: five wrong-password attempts for one account — from rotating
    // IPs, so the 2.1 email|IP limiter never trips — lock that account for
    // 15 minutes.
    public function test_five_failures_from_rotated_ips_lock_the_account()
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->postLoginFrom('10.0.0.'.$attempt, [
                'email' => $user->email,
                'password' => 'wrong-'.$attempt,
            ]);
        }

        $this->assertGuest();
        $this->assertTrue($user->fresh()->isLockedUntil());

        // Correct password inside the window still fails.
        $this->postLoginFrom('10.0.0.6', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    // FR-4: after the lockout window, the correct password succeeds and
    // the failure counter is cleared.
    public function test_lock_expires_and_counter_clears_after_window()
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->postLoginFrom('10.0.0.'.$attempt, [
                'email' => $user->email,
                'password' => 'wrong-'.$attempt,
            ]);
        }

        $this->assertTrue($user->fresh()->isLockedUntil());
        $this->travel(16)->minutes();

        $this->postLoginFrom('10.0.0.1', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, $user->fresh()->failed_login_count);
        $this->assertNull($user->fresh()->locked_until);
    }

    // FR-4: a success ends the streak — "berturut-turut" (consecutive).
    public function test_success_resets_consecutive_failure_counter()
    {
        $user = User::factory()->create();

        foreach (range(1, 4) as $attempt) {
            $this->postLoginFrom('10.0.0.'.$attempt, [
                'email' => $user->email,
                'password' => 'wrong-'.$attempt,
            ]);
        }

        $this->postLoginFrom('10.0.0.5', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'));
        $this->assertGuest();

        // One later failure must NOT reach the threshold again.
        $this->postLoginFrom('10.0.0.6', [
            'email' => $user->email,
            'password' => 'wrong-again',
        ]);

        $this->assertGuest();
        $fresh = $user->fresh();
        $this->assertSame(1, $fresh->failed_login_count);
        $this->assertNull($fresh->locked_until);
        $this->assertFalse($fresh->isLockedUntil());
    }

    // FR-4: locked account gets the same generic message as failed auth —
    // no "locked" wording.
    public function test_locked_attempts_return_the_generic_message()
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->postLoginFrom('10.0.0.'.$attempt, [
                'email' => $user->email,
                'password' => 'wrong-'.$attempt,
            ]);
        }

        $this->travel(1)->minutes();

        $response = $this->from(route('login'))->postLoginFrom('10.0.0.9', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'Email or password is incorrect']);
        $this->assertGuest();
    }

    /**
     * Post the login form from a specific source IP, bypassing the 2.1
     * email|IP throttle so only the account gate is exercised.
     *
     * @param  array<string, string>  $credentials
     */
    private function postLoginFrom(string $ip, array $credentials): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post(route('login.store'), $credentials);
    }
}
