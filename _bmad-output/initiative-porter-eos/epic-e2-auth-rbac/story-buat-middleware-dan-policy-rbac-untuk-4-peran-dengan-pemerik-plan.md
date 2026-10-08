---
title: '2.5 — RBAC middleware and policy for the 4 roles, checked server-side on every request'
type: 'feature'
ticket: '5'
created: '2026-10-08'
status: done
baseline_revision: '13f26d0'
route: 'full'
route_source: 'auto'
risk: 'high'
review: 'quick'
review_source: 'pinned'
lenses_ran: []
review_loop_iteration: 0
context: []
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Subtask 2.5 (FR-1): the four PORTER roles (EOS, SUPERVISI, HR, ADMINISTRATOR) must be enforced SERVER-SIDE on every request. Administrator owns every Supervisi capability plus template, master-data and audit-log management (PRD decision). No spatie/laravel-permission — plain Laravel primitives (Ask First rule).

**Approach:** Single `role_id` FK on users (epic 1 schema) + `Role::` code constants. Role helpers on `App\Models\User` (`hasRole(...$codes)`, per-role shortcuts, `hasSupervisiAccess()` for the ⊇ rule). A parameterized `EnsureRole` middleware (`role:EOS,SUPERVISI`) aborts 403 on mismatch, registered under the `role` alias. Policy foundation: `SitePolicy` proving the pattern later epics extend. Dev-stub probe routes in `routes/web.php` give the middleware observable coverage until epics 2.6/3/4 land the real modules.

</frozen-after-approval>

## Code Map

- `app/Models/User.php` -- `hasRole(string ...$codes)`, `isEos()`, `isSupervisi()`, `isHr()`, `isAdministrator()`, `hasSupervisiAccess()`; all reference `Role::` consts.
- `app/Http/Middleware/EnsureRole.php` -- variadic `handle(string ...$roles)`, `abort(403)` on mismatch.
- `bootstrap/app.php` -- `$middleware->alias(['role' => EnsureRole::class])`.
- `routes/web.php` -- dev-stub guarded routes `/eos/attendance`, `/supervisi/attendance`, `/admin/users`, each commented with the epic that replaces it.
- `app/Policies/SitePolicy.php` -- viewAny/view: any authenticated; create/update/delete: ADMINISTRATOR (FR-34).
- `app/Providers/AppServiceProvider.php` -- `Gate::policy(Site::class, SitePolicy::class)`.
- `tests/Feature/Auth/RoleAccessTest.php` -- per-role matrix + superset helper + Site policy matrix.

## Tasks & Acceptance

**Execution:**
- [x] User role helpers -- rationale: one source of truth for role membership; middleware/policies/callers never hard-code strings.
- [x] EnsureRole + `role` alias -- rationale: the per-request server-side gate.
- [x] Dev-stub guarded routes -- rationale: real module routes belong to epics 2.6/3/4; middleware still needs observable coverage now.
- [x] SitePolicy + registration -- rationale: the policy pattern later epics extend.
- [x] RoleAccessTest -- rationale: verify = feature tests pass.

**Acceptance Criteria:**
- Given an EOS user, when hitting `/eos/attendance`, then 200; `/supervisi/attendance` and `/admin/users` then 403.
- Given a Supervisi user, then `/supervisi/attendance` 200; `/admin/users` 403.
- Given an Administrator, then `/supervisi/attendance` and `/admin/users` 200 (⊇ rule); `/eos/attendance` 403 (personal EOS module is EOS-only).
- Given an HR user, then 403 on all module stubs (HR area arrives with a later epic).
- Given a guest, then redirect to login on every stub.
- Given EOS/SUPERVISI/HR, then `viewAny`/`view` Site allowed, `create`/`update`/`delete` denied; given ADMINISTRATOR, all allowed.

## Implementation Notes

- **Variadic middleware parameter:** `handle(Request, Closure, string ...$roles)` — the pipeline splits the `role:SUPERVISI,ADMINISTRATOR` pipe string on commas (`Pipeline::parsePipeString`), so each code arrives as its own argument and the route definition stays `role:CODE,CODE`. Gate is `$request->user()?->hasRole(...$roles)`; guests fail the check and get the same 403 (the `auth` middleware in front already redirects them to login, 403 is only reachable for authenticated mismatches).
- **Superset scope decision (from the dashboard launcher reality):** PRD says Administrator ⊇ Supervisi — the launcher exposes `/eos/*` (EOS personal modules) and `/supervisi/*` (monitoring/management) as separate areas, so Administrator does NOT pass the EOS-only gate. Stubs: `/eos/attendance` → `role:EOS`; `/supervisi/attendance` → `role:SUPERVISI,ADMINISTRATOR`; `/admin/users` → `role:ADMINISTRATOR`. `hasSupervisiAccess()` is the reusable ⊇ helper for epics 2.6/3.x controllers.
- **Dev-stub routes:** clearly marked FR-1 probe routes; each carries a comment naming the epic that replaces it (epic 4 for the two attendance stubs, 2.6 for `/admin/users`). They exist ONLY so the middleware has observable coverage; epics must delete them when the real modules land.
- **SitePolicy:** PRD 3.x — Administrator manages site master data (FR-34), Supervisi/HR view. `viewAny`/`view` return true for any authenticated user (policy methods only run post-auth); `create`/`update`/`delete` are `isAdministrator()`. Registered explicitly via `Gate::policy()` in `AppServiceProvider::boot()`. Other models' policies belong to their epics — do not pile on here.
- **Pint:** fixed a superfluous `@param` on `hasRole()` (inline type already declares it); FR-1 WHY comment kept.

## Plan Change Log

- 2026-10-08: plan file was absent from the working tree at build time (2.2/2.3-style plans exist, this one was never written); reconstructed from the subtask 2.5 ticket + PRD after the build, documenting what shipped.

## Review Triage Log

## Verification

**Commands:**
- `./vendor/bin/sail php artisan test tests/Feature/Auth/RoleAccessTest.php --compact` -- expected: green (7 passed, 42 assertions)
- `./vendor/bin/sail php artisan test tests/Feature/ --compact` -- expected: green (102 passed)
- `vendor/bin/pint --dirty --format agent` -- expected: clean
