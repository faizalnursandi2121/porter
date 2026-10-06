# BMAD Delivery Workflow

## 1. Purpose

Dokumen ini mengatur delivery BMAD untuk aplikasi full-stack Laravel 13 + Inertia v3 + React 19: satu developer menulis route, controller, FormRequest, Inertia page, dan test dalam satu story — tidak ada fase contract-first OpenAPI, tidak ada pengembangan frontend/backend paralel terpisah. Kontrak lintas layer (payload props, validasi, error) hidup di dalam codebase yang sama dan diverifikasi test, bukan dokumen terpisah.

## 2. Work Item Types

| Item | Tujuan | Output minimum |
|---|---|---|
| Epic | Capability bisnis besar | Scope, outcome, story map, dependency, release goal |
| Full-stack Story | Implementasi domain/use case + UI dalam satu codebase | Migration/Eloquent model, controller + FormRequest, Inertia page, unit/integration tests |
| Integration Story | Menghubungkan komponen yang berjalan terpisah | Upload/queue/scheduler wiring, worker job, notification, export stream |
| E2E Story | Membuktikan journey end-to-end | Happy path, failure/recovery, role/scope coverage |
| Hardening Story | Security/performance/reliability | Findings fixed, test/release evidence |

## 3. Lifecycle

```text
Draft
→ Ready for Build
→ In Progress (satu dev: migration → controller/FormRequest → page → test)
→ E2E Verified
→ Done
```

### Gates

| Transition | Mandatory gate |
|---|---|
| Draft → Ready for Build | Scope/acceptance criteria, data ownership, UX state, policy/RBAC, audit, error copy, test approach agreed |
| Ready for Build → In Progress | Migration dependency dan file ownership jelas; tidak ada story lain mengedit migration/controller yang sama bersamaan |
| Build → E2E Verified | Unit/integration test pass; journey user-visible dapat dijalankan pada aplikasi nyata (web + worker bila terlibat) |
| E2E Verified → Done | Required E2E/security/RBAC tests pass; docs updated; review complete |
| Done → Release promotion | Staging gate: capability diverifikasi di staging sebelum pilot/production promotion; smoke-E2E per promotion (happy path check-in→report→clock-out + 3 failure state utama: gate report, attachment rejected, double submit; Android Chrome, iOS Safari, desktop Chrome). Full regression E2E (semua journey + boundary + negatif) dijalankan per release (mingguan/manual), bukan per promotion |

## 4. Story Checklist

Each story must define:

```text
:- Business outcome and non-goals
:- Actor/role and object/site scope
:- UX states: loading, empty, validation, server error, retry, permission denied
:- Route + controller + FormRequest + validation rules + error copy
:- Authentication, session, rate limit requirement
:- Data entities, migration/query impact, source of truth
:- State transition/precondition/invariant
:- Audit action (activitylog) if applicable
:- Attachment/evidence rule if applicable
:- Unit, integration, and E2E acceptance test
```

## 5. Full-Stack Story Flow

Satu dev menulis vertikal slice dalam satu PR:

```text
Migration / Eloquent model
→ route (web.php / Wayfinder) + controller + FormRequest (validasi + policy)
→ Inertia page (React 19 + Tailwind 4 + shadcn/ui) + UX state
→ Pest test: unit (service/rule) + integration (HTTP + database) + komponen bila perlu
```

Layer yang dipisah hanya untuk akses lintas proses (worker `queue:work`, `schedule:work`) menjadi Integration Story: job class + dispatch dari service, diverifikasi dengan worker berjalan (atau `queue:work --once` / `queue:fake` + assert di integration test).

Tidak ada mock server/MSW: integration test Pest mengenai aplikasi nyata; page test cukup dengan prop rendering, bukan kontrak HTTP terpisah.

## 6. File Ownership and Coordination

| Artifact | Primary owner during story | Rule |
|---|---|---|
| PRD/ADR/architecture/security | Product/architecture owner | Change only through review/approved decision |
| Migration / Eloquent model | Story owner | Avoid concurrent conflicting migration edits |
| Route + controller + FormRequest | Story owner | Satu slice satu owner; bila dua story menyentuh controller sama, selesaikan/merge story pertama |
| Inertia page / React component | Story owner | Component shadcn/ui shared hanya diubah via story yang memilikinya |
| Job/queue/scheduler | Integration Story owner | Dispatch point diverifikasi bersama slice pemiliknya |
| E2E test | E2E Story owner | Test real integrated system |

If two stories need the same migration/controller, complete or merge the first story rather than resolve semantic conflicts later.

## 7. Example: Attendance Check-In

```text
EP-03 Attendance

ST-3.01 Full-stack — Check-in: migration attendance record, controller (route /attendance/check-in),
  FormRequest (selfie + GPS + validasi), selfie overlay page (FaceDetector opsional + fallback),
  Haversine service (jarak sebagai informasi), Pest integration test
ST-3.02 Full-stack — Unique constraint double submit + error copy + UX state
ST-3.03 Full-stack — Clock-out + gate report (error gate → arahan melengkapi report)
ST-3.09 E2E — Check-in sukses, double submit ditolak, gate report, WIB/WITA/WIT
```

Contoh Integration Story dari E4: ST-4.07 thumbnail queued job — validasi sinkron di ST-4.06 (slice full-stack), derivative dihitung worker terpisah, diverifikasi dengan worker berjalan.

## 8. Definition of Done

A story is Done only when:

```text
:- Scope and acceptance criteria implemented.
:- Relevant unit/integration/frontend tests pass.
:- Security and authorization requirements pass (policy/scope, audit titik kritis).
:- Critical mutation has audit behavior where required.
:- No temporary debug/bypass remains in production path.
:- Documentation and migration are updated where required.
:- Integration/E2E is complete for a user-visible capability.
:- Capability diverifikasi di staging bila masuk release yang dipromosikan (staging gate).
```
