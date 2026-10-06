# Security Specification — PORTER (Portal Operasional Terpadu Sekolah Rakyat)

**Status:** Baseline MVP  
**Dokumen:** Security, Privacy, and Security Verification Specification  
**Scope:** Dashboard web responsif dan PWA; Laravel 13 single app Inertia v3 + React 19 (server-side routing, tanpa REST API layer terpisah); PostgreSQL; queue/scheduler worker; Docker Compose (Sail); local private attachment disk (`storage/app`).

## 1. Tujuan, Scope, dan Baseline

Dokumen ini menetapkan kontrol keamanan, privacy, dan verification gate untuk PORTER (Portal Operasional Terpadu Sekolah Rakyat). Sistem memproses data bukti kehadiran (selfie, koordinat GPS), laporan operasional, lampiran bukti, inventaris, serta data identitas pengguna. Kontrol di dokumen ini harus diterapkan di backend Laravel, frontend React/Inertia, database, deployment, CI/CD, dan prosedur operasional.

Baseline referensi:

- OWASP ASVS sebagai basis verifikasi kontrol aplikasi web.
- OWASP Top 10 risiko aplikasi web (broken access control, injection, misconfiguration, dsb) sebagai basis risiko seluruh endpoint HTTP — halaman Inertia maupun action route yang menangani mutasi.
- OWASP File Upload guidance sebagai basis perlindungan upload file.

Dokumen ini tidak menggantikan kebijakan perusahaan, kewajiban hukum, atau standar keamanan/privasi yang mungkin berlaku. Bila kebijakan perusahaan lebih ketat, kebijakan perusahaan berlaku.

### 1.1 Browser dan device baseline

Browser baseline yang didukung dan menjadi target pengujian keamanan (header, PWA, camera/geolocation API):

- Dua major version terbaru Android Chrome.
- Dua major version terbaru iOS Safari.
- Dua major version terbaru desktop Chrome, Edge, Firefox.

HEIC/HEIF diterima sebagai image attachment; thumbnail/preview di-generate queued job memakai Intervention Image dengan Imagick dan dukungan libheif. Jika HEIC gagal decode/validasi, file ditolak dengan pesan aman.

## 2. Prinsip Keamanan

```text
1. Backend Laravel adalah security authority; frontend React/Inertia bukan security boundary.
2. Default deny; akses diberikan secara eksplisit berdasarkan permission spatie, Policy, dan scope object/site.
3. PostgreSQL adalah source of truth untuk data bisnis, session, dan audit yang kritis.
4. Seluruh runtime state (session, cache, queue, rate limit, atomic lock) memakai driver `database` PostgreSQL; tidak ada Redis.
5. Evidence seperti selfie, lokasi, dan lampiran bersifat private serta hanya dapat diakses melalui authorization backend.
6. Waktu server, bukan jam perangkat, menjadi authority timestamp kehadiran.
7. Semua tindakan kritis dapat ditelusuri melalui audit log (spatie/laravel-activitylog).
8. Security controls harus testable (Pest) dan menjadi release gate, bukan sekadar dokumentasi.
9. Kegagalan security harus fail closed untuk tindakan sensitif.
10. Data minimization: kumpulkan, tampilkan, dan simpan data sesuai kebutuhan operasional.
```

## 3. Data Classification dan Retensi

### 3.1 Klasifikasi data

| Klasifikasi | Contoh data | Perlindungan minimum |
|---|---|---|
| Sangat sensitif | Password hash, session data, secret, APP_KEY/encryption key | Tidak pernah dilog; secret store (environment variable); akses sangat terbatas; enkripsi saat transit dan at rest sesuai kemampuan infrastruktur |
| Sensitif pribadi/operasional | Selfie check-in/clock-out, koordinat lokasi, distance_m, IP address, employee code, evidence attachment | RBAC ketat, private storage, audit access yang relevan, retensi terbatas, HTTPS wajib |
| Internal terbatas | Daily Report, anomali perangkat, data aset, stok, assignment EOS, audit log | RBAC/scope, halaman/route private, audit perubahan |
| Internal umum | Master kategori aset, checklist versi non-sensitif | RBAC write access dan versioning/audit perubahan |
| Publik | Tidak ada data produk yang direncanakan publik pada MVP | Default tidak publik |

### 3.2 Retensi baseline

| Data | Retensi minimum baseline | Ketentuan |
|---|---:|---|
| Attendance record, lokasi, selfie evidence | 2 tahun | Setelah retensi, archive/purge terkontrol sesuai kebijakan perusahaan dan kebutuhan audit |
| Daily Report dan lampiran | 2 tahun | Nomor dokumen dan metadata tetap menjaga referensi audit bila artefak dipurge sah |
| Inventory asset, stock transaction, dan finding | Selama aset/riwayat operasional relevan; minimum 2 tahun setelah closed/retired | Hindari hard delete histori |
| Audit log (activity_log) | Minimum 3 tahun | Append-only secara aplikasi; archive bila diperlukan |
| Login/session lifecycle audit | Minimum 1 tahun | Dapat mengikuti retensi audit log perusahaan jika lebih tinggi |
| File sementara (thumbnail job retry, file gagal validasi) | Maksimum 7 hari atau sampai selesai/ditolak | Scheduled command pembersihan terjadwal |

Penghapusan/purge tidak boleh menghapus integritas referensi audit. Modul legal hold ditunda ke fase 2 (deferred — built when retention/purge automation is implemented): saat purge automation diimplementasikan, aksi purge/retention wajib memeriksa legal hold sebelum eksekusi; legal hold menyimpan reason, creator, data scope, start/end time, dan audit event, dan data dalam hold tidak boleh dipurge sampai hold dilepas. Sampai fase tersebut, semua aksi purge/retention manual wajib memiliki prosedur, otorisasi, dan audit log. Visibility matrix data sensitif tetap berlaku penuh tanpa terpengaruh penundaan ini.

## 4. Threat Model Ringkas

| Asset/flow | Ancaman utama | Kontrol baseline |
|---|---|---|
| Local login | Credential stuffing, brute force, account enumeration, session theft | Argon2id, RateLimiter login (lockout), generic error, secure cookie, session rotation/revocation, audit |
| Kehadiran (bukti) | Fake GPS, replay/double submit, manipulasi jam device | Server timestamp, unique constraint EOS+site+tanggal, selfie, jarak disimpan sebagai informasi, audit |
| Daily Report | Submit ganda, edit submitted report, manipulasi nomor report, missing evidence | Transactional submit, validasi FormRequest/service, SEQUENCE PostgreSQL atomik, reopen with audit, attachment validation |
| Attachment | Malware, polyglot file, MIME spoof, public URL leakage, path traversal, excessive upload | Allowlist, magic-byte/MIME check, size limit, UUID storage key, private disk, download terkontrol, rate limit |
| Inventory | Mutasi tanpa jejak, direct balance edit, unauthorized site access | Transaction ledger + row lock, RBAC/scope, audit, transaksi via service |
| Route/endpoint | IDOR, privilege escalation, mass assignment, injection, resource exhaustion | Policy object-level, FormRequest + `$fillable` allowlist, Eloquent parameter binding, rate limit, pagination, output encoding |
| Deployment | Exposed DB, leaked secret, vulnerable image, lost volume | Internal Docker network, secret variables, pinned images, CI scan, backup/restore, least privilege |
| Privacy | Oversharing selfie/location, excessive retention, logs berisi PII | Permission scope, masked logs, private attachment, retention/purge policy, data minimization |

## 5. Authentication dan Password Security

Autentikasi memakai **Laravel Fortify** (bawaan starter kit) di atas session-based auth Laravel. Seluruh konfigurasi policy di bawah di-set di config Fortify/Laravel dan diuji dengan Pest.

### 5.1 Local identity

MVP memakai local account di PostgreSQL. Identifier login adalah email atau employee code ditambah password. External identity provider, SSO, dan passkey berada di luar scope MVP (architecture siap untuk future passkey via Fortify).

### 5.2 Password policy

```text
- Minimum panjang: 12 karakter (validation rule `Password::min(12)`) — konfigurasi Fortify.
- Maksimum panjang: 128 karakter.
- Password umum/lemah/terkompromi harus ditolak bila screening tersedia (rule `Password::uncompromised` bila API tersedia).
- Password tidak boleh sama dengan email atau employee code, case-insensitive.
- Password tidak disimpan, dikirim ulang, dilog, atau dimasukkan ke audit log dalam plaintext.
- Password disimpan sebagai Argon2id hash (config `hashing` driver argon2id) dengan random unique salt.
- Parameter Argon2id dikonfigurasi dan dapat dinaikkan melalui `Hash::needsRehash` / rehash-on-login policy.
- Tidak ada forced password rotation periodik tanpa indikasi compromise atau policy perusahaan.
- Password reset atau perubahan password merevoke seluruh session aktif pengguna.
```

### 5.3 Login protection

- Error login memakai pesan generik; tidak membedakan user tidak ditemukan, password salah, atau akun tidak aktif.
- Rate limit login memakai **Laravel RateLimiter (driver database)** yang dikonfigurasi sebagai login limiter Fortify, dengan dua counter independen: per identifier dan per IP.
- Normalisasi identifier: email di-trim dan di-lowercase; employee code di-trim dan di-uppercase.
- Window counter 15 menit; TTL counter 15 menit; ambang 5 kegagalan.
- Counter identifier dan counter IP dinaikkan secara independen: percobaan gagal untuk identifier yang dikenal menaikkan kedua counter (identifier + IP); identifier tak dikenal (user tidak ada) hanya menaikkan counter IP, bukan counter identifier (anti-enumeration; response tetap error generik).
- Lockout 15 menit aktif ketika SALAH SATU counter (identifier ATAU IP) mencapai 5 (via `RateLimiter::tooManyAttempts` + Fortify login limiter / `EnsureLoginIsNotThrottled`); lockout selalu diaudit tanpa membocorkan keberadaan akun.
- Login sukses mereset counter identifier user tersebut (`RateLimiter::clear`); counter IP tidak direset oleh satu login sukses.
- Catat login sukses, gagal, logout, reset password, perubahan password, account disable, dan perubahan role dalam audit log (listener event Fortify `Failed`, `Login`, `Logout`, `PasswordUpdated`, dsb).
- Login hanya diterima melalui HTTPS.
- Credential tidak ditulis dalam URL/query string, analytics event, crash report, atau log.

### 5.4 Password reset dan forced change (`must_change_password`)

- Reset password MVP dilakukan oleh Super Admin sesuai permission dan audit trail; pengiriman reset token via email/SMTP ditunda dan bukan bagian MVP.
- Super Admin menetapkan password sementara; sistem memaksa user mengganti password saat login berikutnya setelah reset (`must_change_password=true`).
- Mekanisme forced change lengkap:
  - Flag `users.must_change_password` di-set `true` oleh reset password Super Admin; flag (beserta `password_changed_at`) dipassing ke halaman Inertia (shared props) agar UI mengarahkan user ke alur ganti password.
  - Route self-service ganti password (POST, FormRequest `old_password` + `new_password` 12–128 karakter; CSRF required).
  - Selama `must_change_password=true`, **semua mutasi selain ganti password ditolak** oleh middleware `must_change_password` (route middleware, registered alias) — request mutasi diarahkan paksa ke halaman ganti password; halaman/GET tetap diizinkan.
  - Sukses ganti password: server merevoke semua session user lain (hapus record session lain di tabel sessions), **meregenerate session saat ini** (`session()->regenerate()` — CSRF token lama otomatis invalid), meng-clear flag `must_change_password`, mencatat `password_changed_at`, dan mencatat audit event `PASSWORD_CHANGED`.
  - Reset tidak boleh mengungkap apakah email tertentu terdaftar.
  - Setelah reset berhasil, semua session aktif user direvoke dan audit event dibuat.
- Jika SMTP tersedia pada fase berikutnya, sistem dapat mengirim reset token sekali pakai dengan expiry singkat.

## 6. Session, Cookie, dan CSRF

### 6.1 Session model

- Session driver adalah **`database`** (tabel `sessions` di PostgreSQL) — tidak ada Redis; lifecycle session persisten lintas restart.
- Session ID bersifat opaque, acak kriptografis, dan tidak memuat role/data bisnis dalam plaintext.
- Browser menyimpan session ID melalui cookie session Laravel: `Secure`, `HttpOnly`, dan `SameSite=Lax` sebagai baseline (config `session.secure`, `session.http_only`, `session.same_site`).
- Session memiliki idle timeout 30 menit (`session.lifetime` = 30, absolute idle) dan absolute expiry 8 jam (login timestamp disimpan di session; middleware memeriksa dan memaksa logout/re-auth setelah 8 jam) sebagai baseline final.
- Session ID diputar setelah login dan perubahan password (`$request->session()->regenerate()`).
- Logout merevoke session (`Auth::guard()->logout()` + `session()->invalidate()`).
- Akun disabled atau reset password merevoke seluruh session aktif user.

### 6.2 CSRF

Karena browser memakai cookie session authentication, seluruh route state-changing wajib melewati middleware `VerifyCsrfToken` Laravel (pola double-submit token):

- Laravel menyimpan token CSRF terikat session pada cookie `XSRF-TOKEN` (non-HttpOnly, `Secure`, `SameSite=Lax`) dan client Inertia mengirimkannya pada setiap request mutasi melalui header `X-CSRF-TOKEN` (Inertia menangani ini otomatis pada setiap request).
- Token terikat pada session: `session()->regenerate()` saat login/password change mengganti token sehingga token lama otomatis invalid.
- Middleware memvalidasi kecocokan token dengan session aktif; ketidakcocokan menolak request (419).
- Token tersedia sejak halaman pertama render (Inertia page props / cookie) sehingga tidak ada race window antara login dan mutasi pertama.
- Route login POST juga memakai CSRF protection; ditambah validasi Origin/Referer allowlist: bila header Origin ada tetapi tidak cocok dengan allowlist (host aplikasi), request ditolak; bila Origin absen, request diterima (transport tetap HTTPS dan rate limit login tetap berlaku).
- Cookie session tetap tidak dapat dibaca JavaScript (`HttpOnly`); hanya cookie `XSRF-TOKEN` yang dibaca frontend untuk header.
- Route mutasi tidak boleh menerima request lintas-origin tanpa policy eksplisit.

### 6.3 CORS

- Aplikasi Inertia adalah same-origin; CORS default tertutup.
- CORS menggunakan allowlist origin yang eksplisit per environment, hanya untuk kebutuhan nyata (mis. dev server Vite pada local).
- `Access-Control-Allow-Origin: *` dilarang untuk endpoint authenticated.
- `credentials` hanya diaktifkan untuk origin yang benar-benar diperlukan.
- Method, header, dan preflight dibatasi sesuai kontrak route.

## 7. Authorization dan RBAC

### 7.1 Role dan permission

```text
Role (spatie/laravel-permission, single role per user, guard web):
SUPER_ADMIN
MANAGER
SUPERVISOR
HR
EOS
```

RBAC mengikuti PRD. Semua route/halaman melakukan server-side authorization (middleware `auth` + `can`/Policy); menyembunyikan menu frontend bukan kontrol keamanan.

### 7.2 Object-level authorization

Setiap route yang menerima identifier object harus memverifikasi (via **Policy model** + **ScopeService**):

```text
1. Session valid dan user aktif.
2. Permission/action pada role user (spatie permission).
3. Scope object: site, assignment EOS, owner record, atau cakupan global role (ScopeService: EOS = assignment aktif; non-EOS = semua site aktif).
4. Status workflow yang mengizinkan action.
5. Segregation of duties / reviewer authority bila diperlukan.
```

Contoh wajib:

- EOS hanya melihat/mengubah attendance dan Daily Report miliknya pada scope assignment aktif/riwayat yang diizinkan.
- EOS hanya melihat inventaris site penugasannya dan tidak dapat mengubah saldo/aset langsung (dapat meregistrasi aset yang datang dari gudang sesuai flow §8.4).
- Supervisor dapat mereview Daily Report, finding, dan data kehadiran seluruh site aktif, sesuai baseline produk.
- HR hanya melihat data kehadiran yang diizinkan; tidak otomatis berhak melihat bukti teknis Daily Report.
- Manager dapat melihat analitik dan data lintas-site sesuai PRD, tetapi bukan action operasional yang tidak diberikan.
- Super Admin memiliki cakupan administratif penuh, namun action sensitif tetap teraudit.

### 7.3 Sensitive data visibility matrix

Data sensitif yang dibatasi: selfie, precise GPS, distance_m, evidence attachment, IP address, dan data operasional identitas tertentu.

| Role | Selfie/GPS presisi | Evidence Daily Report | Data lintas-site |
|---|---|---|---|
| SUPER_ADMIN | Ya, sesuai kebutuhan operasional/audit | Ya, teraudit | Ya |
| SUPERVISOR | Ya, site scope, untuk review kehadiran/report | Ya, site scope | Site scope saja |
| MANAGER | Tidak; ringkasan lintas-site tanpa raw sensitive | Ringkasan, bukan raw evidence | Ya, sesuai scope |
| HR | Tidak; data kehadiran sesuai otorisasi tanpa technical evidence | Tidak otomatis | Sesuai otorisasi |
| EOS | Miliknya sendiri | Miliknya sendiri | Data site penugasannya |

Scope role non-EOS MVP: kolom "site scope" untuk Supervisor/HR/Manager pada matrix ini berarti **semua site aktif** (single-project, ~200 site, tim kecil) — pembatasan per-site untuk role non-EOS ditunda sebagai reserved future capability, bukan bagian MVP. Authorization backend tetap menegakkan scope berdasarkan site aktif; site non-aktif tidak termasuk scope operasional.

- Semua akses/download data sensitif menghasilkan audit event (activitylog).
- Export (Excel/PDF/CSV) wajib mengikuti matrix ini; Manager tidak boleh export raw selfie, precise GPS, atau sensitive attachment. Export berjalan sinkron (streamed response) dan audit event export ditulis sinkron sebelum stream dimulai; pelanggaran scope privacy ditolak 403 `EXPORT_SCOPE_FORBIDDEN`.

### 7.4 Mass assignment dan action authorization

- Validasi input memakai **FormRequest** dengan field eksplisit; model Eloquent memakai `$fillable` allowlist; jangan bind massal dari request.
- Field seperti `role`, `site_id`, `status`, `approved_by`, `report_number`, `created_by`, dan audit fields tidak boleh dapat diatur bebas client.
- Status transition dilakukan hanya melalui use case/service action yang eksplisit, misalnya `submit`, `reopen`, `void`, `reject`, atau `resolve`.

### 7.5 Permission registry

Backend tidak melakukan hard-code role check per controller; seluruh otorisasi action melewati **permission spatie**:

- Policy/middleware memeriksa **permission code** (mis. `user.create`, `checklist.publish`, `report.reopen`), bukan role literal.
- Permission dan mapping role→permission disimpan oleh `spatie/laravel-permission` (tabel `permissions`, `roles`, `role_has_permissions`) dan di-seed dari matriks kapabilitas/visibilitas PRD §4.
- **Server-side tetap authoritative**: menyembunyikan UI bukan kontrol keamanan; UI settings RBAC (mengubah mapping runtime) adalah fase 2 tanpa refactor karena otorisasi sudah berbasis permission code.
- Setiap override permission (bila diaktifkan fase 2) wajib diaudit.
- Permission code di-declare di kode (seeder/migration); menambah permission baru tidak boleh dilakukan runtime — memerlukan release. Role lima nilai tetap; MVP seed-only tanpa UI admin permission.

## 8. Route dan Request Security

### 8.1 Input, output, dan error

- Semua input divalidasi server-side melalui FormRequest: tipe, panjang, range, format, dan business rule.
- Response halaman/route memakai data yang di-mapping eksplisit (props/resource); jangan mengekspose model Eloquent mentah lebih luas dari kebutuhan.
- Query PostgreSQL memakai Eloquent/query builder dengan parameter binding; string concatenation untuk parameter user dilarang.
- Output yang masuk UI di-escape secara default (React auto-escaping); rendering HTML rich text dari user dilarang pada MVP kecuali sanitization ketat diterapkan.
- Error response konsisten, tidak memuat stack trace, secret, query, internal host, atau detail authorization yang berlebihan (mode produksi `APP_DEBUG=false`).
- Route publik dibatasi pada halaman login dan static asset; seluruhnya route aplikasi lain memerlukan auth.

### 8.2 Rate limiting dan resource control

Rate limit memakai **Laravel RateLimiter dengan cache driver `database`** berdasarkan IP, identity, session, dan endpoint sensitivity.

Rate limit login (counter identifier + IP, lockout) diatur pada §5.3. Rate limit non-login minimum (named limiters + middleware `throttle`):

| Route/action | Limit | Key | Response saat terlampaui |
|---|---|---|---|
| Check-in/clock-out | 30/min/user | `user_id` (auth) | 429 + `Retry-After` |
| Upload attachment | 20/min/user | `user_id` (auth) | 429 + `Retry-After` |
| Submit Daily Report | 10/min/user | `user_id` (auth) | 429 + `Retry-After` |
| Approve/reject/resolve/reopen | 60/min/user | `user_id` (auth) | 429 + `Retry-After` |
| Analytics/export | 30/min/user | `user_id` (auth) | 429 + `Retry-After` |

- Semua rate limit berbasis session memakai key `user_id` dari auth user aktif.
- Response limit terlampaui adalah HTTP 429 dengan header `Retry-After`.
- Karena rate limit state berada di PostgreSQL (source of truth yang sama dengan data bisnis), tidak ada failure mode "limiter unavailable tapi DB hidup"; bila koneksi database gagal, request mutasi memang gagal — fail closed secara natural. Endpoint read-only mengikuti behavior koneksi yang sama.

Semua listing memakai pagination server-side, batas `page_size`, filter allowlist, sort allowlist, dan query timeout. Upload memiliki batas ukuran 10 MB per file dan batas jumlah file per context yang configurable.

### 8.3 Idempotency dan replay protection

Endpoint kritis dilindungi dari duplikasi lewat constraint dan status domain:

- Check-in/clock-out: unique constraint `EOS + site + work_date_local` + status record (`NOT_CHECKED_IN → CHECKED_IN → COMPLETED`) — percobaan kedua ditolak tanpa membuat record kedua.
- Submit Daily Report: unique `EOS + site + work_date` + status `SUBMITTED` (immutable); retry submit pada report yang sudah submitted ditolak.
- Upload attachment: batas jumlah per context (unique satu attachment tepat satu context).

Tidak ada header `Idempotency-Key` generik pada MVP; perlindungan duplikat melekat pada invariant domain di atas.

### 8.4 Route business-flow controls

- Check-in dan clock-out menerima selfie + koordinat GPS; timestamp resmi adalah waktu server memproses request (UTC).
- Check-in dan clock-out harus berada pada local calendar date yang sama (timezone site); cross-midnight tidak didukung.
- Satu attendance record per EOS + site + `work_date_local` (unique constraint); percobaan kedua ditolak (`ALREADY_CHECKED_IN`/`ALREADY_CLOCKED_OUT`) dan attempt gagal tetap diaudit.
- Clock-out hanya valid setelah Daily Report tanggal tsb `SUBMITTED` dan seluruh required evidence `AVAILABLE`; bila belum, request ditolak dan user diarahkan melengkapi report (flow kontinu).
- Jarak ke site (Haversine, dihitung PHP) disimpan sebagai informasi; tidak ada penolakan radius.
- Daily Report submitted tidak diedit langsung; reopen memiliki permission khusus, reason wajib, dan audit.
- Nomor report hanya dialokasikan server secara atomik (PostgreSQL SEQUENCE) pada submit sukses.
- Inventory saldo hanya berubah melalui transaction ledger yang tervalidasi (row lock, saldo tidak negatif), bukan update saldo langsung.
- Registrasi aset oleh EOS: format tag/SN divalidasi (regex CMX) + unik; perubahan status aset = transaksi beralasan + audit + foto bila rusak/hilang.

## 9. Attendance Integrity dan Privacy

### 9.1 Server-authoritative timestamp

- Timestamp resmi check-in/check-out adalah waktu server Laravel menerima dan memproses request (UTC canonical).
- Frontend boleh mengirim metadata capture; timestamp perangkat bukan sumber waktu resmi.
- Backend menentukan timezone site dan `work_date_local` (disimpan sebagai snapshot untuk penampilan).

### 9.2 Lokasi

- Lokasi dikirim bersama latitude, longitude, accuracy, dan timestamp capture (diambil sekali dengan toleransi longgar).
- Backend menghitung jarak Haversine terhadap koordinat site (PHP) dan menyimpan `distance_m` sebagai **informasi**, bukan gerbang: tidak ada penolakan radius, tidak ada borderline flag.
- Raw location point dan calculated distance tidak bisa dimanipulasi klien (dihitung server-side).
- Sistem tidak mengklaim anti-fake GPS 100%; sistem hanya mencatat bukti kehadiran — disiplin kehadiran ditangani absensi vendor EOS.
- Defence in depth: server time, unique constraint anti-double-submit, selfie, dan audit.

### 9.3 Selfie

- Check-in/clock-out wajib memakai selfie dari camera capture (getUserMedia), bukan upload file bebas dari galeri pada UI normal.
- UI selfie: overlay lingkaran panduan; deteksi wajah opsional via browser FaceDetector API bila tersedia (hanya indikasi wajah terdeteksi untuk mengaktifkan tombol jepret); fallback overlay panduan saja.
- Tidak ada penyimpanan biometrik dan tidak ada face-matching server-side.
- Backend tetap memvalidasi content type, ukuran, signature/magic byte, dan decode-safe sesuai §10.
- Selfie hanya dapat diakses role yang memang membutuhkan evidence sesuai RBAC (visibility matrix §7.3).
- Selfie tidak boleh berada di public directory (`storage/app` di luar `public/`), public URL, log, atau analytics telemetry.

## 10. File Upload dan Attachment Security

### 10.1 Allowlist dan batas file

| Aturan | Baseline |
|---|---|
| Ukuran maksimum | 10 MB per file |
| Format umum yang diizinkan | JPEG, PNG, WebP, HEIC/HEIF, PDF |
| Format selfie | JPEG, PNG, WebP, HEIC/HEIF (tanpa PDF) |
| Storage | Local disk private `storage/app` (di luar webroot/public) |
| Naming | UUID/random storage key dari server |
| Akses | Controller download terkontrol (authorization + audit) |
| Metadata database | Entity/context, attachment type, uploader, size, content type, checksum SHA-256, storage key, upload time, status |
| Batas per context | 10 per Daily Report; 5 per section evidence; 5 per item evidence; 1 selfie check-in + 1 selfie clock-out; 5 per inventory mutation |

### 10.2 Validasi sinkron dan thumbnail queued

- Validasi extension adalah lapisan tambahan, bukan satu-satunya kontrol.
- **Validasi dilakukan sinkron pada request** (FormRequest/service): backend memverifikasi magic bytes/file signature serta MIME hasil inspeksi (Imagick), membandingkan konsistensi dengan extension yang dinyatakan, memeriksa size, memastikan file decode-safe, dan menghitung checksum SHA-256; jangan percaya `Content-Type` dari client.
- File yang lolos seluruh validasi **langsung berstatus `AVAILABLE`**; file yang gagal ditolak dengan pesan aman (`REJECTED`) dan audit event. Tidak ada status QUARANTINED/PROCESSING.
- Thumbnail/preview WebP (max 2048 px) dan thumbnail kecil (max 480 px) di-generate **queued job** (database queue driver) memakai Intervention Image (Imagick + libheif untuk HEIC). Original immutable dan private; derivative tidak menggantikan original; PDF original tidak direcompress.
- Tidak ada malware scan (ClamAV dihapus — ADR-044): risiko diterima; mitigasi: validasi MIME/magic-byte, batas ukuran, decode aman, akses terotorisasi + audit.
- File `REJECTED` atau corrupt tidak dapat dipakai sebagai evidence final atau dipreview; status tersebut diikuti audit event.
- Tolak nama/path yang mengandung traversal atau karakter berbahaya; nama asli hanya sebagai metadata ter-sanitasi.
- Terapkan batas file count, total size, request timeout, dan disk quota/alert operasional.
- Job image memakai Imagick dengan timeout dan resource limits; worker = container/service terpisah `php artisan queue:work`.
- File sementara (mis. job thumbnail gagal berkali-kali) dibersihkan scheduled command maksimum tujuh hari.

### 10.3 Delivery dan akses file

- File tidak disajikan lewat static public directory.
- Route preview/download memverifikasi session, role, object scope, dan association entity sebelum membuka file.
- Set `Content-Disposition` aman dan `X-Content-Type-Options: nosniff`.
- PDF/image rendering diperlakukan sebagai content untrusted; iframe/embed policy dibatasi.
- Semua akses/download attachment sensitif (selfie, GPS presisi, evidence) wajib dicatat sebagai audit event (activitylog), bukan opsional hardening.

## 11. Data Protection, Logging, dan Audit

### 11.1 Transit dan at rest

- HTTPS wajib untuk seluruh traffic pengguna dan aplikasi.
- PostgreSQL tidak diekspos ke internet; hanya internal Docker network.
- Persistent volume (database dan `storage/app`) memiliki permission host yang dibatasi.
- Backup (pg_dump + rsync attachment) dienkripsi dan disimpan di lokasi berbeda dari host utama.
- Enkripsi at-rest bergantung kemampuan host/storage; minimum baseline adalah disk/backup encryption bila infrastruktur menyediakan.

### 11.2 Logging dan masking

- Aplikasi menghasilkan structured log ke stdout (channel Laravel terkonfigurasi JSON/structured).
- Log tidak boleh berisi password, raw session ID, CSRF token, secret, APP_KEY, full cookie header, binary attachment, atau koordinat/selfie berlebihan.
- Error yang terkait PII memakai identifier internal/pseudonym bila memungkinkan.
- Request ID/correlation ID digunakan untuk troubleshooting lintas web/worker tanpa memasukkan secret.

### 11.3 Audit log

Audit event memakai **spatie/laravel-activitylog** (tabel `activity_log`), dipasang di titik kritis eksplisit (service/controller, bukan auto-log seluruh model). Setiap event minimal mencatat:

```text
- causer (user / system actor)
- action/event name
- subject type dan subject ID
- occurred_at UTC
- properties before/after yang telah disaring dari secret
- batch/transaction ID bila relevan
- IP/user agent bila dibutuhkan dan sesuai privacy policy
- metadata konteks
```

Minimal action yang diaudit:

- Login sukses/gagal, logout, reset/change password, account enable/disable.
- Role/user/site/policy/assignment/checklist version change (publish/supersede).
- Check-in/clock-out beserta attempt gagal.
- Report submit/reopen/void.
- Asset status change, stock mutation (dan reversal), finding state change.
- Attachment upload/validation/rejection dan akses/download data sensitif.
- Export (actor, tipe data, filter, scope, format, timestamp, outcome).
- Secret/configuration reference change tanpa menulis secret actual.

## 12. Application Security Hardening

### 12.1 XSS

- React output di-escape secara default; `dangerouslySetInnerHTML` dilarang kecuali exception terdokumentasi dan sanitization kuat.
- User-provided text ditampilkan sebagai plain text secara default.
- Content Security Policy (CSP) diterapkan dan diuji bertahap agar tidak memutus PWA/upload flows.

### 12.2 Injection

- SQL memakai Eloquent/query builder parameterized.
- Dynamic sort/filter memakai allowlist nilai yang dipetakan server-side.
- Shell command dengan input user dilarang.
- Jika image processor dipanggil (Intervention/Imagick), gunakan adapter library dengan timeout dan resource limit; jangan compose shell argument dari input user.

### 12.3 SSRF dan outbound request

MVP tidak memiliki fetch URL dari input pengguna. Jika future feature menerima URL/link atau memanggil external integration:

- Validasi scheme/domain/IP destination.
- Blok localhost, RFC1918/internal network, metadata service, serta redirect tidak aman.
- Terapkan allowlist provider dan timeout.
- Jangan mengirim secret ke endpoint dari input user.

### 12.4 Security headers

Baseline reverse proxy/aplikasi:

```text
Strict-Transport-Security
Content-Security-Policy
X-Content-Type-Options: nosniff
Referrer-Policy
Permissions-Policy
X-Frame-Options atau CSP frame-ancestors
Cache-Control: no-store untuk response auth/sensitive
```

`Permissions-Policy` membatasi geolocation dan camera hanya sesuai kebutuhan aplikasi/origin. Header harus diuji terhadap requirement PWA dan browser target.

## 13. Infrastructure, Docker, PostgreSQL

### 13.1 Docker Compose (Sail)

- Image version dipin; `latest` dilarang pada production.
- Container menjalankan user non-root bila image mendukung.
- Filesystem container dibuat read-only bila kompatibel, kecuali mount yang diperlukan.
- Service dipisah: web (FPM/server), queue worker (`php artisan queue:work`), scheduler (`php artisan schedule:work`), PostgreSQL. Tidak ada Redis.
- Hanya reverse proxy yang membuka port publik HTTPS; PostgreSQL tanpa port publik.
- Persistent volumes terpisah: `postgres_data`, `storage_data` (attachment private).
- Docker socket tidak dimount pada application container.
- Tiga environment terpisah: local development, staging, dan production. Staging wajib sebelum pilot dan production release; deployment produksi hanya dari branch `production` melalui promotion `faizaldev -> staging -> production`.
- Staging tidak boleh memakai raw production data (selfie, GPS, evidence, data identitas); uji coba memakai data uji/anonim.
- Secrets terpisah per environment (environment variables); tidak ada secret lintas environment.
- Monitoring: health endpoint sederhana + structured log stdout.

### 13.2 PostgreSQL

- Credential database unik per environment; password kuat melalui secret runtime.
- Application memakai account least-privilege, bukan PostgreSQL superuser untuk runtime.
- Migration account dapat dipisahkan dari runtime account bila implementasi siap.
- Koneksi memakai TLS bila PostgreSQL dipindahkan lintas host/network; intra-host Docker network tetap dibatasi.
- Backup terjadwal (pg_dump harian), terenkripsi, diuji restore, dan tidak dapat diakses publik; attachment di-backup rsync terjadwal.
- Migration Laravel direview dan dijalankan terkontrol dalam pipeline/deploy.

## 14. Secrets dan Supply Chain

### 14.1 Secret management

- Secret disimpan di environment configuration (Docker Compose/Sail env, CI/CD variables) per environment.
- Secret tidak boleh masuk Git, Docker image, build artifact, log, issue, atau chat export.
- Secret production hanya tersedia untuk environment/pipeline production yang berwenang.
- Startup aplikasi memvalidasi secret wajib (APP_KEY, DB credential, dsb) dan gagal aman bila secret penting kosong/default (config validation saat deploy/health check).
- Rotasi secret memiliki runbook dan audit event.

### 14.2 Dependency dan image security

- Dependency PHP/Node dikunci menggunakan `composer.lock`/`package-lock.json` dan version pin.
- CI menjalankan dependency/vulnerability scan (composer audit, npm audit, secret scan) sesuai kemampuan pipeline.
- Container image dibangun dari base image terpercaya dan dipin version/digest bila memungkinkan.
- High/critical vulnerability ditriage sebelum deploy; exception harus terdokumentasi dengan risk owner dan expiry.
- Jangan menggunakan package yang tidak perlu, terutama untuk auth, crypto, upload, dan parsing file.

## 15. Backup, Recovery, dan Incident Response

### 15.1 Backup

| Data | Kebutuhan minimum |
|---|---|
| PostgreSQL | pg_dump harian, encrypted, off-host, retention 30 hari, restore test bulanan |
| Attachment volume (`storage/app`) | rsync terjadwal, encrypted/off-host, retention 30 hari, integrity check (checksum SHA-256) |
| Compose/Sail config | Konfigurasi terdokumentasi/backup aman tanpa memasukkan secret plaintext |

### 15.2 Restore dan recovery

- RPO maksimum 24 jam; RTO maksimum 8 jam. Target ini baseline final dan menjadi parameter desain backup/monitoring.
- Restore test PostgreSQL dan attachment volume (restore drill) dilakukan bulanan di staging; hasilnya mencatat date, operator, duration, dan outcome.
- Recovery runbook harus mencakup: provisioning host, restore volume/database, inject secret, migrate kompatibel (`php artisan migrate`), deploy service, validation smoke test, dan audit/event review.
- Single point of failure harus diterima secara sadar pada MVP; kontrol di dokumen ini adalah mitigasi minimum.

### 15.3 Incident baseline

Saat incident keamanan dicurigai:

```text
1. Triage dan catat waktu/indikator.
2. Contain: revoke session, disable account, block route/rate limit, atau isolate service sesuai kebutuhan.
3. Preserve evidence: audit log, application log, database state, file metadata; jangan mengubah bukti tanpa jejak.
4. Assess impact terhadap user, attendance evidence, report, dan data.
5. Eradicate/remediate root cause.
6. Recover melalui backup/rollback/redeploy yang terkontrol.
7. Post-incident review, corrective action, dan update security/test documentation.
```

## 16. Security Testing dan Release Gate

### 16.1 Test minimum

| Layer | Pengujian minimum |
|---|---|
| Unit (Pest) | Password policy, time/work_date logic, Haversine distance, Policy/permission, status transition, sequence/idempotency logic, throughput normalization |
| Feature (Pest) | PostgreSQL constraints/transaction, session database, validasi file sinkron + thumbnail queued job, protected attachment retrieval, seluruh matrix role/route |
| Security route | Authentication, authorization per role (Policy + spatie), object-level access, mass assignment, CSRF, CORS, rate limit, validation/error handling |
| E2E | Login, session expiry, EOS check-in/report/clock-out, export, finding lifecycle, attachment failure/retry |
| Manual/security review | Permission matrix, exposed service/port, secret scan, headers, Docker config, backup restore evidence |

Detail test mandatory ada di `test-strategy.md`.

### 16.2 Production release gate

Sebelum production MVP, seluruh kondisi minimum berikut harus lulus:

```text
[ ] HTTPS aktif dan HTTP redirect/disable sesuai policy.
[ ] PostgreSQL tidak memiliki public port; tidak ada Redis yang di-deploy.
[ ] Default credential tidak digunakan.
[ ] Secret tidak terdeteksi di repository/image/log.
[ ] Password hash Argon2id dan secure session/cookie aktif (driver database).
[ ] CSRF + CORS policy sudah diuji pada browser/PWA target.
[ ] Middleware must_change_password aktif menolak semua mutasi selain ganti password (read tetap boleh); change password sukses merevoke session lain, meregenerate session, meng-clear flag, dan mengaudit PASSWORD_CHANGED.
[ ] RBAC (spatie + Policy) dan object-level authorization memiliki automated test (Pest) untuk role utama.
[ ] Check-in/clock-out/report submit memiliki duplicate protection (unique constraint + status gate).
[ ] File upload allowlist, magic-byte/MIME validation, size limit, private disk, dan authorization retrieval diuji.
[ ] Tidak ada ClamAV/malware scan (ADR-044); attachment pipeline hanya validasi sinkron MIME/magic-byte, size, decode-safe, SHA-256; thumbnail via queued job.
[ ] Audit events kritis tercatat (activity_log) dan tidak dapat dihapus lewat aplikasi.
[ ] Backup PostgreSQL dan attachment volume sukses; restore test terbukti.
[ ] Dependency/image vulnerability high/critical sudah ditriage.
[ ] Security headers, rate limit, error masking, dan log redaction diverifikasi.
[ ] Incident/recovery contact dan runbook tersedia.
```

## 17. Security Decisions Summary

```text
Authentication        : Local account, email/employee code + password (Laravel Fortify, session-based)
Password hash         : Argon2id + unique random salt (Laravel hashing)
Password baseline     : Min 12, max 128, block common/compromised, no forced periodic rotation
MFA                   : Deferred; architecture ready for future TOTP/passkey via Fortify
Session               : Driver database (tabel sessions), cookie Secure+HttpOnly+SameSite=Lax, idle 30 m / absolute 8 h, regenerate on login/password change, revoke saat logout/reset/disable
Login protection      : Two independent RateLimiter counters per identifier AND per IP; window/TTL 15 min; threshold 5 on EITHER counter triggers 15-min lockout; unknown identifier raises IP counter only (anti-enumeration); successful login resets that user's identifier counter; lockout audited
Forced change         : users.must_change_password flag (set oleh reset Super Admin); selama aktif middleware must_change_password menolak semua mutasi selain ganti password (read tetap boleh); change password sukses merevoke session lain + meregenerate session + meng-clear flag + mengaudit PASSWORD_CHANGED
CSRF                  : Laravel VerifyCsrfToken (token terikat session) — cookie XSRF-TOKEN + header X-CSRF-TOKEN otomatis via Inertia; invalid saat session regenerate/revoke; login route + Origin allowlist (present + mismatch reject, absent accept)
Authorization         : spatie/laravel-permission (permission code, seed-only) + Laravel Policy per model + ScopeService (EOS assignment aktif; non-EOS semua site aktif) + sensitive data visibility matrix
Attendance integrity  : Server timestamp UTC + unique constraint EOS+site+tanggal + selfie + jarak Haversine disimpan sebagai informasi + audit (bukti kehadiran; disiplin ditangani vendor)
File upload           : 10 MB max, general JPEG/PNG/WebP/HEIC-HEIF/PDF, selfie JPEG/PNG/WebP/HEIC-HEIF (tanpa PDF), private disk storage/app, SHA-256, validasi sinkron → langsung AVAILABLE, thumbnail WebP queued job
Malware               : None — ClamAV dropped (ADR-044); validation-only: MIME/magic-byte, size limit, safe decode, SHA-256; risk accepted, access control + audit remain
Data retention        : Attendance/report evidence 2 years; audit 3 years minimum
Routes                : Inertia pages + action routes, FormRequest allowlist, validasi server-side, rate limit, duplicate protection domain invariant
Database              : PostgreSQL source of truth (data + session + queue + cache + rate limit + activity_log), Eloquent parameter binding
Deployment            : Docker Compose/Sail (web, queue worker, scheduler, PostgreSQL), local/staging/production separated, staging without raw production data, per-environment secrets, pinned images
Operations            : Structured redacted logs (stdout), health endpoint, pg_dump harian + rsync attachment 30-day retention, RPO 24 h / RTO 8 h, monthly restore drill in staging, legal hold before purge (deferred — fase 2, bersama purge automation), incident runbook
```
