---
title: '1.5 — Seeder: 4 roles, initial Administrator, sample sites across timezones'
type: 'feature'
ticket: '5'
created: '2026-10-07'
status: 'built'
baseline_revision: '39dc637'
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

**Problem:** Subtask 1.5: seeders for the four roles (EOS, Supervisi, HR, Administrator), an initial Administrator account (no self-registration exists — FR-2), and sample sites with different timezones so epic-2 login and later stories have data to work against.

**Approach:** `RoleSeeder` (4 roles, idempotent updateOrCreate), `InitialSeeder` (admin account with fixed bootstrap credentials + 3 sites: Asia/Jakarta, Asia/Makassar, Asia/Jayapura), `DatabaseSeeder` delegates to `InitialSeeder`. Feature tests cover content + idempotency.

</frozen-after-approval>

## Implementation Notes

- `RoleSeeder`: 4 roles via updateOrCreate (idempotent). `InitialSeeder`: admin `admin@porter.local` (password constant `porter-admin-2026`; forced change lands with FR-2 in epic 2) + 3 sample sites (Jakarta/Makassar/Jayapura = WIB/WITA/WIT). `DatabaseSeeder` now calls InitialSeeder only (starter-kit Test User removed).
- Verification: db:seed clean; roles=4/users=1/sites=3 verified in tinker (admin role code ADMINISTRATOR; tz set exact); 3/3 SeederTest (content + idempotent re-seed); suite 83/83; Pint clean.

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `php artisan migrate:refresh --force && php artisan db:seed --force` -- expected: clean
- `vendor/bin/pest tests/Feature/SeederTest.php` -- expected: 3/3 green
- `vendor/bin/pest` full -- expected: green; `pint --dirty` -- clean
