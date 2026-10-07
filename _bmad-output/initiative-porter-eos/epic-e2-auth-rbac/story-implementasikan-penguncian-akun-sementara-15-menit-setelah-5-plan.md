---
title: '2.2 — Per-account 15-minute lockout after 5 consecutive failures'
type: 'feature'
ticket: '2'
created: '2026-10-07'
status: 'in-progress'
baseline_revision: 'fff0abc'
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

**Problem:** Subtask 2.2: FR-4's lockout is **per account** — after 5 consecutive failures, that account is locked for 15 minutes regardless of source IP or device. The 2.1 rate limiter keys `email|ip`, which an attacker bypasses by rotating IPs; FR-4's "akun terkunci" wording demands the durable key be the account.

**Approach:** Extend the auth pipeline with a per-account gate: attempts counter on the user (email key), 5 failures → locked until `locked_until` (15 min). Lock applies before password verification; generic FR-4 message preserved (no "account locked" reveal beyond what the generic message already hides — keep the same message; the lock simply makes every attempt fail). Successful login resets the counter. DB columns on `users`: `failed_login_count`, `locked_until`.

</frozen-after-approval>

## Code Map

- `app/Providers/FortifyServiceProvider.php` — `Fortify::authenticateThrough()` NOT currently customized; add pipeline class via `Fortify::authenticateUsing` NO — use a custom pipeline entry between `EnsureLoginIsNotThrottled` and `AttemptToAuthenticate`.
- New `app/Actions/Fortify/EnsureAccountIsNotLocked.php` — invokable pipeline step.
- `database/migrations/` — add `failed_login_count`, `locked_until` to `users`.
- `app/Models/User.php` — casts + helper `isLocked()`.
- Reset hook: `AttemptToAuthenticate` success path — clear counter (listener or pipeline step after auth).
- FR-4 tests: `tests/Feature/Auth/AuthenticationTest.php` (2.1's `test_five_consecutive_failures...` updates: now account-level, not email|ip).

## Tasks & Acceptance

**Execution:**
- [ ] `database/migrations/..._add_lockout_columns_to_users_table.php` -- `failed_login_count` (default 0), `locked_until` (timestamp nullable) -- rationale: durable per-account state survives IP rotation.
- [ ] `app/Actions/Fortify/EnsureAccountIsNotLocked.php` + pipeline wiring -- rationale: gate runs before credential check.
- [ ] `app/Http/Controllers/` or listener -- increment on failure, reset on success -- rationale: FR-4 "berturut-turut" (consecutive).
- [ ] `tests/Feature/Auth/AccountLockoutTest.php` -- rationale: verify = feature tests pass.

**Acceptance Criteria:**
- Given 5 wrong-password attempts for user U from any IPs, then U is locked 15 minutes: attempt #6 with the CORRECT password fails (locked).
- Given U locked, then a DIFFERENT user from the same IP can still log in (2.1's limiter untouched).
- Given U locked, after 15 minutes a correct-password attempt succeeds and the counter is cleared.
- Given U with 4 failures then a successful login, then a later single failure does NOT lock (counter reset — "berturut-turut").
- Given U locked, then every attempt returns the same generic FR-4 message (no "locked" wording).

## Implementation Notes

(filled during implementation)

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `vendor/bin/pest tests/Feature/Auth/AccountLockoutTest.php` + `AuthenticationTest.php` -- expected: green
- `vendor/bin/pest` full + `pint --dirty` -- expected: green/clean
