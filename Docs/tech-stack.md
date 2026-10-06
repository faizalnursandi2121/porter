# Tech Stack — PORTER (Portal Operasional Terpadu Sekolah Rakyat)

**Status:** Baseline MVP
**Dokumen:** Technical Architecture and Technology Stack
**Platform:** Dashboard web responsif dan Progressive Web App (PWA)
**Repository:** GitLab monorepo

## 1. Tujuan dan Prinsip

Dokumen ini menetapkan teknologi dan guardrail implementasi untuk PORTER (Portal Operasional Terpadu Sekolah Rakyat). Keputusan ini mendukung bukti kehadiran berbasis selfie + GPS (server timestamp UTC), Daily Report, inventaris per site, RBAC, audit log, lampiran file, dan export pada tiga environment (local, staging, production) yang mudah dioperasikan.

Prinsip utama:

- Mulai sederhana sebagai modular monolith Laravel; jangan memecah ke microservices pada MVP.
- Laravel server adalah authority tunggal untuk business rule, authorization, server time, transaksi, sequence nomor laporan, dan audit.
- PostgreSQL adalah system of record / source of truth untuk data bisnis.
- Frontend (Inertia page) tidak boleh menjadi authority untuk RBAC, waktu absensi, jarak/presisi GPS, nomor dokumen, maupun perubahan data kritis.
- Tanpa Redis: session, cache, queue, rate limit, dan atomic lock memakai driver `database`. Skala MVP: 200 site aktif, ~1–2 EOS per site.
- Tidak ada REST API layer terpisah dan tidak ada OpenAPI; seluruh interaksi melalui halaman/route Inertia + form request (kontrak ada di `api-contract.md`).
- Aplikasi berjalan pada tiga environment terpisah—local development, staging, dan production—melalui Docker Compose (Sail); staging wajib sebelum pilot dan production release.

## 2. Arsitektur Baseline

### Pola aplikasi

```text
Architecture  : Modular monolith (single Laravel app)
Interface     : Inertia v3 + React 19 (server-side routing, SSR-ready)
Repository    : GitLab monorepo
Deployment    : Docker Compose via Sail; local + staging + production
```

Modular monolith berarti aplikasi dideploy sebagai satu unit utama, namun source code dipisahkan berdasarkan domain dan layer. Domain awal meliputi Identity/RBAC, Site & EOS Assignment, Attendance, Daily Report, Checklist, Inventory, Attachment, Audit, dan Reporting/Export.

### Batas authority

```text
Inertia page (React 19)
  - UI state, form state, pengalaman pengguna, komponen shadcn/ui,
    peta Leaflet, dan pengambilan foto/lokasi perangkat.
  - Tidak menetapkan keputusan bisnis final.

Laravel server
  - Authority untuk autentikasi, session, RBAC, scope site,
    server time (UTC), jarak Haversine (sebagai informasi),
    sequence report number, workflow attendance/report,
    transaction, dan audit.

PostgreSQL
  - Source of truth untuk data bisnis, histori, audit,
    queue/scheduler (database driver), session, serta constraint.
```

Tidak ada komponen runtime state eksternal: seluruh state yang sebelumnya dipegang Redis (session, queue, lock, rate limit, cache) berada di PostgreSQL melalui driver `database`.

### Topologi deployment per environment

```text
Internet
   │
   ▼
Reverse proxy + HTTPS
   │
   └── app    : Laravel 13 + Inertia (PHP-FPM) + static asset hasil build Vite
          │
          ├── postgres : PostgreSQL, internal Docker network
          └── storage  : persistent local volume (private disk)

queue-worker : php artisan queue:work (container/service terpisah)
   ├── PostgreSQL
   └── storage persistent volume

scheduler    : php artisan schedule:work (container/service terpisah)
   └── PostgreSQL
```

PostgreSQL hanya tersedia melalui Docker internal network. Port database tidak diekspos ke internet.

Topologi di atas berlaku sama untuk staging dan production. Local development memakai Docker Compose/Sail dengan data seed/dummy. Detail promotion flow ada di bagian 11 (Deployment).

## 3. Technology Stack

| Area | Teknologi | Status | Keputusan dan penggunaan |
|---|---|---|---|
| Bahasa backend | PHP 8.3+ (8.4 direkomendasikan) | Final | Bahasa tunggal full-stack; seluruh business rule, validasi, dan worker |
| Framework backend | Laravel 13 | Final | Modular monolith; routing, middleware, Eloquent, queue, scheduler, validation, dan event bawaan framework |
| Frontend | Inertia v3 + React 19 | Final | Halaman React dirender lewat server-side routing Laravel; satu aplikasi, tanpa REST API layer terpisah |
| Bahasa frontend | TypeScript | Final | Type safety pada komponen halaman, props Inertia, dan form helper |
| Build tool | Vite | Final | Dev server (HMR) dan build asset production |
| Routing frontend | Wayfinder | Final | Typed routes/method dari route Laravel ke TypeScript; URL tidak di-hardcode |
| UI framework | Tailwind CSS 4 | Final | Styling seluruh halaman backoffice dan EOS |
| UI component | shadcn/ui | Final | Baseline UI; komponen dimiliki repo; capture selfie, geolocation, peta Leaflet, dan checklist renderer tetap custom |
| Map library | Leaflet + react-leaflet | To install | Tampilkan/ubah koordinat site, review titik check-in vs site, dan radius sebagai info visual; bukan authority |
| PWA | vite-plugin-pwa | To install | App shell + static assets installable; tanpa offline draft |
| Database | PostgreSQL | Final | System of record transactional |
| Data layer | Eloquent ORM + migrations | Final | Laravel way: model, relationship, migration versioned, UUID PK untuk record operasional; transaction via DB façade |
| Queue/job | Laravel Queue (database driver) | Final | Job async (thumbnail, notifikasi, agregasi), retry, dan failed job table |
| Scheduler | Laravel Scheduler (schedule:work) | Final | Task terjadwal; tanpa cron OS tambahan |
| RBAC | spatie/laravel-permission + Policies | To install | 5 role fixed + permission; Laravel Policy per model; ScopeService untuk scope site |
| Audit | spatie/laravel-activitylog | To install | Tabel activity_log untuk audit event aplikasi di titik kritis eksplisit |
| Authentication | Fortify (starter kit) | Ter-install | Login, reset, lockout, dan profil bawaan; konfigurasi sesuai bagian 8 |
| Passkeys | laravel/passkeys | Ter-install (disabled) | Out-of-scope MVP: package ada dari starter kit tapi fitur passkey tidak diaktifkan (nullable/disabled-optional); autentikasi MVP = email/employee code + password (lihat security.md §5.1) |
| Password hashing | Argon2id (bcrypt default Laravel di-override) | Final | Hash password server-side dengan salt acak unik |
| Session | Secure HttpOnly cookie + database driver | Final | Session state di tabel sessions PostgreSQL |
| Rate limit / lock | Laravel rate limiter + cache lock (database driver) | Final | Tanpa Redis; atomic lock via cache lock database |
| File storage | Local disk private (storage/app) | Final MVP | Selfie, foto aset, screenshot AP, dan lampiran report; download via controller terkontrol |
| Future file storage | S3-compatible disk | Future | Migrasi bila volume/HA berkembang; cukup ganti disk Laravel |
| Image processing | Intervention Image + Imagick/libheif | To install | Thumbnail/preview WebP (2048px/480px) via queued job; dukungan HEIC/HEIF |
| Export Excel | maatwebsite/excel | To install | Xlsx styled (header bold, border, auto-width, judul+periode) |
| Export PDF | barryvdh/laravel-dompdf | To install | PDF formal header instansi, siap cetak |
| Export CSV | Streamed response Laravel | Final | Data mentah via streamed response sinkron |
| Container | Docker (via Sail) | Final | Packaging service yang reproducible |
| Deployment | Docker Compose via Sail | Final | Tiga environment terpisah: local, staging, production |
| Reverse proxy/TLS | Reverse proxy environment (HTTPS termination) | Final | HTTPS dan routing publik |
| Source control | GitLab | Final | Monorepo, merge request, CI/CD |
| CI/CD | GitLab CI/CD | Final | Pint, Larastan, Pest, build, check migration, image build, deploy |
| Logging | Structured log ke stdout | Final | Log aplikasi dikumpulkan oleh runtime/container platform |
| Health check | Endpoint health sederhana (Laravel `/up`) | Final | Liveness/readiness aplikasi + dependency |
| Secret management | Environment variable (Compose/GitLab CI variables) | Final | Secret tidak masuk repository maupun image |

Catatan status: **Ter-install** = sudah ada di repo dari React starter kit; **To install** = package baru yang ditambahkan saat implementasi (lihat bagian 16).

## 4. Frontend Runtime dan PWA

### Inertia v3 + React 19 + TypeScript + Vite

Frontend bukan SPA terpisah: halaman React 19 hidup di dalam aplikasi Laravel melalui Inertia v3. Routing ditetapkan di Laravel (server-side routing); navigasi client-side tetap terasa SPA tanpa reload penuh. Route dikenali TypeScript via Wayfinder.

Frontend bertanggung jawab untuk:

- Menampilkan dashboard dan form sesuai role (menyembunyikan navigasi yang tidak relevan).
- Mengelola input form, upload progress, serta feedback kegagalan validasi server.
- Meminta lokasi perangkat dan izin kamera untuk selfie check-in/clock-out, lalu mengirim evidence ke server.
- Overlay lingkaran panduan selfie; deteksi wajah opsional via browser FaceDetector API bila tersedia (tombol jepret aktif setelah kamera stabil); fallback overlay panduan saja.
- Menampilkan waktu lokal site (label timezone IANA) dengan timestamp canonical UTC dari server.
- Menampilkan peta Leaflet untuk koordinat site dan titik check-in (info visual).

Frontend tidak bertanggung jawab untuk:

- Menentukan waktu absensi resmi (server timestamp UTC).
- Menolak atau menyetujui jarak GPS ke site (jarak dihitung Haversine di PHP dan disimpan sebagai informasi; tidak ada penolakan radius).
- Menetapkan hak akses sebenarnya.
- Mengalokasikan nomor Daily Report (`CMX.WR.YYYYMM.SEQUENCE` dialokasikan atomik di server).
- Menentukan validitas clock-out (gate Daily Report `SUBMITTED` + evidence `AVAILABLE` dievaluasi server).

### PWA

PWA dipakai agar aplikasi web dapat dipasang di home screen Android/iOS dan dibuka menyerupai aplikasi. PWA bukan aplikasi native.

Baseline PWA (vite-plugin-pwa):

- Web app manifest dan mode display standalone.
- HTTPS wajib.
- Pre-cache app shell + static assets saja untuk loading lebih cepat.
- Tidak ada offline draft: seluruh data (absensi, report) hanya valid setelah diterima dan divalidasi server. Offline draft dan offline attendance dihapus dari scope.
- UI harus membedakan tegas draft tersimpan di server, submitted, dan gagal dikirim.

### Browser/device matrix

Dukungan minimum (ADR-021):

- Dua major version terbaru Android Chrome.
- Dua major version terbaru iOS Safari.
- Dua major version terbaru desktop Chrome, Edge, dan Firefox.

Prioritas eksekusi test E2E (tidak mengubah dukungan browser ADR-021): smoke-E2E per promotion berjalan pada Android Chrome, iOS Safari, dan desktop Chrome; Firefox/Edge best-effort; full regression E2E per release mencakup seluruh matrix di atas.

HEIC/HEIF diterima sebagai image attachment; original disimpan private dan immutable; thumbnail/preview WebP dibuat oleh queued job via Intervention Image (Imagick + libheif). Bila HEIC gagal decode saat validasi sinkron, file ditolak dengan pesan aman.

## 5. Backend: Laravel 13

Laravel adalah satu-satunya runtime aplikasi: web (HTTP) dan console command (worker, scheduler, migrate). Pola minimum per request/job:

```text
HTTP request / console command
→ Middleware (auth, session, role/scope)
→ Controller tipis
→ FormRequest (validasi input) / typed input
→ Service/Eloquent model (business rule, transaction)
→ Response: Inertia page props / redirect / streamed download
```

Contoh business rule yang wajib berada di service/model, bukan di controller atau frontend:

- Satu record attendance per EOS + site + tanggal lokal (unique constraint); check-in dan clock-out harus tanggal lokal yang sama; maksimum 1 selfie check-in + 1 selfie clock-out.
- Clock-out hanya valid setelah Daily Report tanggal tersebut `SUBMITTED` dan required evidence `AVAILABLE` (arahkan EOS melengkapi report).
- Jarak selfie ke site dihitung Haversine di PHP dan disimpan sebagai informasi (bukan gerbang; tanpa penolakan radius).
- Allocation nomor `CMX.WR.YYYYMM.SEQUENCE` secara atomik via PostgreSQL SEQUENCE hanya saat submit sukses.
- Validasi eksplisit per rule checklist (FormRequest/service), bukan interpreter JSON generik; rules v1 sesuai FR-10.
- Mutasi stok dengan row lock, saldo tidak negatif, histori, reversal, dan audit.
- RBAC, role, serta scope data pengguna (EOS: assignment aktif; non-EOS: semua site aktif).

Validasi request memakai FormRequest; error dikembalikan ke halaman Inertia sesuai kontrak di `api-contract.md` (validasi, error handling, redirect).

## 6. Data Layer

### PostgreSQL + Eloquent

PostgreSQL adalah system of record untuk seluruh data yang harus konsisten, teraudit, dan dapat dipulihkan, termasuk:

- User, role/permission, assignment, site (nama, kode, alamat, lat/lng/radius-info, timezone IANA, status), dan master data.
- Attendance record (bukti kehadiran) dan evidence metadata.
- Daily Report, checklist version + snapshot JSONB, jawaban, serta nomor laporan.
- Aset, catalog, stok, mutasi inventaris, dan findings.
- Attachment metadata.
- Activity log (audit), notifications, jobs, failed_jobs, sessions, cache, rate limit — semuanya database driver.

Aturan:

- Gunakan DB transaction untuk workflow lintas tabel dan perubahan kritis.
- Constraint, unique index, check, dan PostgreSQL SEQUENCE dijadikan lapisan integritas tambahan selain validasi service.
- UUID sebagai primary key untuk record operasional.
- Timestamp canonical memakai UTC (`timestamp with time zone`); tanggal kerja serta timezone site disimpan sebagai snapshot pada attendance/report untuk penampilan lokal.
- Migration Laravel adalah satu-satunya mekanisme perubahan skema production.
- Query berat/lintas domain dapat memakai query builder/SQL eksplisit; Eloquent tetap core data layer — ini Laravel way, tanpa layer alternative.
- Tidak menyimpan binary selfie atau lampiran dalam tabel PostgreSQL.

## 7. Queue dan Scheduler (tanpa Redis, tanpa outbox)

Background job memakai Laravel Queue dengan driver `database` (tabel `jobs` + `failed_jobs` di PostgreSQL). Scheduler memakai `php artisan schedule:work` (tidak ada cron OS eksternal di container).

Tanggung jawab job terjadwal/queued:

- Thumbnail/preview WebP attachment (Intervention Image + Imagick/libheif).
- Notifikasi in-app (tabel notifications, database channel) — report reopened, attachment rejected.
- Pembersihan file temporary dan cleanup sesuai retention policy.

Aturan:

- Worker dijalankan sebagai container/service terpisah `php artisan queue:work` (dipisah dari web service); scheduler sebagai service terpisah `php artisan schedule:work`.
- Job wajib idempotent dan retryable; failed job tercatat di `failed_jobs` untuk investigasi dan retry manual.
- Tidak ada transactional outbox: job dipanggil setelah commit (`dispatch:afterCommit`); pekerjaan yang tidak boleh hilang direkam via status record domain + activity log, bukan tabel outbox.
- Export Excel/PDF/CSV tidak dieksekusi worker: export berjalan sinkron (streamed) pada controller; audit event export ditulis sebelum stream dimulai.

## 8. Identity, Session, dan RBAC

### Autentikasi (Fortify bawaan starter kit)

MVP menggunakan akun lokal dalam PostgreSQL; SSO/external identity provider tidak digunakan pada fase ini.

Identitas login: email atau employee code + password. Reset password oleh Super Admin; tanpa pengiriman reset token via email pada MVP. Passkeys (laravel/passkeys, ter-install) out-of-scope MVP — package tetap ada sebagai dependency starter kit namun fitur dinonaktifkan (disabled-optional, nullable); tidak dipakai pada alur autentikasi MVP.

Konfigurasi keamanan (final):

- Password 12–128 karakter, hash Argon2id.
- Cookie session: `Secure`, `HttpOnly`, `SameSite=Lax`.
- Idle timeout 30 menit + absolute expiration 8 jam.
- Lockout login 5 kegagalan / 15 menit.
- Flag `must_change_password` + middleware paksa ganti password.
- Session diputar setelah login dan password change.
- Saat akun dinonaktiskan atau password direset, session aktif user direvoke.
- Session state memakai driver `database` (tabel `sessions`); PostgreSQL tetap source of truth lifecycle session.
- Rate limit login memakai Laravel rate limiter (database driver), per IP dan per identifier.
- Error login bersifat generik; plaintext password tidak masuk log atau audit.

### RBAC

RBAC ditegakkan di server (spatie/laravel-permission + Laravel Policies) untuk lima role fixed:

```text
SUPER_ADMIN
MANAGER
SUPERVISOR
HR
EOS
```

Single role per user. Authorization policy per model + ScopeService (EOS: assignment aktif; non-EOS: semua site aktif) mengikuti `prd.md` dan matriks RBAC produk. Frontend boleh menyembunyikan navigation yang tidak relevan, namun setiap route/controller wajib memeriksa session, user aktif, permission, role, serta scope di server (middleware + Policy).

Audit event kritis (login, sensitive access, master change, export, transisi lifecycle report, perubahan status aset, perubahan koordinat/timezone site) dicatat eksplisit via spatie/laravel-activitylog ke tabel `activity_log`.

## 9. Attachment dan File Storage

### MVP local persistent storage

File biner disimpan di local disk **private** (`storage/app`, di luar `public/`) pada persistent volume. PostgreSQL hanya menyimpan metadata file dan `storage_key`.

Jenis file utama: selfie check-in dan clock-out, foto pendaftaran aset (wajib saat registrasi), foto aset rusak/hilang, screenshot AP, screenshot log anomali, dan dokumen pendukung yang diizinkan.

### Aturan file storage

- Persistent volume terpisah untuk upload data; jangan menyimpan file dalam filesystem container tanpa volume.
- Backend membuat storage key UUID/acak; nama file pengguna tidak dipakai sebagai path otoritatif.
- Allowlist format: JPG/PNG/WebP/HEIC/PDF; maksimum 10 MB; limit per context tetap: report 10, section 5, item 5, selfie 1+1, mutation 5.
- File tidak dipublikasikan sebagai directory statis terbuka.
- Download/preview melalui controller terproteksi yang melakukan authorization server-side + audit event.
- Metadata mencatat uploader, entity relasi, tipe attachment, ukuran, content type, hash SHA-256, dan timestamp upload.
- Retensi dan backup attachment mengikuti kebijakan perusahaan karena selfie dan GPS merupakan data pribadi; Manager tidak melihat raw selfie/GPS presisi (privacy visibility di-enforce di export dan UI).

### Abstraksi disk

Storage memakai Laravel Storage (disk abstraction). Disk pertama `local` (private). Disk berikutnya dapat S3-compatible tanpa mengubah use case attendance, report, atau inventory.

### Lifecycle attachment — validasi sinkron

Status lifecycle attachment:

```text
AVAILABLE | REJECTED
```

Pipeline (validasi sinkron di request, tanpa quarantine async penuh, tanpa ClamAV — ADR-044):

```text
Client preflight size/format
→ auth/CSRF/site-context authorization
→ server-generated storage key, file disimpan private
→ validasi sinkron: magic byte, size, decode-safe, hash SHA-256
→ AVAILABLE bila lolos; REJECTED bila gagal
→ queued job: thumbnail/preview WebP (2048px/480px) via Intervention Image + Imagick/libheif
```

Aturan:

- Validasi berat (decode-safe, magic byte, SHA-256) berjalan dalam request upload; file valid langsung `AVAILABLE`.
- Thumbnail dibuat async sebagai enhancement, bukan gerbang; kegagalan thumbnail tidak mengubah status file.
- Original file immutable/private; PDF original tidak direcompress.
- CI/test memakai fixture JPEG, PNG, WebP, HEIC, HEIF, PDF valid, file rusak, MIME/signature mismatch, serta oversized file.

## 10. Background Worker dan Scheduler

Worker dijalankan sebagai service/container terpisah dari web, image/source sama, command `php artisan queue:work`; scheduler `php artisan schedule:work`. Worker tidak memiliki endpoint publik; mengakses PostgreSQL dan storage via network/volume internal.

Tanggung jawab worker/scheduler dirinci di bagian 7 (Queue dan Scheduler). Export tetap sinkron di web service (streamed response; audit sebelum stream).

## 11. Deployment

### Tiga environment

Deployment berjalan pada tiga environment terpisah, masing-masing menjalankan komponen yang sama melalui Docker Compose (Sail):

```text
app          (Laravel + PHP-FPM + Inertia; asset Vite di-build saat image build)
queue-worker (php artisan queue:work)
scheduler    (php artisan schedule:work)
migrate      (php artisan migrate --force; exit-on-success; berjalan sebelum rollout baru)
postgres
reverse proxy/TLS
```

- **Local development** — Sail/Compose di workstation developer; data dummy/seed.
- **Staging** — topologi identik production; wajib sebelum pilot dan production release; bukan tempat memakai raw production data (data sintetis/anonimisasi terkontrol).
- **Production** — melayani user nyata.

Promotion branch:

```text
faizaldev -> staging -> production
```

Aturan:

- Production deploy hanya dari branch `production`; deploy manual (auto-deploy OFF) setelah pipeline GitLab CI hijau; staging dari branch `faizaldev`.
- Branch `staging`/`production` diproteksi di GitLab (merge request + pipeline sukses wajib — checklist HITL).
- Migration dijalankan sebagai service/command `migrate` terpisah SEBELUM rollout app/worker baru; schema evolution memakai expand-migrate-contract untuk zero-downtime.
- Secret terpisah per environment; tidak ada berbagi kredensial lintas environment.
- Service tetap dipisahkan (app / queue-worker / scheduler) agar dapat diskalakan bertahap.
- Environment per server (staging dan production masing-masing tunggal) memiliki single point of failure per environment; lihat bagian 14 (Keterbatasan MVP).
- Monitoring: health endpoint sederhana (`/up`) + structured log stdout; backup: `pg_dump` harian + rsync attachment terjadwal + restore drill berkala.

### Persistent volumes

| Volume | Mount target | Isi | Prioritas backup |
|---|---|---|---|
| `postgres_data` | `/var/lib/postgresql/data` | Database utama (data bisnis + session + queue) | Kritis |
| `storage_data` | `storage/app` (private disk) | Selfie dan semua lampiran | Kritis |

### Docker Compose guardrail

- Image memakai versi yang dipin; tag `latest` dilarang untuk production.
- `restart: unless-stopped` untuk service kritis.
- Healthcheck untuk PostgreSQL, app (`/up`), dan worker/scheduler (process alive).
- App/worker menunggu readiness dependency, bukan sekadar urutan start container.
- PostgreSQL tidak memiliki port publik.
- Semua secret diberikan sebagai environment/secret runtime, bukan hardcode di Compose atau repository.
- Volume tidak dihapus dalam prosedur redeploy rutin.

## 12. GitLab dan CI/CD

### Repository

Repository memakai monorepo GitLab agar aplikasi, database contract, dokumentasi, dan deployment versioned bersama.

```text
cmx-sekolah-rakyat/          # single Laravel app (React starter kit base)
├── app/
│   ├── Http/Controllers/    # controller tipis + FormRequest
│   ├── Models/
│   ├── Services/            # business rule & scope
│   ├── Policies/
│   └── Jobs/                # queued job (thumbnail, notifikasi, agregasi)
├── database/
│   ├── migrations/          # Laravel migrations
│   └── seeders/             # development/test seed
├── resources/
│   ├── js/                  # React 19 + TypeScript pages/komponen (shadcn/ui)
│   └── views/
├── routes/                  # route web (Inertia) + console
├── storage/app/             # private disk (volume)
├── docker-compose.yml       # Sail
├── .gitlab-ci.yml
├── Docs/                    # prd.md, erd.md, tech-stack.md, adr/
└── ...
```

### Branching baseline

```text
faizaldev  → development/integration
staging    → UAT / pre-production
production → production release
```

Perubahan menuju branch `staging` dan `production` dilakukan melalui merge request, review, serta pipeline yang sukses. Branch diproteksi di GitLab (maintainer merge, tanpa direct push; checklist HITL).

### Minimum pipeline

| Area | Validasi minimum |
|---|---|
| Backend | Pint (format), Larastan (static analysis), Pest unit/feature test |
| Frontend | Dependency install, typecheck, lint, unit test bila tersedia, production build (Vite) |
| Database | `php artisan migrate` validation di database CI, migration check, integration test |
| Container | Docker build, vulnerability/dependency scan sesuai capability GitLab |
| Security/attachment | Dependency/secret/image scan; attachment fixture suite (JPEG, PNG, WebP, HEIC, HEIF, PDF valid, file rusak, MIME/signature mismatch, oversized) |
| E2E | Smoke-E2E per promotion staging/production (happy path check-in → report → clock-out + failure state utama); full regression E2E journey suite per release |
| Deployment | Pipeline = gate kualitas (build image sebagai verifikasi build); deploy staging/produksi manual HITL setelah pipeline hijau; service `migrate` berjalan sebelum rollout app/worker; expand-migrate-contract; smoke manual via domain staging/produksi |

## 13. Secrets dan Konfigurasi

Secret dikelola melalui environment variable Compose dan GitLab CI/CD variables. Secret dilarang masuk ke Git repository, source code, contoh konfigurasi, screenshot, atau image build.

Contoh konfigurasi secret/environment:

```text
APP_KEY
APP_ENV / APP_URL / APP_DEBUG=false (production)
DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=private
```

Aturan:

- Konfigurasi via `.env` per environment; secret production hanya tersedia di environment production.
- `APP_KEY` wajib; rotasi secret dicatat dan diuji melalui prosedur operasional.
- Application startup memvalidasi konfigurasi wajib dan gagal aman bila secret penting tidak tersedia.
- Enkripsi at-rest ditangani infrastruktur (disk/backup encryption); tidak ada envelope encryption application-level pada MVP.

## 14. Logging, Health, Backup, dan Recovery

### Logging

- App, worker, dan scheduler menghasilkan structured log ke stdout (monolog JSON channel) — dikumpulkan runtime/container platform.
- Setiap request memiliki request ID/correlation ID bila tersedia.
- Log tidak boleh memuat password, raw session token, data autentikasi, atau binary attachment.
- Error log menyimpan konteks yang cukup untuk troubleshooting tanpa melanggar privasi data.
- Audit trail aplikasi tidak berada di log: event kritis tercatat di tabel `activity_log` (spatie/laravel-activitylog).

### Health endpoint

```text
GET /up  → health endpoint sederhana bawaan Laravel: proses hidup
           dan dependency dasar terjangkau.
```

Endpoint health dapat dibatasi agar tidak mengekspos detail internal ke publik.

### Backup minimum

| Data | Metode minimum | Target |
|---|---|---|
| PostgreSQL | `pg_dump` harian terjadwal dan terenkripsi | Lokasi berbeda dari server utama |
| Storage volume (attachment) | Rsync terjadwal | Lokasi berbeda dari server utama |
| Compose config | Backup konfigurasi tanpa secret plaintext | Lokasi aman dengan akses terbatas |

Backup belum dianggap valid hanya karena prosesnya berjalan. Restore PostgreSQL dan attachment volume harus diuji berkala (restore drill) pada environment terpisah, lalu recovery procedure didokumentasikan.

### Keterbatasan MVP

Setiap environment staging dan production berjalan pada satu server sehingga memiliki single point of failure per environment. Bila host, disk, atau network host gagal, seluruh service pada environment tersebut terdampak. High availability, multi-node deployment, database replication, object storage cluster, dan disaster-recovery automation adalah scope fase berikutnya.

## 15. Security Guardrails

- HTTPS wajib untuk seluruh aplikasi karena PWA, cookie secure, dan geolocation memerlukannya.
- PostgreSQL private pada Docker network; tidak ada public port.
- Seluruh authorization diputuskan server (middleware + Policy + ScopeService); UI bukan security boundary.
- Rate limit (database driver) diterapkan pada login, upload, dan endpoint sensitif.
- Upload divalidasi sinkron (magic byte, size, decode-safe, SHA-256) dan disimpan non-public; download via controller terkontrol + audit.
- Activity log untuk tindakan kritis, dipasang eksplisit di titik kritis.
- Migration dan dependency diperiksa dalam CI; seluruh query melalui Eloquent/query builder (parameterized) — tidak ada string SQL mentah dari input.
- CSRF protection bawaan Laravel aktif untuk seluruh form/request Inertia.
- Data selfie dan lokasi diperlakukan sebagai data sensitif operasional; akses mengikuti RBAC, privacy visibility (Manager tanpa raw selfie/precise GPS), dan retensi perusahaan. Tidak ada penyimpanan biometrik atau face-matching server-side.

## 16. Package: Sudah Ada vs Harus Di-install

### Sudah ter-install (React starter kit Laravel 13, ada di repo)

| Package | Peran |
|---|---|
| `laravel/framework` 13 | Framework inti |
| `inertiajs/inertia-laravel` + `@inertiajs/react` (v3, React 19) | Full-stack Inertia |
| `laravel/fortify` | Autentikasi (login, lockout, reset, profil) |
| `laravel/passkeys` | Passkey/passwordless — out-of-scope MVP, fitur disabled (dependency starter kit, tidak diaktifkan) |
| `laravel/wayfinder` | Typed routes ke TypeScript |
| `tailwindcss` 4 + shadcn/ui + Radix | UI component baseline |
| `vite` + TypeScript | Build frontend |
| `pest` / `larastan` / `pint` | Test, static analysis, formatter (CI baseline) |

### Harus di-install saat implementasi (belum ada di repo)

| Package | Peran | Catatan |
|---|---|---|
| `spatie/laravel-permission` | Role + permission (5 role fixed, single role per user) | Migration role/permission + seed |
| `spatie/laravel-activitylog` | Audit event aplikasi (`activity_log`) | Dipasang eksplisit di titik kritis |
| `intervention/image` + ext `imagick` + libheif | Thumbnail/preview WebP HEIC-aware | Required extension Imagick; libheif system library di image Docker |
| `maatwebsite/excel` | Export Excel xlsx styled | Preset export per user |
| `barryvdh/laravel-dompdf` | Export PDF formal | Header instansi |
| `vite-plugin-pwa` | PWA manifest + precache app shell | Tanpa offline draft |
| `leaflet` + `react-leaflet` | Peta koordinat site & titik check-in (info visual) | Tanpa Google Maps/Mapbox |

## 17. Explicit Non-Decisions / Out of Scope

Hal berikut tidak ditetapkan sebagai bagian stack MVP:

- Microservices, service mesh, Kubernetes, dan multi-cluster deployment.
- REST API layer terpisah / OpenAPI / client generation (diganti kontrak route Inertia).
- Redis atau message broker eksternal (database driver untuk semua).
- Transactional outbox.
- Native Android/iOS app.
- External authentication provider seperti Clerk, WorkOS, atau enterprise SSO.
- Managed cloud database atau external object storage wajib.
- S3 sebagai storage awal; local private disk digunakan terlebih dahulu.
- Stack observability penuh seperti Prometheus/Grafana/Loki sebagai scope awal.
- Integrasi Zabbix, SNMP, cloud AP controller, email, atau WhatsApp sebagai dependency MVP.
- Face matching / biometrik server-side (deteksi wajah browser hanya indikasi UI).
- Geofence gate, window jam, late, kalender kerja, Attendance Request, periode 21–20 (disiplin kehadiran ditangani vendor absensi EOS).
- PostGIS dan Google Maps/Mapbox.
- Multi-tenant.

## 18. Roadmap Evolusi Teknis

| Trigger | Evolusi yang dipertimbangkan |
|---|---|
| Upload meningkat, multi-replica app, atau kebutuhan backup file lebih ketat | Ganti disk Laravel ke S3-compatible object storage |
| Kebutuhan SSO/MFA/directory perusahaan | Implementasi OIDC adapter dan integrasi identity provider |
| Beban queue/cache meningkat melebihi database driver | Pisahkan Redis untuk cache/queue (perlu revisi ADR-047) |
| Database/application butuh isolasi lebih kuat | Pindahkan PostgreSQL ke VM/database server terpisah |
| Availability requirement meningkat | Multi-node app/worker, reverse proxy/load balancer, PostgreSQL replication, dan disaster recovery |
| Export volume besar mengganggu request sinkron | Pindahkan export ke queued job + notifikasi selesai (evolusi future, bukan MVP — export MVP sinkron/streamed, tidak ada notifikasi export) |

## 19. Ringkasan Keputusan Final

```text
Architecture  : Modular monolith (single Laravel 13 app)
Repository    : GitLab monorepo
Frontend      : Inertia v3 + React 19 + TypeScript + Tailwind 4 + shadcn/ui + Vite
Routing       : Server-side routing Laravel; Wayfinder typed routes; tanpa REST API/OpenAPI
Backend       : PHP 8.3+/8.4 + Laravel 13
Database      : PostgreSQL + Eloquent + migrations (UUID PK operasional)
Cache/session : Driver database (tanpa Redis)
Queue         : Laravel Queue database driver + Scheduler; worker container terpisah
Authentication: Fortify (starter kit); akun lokal (email/employee code + password); laravel/passkeys disabled — out-of-scope MVP
Password      : Argon2id, 12–128, lockout 5/15 menit, must_change_password
Session       : Secure HttpOnly cookie SameSite=Lax; idle 30m + absolute 8h; driver database
Authorization : spatie/laravel-permission + Policies + ScopeService (server-enforced)
Audit         : spatie/laravel-activitylog (tabel activity_log)
Files         : Local private disk (storage/app) + validasi sinkron + thumbnail queued
Image         : Intervention Image + Imagick/libheif (WebP preview, HEIC)
Export        : maatwebsite/excel (xlsx styled) + barryvdh/laravel-dompdf (PDF) + CSV streamed
Map           : Leaflet + react-leaflet (info visual)
PWA           : vite-plugin-pwa (app shell saja, tanpa offline draft)
Deployment    : Docker Compose via Sail; local + staging + production
CI/CD         : GitLab CI/CD (Pint, Larastan, Pest, build, migrate check)
Operations    : Structured log stdout, health endpoint /up, pg_dump + rsync backup, restore drill
```
