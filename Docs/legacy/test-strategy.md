# Test Strategy

## 1. Objective

Test strategy memastikan business rule, security control, data integrity, kontrak halaman/route Inertia + FormRequest, UX recovery state, dan deployment readiness terverifikasi sebelum release. Semua rule di dokumen ini mengikuti keputusan final di brief konsolidasi; implementasi yang masih memakai aturan lama (Go/Redis/outbox, window absensi, geofence gate, Attendance Request) harus gagal pada suite ini.

Stack test: **Pest** (feature + unit) untuk backend Laravel, React Testing (component) untuk frontend, browser E2E untuk journey Inertia. CI quality gate: **Pint, Larastan, Pest**.

## 2. Test Pyramid and Environments

| Layer | Scope | Environment |
|---|---|---|
| Unit (Pest) | Domain/service rule (Haversine, work_date lokal, normalisasi throughput, sequence, status transition, checklist rules) | Local/CI |
| Component | React/Inertia component/form/state | Local/CI |
| Feature (Pest) | Route Inertia + FormRequest + Policy + Eloquent/PostgreSQL (constraint, transaction), file storage disk, queued job (queue database), mail/notify | Local/CI (Sail container) |
| E2E | Browser/PWA journey terhadap aplikasi Inertia full-stack | Staging/CI browser |
| UAT | Operational validation by business user | Staging/pilot site |
| Recovery | Backup/restore/deploy rollback | Non-production recovery environment |

Environment wajib terpisah: local development, staging, production. Staging adalah gate wajib sebelum pilot dan production release, dan tidak boleh memakai raw production data.

## 3. Mandatory Business Rule Tests

Seluruh test di bawah diimplementasikan sebagai Pest feature test (request → route → assert response/state) atau unit test service bila murni logika domain.

### Attendance — bukti kehadiran (model baru)

```text
::- Check-in: selfie + koordinat GPS + server timestamp; record dibuat dengan status CHECKED_IN; selfie check-in tepat satu.
::- Clock-out: selfie + koordinat GPS + server timestamp; hanya valid setelah check-in (ALREADY final gate) — record menjadi COMPLETED; selfie clock-out tepat satu.
::- Clock-out ditolak bila Daily Report work_date tsb belum SUBMITTED (atau required evidence belum AVAILABLE); response mengarahkan EOS melengkapi report (flow kontinu), bukan error dead-end.
:::- Satu attendance record per EOS + site + work_date_local (unique constraint): check-in kedua ditolak ALREADY_CHECKED_IN; clock-out kedua ditolak ALREADY_CLOCKED_OUT; attempt gagal tetap menghasilkan audit event; percobaan double submit BERSAMAAN (paralel/race — PRD:655) diuji: dua request check-in paralel untuk EOS+site+tanggal yang sama, tepat satu record dibuat, satunya ditolak ALREADY_CHECKED_IN (unique constraint database menahan race, bukan hanya validasi aplikasi berurutan).
::- Check-in dan clock-out harus berada pada local calendar date yang sama (timezone site); pasangan lintas tanggal ditolak.
::- Server timestamp (UTC) meng-override client device timestamp yang dimanipulasi; work_date_local dihitung backend dari timezone site.
:::- Jarak Haversine ke site dihitung backend (PHP), disimpan sebagai distance_m (informasi); accuracy GPS tersimpan sebagai accuracy_m pada record (PRD:653, BR-02); TIDAK ada penolakan radius/geofence/borderline — test regresi: koordinat jauh tetap diterima dan jaraknya tercatat.
::- Status record: NOT_CHECKED_IN → CHECKED_IN → COMPLETED; hari tanpa absen = tidak ada record (bukan job ABSENT, bukan klasifikasi EARLY/ON_TIME/LATE/EARLY_CLOCK_OUT, tanpa late_minutes) — test regresi: field/enum klasifikasi lama tidak muncul.
::- Test regresi scope terhapus: window jam 05:00–11:00 / 16:00–23:59, kalender kerja/holiday/override/EFFECTIVE_WORKING_DAY, periode 21–20, Attendance Request — endpoint/entitas lama harus tidak ada (404/route missing), bukan sekadar tidak dipakai.
::- Timezone site (WIB/WITA/WIT) menghasilkan work_date_local dan penampilan waktu lokal yang benar; record menyimpan timezone snapshot; timestamp tetap UTC canonical.
```

### Daily Report

```text
:::- Draft dibuat untuk hari kerja terkait; DRAFT tidak punya nomor report; nomor CMX.WR.YYYYMM.SEQUENCE dialokasikan hanya ketika submit sukses; sequence global (PostgreSQL SEQUENCE) atomik dan tidak reset; YYYYMM memakai site local date saat submit.
:::- Format nomor: SEQUENCE zero-padding minimal 4 digit (0110 tetap "0110"), bukan cap — sequence >= 10000 tetap valid dan formatnya melebar alami (CMX.WR.YYYYMM.10000, tanpa pemotongan/overflow); test batas tepat 9999 → 10000.
:::- Submit concurrency: sequence unique global atomik (parallel submit test).
::- Snapshot: header menyimpan checklist_version_id + snapshot JSONB (definisi checklist + site + EOS + network links); report lama tetap konsisten setelah checklist baru dipublish.
::- SUBMITTED immutable kecuali reopen.
::- Reopen: authority Supervisor/Super Admin (permission report.reopen); reason wajib; maksimum 7 hari kalender setelah submit; resubmit menambah revision tanpa mengubah nomor; transisi status diaudit (activitylog).
::- VOIDED hanya untuk governance.
::- Satu Daily Report aktif per EOS + site + work_date_local (unique constraint); submit ganda ditolak.
::- Checklist version lifecycle: DRAFT → PUBLISHED (Super Admin, publish immutable) → SUPERSEDED/RETIRED; hanya versi PUBLISHED yang bisa dipakai report baru; struktur JSON divalidasi skema saat publish.
```

### Checklist rules (FR-10) — validasi eksplisit per rule

Rules divalidasi backend FormRequest/service dengan implementasi eksplisit per rule — test per rule v1 eksplisit, bukan interpreter JSON condition/action generik:

```text
::- Log Anomali MINOR => note wajib; MAJOR => note + item evidence AVAILABLE; NOT_CHECKED => note alasan.
::- Status AP NORMAL => AP offline harus 0; WARNING/DOWN => AP offline >= 1; UNKNOWN => note alasan, count boleh null; Dokumentasi AP Cloud => minimum 1 attachment AVAILABLE.
::- Power UNSTABLE/OUTAGE/BACKUP_ACTIVE => note + item evidence AVAILABLE; NOT_CHECKED => note alasan.
::- Suhu wajib diisi (range -20.0 sampai 80.0, satu desimal Celsius); tidak ada NOT_MEASURED.
::- Environment ATTENTION => note; UNFIT => note + item evidence AVAILABLE; NOT_CHECKED => note alasan.
::- AP offline vs status AP (konsistensi lintas item) divalidasi backend.
::- Semua enum/range/rules FR-10 lama dipertahankan: test edge range suhu (−20.0, 80.0, −20.1 tolak, 80.1 tolak, dua desimal ditolak), enum tidak valid ditolak.
```

### Section evidence dan batas attachment per context

```text
::- Semua section operasional (Router & Firewall, Access Point, Infrastruktur & Lingkungan, Konektivitas) wajib minimal satu attachment AVAILABLE pada Evidence Section.
::- Info Umum: Evidence Section optional 0–5.
::- Maksimum 5 attachment per Evidence Section; 5 per item evidence; 10 per Daily Report (hitungan sederhana, tanpa dedup lintas context).
::- Satu attachment tepat satu context (unique per attachment); tidak ada taut ulang lintas context — evidence untuk context lain diunggah terpisah; file sama boleh diunggah ulang.
::- Satu screenshot Main Link tidak otomatis menjadi evidence Secondary Link; bukti Secondary Link diunggah terpisah.
::- Submit ditolak bila evidence wajib belum AVAILABLE (termasuk file yang baru ditolak/validasi gagal).
```

### Dual-link site network dan konektivitas

```text
::- site_network_links constraint unique(site_id, role) where active = true: tepat satu MAIN active dan satu SECONDARY active per site aktif.
::- Konfigurasi MAIN/SECONDARY belum lengkap => site tidak boleh aktif untuk Daily Report.
::- Site tidak boleh punya dua MAIN active atau dua SECONDARY active; duplikat ditolak oleh constraint.
::- LINK_TRAFFIC: status AVAILABLE | DOWN | NOT_CHECKED; window measurement tetap 08:00–17:00 site-local.
::- LINK_TRAFFIC AVAILABLE => avg inbound, peak inbound, avg outbound, peak outbound + source + item evidence AVAILABLE wajib.
::- LINK_TRAFFIC DOWN => note + item evidence AVAILABLE; NOT_CHECKED => note alasan.
::- Throughput unit: KBPS => normalized_kbps = nilai sama; MBPS => nilai x 1000; Gbps, KB/s, MB/s, byte-per-second ditolak.
::- Input source value + unit disimpan; backend menghitung normalized_kbps (client tidak bisa mengirim normalized_kbps langsung — mass assignment test).
::- Dua item speedtest terpisah: Connection Test Main Link (SPEEDTEST_RESULT) dan Connection Test Secondary Link (SPEEDTEST_RESULT); bukan satu field generic.
::- SPEEDTEST_RESULT SUCCESS => download, upload, latency, jitter, screenshot AVAILABLE wajib.
::- SPEEDTEST_RESULT FAILED / NOT_TESTED => note alasan wajib.
::- Link down => entry speedtest link tersebut tetap wajib dengan FAILED atau NOT_TESTED dan reason `Link down`.
::- Aplikasi hanya mencatat route_declaration (TESTED_VIA_MAIN_LINK / TESTED_VIA_SECONDARY_LINK); tidak mengklaim verifikasi actual network path otomatis.
::- Konfigurasi link (MAIN + SECONDARY) di-snapshot ke Daily Report.
```

### Attachment — validasi sinkron + thumbnail queued

```text
::- Validasi sinkron pada request: file lolos tipe/format/size/decode/magic-byte => langsung AVAILABLE (tanpa status QUARANTINED/PROCESSING) — test regresi: enum status lama pending/validated/quarantined tidak muncul di mana pun.
::- Allowed type: JPG/JPEG, PNG, WebP, HEIC/HEIF, PDF; maksimum 10 MB per file.
::- Maksimum 10 attachment per Daily Report; 5 per section evidence; 5 per item evidence; 5 per inventory mutation; satu selfie check-in; satu selfie clock-out.
::- PDF boleh pada report/section evidence; PDF ditolak untuk selfie dan inventory mutation.
::- MIME/signature mismatch ditolak: content-type header dan magic byte harus konsisten dengan extension yang dinyatakan.
::- Oversized file ditolak sebelum dan sesudah upload (client preflight + server FormRequest validation).
::- Tidak ada malware scan (ClamAV dihapus — ADR-044): validasi-only pipeline.
::- HEIC/HEIF: fixture valid diproses queued job (Intervention/Imagick + libheif) menjadi preview WebP max 2048 px dan thumbnail WebP max 480 px; HEIC gagal decode => ditolak dengan pesan aman.
::- Original immutable/private (storage/app) dengan SHA-256; PDF original tidak direcompress; derivative tidak menggantikan original.
::- Semua download lewat controller terkontrol (Policy + scope); akses/download sensitive evidence diaudit (activitylog).
::- Nama file user disimpan hanya sebagai metadata ter-sanitasi; storage key UUID server-side; path traversal ditolak.
::- CI fixture wajib: JPEG, PNG, WebP, HEIC, HEIF, PDF valid, file rusak, MIME/signature mismatch, oversized file.
```

### PWA dan konektivitas

```text
:- Tidak ada penyimpanan draft offline: tidak ada IndexedDB draft, aturan 14 hari, tombol sync, atau state gagal sinkronisasi (regresi: UI tidak menampilkan elemen tersebut).
:- Report authoring online-only; tanpa konektivitas aplikasi tidak dapat dipakai.
:- Attachment tidak dapat di-queue offline; report tidak bisa submit jika evidence wajib belum AVAILABLE.
:- PWA installable (app shell + static assets saja).
```

### Inventory and Finding

```text
::- Asset belongs to site, not EOS assignment.
::- Registrasi aset oleh EOS saat barang datang dari gudang: tag/SN sudah ada dari gudang Comtronics, EOS input apa adanya; format tag (regex CMX) + uniqueness divalidasi; foto wajib saat registrasi.
::- Asset tag immutable setelah registrasi; barang tidak berpindah antar site; rusak => dikembalikan ke gudang.
::- Status aset 6 nilai: IN_USE, SPARE, RETURNED, DAMAGED, LOST, DISPOSED; perubahan status = transaksi beralasan + audit + foto bila rusak/hilang.
::- Stock balance hanya berubah melalui posted mutation ledger; posted mutation immutable.
::- Tipe mutasi: RECEIPT, USAGE, ADJUSTMENT, DAMAGED, LOST, RETURN, TRANSFER_IN, TRANSFER_OUT.
::- ADJUSTMENT, DAMAGED, LOST, TRANSFER wajib note.
::- Reversal memakai compensating mutation dengan reversal_of_mutation_id, bukan edit/delete.
::- Stok tidak boleh negatif (transaction + row lock test dengan concurrency); negative stock rejected.
::- EOS tidak bisa langsung mengubah asset status di luar flow registrasi dan tidak bisa mengubah stock balance.
::- Inventory Finding terpisah dari stock mutation; scoped site dan requires evidence.
```

### Notification

```text
:::- Event generation untuk 2 event tersisa: report reopened (DAILY_REPORT_REOPENED), attachment rejected (ATTACHMENT_REJECTED) masing-masing menghasilkan notification database Laravel untuk recipient yang benar; tidak ada event export selesai (export sinkron/streamed).
::- Test regresi: event attendance-request tidak ada (modul dihapus).
::- Mark read hanya untuk notification milik recipient; read_at terisi; idempotent; notification user lain => 404/403.
::- Notification bukan authority: perubahan source record dan audit tetap menjadi sumber kebenaran; notifikasi tidak memuat secret/binary.
::- List: pagination, unread_only filter, urutan created_at desc.
```

### Export

```text
:::- Tiga format: Excel (xlsx styled — header bold, border, lebar kolom auto, judul+periode header, filename dinamis), PDF formal (header instansi, siap cetak), CSV (data mentah).
::- Kustomisasi: pilih kolom (checkbox per kolom), filter periode/site/status/EOS.
::- Preset per user: simpan preset, dipakai ulang, hanya milik user tersebut (BOLA test).
::- Sinkron (streamed response); audit event ditulis sebelum stream dimulai: actor, role, tipe data, filter, scope, format, timestamp, outcome.
::- Privacy visibility di-enforce: Manager tidak boleh export raw selfie/precise GPS/sensitive attachment; pelanggaran scope => 403 EXPORT_SCOPE_FORBIDDEN.
:::- Export sesuai matriks role PRD §4: Super Admin penuh; Manager ringkasan lintas-site sesuai scope; Supervisor site scope; HR kehadiran sesuai otorisasi; EOS tidak bisa export. Test role: export Supervisor (site scope) sukses untuk data dalam scope-nya; export HR data kehadiran sesuai otorisasi sukses sedangkan data non-kehadiran (report/inventory) ditolak; pelanggaran scope tetap 403 EXPORT_SCOPE_FORBIDDEN.
```

### Role visibility dan export scope

```text
::- Role matrix per route/action (Policy + spatie permission): SUPER_ADMIN, MANAGER, SUPERVISOR, HR, EOS.
::- Supervisor (site scope = semua site aktif) dapat melihat selfie/evidence/precise GPS untuk review kehadiran/report; Manager default tidak melihat raw selfie, precise GPS, atau sensitive evidence; HR tidak otomatis melihat technical Daily Report evidence; EOS hanya data miliknya dan data site penugasannya.
::- Setiap akses/download selfie, precise GPS, sensitive evidence menghasilkan audit event.
```

## 4. Security Test Matrix

| Area | Required test |
|---|---|
| Authentication | Generic login failure, disabled account, dua counter independen per identifier DAN per IP (RateLimiter, window/TTL 15 menit), lockout 15 menit saat SALAH SATU counter capai 5, identifier tak dikenal hanya menaikkan counter IP (anti-enumeration, response tetap error generik), login sukses mereset counter identifier user, lockout diaudit, password/session revoke |
| Session/CSRF | Driver database; idle timeout 30 menit, absolute timeout 8 jam, session regenerate saat login/password change, revoke saat logout/reset/disable, cookie Secure+HttpOnly+SameSite=Lax, CSRF Laravel pada semua route mutasi (token terikat session, invalid saat regenerate), login Origin allowlist (mismatch tolak / absen terima) |
| Rate limit non-login | Check-in/out 30/min/user, upload 20/min/user, submit report 10/min/user, approve/reject/resolve/reopen 60/min/user, analytics/export 30/min/user (key user_id auth); 429 + Retry-After |
| must_change_password gating | Flag aktif (setelah reset Super Admin ATAU user baru dipaksa ganti password saat login pertama — PRD:591): semua mutasi selain ganti password ditolak middleware must_change_password (read/GET tetap boleh); change password sukses merevoke session lain, meregenerate session saat ini, meng-clear flag, mengaudit PASSWORD_CHANGED; shared props halaman mengekspos flag |
| RBAC | Role matrix per route/action untuk lima role (spatie permission + Policy) |
| BOLA/IDOR | EOS mengakses record/report/finding/attachment/inventory milik EOS atau site lain harus gagal (403/404) |
| Mass assignment | Field `role`, `site_id`, `status`, `report_number`, `normalized_kbps`, `created_by` tidak bisa di-set client (FormRequest allowlist + `$fillable`) |
| Upload | Size, forbidden extension, spoofed MIME, invalid magic bytes, unauthorised download, file valid langsung AVAILABLE setelah lolos validasi sinkron (tanpa malware scan — ADR-044) |
| FormRequest validation | Invalid types/range/enums/sort/filter rejected safely; throughput unit selain KBPS/MBPS ditolak; batas attachment per context |
| Injection/XSS | Eloquent parameter binding, React output encoding, unsafe HTML prevented |
| Privacy/log | Password/token/secret dan raw file tidak masuk log/audit (activitylog properties disaring) |
| Sensitive access | Setiap akses/download selfie, precise GPS, sensitive evidence menghasilkan audit event (activitylog) |
| Infrastructure | DB tidak publik; default credential absent; secret scan clean; secrets terpisah per environment; tidak ada Redis yang di-deploy |

## 5. Route/Halaman Inertia dan FormRequest Tests

Menggantikan contract test REST/OpenAPI lama:

- Test setiap route Inertia (GET halaman): auth required, permission required, props yang dikirim sesuai visibility matrix (tidak ada raw sensitive data pada props untuk role tanpa hak).
- Test setiap route action (POST/PUT/DELETE): FormRequest validation rules (required, type, range, enum, max), error bag Inertia (redirect back + errors), Policy check, dan state transition.
- Test partial reload/route model binding: identifier object milik user lain => 403/404.
- Test pagination, filter allowlist, sort allowlist pada halaman listing.
- Test error handling konsisten: `APP_DEBUG=false` di CI test produksi-mode; tidak ada stack trace/internal detail pada response error.
- Route yang belum didukung implementasi baseline wajib ditandai deferred, tidak diuji sebagai implemented.

## 6. E2E Journeys

| Journey | Mandatory scenario |
|---|---|
| EOS login | Login, session expiry, re-login, unauthorized route, lockout, must_change_password flow (reset oleh Super Admin → dipaksa ganti → mutasi lanjut) |
| Check-in | Success (selfie + GPS mocked, status CHECKED_IN, jarak tercatat sebagai informasi), koordinat jauh tetap diterima (tanpa geofence), upload selfie retry, second check-in ALREADY_CHECKED_IN, kamera/overlay panduan wajah; fallback FaceDetector API tidak tersedia: overlay panduan tetap tampil dan check-in tetap berfungsi penuh tanpa deteksi wajah (progressive enhancement — PRD:654/PRD:616) |
| Daily Report | Create draft, section evidence required, checklist rules validation jump, submit/report number/read-only, snapshot konsisten setelah checklist publish baru |
| Clock-out | Flow kontinu (report belum submit → diarahkan ke form report → submit → clock-out lanjut), gate report belum SUBMITTED ditolak dengan arahan, success (COMPLETED), check-in/clock-out lintas tanggal ditolak |
| Dual-link | MAIN + SECONDARY konfigurasi tidak lengkap memblokir report; dua speedtest card terpisah; LINK_TRAFFIC Kbps/Mbps |
| Attachment | Upload valid langsung AVAILABLE, rejected (format/ukuran/magic byte), HEIC decode + thumbnail queued, download terkontrol |
| Inventory | Mutation post, reversal, negative stock rejection, finding lifecycle, registrasi aset oleh EOS (tag gudang, foto wajib) |
| Export | Preset simpan/pakai ulang, pilih kolom + filter, tiga format (xlsx styled/PDF/CSV), privacy scope Manager (tidak ada raw selfie/GPS), audit event |
| Backoffice | Filter/date timezone context (WIB/WITA/WIT), drill-down, access denied vs empty/error states, role visibility boundary |
| Notification | Event generation (report reopened, attachment rejected), list + unread filter, mark read happy path, akses notification milik user lain ditolak, notifikasi tidak menjadi authority (source record tetap diverifikasi) |

Split eksekusi E2E:

**Smoke-E2E (per promotion)** — dijalankan pada setiap promotion staging/production:

```text
::- Happy path: login → check-in (selfie+GPS mock) → isi report → submit → clock-out.
::- Tiga failure state utama: report gate (clock-out tanpa report SUBMITTED), attachment rejected (format/ukuran/validasi sinkron), must_change_password gate.
::- Browser prioritas: Android Chrome, iOS Safari, desktop Chrome (dua major version terbaru bila perangkat tersedia).
```

**Full regression E2E (per release)** — semua journey pada tabel di atas (happy path + boundary + negatif), dijadwalkan mingguan dan/atau manual sebelum release, bukan per promotion. E2E pilot mencakup site/test fixture WIB, WITA, dan WIT.

## 7. Recovery/Operations

Firefox/Edge tetap didukung namun best-effort pada eksekusi E2E — hanya prioritas eksekusi test yang berubah, bukan dukungan browser.

```text
::- PostgreSQL dan attachment volume: backup harian (pg_dump + rsync), retention 30 hari.
::- RPO <= 24 jam; RTO <= 8 jam; diuji, bukan hanya dinyatakan.
::- Restore test bulanan di staging (restore drill); mencatat date, operator, duration, outcome.
::- Alert terverifikasi terpicu: backup job failure, disk >80%, worker backlog naik, repeated worker job failure (queue database).
::- Session/queue/cache/rate-limit state berada di PostgreSQL: recovery penuh dari database restore, tidak ada komponen runtime eksternal yang perlu direstore.
::- Staging/production separation: staging tidak memakai raw production data; secrets terpisah per environment.
::- Promotion branch faizaldev -> staging -> production; production deploy hanya dari branch production.
::- Migration Laravel dijalankan terkontrol sebagai bagian deploy, bukan manual dari workstation developer.
::- Queue worker + scheduler berjalan sebagai service terpisah (queue:work, schedule:work); job idempotent terhadap retry.
::- Legal hold: deferred — built when retention/purge automation is implemented; legal hold gate test ditandai deferred dan tidak diuji sebagai implemented pada MVP.
```

## 8. CI Quality Gate

```text
Format/lint : Pint (PHP), ESLint (frontend).
Statis      : Larastan (PHP level project max), TypeScript typecheck.
Unit/Feature: Pest — seluruh mandatory business rule tests (§3) + security test matrix (§4) + route/FormRequest tests (§5).
Data        : Migration validation, model test, constraint test (unique, row lock, no negative stock).
Frontend    : React component tests, production build (Vite).
Security    : dependency/secret/image scan (composer audit, npm audit, secret scan); attachment fixture suite (tanpa malware scan — ClamAV dihapus, ADR-044).
E2E     : smoke-E2E (happy path login→check-in→report→clock-out + 3 failure state utama: report gate, attachment rejected, must_change_password gate) sebelum setiap staging/production promotion; full regression E2E journey suite per release (mingguan/manual).
```

## 9. UAT and Release Evidence

UAT harus menyimpan bukti skenario, expected/actual result, tester, environment, date/time, issue reference, dan sign-off. UAT berjalan di staging sebelum pilot. Backup/restore test (RPO/RTO), staging promotion gate, dan production release checklist adalah gate wajib.
