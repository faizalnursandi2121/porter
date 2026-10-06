# Data Dictionary — PORTER (Portal Operasional Terpadu Sekolah Rakyat)

**Status:** Final Baseline MVP  
**Database target:** PostgreSQL (Laravel 13 + Eloquent, Sail)  
**Canonical time:** UTC `timestamptz`; tanggal operasional memakai timezone IANA site  
**Related documents:** `prd.md`, `erd.md`, `tech-stack.md`, `ux.md`, `security.md`, `architecture.md`, `api-contract.md`.

## 1. Purpose and Conventions

Dokumen ini adalah kontrak makna data untuk product, backend, frontend, QA, HR, Supervisor, Manager, dan Super Admin. ERD menjelaskan struktur relasi; data dictionary menjelaskan definisi bisnis, ownership, source of truth, validasi, sensitivitas, retensi, dan lifecycle data penting.

### 1.1 Conventions

| Konvensi | Aturan |
|---|---|
| Primary key | UUID untuk record operasional (Eloquent + migration); pengecualian: tabel package (spatie/laravel-permission, spatie/laravel-activitylog) memakai `bigint` auto-increment bawaan package |
| Timestamp | `timestamptz`, disimpan UTC; kolom Laravel `created_at`/`updated_at` otomatis |
| Business date | `date`, dievaluasi dengan timezone site |
| Local datetime snapshot | RFC 3339 with offset dan `site_timezone` terkait |
| Timezone | IANA timezone: `Asia/Jakarta`, `Asia/Makassar`, `Asia/Jayapura` |
| Boolean | `true`/`false`; jangan gunakan string Ya/Tidak di database/UI |
| Enum/status | Upper snake case, misalnya `CHECKED_IN`, `AVAILABLE` |
| Money | Tidak ada domain uang pada MVP; jangan memakai float untuk nilai uang future |
| Persentase | Numeric 0–100 dengan unit `%` eksplisit (hanya untuk CPU/RAM; traffic link memakai throughput, bukan persentase) |
| Throughput | `ThroughputValue` (`value` + `unit` `KBPS|MBPS` + `normalized_kbps` backend) |
| Kuantitas stok | `numeric(18,3)` + unit of measure |
| File binary | Tidak disimpan di PostgreSQL; file di local disk private (`storage/app`, di luar `public/`), database menyimpan metadata dan `storage_key` |
| Soft delete | Master data dapat memakai `deleted_at`; histori operasional/audit tidak dihapus langsung |
| Cache/session/queue | Semua driver `database` (tanpa Redis); tabel infrastruktur Laravel (`sessions`, `cache`, `jobs`, `failed_jobs`) adalah bagian skema |

### 1.2 Klasifikasi data

| Kelas | Definisi | Contoh | Retensi baseline |
|---|---|---|---|
| Highly Sensitive | Secret/credential/token | password hash, session payload, reset token hash | Sesuai kebijakan security; tidak pernah terekspos |
| Sensitive Personal/Operational | Identitas/evidence lokasi/foto atau data yang membahayakan operasional bila terekspos | selfie, koordinat precise GPS, GPS accuracy, evidence attachment, IP address, employee code | Access/download wajib audit event (activity_log); retensi sesuai parent (attendance 2 tahun, audit 3 tahun) |
| Internal Restricted | Data bisnis nonpublik | report, inventaris, assignment, activity log, preset export | Sesuai masing-masing domain |
| Internal | Master data umum internal | kategori aset, checklist non-sensitif, network link master | Sesuai lifecycle master data |

Semua akses/download data sensitive (selfie, precise GPS, GPS accuracy, evidence attachment, IP) harus dicatat sebagai event `activity_log`.

## 2. Time dan Timezone

### 2.1 Site timezone

| Field | Definisi | Source/owner | Validasi | Kelas |
|---|---|---|---|---|
| `site_timezone` | Timezone IANA yang menentukan tanggal operasional site | `sites.timezone`; Super Admin | Hanya `Asia/Jakarta`, `Asia/Makassar`, `Asia/Jayapura` untuk MVP | Internal |
| `timezone_label` | Label tampilan manusiawi | Derived system | WIB/WITA/WIT dari `site_timezone`; tidak authoritative | Internal |

Perubahan timezone site (dan koordinat) memerlukan reason dan event `activity_log`.

### 2.2 Canonical timestamp dan local snapshot

| Field/pattern | Definisi | Source of truth | Aturan |
|---|---|---|---|
| `*_at` (timestamptz) | Waktu event yang disimpan/ditetapkan server | Backend/PostgreSQL | UTC; sumber audit utama |
| `*_local` | Representasi waktu event pada timezone site | Backend derived | Dibentuk dari UTC + `site_timezone`; selalu sertakan timezone/offset saat render UI |
| `work_date_local` | Tanggal operasional berdasarkan timestamp server dan timezone site | Backend derived | Tidak mengambil tanggal dari device; dipakai attendance/report/filter operasional |
| `location_captured_at` | Timestamp ketika posisi didapat perangkat | Client evidence | Bukan waktu resmi absensi; diperiksa freshness terhadap server time dengan toleransi longgar (bukan gerbang penolakan) |

Timestamp server tetap lengkap UTC untuk audit. Tidak ada lagi aturan presisi menit/keterlambatan: absensi hanya bukti kehadiran, disiplin kehadiran ditangani absensi vendor EOS.

## 3. Identity, Role, dan Session

### 3.1 `users`

| Field | Definisi bisnis | Source of truth | Owner perubahan | Validasi | Kelas/retensi |
|---|---|---|---|---|---|
| `users.id` | Identitas internal pengguna | PostgreSQL | System | UUID immutable | Internal Restricted; sesuai lifecycle user |
| `employee_code` | Kode pegawai/petugas yang dapat dipakai login | PostgreSQL | Super Admin | Unique bila tidak null; format perusahaan | Sensitive Operational |
| `full_name` | Nama tampilan/legal internal pengguna | PostgreSQL | Super Admin | 1–200 karakter | Sensitive Personal |
| `email` | Email login/komunikasi | PostgreSQL | Super Admin/user via controlled flow | Unique, normalized, valid email | Sensitive Personal |
| `phone` | Nomor telepon opsional | PostgreSQL | Authorized admin | Format E.164/format policy | Sensitive Personal |
| `password` | Hash Argon2id password | PostgreSQL | Identity service only | Plaintext 12–128 karakter saat input; tidak pernah plaintext/log/response | Highly Sensitive |
| `is_active` | Apakah user dapat login dan memakai sistem | PostgreSQL | Super Admin | Boolean; disable merevoke seluruh session aktif | Internal Restricted |
| `must_change_password` | Flag wajib ganti password pada login/aktivitas terautentikasi berikutnya | PostgreSQL | Super Admin/system reset flow | Boolean default false; di-set true saat reset password oleh Super Admin; di-clear saat change password sukses; middleware memaksa ganti password | Sensitive Operational |
| `password_changed_at` | Waktu password terakhir diubah | PostgreSQL | System | UTC nullable; dasar revoke session saat password berubah | Sensitive Operational |
| `last_login_at` | Waktu login berhasil terakhir | PostgreSQL | System | UTC nullable | Sensitive Operational; min 1 tahun |

Catatan lockout: kegagalan login dan lockout sementara (5 gagal / 15 menit; lockout 15 menit) dijalankan Laravel RateLimiter dengan cache driver `database` — TIDAK disimpan sebagai kolom `users` dan tidak memakai Redis. Kejadian lockout dan login diaudit via `activity_log`. Identifier tak dikenal hanya menaikkan counter IP (anti-enumeration; response tetap generik).

### 3.2 Role dan permission (`spatie/laravel-permission`)

RBAC memakai `spatie/laravel-permission`. Lima role fixed, **satu role per user** (dipaksa di layer aplikasi: set user memakai `assignRole`/`syncRoles` tepat satu role; tidak ada UI multi-role).

| Tabel | Field | Definisi | Aturan |
|---|---|---|---|
| `roles` | `id` (bigint PK), `name`, `guard_name`, `timestamps` | Role sistem | `name` unique per guard; nilai fixed: `SUPER_ADMIN`, `MANAGER`, `SUPERVISOR`, `HR`, `EOS`; di-seed, tidak dikelola runtime |
| `permissions` | `id` (bigint PK), `name`, `guard_name`, `timestamps` | Kode permission | Unique per guard; contoh `user.create`, `checklist.publish`, `daily_report.reopen`, `export.run`; menambah permission baru memerlukan release (di-seed dari matriks kapabilitas `prd.md` §4) |
| `model_has_roles` | `role_id`, `model_type`, `model_id` | Pemetaan user→role | FK `roles`; satu baris aktif per user (single role); histori perubahan role via `activity_log` |
| `model_has_permissions` | `permission_id`, `model_type`, `model_id` | Pemberian permission langsung ke user | Tidak dipakai pada MVP (otorisasi murni via role); tabel tersedia bawaan package |
| `role_has_permissions` | `permission_id`, `role_id` | Pemetaan role→permission | Seed-only; perubahan mapping runtime (override) fase 2 via UI dan selalu diaudit |

Otorisasi server-side memeriksa permission name via Laravel Policies per model + `ScopeService` (EOS: assignment aktif; non-EOS: semua site aktif), bukan role literal. Scope role MVP: untuk role non-EOS (`SUPERVISOR`, `HR`, `MANAGER`), scope otorisasi default adalah **semua site aktif** (single-project, ~200 site, tim kecil). Frasa "site scope" pada data ini dibaca sebagai "site yang berada dalam scope-nya (MVP: semua site aktif)". Pembatasan per-site untuk role non-EOS ditunda sebagai reserved future capability.

### 3.3 Session (Laravel database driver)

Session persisten memakai tabel bawaan Laravel `sessions` (driver `database`, tanpa Redis). Cookie session `Secure` + `HttpOnly` + `SameSite=Lax`.

| Field | Definisi | Source | Sensitivitas | Aturan |
|---|---|---|---|---|
| `sessions.id` | ID session (PK, string) | System | Highly Sensitive | Raw session ID hanya di cookie terenkripsi; payload session terenkripsi di `payload` |
| `sessions.user_id` | Pemilik session | System | Sensitive Operational | FK users nullable (guest); dasar revoke semua session user |
| `sessions.ip_address` | IP pembuatan/terakhir | System | Sensitive Operational | Nullable |
| `sessions.user_agent` | User agent | System | Sensitive Operational | Nullable text |
| `sessions.payload` | Data session terenkripsi (base64) | System | Highly Sensitive | Tidak pernah dibaca/inspect oleh fitur bisnis; tidak di-log |
| `sessions.last_activity` | Timestamp aktivitas terakhir (unix int) | System | Sensitive Operational | Dasar idle timeout |

Timeout policy (diterapkan via konfigurasi + middleware, bukan kolom): idle timeout 30 menit sejak `last_activity`; absolute timeout 8 jam sejak login. Logout, perubahan/reset password, dan disable user menghapus/merevoke seluruh session aktif user. Kejadian login/logout/lockout/reset password diaudit via `activity_log`.

`password_reset_tokens` (tabel bawaan Laravel/Fortify): reserved — forgot-password self-service deferred (SMTP belum tersedia); reset password pada MVP dilakukan Super Admin dan memaksa `must_change_password`. Token hash single-use, expiry pendek, bila dipakai.

## 4. Site, Network Link, dan EOS Assignment

### 4.1 `sites`

| Field | Definisi bisnis | Source/owner | Validasi | Kelas |
|---|---|---|---|---|
| `sites.id` | Identitas internal site | PostgreSQL | UUID | Internal Restricted |
| `site_code` | Kode unik site Sekolah Rakyat | Super Admin | Unique; immutable/controlled change; dipakai pada asset tag | Internal Restricted |
| `school_name` | Nama sekolah/site | Super Admin | Required, max 200 | Internal |
| `address` | Alamat site | Super Admin | Text sanitized | Sensitive Operational |
| `province` | Provinsi site | Super Admin | Master/free text standardized | Internal |
| `latitude` | Koordinat referensi site | Super Admin | -90..90; decimal precision | Sensitive Operational |
| `longitude` | Koordinat referensi site | Super Admin | -180..180 | Sensitive Operational |
| `radius_meters` | Radius referensi site untuk INFORMASI jarak absensi (bukan gerbang) | Super Admin | Integer > 0; default 100 | Sensitive Operational |
| `timezone` | IANA timezone site | Super Admin | Allowlist WIB/WITA/WIT | Internal |
| `is_active` | Site aktif untuk operasional baru | Super Admin | Boolean; site tidak boleh aktif untuk Daily Report bila konfigurasi Main/Secondary link belum lengkap | Internal Restricted |
| `created_by_user_id`, `created_at`, `updated_at` | Audit fields | System | FK user + UTC | Internal |

Perubahan `latitude`, `longitude`, `radius_meters`, atau `timezone` selalu membuat event `activity_log` (wajib reason) karena memengaruhi penampilan jarak/penampilan waktu operasional berikutnya.

### 4.2 `site_network_links` (dual-link)

| Field | Definisi | Source/owner | Validasi | Kelas |
|---|---|---|---|---|
| `site_network_links.id` | ID link | PostgreSQL | UUID | Internal Restricted |
| `site_id` | Site pemilik link | Super Admin | FK site | Internal Restricted |
| `role` | Peran link pada dual-link site | Super Admin | `MAIN` atau `SECONDARY` | Internal |
| `provider_name` | Nama provider layanan | Super Admin | Required, max 200 | Internal |
| `service_name` | Nama paket/layanan spesifik | Super Admin | Nullable | Internal |
| `connection_medium` | Media koneksi | Super Admin | `FIBER`, `WIRELESS`, `CELLULAR`, `SATELLITE`, `OTHER` | Internal |
| `subscribed_download_value` + `subscribed_download_unit` | Kapasitas download langganan | Super Admin | Nullable; unit `KBPS`/`MBPS` | Internal |
| `subscribed_upload_value` + `subscribed_upload_unit` | Kapasitas upload langganan | Super Admin | Nullable; unit `KBPS`/`MBPS` | Internal |
| `active` | Status aktif link | Super Admin | Boolean; constraint `unique(site_id, role) where active = true` | Internal |
| `effective_from` / `effective_to` | Masa berlaku konfigurasi | Super Admin | Date; `effective_to` nullable | Internal |
| `note` | Catatan link | Super Admin | Nullable, sanitized | Internal |
| `created_by_user_id`, `created_at`, `updated_at` | Audit fields | System | FK user + UTC | Internal |

Invariant: setiap site aktif wajib punya tepat satu `MAIN` aktif dan satu `SECONDARY` aktif. Site tidak boleh aktif untuk Daily Report jika konfigurasi belum lengkap. Konfigurasi link disnapshot ke Daily Report (`snapshot.network_links`) saat submit.

### 4.3 `eos_site_assignments`

| Field | Definisi | Source/owner | Validasi | Kelas |
|---|---|---|---|---|
| `eos_user_id` | User role EOS yang ditugaskan | Supervisor/Super Admin | User valid dan memiliki role EOS | Sensitive Operational |
| `site_id` | Site penugasan | Supervisor/Super Admin | Site aktif | Internal Restricted |
| `starts_on_local` | Tanggal mulai assignment | Supervisor/Super Admin | Date local site | Internal |
| `ends_on_local` | Tanggal akhir assignment | Supervisor/Super Admin | Null saat aktif; >= starts | Internal |
| `status` | Lifecycle assignment | System/admin | `ACTIVE`, `ENDED`, `CANCELLED` | Internal |
| `end_reason` | Alasan pengakhiran | Supervisor/Super Admin | Required untuk ended/cancelled sesuai policy | Internal Restricted |
| `assigned_by_user_id`, `ended_by_user_id` | Actor penugasan/pengakhiran | System | FK user | Internal Restricted |

Invariant: seorang EOS hanya boleh memiliki satu assignment `ACTIVE` dalam waktu yang sama (partial unique index pada `eos_user_id WHERE status = 'ACTIVE'`). Inventaris tidak pernah melekat pada assignment/EOS; inventaris melekat pada site. Penempatan EOS permanen per site; tidak ada penugasan lapangan sementara.

## 5. Absensi — Bukti Kehadiran

Model absensi BARU: bukti kehadiran di site, bukan alat disiplin. Disiplin/potongan kehadiran ditangani absensi vendor EOS. Check-in/clock-out = selfie + koordinat GPS + server timestamp UTC. Jarak ke site dihitung Haversine di PHP dan DISIMPAN SEBAGAI INFORMASI (bukan gerbang); tidak ada penolakan radius, tidak ada window jam, tidak ada late minutes, tidak ada `ABSENT`, tidak ada Attendance Request.

### 5.1 `attendance_records` — record

| Field | Definisi bisnis | Source of truth | Owner perubahan | Validasi/aturan | Kelas/retensi |
|---|---|---|---|---|---|
| `attendance_records.id` | ID internal attendance | PostgreSQL | System | UUID immutable | Internal Restricted; min 2 tahun |
| `user_id` | EOS pemilik record | Session | System | Tidak dipilih bebas client | Sensitive Operational |
| `site_id` | Site attendance | Assignment aktif | System | Assignment aktif EOS | Sensitive Operational |
| `work_date_local` | Tanggal lokal site record | Backend derived | System | Dari server time + `site_timezone` | Internal |
| `site_timezone` | Snapshot timezone site saat record dibuat | Backend | System | IANA timezone; untuk penampilan waktu lokal | Internal |
| `status` | Lifecycle kehadiran | Backend | System | `NOT_CHECKED_IN` → `CHECKED_IN` → `COMPLETED`; status `ABSENT` TIDAK ADA — hari tanpa absen = kosongnya record (urusan vendor) | Internal |

Unique: `(user_id, site_id, work_date_local)` — satu record per EOS + site + tanggal lokal; mencegah double submit. Check-in dan clock-out harus tanggal lokal yang sama (cross-midnight tidak didukung). Maksimum satu selfie check-in dan satu selfie clock-out (FK kolom unik). Attempt gagal tetap diaudit via `activity_log` (mis. `ALREADY_CHECKED_IN`). Absensi wajib online; tidak dapat offline/queued. Kehadiran tidak lagi bergantung konsep hari kerja/kalender.

### 5.2 Check-in evidence fields

| Field | Definisi | Source | Validasi | Kelas |
|---|---|---|---|---|
| `check_in_at` | Waktu resmi check-in (server) | Backend | UTC; tidak memakai clock device | Sensitive Operational |
| `check_in_latitude` | Latitude posisi perangkat | Client evidence | -90..90; diambil SEKALI dengan toleransi longgar | Sensitive Personal |
| `check_in_longitude` | Longitude posisi perangkat | Client evidence | -180..180 | Sensitive Personal |
| `check_in_accuracy_meters` | Estimasi accuracy posisi | Client evidence | Nullable; informasi, bukan gerbang | Sensitive Operational |
| `check_in_location_captured_at` | Waktu lokasi diperoleh device | Client evidence | Nullable; not authoritative; freshness longgar | Sensitive Operational |
| `check_in_distance_meters` | Jarak Haversine ke titik site, dihitung backend PHP | Backend | >= 0; DISIMPAN SEBAGAI INFORMASI — tidak ada penolakan radius, tidak ada borderline flag, tidak ada geofence status | Sensitive Operational |
| `check_in_selfie_attachment_id` | Evidence selfie check-in | Attachment module | FK attachment status `AVAILABLE`; maksimum satu | Sensitive Personal |

GPS diambil sekali per aksi dengan toleransi longgar; deteksi wajah pada UI bersifat opsional (browser FaceDetector API bila tersedia; fallback overlay panduan) dan TIDAK menghasilkan data yang disimpan — tidak ada penyimpanan biometrik, tidak ada face-matching server-side.

### 5.3 Clock-out evidence fields

`check_out_at`, `check_out_latitude`, `check_out_longitude`, `check_out_accuracy_meters`, `check_out_location_captured_at`, `check_out_distance_meters`, `check_out_selfie_attachment_id` — makna sama dengan pasangan check-in untuk event clock-out.

**Gate clock-out** (satu-satunya gate domain): clock-out hanya valid bila record berstatus `CHECKED_IN`, Daily Report tanggal `work_date_local` yang sama berstatus `SUBMITTED`, dan seluruh required evidence report `AVAILABLE`. Bila report belum lengkap, alur mengarahkan EOS melengkapi report terlebih dahulu (flow kontinu, bukan penolakan permanen).

Selfie check-in dan clock-out tidak lewat `attachment_links` — relasi selfie adalah kolom FK langsung pada `attendance_records` (lihat §7.3).

## 6. Daily Report dan Checklist Versioned

### 6.1 `daily_reports` — header

| Field | Definisi bisnis | Source/owner | Validasi/aturan | Kelas/retensi |
|---|---|---|---|---|
| `daily_reports.id` | ID internal report | PostgreSQL | UUID immutable | Internal Restricted; 2 tahun |
| `report_number` | Nomor resmi report | Backend/PostgreSQL | Nullable pada draft; dialokasikan atomik HANYA saat submit sukses; format `CMX.WR.YYYYMM.SEQUENCE`; unique; tidak berubah saat reopen/resubmit | Internal Restricted |
| `report_sequence` | Nilai sequence global | PostgreSQL SEQUENCE (bigint) | Monotonic, unique, tidak reset; dialokasikan dalam transaction submit | Internal Restricted |
| `eos_user_id` | Pemilik/pengirim report | Session/assignment | EOS tidak memilih user lain | Sensitive Operational |
| `site_id` | Site report | Assignment aktif | Tidak dipilih bebas client EOS | Internal Restricted |
| `work_date_local` | Tanggal laporan operasional | Backend derived | Server time + `site_timezone` | Internal |
| `site_timezone` | Snapshot timezone site | Backend | IANA valid | Internal |
| `checklist_version_id` | Version checklist yang digunakan | Backend | FK `checklist_versions` status `PUBLISHED`; snapshot saat draft dibuat | Internal |
| `snapshot` | Snapshot JSONB: definisi checklist + site + EOS + network links | Backend | Immutable setelah submit; struktur lihat bawah | Internal Restricted |
| `status` | Lifecycle report | Backend | `DRAFT`, `SUBMITTED`, `REOPENED`, `VOIDED` — `VOIDED` via aksi governance Super Admin; endpoint VOIDED **deferred** pada MVP (nilai reserved, belum dapat dicapai via aplikasi) | Internal |
| `revision` | Nomor revisi setelah reopen | Backend | Default 0; bertambah tiap resubmit; nomor report tidak berubah | Internal |
| `submitted_at` | Waktu submit resmi | Backend | UTC nullable until submit | Internal |
| `reopened_at` | Waktu report dibuka kembali | Backend | UTC nullable; maksimum 7 hari kalender setelah submit | Internal |
| `reopened_by_user_id` | Actor reopen | Backend | Supervisor/Super Admin only | Internal Restricted |
| `reopen_reason` | Alasan reopen | Authorized actor | Required on reopen | Internal Restricted |
| `created_at`, `updated_at` | Audit fields | System | UTC | Internal |

Unique: `(eos_user_id, site_id, work_date_local)` — satu report per EOS/site/tanggal. Submit ditolak bila required evidence belum `AVAILABLE` (validasi attachment sinkron; attachment `REJECTED` tidak dapat memenuhi required evidence).

#### Struktur `snapshot` (JSONB; `schema_version: 1`)

Snapshot menyertakan field `schema_version` (integer; nilai awal `1`) per objek agar struktur dapat berevolusi tanpa merusak interpretasi data historis.

`snapshot.checklist` (definisi checklist saat report dibuat):

| Field | Tipe | Catatan |
|---|---|---|
| `schema_version` | integer | `1` |
| `checklist_version_id` | UUID | Version checklist yang disnapshot |
| `template_code` | string | `DAILY_SITE_REPORT` |
| `sections` | array | Lihat `sections[]` di §6.3 (struktur identik dengan `structure` pada `checklist_versions`) |

`snapshot.site`:

| Field | Tipe |
|---|---|
| `schema_version` | integer (`1`) |
| `site_id` | UUID |
| `site_code` | string |
| `site_name` | string |
| `timezone` | string (IANA) |
| `latitude` | number |
| `longitude` | number |
| `radius_meters` | integer |

`snapshot.eos`:

| Field | Tipe |
|---|---|
| `schema_version` | integer (`1`) |
| `user_id` | UUID |
| `employee_code` | string |
| `full_name` | string |

`snapshot.network_links`:

| Field | Tipe |
|---|---|
| `schema_version` | integer (`1`) |
| `links` | array of `{role, provider_name, service_name, connection_medium, subscribed_download_value, subscribed_download_unit, subscribed_upload_value, subscribed_upload_unit}` (enum dan unit sama dengan §4.2) |

### 6.2 Nomor report

```text
Format: CMX.WR.YYYYMM.SEQUENCE
Contoh: CMX.WR.202610.0001
```

| Segmen | Definisi | Source |
|---|---|---|
| `CMX` | Company code Comtronics | Fixed, bukan konfigurasi (tidak ada tabel system settings) |
| `WR` | Work report | Fixed, bukan konfigurasi |
| `YYYYMM` | Tahun/bulan `work_date_local` berdasarkan timezone site saat submit | Backend derived |
| `SEQUENCE` | Global sequence lintas semua site/bulan/tahun | PostgreSQL SEQUENCE native (bigint), padded min 4 digit |

Sequence native PostgreSQL tidak direset bulanan/tahunan dan hanya dialokasikan saat submit sukses (`nextval` dalam transaction submit), bukan ketika draft dibuat. Tidak ada tabel counter — allocation murni via SEQUENCE database.

### 6.3 `checklist_versions` — master versioned (JSON)

Satu tabel versi + struktur JSON (bukan 5–6 tabel relasional). Super Admin publish; version `PUBLISHED` immutable. Satu template global `DAILY_SITE_REPORT`.

| Field | Definisi | Owner | Validasi |
|---|---|---|---|
| `checklist_versions.id` | ID version | System | UUID |
| `template_code` | Kode template stabil | Super Admin | `DAILY_SITE_REPORT` (satu template global MVP) |
| `version_number` | Nomor versi | System/admin | Unique per template; integer > 0 |
| `status` | Lifecycle version | Super Admin publish | `DRAFT`, `PUBLISHED`, `SUPERSEDED`, `RETIRED` |
| `structure` | Struktur JSON checklist: section/item/option/rule | Super Admin | Validasi struktur saat save/publish; lihat `structure` di bawah |
| `published_by_user_id` | Publisher | Super Admin | Hanya Super Admin yang publish |
| `published_at` | Waktu publish | Backend | UTC nullable; set saat publish |
| `superseded_by_version_id` | Version pengganti | System | Nullable; FK `checklist_versions` |
| `created_at`, `updated_at` | Audit fields | System | UTC |

Version `PUBLISHED` immutable — perubahan section/item/option/rule/order harus melalui version baru (version lama menjadi `SUPERSEDED`). Renderer/input type baru membutuhkan frontend/backend release. Checklist tidak boleh hard-coded sebagai kolom permanen form.

Struktur `structure` (JSONB):

| Field | Tipe | Catatan |
|---|---|---|
| `schema_version` | integer | `1` |
| `sections` | array | `sections[]` di bawah |

`sections[]`:

| Field | Tipe | Catatan |
|---|---|---|
| `section_code` | string | Unique dalam version |
| `section_label` | string | |
| `display_order` | integer | Urutan deterministic |
| `evidence_required` | boolean | Section Evidence wajib (true untuk semua section operasional; Info Umum optional) |
| `evidence_min_count` / `evidence_max_count` | integer | Baseline 1/5 untuk required; 0/5 untuk optional |
| `items` | array | `items[]` di bawah |

`items[]`:

| Field | Tipe | Catatan |
|---|---|---|
| `item_code` | string | Unique dalam version |
| `item_label` | string | |
| `input_type` | string | Enum input type (lihat §13) |
| `options` | array of string | Hanya untuk `ENUM` |
| `unit` | string nullable | Unit item (mis. `%`, `°C`, `ms`) |
| `is_required` | boolean | Kewajiban item |
| `allows_not_applicable` | boolean | Apakah N/A valid |
| `allows_item_evidence` | boolean | Evidence level item; maksimum 5 attachment |
| `validation_rules` | object nullable | Range/min/max/precision/option/schema item |
| `rules` | array | Rule item — lihat `rules[]` di bawah |
| `evidence_min_count` / `evidence_max_count` | integer | Batas attachment item evidence (0/5) |

`rules[]`:

| Field | Tipe | Catatan |
|---|---|---|
| `rule_type` | string | `REQUIRE_NOTE`, `REQUIRE_ITEM_EVIDENCE`, `REQUIRE_FIELD`, `CONSISTENCY` |
| `condition` | object | Trigger rule, mis. `{"answer": "MAJOR"}` |
| `action` | object | Aksi rule, mis. `{"note": true}` |
| `error_code` | string | Error code katalog untuk validasi submit |

Rules v1 divalidasi **eksplisit per rule di kode** (FormRequest/service), bukan interpreter JSON generik (interpreter generik direncanakan untuk checklist v2); menghasilkan error code katalog. Seluruh rules FR-10 v1 lama dipertahankan (lihat §6.5).

### 6.4 Tipe nilai structured

#### `ThroughputValue`

| Field | Definisi | Validasi |
|---|---|---|
| `value` | Nilai throughput sesuai input source | Numeric > 0 |
| `unit` | Unit input | `KBPS` atau `MBPS`; Gbps, KB/s, MB/s, byte-per-second tidak diterima |
| `normalized_kbps` | Normalisasi backend | `KBPS` → nilai sama; `MBPS` → nilai × 1000 |

Dipakai pada `LINK_TRAFFIC` (avg/peak inbound/outbound), `SPEEDTEST_RESULT` (download/upload), dan subscribed capacity `site_network_links` (tanpa `normalized_kbps` yang disimpan — dihitung saat perlu).

#### `LINK_TRAFFIC`

Utilisasi link terukur (bukan persentase; struktur throughput avg/peak). Measurement window tetap 08:00–17:00 site-local.

| Field | Definisi | Validasi |
|---|---|---|
| `status` | Status link traffic | `AVAILABLE`, `DOWN`, `NOT_CHECKED` |
| `avg_inbound` / `peak_inbound` | Rata-rata/peak inbound | `ThroughputValue` |
| `avg_outbound` / `peak_outbound` | Rata-rata/peak outbound | `ThroughputValue` |
| `source` | Sumber dashboard/monitoring | Required saat `AVAILABLE` |
| `note` | Catatan | Required saat `DOWN`/`NOT_CHECKED` (alasan) |
| evidence | Item evidence | `AVAILABLE` wajib saat status `AVAILABLE`/`DOWN` |

Rules: `AVAILABLE` → empat nilai traffic + source + item evidence `AVAILABLE` wajib; `DOWN` → note + item evidence `AVAILABLE` wajib; `NOT_CHECKED` → note alasan wajib.

#### `SPEEDTEST_RESULT`

Connection test manual oleh EOS sebelum report submit; wajib terpisah untuk `MAIN` dan `SECONDARY`.

| Field | Definisi | Validasi |
|---|---|---|
| `status` | Hasil test | `SUCCESS`, `FAILED`, `NOT_TESTED` |
| `link_role` | Link yang diuji | `MAIN` atau `SECONDARY` |
| `download` / `upload` | Hasil throughput | `ThroughputValue`; required saat `SUCCESS` |
| `latency_ms` | Latensi | Integer >= 0; required saat `SUCCESS` |
| `jitter_ms` | Jitter | Integer >= 0; required saat `SUCCESS` |
| `packet_loss_percent` | Packet loss | Nullable; numeric 0–100 |
| `server_name` | Nama server test | Nullable |
| `route_declaration` | Deklarasi rute test | `TESTED_VIA_MAIN_LINK` atau `TESTED_VIA_SECONDARY_LINK`; deklarasi, bukan verifikasi otomatis jalur (out of scope) |
| `note` | Catatan | Required saat `FAILED`/`NOT_TESTED` (alasan, mis. `Link down`) |
| evidence | Screenshot hasil test | Item evidence `AVAILABLE` wajib saat `SUCCESS` |

Rules: `SUCCESS` → download, upload, latency, jitter, screenshot `AVAILABLE` wajib; `FAILED`/`NOT_TESTED` → note alasan wajib. Bila link down, entry speedtest link tersebut tetap wajib (`FAILED` atau `NOT_TESTED` dengan reason `Link down`). Secondary test wajib mengikuti safe routing/failover SOP (detail SOP per router/site = Open Operational Configuration).

### 6.5 Checklist Master v1

Template `DAILY_SITE_REPORT`, satu version `PUBLISHED` global pada MVP.

| Section | Item | Code | Input/value | Aturan minimum |
|---|---|---|---|---|
| Info Umum | ID Laporan | `REPORT_NUMBER` | `READ_ONLY` | Nomor hanya final saat submit |
| Info Umum | Tanggal | `REPORT_DATE` | `READ_ONLY` | Site-local date |
| Info Umum | Nama Sekolah | `SCHOOL_NAME` | `READ_ONLY` | From assignment site |
| Info Umum | Nama Petugas | `OFFICER_NAME` | `READ_ONLY` | From session EOS |
| Router & Firewall | Uptime | `ROUTER_UPTIME` | `DURATION` | Required |
| Router & Firewall | Utilisasi CPU | `ROUTER_CPU_UTILIZATION` | `PERCENTAGE` | 0–100; required |
| Router & Firewall | Utilisasi RAM | `ROUTER_RAM_UTILIZATION` | `PERCENTAGE` | 0–100; required |
| Router & Firewall | Log Anomali | `ROUTER_ANOMALY_LOG` | `ENUM` | `NONE`, `MINOR`, `MAJOR`, `NOT_CHECKED`; required |
| Access Point | Status Monitoring AP | `AP_MONITORING_STATUS` | `ENUM` | `NORMAL`, `WARNING`, `DOWN`, `UNKNOWN`; required |
| Access Point | Jumlah AP Offline | `AP_OFFLINE_COUNT` | `INTEGER` | 0–10.000 |
| Access Point | Dokumentasi AP di Cloud | `AP_CLOUD_DOCUMENTATION` | Item evidence | Minimum 1 attachment `AVAILABLE` wajib |
| Infrastruktur & Lingkungan | Pengecekan Kelistrikan | `POWER_CHECK_STATUS` | `ENUM` | `NORMAL`, `UNSTABLE`, `OUTAGE`, `BACKUP_ACTIVE`, `NOT_CHECKED`; required |
| Infrastruktur & Lingkungan | Suhu Ruangan Server | `SERVER_ROOM_TEMPERATURE` | `NUMBER` | Celsius, satu desimal, input range -20.0 sampai 80.0; wajib diisi (alat suhu tersedia di site; tidak ada `NOT_MEASURED`) |
| Infrastruktur & Lingkungan | Kondisi Lingkungan | `ENVIRONMENT_CONDITION` | `ENUM` | `GOOD`, `ATTENTION`, `UNFIT`, `NOT_CHECKED`; required |
| Konektivitas | Utilisasi Main Link | `MAIN_LINK_TRAFFIC` | `LINK_TRAFFIC` | Measurement window 08:00–17:00 site-local |
| Konektivitas | Utilisasi Secondary Link | `SECONDARY_LINK_TRAFFIC` | `LINK_TRAFFIC` | Measurement window 08:00–17:00 site-local |
| Konektivitas | Connection Test Main Link | `MAIN_LINK_SPEEDTEST` | `SPEEDTEST_RESULT` | Wajib, terpisah dari Secondary |
| Konektivitas | Connection Test Secondary Link | `SECONDARY_LINK_SPEEDTEST` | `SPEEDTEST_RESULT` | Wajib, terpisah dari Main |

Rules Checklist Master v1:

| Rule scope | Aturan |
|---|---|
| Router: `MINOR` | Note wajib |
| Router: `MAJOR` | Note + item evidence `AVAILABLE` wajib |
| Router: `NOT_CHECKED` | Note alasan wajib |
| AP: `NORMAL` | Jumlah AP Offline harus 0 |
| AP: `WARNING` / `DOWN` | Jumlah AP Offline wajib >= 1 |
| AP: `UNKNOWN` | Note alasan wajib; count boleh null |
| AP: Dokumentasi AP Cloud | Minimum 1 attachment `AVAILABLE` wajib |
| Power: `UNSTABLE`, `OUTAGE`, `BACKUP_ACTIVE` | Note + item evidence `AVAILABLE` wajib |
| Power: `NOT_CHECKED` | Note alasan wajib |
| Environment: `ATTENTION` | Note wajib |
| Environment: `UNFIT` | Note + item evidence `AVAILABLE` wajib |
| Environment: `NOT_CHECKED` | Note alasan wajib |
| Suhu | Wajib diisi; satu desimal; -20.0 sampai 80.0 |

Threshold warning/critical untuk CPU/RAM/suhu/utilisasi link adalah Open Operational Configuration, bukan bagian master data v1.

Aturan Evidence Section:

- Semua section operasional wajib punya minimal satu attachment `AVAILABLE` (1–5).
- Info Umum optional (0–5).
- Maksimum lima attachment pada satu Evidence Section dan lima pada satu item evidence.
- Maksimum 10 attachment per Daily Report (hitungan sederhana, tanpa dedup lintas context).
- Satu attachment hanya teraut ke tepat satu context (`attachment_links` 1-baris-per-attachment); evidence untuk context berbeda diunggah terpisah, file sama boleh diunggah ulang.
- Satu screenshot Main Link tidak otomatis menjadi evidence Secondary Link; bukti Secondary Link diunggah terpisah.

### 6.6 `daily_report_answers`

| Field | Definisi bisnis | Source/owner | Validasi | Kelas |
|---|---|---|---|---|
| `daily_report_answers.id` | ID answer | System | UUID | Internal |
| `daily_report_id` | Report parent | System | FK valid | Internal |
| `item_code` | Kode item checklist (dari `snapshot.checklist`/version) | System | Unique bersama report; item harus ada pada checklist version report | Internal |
| `value` | Nilai terstruktur JSON per item (lihat skema di bawah) | EOS | Validasi eksplisit per item type/rule (FormRequest/service) | Internal Restricted |
| `note` | Catatan EOS | EOS | Required conditionally per rule; sanitized text | Internal Restricted |
| `is_not_applicable` | Penanda N/A | EOS | Only allowed if item allows N/A | Internal |
| `created_at`, `updated_at` | Audit fields | System | UTC | Internal |

Unique: `(daily_report_id, item_code)`. Nilai `value` (JSONB) untuk tipe item:

- `ENUM`: `{"value": "MINOR"}`
- `LINK_TRAFFIC`: `{status, avg_inbound, peak_inbound, avg_outbound, peak_outbound, source, note}` dengan setiap throughput berupa `ThroughputValue` (`{value, unit, normalized_kbps}`)
- `SPEEDTEST_RESULT`: `{status, link_role, download, upload, latency_ms, jitter_ms, packet_loss_percent, server_name, route_declaration, note}`
- `NUMBER`/`PERCENTAGE`/`INTEGER`/`DURATION`/`TEXT`/`BOOLEAN`/`READ_ONLY`: nilai skalar sesuai `input_type` dan `validation_rules` item

### 6.7 Evidence Section (via `attachment_links`)

Evidence Section pada akhir setiap section checklist **tidak disimpan sebagai tabel terpisah**. `attachment_links` (lihat §7.3) adalah **source of truth relasi evidence**: relasi section-evidence disimpan sebagai baris `attachment_links` dengan `context_type = DAILY_REPORT_SECTION` dan `context_id` = ID Daily Report, dengan kode section dibawa pada konteks link.

Batas (dihitung langsung dari `attachment_links` per context): 5 per Evidence Section (`DAILY_REPORT_SECTION`), 5 per item evidence (`DAILY_REPORT_ITEM`), 10 per Daily Report total (hitungan sederhana, tanpa dedup lintas context). Status `AVAILABLE` wajib untuk memenuhi submit. **Selfie absensi TIDAK lewat `attachment_links`** — relasi selfie disimpan sebagai kolom FK langsung pada `attendance_records` (`check_in_selfie_attachment_id` / `check_out_selfie_attachment_id`, lihat §5.2/§5.3).

## 7. Attachment

### 7.1 `attachments` — metadata

| Field | Definisi bisnis | Source/owner | Validasi | Kelas/retensi |
|---|---|---|---|---|
| `attachments.id` | ID attachment | PostgreSQL | UUID immutable | Sensitive; per parent retention |
| `attachment_type` | Tipe evidence (katalog) | Client selected + server allowlist | `CHECK_IN_SELFIE`, `CHECK_OUT_SELFIE`, `SECTION_EVIDENCE`, `ITEM_EVIDENCE`, `LINK_TRAFFIC_EVIDENCE`, `SPEEDTEST_EVIDENCE`, `AP_CLOUD_EVIDENCE`, `ANOMALY_LOG`, `FINDING_EVIDENCE`, `ASSET_REGISTRATION_PHOTO`, `ASSET_STATUS_PHOTO`, `MUTATION_EVIDENCE`, `OTHER` | Sensitive Operational |
| `original_filename` | Nama file asli sebagai metadata | Client; sanitized | Tidak digunakan sebagai path | Internal Restricted |
| `content_type` | MIME hasil validasi server | System | Allowlist JPG/JPEG, PNG, WebP, HEIC/HEIF, PDF | Internal |
| `size_bytes` | Ukuran original | System | <= 10 MB | Internal |
| `storage_key` | Object key file private | System | Server-generated UUID/random; never public path | Highly Sensitive/Operational |
| `checksum_sha256` | SHA-256 original | System | Hex 64 char; integrity reference; dihitung sinkron saat upload | Internal Restricted |
| `status` | Hasil validasi | System | `AVAILABLE`, `REJECTED` (lihat pipeline di bawah) | Internal |
| `rejection_reason` | Alasan ditolak | System | Required untuk `REJECTED` | Internal Restricted |
| `uploaded_by_user_id` | User uploader | Session actor | FK user | Sensitive Operational |
| `uploaded_at` | Waktu upload | Backend | UTC | Internal |
| `metadata` | Metadata aman: width/height/orientation dll | System | Do not retain EXIF GPS | Sensitive Operational |

Pipeline validasi **sinkron dalam request** upload: magic byte/signature, size, decode-safe, hash SHA-256, konten sesuai tipe — lolos → langsung `AVAILABLE`; gagal → `REJECTED` (tidak tersedia ke user; event `activity_log` dibuat). Tidak ada status `QUARANTINED`/`PROCESSING` dan tidak ada ClamAV (ADR-044 tetap; tanpa quarantine async penuh). Thumbnail/preview dibuat oleh queued job terpisah (§7.2), tidak memengaruhi status. Original immutable/private di local disk (`storage/app`); semua download diotorisasi controller terkontrol dan diaudit.

PDF hanya boleh pada report/section evidence; tidak boleh untuk selfie dan inventory mutation.

### 7.2 `attachment_derivatives`

Derivative dibuat oleh **queued job** (Laravel Queue, database driver) memakai Intervention Image (Imagick + libheif untuk HEIC) setelah upload tervalidasi `AVAILABLE`.

| Field | Definisi | Aturan |
|---|---|---|
| `attachment_id` | Parent original attachment | FK required |
| `variant_type` | `PREVIEW` atau `THUMBNAIL` | Unique per attachment/type |
| `storage_key` | Private key derivative | Berbeda dari original |
| `content_type` | Format derivative | WebP |
| `width`, `height` | Pixel dimension derivative | Positive, preserve aspect ratio |
| `size_bytes` | Ukuran derivative | Integer >= 0 |
| `created_at` | Waktu job selesai | UTC |

Policy derivative:

```text
Preview WebP max 2048 px; thumbnail WebP max 480 px.
PDF: original only MVP; no automatic derivative.
Original remains immutable private evidence.
```

Kegagalan job derivative tidak menurunkan status attachment (original tetap `AVAILABLE`); job di-retry oleh queue worker dan kegagalan berulang masuk kategori alert worker job failure.

### 7.3 `attachment_links`

Satu attachment hanya teraut ke tepat satu context (relasi 1-baris-per-attachment; unique per attachment); evidence untuk context berbeda diunggah terpisah — file sama boleh diunggah ulang.

| Field | Definisi | Source/owner | Validasi | Kelas |
|---|---|---|---|---|
| `attachment_id` | File yang ditautkan | EOS/actor | FK attachment | Sensitive |
| `context_type` | Konteks tujuan | Client + server allowlist | `DAILY_REPORT`, `DAILY_REPORT_SECTION`, `DAILY_REPORT_ITEM`, `INVENTORY_MUTATION`, `INVENTORY_FINDING`, `ASSET` | Internal |
| `context_id` | Entity tujuan | System | FK/referensi valid | Internal |
| `linked_by_user_id` | Actor penaut | Session actor | FK user | Sensitive Operational |
| `linked_at` | Waktu penautan | Backend | UTC | Internal |

Batas per context: section evidence 5; item evidence 5; inventory mutation 5; Daily Report total 10 (hitungan sederhana, tanpa dedup lintas context). Selfie check-in/clock-out: 1+1 — lewat kolom FK `attendance_records`, bukan `attachment_links`.

## 8. Inventory

### 8.1 `asset_categories`

| Field | Definisi | Owner | Validasi |
|---|---|---|---|
| `asset_categories.id` | ID kategori | System | UUID |
| `code` | Kode kategori | Authorized admin | Unique; referensi klasifikasi pada asset tag dari gudang |
| `name` | Nama kategori | Authorized admin | Required, max 120 |
| `is_active` | Kategori dapat dipakai untuk asset baru | Authorized admin | Boolean |
| `created_at`, `updated_at` | Audit fields | System | UTC |

### 8.2 `assets`

Model aset: **EOS yang meregistrasi aset saat barang datang dari gudang Comtronics** (bukan Supervisor). Tag asset + SN sudah ada dari gudang — EOS input apa adanya; sistem memvalidasi format (regex CMX) + unik. Barang tidak berpindah antar site; rusak → dikembalikan ke gudang.

| Field | Definisi bisnis | Owner | Validasi | Kelas |
|---|---|---|---|---|
| `assets.id` | ID internal asset | System | UUID | Internal Restricted |
| `site_id` | Site pemilik asset | System dari assignment EOS saat registrasi | Required FK; asset melekat site, tidak pernah EOS/assignment | Internal Restricted |
| `category_id` | Kategori asset | EOS/authorized admin | Valid category | Internal |
| `asset_tag` | Kode asset unik dari gudang | EOS input apa adanya | Unique, required, immutable; format divalidasi regex pola `CMX.*` (tag gudang Comtronics); tidak digenerate sistem | Internal Restricted |
| `name` | Nama asset | EOS/authorized admin | Required | Internal |
| `brand`, `model` | Identitas vendor/model | EOS/authorized admin | Optional | Internal |
| `serial_number` | Nomor seri perangkat dari gudang | EOS input apa adanya | Unique bila tersedia (policy unik); format divalidasi | Internal Restricted |
| `status` | Kondisi/lifecycle asset | Authorized inventory flow | `IN_USE`, `SPARE`, `RETURNED`, `DAMAGED`, `LOST`, `DISPOSED` | Internal |
| `installation_location` | Lokasi detail: gedung/ruang/rack | EOS/authorized admin | Sanitized text | Sensitive Operational |
| `notes` | Catatan asset | Authorized role | Sanitized bounded text | Internal Restricted |
| `registered_by_user_id` | EOS registrasi | Session actor | FK user; registrasi oleh EOS, bukan Supervisor | Sensitive Operational |
| `registered_at` | Waktu registrasi | Backend | UTC | Internal |
| `created_at`, `updated_at` | Audit fields | System | UTC | Internal |
| registration photo | Bukti foto saat registrasi | Attachment module | **Wajib** saat registrasi (`ASSET_REGISTRATION_PHOTO`, context `ASSET`) | Sensitive Operational |

Perubahan status asset = aksi beralasan (reason wajib) + event `activity_log` + foto tambahan bila rusak/hilang. Aset tidak dihapus langsung; status `DISPOSED`/`LOST`/`RETURNED` adalah terminal state. Asset tag immutable. Barang material/sparepart = stok kuantitas per site (§8.3–§8.4), bukan asset terregistrasi.

### 8.3 `inventory_items` (catalog) dan `inventory_stock`

| Field | Definisi | Owner | Validasi |
|---|---|---|---|
| `inventory_items.id` | ID item catalog | System | UUID |
| `inventory_items.sku` | Kode unik item material | Manager/Supervisor/Super Admin | Unique |
| `inventory_items.name` | Nama item | Authorized admin | Required |
| `inventory_items.category` | Kategori material | Authorized admin | Controlled/free text standardized |
| `inventory_items.unit_of_measure` | Unit kuantitas | Authorized admin | e.g. pcs, meter, box |
| `inventory_items.minimum_stock` | Ambang stok minimum opsional | Authorized admin | numeric >= 0 |
| `inventory_items.is_active` | Item dapat dipakai transaksi baru | Authorized admin | Boolean |
| `inventory_stock.site_id` | Site penyimpan stok | System/authorized workflow | FK site; unique bersama item |
| `inventory_stock.item_id` | Item catalog | System/authorized workflow | FK `inventory_items` |
| `inventory_stock.quantity_on_hand` | Saldo material saat ini | System only via transaction | numeric(18,3); no direct UI edit; tidak boleh negatif |
| `inventory_stock.updated_at` | Waktu saldo terakhir berubah | System | UTC |

### 8.4 `inventory_transactions` (ledger)

| Field | Definisi | Source/owner | Validasi |
|---|---|---|---|
| `inventory_transactions.id` | ID mutasi | System | UUID |
| `transaction_type` | Jenis mutasi | Authorized inventory use case | `RECEIPT`, `USAGE`, `ADJUSTMENT`, `DAMAGED`, `LOST`, `RETURN`, `TRANSFER_IN`, `TRANSFER_OUT` |
| `site_id`, `item_id` | Scope mutasi | System | FK site + item; stok site terkait |
| `quantity_delta` | Perubahan kuantitas | Authorized actor | Non-zero; sign/type valid |
| `quantity_before` | Saldo sebelum | Backend transaction | Immutable, >= 0 |
| `quantity_after` | Saldo setelah | Backend transaction | `before + delta`; >= 0; reject negative |
| `occurred_at` | Waktu mutasi | Backend | UTC |
| `reason` | Alasan mutasi | Actor | Required; `ADJUSTMENT`, `DAMAGED`, `LOST`, `TRANSFER_*` wajib catatan |
| `reversal_of_mutation_id` | Mutasi asal bila ini compensating reversal | System | FK transaction posted; immutable |
| `reference_type`/`reference_id` | Referensi finding/asset/doc bila ada | System/actor | Authorized association |
| `performed_by_user_id` | Pelaksana mutasi | Session | Immutable audit; Supervisor dapat post langsung untuk scope tanpa approval Manager pada MVP |
| `created_at` | Waktu record | System | UTC |

Mutation yang sudah posted immutable (tidak dapat edit/delete); reversal menggunakan compensating new mutation dengan `reversal_of_mutation_id`. Stok tidak boleh negatif; saldo di-update dalam database transaction dengan row lock pada `inventory_stock`.

## 9. `inventory_findings`

| Field | Definisi bisnis | Source/owner | Validasi | Kelas/retensi |
|---|---|---|---|---|
| `inventory_findings.id` | ID finding | PostgreSQL | UUID | Internal Restricted; min 2 years after close |
| `site_id` | Site finding | System from EOS assignment / authorized actor | EOS tidak dapat memilih site arbitrer | Internal Restricted |
| `reported_by_user_id` | EOS/actor pembuat | Session actor | Immutable | Sensitive Operational |
| `finding_type` | Jenis temuan | EOS/authorized actor | `ASSET_DAMAGED`, `ASSET_MISSING`, `ASSET_DATA_MISMATCH`, `STOCK_LOW`, `STOCK_DAMAGED`, `ASSET_REGISTRATION_NEEDED`, `OTHER` | Internal |
| `asset_id` | Asset terkait bila ada | EOS/actor | Must belong to same site | Internal Restricted |
| `stock_item_id` | Stock item terkait bila ada | EOS/actor | Must belong to same site | Internal Restricted |
| `description` | Uraian temuan | EOS/actor | Required, sanitized bounded text | Internal Restricted |
| `status` | Lifecycle finding | System/Supervisor | `OPEN`, `UNDER_REVIEW`, `RESOLVED`, `CLOSED`, `REJECTED` | Internal |
| `reviewed_by_user_id` | Reviewer | Supervisor/System | Authorized role | Internal |
| `resolution_note` | Catatan tindakan/hasil | Supervisor | Required for resolve/close/reject policy | Internal Restricted |
| `resolution_reference_type`/`id` | Link mutasi stock/asset action jika ada | Supervisor/System | Valid authorized reference | Internal |
| `reported_at`, `reviewed_at`, `resolved_at`, `closed_at` | Lifecycle timestamps | Backend | UTC | Internal |

Finding bukan tiket SLA customer. Finding tidak memodifikasi asset/status/saldo langsung; perubahan inventory dilakukan melalui use case inventory yang berotorisasi dan dapat direferensikan oleh finding. Inventory Finding terpisah dari stock mutation. Evidence finding melekat via `attachment_links` context `INVENTORY_FINDING`.

## 10. Notification dan Export

### 10.1 `notifications` (Laravel database channel)

Notifikasi in-app only, memakai tabel bawaan Laravel notifications (database channel) + polling frontend. Email/WhatsApp out of scope. Notification bukan authority; source record dan audit adalah authority.

| Field | Definisi | Source/owner | Validasi | Kelas |
|---|---|---|---|---|
| `id` | ID notification (UUID) | System | PK bawaan Laravel | Internal |
| `type` | Class/type notifikasi | System | Event type terbatas (lihat bawah) | Internal |
| `notifiable_type`/`notifiable_id` | Penerima (morph ke `users`) | System | FK user | Sensitive Operational |
| `data` | Payload referensi entity terkait | System | JSON sanitized; no secret | Internal |
| `read_at` | Waktu dibaca | Recipient | UTC nullable | Internal |
| `created_at`/`updated_at` | Waktu dibuat/diperbarui | System | UTC | Internal |

Event notifikasi pada MVP:

```text
DAILY_REPORT_REOPENED      → report dibuka kembali oleh Supervisor/Super Admin
ATTACHMENT_REJECTED        → upload attachment ditolak validasi
EXPORT_COMPLETED           → export selesai di-stream ke requester
```

### 10.2 Export — format, preset, dan audit

Tiga format: **Excel (xlsx) styled** (header bold, border, lebar kolom auto, judul+periode di header, filename dinamis), **PDF formal** (header instansi, siap cetak), **CSV** (data mentah). Kustomisasi: pilih kolom (checkbox per kolom), filter periode/site/status/EOS, disimpan sebagai **preset per user**. Export berjalan sinkron (streamed response); Manager dan Super Admin sesuai scope.

#### `export_presets`

| Field | Definisi | Owner | Validasi |
|---|---|---|---|
| `export_presets.id` | ID preset | System | UUID |
| `user_id` | Pemilik preset | Session | FK users; unique `(user_id, name)` |
| `name` | Nama preset | User (Manager/Super Admin) | Required, max 100 |
| `data_type` | Jenis data yang diexport | User | `ATTENDANCE`, `DAILY_REPORT`, `INVENTORY`, `ASSET`, `INVENTORY_FINDING` |
| `format` | Format output | User | `XLSX`, `PDF`, `CSV` |
| `columns` | Daftar kolom terpilih (ordered) | User | JSONB array of column key; hanya kolom yang tersedia untuk `data_type` |
| `filters` | Filter tersimpan (periode/site/status/EOS) | User | JSONB; site tidak boleh melebihi scope otorisasi saat preset DIPAKAI (divalidasi ulang tiap eksekusi, bukan saat simpan) |
| `created_at`, `updated_at` | Audit fields | System | UTC |

Preset dipakai ulang sebagai konfigurasi awal form export; scope authorization selalu dievaluasi ulang saat eksekusi.

#### Audit export

Tidak ada tabel `export_audit_events` terpisah — audit export adalah event `activity_log` (spatie) dengan `log_name` khusus (mis. `export`), ditulis **sinkron sebelum stream dimulai**, berisi properties: actor, `data_type`, `format`, `filter`/preset, `site_scope`, `outcome` (`COMPLETED`/`FAILED`). Export tidak boleh melanggar privacy visibility: Manager tidak boleh export raw selfie/precise GPS/sensitive attachment (kolom tersebut tidak tersedia pada pilihan kolom untuk role Manager).

### 10.3 Restore test log (`restore_tests`)

| Field | Definisi | Source/owner | Validasi |
|---|---|---|---|
| `restore_tests.test_date` | Tanggal restore test | Operator (Super Admin/ops) | Date; baseline restore drill terjadwal |
| `environment` | Environment test | Operator | Baseline `STAGING` |
| `scope` | Cakupan (PostgreSQL/attachment volume) | Operator | Required |
| `performed_by_user_id` | Operator | Session actor | FK nullable |
| `started_at` / `completed_at` | Waktu mulai/selesai | Backend | UTC |
| `duration_minutes` | Durasi restore | System derived | Integer >= 0 |
| `outcome` | Hasil test | Operator | `PASSED`, `FAILED`, `PARTIAL` |
| `notes` | Catatan | Operator | Nullable |

Backup: pg_dump harian + rsync attachment terjadwal; restore drill dicatat di tabel ini. Bila restore drill dicatat di luar aplikasi, tabel ini opsional.

## 11. Audit (`activity_log` spatie) dan Queue

### 11.1 `activity_log`

Audit memakai `spatie/laravel-activitylog` — dipasang di titik kritis eksplisit (bukan auto-log semua model). Append-only secara aplikasi; retensi minimum 3 tahun.

| Field | Definisi | Aturan |
|---|---|---|
| `activity_log.id` | ID event (bigint PK bawaan spatie) | Immutable |
| `log_name` | Grup/nama log | e.g. `auth`, `attendance`, `report`, `inventory`, `attachment`, `export`, `master` |
| `description` | Kode/uraian event | e.g. `LOGIN`, `LOGIN_LOCKED`, `PASSWORD_RESET`, `ATTENDANCE_CHECKED_IN`, `ATTENDANCE_CLOCKED_OUT`, `DAILY_REPORT_SUBMITTED`, `DAILY_REPORT_REOPENED`, `CHECKLIST_PUBLISHED`, `INVENTORY_MUTATION_POSTED`, `ASSET_STATUS_CHANGED`, `ASSET_REGISTERED`, `ATTACHMENT_REJECTED`, `SENSITIVE_DATA_ACCESSED`, `EXPORT_REQUESTED`, `SITE_COORDINATES_CHANGED`, `ROLE_ASSIGNED` |
| `subject_type`, `subject_id` | Entity yang dipengaruhi (morph) | Required untuk event domain |
| `causer_type`, `causer_id` | Actor (morph, umumnya `users`) | Nullable untuk system/job |
| `properties` | Snapshot perubahan/konteks (JSON) | Tidak menyimpan password/token/secret/binary; berisi before/after terfilter, reason, request context (IP/user agent bila relevan) |
| `created_at`, `updated_at` | Waktu event | UTC |

Event yang WAJIB diaudit (titik kritis eksplisit): login sukses/gagal berulang/lockout, reset password, perubahan role, sensitive access/download (selfie, precise GPS, evidence attachment), perubahan master site (koordinat/timezone/kode), perubahan konfigurasi network link, submit/reopen/void report, publish checklist version, registrasi dan perubahan status asset, inventory mutation + reversal, attachment rejected, export sebelum stream, keputusan workflow finding.

### 11.2 Tabel infrastruktur Laravel (database driver)

Tanpa Redis: session/cache/queue/rate-limit semuanya driver `database`.

| Tabel | Fungsi | Catatan field penting |
|---|---|---|
| `jobs` | Queue jobs (database driver) | `id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`; diproses worker `php artisan queue:work` (container terpisah) |
| `job_batches` | Batch job (bila dipakai) | progress tracking batch queued job |
| `failed_jobs` | Job gagal permanen | `failed_at`, exception; masuk kategori alert worker job failure |
| `cache` / `cache_locks` | Cache + atomic lock database | Rate limiter login (5/15 menit), lockout counter |
| `sessions` | Session persisten | Lihat §3.3 |
| `notifications` | Database notification channel | Lihat §10.1 |

Scheduler menjalankan `php artisan schedule:work` (container terpisah) untuk job terjadwal (thumbnail retry, backup log, dsb). Tidak ada transactional outbox — side-effect async (thumbnail, notifikasi) dipicu via dispatch job pada titik yang sama dengan perubahan bisnis; handler job wajib idempoten.

### 11.3 Analytics fields

| Field | Definisi | Source | Aturan |
|---|---|---|---|
| `filter_date_from/to` | Rentang filter yang diminta | Request | Site-local semantics documented |
| `site_scope` | Site yang included | Authorization/ScopeService | Must not exceed actor permission |
| `report_submission_compliance` | Kepatuhan report submitted | Daily report source | Formula versioned/documented before use as KPI |
| `open_inventory_findings_count` | Jumlah finding unresolved | Finding source | Status `OPEN`/`UNDER_REVIEW` defined explicitly |

## 12. Enum Catalog

### 12.1 Roles

```text
SUPER_ADMIN
MANAGER
SUPERVISOR
HR
EOS
```

### 12.2 Attendance (bukti kehadiran)

```text
attendance_records.status:
NOT_CHECKED_IN
CHECKED_IN
COMPLETED
```

### 12.3 Daily Report dan checklist

```text
daily_reports.status:
DRAFT
SUBMITTED
REOPENED
VOIDED

checklist_versions.status:
DRAFT
PUBLISHED
SUPERSEDED
RETIRED

checklist item input_type:
# dipakai v1:
READ_ONLY
DURATION
PERCENTAGE
ENUM
NUMBER
INTEGER
TEXT
BOOLEAN
LINK_TRAFFIC
SPEEDTEST_RESULT
# reserved future (tidak dipakai v1; renderer input type baru
# membutuhkan frontend/backend release):
COMPOSITE
DECIMAL
URL

checklist rule.rule_type:
REQUIRE_NOTE
REQUIRE_ITEM_EVIDENCE
REQUIRE_FIELD
CONSISTENCY
```

### 12.4 Checklist Master v1 enums

```text
ROUTER_ANOMALY_LOG:
NONE
MINOR
MAJOR
NOT_CHECKED

AP_MONITORING_STATUS:
NORMAL
WARNING
DOWN
UNKNOWN

POWER_CHECK_STATUS:
NORMAL
UNSTABLE
OUTAGE
BACKUP_ACTIVE
NOT_CHECKED

ENVIRONMENT_CONDITION:
GOOD
ATTENTION
UNFIT
NOT_CHECKED
```

### 12.5 Network link dan konektivitas

```text
site_network_links.role:
MAIN
SECONDARY

site_network_links.connection_medium:
FIBER
WIRELESS
CELLULAR
SATELLITE
OTHER

throughput unit:
KBPS
MBPS

LINK_TRAFFIC.status:
AVAILABLE
DOWN
NOT_CHECKED

SPEEDTEST_RESULT.status:
SUCCESS
FAILED
NOT_TESTED

SPEEDTEST_RESULT.route_declaration:
TESTED_VIA_MAIN_LINK
TESTED_VIA_SECONDARY_LINK
```

### 12.6 Attachment

```text
attachment.status (hasil validasi sinkron):
AVAILABLE
REJECTED

attachment.variant_type:
PREVIEW
THUMBNAIL

attachment_link.context_type:
DAILY_REPORT
DAILY_REPORT_SECTION
DAILY_REPORT_ITEM
INVENTORY_MUTATION
INVENTORY_FINDING
ASSET

attachment.attachment_type (katalog):
CHECK_IN_SELFIE
CHECK_OUT_SELFIE
SECTION_EVIDENCE
ITEM_EVIDENCE
LINK_TRAFFIC_EVIDENCE
SPEEDTEST_EVIDENCE
AP_CLOUD_EVIDENCE
ANOMALY_LOG
FINDING_EVIDENCE
ASSET_REGISTRATION_PHOTO
ASSET_STATUS_PHOTO
MUTATION_EVIDENCE
OTHER
```

### 12.7 Inventory

```text
asset.status:
IN_USE
SPARE
RETURNED
DAMAGED
LOST
DISPOSED

inventory_transactions.transaction_type:
RECEIPT
USAGE
ADJUSTMENT
DAMAGED
LOST
RETURN
TRANSFER_IN
TRANSFER_OUT

inventory_finding.status:
OPEN
UNDER_REVIEW
RESOLVED
CLOSED
REJECTED

inventory_finding.finding_type:
ASSET_DAMAGED
ASSET_MISSING
ASSET_DATA_MISMATCH
STOCK_LOW
STOCK_DAMAGED
ASSET_REGISTRATION_NEEDED
OTHER
```

### 12.8 Assignment, notification, export, restore test

```text
eos_site_assignments.status:
ACTIVE
ENDED
CANCELLED

notification event type:
DAILY_REPORT_REOPENED
ATTACHMENT_REJECTED
EXPORT_COMPLETED


export_presets.data_type / export data_type:
ATTENDANCE
DAILY_REPORT
INVENTORY
ASSET
INVENTORY_FINDING

export_presets.format / export format:
XLSX
PDF
CSV

export outcome (properties event activity_log):
COMPLETED
FAILED

restore_test.outcome:
PASSED
FAILED
PARTIAL
```

### 12.9 Backup dan restore

```text
RPO: maksimum 24 jam
RTO: maksimum 8 jam
Restore drill: terjadwal, dicatat pada restore_tests
```

## 13. Data Quality Rules

```text
- Server owns official timestamps, business date, timezone evaluation,
  report number, distance calculation, and stock balance.
- Client evidence (GPS point, accuracy, captured time) is never accepted
  as final decision without server validation, but jarak Haversine
  disimpan sebagai INFORMASI — tidak ada gerbang radius penolakan.
- UUID/FK references must belong to authorized actor/site/report context.
- One EOS has at most one ACTIVE site assignment at a time.
- One attendance record per EOS/site/work_date_local (unique); check-in
  dan clock-out harus tanggal lokal yang sama; maksimum satu selfie
  check-in dan satu selfie clock-out.
- Attendance status: NOT_CHECKED_IN → CHECKED_IN → COMPLETED; tidak ada
  ABSENT, tidak ada window jam, tidak ada late minutes — kehadiran hari
  tanpa absen = kosongnya record (disiplin ditangani vendor).
- Clock-out hanya valid setelah check-in (status CHECKED_IN), Daily Report
  tanggal sama SUBMITTED, dan required evidence AVAILABLE.
- One Daily Report per EOS/site/work_date_local; sequence native
  PostgreSQL dialokasikan atomik hanya saat submit sukses; report number
  never changes on reopen/resubmit.
- Submitted Daily Report is immutable except explicit REOPENED workflow
  (max 7 calendar days after submit; resubmit increments revision;
  reopen wajib reason dan event activity_log).
- Submit requires all required evidence AVAILABLE.
- Site must have exactly one MAIN and one SECONDARY active network link
  to be active for Daily Report.
- Checklist version PUBLISHED is immutable; changes require a new
  version; rules v1 divalidasi eksplisit per rule di kode.
- Asset tag dan serial number berasal dari gudang Comtronics: EOS input
  apa adanya; sistem memvalidasi format (regex CMX) + unik; asset tidak
  berpindah antar site; rusak → dikembalikan ke gudang (RETURNED/DAMAGED).
- Perubahan status aset wajib reason + audit + foto bila rusak/hilang.
- Stock quantity changes only through immutable posted transaction ledger;
  reversal uses compensating mutation; stock never negative (row lock).
- Attachment original is immutable/private pada local disk; validasi
  sinkron pada request → AVAILABLE/REJECTED; derivative dibuat queued job;
  semua download diotorisasi dan diaudit.
- Export is synchronous (streamed) and its activity_log event is written
  before the stream starts; export must respect role privacy visibility —
  Manager cannot export raw selfie/precise GPS/sensitive attachment.
- One attachment is linked to exactly one context; evidence for different
  contexts is uploaded separately.
- Audit events (activity_log) ditulis pada titik kritis eksplisit:
  auth, sensitive access, master change, report lifecycle, checklist
  publish, inventory mutation, asset change, attachment reject, export.
- Retention/purge will check legal holds when implemented; legal hold and
  purge automation are deferred to phase 2 — never silently destroys
  business/audit reference.
```

## 14. Ownership Summary

| Domain | Primary data owner | Write authority | Read baseline |
|---|---|---|---|
| Identity/role | Super Admin | Super Admin/system identity flow (spatie permission) | Own profile; admins per RBAC |
| Site/network link | Super Admin | Super Admin; perubahan koordinat/timezone wajib reason + audit | EOS own site limited; backoffice per role |
| Assignment | Supervisor | Supervisor/Super Admin | EOS own; backoffice per role |
| Attendance | System/EOS evidence | EOS action (check-in/clock-out) | EOS own; HR/Supervisor/Manager per RBAC; raw selfie/GPS restricted |
| Daily Report | EOS/System | EOS draft/submit; Supervisor/Super Admin reopen | EOS own; Supervisor/Manager/Super Admin per RBAC |
| Checklist master | Super Admin publish | Super Admin (publish); draft oleh admin berwenang | EOS consumes published snapshot |
| Inventory (asset + stok) | Site | EOS registrasi asset; Supervisor/Super Admin authorized flow | EOS own site read; others per RBAC |
| Inventory Finding | EOS reporter/Supervisor reviewer | EOS create; Supervisor workflow | Scope-based |
| Attachment | Attachment module | Authorized uploader/system job (derivative) | Private/scope-based; sensitive access diaudit |
| Notification | System | System (queued job) | Recipient only |
| Export preset | User (Manager/Super Admin) | Owner user CRUD sendiri | Owner only |
| Activity log (audit) | System | Application only (spatie activitylog) | Super Admin/limited authorized read |
