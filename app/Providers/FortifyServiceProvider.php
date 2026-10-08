<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\EnsureAccountIsNotLocked;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\FailedPasswordResetLinkRequestResponse as FailedLinkRequestResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedLinkRequestResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // FR-4a: failed reset-link requests (unknown email, throttled) must be
        // indistinguishable from successful ones — no account-existence
        // oracle, same contract as FR-4's generic login failure.
        $this->app->singleton(
            FailedLinkRequestResponseContract::class,
            FailedLinkRequestResponse::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // FR-4 (2.2) listeners in app/Listeners/Auth are auto-discovered.
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureLoginPipeline();
        $this->configurePasswordResetThrottle();
    }

    /**
     * FR-4a: Fortify registers no limiter on the reset-link route, so
     * app-side throttling is appended here — this file owns all Fortify
     * wiring and `routes/web.php` stays untouched. The hook runs once all
     * routes are registered, but the collection's name-lookup index is
     * refreshed only afterwards — hence the attribute scan. The broker's
     * own per-user 60 s token throttle (config/auth.php) remains the
     * second layer; its failure renders the same generic status.
     */
    private function configurePasswordResetThrottle(): void
    {
        if (! Features::enabled(Features::resetPasswords())) {
            return;
        }

        $this->app->booted(function (): void {
            // FR-4a: the name-lookup index is refreshed only after this
            // hook, so the route is located by its name attribute directly.
            foreach (Route::getRoutes()->getRoutes() as $route) {
                if ($route->getName() === 'password.email') {
                    $route->middleware('throttle:5,1');
                }
            }
        });
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure the login pipeline (FR-4 2.2): mirrors the default
     * Fortify pipeline with the per-account lock gate inserted between
     * the email|IP throttle (route middleware `throttle:login`) and the
     * credential check.
     */
    private function configureLoginPipeline(): void
    {
        Fortify::authenticateThrough(function (Request $request) {
            return array_filter([
                config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
                config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
                EnsureAccountIsNotLocked::class,
                Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class : null,
                AttemptToAuthenticate::class,
                PrepareAuthenticatedSession::class,
            ]);
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // FR-4: 5 consecutive failures lock the email+IP pair for 15 minutes.
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5, decayMinutes: 15)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by(
                ($request->input('credential.id') ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}
