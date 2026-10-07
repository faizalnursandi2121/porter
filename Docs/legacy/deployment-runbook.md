# Deployment Runbook — MVP

## 1. Scope and Environments

Deployment uses GitLab CI/CD and Docker Compose (Sail) across three separate environments (ADR-030):

1. Local development.
2. Staging.
3. Production.

- Promotion branch: `faizaldev -> staging -> production`.
- Production deploy only from the `production` branch.
- Staging is mandatory before pilot and production release (pilot gate, section 8).
- Staging never uses raw production data.
- Secrets are separate per environment.

Each environment runs the same service set: `laravel.test` (app web), `worker` (`php artisan queue:work`), `scheduler` (`php artisan schedule:work`), `pgsql`, plus reverse proxy/TLS generik milik environment (HTTPS termination). Persistent volumes: `postgres_data`, `storage_data` (private disk `storage/app`). Tidak ada Redis (semua driver `database` — ADR-047), tidak ada service ClamAV/`clam_db` (ADR-044), dan tidak ada Dokploy/service Go — deployment tetap Compose.

## 2. Preconditions

```text
- Domain/DNS points to the environment reverse proxy per environment.
- HTTPS certificate active per environment.
- PostgreSQL has no public port in any environment.
- Persistent volumes provisioned and writable by correct service user.
- Off-host encrypted backup destination configured per environment.
- Secrets configured per environment (Compose env / GitLab CI variables); no secret shared between environments.
- First Super Admin bootstrap procedure approved (seeder/command terkontrol).
```

## 3. Required Secrets

Secrets are configured per environment (local/staging/production) and never shared:

```text
APP_KEY
APP_ENV / APP_URL / APP_DEBUG=false (production)
DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=private
```

`APP_KEY` wajib (rotasi dicatat dan diuji lewat prosedur operasional). Enkripsi at-rest ditangani infrastruktur (disk/backup encryption); tidak ada envelope encryption application-level pada MVP. Tidak ada konfigurasi Redis (`REDIS_*`) maupun ClamAV (`CLAMD_*`) — driver session/cache/queue/rate-limit/atomic-lock memakai `database` (ADR-047); attachment divalidasi sinkron tanpa malware scan (ADR-044).

Secrets are never committed, printed by CI, or embedded in image.

## 4. Deploy Procedure

- Image build dijalankan oleh pipeline GitLab CI (image sebagai verifikasi build); asset frontend di-build DALAM image build (`npm run build` / Vite — hasil build static ikut di dalam image, bukan step deploy terpisah).
- Staging deploy = Compose terhubung ke branch `faizaldev`. Auto-deploy OFF: user menjalankan `docker compose up -d` (atau trigger manual setara) setelah pipeline hijau.
- Production deploy = Compose hanya dari branch `production`, model manual yang sama (auto-deploy OFF).
- Migration berjalan sebagai service/command `migrate` terpisah (image sama dengan app, `php artisan migrate --force`, exit-on-success) SEBELUM rollout `laravel.test`/`worker`/`scheduler` baru; exit non-zero gagalkan deploy dan blokir rollout (`depends_on: service_completed_successfully`).
- Schema evolution follows expand-migrate-contract for zero-downtime: the new application version is backward-compatible with the old schema during the migration window; contract/cleanup steps land in a later release.

Staging deploy (manual trigger setelah pipeline hijau):

```text
1. Merge approved change into `faizaldev`.
2. GitLab pipeline runs Pint, Larastan, Pest, typecheck, production build (Vite), migration validation, image build.
3. Wait for the pipeline to be green.
4. Build image (termasuk `npm run build` asset Vite) dari branch `faizaldev` dan jalankan service `migrate` (`php artisan migrate --force`, exit-on-success) SEBELUM rollout app/worker/scheduler baru; migration failure aborts the rollout.
5. Deploy `laravel.test` (web), `worker` (queue:work), `scheduler` (schedule:work) ke staging.
6. Wait for PostgreSQL readiness checks dan health endpoint `/up` (Laravel health route) mengembalikan 200.
7. Run smoke test manual pada domain staging: login, check-in (selfie+GPS), submit report, clock-out gate, export (lihat test-strategy.md smoke-E2E).
8. Verify deployment logs, migration result, version, and audit-sensitive flow (activity_log).
9. Mark deployment result in release record.
```

Production deploy (only from branch `production`, after staging gate passes):

```text
1. Promote approved release from `staging` to `production` branch (merge request, review, pipeline green).
2. GitLab pipeline re-runs the full quality gate on the `production` branch.
3. Verify PostgreSQL and attachment-volume backup completed and restore evidence is current (ADR-031).
4. Build image (termasuk `npm run build` asset Vite) dari branch `production` dan jalankan service `migrate` (`php artisan migrate --force`, exit-on-success) SEBELUM rollout app/worker/scheduler baru; migration failure aborts the rollout (expand-migrate-contract; zero-downtime).
5. Deploy `laravel.test` (web), `worker`, `scheduler` ke production.
6. Wait for PostgreSQL readiness checks dan health endpoint `/up` mengembalikan 200.
7. Run smoke test pada domain production: login, check-in (selfie+GPS), submit report, clock-out gate, export (browser matrix sesuai §8: Android Chrome, iOS Safari, desktop Chrome/Edge/Firefox dua major terbaru).
8. Verify deployment logs, migration result, version, and audit-sensitive flow (activity_log).
9. Mark deployment result in release record.
```

## 5. Migration Rules

- Migrations run as a controlled job that is part of the deploy, never manually from a developer workstation: the `migrate` service/command (same image as app, `php artisan migrate --force`, exit-on-success) runs before the new app/worker rollout.
- Prefer expand → migrate data → deploy compatible app → contract cleanup (zero-downtime schema evolution).
- Production migration is explicit, reviewed, backed up, and forward-only unless approved rollback plan exists.
- Never run destructive migration without tested backup/restore and compatibility review.
- Migration Laravel adalah satu-satunya mekanisme perubahan skema production; `database/migrations` versioned di Git.

## 6. Rollback

```text
Application rollback:
- Redeploy previous compatible image from the environment branch.
- Do not roll back schema blindly.

Data/schema rollback:
- Use forward corrective migration when possible.
- Restore backup only through approved incident procedure.

Attachment rollback:
- Preserve storage volume (`storage_data`); do not delete volume during application rollback.
```

## 7. Backup and Restore

| Asset | Backup | Retention | Validation |
|---|---|---|---|
| PostgreSQL | Daily scheduled `pg_dump` off-host backup (scheduler container) | 30 hari | Monthly restore test on staging (ADR-031) |
| Storage (attachment) volume | Daily scheduled `rsync` snapshot `storage/app` off-host | 30 hari | File integrity + monthly restore test |
| Configuration | Document/export safely without plaintext secret | — | Rebuild drill |

Recovery objectives: RPO maksimum 24 jam, RTO maksimum 8 jam (ADR-031). Tidak ada backup Redis (tidak ada Redis di runtime — semua state di PostgreSQL; ADR-047).

Restore drill (bulanan, di staging): provision isolated environment → restore PostgreSQL (`pg_restore` via container postgres scratch) → restore attachment volume (`rsync`/tar) → configure environment secrets → deploy compatible build → run migration if approved → smoke test login/report/attachment → catat date, operator, duration, outcome. Urutan drill lengkap di `backup-restore-drill.md`.

## 8. Pilot and Production Gate

- Staging must be deployed and verified before pilot and before any production release.
- No ClamAV/malware scan: the attachment pipeline validates type/format/size/decode + SHA-256 synchronously in the request, and a file that passes validation becomes `AVAILABLE` directly (ADR-044; ADR-053); thumbnail/preview WebP dihasilkan queued job di worker.
- Production pilot/go-live requires the production go-live checklist in `release-checklist.md` to be complete.

- Setiap promotion menjalankan smoke-E2E (login → check-in selfie+GPS → report → submit → clock-out gate + failure state utama: gate report, attachment rejected, must_change_password) sebagai bagian staging gate; browser smoke matrix = dua major version terbaru Android Chrome, iOS Safari, dan desktop Chrome/Edge/Firefox (PRD:616); full regression E2E hanya per release (lihat `test-strategy.md` §6).

ClamAV provisioning is no longer part of the production gate (ADR-044): no `clamav` service, `clam_db` volume, `CLAMD_HOST`/`CLAMD_PORT` wiring, EICAR test, or fail-closed scanner drill.

Branch protection (HITL — GitLab UI, not config-as-code; do this once before first production deploy):

```text
[ ] Create the `production` branch from the promoted `staging` commit (currently the branch does not exist on the remote).
[ ] GitLab → Repository → Protected branches: protect `production` (and `staging`).
[ ] Allowed to merge: Maintainers only; allowed to push and merge: no one (MR-only) — direct pushes are rejected.
[ ] Code owner approval required for the paths that gate go-live when configured.
[ ] Pipeline must succeed: MRs targeting `production`/`staging` cannot merge while the pipeline is red.
[ ] Verify with a dry-run: attempt a direct push to `production` → must be rejected by GitLab.
```

Compose stack production dapat divalidasi secara lokal (env dummy) dengan `docker compose config` sebelum deploy nyata; real pipeline, deploy, dan branch protection dieksekusi oleh user (HITL).

## 9. Incident Actions

For suspected compromise: contain by disabling user/revoking sessions (session driver database) or isolating service; preserve logs/activity_log/metadata; do not overwrite evidence; follow `security.md` incident flow and the incident procedure in `operations-runbook.md`.
