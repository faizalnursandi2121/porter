# Code Review — epic-e1-foundation (2026-10-07)

Range `e1008d8..f92994d` (HEAD), 71 files +2727/−22. Lenses: blind-hunter + edge-case-hunter + verification-gap + intent-alignment. Suite: 83 passed / 242 assertions (sail).

**0 decision-needed, 5 patch, 6 defer, 14 rejected.**

## Patch

- [x] [Review][Patch] **auth.role shared as Role model, FE expects code string — launcher renders 0 tiles** [app/Http/Middleware/HandleInertiaRequests.php:46] — `users.role` column dropped; `$request->user()?->role` now resolves a Role model → Inertia serializes object; `dashboard.tsx:113` `tile.roles.includes(auth?.role)` + `types/auth.ts` `role?: string` never match. No test observes `auth.role` (DashboardTest is assertOk-only). Fix: `->role?->code` + DashboardTest `assertInertia` asserting `auth.role` code per role.
- [x] [Review][Patch] **Attendance::dailyReport() relation queries non-existent column** [app/Models/Attendance.php:58] — `belongsTo(DailyReport)` looks up `attendances.daily_report_id`; FK lives on `daily_reports.attendance_id`. Fix: `hasOne(DailyReport::class, 'attendance_id')` + test.
- [x] [Review][Patch] **DatabaseSeeder delegation never executed by tests** [tests/Feature/SeederTest.php] — bootstrap path (fresh-env admin provisioning, FR-2) untested; SeederTest seeds InitialSeeder directly. Fix: add `$this->seed(DatabaseSeeder::class)` test asserting 4 roles + admin + 3 sites.
- [x] [Review][Patch] **PrivateStorageTest probe file leaks on failed assertion** [tests/Feature/PrivateStorageTest.php:31-43] — delete runs after assertions, skipped on failure. Fix: try/finally (or afterEach cleanup) in both smoke tests.
- [x] [Review][Patch] **SiteTimeTest Carbon::setTestNow leaks into first Feature test** [tests/Unit/SiteTimeTest.php:52-57] — Unit runs first; plain PHPUnit TestCase has no teardown clearing mocked clock; Pest.php binds TestCase only for Feature. Fix: bind `pest()->extend(TestCase::class)->in('Unit')` (mirrors Feature).

## Defer

- [x] [Review][Defer] Plan-doc hygiene: 1.4 frozen intent self-contradiction, stale "5 roles" in 1.1 approach, empty Plan Change Logs, dangling OK? in 1.2, risk/review front-matter mismatch [_bmad-output/.../epic-e1-foundation/*-plan.md] — deferred: fixes edit plan docs (workflow rule).
- [x] [Review][Defer] Factory tz snapshots use server date/Jakarta (AttendanceFactory, AuditLogFactory, DailyReportFactory::forAttendance) — deferred: epics 4/5 rewrite these creation paths; wrong tz only misshapes future test data, no shipped caller.
- [x] [Review][Defer] Bootstrap password plaintext in VCS (InitialSeeder::INITIAL_ADMIN_PASSWORD) — deferred: epic 2 first-login forced rotation lands before any real deploy; rotation closes the window.
- [x] [Review][Defer] `/manager/analytics/overview` route + HR role with zero tiles (silent empty dashboard) — deferred: epic 7 owns Analytics routes; HR gets its module then; cosmetic today.
- [x] [Review][Defer] Case-variant free-text bypass (site_connections.kind, status/timezone columns unconstrained) — deferred: maybe-false until epic-7 DB-constraint review decides CHECK coverage; settle with one constraint audit.
- [x] [Review][Defer] `'throw' => false` on storage disks risks silent write failures — deferred: pre-existing baseline config, not this diff; epic 7 storage endpoint hardening.

## Rejected (key refutations)

- DB CHECK constraints on every status/timezone/kind column (~10 edge-case findings): design choice recorded in plans 1.1–1.3 (const vocabulary + unique/FK); PRD decision #14 names the five critical DB-guarded rules — all covered; epic 7 owns constraint review. Over-engineering beyond diff scope.
- InventoryAuditSchemaTest `DB::commit()` leaks rows: disproven — probe runs on separate `probe` connection, suite green.
- Unique-violation tests assert `RuntimeException`: it IS Laravel's parent of `UniqueConstraintViolationException`; behavior still exercised.
- Attendance selfie bare path columns: PRD/legacy model keeps selfies as path columns (FR-9a/§5), no disk/mime requirement.
- StockMovement direction semantics: before/after balances make direction derivable; `type` enum exists.
- ReportSectionTemplateFactory unique() exhaustion: needs 6th template in one process; order guarded by static sequence — not met in everyday use.
- EXCLUDE-overlap assignments, report-number↔status CHECK, ledger derive-check: app-level stories 5.8/4.x own these; schema already carries the PRD-critical invariants.
