# MVP Delivery Backlog — PORTER (Portal Operasional Terpadu Sekolah Rakyat)

## 1. Backlog Principles

- Semua implementasi mengikuti `AGENTS.md` dan `delivery-workflow.md`.
- Satu aplikasi Laravel 13 + Inertia v3 + React 19: satu dev menulis route, controller, Inertia page, dan test dalam satu story (full-stack); tidak ada Contract Story paralel FE/BE, tidak ada OpenAPI.
- User-facing capability hanya Done setelah integration/E2E nyata terhadap aplikasi berjalan.
- Priority: P0 wajib MVP, P1 setelah core stabil, P2 future.
- Semua acceptance criteria mengacu keputusan final `decisions.md` (Laravel 13 + Inertia, queue database driver tanpa Redis, absensi model bukti kehadiran tanpa window/late/kalender/geofence gate, checklist JSON versioned, attachment validasi sinkron + thumbnail queued, export tiga format dengan preset per user, aset registrasi oleh EOS).

## 2. Epics

| Epic | Priority | Outcome |
|---|---:|---|
| E1 Foundation | P0 | Laravel app + starter kit (Fortify/Inertia/Tailwind/shadcn), Docker Compose (Sail) tiga environment, CI (Pest/Larastan/Pint), migration, health |
| E2 Master Data | P0 | User/role/assignment, site + timezone + koordinat (info), site network links, checklist versioning JSON |
| E3 Attendance | P0 | Check-in/clock-out bukti kehadiran: selfie overlay + GPS + server timestamp UTC + jarak Haversine sebagai informasi + unique per hari + gate report untuk clock-out |
| E4 Daily Report and Attachment | P0 | Checklist v1 JSON versioned + rules eksplisit, snapshot, nomor sequence, reopen, attachment validasi sinkron + thumbnail queued |
| E5 Inventory | P0 | Aset register oleh EOS (tag gudang), 6 status aset, stok material/sparepart, ledger mutasi, inventory finding |
| E6 Governance and Operations | P0 | activitylog, notification, export Excel/PDF/CSV preset per user, backup/restore, monitoring |
| E7 Quality, UAT, Pilot | P0 | Security gate, restore drill, UAT staging, pilot site |

## 3. Story Map

### E1 Foundation

| ID | Type | Story | Acceptance criteria | Depends on |
|---|---|---|---|---|
| ST-1.01 | Backend | Laravel app skeleton + starter kit baseline: Fortify, Passkeys, Inertia v3, React 19, Tailwind 4, shadcn/ui, Wayfinder, Pest, Larastan, Pint; konfigurasi session/cache/queue/rate-limit/atomic-lock memakai driver `database` | App boot Inertia page pertama tanpa Redis; semua driver database diverifikasi konfigurasi (tanpa `redis` di konfigurasi runtime); struktur modul sesuai architecture.md | — |
| ST-1.02 | DevOps | Docker Compose (Sail) tiga environment local/staging/production, termasuk container/service worker terpisah `php artisan queue:work` dan `php artisan schedule:work`; promotion branch `faizaldev -> staging -> production` | Production deploy hanya dari branch `production`; secrets terpisah per environment; staging tidak memakai raw production data; worker container berjalan terpisah dari app web; health endpoint sederhana hidup | ST-1.01 |
| ST-1.03 | Data | Migrations + seed mechanism; UUID PK untuk record operasional | Migration tervalidasi di CI; seeder role `spatie/laravel-permission` (5 role fixed, single role per user) + akun admin awal | ST-1.01 |
| ST-1.04 | CI | Pipeline quality gate (Pest + Larastan + Pint) + attachment fixture suite (JPEG, PNG, WebP, HEIC, HEIF, PDF, rusak, MIME mismatch, oversized) | CI merah bila fixture suite atau quality gate gagal | ST-1.01 |
| ST-1.05 | Backend | spatie/laravel-activitylog terpasang + ScopeService (RBAC scope) + Laravel Policies per model | activity_log tercatat di titik kritis eksplisit (login, sensitive access, master change, export, dsb); EOS scoped ke assignment aktif, non-EOS semua site aktif; Policies meng-enforce matriks role PRD | ST-1.01 |

### E2 Master Data

| ID | Type | Story | Acceptance criteria | Depends on |
|---|---|---|---|---|
| ST-2.01 | Backend | Fortify auth hardening: password 12–128 Argon2id, session Secure/HttpOnly/SameSite=Lax, idle 30 menit + absolute 8 jam, lockout 5 gagal/15 menit, `must_change_password` flag + middleware paksa ganti password, reset password oleh Super Admin | Login sukses/gagal/lockout/reset/disable diaudit; change-password sukses menghapus flag `must_change_password` dan merotasi session; reset-password set flag + revoke semua session; semua mutasi non-ganti-password terblokir saat flag aktif | ST-1.05 |
| ST-2.02 | Backend | Admin user CRUD, role change, disable, reset-password (halaman Inertia + controller + policy) | Reset-password set `must_change_password` + revoke semua session + audit `PASSWORD_RESET`; audit `USER_CREATED`/`ROLE_CHANGED`/`USER_DISABLED`; validasi duplicate email/employee code dengan error validasi jelas; patch role = revoke + assign role baru satu transaksi (single role per user) + audit before/after | ST-2.01 |
| ST-2.03 | Backend | Site master: kode, nama, alamat, lat/lng/radius (informasi), timezone IANA (WIB/WITA/WIT), status aktif | Perubahan koordinat/timezone wajib reason + audit; site tidak aktif untuk Daily Report bila network links belum lengkap | ST-1.05 |
| ST-2.04 | Backend | `site_network_links` dengan constraint `unique(site_id, role) where active = true` | Tepat satu MAIN active + satu SECONDARY active per site aktif; `NOT_AVAILABLE` invalid; field provider/service/medium/subscribed throughput sesuai kontrak PRD; snapshot link ke report tersedia | ST-2.03 |
| ST-2.05 | Backend | Checklist master versioned: `checklist_versions` (version, status DRAFT/PUBLISHED/SUPERSEDED/RETIRED, struktur JSON section/item/option/rule) | Satu template global `DAILY_SITE_REPORT`; Super Admin publish; published immutable; perubahan standard rule/option/urutan via master data tanpa release | ST-1.03 |
| ST-2.06 | Backend | Assignment: satu assignment aktif per EOS; inventaris melekat site | Assignment baru ditolak bila EOS masih punya assignment aktif lain; riwayat assignment tidak boleh dihapus; transisi assignment diaudit | ST-2.03 |
| ST-2.07 | Frontend | App shell, login, session/access states, must_change_password forced flow | Login dengan error copy generik; session expired → re-login; access denied sesuai role; `must_change_password = true` → layar ganti password wajib, semua halaman lain terblokir middleware | ST-2.01 |
| ST-2.08 | Frontend | Master Site + Network Link + Assignment screens | Form Site (input IANA timezone terbatas, lat/lng/radius informatif, perubahan wajib reason); form dual-link MAIN/SECONDARY sesuai constraint; state site belum lengkap terlihat jelas; form Assignment EOS dengan validasi client-side satu assignment aktif + pesan error invariant server ditampilkan jelas | ST-2.03, ST-2.04, ST-2.06 |
| ST-2.09 | Frontend | Checklist template editor/preview | List version + editor/preview struktur JSON; lifecycle DRAFT (Manager/Supervisor) → publish (Super Admin) dengan konfirmasi; published immutable read-only di editor | ST-2.05 |

### E3 Attendance

| ID | Type | Story | Acceptance criteria | Depends on |
|---|---|---|---|---|
| ST-3.01 | Backend | Check-in: selfie + koordinat GPS + server timestamp UTC; jarak Haversine di PHP disimpan sebagai informasi | GPS diambil sekali dengan toleransi longgar (tanpa penolakan radius); jarak ke site dihitung backend dan disimpan; timestamp resmi = waktu penerimaan server (UTC canonical); `work_date_local` + `site_timezone_snapshot` disimpan; tanpa window jam, tanpa late minutes, tanpa klasifikasi EARLY/ON_TIME/LATE; check-in hanya oleh EOS dengan assignment aktif; attempt diaudit | ST-2.06 |
| ST-3.02 | Backend | Unique constraint: satu record per EOS + site + tanggal lokal | `unique(eos, site, work_date_local)` di database; double submit ditolak dengan error jelas (check-in kedua `ALREADY_CHECKED_IN`, clock-out kedua `ALREADY_CLOCKED_OUT`); check-in dan clock-out harus tanggal lokal sama | ST-3.01 |
| ST-3.03 | Backend | Clock-out + gate report | Clock-out valid hanya setelah check-in dan Daily Report tanggal tsb `SUBMITTED` + required evidence `AVAILABLE`; bila report belum submitted, respons error gate yang UX konversi jadi arahan melengkapi report (flow kontinu); status record `NOT_CHECKED_IN` → `CHECKED_IN` → `COMPLETED`; selfie clock-out maksimum 1 | ST-3.02, ST-4.05 |
| ST-3.04 | Backend | Selfie evidence enforcement (backend) | Maksimum satu selfie check-in dan satu selfie clock-out per record; selfie milik uploader, tipe tepat check-in/clock-out; ownership/type/status AVAILABLE di-gate pada controller | ST-3.01 |
| ST-3.05 | Frontend | Beranda EOS today | State beranda: sebelum check-in, sudah check-in (report belum submitted), report submitted (clock-out tersedia), clock-out selesai (COMPLETED); hari tanpa absen = record kosong (tidak ada status ABSENT sistem); waktu ditampilkan dalam timezone site (label WIB/WITA/WIT) | ST-3.01, ST-2.07 |
| ST-3.06 | Frontend | Check-in capture: kamera selfie overlay + GPS | Overlay lingkaran "posisikan wajah di dalam lingkaran"; deteksi wajah opsional via browser FaceDetector API bila tersedia (indikasi wajah terdeteksi, tombol jepret aktif setelah kamera stabil); fallback overlay panduan saja bila API tidak tersedia; kamera utama via `getUserMedia` dengan fallback `<input type="file" accept="image/*" capture="user">` (tanpa gallery upload); koordinat GPS diambil sekali; jarak ke site ditampilkan sebagai informasi | ST-3.01, ST-2.07 |
| ST-3.07 | Frontend | Clock-out capture + blocked state | Clock-out capture selfie + GPS sama seperti check-in; blocked state (report belum `SUBMITTED` / required evidence belum `AVAILABLE`) menampilkan penjelasan + tombol menuju form Daily Report (flow kontinu), bukan penolakan mentah | ST-3.03, ST-2.07 |
| ST-3.08 | Frontend | Riwayat attendance | Riwayat per EOS: tanggal lokal, waktu check-in/clock-out, jarak ke site, thumbnail selfie; timezone label konsisten; tanpa kolom late/klasifikasi; klik record membuka detail dengan bukti lengkap | ST-3.05 |
| ST-3.09 | Integration/E2E | Attendance journeys: check-in, double submit, gate report, clock-out, WIB/WITA/WIT | Unique constraint terverifikasi (double submit ditolak); gate report terverifikasi (clock-out tanpa report blocked → lengkapi report → clock-out sukses); tanggal lokal sama check-in/clock-out terverifikasi di tiga timezone; jarak Haversine tersimpan sebagai informasi | ST-3.01–ST-3.08 |

### E4 Daily Report and Attachment

| ID | Type | Story | Acceptance criteria | Depends on |
|---|---|---|---|---|
| ST-4.01 | Backend | Report draft + snapshot: header `daily_reports` (status, revision, checklist_version_id, report_number nullable, work_date_local, timezone snapshot, snapshot JSONB definisi checklist + site + EOS + network links) + `daily_report_answers` (jawaban per item, nilai terstruktur JSON per item) + unique EOS+site+work_date | Report menyimpan snapshot; report lama tidak berubah saat checklist publish versi baru; Info Umum auto dari site + EOS | ST-2.05 |
| ST-4.02 | Backend | Rules validasi v1 eksplisit per rule di kode (FormRequest/service), bukan interpreter JSON generik | Rules FR-10 dipertahankan penuh: MINOR→note wajib, MAJOR→note+evidence, NOT_CHECKED→reason; AP NORMAL→offline 0, WARNING/DOWN→offline >=1, UNKNOWN→note; suhu wajib -20.0..80.0 satu desimal; kelistrikan UNSTABLE/OUTAGE/BACKUP_ACTIVE→note+evidence; lingkungan ATTENTION→note, UNFIT→note+evidence; evidence section required 1–5 per section operasional, Info Umum 0–5; satu attachment tepat satu context (screenshot Main Link tidak otomatis jadi evidence Secondary Link) | ST-4.01 |
| ST-4.03 | Backend | LINK_TRAFFIC + dua SPEEDTEST_RESULT terpisah (MAIN dan SECONDARY) | LINK_TRAFFIC window 08:00–17:00, status AVAILABLE/DOWN/NOT_CHECKED dengan rule evidence/note; throughput KBPS (sama) / MBPS (x1000) ke `normalized_kbps`, unit lain ditolak; speedtest terpisah dengan status SUCCESS/FAILED/NOT_TESTED dan rule field/note; link down → tetap wajib FAILED/NOT_TESTED reason `Link down`; `route_declaration` dicatat tanpa klaim verifikasi path otomatis | ST-4.02 |
| ST-4.04 | Backend | Submit: sequence global + gate evidence | Nomor `CMX.WR.YYYYMM.SEQUENCE` via PostgreSQL SEQUENCE global bigint, dialokasikan atomik hanya saat submit sukses (sequence tidak reset, YYYYMM site local date, draft tanpa nomor); required evidence harus `AVAILABLE`; satu report per EOS+site+work date; submit + audit atomik | ST-4.02, ST-4.06 |
| ST-4.05 | Backend | Reopen/revision | Authority Supervisor / Super Admin; reason wajib; maksimum 7 hari kalender setelah submit; resubmit menambah `revision` tanpa mengubah nomor; audit tiap transisi (created, submitted, reopened, resubmitted); `SUBMITTED` immutable kecuali reopen; `VOIDED` hanya governance | ST-4.04 |
| ST-4.06 | Backend | Attachment upload validasi sinkron | Local disk private (storage/app, di luar public/); download via controller terkontrol + audit; format JPG/PNG/WebP/HEIC/PDF, max 10MB; validasi sinkron di request (magic byte, size, hash SHA-256, decode-safe) → langsung `AVAILABLE`; rejected tidak tersedia ke user biasa + audit event; limit per context (report 10, section 5, item 5, selfie 1+1, mutation 5); PDF tidak untuk selfie/mutation; original immutable/private | ST-1.04 |
| ST-4.07 | Backend | Thumbnail/preview WebP via queued job | Queued job (queue database driver) menghasilkan preview WebP max 2048px + thumbnail WebP 480px via Intervention Image (Imagick + libheif untuk HEIC); PDF original tidak direcompress; HEIC gagal decode ditolak dengan pesan aman; job berjalan di worker container terpisah | ST-4.06 |
| ST-4.08 | Frontend | Daily Report form: checklist v1, Evidence Section per section, LINK_TRAFFIC, dua kartu speedtest | Kbps/Mbps selector; dual-link snapshot display; validasi jump ke field bermasalah; state upload (uploading → AVAILABLE / REJECTED); Info Umum read-only auto | ST-4.01, ST-2.07 |
| ST-4.09 | Frontend | PWA installable | App shell + static assets di-cache (installable); tanpa offline draft (regresi: tidak ada IndexedDB draft/sync/expiry UI); attachment tidak bisa di-queue offline | ST-4.08 |
| ST-4.10 | Integration/E2E | Submit/number/read-only/reopen/attachment journeys | Nomor sequence unique dan tidak berubah saat reopen; max 5/5/10 attachment; reopen 7 hari; HEIC; rejected; thumbnail queued muncul setelah worker memproses | ST-4.01–ST-4.09 |

### E5 Inventory

| ID | Type | Story | Acceptance criteria | Depends on |
|---|---|---|---|---|
| ST-5.01 | Data | Inventory model: `assets` + `inventory_items` (catalog) + `inventory_stock` (saldo per site+item) + `inventory_transactions` (ledger immutable + `reversal_of_mutation_id`, row lock, saldo tidak negatif) + `inventory_findings` terpisah | Tipe mutasi RECEIPT/USAGE/ADJUSTMENT/DAMAGED/LOST/RETURN/TRANSFER_IN/TRANSFER_OUT; ADJUSTMENT/DAMAGED/LOST/TRANSFER wajib note; mutasi posted immutable, koreksi via compensating mutation `reversal_of_mutation_id`; maks 5 attachment per mutation | ST-1.03 |
| ST-5.02 | Backend | Aset: registrasi oleh EOS saat barang datang dari gudang Comtronics | Tag asset + SN sudah ada dari gudang — EOS input apa adanya; sistem validasi format (regex CMX) + unik; barang tidak berpindah antar site (rusak → dikembalikan ke gudang); foto wajib saat registrasi; asset tag immutable | ST-5.01 |
| ST-5.03 | Backend | Status aset 6 + transaksi beralasan | Status: IN_USE, SPARE, RETURNED, DAMAGED, LOST, DISPOSED; perubahan status = transaksi beralasan + audit + foto bila rusak/hilang; aset rusak/hilang tidak dihapus (histori status tersimpan) | ST-5.02 |
| ST-5.04 | Backend | Stock mutation: Supervisor post langsung tanpa approval Manager (MVP) + material/sparepart stok kuantitas per site | Scoped site; audited; saldo tidak negatif via transaction/row lock | ST-5.01 |
| ST-5.05 | Backend | Inventory Finding create/review/resolve/reject dengan audit | Finding scoped site + evidence; terpisah dari stock mutation (tidak silently mutate stock) | ST-5.01 |
| ST-5.06 | Frontend | EOS inventory views + registrasi aset + create finding | Inventaris site: aset (dengan registrasi aset baru oleh EOS: tag dari gudang + SN + foto wajib) dan material/sparepart (read-only untuk EOS); Inventory Finding list, buat Finding, detail Finding; EOS tidak bisa langsung ubah asset/stock | ST-5.02, ST-5.04, ST-5.05 |
| ST-5.07 | Frontend | Backoffice asset/stock admin | Inventory dashboard/listing aset, detail/create/edit asset, stock item dan histori mutasi; max 5 attachment per mutation | ST-5.01, ST-5.02, ST-5.04 |
| ST-5.08 | Frontend | Supervisor finding review | Inventory Finding list, review/resolve/reject Finding | ST-5.05 |
| ST-5.09 | Integration/E2E | Aset registrasi, status 6, mutation post, reversal, negative stock rejection, finding journey | Verifikasi immutability dan reversal; registrasi aset oleh EOS; perubahan status beralasan + audit | ST-5.01–ST-5.08 |

### E6 Governance and Operations

| ID | Type | Story | Acceptance criteria | Depends on |
|---|---|---|---|---|
| ST-6.01 | Backend | Audit via activitylog di titik kritis + role visibility matrix | 5 role fixed single role per user; login/lockout/reset/disable/role change/sensitive access/download/export/master change diaudit; audit tidak menyimpan password/secret/session token raw; Manager default tanpa raw selfie/precise GPS/sensitive; HR tanpa technical evidence default; semua akses sensitive diaudit | ST-1.05 |
| ST-6.02 | Backend | Export engine tiga format: Excel (xlsx) styled + PDF formal + CSV, dengan pilihan kolom + filter + preset per user | Excel: header bold, border, lebar kolom auto, judul+periode di header, filename dinamis; PDF: header instansi, siap cetak; CSV: data mentah; kustomisasi pilih kolom (checkbox per kolom), filter periode/site/status/EOS, preset disimpan per user dan dipakai ulang; sinkron (streamed), audit event sebelum stream; privacy visibility di-enforce (Manager tanpa raw selfie/precise GPS/sensitive) | ST-6.01 |
| ST-6.03 | Frontend | Export UI (Super Admin/Manager) + preset management | Filter data type/periode/site scope + pilih kolom per checkbox; simpan preset per user, load preset; state `Menyiapkan berkas…` + error retry; hasil stream download langsung | ST-6.02 |
| ST-6.04 | Backend | Notification in-app MVP | Tabel notifications Laravel (database channel) + polling; event: report reopened, attachment rejected, export selesai; bukan authority; tanpa email/WhatsApp | ST-6.01 |
| ST-6.04a | Frontend | Notification center UI | Badge unread di header semua halaman; Notification Center daftar + `Tandai dibaca`/`Tandai semua dibaca` + tautan ke record sumber; empty state | ST-6.04 |
| ST-6.05 | DevOps | Backup/restore: pg_dump harian + rsync attachment terjadwal + restore drill | RPO <= 24 jam, RTO <= 8 jam; restore drill terjadwal dengan catatan date/operator/duration/outcome | ST-1.02 |
| ST-6.06 | DevOps | Monitoring/alert | Health endpoint sederhana + structured log stdout; backup job failure, disk >80%, site attachment quota >80%, worker backlog naik, repeated worker job failure | ST-6.05 |
| ST-6.07 | Backend | Analytics dashboard queries (Inertia) + audit-logs read | Analitik attendance (kehadiran/report/inventory) scoped filter (tanggal lokal/site/role EOS/status) via Inertia props; audit log list Super Admin; role visibility sesuai matriks (Manager tanpa raw selfie/precise GPS/sensitive; akses sensitive diaudit); pagination | ST-6.01 |
| ST-6.07a | Frontend | Dashboard & analitik screens | Dashboard Eksekutif/HR/EOS + analitik + audit log list; semua filter diproses server-side; drill-down dari KPI ke daftar detail tersedia; role visibility di UI konsisten dengan matriks backend | ST-6.07 |

### E7 Quality, UAT, Pilot

| ID | Type | Story | Acceptance criteria | Depends on |
|---|---|---|---|---|
| ST-7.01 | Backend | Security release gate remediation | Semua test matrix security hijau (lockout, session, BOLA/policy, upload validation, log hygiene) | All P0 |
| ST-7.02 | Backend | Full boundary test suite attendance/report/attachment | Double submit (unique), gate report clock-out, tanggal lokal sama, max 5/5/10 attachment, HEIC, reopen 7 hari, sequence nomor unique; tanpa regresi elemen offline di UI | E3, E4 |
| ST-7.03 | Backend | UAT di staging oleh business user | Bukti skenario, expected/actual, tester, environment, waktu, sign-off; staging gate sebelum pilot | All P0 |
| ST-7.04 | Backend | Pilot site WIB/WITA/WIT | Site fixture tiga timezone; Android Chrome dan iOS Safari dua major version terbaru; selfie overlay + GPS + gate report diverifikasi di perangkat nyata | ST-7.03 |
| ST-7.05 | DevOps | Production go-live checklist | Promotion branch hanya dari `production`; release checklist lengkap; backup/restore drill terakhir lulus | ST-7.01–04 |

### Deferred (Fase 2 — di luar MVP)

| ID | Type | Story | Catatan |
|---|---|---|---|
| ST-6.08 | Backend | Legal hold | Deferred — dibangun saat retention/purge automation diimplementasikan. Saat diaktifkan: legal hold menyimpan reason, creator, data scope, start/end, audit event; purge/retention wajib cek legal hold. Visibility matrix ADR-034 tetap berlaku penuh; ini scope decision, bukan open operational item. |

## 4. Out of Scope (dihapus dari MVP — keputusan `decisions.md`)

- Disiplin kehadiran: window jam, late minutes, klasifikasi EARLY/ON_TIME/LATE/EARLY_CLOCK_OUT, geofence gate + borderline, kalender kerja/holiday/override/`EFFECTIVE_WORKING_DAY`, periode 21–20, job ABSENT, Attendance Request (seluruh modul) — ditangani absensi vendor EOS.
- Face matching/biometrik server-side; tidak ada penyimpanan biometrik.
- Transactional outbox (diganti Laravel Queue database driver + Scheduler).
- OpenAPI contract layer (diganti Inertia route + FormRequest).
- Seluruh out of scope lama yang tetap: app native, ticketing/SLA, Zabbix/SNMP wajib, payroll, cross-midnight, offline draft/attendance, email/WhatsApp notifikasi, PostGIS, Google Maps/Mapbox, external SSO, multi-tenant.

## 5. Open Operational Configuration (non-blocking)

Threshold CPU/RAM/suhu/traffic, SOP routing speedtest secondary, isi master data awal, teks final privacy notice, dan detail purge arsip 2/3 tahun dicatat sebagai open item di `docs-consistency-report.md`; tidak memblokir story di atas.

## 6. Definition of Ready

A story is Ready when it has scope, actor, acceptance criteria, dependencies, RBAC/policy/audit rule, data impact, and test expectation.

## 7. Definition of Done

Use `delivery-workflow.md` DoD plus: merged review, CI green, no unresolved P0/P1 defect, and documentation updated.
