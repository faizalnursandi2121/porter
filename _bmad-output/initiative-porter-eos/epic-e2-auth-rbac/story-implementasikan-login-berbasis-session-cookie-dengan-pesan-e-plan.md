---
title: '2.1 — Session-cookie login with FR-4 generic error'
type: 'feature'
ticket: '1'
created: '2026-10-07'
status: done
baseline_revision: '11375d8'
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

**Problem:** Subtask 2.1 (CAP-1): session-cookie login where wrong credentials return the FR-4 generic error "Email atau kata sandi salah" (no hint which part failed) and 5 consecutive failures trigger a 15-minute lockout. Fortify is installed with a login view wired to Inertia `auth/login` and a per-minute(5) login limiter — but FR-4's semantics are untested and the lockout window/behavior must be explicit.

**Approach:** Keep Fortify's pipeline (`EnsureLoginIsNotThrottled` → `AttemptToAuthenticate` → `PrepareAuthenticatedSession`), session-cookie guard (already default), and shape FR-4 explicitly: generic credentials error message, lockout limiter `5 attempts / 15 min decay` keyed `email|ip`, error surfaced as the same generic message (no lockout hint that reveals account existence). Translate FE copy to the FR-verbatim string via `lang/en` + `__()` per conventions.

</frozen-after-approval>

## Code Map

- `app/Providers/FortifyServiceProvider.php` — `configureRateLimiting()` has `Limit::perMinute(5)` (wrong window for FR-4); `configureViews()` login view OK.
- `resources/js/pages/auth/login.tsx` — Inertia `Form` posts to `login.store`; errors render via `InputError` on email/password fields; copy currently English placeholder texts.
- `config/fortify.php` — `limiters.login = 'login'`, `username = email`, `lowercase_usernames = true`.
- FR source: FR-4 (generic error + 5-fail/15-min lockout), FR-4a is 2.3's scope.
- Test pattern: `tests/Feature/DashboardTest.php` (assertInertia), `LoginTest` may exist under `tests/Feature/Auth/`.

## Tasks & Acceptance

**Execution:**
- [ ] `app/Providers/FortifyServiceProvider.php` — login limiter → `Limit::perMinute(5)->decayMinutes(15)` keyed `email|ip` -- rationale: FR-4 window.
- [ ] `lang/en/*.php` + `resources/js/pages/auth/login.tsx` — FR-4 verbatim string wired through `__()` / Inertia errors -- rationale: copy UI verbatim per epic Done-when.
- [ ] `tests/Feature/Auth/LoginTest.php` — wrong-password returns FR-4 message (not "user not found"); 5th failure locks 15 min; after decay window login succeeds; correct credentials authenticate -- rationale: verify = feature tests pass.

**Acceptance Criteria:**
- Given a registered user, when logging in with wrong password, then the response carries exactly "Email atau kata sandi salah" (no differentiator).
- Given no such user, when logging in, then the same message (no account-existence hint).
- Given 5 consecutive wrong attempts for the same email+IP, then further attempts are locked out for 15 minutes (throttle exception/message), and a correct attempt inside the window still fails; after the window it succeeds.
- Given correct credentials, when logging in, then session cookie is set and dashboard renders with `auth.role` = user's role code.

## Implementation Notes

- Laravel 13 gotcha found: `Limit::decayMinutes()` does NOT exist (Laravel ≤12 fluent API) — correct form is `Limit::perMinute(5, decayMinutes: 15)`. Recorded because every future rate limiter in this repo hits the same trap.
- FR-4 verbatim message via `lang/en/auth.php`: `failed`, `password`, AND `throttle` all = "Email or password is incorrect" — the throttle message must not reveal account existence (no oracle). Verified `trans()` resolves.
- Lockout semantics: limiter keyed `email|ip` (Fortify default) → 5 failures lock that pair for 15 min; different account from same IP unaffected (test covers); correct password inside window still fails (test covers); works after 16 min (`travel()` test covers).
- FR-2 consistency fix in this story: disabled `Features::registration()` (self-registration is prohibited); removed register routes/page/links + deleted `RegistrationTest` (tested a feature that must not exist). Wayfinder regenerated.
- Assertion style note: `assertInvalid('email', msg)` fails on Fortify login because Inertia's exception handler flattens session errors for the page component — use `assertSessionHasErrors(['email' => msg])`.
- Verification: 10/10 AuthenticationTest (incl. 3 new FR-4 tests), suite 88/88, Pint clean, build green.

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `vendor/bin/pest tests/Feature/Auth/LoginTest.php` -- expected: green
- `vendor/bin/pest` full + `pint --dirty` + `npm run build` -- expected: green/clean
