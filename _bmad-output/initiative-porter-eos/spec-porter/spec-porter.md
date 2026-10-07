---
id: SPEC-porter
companions:
  - fr-catalog.md
  - stack.md
  - conventions.md
  - delivery.md
  - success-metrics.md
  - ../../../Docs/PRD/ui-design.md
sources:
  - Docs/PRD/prd-porter-portal-operasional-terpadu.md
  - Docs/PRD/tasks-task-list-baru.md
---

> **Canonical contract.** This SPEC and the files in `companions:` are the complete, preservation-validated contract for what to build, test, and validate. Source documents listed in frontmatter are for traceability — consult them only if you need narrative rationale or prose color this contract intentionally omits.

# PORTER v1 — Portal Operasional Terpadu

## Why

A mandate plus a pain. Comtronics fields one Engineer On Site (EOS) at each of 200+ Sekolah Rakyat schools across WIB–WIT, but Supervision has no visibility: attendance proof, device checks, and stock live in chats and spreadsheets with per-site formats that cannot be reconciled or audited. PORTER replaces that with one web app (PWA) joining four functions — attendance proof, structured Daily Reports, per-site inventory, and a supervision KPI dashboard — for four roles: EOS, Supervisi, HR, Administrator.

## Capabilities

- **CAP-1 — Accounts & assignments**
  - intent: Supervisi/Administrator create all accounts (no self-registration; EOS account requires school placement; temporary password with forced first-login change) and move/end EOS assignments with history.
  - success: feature tests prove role-scoped access, 15-min lockout after 5 consecutive failures, and the one-active-assignment-per-EOS / one-active-EOS-per-school invariants.
- **CAP-2 — Attendance proof**
  - intent: EOS checks in and out, each with one selfie + device GPS, server-watermarked, times recorded per school timezone with UTC reference.
  - success: feature tests for double check-in prevention, check-out before check-in rejection, check-out gated on Submitted report, second check-out rejection, same-local-day rule, and stored watermark file differing from the original.
- **CAP-3 — Attendance history**
  - intent: EOS sees own history; Supervisi/Administrator/HR see per-EOS/per-site history with period filter, local times, status, location, selfie access per FR-48.
  - success: RBAC test proves EOS isolation and HR denial of report/inventory photos.
- **CAP-4 — Daily Report lifecycle**
  - intent: EOS fills a 5-section administrator-templated report with conditional evidence rules, drafts server-side, submits to a permanent unique number, revises same-day, and gets notified on reopen.
  - success: tests for number allocation/format `CMX.WR.YYYYMM.SEQUENCE`, section completeness validation, revision counting, reopen-to-Needs-Revision with in-app notification.
- **CAP-5 — Inventory & consumables**
  - intent: EOS logs received goods (photo mandatory), manages item statuses with reasons, and records material stock movements that may never go negative; inventory stays with the school.
  - success: tests prove negative-stock rejection stating the balance, ownership surviving EOS transfer, and audited status changes.
- **CAP-6 — KPI dashboard & export**
  - intent: Supervisi/Administrator filter combinable period/school/EOS across six KPI groups and export server-side Excel/PDF honoring active filters.
  - success: demonstration produces an export matching the active filter; empty filter shows "No data for this filter."
- **CAP-7 — Audit & photo access control**
  - intent: every critical action and every selfie/evidence photo access lands in an append-only audit log; photos stream only through an authorized endpoint.
  - success: tests prove unauthorized roles denied at the photo endpoint, each access writes an audit row, and app users cannot modify audit rows.
- **CAP-8 — Master site data**
  - intent: Administrator manages site name, address, coordinates, timezone, providers; deactivation never deletes history.
  - success: test proves a deactivated site leaves active flows while its records stay viewable.
- **CAP-9 — PWA field experience**
  - intent: installable PWA, mobile-first, attendance explicitly disabled offline, upload progress with double-submit prevention and retry preserving captured data.
  - success: offline state disables attendance with the FR-6 message; a failed upload leaves captured selfie/GPS/report data intact for Try Again.
- **CAP-10 — Local-time correctness**
  - intent: every timestamp displays in the site's timezone with tz label across attendance, reports, dashboard, exports.
  - success: unit tests of the UTC↔local helper plus UI/export render site-local time with WIB/WITA/WIT label.

## Constraints

- Stack locked (see `stack.md`): Laravel 13 + Inertia v3 + React 19 + shadcn/ui, session-cookie auth, server-side routing only — no separate REST API; RBAC 4 roles enforced server-side per request; Administrator ⊇ Supervisi.
- All timestamps stored UTC; site master data drives local conversion; every display carries a tz label (FR-35).
- Photos live in private storage; every view passes a role-checking, audit-writing endpoint (FR-48/49) — no directly reachable URL (see `stack.md`).
- Audit log append-only, auto-written on every critical action, never editable by app users.
- Selfie watermark stamped server-side at receive (site, local time, lat/lng ± accuracy); bundle internal TTF font; original unwatermarked file need not be kept (FR-5b).
- Report numbers `CMX.WR.YYYYMM.SEQUENCE` issued only on successful submit, globally unique, never reused or reset; drafts unnumbered; revision/reopen never renumber (FR-22a).
- Template edits affect new reports only; each report snapshots its template; dashboards compare only stable core fields (FR-25).
- Photo rules FR-20a: JPG/JPEG/PNG/WebP/HEIC/HEIF, PDF never for selfie, ≤10 MB/file, ≤5/section, ≤10/report, exactly 1 selfie per check event; rejects at upload with explicit reason.
- Validation on client and server; server is source of truth and rejects incomplete submits (FR-20).
- Material stock never negative; rejections state the current balance (FR-29).
- Critical rules carry automated tests: double check-in, report gate, double check-out, number allocation, photo access rights.
- Inventory belongs to the school, not the EOS (FR-31).
- UI obeys the locked visual baseline `../../../Docs/PRD/ui-design.md`: launcher for all roles, no sidebar, header module nav (mobile Sheet), English copy via `__()`, fixed badge variants, verbatim FR empty states.
- Every list has informative empty/error states; dashboard < 3 s on common filters; schema/indexes sized for 200+ sites.
- Deploy GitHub→Dokploy, no staging; daily automated backup with restore tested before go-live.

## Non-goals

- No lateness/payroll computation; no integration with vendor attendance systems (FR-13).
- No offline mode or sync for attendance/reports (FR-6 requires connectivity).
- No push notifications; reopen notice is in-app polling only.
- No procurement/purchasing workflows; only received-goods recording.
- No automatic device monitoring; EOS enters CPU/RAM/uptime manually.
- No native iOS/Android apps — PWA only.
- No vendor contract/SLA management; no internal chat.
- No minimum-stock thresholds or alerts — dashboard lowest-stock recap only (FR-30).
- No absence time-window enforcement — attendance is evidence only.
- No photo retention/purge policy this version (deferred until company policy exists).
- No warehouse data import (deferred until source format is known).
- No multi-EOS-per-site; no staging environment.

## Success signal

An EOS at any site checks in with a watermarked selfie, files a complete Daily Report, records an arrived item, and clocks out — while a Supervisi in the office filters the dashboard to that school, sees the report and attendance, and exports the recap to Excel/PDF in under five minutes, with every step and photo view in the audit log.

## Assumptions

- Single national deployment covering all 200+ sites; site timezone is authoritative for local dates.
- No staging environment is acceptable; releases ship GitHub→Dokploy.

## Open Questions

None — all fifteen product questions were confirmed by the product owner and are recorded in `fr-catalog.md` §Decisions.
