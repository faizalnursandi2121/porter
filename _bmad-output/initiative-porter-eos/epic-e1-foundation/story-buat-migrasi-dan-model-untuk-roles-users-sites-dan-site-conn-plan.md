---
title: '1.1 — Migrations and models: roles, users, sites, site_connections'
type: 'feature'
ticket: '1'
created: '2026-10-07'
status: done
baseline_revision: 'e1008d8'
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

**Problem:** Subtask 1.1: migrations + models for `roles`, `users`, `sites`, `site_connections` per the ERD — the base every later epic queries. No PORTER schema exists yet (only Laravel defaults + a nullable `users.role` string stub from the starter-kit era).

**Approach:** `roles` lookup table (5 fixed roles, id + code + label), `users.role_id` FK replacing the string stub, `sites` master data per FR-34 (name, address, lat/lng, timezone enum WIB/WITA/WIT→IANA, active), `site_connections` per FR-34/§11 (per-site primary/backup internet provider rows). Models with relations; cast enums. Feature tests prove schema path: role rows, FK integrity, site+connections CRUD basics.

</frozen-after-approval>

## Code Map

- `database/migrations/0001_01_01_000000_create_users_table.php` -- Laravel default users table; keep, add `role_id` in a NEW migration (never edit shipped ones).
- `database/migrations/2026_10_06_101231_add_role_to_users_table.php` -- nullable `role` string stub (32) — superseded; new migration drops it after `role_id` lands.
- `app/Models/User.php` -- has `#[Fillable]`/`#[Hidden]` attributes + casts; add `role_id` fillable + `role` relation; keep Fortify interfaces intact.
- `app/Models/` -- only `User` exists; new: `Role`, `Site`, `SiteConnection`.
- ERD truth: PRD FR-34 (site fields + providers), spec §11 legacy (`site_network_links` naming superseded by subtask's `site_connections`), legacy §8.1 (role fixed set) — 4 roles for PORTER v1 per spec-porter kernel (EOS, SUPERVISOR, HR, SUPER_ADMIN... see Open Questions).
- `database/factories/UserFactory.php` -- exists; extend for role; add `RoleFactory`, `SiteFactory`, `SiteConnectionFactory`.

## Tasks & Acceptance

**Execution:**
- [ ] `database/migrations/2026_10_07_*.php` (4 files) -- create `roles`; create `sites`; create `site_connections`; migrate `users.role_id` + drop `role` string -- rationale: one concern per migration, ordered by FK dependency.
- [ ] `app/Models/Role.php`, `Site.php`, `SiteConnection.php` -- models + relations + enum casts -- rationale: Eloquent API surface for all later stories.
- [ ] `app/Models/User.php` -- `role()` belongsTo + `role_id` fillable -- rationale: FR-1 single role per user.
- [ ] `database/factories/` -- `RoleFactory`, `SiteFactory`, `SiteConnectionFactory`; extend `UserFactory` -- rationale: tests need factories per conventions.md.
- [ ] `tests/Feature/` -- schema/relation feature tests -- rationale: verify = "feature tests for the behavior pass".

**Acceptance Criteria:**
- Given a fresh migrate, when `php artisan migrate` runs, then all four tables exist with correct columns and FK constraints.
- Given a user with a role, when queried, then `$user->role->code` resolves; dropping the string `role` column breaks nothing.
- Given a site with primary+backup connections, when queried, then `$site->connections` returns both with `kind` distinguishing them.
- Given timezone input `WIB`, then the stored value maps to `Asia/Jakarta` (cast).

## Open Questions

1. ~~Role code set~~ — RESOLVED by product owner (2026-10-07, chat): exactly 4 roles — EOS, Supervisi, HR, Administrator. Legacy 5-role set is not authoritative (AGENTS.md). FE stubs referencing `MANAGER`/`SUPER_ADMIN` (dashboard.tsx ×7, auth.ts) normalize in this story: `SUPER_ADMIN`→`ADMINISTRATOR`, `MANAGER` tiles fold into `SUPERVISOR`.
2. ~~Role naming~~ — RESOLVED (same message): `code` = stable English uppercase identifier (`EOS`, `SUPERVISOR`, `HR`, `ADMINISTRATOR`); `label` = locked PRD term (`EOS`, `Supervisi`, `HR`, `Administrator`) for UI copy.

## Implementation Notes

- Schema: `roles` (id, unique `code`, `label`, timestamps) → `sites` (name, address nullable, lat/lng decimal(10,7), `timezone` IANA string default Asia/Jakarta, `is_active`) → `site_connections` (`site_id` FK cascade, `kind` PRIMARY|BACKUP, unique(site_id,kind), provider, is_active) → `users.role_id` FK restrict + drop of the starter-kit `role` string stub (2026_10_06 migration left untouched; superseded).
- Models: `Role` (consts EOS/SUPERVISOR/HR/ADMINISTRATOR), `Site` (TIMEZONES map WIB/WITA/WIT→IANA, `primaryConnection()` helper), `SiteConnection` (PRIMARY/BACKUP consts), `User.role()` belongsTo; `role_id` fillable.
- Factories: RoleFactory (code state), SiteFactory (inactive, timezone states), SiteConnectionFactory (backup state), UserFactory `withRole(code)` via firstOrCreate so tests don't need seeded roles.
- FE stub normalization per resolved decision: dashboard.tsx `SUPER_ADMIN`→`ADMINISTRATOR` (4 sites), `MANAGER` folded into `SUPERVISOR` (Analytics tile now SUPERVISOR+ADMINISTRATOR); auth.ts doc comment updated. No `MANAGER`/`SUPER_ADMIN` references remain in resources/js.
- Timezone stored as IANA string (not enum) — the WIB/WITA/WIT↔IANA mapping lives in `Site::TIMEZONES`; helper conversion + tz seed data are 1.5/1.6's scope.
- Verification: migrate up clean; `migrate:rollback --step=4` drops FK-safe then re-migrates; 8/8 new feature tests pass; full suite 49/49; Pint clean after fixes (import order, FQCN).

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `php artisan migrate --force` (sail) -- expected: 4 new migrations run clean
- `php artisan migrate:rollback` batch -- expected: drops cleanly (down() reverses in FK-safe order)
- `vendor/bin/pest tests/Feature/<schema tests>` -- expected: green
- `vendor/bin/pint --dirty --format agent` -- expected: clean

**Manual checks:**
- `users.role` string column gone; `role_id` FK with restrict-on-delete.
- PRD decision noted if (a)/(b)/(c) chosen.
