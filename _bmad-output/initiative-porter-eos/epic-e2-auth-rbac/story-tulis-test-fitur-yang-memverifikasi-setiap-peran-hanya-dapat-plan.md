---
title: '2.7 — Feature tests pinning per-role access authority'
type: 'feature'
ticket: '7'
created: '2026-10-07'
status: 'built'
baseline_revision: 'b1a31ba'
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

**Problem:** Subtask 2.7 (CAP-1 closure): feature tests that verify **every role reaches only its authorized features** — one consolidated role-scope suite pinning the rules spread across 2.1–2.6 (login/lockout 2.1-2.2, forced change 2.3, reset 2.4, role middleware 2.5, account management 2.6) and future epics' route guards.

**Approach:** `tests/Feature/Auth/RoleScopeTest.php` (Pest, data-driven matrix) asserting: guest → login redirect; EOS/SUPERVISI/HR/ADMINISTRATOR × every existing guarded route (dev stubs + admin.users GET/POST); policy gates (SitePolicy write matrix); forced-password-change gate ordering vs role (a temp-password ADMINISTRATOR still cannot bypass); login/lockout semantics stay role-agnostic (any role's account locks the same). No new production code expected — this is a pinning suite; add production code ONLY if a test exposes a real hole.

</frozen-after-approval>

## Code Map

- `tests/Feature/Auth/RoleAccessTest.php` (2.5) — route middleware matrix (7 tests). 2.7 complements: consolidates + adds policy/forced-change/lockout cross-cutting checks without duplicating.
- `tests/Feature/Admin/UserManagementTest.php` (2.6) — /admin/users matrix.
- `tests/Feature/Auth/AccountLockoutTest.php` (2.2), `ForcePasswordChangeTest.php` (2.3), `PasswordResetEdgeTest.php` (2.4).
- Guards: `EnsureRole` alias, `EnsurePasswordChanged`, `SitePolicy`, `routes/web.php` dev stubs.

## Tasks & Acceptance

**Execution:**
- [ ] `tests/Feature/Auth/RoleScopeTest.php` — data-driven 4-role × route matrix + policy + gate-ordering cases -- rationale: single place a reviewer reads "which role can do what".

**Acceptance Criteria:**
- Guest hits every guarded route → redirect to login (not 403).
- EOS: 200 on /eos/attendance; 403 on /supervisi/*, /admin/users (GET+POST), SitePolicy create.
- SUPERVISI: 200 on /supervisi/*, /admin/users (GET); 403 on POST /admin/users, SitePolicy create.
- HR: 403 on all module routes + SitePolicy create.
- ADMINISTRATOR: 200 on /supervisi/*, /admin/users (GET+POST allowed route), SitePolicy create allowed; 403 on /eos/attendance (superset ≠ EOS personal area).
- A temp-password user of ANY role gets redirected to change-password even on routes their role allows (gate order: role check happens, but forced change wins for page access).
- Lockout test: 5 failures lock an EOS account the same as any other (role-agnostic).

## Implementation Notes

- 7 tests / 57 assertions in `RoleScopeTest`: guest-redirect matrix; 4-role × 3 guarded routes matrix (allowed vs 403 exact); POST /admin/users ADMIN-only while SUPERVISI GET stays 200; SitePolicy view/create/update/delete × 4 roles; forced-password-change gate fires for EVERY role before role-allowed pages (uses `withTemporaryPassword()` state); lockout role-agnostic (all 4 roles lock identically, correct password inside window rejected).
- Unguarded-write sentinel test: iterates all POST routes, rejects the intentional public/benign set (password/*, email/*, passkeys/*, user/passkeys, confirm-password, logout, two-factor, health) and asserts the rest carry `auth`. First run caught 5 real POST routes without `auth` in their middleware list — all Fortify passkey/verification ceremony endpoints (session-adjacent by design, documented in the reject list).
- No production code changed — suite is pure pinning; the sentinel test is the regression tripwire for future epics adding routes without auth.
- Verification: 7/7 RoleScopeTest; full suite 136/136 (563 assertions); Pint clean.

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `vendor/bin/pest tests/Feature/Auth/RoleScopeTest.php` -- expected: green
- `vendor/bin/pest` full + `pint --dirty` -- expected: green/clean
