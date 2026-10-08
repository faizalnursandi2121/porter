---
title: '2.3 — Mandatory password change at first login and after admin reset'
type: 'feature'
ticket: '3'
created: '2026-10-07'
status: done
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

**Problem:** Subtask 2.3 (FR-2 tail + FR-4a tail): new accounts (created by Supervisi/Administrator with a temporary password) and accounts whose password was reset by Supervisi/Administrator must change their password at next login before reaching any app page.

**Approach:** `users.password_changed_at` (nullable timestamp). Account-creation and admin-reset paths leave it NULL; the user's own change sets it. Middleware redirects any authenticated request to a `password.change` page unless it is that page or logout. Reset-by-email (self-service, FR-4a first half) marks it set — the person just proved ownership of the mailbox.

</frozen-after-approval>

## Code Map

- `database/migrations/` -- add `password_changed_at` to `users`.
- `app/Models/User.php` -- cast + `mustChangePassword()` helper.
- New `app/Http/Middleware/EnsurePasswordChanged.php` -- registered in `web` group after auth in `bootstrap/app.php`.
- New page `resources/js/pages/auth/change-password.tsx` + Fortify user-password store route (exists: `resources/js/routes/user-password/`).
- Creation path with temp password: `CreateNewUser` action exists but registration disabled — epic 2.6 admin-create will set NULL; for now seeders/factories must set a value or tests rely on middleware behavior. `InitialSeeder` admin = set now (self-set later by login; admin changes own password at first login too).
- Reset paths: `ResetUserPassword` action (admin, 2.3 scope marks it NULL); self-service email reset sets it (Fortify `ResetUserPassword` is admin-reset action here; the broker's reset is `App\Actions\Fortify\ResetUserPassword` too — differentiate: mark NULL only when reset performed by admin).
- Settings password page (`Settings/PasswordController`) — user changing own password in settings sets it.

## Tasks & Acceptance

**Execution:**
- [ ] `database/migrations/..._add_password_changed_at_to_users_table.php` + model cast/helper -- rationale: single source of the "must change" state.
- [ ] `app/Http/Middleware/EnsurePasswordChanged.php` + `bootstrap/app.php` registration -- rationale: FR requires the gate on every page.
- [ ] `resources/js/pages/auth/change-password.tsx` + route -- rationale: the destination page.
- [ ] `tests/Feature/Auth/ForcePasswordChangeTest.php` -- rationale: verify = feature tests pass.

**Acceptance Criteria:**
- Given a user with `password_changed_at` NULL, when authenticated and visiting `/dashboard`, then redirected to the change-password page; visiting `/settings/profile` likewise.
- Given that user submits a valid new password via the change page, then `password_changed_at` set and subsequent visits reach the dashboard.
- Given a user with `password_changed_at` set, then no redirect anywhere.
- Given logout + login again (no reset since), then no forced change (state persists on the column, not the session).
- Given admin reset another user's password (2.3 will stub the admin action; full admin UI is 2.6), then that user's `password_changed_at` NULL again → forced change on next login.

## Implementation Notes

- **Endpoint choice:** Fortify's generic `/user/password` PUT is NOT registered in this app (`Features::updatePasswords()` absent from `config/fortify.php`), so reusing it was impossible without flipping a feature flag. Added a small `App\Http\Controllers\Auth\ChangePasswordController` (`GET`/`PUT /change-password`, names `password.change.show` / `password.change.store`, throttled `6,1` like the settings route). Both self-service endpoints stamp the marker: `ChangePasswordController@store` and `Settings\SecurityController@update` (DRY: any self-service change resolves the temp-password state; FR-4a email reset sets it via `Actions\Fortify\ResetUserPassword`).
- **Middleware ordering reality:** appended `web` middleware runs BEFORE the route `auth` middleware in Laravel 11+ structure, so `EnsurePasswordChanged` resolves the user itself (`$request->user()` via session — StartSession precedes appends), passes guests through, and leaves unauthenticated redirects to `auth`. Allow-list uses route names (`password.change.*`, `logout`), not paths.
- **Seeded admin decision (per main-agent assignment):** `InitialSeeder` sets `password_changed_at = now()` — FR-2's forced change targets temp-password accounts created BY admins (epic 2.6 scope). Note: this intentionally supersedes the original seeder comment ("rotated on first login per FR-2"); the bootstrap admin owns the fresh install and must not be locked out of it.
- **UserFactory:** default state sets `password_changed_at = now()` (existing 88 tests unaffected); new `withTemporaryPassword()` state leaves it NULL for forced-change scenarios.
- **Verification:** `ForcePasswordChangeTest` 11 passed (33 assertions); scope run `tests/Feature/Auth + tests/Feature/Settings + DashboardTest` 57 passed. `pint --dirty` clean. Full suite + `npm run build` + single commit: integration owner (story 2.2 session). Pre-existing `tsc` error in `dashboard.tsx` (Property 'modules') confirmed present on clean HEAD — not from 2.3.

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `vendor/bin/pest tests/Feature/Auth/ForcePasswordChangeTest.php` -- expected: green
- `vendor/bin/pest` full + `pint --dirty` + `npm run build` -- expected: green/clean
