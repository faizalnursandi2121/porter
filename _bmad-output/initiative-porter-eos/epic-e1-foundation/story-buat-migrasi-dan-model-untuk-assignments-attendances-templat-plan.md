---
title: '1.2 — Migrations and models: assignments, attendances, templates, section_templates, daily_reports, report_sections, report_photos'
type: 'feature'
ticket: '2'
created: '2026-10-07'
status: done
baseline_revision: '3358447'
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

**Problem:** Subtask 1.2: migrations + models for the attendance and Daily Report domains — `assignments`, `attendances`, `templates`, `section_templates`, `daily_reports`, `report_sections`, `report_photos` — the schema every later story (epics 4–7) queries. Only 1.1's tables exist so far.

**Approach:** FK-safe migration order (assignments → attendances → templates → section_templates → daily_reports → report_sections → report_photos), models with relations + enum-ish consts, partial unique indexes for the business invariants (one active assignment per EOS/site; one attendance row per EOS per local date; unique report number; one section row per report per template section). Snapshot semantics per FR-25 baked in (report stores template_id + snapshot fields on sections). Timezone-aware local date columns (`work_date_local`) per FR-7/§10 legacy-time handling — UTC timestamps + local-date columns.

</frozen-after-approval>

## Code Map

- `app/Models/Role.php`, `Site.php`, `SiteConnection.php`, `User.php` -- 1.1 models; FK targets. `Site::TIMEZONES` used for local-date derivation later (helper is 1.6).
- `database/migrations/2026_10_07_0715*.php` -- 1.1 migrations (order reference for new batch).
- FR sources: FR-3 (assignments invariants), FR-7/8/9a-c (attendance), FR-14/15/20-25 (report/template), FR-20a (photos), FR-22a (report number `CMX.WR.YYYYMM.SEQ`).
- Verification target: `tests/Feature/SchemaFoundationTest.php` pattern (1.1) extends to `ReportSchemaTest.php`.

## Tasks & Acceptance

**Execution:**
- [ ] `database/migrations/` (7 files) -- one table per migration, ordered by FK dependency -- rationale: each concern isolated; down() reverses in reverse order.
- [ ] `app/Models/` (7 new) -- Assignment, Attendance, ReportTemplate, ReportSectionTemplate, DailyReport, ReportSection, ReportPhoto -- rationale: naming matches subtask's table names (`templates`→`ReportTemplate` alias documented in model; table name kept per subtask).
- [ ] `database/factories/` (7 new) + UserFactory role states already exist -- rationale: conventions.md requires factories; epics 4-7 tests need them.
- [ ] `tests/Feature/ReportSchemaTest.php` -- invariants: active-assignment uniqueness, one-attendance-per-date, report number unique, section uniqueness, photo FK cascade -- rationale: verify = feature tests pass.

**Acceptance Criteria:**
- Given fresh migrate, then 7 tables exist with FK constraints to users/sites/templates.
- Given an active assignment for EOS X at site Y, then creating another active assignment for X (any site) or for another EOS at Y fails (partial unique indexes).
- Given an attendance row for EOS X on local date D, then a second row for X on D fails.
- Given a submitted report, then `report_number` matches `CMX.WR.YYYYMM.NNNN` format and is unique; draft has null number.
- Given a report, then each `report_sections` row references its template section (snapshot FK) and unique(report_id, section_template_id) holds.
- Given photos rows, then deleting the report cascades; `report_photos.section_id` nullable when photo belongs to report-level (selfie equivalent section semantics per FR-20a).

## Open Questions

1. **`report_photos` grouping**: FR-20a caps "≤5 photos/section; ≤10/report; 1 selfie per check-in/out" — selfies live in `attendances` (selfie path column), NOT in report_photos. Report photos attach to a section (nullable `section_id` not needed — every report photo belongs to a section per FR-20). Plan: `report_photos.section_id` NOT NULL FK. OK?
2. **Attendance status values**: derive `CheckedIn`/`Completed` (FR-9b wording) as string consts on model, DB string column (no pg enum — migrations portable). OK?
3. **Soft deletes**: none anywhere (audit log + append-only DB is the safety net; PRD has no soft-delete requirement). Confirm none?

## Implementation Notes

- 7 migrations, FK order: assignments → attendances → templates → section_templates → daily_reports → report_sections → report_photos.
- Invariants at DB level (Postgres partial unique indexes): `assignments_one_active_per_eos` / `assignments_one_active_per_site` (WHERE ended_at IS NULL); attendances unique(user_id, work_date_local); daily_reports unique(user_id, work_date_local) + report_number unique (null for drafts — Postgres treats NULLs as distinct); report_sections unique(daily_report_id, section_template_id); section_templates unique(template_id, order).
- **Gotcha found**: fluent `$table->unique('user_id', 'name')->whereNull('ended_at')` silently drops the WHERE clause (verified via pg_indexes — indexdef had no WHERE). Both partial indexes are raw `DB::statement`; comment in migration records this.
- Timezone/tz: attendances + daily_reports carry `work_date_local` + `timezone` snapshot (FR-7); UTC `timestamp(6)` for checked_in/out, submitted_at. `attendance_id` on daily_reports nullable (check-out gate comes later; report can exist for a day with lost attendance edge — FR-24 reopen flow needs the link once checked).
- `report_sections.payload` jsonb + GIN index (stable core KPI fields live inside payload per FR-25).
- `ReportTemplate` class ↔ `templates` table (subtask's table name kept; class avoids generic name). `ReportSectionTemplate` ↔ `section_templates`.
- Factories: 7 new, incl. `DailyReportFactory::submitted()` (formats CMX.WR.YYYYMM.NNNN), `needsRevision()`, `forAttendance()`; `AttendanceFactory::completed()`.
- Verification: migrate up 7/7; rollback step=7 + re-migrate clean; 11/11 ReportSchemaTest; full suite 60/60; Pint clean.
- Test-writing note: assert-then-throw inside one test must not continue querying after the expected unique violation — Postgres aborts the tx; restructured order-uniqueness test to try/catch with `fail()` first.

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `php artisan migrate --force` (sail) -- expected: 7 migrations clean
- `php artisan migrate:rollback --step=7` then re-migrate -- expected: FK-safe down/up
- `vendor/bin/pest tests/Feature/ReportSchemaTest.php` -- expected: green
- `vendor/bin/pint --dirty --format agent` -- expected: clean

**Manual checks:**
- FR-3/8/9/22a invariants enforced at DB level (partial unique indexes), not just app level.
