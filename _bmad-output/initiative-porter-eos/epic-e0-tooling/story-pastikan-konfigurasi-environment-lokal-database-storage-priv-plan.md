---
title: '0.2 — Verify local environment readiness (database, private storage, mail)'
type: 'chore'
ticket: '2'
created: '2026-10-07'
status: 'built'
baseline_revision: '16216f3'
route: 'oneshot'
route_source: 'auto'
risk: 'medium'
review: 'quick'
review_source: 'pinned'
lenses_ran: ['quick']
review_loop_iteration: 0
context: []
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Before any PORTER feature code lands, the local environment must be proven working for the three domains subtask 0.2 names: database (migrations run), private storage (photos live outside the public root per stack.md), and mail.

**Approach:** Bring the Sail stack up, run `php artisan migrate`, and smoke the private disk (write/read/delete via `Storage::disk('local')`, root `storage/app/private`) plus the mail mailer (send and observe output) on the configured local env. Fix only what verification proves broken; add no new services.

</frozen-after-approval>

## Implementation Notes

(oneshot route: environment verification with a one-line .env correction; no production code.)

- Sail stack (`laravel.test` + `pgsql` on postgres:18-alpine) restarted after 13h Exited state; both healthy.
- DB: `migrate --force` → "Nothing to migrate"; 10 tables present: 9 from migrations (users, password_reset_tokens, sessions, cache, cache_locks, jobs, job_batches, failed_jobs, passkeys) + `migrations` itself. No PORTER schema yet — that is epic 1's scope, not an env gap.
- Private storage: disk `local` root = `storage/app/private`; write/read/delete smoke passed. Directory is outside `public/`, no symlink reaches it — matches stack.md "private disk outside public root".
- Mail: `MAIL_MAILER=log`; raw send succeeded, message rendered in `storage/logs/laravel.log`. No Mailpit/smtp trap exists anywhere; spec/docs do not mandate one, so none was added (Ask First rule).
- Fixed: `.env` `APP_URL` `http://localhost:8000` → `http://localhost` — Sail maps `${APP_PORT:-80}:80`; port 8000 was unreachable (curl 000) and `route('home')` generated the dead URL. Re-verified: `route('home')` → `http://localhost`, curl port 80 → 302.
- Heads-up for the FR-48/49 photo story (epic 4): the `local` disk ships `serve => true`, which exposes Laravel-mediated `GET /storage/{path}` (storage.local) and `PUT /storage/{path}` (storage.local.upload, raw-body write with only a signature check) without role check or audit. Spec requires role-checked, audit-writing streaming endpoint. Decision (flip `serve` off vs. guard the route) belongs to that story, not 0.2.

## Plan Change Log

## Review Triage Log

- 2026-10-07 quick lens, 3 findings:
  - `.env.example` still had `APP_URL=http://localhost:8000` — verdict `medium` (real; committed template would reproduce dead URLs on next bootstrap), patched: aligned to `http://localhost`, verified against `compose.yaml` `${APP_PORT:-80}:80`.
  - `serve => true` also registers `PUT /storage/{path}` (`storage.local.upload`) writing raw bodies with only a signature check — verdict `medium` (real, verified via `route:list`), accepted as plan-note scope extension: Implementation Notes now name both routes for the epic-4/1.7 decision. No code change in 0.2 (guarding routes is that story's job).
  - Plan said "10 Laravel-default tables" vs 9 migration-defined tables — verdict `low` (record-keeping), corrected: 9 from migrations + `migrations` itself.

## Verification

**Commands (all executed, 2026-10-07):**
- `./vendor/bin/sail ps` -- expected: `laravel.test` and `pgsql` Up
- `./vendor/bin/sail php artisan migrate --force` -- expected: nothing to migrate / success
- `Storage::disk('local')` put/exists/get/delete via tinker -- expected: written true → readback matches → after-delete false
- `Mail::raw(...)` via tinker with `MAIL_MAILER=log` -- expected: message text in `storage/logs/laravel.log`
- `curl -o /dev/null -w '%{http_code}' http://localhost/` -- expected: 302 (app answers on the URL `route()` generates)

**Manual checks:**
- `- [ ] 0.2` → `- [x]` in `Docs/PRD/tasks-task-list-baru.md` after the checks above; parent `- [ ] 0.0` stays open until 0.3–0.5 land.
