---
title: '2.6 — Account management for Supervisi/Administrator: create EOS with mandatory placement'
type: 'feature'
ticket: '6'
created: '2026-10-07'
status: done
baseline_revision: '1e98d39'
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

**Problem:** Subtask 2.6 (CAP-1): Supervisi/Administrator need the account management page — list users, create accounts (any of the 4 roles), and create EOS accounts with a mandatory school placement. FR-2: new accounts get a temporary password and must change it at first login (2.3's gate already enforces when `password_changed_at` is NULL). FR-3: moving an EOS / ending assignments is the placement domain — this story delivers create + placement-at-creation; move/end/history rides epic 3's assignment stories.

**Approach:** Route group `/admin/users` (Administrator-only create/delete; Supervisi read-only per PRD matrix + 2.5 policy pattern), Inertia pages with shadcn: Table list (ui-design §2.2: header + Table + Badge role + empty state), Dialog create form (role Select; when EOS → mandatory site Select), temp password shown once on creation success, audit log rows on create (FR-46). Admin reset password action (FR-4a tail: sets temp password, `password_changed_at` NULL via 2.4's `marksPasswordChanged` flag).

</frozen-after-approval>

## Code Map

- `app/Models/User.php` — role helpers (`hasRole`, `isAdministrator`, …) from 2.5; `password_changed_at`.
- `app/Actions/Fortify/ResetUserPassword.php` — `marksPasswordChanged` ctor flag (2.4): `false` = temp password mode.
- `app/Policies/SitePolicy.php` — 2.5 pattern for policies; `EnsureRole` middleware alias `role:CODES`.
- `resources/js/pages/settings/*.tsx` — existing Inertia form + shadcn patterns (Label/Input/Select/Button).
- `docs/PRD/ui-design.md §2.2/§2.3` — list/form anatomy; §7 English copy.
- `audit_logs` — 1.3 schema: actor_id, actor_role, action, object_type/id, before/after jsonb.

## Tasks & Acceptance

**Execution:**
- [ ] `app/Http/Controllers/UserManagementController.php` + `app/Http/Requests/StoreUserRequest.php` — list (paginate 15, with role + active assignment site for EOS), create (temp password via `Str::password(12)`, hash, `password_changed_at = null`), reset-password action (new temp password, marks it changed=false, audit) -- rationale: server-side authority per PRD.
- [ ] `routes/web.php` — `/admin/users` group, `role:ADMINISTRATOR` for create/reset; `role:SUPERVISI,ADMINISTRATOR` for index (read-only for Supervisi) -- rationale: PRD matrix; matches 2.5 middleware.
- [ ] `resources/js/pages/admin/users/index.tsx` + `create.tsx` (Dialog on index) — shadcn Table/Badge/Dialog/Select/Form/Alert; empty state English; pagination controls -- rationale: ui-design §2.2 + shadcn mandate.
- [ ] Audit rows — `audit_logs` insert on user.created / user.password_reset -- rationale: FR-46 lists account management.
- [ ] `tests/Feature/Admin/UserManagementTest.php` -- rationale: verify = feature tests pass.

**Acceptance Criteria:**
- Given Administrator, when visiting `/admin/users`, then paginated table lists users with role badge and (EOS) site; given Supervisi, same page read-only (no create/reset controls, 403 on POST); given EOS/HR, 403.
- Given create form with role EOS and no site selected, then validation error requires placement; with site, then user created with temp password + `password_changed_at` NULL + success dialog shows the temp password once; audit row written.
- Given create with role SUPERVISI/HR/ADMINISTRATOR, then no site required.
- Given reset-password on a user, then new temp password issued, `password_changed_at` NULL again (forced change gate re-arms), audit row written.
- Given duplicate email, then unique validation error.

## Implementation Notes

- **Backend**
  - `StoreUserRequest`: `role_code` validated against `Rule::in(Role::EOS, …)` consts; `site_id` is `Rule::requiredIf(role_code === EOS)` + `exists(sites.id)` scoped to **active** sites only; custom message "An EOS account must be placed at a school."
  - `UserManagementController::index`: paginates 15 (`withQueryString`), eager-loads `role` + active `assignments.site` (Select-list safe: only `id`/`name` columns). Also passes `sites` (active, for the create form), the 4 `roles` (code/label), and `canManageUsers` (`isAdministrator()`) — deliberately **not** nested under `auth` so the middleware-shared `auth` prop is untouched.
  - `store`: DB transaction — `User::create` (temp `Str::password(12)`, hashed cast, `password_changed_at = null`), `Assignment::create` when `site_id` present (started today), audit row `user.created` (actor_id/role, object `user`, before null, after jsonb payload), then `Inertia::flash('temp_password', …)` → one-shot success dialog.
  - `resetPassword`: takes a **plain `Request`** (FormRequest would apply create-form validation to the reset route); `app(ResetUserPassword::class, ['marksPasswordChanged' => false])` per 2.4 contract → `password_changed_at` re-armed to NULL; audit `user.password_reset`; flashes new temp password once.
  - Audit via private `recordAudit()` (`AuditLog::create`); `occurred_at now()`, `occurred_date_local today`.
- **Routes**: replaced the `dev.admin.users` stub with `admin.users.index` (`role:SUPERVISI,ADMINISTRATOR`), `admin.users.store` + `admin.users.reset-password` (nested `role:ADMINISTRATOR` group). Wayfinder regenerated `resources/js/actions/.../UserManagementController.ts` (gitignored).
- **Model**: added `User::assignments(): HasMany` (needed for the EOS site column; FR-3 history rides epic 3). This touched `app/Models/User.php` — outside the original contract, required by the plan's eager-load requirement.
- **Frontend** (`resources/js/pages/admin/users/`)
  - `index.tsx`: page header + shadcn Table (Name, Email, Role Badge — ADMINISTRATOR=default, SUPERVISI=secondary, EOS=outline, HR=destructive, role-tint-free token variants; Site for EOS; Created `Intl.DateTimeFormat('en-GB')`; Actions DropdownMenu) inside an `overflow-x-auto` container for mobile. Empty state: "No users yet. Create the first account." Server-side pagination: paginator `links` array rendered as Inertia `Link`s with `withQueryString`, plus "x–y of n" count; no precedent existed in the repo, so standard `users.links` pattern per plan fallback.
  - `create.tsx`: Inertia `<Form {...UserManagementController.store.form()}>` in a Dialog; role Select (4 codes/labels); site Select appears only when role = EOS ("School placement" + helper copy). Radix Select renders no native input, so values are mirrored into hidden inputs (Inertia `<Form>` collects via FormData). Form resets + dialog closes `onSuccess`.
  - `temp-password-dialog.tsx`: monospace read-only input + copy button (existing `useClipboard` hook, Check/Copy icon swap) + Done. Open only while the flashed value is in local state (`useTempPasswordFlash`), so refresh/next navigation kills it — value exists nowhere else client-side.
  - Reset uses a confirm Dialog, then `router.post(resetPassword.url(id))` (repo pattern from `manage-passkeys.tsx`). Success toast rides the shared `flash.toast` sonner path; Supervisi sees no create/reset controls (`canManageUsers` gate) and 403s are server-enforced anyway.
- **Tests**: 9 tests in `UserManagementTest.php` cover every AC: full role matrix (admin full, supervisi GET-only, EOS/HR 403, guest redirect), EOS-without-site error, EOS-with-site (temp hash matches flash, `password_changed_at` NULL, assignment row, audit row), non-EOS trio without site, reset re-arms gate + audit + old password dead, duplicate email, index props/badge/site/pagination, Supervisi `canManageUsers=false`. Flash pulled from session key `inertia.flash_data.temp_password`.
- **Coordination**: `tests/Feature/Auth/RoleAccessTest.php` line 37 updated per Main's grant (Supervisi GET `/admin/users` → `assertOk()` + added POST `assertForbidden()`; comment refreshed). Without it, the frozen AC and 2.5 baseline test conflicted.
- **Surprises**: `User` model had no `assignments()` relation (2.5 stopped at role helpers); Inertia v3 `<Form>` reads fields straight from the DOM (FormData), which is why Select values need hidden-input mirroring; `users.meta` doesn't exist on Laravel paginators serialized through Inertia — only `links`/`current_page`/`last_page`/`total`.

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `vendor/bin/pest tests/Feature/Admin/UserManagementTest.php` -- expected: green
- `vendor/bin/pest` full + `pint --dirty` + `npm run build` -- expected: green/clean

**Manual checks:**
- Pages render per ui-design §2.2 anatomy with shadcn components only; small-screen smoke (Card list fallback).
