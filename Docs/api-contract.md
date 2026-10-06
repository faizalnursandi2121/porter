# Kontrak Halaman & Aksi Inertia — PORTER (Portal Operasional Terpadu Sekolah Rakyat)

**Status:** Baseline MVP
**Dokumen:** Kontrak Halaman & Aksi (naratif)
**Stack:** Laravel 13 single app — Inertia v3 + React 19, server-side routing
**Autentikasi:** Session cookie `Secure`, `HttpOnly`, `SameSite=Lax`; CSRF via middleware `VerifyCsrfToken`

## 1. Tujuan dan Scope

Dokumen ini menetapkan kontrak antara controller Laravel dan halaman React (Inertia) untuk seluruh aksi pengguna. Tidak ada REST API layer terpisah, tidak ada OpenAPI, tidak ada envelope JSON `data`/`error`. Semua interaksi adalah:

1. **Navigasi halaman** — `GET` route mengembalikan `Inertia::render('PageName', $props)`.
2. **Aksi form** — `POST`/`PATCH`/`PUT`/`DELETE` route dengan payload form; controller memvalidasi via `FormRequest`, menjalankan service, lalu `redirect()->route(...)` (sukses, dengan flash) atau redirect back with errors (gagal).
3. **Response biner** — streamed download/preview attachment dan export (xlsx/pdf/csv); tidak melewati Inertia.

Kontrak ini melengkapi `prd.md`, `erd.md`, `data-dictionary.md`, `ux.md`, `ui-spec.md`, dan `architecture.md`. Route diakses dari React melalui helper typed Wayfinder (`route('eos.attendance.check-in.store')` dsb), sehingga nama route di dokumen ini adalah kontrak stabil antara backend dan frontend. Struktur path dan page component di dokumen ini selaras dengan baseline navigasi di `ux.md` §9 dan `ui-spec.md` §3.

Scope baseline mencakup:

- Auth (login/logout/ganti password) + user management dan reset password (Super Admin).
- Site master, network link, assignment.
- Attendance check-in/clock-out sebagai bukti kehadiran (selfie + GPS + timestamp server).
- Daily Report (create draft, isi, submit, reopen) + checklist version (draft/publish).
- Inventory: registrasi aset oleh EOS, perubahan status aset, mutasi stok, finding.
- Attachment upload (validasi sinkron) dan preview/download terkontrol.
- Notifikasi in-app (read), export xlsx/pdf/csv dengan preset, audit view, storage usage, dashboard, analytics Manager.

Out of scope dibuat eksplisit: semua fitur disiplin absensi (window jam, late, klasifikasi, kalender kerja, geofence gate, Attendance Request — seluruh modul dihapus), face matching/biometrik server-side, legal hold, forgot/reset password self-service (SMTP ditunda; reset oleh Super Admin).

## 2. Konvensi Global

### 2.1 Route, page, dan prop

Penamaan route mengikuti konvensi Laravel `resource.controller.aksi` dengan prefix role (`eos.`, `supervisor.`, `manager.`, `hr.`, `admin.`) untuk halaman role-specific. Page component React berada di `resources/js/pages/...` mengikuti folder per area (`Auth/`, `Profile/`, `EOS/`, `Supervisor/`, `Manager/`, `Hr/`, `Admin/`, `Notifications/`, `Exports/`); path component dicantumkan pada tabel matriks.

Setiap halaman menerima prop standar (shared props middleware):

| Prop | Isi | Keterangan |
|---|---|---|
| `auth.user` | user aktif: `id`, `name`, `employee_code`, `email`, `role` | single role per user |
| `auth.can` | permission ringkas (hasil Policies) | untuk render kondisional UI |
| `auth.unread_notifications` | count badge notifikasi | UI tidak pernah menghitung lokal |
| `flash` | `{ type: 'success' | 'error', message: string }` | dari session flash |
| `server_time` | server time UTC | konteks waktu server |

Halaman Inertia tidak pernah menjadi otorisasi; keputusan akses final selalu di middleware + Policy + FormRequest + service di server. Visibility data sensitif (selfie, precise GPS) mengikuti matriks PRD dan setiap akses/download sensitive evidence diaudit via activity log.

### 2.2 FormRequest dan validasi

Setiap aksi POST/PATCH/PUT/DELETE memakai class `FormRequest` tersendiri (mis. `StoreDailyReportRequest`, `StoreCheckInRequest`) yang memuat:

- `authorize()` — role + ownership + scope (EOS hanya boleh objek milik assignment aktifnya).
- `rules()` — validasi field-level (tipe, range, enum, format).
- Validasi business lintas-field (rule FR-10, gate clock-out, dst) berada di service dan dilempar sebagai `ValidationException::withMessages()` sehingga error kembali ke form dengan pola yang sama seperti error field-level.

Enum/range/format validasi (checklist v1, tipe mutasi, format attachment, dst) dijelaskan di section domain masing-masing dan HARUS identik dengan `data-dictionary.md`.

### 2.3 Error handling Inertia

| Situasi | Perilaku server | Perilaku UI |
|---|---|---|
| Validasi form gagal | `FormRequest` → redirect back, errors masuk shared prop `errors` | field highlight + pesan per field; input lama dipertahankan |
| Validasi business gagal (service) | `ValidationException::withMessages(['key' => 'pesan'])` → redirect back | sama seperti error field; key bisa key sintetis (mis. `attendance`) bila bukan field |
| Error bag terpisah | aksi memakai `errorBag: 'namaBag'` di opsi submit Inertia `useForm` | hanya bag itu yang dibaca form terkait |
| Otorisasi ditolak | middleware/Policy → `403` | halaman error 403 Inertia (aman, tanpa detail internal) |
| Resource tidak ada / luar scope | `404` (dont leak) | halaman error 404 |
| Konflik state (duplicate submit, dsb) | `ValidationException` dengan key spesifik | toast/inline error; tombol submit di-disable saat proses |
| Rate limited | `RateLimiter` → `429` | halaman error 429 dengan pesan coba lagi |
| Internal error | `500` aman (tanpa SQL/stack/secret) | halaman error 500 |

Kunci error dan pesan aman untuk pengguna terdaftar di katalog §17. Pesan tidak membocorkan SQL, stack trace, secret, file path internal, atau resource yang tidak diizinkan.

Double-submit dicegah oleh kombinasi: unique constraint database (satu record per EOS+site+tanggal, dsb), disabled button selama request berjalan (`useForm.processing`), dan idempotent-by-redirect. Tidak ada idempotency key eksplisit.

### 2.4 Flash message

Sukses aksi selalu redirect (PRG) dengan session flash:

```php
redirect()->route('eos.daily-reports.show', $report)
    ->with('flash', ['type' => 'success', 'message' => 'Report CMX.WR.202610.0001 berhasil disubmit.']);
```

- Tipe: `success` (aksi sukses), `error` (kegagalan yang tetap di-redirect, mis. catatan non-blocking).
- Flash dirender sebagai toast/inline banner; tidak pernah menggantikan error bag validasi.
- Teks flash untuk setiap aksi dicantumkan di section domain (kolom "Flash sukses" atau catatan).

### 2.5 Pagination, filter, dan sorting

Halaman list memakai paginator Laravel yang dikirim ke Inertia sebagai prop (`items`, `filters` echo):

```text
?page=1&per_page=25   (default 25, maksimum 100)
&sort_by=created_at&sort_order=desc
```

- Field sort dan filter adalah allowlist per halaman; field di luar allowlist diabaikan.
- Filter tanggal memakai `date_from`/`date_to` format `YYYY-MM-DD`; data site-local menyertakan konteks timezone site pada prop.
- Tidak ada halaman list tanpa limit.

### 2.6 Rate limit dan lockout

Rate limit memakai `RateLimiter` Laravel dengan driver `database` (cache database; tanpa Redis). Window 15 menit. Endpoint yang mencapai limit menghasilkan halaman `429` dengan pesan coba lagi (sisa detik dihitung server).

| Aksi | Limit (per user terautentikasi) |
|---|---:|
| Check-in / clock-out | 30/menit |
| Upload attachment | 20/menit |
| Submit Daily Report | 10/menit |
| Aksi review (finding resolve/reject/close, report reopen, dsb) | 60/menit |
| Export | 30/menit |

Login lockout via Fortify + RateLimiter: 5 kegagalan dalam 15 menit → lockout 15 menit; dua dimensi counter (identifier ter-normalisasi dan IP) diperiksa sebelum verifikasi kredensial; pesan gagal selalu generik (anti-enumeration: identifier tak dikenal hanya menaikkan counter IP); login sukses me-reset counter identifier; setiap lockout diaudit.

### 2.7 Response non-Inertia

Preview/download attachment dan export adalah streamed response dari controller (bukan `Inertia::render`), dengan otorisasi Policy + audit di server sebelum stream dimulai. `Content-Disposition` aman, `X-Content-Type-Options: nosniff`. File attachment tidak memiliki public URL (local disk private di luar `public/`). Export di-submit sebagai form POST biasa (bukan router Inertia) karena response-nya adalah binary stream, bukan redirect.

## 3. Auth: Login, Logout, Ganti Password

Fortify bawaan starter kit dengan view diganti Inertia.

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `login` `/login` | GET | `Auth/Login` | Guest | — |
| `login.attempt` `/login` | POST | submit → redirect dashboard role | Guest | identifier + password; lockout 5/15 menit; pesan generik |
| `logout` `/logout` | POST | revoke session → redirect `login` | Semua (terautentikasi) | — |
| `password.change` `/change-password` | GET | `Profile/ChangePassword` | Semua | — |
| `password.update` `/change-password` | POST | ganti password → redirect dashboard role | Semua | `old_password` cocok; `new_password` 12–128 |

Form login:

| Field | Rule |
|---|---|
| `identifier` | wajib; email (trim+lowercase) atau employee code (trim+uppercase) |
| `password` | wajib |

Perilaku:

- Login gagal: error bag `default`, pesan tunggal generik "Kredensial tidak valid." (anti-enumeration); setiap kegagalan/lockout diaudit.
- Login sukses: session baru (rotasi), cookie `Secure/HttpOnly/SameSite=Lax`, redirect ke dashboard role (`eos.dashboard`, `supervisor.dashboard`, `manager.dashboard`, `hr.dashboard`, atau `admin.dashboard`). Bila `must_change_password = true`, redirect paksa ke `password.change` oleh middleware `EnsurePasswordChanged`; semua aksi selain change-password ditolak sampai password diganti.
- Idle timeout 30 menit; absolute timeout 8 jam. Session expired → Inertia mengarahkan ulang ke login.
- Change password sukses: revoke semua session lain, rotasi session saat ini, clear `must_change_password`, catat `password_changed_at`, audit event `PASSWORD_CHANGED`. `old_password` salah → pesan generik di error bag.
- Logout: revoke session, audit event, redirect login.

Reset password (bukan self-service) dikelola Super Admin di §3.1.

### 3.1 User management dan reset password (Super Admin)

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `admin.users.index` `/admin/users` | GET | `Admin/Users/Index` (list + filter role/status) | SUPER_ADMIN | — |
| `admin.users.create` `/admin/users/create` | GET | `Admin/Users/Create` | SUPER_ADMIN | — |
| `admin.users.store` `/admin/users` | POST | redirect `admin.users.index` | SUPER_ADMIN | lihat form |
| `admin.users.edit` `/admin/users/{user}/edit` | GET | `Admin/Users/Edit` | SUPER_ADMIN | — |
| `admin.users.update` `/admin/users/{user}` | PATCH | redirect `admin.users.index` | SUPER_ADMIN | lihat form |
| `admin.users.reset-password` `/admin/users/{user}/reset-password` | POST | redirect `admin.users.edit` | SUPER_ADMIN | konfirmasi; audit |
| `admin.roles.index` `/admin/roles` | GET | `Admin/Roles/Index` (5 role fixed + permission per role; read-only) | SUPER_ADMIN | — |

Form user (`admin.users.store`/`admin.users.update`):

| Field | Rule |
|---|---|
| `name` | wajib |
| `email` | wajib, email, unik |
| `employee_code` | wajib, unik (format `EOS-###` untuk role EOS) |
| `role` | wajib; enum `SUPER_ADMIN, MANAGER, SUPERVISOR, HR, EOS`; single role per user; 5 role fixed |
| `status` | `update` saja; `ACTIVE`/`DISABLED`; disable → revoke semua session |

- Duplicate identifier → error field `email`/`employee_code` "Identifier sudah dipakai.".
- Semua perubahan role dan disable user diaudit. Reset password: set hash Argon2id, set `must_change_password`, revoke semua session target, audit; EOS dipaksa ganti password pada login berikutnya. Flash sukses: "Password user direset. User dipaksa mengganti password pada login berikutnya."
- Password policy global: 12–128 karakter, hash Argon2id.

## 4. Dashboard

Route `/` redirect ke dashboard sesuai role terautentikasi.

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `eos.dashboard` `/eos` | GET | `EOS/Dashboard` | EOS | — |
| `supervisor.dashboard` `/supervisor/dashboard` | GET | `Supervisor/Dashboard` | SUPERVISOR | — |
| `manager.dashboard` `/manager/dashboard` | GET | `Manager/Dashboard` | MANAGER | — |
| `hr.dashboard` `/hr/dashboard` | GET | `Hr/Dashboard` | HR | — |
| `admin.dashboard` `/admin` | GET | `Admin/Dashboard` | SUPER_ADMIN | — |

Konten per role:

- **EOS** (`EOS/Dashboard`): workspace hari ini — site aktif (assignment), status attendance (`NOT_CHECKED_IN`/`CHECKED_IN`/`COMPLETED`, record kosong bila belum absen), status Daily Report (`NOT_CREATED`/`DRAFT`/`REOPENED`/`SUBMITTED`), next action tunggal: check-in → create/lanjut draft → submit → clock-out → selesai. Waktu lokal site ditampilkan dari `site.timezone` (snapshot IANA), timestamp tetap UTC canonical.
- **SUPERVISOR**: ringkasan hari ini per site (report submitted, finding OPEN, aset perlu perhatian) dalam scope (semua site aktif).
- **MANAGER**: ringkasan KPI lintas site + shortcut analytics (§14).
- **HR**: ringkasan kehadiran (record bukti kehadiran) scope + shortcut `/hr/attendance`, `/hr/eos-history`.
- **SUPER_ADMIN**: ringkasan sistem + shortcut admin (`/admin/*`).

Next action EOS (kode stabil untuk navigasi UI):

```text
CHECK_IN_AVAILABLE               belum ada record hari ini
CREATE_OR_CONTINUE_DAILY_REPORT  check-in sukses, report belum ada / DRAFT
DAILY_REPORT_SUBMITTED           report sudah SUBMITTED, clock-out belum
DAY_COMPLETED                    check-in + clock-out lengkap
```

Tanpa `NON_WORKING_DAY`/`OUT_OF_WINDOW` (window dan kalender kerja dihapus).

## 5. Site Master dan Network Link

Halaman master berada di area `/supervisor/master/...` (dipakai bersama oleh SUPERVISOR dan MANAGER sesuai hak akses; SUPER_ADMIN mengakses path yang sama — lihat kolom Role).

### 5.1 Site

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `supervisor.master.sites.index` `/supervisor/master/sites` | GET | `Supervisor/Master/Sites/Index` (list + filter status/kode/nama) | SUPERVISOR, MANAGER (read), SUPER_ADMIN | — |
| `supervisor.master.sites.create` `/supervisor/master/sites/create` | GET | `Supervisor/Master/Sites/Create` | SUPER_ADMIN | — |
| `supervisor.master.sites.store` `/supervisor/master/sites` | POST | redirect `sites.index` | SUPER_ADMIN | lihat form |
| `supervisor.master.sites.edit` `/supervisor/master/sites/{site}/edit` | GET | `Supervisor/Master/Sites/Edit` (+ tab network link §5.2) | SUPER_ADMIN | — |
| `supervisor.master.sites.update` `/supervisor/master/sites/{site}` | PATCH | redirect back | SUPER_ADMIN | lihat form + reason |

Form site:

| Field | Rule |
|---|---|
| `site_code` | wajib, unik, format kode site (mis. `SR-JABAR-001`) |
| `school_name` | wajib |
| `address` | wajib |
| `latitude` / `longitude` | wajib; numeric valid range |
| `radius_meters` | opsional, info-only (default 100) — bukan gerbang absensi |
| `timezone` | wajib; enum IANA `Asia/Jakarta`, `Asia/Makassar`, `Asia/Jayapura` |
| `status` | `ACTIVE`/`INACTIVE` |
| `change_reason` | **wajib bila mengubah latitude/longitude/timezone** (selain itu opsional) |

- Perubahan koordinat/timezone wajib `change_reason` + audit event (master change).
- Site tidak boleh diaktifkan bila konfigurasi MAIN/SECONDARY network link belum lengkap → error `site`: "Network link MAIN/SECONDARY belum lengkap; site tidak dapat diaktifkan untuk Daily Report."
- Tidak ada hard delete; nonaktifkan via `status`.

### 5.2 Network link

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `supervisor.master.network-links.store` `/supervisor/master/sites/{site}/network-links` | POST | redirect back (tab network link pada halaman edit site) | SUPER_ADMIN | lihat form |
| `supervisor.master.network-links.update` `/supervisor/master/sites/{site}/network-links/{link}` | PATCH | redirect back | SUPER_ADMIN | lihat form |

Form network link (`site_network_links`):

| Field | Rule |
|---|---|
| `role` | wajib; enum `MAIN`, `SECONDARY` saja |
| `provider_name` | wajib |
| `service_name` | opsional |
| `connection_medium` | wajib; enum `FIBER, WIRELESS, CELLULAR, SATELLITE, OTHER` |
| `subscribed_download.value` / `.unit` | ThroughputValue; unit `KBPS`/`MBPS` |
| `subscribed_upload.value` / `.unit` | ThroughputValue; unit `KBPS`/`MBPS` |
| `active` | boolean |
| `note` | opsional |

- Invariant: tepat satu `MAIN` aktif + satu `SECONDARY` aktif per site aktif — constraint `unique(site_id, role) where active`; pelanggaran → error `role`: "Site sudah memiliki link aktif untuk role ini."
- Konfigurasi link di-snapshot ke Daily Report saat create draft (`network_links_snapshot`). Mutasi link diaudit.

## 6. Assignment EOS

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `supervisor.master.assignments.index` `/supervisor/master/assignments` | GET | `Supervisor/Master/Assignments/Index` (list + filter site/EOS/aktif) | SUPERVISOR, MANAGER (read), SUPER_ADMIN | — |
| `supervisor.master.assignments.create` `/supervisor/master/assignments/create` | GET | `Supervisor/Master/Assignments/Create` | SUPERVISOR, SUPER_ADMIN | — |
| `supervisor.master.assignments.store` `/supervisor/master/assignments` | POST | redirect back | SUPERVISOR, SUPER_ADMIN | lihat form |
| `supervisor.master.assignments.update` `/supervisor/master/assignments/{assignment}` | PATCH | redirect back | SUPERVISOR, SUPER_ADMIN | lihat form |

Form assignment:

| Field | Rule |
|---|---|
| `eos_user_id` | wajib; user role EOS; **tidak boleh sudah punya assignment aktif lain** |
| `site_id` | wajib; site aktif |
| `effective_from` | wajib; `YYYY-MM-DD` |
| `effective_to` | opsional; `>= effective_from` |

- Satu assignment aktif per EOS; pelanggaran → error `eos_user_id`: "EOS sudah memiliki assignment aktif." (assignment aktif lama harus diakhiri dulu via `effective_to`).
- Inventaris melekat ke site (bukan EOS). Mutasi assignment diaudit.
- Scope data EOS (semua halaman EOS) diturunkan dari assignment aktif — ScopeService (EOS: assignment aktif; non-EOS: semua site aktif).

## 7. Attendance — Bukti Kehadiran

Model absensi adalah **bukti kehadiran di site**, bukan disiplin: check-in/clock-out = selfie + koordinat GPS + timestamp server UTC. Tidak ada window jam, late minutes, klasifikasi, geofence gate, kalender kerja, atau Attendance Request (semua dihapus — disiplin kehadiran ditangani absensi vendor EOS).

### 7.1 Check-in

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `eos.attendance.check-in.create` `/eos/attendance/check-in` | GET | `EOS/Attendance/CheckIn` | EOS | — |
| `eos.attendance.check-in.store` `/eos/attendance/check-in` | POST | submit → redirect `eos.dashboard` | EOS | lihat form + business |

Form check-in (multipart — selfie dikirim sebagai file):

| Field | Rule |
|---|---|
| `selfie` | wajib; file image `JPG/PNG/WebP/HEIC` (PDF tidak boleh); ≤ 10 MB; validasi sinkron §11 |
| `latitude` | wajib; numeric |
| `longitude` | wajib; numeric |
| `accuracy_meters` | opsional; numeric (disimpan sebagai informasi) |

UI halaman check-in: overlay lingkaran "posisikan wajah di dalam lingkaran"; deteksi wajah opsional via browser FaceDetector API bila tersedia (indikasi wajah terdeteksi; tombol jepret aktif setelah kamera stabil); fallback overlay panduan saja. Tanpa penyimpanan biometrik, tanpa face-matching server-side.

Perilaku server (`eos.attendance.check-in.store`):

- Wajib assignment aktif; tanpa assignment → error: "Tidak ada assignment aktif." (key `attendance`).
- GPS diambil sekali dengan toleransi longgar; jarak ke site dihitung Haversine di PHP dan **disimpan sebagai informasi** (`distance_meters`) — tanpa penolakan radius.
- `check_in_at` = timestamp server UTC; tanggal lokal site (dari `site.timezone`) menentukan `work_date_local`.
- Unique: satu record per EOS + site + `work_date_local`; check-in kedua ditolak → error: "Sudah check-in untuk tanggal ini." (key `attendance`).
- Status record: `NOT_CHECKED_IN` → `CHECKED_IN`. Maksimum 1 selfie check-in.
- Attempt gagal tetap diaudit.
- Flash sukses: "Check-in tercatat pukul {HH:MM} waktu site."

### 7.2 Clock-out

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `eos.attendance.clock-out.create` `/eos/attendance/clock-out` | GET | `EOS/Attendance/ClockOut` | EOS (status `CHECKED_IN`) | — |
| `eos.attendance.clock-out.store` `/eos/attendance/clock-out` | POST | submit → redirect `eos.dashboard` | EOS | lihat form + business |

Form clock-out: schema selfie + GPS sama dengan check-in (maksimum 1 selfie clock-out).

Aturan server:

- Record attendance hari ini harus berstatus `CHECKED_IN`; tanpa check-in → error: "Belum check-in hari ini." (key `attendance`).
- Clock-out harus pada **tanggal lokal yang sama** dengan check-in.
- **Gate**: Daily Report tanggal tsb wajib `SUBMITTED` dan semua required evidence `AVAILABLE`. Bila belum → error: "Daily Report tanggal ini belum disubmit. Lengkapi report terlebih dahulu." (key `attendance`) — flow kontinu: halaman error mengarahkan EOS melengkapi report (tombol ke `eos.daily-report.current`).
- Clock-out kedua ditolak → error: "Sudah clock-out untuk tanggal ini."
- Status record menjadi `COMPLETED`; `clock_out_at` = timestamp server UTC.
- Flash sukses: "Clock-out tercatat pukul {HH:MM} waktu site. Hari selesai."

Kehadiran hari tanpa absen = kosongnya record (urusan vendor) — dashboard menampilkan kondisi "belum ada record" tanpa penandaan disiplin.

### 7.3 Riwayat attendance

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `eos.attendance.index` `/eos/attendance` | GET | `EOS/Attendance/Index` (list own; filter `date_from`, `date_to`) | EOS | scope assignment aktif |
| `eos.attendance.show` `/eos/attendance/{record}` | GET | `EOS/Attendance/Show` | EOS (own) | ownership via Policy |
| `supervisor.attendance.index` `/supervisor/attendance` | GET | `Supervisor/Attendance/Index` (list scope; filter `date_from`, `date_to`, `site_id`, `eos_user_id`) | SUPERVISOR | filter allowlist |
| `supervisor.attendance.show` `/supervisor/attendance/{record}` | GET | `Supervisor/Attendance/Show` | SUPERVISOR (scope) | scope via Policy |
| `hr.attendance.index` `/hr/attendance` | GET | `Hr/Attendance/Index` (list scope; filter `date_from`, `date_to`, `eos_user_id`) | HR | filter allowlist |
| `hr.eos-history.index` `/hr/eos-history` | GET | `Hr/EosHistory/Index` (riwayat per EOS; filter `eos_user_id`, `date_from`, `date_to`) | HR | `eos_user_id` wajib |

- EOS hanya menerima record miliknya (scope assignment aktif; server menolak `site_id`/`eos_user_id` arbitrer dari EOS).
- Backoffice menerima record sesuai role/scope/filter. Data sensitif (selfie, precise GPS, accuracy) hanya dirender sesuai visibility matrix; akses sensitive evidence oleh role berwenang diaudit.
- Display memakai waktu lokal site (`site.timezone` snapshot), timestamp canonical UTC.

## 8. Daily Report

Nilai inti sistem: satu report per EOS + site + `work_date_local` (unique). Checklist master versioned: 1 tabel versi + struktur JSON (`checklist_versions`: `version`, status `DRAFT/PUBLISHED/SUPERSEDED/RETIRED`, struktur JSON section/item/option/rule); satu template global `DAILY_SITE_REPORT`; published immutable. Nomor `CMX.WR.YYYYMM.SEQUENCE` via PostgreSQL SEQUENCE global bigint, dialokasikan atomik hanya saat submit sukses.

### 8.1 Route

Halaman form EOS memakai path tunggal `/eos/daily-report/current` (report untuk work date berjalan); halaman show EOS memakai `/eos/daily-reports/{report}` (riwayat); Supervisor memakai `/supervisor/daily-reports`.

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `eos.daily-report.current` `/eos/daily-report/current` | GET | `EOS/DailyReport/Form` (form report hari ini; empty state + tombol buat draft bila belum ada) | EOS | assignment aktif |
| `eos.daily-report.store` `/eos/daily-report` | POST | create draft → redirect `eos.daily-report.current` | EOS | lihat catatan |
| `eos.daily-report.update` `/eos/daily-report/current` | PATCH | save draft → redirect back | EOS owner (DRAFT/REOPENED) | lihat §8.3 |
| `eos.daily-report.submit` `/eos/daily-report/current/submit` | POST | submit → redirect `eos.daily-reports.show` | EOS owner | semua rules FR-10; lihat §8.4 |
| `eos.daily-reports.index` `/eos/daily-reports` | GET | `EOS/DailyReports/Index` (list own; filter `status`, `date_from`, `date_to`) | EOS (own) | scope assignment |
| `eos.daily-reports.show` `/eos/daily-reports/{report}` | GET | `EOS/DailyReports/Show` (read-only bila SUBMITTED) | EOS owner | ownership via Policy |
| `supervisor.daily-reports.index` `/supervisor/daily-reports` | GET | `Supervisor/DailyReports/Index` (list scope; filter `site_id`, `eos_user_id`, `status`, `date_from`, `date_to`, `has_ap_offline`, `connectivity_status`) | SUPERVISOR, MANAGER (read), SUPER_ADMIN | filter allowlist |
| `supervisor.daily-reports.show` `/supervisor/daily-reports/{report}` | GET | `Supervisor/DailyReports/Show` (+ tombol reopen) | SUPERVISOR (scope), MANAGER (read), SUPER_ADMIN | scope via Policy |
| `supervisor.daily-reports.reopen` `/supervisor/daily-reports/{report}/reopen` | POST | reopen → redirect `supervisor.daily-reports.show` | SUPERVISOR (scope), SUPER_ADMIN | reason wajib; ≤ 7 hari kalender |

- `eos.daily-report.store` tidak menerima payload pilihan site/EOS/tanggal — server menurunkan EOS, assignment aktif, site, `work_date_local`, dan checklist version `PUBLISHED` terkini. Draft tidak punya `report_number`. Report untuk work date yang sudah ada → error: "Report untuk tanggal ini sudah ada."
- Draft create flash: "Draft report {tanggal} dibuat."
- Report `SUBMITTED` read-only untuk EOS; `REOPENED` menampilkan `reopen_reason` + `revision` dan editable oleh EOS owner di `eos.daily-report.current`.
- Report menyimpan snapshot: definisi checklist (`checklist_version_id` + `checklist_snapshot` JSONB), site, EOS, network links (dual-link).
- Status lifecycle: `DRAFT → SUBMITTED → REOPENED → (resubmit) SUBMITTED`; `VOIDED` untuk pembatalan administratif. Reopen: reason wajib, maksimum 7 hari kalender setelah submit → error: "Batas 7 hari kalender reopen sudah terlewati." (key `reason`); actor/time/reason diaudit; resubmit menambah `revision` tanpa mengubah nomor; reopen bukan approval flow. `SUBMITTED` immutable kecuali reopen.

### 8.2 Form fields (fill draft)

Payload `eos.daily-report.update` memakai answer list (bukan bentuk tabel):

```json
{
  "answers": [
    {
      "template_item_id": "uuid",
      "value_text": "14 hari 06 jam",
      "value_numeric": null,
      "value_boolean": null,
      "value_json": null,
      "note": null,
      "is_not_applicable": false,
      "attachment_ids": []
    }
  ],
  "section_evidence": [
    { "section_id": "uuid", "attachment_ids": ["uuid"] }
  ]
}
```

Server memvalidasi: ownership, `template_item_id`/`section_id` harus termasuk dalam `checklist_snapshot` report (item dari template lain ditolak), tipe/range/unit data, attachment milik + status `AVAILABLE` + limit context (§11.3), dan rule note/evidence kondisional (§8.4 — divalidasi penuh saat submit; saat save draft hanya validasi struktur agar draft dapat disimpan parsial).

Item bertipe `LINK_TRAFFIC` dan `SPEEDTEST_RESULT` memakai `value_json` dengan schema §8.5.

### 8.3 Rules validasi FR-10 (checklist v1 — semua dipertahankan)

Section dan item:

#### Info Umum (read-only)

| Item | Tipe input | Ketentuan |
|---|---|---|
| ID Laporan | `READ_ONLY` | otomatis saat submit (report number) |
| Tanggal | `READ_ONLY` | tanggal lokal site |
| Nama Sekolah | `READ_ONLY` | dari site assignment |
| Nama Petugas | `READ_ONLY` | dari akun EOS |

Evidence section: optional, 0–5 attachment.

#### Router & Firewall

| Item | Tipe input | Wajib |
|---|---|---:|
| Uptime | `DURATION` | Ya |
| Utilisasi CPU | `PERCENTAGE`, 0–100 | Ya |
| Utilisasi RAM | `PERCENTAGE`, 0–100 | Ya |
| Log Anomali | `ENUM`: `NONE, MINOR, MAJOR, NOT_CHECKED` | Ya |

Evidence section: required, 1–5 attachment. Rules: `MINOR` → note wajib; `MAJOR` → note + item evidence `AVAILABLE` wajib; `NOT_CHECKED` → note alasan wajib.

#### Access Point

| Item | Tipe input | Wajib |
|---|---|---:|
| Status Monitoring AP | `ENUM`: `NORMAL, WARNING, DOWN, UNKNOWN` | Ya |
| Jumlah AP Offline | integer 0–10.000 | Ya |
| Dokumentasi AP di Cloud | attachment evidence | Ya |

Evidence section: required, 1–5 attachment. Rules: `NORMAL` → AP offline harus 0; `WARNING`/`DOWN` → AP offline wajib ≥ 1; `UNKNOWN` → note alasan wajib, count boleh null; Dokumentasi AP Cloud → minimum 1 attachment `AVAILABLE` wajib.

#### Infrastruktur & Lingkungan

| Item | Tipe input | Wajib |
|---|---|---:|
| Pengecekan Kelistrikan | `ENUM`: `NORMAL, UNSTABLE, OUTAGE, BACKUP_ACTIVE, NOT_CHECKED` | Ya |
| Suhu Ruangan Server | `NUMBER`, Celsius, satu desimal, range −20.0–80.0 | Ya |
| Kondisi Lingkungan | `ENUM`: `GOOD, ATTENTION, UNFIT, NOT_CHECKED` | Ya |

Evidence section: required, 1–5 attachment. Rules: suhu wajib diisi (alat tersedia di site; tidak ada `NOT_MEASURED`); Kelistrikan `UNSTABLE`/`OUTAGE`/`BACKUP_ACTIVE` → note + item evidence `AVAILABLE` wajib; Kelistrikan `NOT_CHECKED` → note alasan wajib; Lingkungan `ATTENTION` → note wajib; `UNFIT` → note + item evidence wajib; `NOT_CHECKED` → note alasan wajib.

#### Konektivitas

| Item | Tipe input | Wajib |
|---|---|---:|
| Utilisasi Main Link (`MAIN_LINK_TRAFFIC`) | `LINK_TRAFFIC` | Ya |
| Utilisasi Secondary Link (`SECONDARY_LINK_TRAFFIC`) | `LINK_TRAFFIC` | Ya |
| Connection Test Main Link (`MAIN_LINK_SPEEDTEST`) | `SPEEDTEST_RESULT` | Ya |
| Connection Test Secondary Link (`SECONDARY_LINK_SPEEDTEST`) | `SPEEDTEST_RESULT` | Ya |

Evidence section: required, 1–5 attachment.

Aturan evidence global (semua section): semua section operasional wajib minimal satu attachment `AVAILABLE`; maksimum 5 attachment per section evidence; maksimum 5 per item evidence; maksimum 10 attachment per Daily Report (hitungan sederhana semua attachment pada report, tanpa dedup lintas context); satu attachment hanya teraut ke tepat satu context — file sama boleh diunggah ulang untuk context lain; screenshot Main Link tidak otomatis menjadi evidence Secondary Link (wajib upload terpisah).

Error message untuk setiap rule mengikuti pola key = `answers.{template_item_id}` (atau `answers.{template_item_id}.note`/`.attachment_ids` untuk sub-field), contoh: "Log Anomali MAJOR wajib disertai note." / "Log Anomali MAJOR wajib disertai evidence attachment."

### 8.4 Submit

`eos.daily-report.submit`:

- Server memvalidasi semua required answer/evidence + seluruh rules §8.3/§8.5 secara penuh (eksplisit per rule di kode FormRequest/service — bukan interpreter JSON generik); kegagalan → redirect back dengan detail error per item (error bag `submitReport`).
- Status harus `DRAFT`/`REOPENED`; submit pada status lain → error: "Report tidak dalam status yang dapat disubmit."
- Semua required evidence harus `AVAILABLE` (upload tervalidasi sinkron §11, jadi tidak ada status intermediate; attachment `REJECTED` tidak dapat memenuhi submit → error per item).
- Submit transaksional: alokasi nomor via SEQUENCE PostgreSQL atomik hanya saat sukses; `YYYYMM` memakai tanggal lokal site saat submit; format `CMX.WR.YYYYMM.SEQUENCE` (prefix `CMX` fixed, bukan konfigurasi).
- Flash sukses: "Report {report_number} berhasil disubmit." Bila `revision > 0`: "Report {report_number} (revisi {n}) berhasil disubmit ulang."
- Setelah submit, halaman show menandai `can_clock_out` (prop) untuk mengarahkan EOS ke clock-out.

### 8.5 Value schema konektivitas

`ThroughputValue`: `{ value, unit, normalized_kbps }` — unit hanya `KBPS | MBPS` (Gbps, KB/s, MB/s, byte-per-second tidak diterima); `normalized_kbps` dihitung backend (KBPS = nilai sama; MBPS × 1000).

`LINK_TRAFFIC` (measurement window tetap 08:00–17:00 site-local):

```json
{
  "status": "AVAILABLE | DOWN | NOT_CHECKED",
  "avg_inbound": "ThroughputValue",
  "peak_inbound": "ThroughputValue",
  "avg_outbound": "ThroughputValue",
  "peak_outbound": "ThroughputValue",
  "source": "Dashboard ISP",
  "note": null
}
```

- `AVAILABLE`: empat nilai traffic + source + item evidence `AVAILABLE` wajib.
- `DOWN`: note + item evidence `AVAILABLE` wajib.
- `NOT_CHECKED`: note alasan wajib.
- BUKAN utilization percentage.

`SPEEDTEST_RESULT` (dua item terpisah wajib untuk MAIN dan SECONDARY, dijalankan manual oleh EOS sebelum submit):

```json
{
  "status": "SUCCESS | FAILED | NOT_TESTED",
  "link_role": "MAIN | SECONDARY",
  "download": "ThroughputValue",
  "upload": "ThroughputValue",
  "latency_ms": 12,
  "jitter_ms": 3,
  "packet_loss_percent": 0.0,
  "server_name": null,
  "route_declaration": "TESTED_VIA_MAIN_LINK | TESTED_VIA_SECONDARY_LINK",
  "note": null
}
```

- `SUCCESS`: download, upload, latency, jitter, screenshot `AVAILABLE` wajib.
- `FAILED`/`NOT_TESTED`: note alasan wajib; bila link down gunakan `FAILED`/`NOT_TESTED` dengan reason `Link down`.
- `route_declaration` adalah deklarasi (bukan verifikasi otomatis actual network path); Secondary test wajib mengikuti safe routing/failover SOP (Open Operational Configuration).

## 9. Checklist Version

Checklist tidak hard-coded: master versioned dengan struktur JSON di `checklist_versions` (section/item/option/rule). Satu template global `DAILY_SITE_REPORT`. Halaman berada di `/admin/checklists` (full, Super Admin) dan `/supervisor/master/checklists` (read + draft authoring Supervisor/Manager — usulan menjadi input keputusan publish Super Admin).

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `admin.checklist-versions.index` `/admin/checklists` | GET | `Admin/Checklists/Index` (list + histori status) | SUPER_ADMIN; SUPERVISOR, MANAGER (read) | — |
| `supervisor.master.checklists.index` `/supervisor/master/checklists` | GET | `Supervisor/Master/Checklists/Index` (read + histori; draft authoring) | SUPERVISOR, MANAGER, SUPER_ADMIN | — |
| `supervisor.master.checklists.create` `/supervisor/master/checklists/create` | GET | `Supervisor/Master/Checklists/Create` | SUPER_ADMIN, MANAGER, SUPERVISOR | — |
| `supervisor.master.checklists.store` `/supervisor/master/checklists` | POST | redirect `.../edit` | SUPER_ADMIN, MANAGER, SUPERVISOR | lihat form |
| `supervisor.master.checklists.edit` `/supervisor/master/checklists/{version}/edit` | GET | `Supervisor/Master/Checklists/Edit` (editor struktur JSON) | author draft, SUPER_ADMIN | status harus `DRAFT` |
| `supervisor.master.checklists.update` `/supervisor/master/checklists/{version}` | PATCH | redirect back | idem | status harus `DRAFT` |
| `supervisor.master.checklists.show` `/supervisor/master/checklists/{version}` | GET | `Supervisor/Master/Checklists/Show` (detail + histori) | SUPERVISOR, MANAGER, SUPER_ADMIN | — |
| `supervisor.master.checklists.publish` `/supervisor/master/checklists/{version}/publish` | POST | redirect `show` | **SUPER_ADMIN saja** | struktur valid |

- Membuat version baru → status `DRAFT`. Manager/Supervisor dapat mengusulkan perubahan dengan membuat/mengedit draft; keputusan publish hanya Super Admin.
- Edit/publish pada version non-`DRAFT` ditolak → error: "Hanya version DRAFT yang dapat diubah/dipublish."
- Publish: struktur item/rule divalidasi (section wajib, tipe input dikenal, rule konsisten); gagal → error detail per bagian struktur; sukses → version menjadi `PUBLISHED` dan immutable, version `PUBLISHED` sebelumnya menjadi `SUPERSEDED`; audit event. Lifecycle: `DRAFT | PUBLISHED | SUPERSEDED | RETIRED`.
- Report menyimpan `checklist_version_id` + `checklist_snapshot` JSONB; perubahan standard rule/option/urutan via master data; tipe input baru membutuhkan release frontend/backend.

## 10. Inventaris

Model: `assets` + `inventory_items` (catalog) + `inventory_stock` (saldo per site+item) + `inventory_transactions` (ledger immutable) + `inventory_findings`. Barang material/sparepart = stok kuantitas per site; aset = unit terdaftar.

### 10.1 Aset — registrasi oleh EOS

Aset diregistrasi **EOS** saat barang datang dari gudang (bukan Supervisor). Tag asset sudah ada dari gudang Comtronics — EOS input apa adanya; serial number diisi bila perangkat punya SN (opsional/nullable, input apa adanya); barang tidak berpindah antar site; rusak → dikembalikan ke gudang.

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `eos.inventory.assets.index` `/eos/inventory/assets` | GET | `EOS/Inventory/Assets/Index` (list site assignment; filter status/kategori) | EOS | — |
| `eos.inventory.assets.create` `/eos/inventory/assets/create` | GET | `EOS/Inventory/Assets/Create` | EOS | — |
| `eos.inventory.assets.store` `/eos/inventory/assets` | POST | redirect `eos.inventory.assets.index` | EOS (assignment aktif) | lihat form |
| `eos.inventory.assets.show` `/eos/inventory/assets/{asset}` | GET | `EOS/Inventory/Assets/Show` (+ histori status) | EOS (site assignment) | scope |
| `eos.inventory.assets.status-change` `/eos/inventory/assets/{asset}/status-change` | POST | redirect `assets.show` | EOS site; SUPERVISOR scope; SUPER_ADMIN | lihat form |
| `supervisor.inventory.assets.index` `/supervisor/inventory/assets` | GET | `Supervisor/Inventory/Assets/Index` (list scope) | SUPERVISOR, MANAGER (read), SUPER_ADMIN | — |
| `supervisor.inventory.assets.show` `/supervisor/inventory/assets/{asset}` | GET | `Supervisor/Inventory/Assets/Show` | idem | scope |

Form registrasi aset:

| Field | Rule |
|---|---|
| `asset_tag` | wajib; regex format `CMX.{SITE_CODE}.{CATEGORY}.{SEQ}` (site code dari assignment); unik |
| `serial_number` | opsional (nullable); bila diisi harus unik; input apa adanya dari gudang (bila perangkat punya SN) |
| `category_id` | wajib; asset category aktif |
| `name` / `description` | opsional |
| `photo` (`attachment_ids`) | **wajib minimal 1** — foto barang saat registrasi |
| `initial_status` | default `IN_USE`; enum status |

- Validasi regex + unik di server; pelanggaran → error field terkait ("Format asset tag tidak sesuai." / "Asset tag sudah terdaftar.").
- Flash sukses: "Aset {asset_tag} teregistrasi."
- Status aset 6 nilai: `IN_USE, SPARE, RETURNED, DAMAGED, LOST, DISPOSED`.

Form perubahan status aset:

| Field | Rule |
|---|---|
| `new_status` | wajib; enum 6 status; transisi valid |
| `reason` | wajib |
| `photo` (`attachment_ids`) | **wajib bila `DAMAGED`/`LOST`** (foto kondisi) |

- Perubahan status = transaksi beralasan + audit + foto bila rusak/hilang. Flash: "Status aset diubah menjadi {status}."

### 10.2 Mutasi stok

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `eos.inventory.stock.index` `/eos/inventory/stock` | GET | `EOS/Inventory/Stock/Index` (saldo site assignment; filter item) | EOS | — |
| `supervisor.inventory.stock.index` `/supervisor/inventory/stock` | GET | `Supervisor/Inventory/Stock/Index` (saldo scope; filter site) | SUPERVISOR, MANAGER (read), SUPER_ADMIN | — |
| `supervisor.inventory.transactions.index` `/supervisor/inventory/transactions` | GET | `Supervisor/Inventory/Transactions/Index` (ledger + filter) | SUPERVISOR, MANAGER (read), SUPER_ADMIN | — |
| `supervisor.inventory.transactions.store` `/supervisor/inventory/transactions` | POST | redirect `supervisor.inventory.stock.index` | SUPERVISOR (scope), SUPER_ADMIN | lihat form |

Form mutasi:

| Field | Rule |
|---|---|
| `site_id` | wajib; site aktif dalam scope |
| `inventory_item_id` | wajib; catalog item aktif |
| `type` | wajib; enum `RECEIPT, USAGE, ADJUSTMENT, DAMAGED, LOST, RETURN, TRANSFER_IN, TRANSFER_OUT` |
| `quantity` | wajib; decimal > 0 |
| `note` | **wajib untuk `ADJUSTMENT`, `DAMAGED`, `LOST`, `TRANSFER_IN`, `TRANSFER_OUT`** |
| `attachment_ids` | maksimum 5 |

- Mutasi posted immutable; koreksi memakai compensating mutation dengan `reversal_of_mutation_id`.
- Saldo tidak boleh negatif — validasi dengan row lock (`lockForUpdate`); pelanggaran → error `quantity`: "Stok tidak cukup untuk mutasi ini."
- `TRANSFER_IN`/`TRANSFER_OUT` berpasangan antar site bila transfer antar site terjadi (admin); inventaris melekat site untuk EOS.
- Mutasi diaudit. Flash: "Mutasi {type} tercatat."

### 10.3 Inventory finding

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `eos.inventory.findings.index` `/eos/inventory/findings` | GET | `EOS/Inventory/Findings/Index` (list site assignment; filter status/type) | EOS | — |
| `eos.inventory.findings.create` `/eos/inventory/findings/create` | GET | `EOS/Inventory/Findings/Create` | EOS | — |
| `eos.inventory.findings.store` `/eos/inventory/findings` | POST | redirect `findings.index` | EOS (assignment) | lihat form |
| `eos.inventory.findings.show` `/eos/inventory/findings/{finding}` | GET | `EOS/Inventory/Findings/Show` (+ histori review) | EOS (site assignment) | scope |
| `supervisor.inventory.findings.index` `/supervisor/inventory/findings` | GET | `Supervisor/Inventory/Findings/Index` (list scope; filter status/type/site) | SUPERVISOR, MANAGER (read), SUPER_ADMIN | — |
| `supervisor.inventory.findings.show` `/supervisor/inventory/findings/{finding}` | GET | `Supervisor/Inventory/Findings/Show` (+ aksi review) | idem | scope |
| `supervisor.inventory.findings.start-review` `/supervisor/inventory/findings/{finding}/start-review` | POST | redirect `findings.show` | SUPERVISOR (scope), SUPER_ADMIN | status `OPEN` |
| `supervisor.inventory.findings.resolve` `/supervisor/inventory/findings/{finding}/resolve` | POST | redirect `findings.show` | idem | status `UNDER_REVIEW`; note wajib |
| `supervisor.inventory.findings.reject` `/supervisor/inventory/findings/{finding}/reject` | POST | redirect `findings.show` | idem | status `UNDER_REVIEW`; reason wajib |
| `supervisor.inventory.findings.close` `/supervisor/inventory/findings/{finding}/close` | POST | redirect `findings.show` | idem | status `RESOLVED`/`REJECTED` |

Form finding (EOS):

| Field | Rule |
|---|---|
| `finding_type` | wajib; enum `ASSET_DAMAGED, ASSET_MISSING, ASSET_DATA_MISMATCH, STOCK_LOW, STOCK_DAMAGED, ASSET_REGISTRATION_NEEDED, OTHER` |
| `asset_id` | opsional; aset di site assignment |
| `description` | wajib |
| `attachment_ids` | wajib minimal 1; maksimum 5; status `AVAILABLE`; image saja (PDF tidak boleh) |

- Reporter dan site diturunkan server dari assignment EOS. Status awal `OPEN`.
- Aksi supervisor: reason/notes sesuai transisi status + audit; finding tidak langsung memutasi stok/aset — perubahan inventaris tetap melalui aksi §10.1/§10.2 yang berotorisasi.
- Status tidak sesuai transisi → error: "Finding tidak dalam status yang dapat diproses aksi ini."

### 10.4 Master inventaris (backoffice)

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `supervisor.master.asset-categories.*` `/supervisor/master/asset-categories...` | GET/POST/PATCH | `Supervisor/Master/AssetCategories/...` | SUPER_ADMIN, MANAGER, SUPERVISOR | nama wajib, unik |
| `supervisor.master.catalog.*` `/supervisor/master/catalog...` | GET/POST/PATCH | `Supervisor/Master/Catalog/...` (inventory items catalog) | SUPER_ADMIN, MANAGER, SUPERVISOR | sku/nama wajib, unik; unit |

- Semua mutasi master: konfirmasi UX, otorisasi server, validasi input, audit; tidak ada hard delete (pakai status/retirement).

## 11. Attachment

Local disk private (`storage/app`, di luar `public/`). Format `JPG/JPEG, PNG, WebP, HEIC/HEIF, PDF`; maksimum 10 MB per file. **Validasi sinkron di request** — magic byte, size, hash SHA-256, decode-safe — file yang lolos langsung `AVAILABLE` (tidak ada QUARANTINED/PROCESSING); yang gagal ditolak dengan pesan aman per field. Thumbnail/preview WebP (max 2048 px / 480 px) dibuat via **queued job** (Intervention Image, Imagick + libheif untuk HEIC) setelah file `AVAILABLE`; thumbnail tidak memblokir submit.

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `attachments.store` `/attachments` | POST | upload (multipart) → redirect back dengan flash (PRG, §2.4); metadata attachment tersedia via shared prop untuk form — bukan response JSON (binary stream hanya untuk download/preview, §2.7) | Sesuai context upload | validasi sinkron |
| `attachments.show` `/attachments/{attachment}` | GET | metadata (dipakai form untuk status) | owner/scope | — |
| `attachments.preview` `/attachments/{attachment}/preview` | GET | streamed variant preview/thumbnail | Policy + visibility matrix | audit sensitive |
| `attachments.download` `/attachments/{attachment}/download` | GET | streamed original | Policy (role/use case evidence asli) | audit selalu |

Form upload (`attachments.store`):

| Field | Rule |
|---|---|
| `attachment_type` | wajib; enum `CHECK_IN_SELFIE, CHECK_OUT_SELFIE, SECTION_EVIDENCE, ITEM_EVIDENCE, LINK_TRAFFIC_EVIDENCE, SPEEDTEST_EVIDENCE, AP_CLOUD_EVIDENCE, ANOMALY_LOG, FINDING_EVIDENCE, ASSET_REGISTRATION_PHOTO, ASSET_STATUS_PHOTO, MUTATION_EVIDENCE, OTHER` |
| `intended_entity_type` | opsional; `DAILY_REPORT, DAILY_REPORT_SECTION, DAILY_REPORT_ITEM, ASSET, INVENTORY_FINDING, INVENTORY_MUTATION` |
| `intended_entity_id` | opsional; UUID entity bila sudah ada |
| `file` | wajib; format/size sesuai aturan |

Aturan format per context:

```text
General : JPEG, PNG, WebP, HEIC/HEIF, PDF
Selfie  : JPEG, PNG, WebP, HEIC/HEIF (tidak PDF)
```

PDF tidak boleh untuk selfie dan inventory mutation/finding evidence. Server membuat object key (UUID) dan tidak pernah memakai path file client. Error upload (ukuran/format/signature/decode) dikirim sebagai error bag upload (`errorBag: 'upload'`) sehingga form utama tidak ikut ter-reset.

Limit per context (harus konsisten dengan §8.3):

| Context | Limit |
|---|---|
| Daily Report (total semua context dalam report) | 10 |
| Section evidence | 5 |
| Item evidence | 5 |
| Check-in selfie | 1 |
| Clock-out selfie | 1 |
| Inventory mutation | 5 |
| Inventory finding | 5 |

Satu attachment hanya terhubung tepat satu context (dipilih saat upload via intended entity; tidak dapat dipindah/ditambah ke context kedua); pelanggaran limit/context → error field upload terkait ("Melebihi limit attachment untuk context ini.").

Lifecycle status: `AVAILABLE` | `REJECTED`. Original immutable dan private; derivative (preview/thumbnail) menghapus EXIF/GPS metadata untuk privacy, preserve aspect ratio, no upscaling, apply EXIF orientation sebelum derivative; original tidak pernah digantikan derivative. Semua download original dan akses preview sensitive diaudit. Attachment `REJECTED` tidak tersedia bagi user biasa + audit event + notifikasi `ATTACHMENT_REJECTED`.

## 12. Notifikasi

Tabel `notifications` Laravel (database channel) + polling dari halaman (partial reload interval; tanpa websocket). In-app only. Notification bukan authority; source record dan audit adalah authority.

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `notifications.index` `/notifications` | GET | `Notifications/Index` (Notification Center; badge dari `auth.unread_notifications`) | Semua (terautentikasi) | — |
| `notifications.read` `/notifications/{notification}/read` | POST | redirect back | owner | notification milik user |
| `notifications.read-all` `/notifications/read-all` | POST | redirect back | idem | idempotent |

Event minimum (`notification_type`):

```text
DAILY_REPORT_REOPENED      report dibuka ulang oleh Supervisor/Super Admin (untuk EOS owner)
ATTACHMENT_REJECTED        attachment gagal validasi (untuk uploader)
```

(Event attendance-request dihapus bersama modulnya.) `read-all` menandai semua notifikasi unread milik user; flash: "Semua notifikasi ditandai dibaca."

## 13. Export

Tiga format output: **xlsx styled** (header bold, border, lebar kolom auto, judul + periode di header sheet, filename dinamis), **PDF formal** (header instansi, siap cetak), **CSV** (data mentah). Kustomisasi: pilih kolom (checkbox per kolom), filter periode/site/status/EOS, disimpan sebagai **preset per user** (dipakai ulang). Sinkron streamed; audit event sebelum stream dimulai.

Panel export di-embed pada halaman data backoffice (`supervisor.attendance.index`, `supervisor.daily-reports.index`, `supervisor.inventory.*`, `hr.attendance.index`) sebagai tombol/panel yang membuka form export (field sama dengan `exports.create`); submit panel memakai route `exports.download` yang sama. Panel pada halaman Supervisor dibatasi site scope; panel pada halaman HR hanya menawarkan `data_type ATTENDANCE` sesuai otorisasi scope.

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `exports.create` `/exports` | GET | `Exports/Create` (form pilih kolom + filter + preset; pratinjau kolom) | MANAGER, SUPERVISOR, SUPER_ADMIN, HR (`data_type ATTENDANCE` saja, sesuai otorisasi scope) | — |
| `exports.download` `/exports` | POST | streamed binary (xlsx/pdf/csv) — submit form biasa, bukan router Inertia (§2.7) | MANAGER, SUPERVISOR, SUPER_ADMIN, HR (`data_type ATTENDANCE` saja, sesuai otorisasi scope) | lihat parameter |
| `export-presets.store` `/export-presets` | POST | redirect `exports.create` | MANAGER, SUPERVISOR, SUPER_ADMIN, HR | lihat form |
| `export-presets.update` `/export-presets/{preset}` | PATCH | redirect back | owner preset | preset milik user |
| `export-presets.destroy` `/export-presets/{preset}` | DELETE | redirect `exports.create` | owner preset | — |

Parameter `exports.download` (juga field preset):

| Parameter | Wajib | Keterangan |
|---|---:|---|
| `data_type` | Ya | `ATTENDANCE, DAILY_REPORT, INVENTORY, ASSET, INVENTORY_FINDING` |
| `format` | Ya | `XLSX, PDF, CSV` |
| `columns[]` | Ya | allowlist kolom per `data_type` |
| `date_from` / `date_to` | Ya | periode; `YYYY-MM-DD`; konsisten |
| `site_id` | Tidak | tanpa parameter = semua site dalam scope actor |
| `status` / `eos_user_id` | Tidak | filter sesuai `data_type` |

- Kolom/filter/sort di luar allowlist ditolak → error form.
- Privacy visibility di-enforce: Manager tidak boleh meng-export raw selfie/precise GPS/sensitive attachment → error: "Pilihan kolom melanggar batas visibility data." Filter/scope di luar scope actor ditolak sama. HR hanya boleh `data_type ATTENDANCE` sesuai otorisasi scope kehadiran; `data_type` lain oleh HR ditolak dengan error yang sama.
- Audit event export (actor, role, data_type, format, kolom, filter, scope, timestamp) ditulis sinkron sebelum stream dimulai.
- Filename dinamis (mis. `daily-reports-2026-10_SRX.xlsx`), `Content-Type` sesuai format, streamed response.
- Export `ATTENDANCE` memuat field bukti kehadiran (`check_in_local`, `clock_out_local`, `status`, jarak info); tanpa kolom late/disiplin (dihapus). Penandaan visual adalah urusan UI, bukan data export.

## 14. Analytics Manager

Read-only, server-side aggregated; tanpa mutasi.

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `manager.analytics.show` `/manager/analytics/{type}` | GET | `Manager/Analytics/Show` (KPI + drill-down; filter scope) | MANAGER (read), SUPER_ADMIN | `type` enum; filter allowlist |

- `{type}` enum: `attendance`, `daily-reports`, `inventory`, `inventory-findings`.
- Filter wajib dalam scope aktor (semua site aktif); response menyertakan filter echo, konteks timezone, periode sumber, nilai KPI, dan identifier drill-down (link ke halaman list terkait).
- Query mahal memakai pagination/timeout; cache tidak menggantikan kebenaran PostgreSQL.

## 15. Audit Log, Storage Usage, dan Konfigurasi (Admin)

Audit aplikasi memakai `spatie/laravel-activitylog` (tabel `activity_log`) dipasang di titik kritis eksplisit: login sukses/gagal/lockout, sensitive access (preview/download attachment, view audit), master change (site/network link/assignment/checklist/user role), transisi report (submit/reopen/resubmit), transisi status aset, mutasi stok, keputusan finding, export, reset password, change password.

| Route | Method | Page / Action | Role | Validasi kunci |
|---|---|---|---|---|
| `admin.audit-logs.index` `/admin/audit-logs` | GET | `Admin/AuditLogs/Index` (list + filter `causer`, `event`, `date_from`/`date_to`, entitas) | SUPER_ADMIN (full); MANAGER, SUPERVISOR (ringkasan); HR (terbatas event kehadiran) | filter allowlist |
| `admin.audit-logs.show` `/admin/audit-logs/{log}` | GET | `Admin/AuditLogs/Show` | SUPER_ADMIN; MANAGER, SUPERVISOR (ringkasan); HR (terbatas event kehadiran) | view sensitive diaudit |
| `admin.storage-usage.index` `/admin/storage-usage` | GET | `Admin/StorageUsage/Index` (ringkasan per site) | SUPER_ADMIN | — |
| `admin.config.index` `/admin/config` | GET | `Admin/Config/Index` (ringkasan konfigurasi operasional aplikasi: policy password, session, batas upload, versi checklist aktif) | SUPER_ADMIN | view diaudit |

- Audit log read-only, append-only; tidak ada route mutation. Halaman ini sendiri merupakan sensitive access yang diaudit.
- Storage usage: bytes terpakai per site (attachment) sebagai alat pemantauan Super Admin — tanpa kuota per-site dan tanpa status warning/notifikasi otomatis pada MVP (kuota 2GB/site dihapus; monitoring via health endpoint + structured log).
- Halaman config bersifat ringkasan/pemantauan (single-project, nilai via config aplikasi); tidak mengubah konfigurasi runtime pada MVP.

## 16. Matriks Otorisasi Halaman/Aksi

| Kelompok halaman/aksi | EOS | SUPERVISOR | MANAGER | HR | SUPER_ADMIN |
|---|---:|---:|---:|---:|---:|
| Auth own session (login/logout/change-password) | Ya | Ya | Ya | Ya | Ya |
| Dashboard role (`/eos`, `/supervisor/...`, dst) | Ya | Ya | Ya | Ya | Ya |
| Check-in/clock-out | Ya (own) | Tidak | Tidak | Tidak | Tidak |
| Riwayat attendance | Own only (`/eos/attendance`) | Read scope (`/supervisor/attendance`) | Tidak | Read scope (`/hr/attendance`, `/hr/eos-history`) | Read (admin/troubleshoot) |
| Daily Report own (create/fill/submit) | Ya | Tidak | Tidak | Tidak | Tidak |
| Daily Report read/reopen | — | Read + reopen (`/supervisor/daily-reports`) | Read | Tidak | Read + reopen + full admin |
| Checklist version | — | Read + draft author (`/supervisor/master/checklists`) | Read + draft author | Tidak | Full + publish (`/admin/checklists`) |
| Site master + network link | — | Read (`/supervisor/master/sites`) | Read | Tidak | Full |
| Assignment | — | Kelola (`/supervisor/master/assignments`) | Read | Tidak | Full |
| Aset: registrasi + status change | Ya (site assignment) | Status change (scope) | Read | Tidak | Full |
| Stok/mutasi inventory | Read site | Mutasi (scope) | Read | Tidak | Full |
| Finding create own site | Ya | Read + review/resolve | Read | Tidak | Full |
| Attachment own upload/preview | Ya | Preview scope sesuai visibility | Terbatas (tanpa raw selfie/GPS) | Tidak | Full |
| Notification own | Ya | Ya | Ya | Ya | Ya |
| Export (`/exports`) | Tidak | Ya (site scope) | Ya sesuai scope | Ya (`data_type ATTENDANCE` sesuai otorisasi) | Ya |
| Analytics (`/manager/analytics/{type}`) | Tidak | Tidak | Ya | Tidak | Ya |
| User/role/reset + storage usage + config | Tidak | Tidak | Tidak | Tidak | Ya (`/admin/*`) |
| Audit view (`/admin/audit-logs`) | Tidak | Ringkasan | Ringkasan | Terbatas kehadiran | Full |

Otorisasi final di-enforce oleh middleware role + Policy + FormRequest + service; tabel ini hanya ringkasan navigasi. Scope role non-EOS MVP = semua site aktif. Data sensitif (selfie, precise GPS, accuracy, evidence) diakses sesuai visibility matrix; setiap akses sensitive diaudit.

## 17. Katalog Pesan Error

Error dikirim sebagai error bag Inertia (key = field atau key sintetis) dengan pesan aman berbahasa Indonesia. Katalog key stabil untuk UI/test (prefix menandai domain):

```text
auth.invalid_credentials        Login gagal; pesan generik anti-enumeration
auth.too_many_attempts          Lockout login 15 menit (5 gagal/15 menit)
auth.password_change_required   must_change_password aktif; aksi lain ditolak
auth.old_password_invalid        Password lama salah (pesan generik)

attendance.no_assignment         Tidak ada assignment aktif
attendance.already_checked_in    Sudah check-in untuk tanggal ini
attendance.not_checked_in        Belum check-in hari ini
attendance.already_clocked_out   Sudah clock-out untuk tanggal ini
attendance.report_required       Daily Report tanggal ini belum disubmit; lengkapi report dulu
attendance.selfie_invalid        Selfie wajib (format image, tanpa PDF)

report.already_exists            Report untuk tanggal ini sudah ada
report.not_editable              Status report tidak dapat diedit/di-submitted
report.validation_failed         Jawaban/evidence belum lengkap (detail per item)
report.reopen_window_expired     Batas 7 hari kalender reopen terlewati
report.reopen_forbidden          Hanya Supervisor scope/Super Admin

checklist.version_not_draft      Hanya version DRAFT yang dapat diubah/di-publish
checklist.publish_invalid        Struktur item/rule tidak valid untuk publish

attachment.too_large             > 10 MB per file
attachment.format_unsupported    Format tidak diizinkan untuk context
attachment.invalid_signature     MIME/signature mismatch
attachment.decode_failed         Gagal decode (mis. HEIC rusak)
attachment.link_context_invalid  Context link tidak valid/di luar scope
attachment.link_limit_exceeded   Melebihi limit context (§11)
attachment.already_linked        Attachment sudah teraut ke satu context

inventory.asset_tag_format       Format asset tag tidak sesuai
inventory.asset_tag_exists       Asset tag sudah terdaftar
inventory.serial_exists          Serial number sudah terdaftar
inventory.stock_insufficient     Stok tidak cukup (row lock, saldo tidak negatif)
inventory.mutation_note_required ADJUSTMENT/DAMAGED/LOST/TRANSFER wajib catatan
inventory.photo_required         Foto wajib (registrasi aset; DAMAGED/LOST)

site.network_link_incomplete     MAIN/SECONDARY belum lengkap; site tidak dapat aktif
site.network_link_duplicate      Site sudah punya link aktif untuk role tersebut
site.reason_required             Perubahan koordinat/timezone wajib reason

assignment.already_active        EOS sudah memiliki assignment aktif

user.already_exists              Identifier sudah dipakai
export.scope_forbidden           Kolom/filter melanggar privacy visibility
```

Error tak terkategori memakai pesan generik aman; halaman error 403/404/429/500 Inertia tidak membocorkan detail internal.

## 18. Acceptance Criteria

```text
[ ] Semua aksi mutation melewati session terautentikasi, middleware role, Policy, FormRequest, dan mencatat audit di titik kritis.
[ ] Semua validasi gagal kembali ke form (redirect back with errors) dengan input lama dipertahankan; aksi dengan form ganda memakai error bag terpisah.
[ ] Semua aksi sukses redirect (PRG) + flash message sesuai section domain.
[ ] Struktur path/page component sesuai baseline ux.md §9 / ui-spec.md §3 (role-prefixed path, folder component per area).
[ ] Login lockout 5 gagal/15 menit (counter identifier + IP), pesan generik anti-enumeration, login sukses me-reset counter identifier.
[ ] must_change_password: middleware memaksa change password; mutasi lain ditolak; sukses ganti merotasi session + revoke session lain + audit.
[ ] Check-in: selfie + GPS + timestamp server UTC; satu record per EOS+site+tanggal lokal (unique); jarak dihitung Haversine dan disimpan sebagai informasi tanpa penolakan radius.
[ ] Clock-out: gate Daily Report SUBMITTED + required evidence AVAILABLE; tanggal lokal sama dengan check-in; status COMPLETED.
[ ] Tidak ada route/halaman untuk window jam, late, klasifikasi, kalender kerja, geofence gate, atau Attendance Request.
[ ] Daily Report submit transaksional: nomor CMX.WR.YYYYMM.SEQUENCE dialokasikan atomik hanya saat sukses; resubmit REOPENED menambah revision tanpa mengubah nomor.
[ ] Seluruh rules FR-10 v1 (section, enum, range, note/evidence kondisional, suhu wajib, AP offline vs status, dual-link + LINK_TRAFFIC + 2 SPEEDTEST terpisah) di-enforce di submit.
[ ] Checklist version: edit hanya DRAFT; publish hanya Super Admin; published immutable; versi sebelumnya SUPERSEDED.
[ ] Aset diregistrasi EOS dengan tag dari gudang (regex + unik), serial number opsional bila perangkat punya SN (nullable), dan foto wajib; perubahan status beralasan + audit + foto saat DAMAGED/LOST.
[ ] Mutasi stok immutable + reversal via compensating mutation; saldo tidak boleh negatif (row lock).
[ ] Attachment divalidasi sinkron (magic byte, size ≤ 10 MB, SHA-256, decode-safe) → langsung AVAILABLE/REJECTED; thumbnail WebP via queued job; limit per context ter-enforce.
[ ] Download/preview attachment hanya via route terkontrol + audit; tanpa public URL.
[ ] Notifikasi: polling database; event DAILY_REPORT_REOPENED, ATTACHMENT_REJECTED.
[ ] Export: xlsx styled/PDF formal/CSV; pilih kolom + filter + preset per user; POST /exports streamed sinkron (form biasa, bukan router Inertia); audit sebelum stream; privacy visibility di-enforce; Supervisor site scope; HR hanya ATTENDANCE sesuai otorisasi.
[ ] Audit view read-only append-only: Super Admin full; Manager/Supervisor ringkasan; HR terbatas event kehadiran; aksesnya sendiri diaudit.
[ ] Semua halaman list paginated dengan filter/sort allowlist.
[ ] Tidak ada endpoint REST/JSON API layer terpisah, envelope data/error, idempotency key, atau artefak OpenAPI di aplikasi.
```
