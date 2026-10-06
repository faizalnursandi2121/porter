# Release Checklist

## 1. Scope

Checklist ini adalah gate untuk promotion staging dan production. Semua item yang relevan harus memiliki evidence/reference; exception harus disetujui dan dicatat.

## 2. Product and Contract

```text
[ ] PRD/UX/ERD/ADR affected by release reviewed (termasuk kontrak halaman/route Inertia di api-contract.md).
[ ] Story acceptance criteria and Definition of Done complete.
[ ] No unapproved scope/contract/security exception.
[ ] Daily Report numbering, timezone, attendance (bukti kehadiran), dan inventory rule remain compliant.
```

## 3. Quality and Test

```text
[ ] Pint (formatter) pass.
[ ] Larastan (static analysis) pass.
[ ] Pest unit/feature test pass.
[ ] Frontend typecheck (npm types:check), lint, dan production build (Vite) pass.
[ ] Migration validation dan DB integration test pass di CI.
[ ] Smoke-E2E per promotion passes against aplikasi nyata (happy path: login → check-in selfie+GPS mock → isi report → submit → clock-out gate → export; plus failure state utama: report gate, attachment rejected, must_change_password gate; browser: Android Chrome, iOS Safari, dan desktop Chrome, Edge, Firefox — dua major version terbaru, PRD:616).
[ ] Full regression E2E per release passes (semua journey + boundary + negatif; jadwal mingguan/manual).
[ ] WIB/WITA/WIT cases tested for changed time-sensitive behavior.
[ ] Failure/retry/empty/access-denied states tested.
```

## 4. Security

```text
[ ] HTTPS and secure cookie active (Secure/HttpOnly/SameSite=Lax).
[ ] CSRF/rate limit for affected mutation routes tested (session/CSRF Laravel, driver database).
[ ] RBAC (spatie permission + Policy) dan object/site authorization tests pass.
[ ] No secret/token/password/PII leak in source/log/artifact (activitylog properties disaring).
[ ] Upload validation sinkron (magic byte/size/decode + SHA-256), private access, dan attachment authorization verified.
[ ] Audit behavior (activity_log) verified for critical mutation.
[ ] Dependency/image vulnerability findings triaged.
[ ] PostgreSQL remains private network only (tidak ada Redis).
```

## 5. Data and Operations

```text
[ ] Promotion branch benar: `faizaldev -> staging -> production`; production build hanya dari branch `production`.
[ ] Staging deploy sukses dan staging gate lolos sebelum production promotion (ADR-030).
[ ] Migration reviewed; service/command `migrate` terpisah (image sama dengan app, `php artisan migrate --force`, exit-on-success) berjalan SEBELUM rollout laravel.test/worker/scheduler baru (deploy manual HITL setelah pipeline hijau, auto-deploy OFF); migration failure membatalkan rollout; expand-migrate-contract untuk zero-downtime.
[ ] PostgreSQL dan attachment volume backup verified (pg_dump + rsync harian terbaru sukses, restore evidence current, retention 30 hari — ADR-031).
[ ] Rollback/forward plan confirmed; previous compatible image tersedia untuk rollback.
[ ] Persistent volumes (`postgres_data`, `storage_data`) are not deleted/recreated by deployment.
[ ] Health (`/up` Laravel) dan smoke test pass (login, check-in, report submit, clock-out gate, export).
[ ] Log redaction and error response do not expose sensitive data.
[ ] Release version, deploy time, owner, and approval recorded.
```

## 6. Pilot and Go-Live Gate

```text
[ ] Staging wajib sebelum pilot dan production release.
[ ] Tidak ada ClamAV/malware scan (ADR-044): attachment pipeline hanya validasi sinkron tipe/ukuran/decode + SHA-256 (ADR-053); file valid langsung `AVAILABLE`.
[ ] Backup dan restore evidence current (RPO 24 jam / RTO 8 jam).
[ ] Production go-live checklist lengkap: promotion via MR ke `production` (branch protected — lihat checklist HITL branch protection di runbook §8), pipeline `production` hijau, deploy manual Compose produksi (dari branch `production` saja), backup verified, migration controlled (migrate service sebelum rollout), smoke test pass, rollback plan tersedia.
```

## 7. UAT and Sign-off

```text
[ ] UAT scenario/result/evidence recorded.
[ ] Product/operations owner accepts functional outcome.
[ ] Security/technical owner accepts relevant risk gate.
[ ] Known limitation and deferred item documented.
[ ] Production promotion approved by designated owner.
```
