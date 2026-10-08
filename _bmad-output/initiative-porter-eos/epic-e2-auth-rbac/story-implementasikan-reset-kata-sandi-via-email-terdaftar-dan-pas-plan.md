---
title: '2.4 — Password reset via emailed link to registered address'
type: 'feature'
ticket: '4'
created: '2026-10-08'
status: done
baseline_revision: 'feature/porter-tooling-baseline'
route: 'full'
route_source: 'auto'
risk: 'medium'
review: 'quick'
review_source: 'pinned'
lenses_ran: []
review_loop_iteration: 0
context: []
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** FR-4a first half: a user with a registered email must be able to reset their password via an emailed link. Fortify's `resetPasswords` feature is enabled with Inertia views wired (`auth/forgot-password`, `auth/reset-password`), but the FR-4a semantics are not shaped or tested: (a) a self-service reset PROVES mailbox ownership, so it must stamp `password_changed_at = now()` and not loop the user into the forced-change flow (story 2.3 interplay); (b) the shared reset action is also 2.6's admin-reset path, which must leave the marker NULL (temporary password, forced change at next login); (c) an unknown email must not reveal account existence (no oracle — same generic status as a known email); (d) reset-link requests must be rate limited; (e) FR-2 second half: no self-registration anywhere.

**Approach:** Keep Fortify's broker flow (`Password::broker()` on the `users` provider, `password.reset`/`password.email`/`password.update` routes). Split `ResetUserPassword` behavior with a boolean constructor flag (`marksPasswordChanged`, default true = self-service) so 2.6 can install a temporary password without stamping. Bind Fortify's `FailedPasswordResetLinkRequestResponse` contract to an app response that renders the success status verbatim (no oracle). Add an app-side per-IP burst throttle on the link-request POST (Fortify registers none) via a container-bound controller subclass — no `routes/web.php` or `bootstrap/app.php` edits.

</frozen-after-approval>

## Code Map

- `app/Actions/Fortify/ResetUserPassword.php` — currently stamps `password_changed_at = now()` unconditionally (2.3 behavior); must become reset-aware for 2.6.
- `app/Providers/FortifyServiceProvider.php` — `configureActions()` binds `ResetsUserPasswords`; needs response/controller container bindings.
- `vendor/laravel/fortify/.../PasswordResetLinkController.php` — `broker()` uses `config('fortify.passwords')` = `users`; NO route throttle (verified in vendor routes).
- `vendor/laravel/fortify/.../FailedPasswordResetLinkRequestResponse.php` — leaks `passwords.user` into `errors.email` (account oracle).
- `config/auth.php` — `passwords.users.throttle = 60` (per-user broker token throttle — second layer, stays).
- `config/fortify.php` — `resetPasswords()` enabled; `registration()` commented out (FR-2, done in 2.1).
- `lang/en/auth.php`, `lang/en/passwords.php` — English UI copy.
- `resources/js/pages/auth/forgot-password.tsx` + `reset-password.tsx` — existing Inertia pages, unchanged.
- FR source: FR-4a first half, FR-2 (no self-registration), interplay with 2.3 (`EnsurePasswordChanged`, `password_changed_at`).

## Tasks & Acceptance

**Execution:**
- [x] `app/Actions/Fortify/ResetUserPassword.php` — boolean `marksPasswordChanged` constructor arg (default true); flag=false installs the password and forces the marker back to NULL (temporary password for 2.6) -- rationale: FR-4a "admin reset → change at next login" without looping self-service resets.
- [x] `app/Http/Responses/FailedPasswordResetLinkRequestResponse.php` + binding in `FortifyServiceProvider::register()` — every failed link request renders the same generic status as success -- rationale: no account-existence oracle for unknown emails (mirrors FR-4 decision 19).
- [x] `app/Http/Controllers/Auth/PasswordResetLinkController.php` (subclass, container-bound over the Fortify FQCN) — `throttle:5,1` on `store` only -- rationale: reset-link requests must be rate limited; Fortify registers no limiter and `fortify.limiters` does not support this route.
- [x] `tests/Feature/Auth/PasswordResetEdgeTest.php` — unknown email = same generic status + no notification; known/unknown identical status; valid token resets password AND stamps `password_changed_at`; login with new password works (dashboard reachable, no forced-change redirect); old password rejected; invalid token changes nothing; reset-link burst → 429; repeated same-user requests stay generic (broker 60s throttle masked); no `register` route/page anywhere -- rationale: verify = feature tests pass.

**Acceptance Criteria:**
- Given a registered email, when requesting a reset link, then `ResetPassword` notification is sent (Sail `MAIL_MAILER=array` in tests) and the link route `password.reset` works.
- Given an unknown email, then the response is indistinguishable from the known-email response (same session status, no errors, no notification).
- Given a valid token, then the password changes, `password_changed_at` is stamped, login with the new password reaches the dashboard without forced-change redirect, and the old password is rejected.
- Given 6 link requests in a minute from one IP, then 429 (app limiter); given a second request for the same user within the broker's 60 s, then the same generic status (no new differentiator).
- Given no `Features::registration()` and no register routes/pages/links, then self-registration is impossible (FR-2).

## Implementation Notes

- Ground truth (vendor verified): Fortify has NO throttle on `password.email`/`password.update`; `fortify.limiters` only supports login/two-factor/passkeys. The only built-in reset throttle is the broker's per-user `passwords.users.throttle = 60` s (token-row `created_at` check). App-side `throttle:5,1` per IP added on `store`-only POST route (views stay unthrottled) via a `booted` hook in `FortifyServiceProvider` that appends to the registered route.
- Throttle-append trap (instrumented, verified): inside `$this->app->booted()` callbacks the route collection already holds all 47 routes, but `getRoutes()->getByName('password.email')` returns NULL — the name-lookup index is refreshed by a LATER booted callback (framework `RouteServiceProvider::register()` queues it after route loading). Direct `Route::middleware()` append in `boot()` also misses (route lookup null there). Working approach: iterate `Route::getRoutes()->getRoutes()` and match `getName() === 'password.email'` — operates on the live dispatch collection, no index needed.
- No-oracle implementation: `FailedPasswordResetLinkRequestResponse` contract rebound to `App\Http\Responses\FailedPasswordResetLinkRequestResponse` (renders `passwords.sent` through the same `back()->with('status', ...)` channel as success, JSON 200 for API) — known, unknown, AND broker-throttled requests render identically. `lang/en/passwords.php` `user`/`throttled` also masked to the `sent` text as defense-in-depth (any future code path that falls back to default responses still cannot leak). Broker-level per-user throttle stays enforced — silently.
- Container-binding trap: `$this->app->singleton(FailedPasswordResetLinkRequestResponse::class, ...)` without importing the Fortify contract resolves the abstract to `App\Providers\FailedPasswordResetLinkRequestResponse` (current namespace) — the binding attaches to a class name nobody requests and the vendor default stays active, silently. Always bind the imported CONTRACT interface.
- 2.6 contract: `ResetUserPassword::__construct(bool $marksPasswordChanged = true)` — resolve as `app(ResetUserPassword::class, ['marksPasswordChanged' => false])`; the account lands back on `password_changed_at = NULL` (temporary password, forced change at next login per FR-4a).
- `ForcePasswordChangeTest` untouched — its `test_self_service_password_reset_marks_password_changed` passes with the default flag; the simulated-admin-reset test never calls the action (direct forceFill).
- FR-2 re-verified: `Features::registration()` commented out (2.1), vendor route block registers nothing, no `auth/register.tsx` page, no register links (passkey components are WebAuthn, not self-registration); regression-asserted in `PasswordResetEdgeTest::test_registration_feature_is_not_exposed` (route names + feature flag).
- Note for integration: `vendor/bin/pint --dirty` (mandated by the story) also applied mechanical style fixers to sibling story-2.5 files that were dirty in the shared tree (`User.php`, `AppServiceProvider.php`, `bootstrap/app.php`, `routes/web.php`); full suite re-verified green afterwards.

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `./vendor/bin/sail php artisan test tests/Feature/Auth/PasswordResetEdgeTest.php --compact` -- expected: green
- `./vendor/bin/sail php artisan test tests/Feature/Auth/ --compact` -- expected: green (incl. existing PasswordResetTest, ForcePasswordChangeTest untouched)
- `vendor/bin/pint --dirty --format agent` -- expected: clean
