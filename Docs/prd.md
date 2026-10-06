# PRD — PORTER (Portal Operasional Terpadu Sekolah Rakyat)

**Status:** Final Baseline  
**Produk:** PORTER — Portal Operasional Terpadu Sekolah Rakyat  
**Platform:** Aplikasi web single-app Laravel 13 + Inertia v3 + React 19, responsif dan installable sebagai Progressive Web App (PWA)  
**Dokumen:** Product Requirements Document (PRD)

## 1. Ringkasan Produk

PORTER (Portal Operasional Terpadu Sekolah Rakyat) adalah aplikasi internal Comtronics untuk mengelola operasional Engineer On Site (EOS) di berbagai site Sekolah Rakyat di Indonesia. Aplikasi menyediakan bukti kehadiran berbasis selfie dan GPS (check-in/clock-out), laporan harian operasional berbasis checklist master terversi, konfigurasi konektivitas dual-link per site, inventaris per site, serta dashboard dan analitik historis untuk manajemen.

Produk berbentuk satu aplikasi web full-stack: server-side routing Laravel + Inertia v3 dengan React 19, tanpa REST API layer terpisah dan tanpa OpenAPI. Aplikasi dapat dipasang sebagai PWA pada Android/iOS. Desktop diprioritaskan untuk Super Admin, Manager, Supervisor, dan Human Resources (HR); tampilan mobile diprioritaskan untuk EOS.

Konteks bisnis absensi: EOS diambil dari vendor pihak ketiga. Disiplin kehadiran, keterlambatan, dan potongan terkait kehadiran ditangani oleh sistem absensi vendor EOS. Sistem Comtronics hanya membutuhkan BUKTI KEHADIRAN EOS di site — bukan penegakan disiplin.

## 2. Latar Belakang dan Tujuan

EOS tersebar di site dengan timezone WIB, WITA, dan WIT. Operasional membutuhkan bukti kehadiran yang dapat diaudit, laporan pemeriksaan teknis yang konsisten dan terversi, konfigurasi jaringan Main Link + Secondary Link yang lengkap per site, serta data inventaris yang melekat pada site—bukan individu EOS.

### Tujuan

- Merekam bukti kehadiran EOS di site melalui selfie dan koordinat GPS dengan server timestamp UTC sebagai waktu resmi.
- Mewajibkan Daily Report `SUBMITTED` sebelum EOS dapat clock-out.
- Menstandardkan pemeriksaan Router & Firewall, Access Point, Infrastruktur & Lingkungan, serta Konektivitas (dual-link) menggunakan checklist master yang terversi.
- Memastikan setiap site aktif memiliki tepat satu Main Link dan satu Secondary Link aktif sebagai dasar laporan konektivitas.
- Menyediakan riwayat aset aktif, stok material/sparepart, dan mutasi inventaris per site.
- Menyediakan rekap, filter historis, dan export (Excel/PDF/CSV) bagi manajemen tanpa menjadikan aplikasi sebagai ticketing/SLA system.

### Indikator keberhasilan MVP

- Setiap check-in/clock-out valid memiliki server timestamp UTC, koordinat GPS beserta akurasinya, jarak terhadap titik site (dihitung Haversine, disimpan sebagai informasi), dan satu selfie.
- Tepat satu record kehadiran per EOS + site + tanggal lokal; double submit ditolak.
- EOS tidak dapat clock-out bila Daily Report untuk tanggal lokal tersebut belum `SUBMITTED` dengan required evidence `AVAILABLE`.
- Setiap site aktif memiliki konfigurasi Main Link dan Secondary Link yang lengkap; report konektivitas berisi data traffic dan dua speedtest terpisah.
- Manajemen dapat memfilter laporan dan data kehadiran berdasarkan periode, site, dan EOS, serta mengekspornya ke Excel styled/PDF formal/CSV sesuai preset per user.
- Semua perubahan penting memiliki audit trail; akses data sensitif selalu diaudit.

## 3. Scope

### In scope MVP

- Autentikasi dan Role-Based Access Control (RBAC) lima role beserta visibilitas data sensitif.
- Master Site: identitas sekolah, koordinat, radius (informasi), timezone, dan status aktif.
- Master network link site (`site_network_links`): Main Link + Secondary Link aktif per site.
- Penugasan EOS: satu EOS memiliki maksimal satu penugasan site aktif pada saat yang sama.
- Bukti kehadiran check-in/clock-out online: selfie dengan overlay panduan wajah, koordinat GPS, server timestamp UTC, jarak Haversine ke site sebagai informasi.
- Daily Report berdasarkan checklist master terversi, lifecycle DRAFT/SUBMITTED/REOPENED/VOIDED, snapshot, dan lampiran per section (Evidence Section).
- Attachment dengan validasi sinkron pada request (magic byte, size, hash, decode-safe) dan pembuatan thumbnail/preview terjadwal via queue; dukungan HEIC/HEIF.
- PWA installable (app shell + static assets); tanpa offline draft — report authoring online-only.
- Inventaris: registrasi aset oleh EOS saat barang datang dari gudang, stok material/sparepart per site, mutasi dan reversal.
- Notification in-app (database channel + polling).
- Export Excel styled, PDF formal, dan CSV (sinkron/streamed) dengan pilihan kolom, filter, dan preset per user.
- Dashboard dan analitik historis dasar.
- Audit log, penyimpanan lampiran, web responsif, dan PWA installable.
- Tiga environment: local development, staging, production.

### Out of scope MVP

- Seluruh fitur disiplin absensi: window jam kerja, perhitungan keterlambatan, klasifikasi kehadiran, kalender kerja/hari libur, geofence sebagai gerbang, job ABSENT, dan Attendance Request (pengajuan absensi). Disiplin kehadiran ditangani absensi vendor EOS.
- Face matching/biometrik server-side: deteksi wajah di browser hanya indikasi panduan, bukan verifikasi identitas.
- Aplikasi native Android/iOS.
- Ticketing mandiri, SLA, auto-escalation, dan workflow penutupan gangguan.
- Integrasi monitoring wajib dengan Zabbix/SNMP/cloud controller.
- Payroll, perhitungan potongan, penggajian, dan modul cuti/leave HR.
- Approval wajib untuk semua Daily Report.
- Cross-midnight shift (shift yang melewati tengah malam) — secara eksplisit tidak didukung.
- Offline attendance atau attendance yang di-queue dan dianggap valid tanpa penerimaan server.
- Offline draft Daily Report (penulisan report online-only; tidak ada penyimpanan IndexedDB, tombol sync, atau state gagal sinkronisasi).
- Notifikasi email atau WhatsApp.
- Legal hold (deferred): modul legal hold ditunda ke fase purge automation — dibangun saat retention/purge automation diimplementasikan; visibility matrix data sensitif tetap berlaku penuh.
- Verifikasi otomatis actual network path / routing speedtest oleh aplikasi.
- PostGIS.
- Google Maps/Mapbox sebagai dependency wajib.
- External SSO.
- Multi-tenant.

## 4. Peran dan Akses

Role yang didukung: `SUPER_ADMIN`, `MANAGER`, `SUPERVISOR`, `HR`, `EOS`. Satu user memiliki tepat satu role.

Scope role non-EOS pada MVP: Supervisor, HR, dan Manager memiliki scope **semua site aktif** (single-project, ~200 site aktif, ~1–2 EOS per site, tim kecil). Setiap frasa "site scope" untuk role non-EOS pada dokumen ini dibaca sebagai "site yang berada dalam scope-nya (MVP: semua site aktif)". Pembatasan akses per-site untuk role non-EOS ditunda sebagai reserved future capability, bukan bagian MVP.

### Matriks kapabilitas

| Kapabilitas | Super Admin | Manager | Supervisor | HR | EOS |
|---|---:|---:|---:|---:|---:|
| Kelola akun, role, dan akses | Ya | Tidak | Tidak | Tidak | Tidak |
| Kelola master site dan konfigurasi | Ya | Lihat | Lihat | Tidak | Lihat site sendiri |
| Kelola site network link (Main/Secondary) | Ya | Lihat | Lihat | Tidak | Lihat site sendiri |
| Kelola penugasan EOS | Ya | Lihat | Ya | Lihat | Lihat sendiri |
| Kelola master checklist | Ya | Lihat + draft (publish hanya Super Admin) | Lihat + draft (publish hanya Super Admin) | Tidak | Tidak |
| Kelola kategori inventaris | Ya | Ya | Ya | Tidak | Tidak |
| Kelola aset dan stok site | Ya | Lihat | Ya | Tidak | Registrasi aset dari gudang; lihat/lapor perubahan |
| Lihat seluruh data kehadiran | Ya | Ringkasan lintas-site sesuai scope | Site scope | Sesuai otorisasi | Diri sendiri |
| Check-in/clock-out | Tidak normal | Tidak | Tidak | Tidak | Ya |
| Buat/kirim Daily Report | Tidak | Tidak | Tidak | Tidak | Ya |
| Reopen Daily Report | Ya | Tidak | Ya (site scope) | Tidak | Tidak |
| Lihat seluruh Daily Report | Ya | Ringkasan lintas-site sesuai scope | Site scope | Tidak | Site sendiri |
| Export data (Excel/PDF/CSV) | Ya | Ya (ringkasan lintas-site sesuai scope) | Ya (site scope) | Kehadiran sesuai otorisasi | Tidak |
| Dashboard analitik | Ya | Ya | Ya | Kehadiran | Ringkasan sendiri/site sendiri |
| Kelola legal hold | Deferred (fase 2) — di luar scope MVP | Tidak | Tidak | Tidak | Tidak |
| Akses audit log | Ya | Ringkasan | Ringkasan | Terbatas kehadiran | Tidak |

### Visibilitas data sensitif

| Role | Visibilitas |
|---|---|
| Super Admin | Akses penuh sesuai kebutuhan operasional/audit. |
| Supervisor | Hanya site scope; dapat melihat selfie, evidence, dan GPS presisi bila diperlukan untuk review kehadiran/report. |
| Manager | Ringkasan lintas-site sesuai scope; tidak melihat raw selfie, precise GPS, atau sensitive evidence secara default. |
| HR | Data kehadiran sesuai otorisasi; tidak otomatis melihat technical Daily Report evidence. |
| EOS | Data/evidence miliknya sendiri dan data site yang dibutuhkan untuk pekerjaan. |

Data sensitif yang dibatasi visibilitasnya:

- Selfie.
- Precise GPS.
- Accuracy GPS.
- Evidence attachment.
- IP address dan identity operational data tertentu.

Semua akses/download sensitive evidence wajib dicatat sebagai audit event. Export tidak boleh melanggar privacy visibility; Manager tidak boleh mengekspor raw selfie/precise GPS/sensitive attachment.

Super Admin memiliki akses administratif dan troubleshooting. Semua perubahan bernilai tinggi—khususnya role, assignment, timezone, koordinat site, status aset, dan inventaris—wajib tercatat dalam audit log.

## 5. Master Data

### Site

Setiap site wajib memiliki nama sekolah, kode site, alamat, latitude, longitude, radius dalam meter (informasi), timezone IANA, status aktif, dan konfigurasi operasional.

Timezone wajib memakai IANA timezone berikut:

| Zona | Nilai timezone |
|---|---|
| WIB | `Asia/Jakarta` |
| WITA | `Asia/Makassar` |
| WIT | `Asia/Jayapura` |

Timezone site disimpan untuk PENAMPILAN waktu lokal di UI; timestamp tetap UTC canonical. Semua perubahan koordinat atau timezone memerlukan reason dan audit log. Record kehadiran/report menyimpan `site_timezone_snapshot`.

### Site Network Link (`site_network_links`)

Setiap site aktif wajib memiliki tepat:

- satu link `MAIN` aktif.
- satu link `SECONDARY` aktif.

`NOT_AVAILABLE` bukan konfigurasi yang valid. Site tidak boleh aktif untuk Daily Report bila konfigurasi Main/Secondary belum lengkap.

Field minimal per link:

| Field | Ketentuan |
|---|---|
| `site_id` | Referensi site |
| `role` | `MAIN` atau `SECONDARY` |
| `provider_name` | Wajib |
| `service_name` | Nullable |
| `connection_medium` | `FIBER`, `WIRELESS`, `CELLULAR`, `SATELLITE`, `OTHER` |
| Subscribed download | value/unit, nullable |
| Subscribed upload | value/unit, nullable |
| `active` | Status aktif |
| `effective_from` / `effective_to` | Periode berlaku |
| `note` | Catatan |
| created/updated audit fields | Pembuat/waktu perubahan |

Constraint basis data: `unique(site_id, role) where active = true`. Konfigurasi link disnapshot ke Daily Report saat submit.

### Penugasan EOS

- EOS hanya boleh memiliki satu assignment aktif.
- Assignment menyimpan site, EOS, mulai/akhir penugasan, status, dan pembuat/perubah.
- Inventaris tidak dipindahkan ketika EOS berubah; inventaris selalu melekat pada site.

### Checklist master terversi

- Checklist tidak di-hard-code sebagai kolom permanen form; menggunakan master data terversi: satu tabel `checklist_versions` (version, status, struktur JSON berisi section/item/option/rule) untuk template `DAILY_SITE_REPORT`.
- Lifecycle version: `DRAFT` → `PUBLISHED` → `SUPERSEDED`/`RETIRED`.
- Hanya Super Admin yang dapat publish; versi published immutable.
- Satu template global `DAILY_SITE_REPORT` pada MVP; future-ready untuk scope site/region namun belum diterapkan.
- Report menyimpan `checklist_version_id` dan snapshot definisi checklist (JSONB).
- Perubahan standard rule/option/urutan dilakukan melalui master data; tipe renderer/input baru memerlukan release aplikasi.
- Backend MVP memvalidasi rules v1 dengan implementasi eksplisit per rule di FormRequest/service (bukan interpreter JSON condition/action generik); interpreter generik direncanakan untuk checklist v2.

## 6. Kebutuhan Fungsional

### FR-01 — Autentikasi dan RBAC

- Sistem wajib mengautentikasi pengguna sebelum akses data.
- Sistem wajib menegakkan hak akses berdasarkan role dan cakupan data (Laravel Policies per model + ScopeService: EOS dibatasi assignment aktif; non-EOS semua site aktif), termasuk pembatasan visibilitas data sensitif (selfie, precise GPS, accuracy GPS, evidence attachment, IP address).
- Pengguna non-EOS tidak dapat membuat kehadiran atau Daily Report sebagai EOS secara normal.
- Site data EOS dibatasi oleh assignment aktif pada tanggal operasional.
- Semua akses/download sensitive evidence menghasilkan audit event.

### FR-02 — Master Site

- Super Admin dapat membuat, mengubah, menonaktifkan, dan melihat site.
- Site menyimpan koordinat referensi, radius (informasi), timezone, network link, dan status.
- Site tidak dapat diaktifkan untuk Daily Report bila konfigurasi Main Link/Secondary Link belum lengkap.
- Perubahan koordinat atau timezone wajib menghasilkan audit log disertai reason.

### FR-03 — Penugasan EOS

- Supervisor dan Super Admin dapat membuat, mengakhiri, dan mengganti assignment EOS.
- Sistem wajib menolak assignment baru bila EOS masih memiliki assignment aktif lain.
- Riwayat assignment tidak boleh dihapus.

### FR-04 — Check-in (Bukti Kehadiran)

- Kehadiran wajib online; tidak dapat offline atau di-queue.
- Authority keputusan: server timestamp UTC, keberadaan selfie, dan koordinat GPS. Jarak ke site dihitung di server (Haversine) dan DISIMPAN SEBAGAI INFORMASI — bukan gerbang; tidak ada penolakan berdasarkan radius.
- EOS membuka form check-in pada site assignment aktif. UI kamera menampilkan overlay lingkaran "posisikan wajah di dalam lingkaran".
- Deteksi wajah opsional via browser FaceDetector API bila tersedia: indikasi wajah terdeteksi ditampilkan dan tombol jepret aktif setelah kamera stabil; bila API tidak tersedia, fallback ke overlay panduan lingkaran saja. Tidak ada penyimpanan data biometrik dan tidak ada face-matching server-side.
- Selfie: utama melalui `MediaDevices.getUserMedia()` dengan preview; fallback `<input type="file" accept="image/*" capture="user">`; UI normal tidak menawarkan gallery upload selfie.
- GPS diambil sekali saat momen jepret dengan toleransi longgar; latitude, longitude, dan accuracy direkam apa adanya.
- Sistem menggunakan waktu penerimaan backend (server timestamp lengkap UTC) sebagai waktu resmi; tanggal operasional (`work_date_local`) ditentukan dari konversi UTC ke timezone site.
- Maksimum satu record kehadiran final per EOS + site + `work_date_local` (unique constraint database) — mencegah double submit.
- Check-in kedua ditolak dengan `ALREADY_CHECKED_IN`; attempt gagal tetap diaudit.
- Status record berubah `NOT_CHECKED_IN` → `CHECKED_IN`.

### FR-05 — Check-out

- Clock-out hanya valid setelah check-in pada `work_date_local` yang sama dan Daily Report pada tanggal tersebut berstatus `SUBMITTED` dengan seluruh required evidence `AVAILABLE` (gate bisnis).
- Flow clock-out kontinu: bila report belum `SUBMITTED` (atau evidence belum `AVAILABLE`) saat EOS menekan clock-out, UI tidak menampilkan penolakan mentah, melainkan mengarahkan — "laporkan pekerjaan hari ini untuk melanjutkan clock-out" — ke form Daily Report; setelah submit berhasil, clock-out dilanjutkan. Gate bisnis tidak berubah.
- Clock-out memakai mekanisme selfie + GPS + server timestamp UTC yang sama dengan check-in: satu selfie clock-out, koordinat direkam, jarak dihitung dan disimpan sebagai informasi.
- Check-in dan clock-out harus berada pada tanggal lokal site yang sama; cross-midnight shift tidak didukung.
- Maksimum satu clock-out final; clock-out kedua ditolak dengan `ALREADY_CLOCKED_OUT`; attempt gagal tetap diaudit.
- Setelah clock-out sukses, status record menjadi `COMPLETED`.
- Kehadiran pada hari tanpa absen tidak membentuk record apa pun (tidak ada job ABSENT, tidak ada reconciliation) — urusan absensi vendor.

### FR-08 — Daily Report Lifecycle

- Satu Daily Report aktif per EOS + site + work date local (unique constraint).
- Draft dibuat pada hari terkait.
- Submit hanya pada work date terkait.
- Status: `DRAFT`, `SUBMITTED`, `REOPENED`, `VOIDED` (voided bila diperlukan governance).
- `SUBMITTED` immutable kecuali melalui reopen.
- Field Info Umum dibuat otomatis: ID laporan, tanggal, nama sekolah, dan nama petugas.
- EOS mengisi checklist wajib, catatan, dan lampiran sesuai konfigurasi master template.
- Tidak ada approval laporan pada MVP.
- Required evidence harus `AVAILABLE` untuk memenuhi submit (dengan validasi sinkron, attachment valid langsung `AVAILABLE`).
- Reopen:
  - Authority: Supervisor dalam site scope dan Super Admin.
  - Reason wajib.
  - Maksimum 7 hari kalender setelah submit.
  - Reopen menambah audit event; seluruh momen lifecycle laporan (created, submitted, reopened, resubmitted) tercatat di audit log dengan tanggal, jam, dan aktor.
  - Resubmit menambah `revision`; nomor report tidak berubah.
- Daily Report snapshot menyimpan: checklist version, definisi section/item, site snapshot, EOS snapshot, dan network links snapshot.

### FR-09 — Nomor Daily Report

Nomor resmi dibuat otomatis hanya saat submit sukses:

```text
CMX.WR.YYYYMM.SEQUENCE
```

Contoh:

```text
CMX.WR.202610.0001
CMX.WR.202610.0002
CMX.WR.202611.0003
```

Aturan:

- `CMX`: Comtronics — fixed company prefix, bukan konfigurasi (tidak ada tabel system settings untuk prefix).
- `WR`: Working Report.
- `YYYYMM`: tahun dan bulan dari site local date saat submit.
- `SEQUENCE`: global PostgreSQL bigint, mulai `0001`, terus bertambah, tidak reset harian/bulanan/tahunan/site/EOS.
- Sequence minimum empat digit; setelah `9999`, nilai tetap bertambah, misalnya `10000`.
- Database memakai internal UUID sebagai primary key; `report_number` adalah unique business identifier.
- Draft tidak menerima nomor dokumen resmi.
- Nomor lama yang telah diterbitkan dengan format sebelumnya (lihat amendment ADR-012) immutable; bila prefix berubah di masa depan, hanya alokasi nomor baru yang terpengaruh.

### FR-10 — Checklist Master dan Jawaban Laporan

Super Admin, Manager, dan Supervisor dapat mengelola DRAFT template checklist (membuat/usulkan perubahan pada version `DRAFT`); PUBLISH hanya dilakukan oleh Super Admin. Manager dan Supervisor dapat membaca versi published beserta histori version. Template dapat memiliki section, item, urutan, tipe input, satuan, kewajiban, pilihan nilai, aturan catatan, dan aturan lampiran.

Setiap section memiliki Evidence Section di akhir section. Aturan evidence section:

- Semua section operasional wajib punya minimal satu attachment `AVAILABLE`.
- Info Umum optional (0–5 attachment).
- Maksimum lima attachment pada satu Evidence Section.
- Maksimum lima attachment pada satu item evidence.
- Maksimum 10 attachment per Daily Report (hitungan sederhana semua attachment pada report, tanpa dedup lintas context).
- Satu attachment hanya teraut ke tepat satu context; evidence untuk context berbeda diunggah terpisah (file sama boleh diunggah ulang).
- Satu screenshot Main Link tidak otomatis menjadi evidence Secondary Link; bukti untuk Secondary Link wajib diunggah terpisah. Rationale: duplikasi file jauh lebih murah daripada kompleksitas penautan lintas context untuk skala MVP.

Daily Report baseline (Checklist Master v1) memiliki section berikut:

#### Info Umum

| Item | Tipe input | Ketentuan |
|---|---|---|
| ID Laporan | `READ_ONLY` | Otomatis saat submit |
| Tanggal | `READ_ONLY` | Tanggal lokal site |
| Nama Sekolah | `READ_ONLY` | Dari site assignment |
| Nama Petugas | `READ_ONLY` | Dari akun EOS |

Evidence Section: optional, 0–5 attachment.

#### Router & Firewall

| Item | Tipe input | Wajib |
|---|---|---:|
| Uptime | `DURATION` | Ya |
| Utilisasi CPU | `PERCENTAGE`, 0–100 | Ya |
| Utilisasi RAM | `PERCENTAGE`, 0–100 | Ya |
| Log Anomali | `ENUM` | Ya |

Enum Log Anomali: `NONE`, `MINOR`, `MAJOR`, `NOT_CHECKED`.

Evidence Section: required, 1–5 attachment.

Rules:

- `MINOR`: note wajib.
- `MAJOR`: note + item evidence `AVAILABLE` wajib.
- `NOT_CHECKED`: note alasan wajib.

#### Access Point

| Item | Tipe input | Wajib |
|---|---|---:|
| Status Monitoring AP | `ENUM` | Ya |
| Jumlah AP Offline | Integer 0–10.000 | Ya |
| Dokumentasi AP di Cloud | attachment evidence | Ya |

Enum Status Monitoring AP: `NORMAL`, `WARNING`, `DOWN`, `UNKNOWN`.

Evidence Section: required, 1–5 attachment.

Rules:

- `NORMAL`: AP offline harus 0.
- `WARNING`/`DOWN`: AP offline wajib >=1.
- `UNKNOWN`: note alasan wajib; count boleh null.
- Dokumentasi AP Cloud: minimum 1 attachment `AVAILABLE` wajib.

#### Infrastruktur & Lingkungan

| Item | Tipe input | Wajib |
|---|---|---:|
| Pengecekan Kelistrikan | `ENUM` | Ya |
| Suhu Ruangan Server | `NUMBER`, Celsius, satu desimal, range -20.0 sampai 80.0 | Ya |
| Kondisi Lingkungan | `ENUM` | Ya |

Enum Pengecekan Kelistrikan: `NORMAL`, `UNSTABLE`, `OUTAGE`, `BACKUP_ACTIVE`, `NOT_CHECKED`.

Enum Kondisi Lingkungan: `GOOD`, `ATTENTION`, `UNFIT`, `NOT_CHECKED`.

Evidence Section: required, 1–5 attachment.

Rules:

- Alat suhu tersedia di site; suhu wajib diisi, tidak ada `NOT_MEASURED`.
- Kelistrikan `UNSTABLE`, `OUTAGE`, `BACKUP_ACTIVE`: note + item evidence `AVAILABLE` wajib.
- Kelistrikan `NOT_CHECKED`: note alasan wajib.
- Lingkungan `ATTENTION`: note wajib.
- Lingkungan `UNFIT`: note + item evidence `AVAILABLE` wajib.
- Lingkungan `NOT_CHECKED`: note alasan wajib.

#### Konektivitas

| Item | Tipe input | Wajib |
|---|---|---:|
| Utilisasi Main Link (`MAIN_LINK_TRAFFIC`) | `LINK_TRAFFIC` | Ya |
| Utilisasi Secondary Link (`SECONDARY_LINK_TRAFFIC`) | `LINK_TRAFFIC` | Ya |
| Connection Test Main Link (`MAIN_LINK_SPEEDTEST`) | `SPEEDTEST_RESULT` | Ya |
| Connection Test Secondary Link (`SECONDARY_LINK_SPEEDTEST`) | `SPEEDTEST_RESULT` | Ya |

Evidence Section: required, 1–5 attachment.

### FR-11 — Dual-Link Connectivity Report

#### LINK_TRAFFIC

- Measurement window tetap 08:00–17:00 site-local.
- Mencatat: status (`AVAILABLE`, `DOWN`, `NOT_CHECKED`), avg inbound, peak inbound, avg outbound, peak outbound, source dashboard/monitoring, note, dan evidence.
- Throughput menerima unit `KBPS` dan `MBPS`; input source disimpan (`value`, `unit`).
- Backend menghitung `normalized_kbps`:
  - `KBPS` => nilai sama.
  - `MBPS` => nilai × 1000.
- Tidak menerima Gbps, KB/s, MB/s, atau byte-per-second pada MVP.
- `AVAILABLE`: empat nilai traffic + source + item evidence `AVAILABLE` wajib.
- `DOWN`: note + item evidence `AVAILABLE` wajib.
- `NOT_CHECKED`: note alasan wajib.

#### SPEEDTEST_RESULT

- Test dijalankan manual oleh EOS sebelum report submit; wajib terpisah untuk `MAIN` dan `SECONDARY` (dua item terstruktur, bukan satu field generic).
- Field: status (`SUCCESS`, `FAILED`, `NOT_TESTED`), `link_role`, download (ThroughputValue), upload (ThroughputValue), `latency_ms`, `jitter_ms`, `packet_loss_percent` (nullable), `server_name` (nullable), `route_declaration`, note, dan evidence.
- `SUCCESS`: download, upload, latency, jitter, dan screenshot `AVAILABLE` wajib.
- `FAILED`/`NOT_TESTED`: note alasan wajib.
- Bila link down, entry speedtest link tersebut tetap wajib: gunakan `FAILED` atau `NOT_TESTED` dengan reason `Link down`.
- Secondary test wajib mengikuti safe routing/failover SOP.
- Aplikasi hanya mencatat `route_declaration` (misalnya `TESTED_VIA_MAIN_LINK` atau `TESTED_VIA_SECONDARY_LINK`); aplikasi tidak mengklaim otomatis memverifikasi actual network path pada MVP.

### FR-12 — Lampiran dan HEIC

- Allowed format: JPG/JPEG, PNG, WebP, HEIC/HEIF, PDF.
- Maksimum 10 MB per file.
- Limit per context: Daily Report maksimum 10 attachment; section evidence maksimum 5; item evidence maksimum 5; inventory mutation maksimum 5; check-in selfie satu; clock-out selfie satu.
- PDF boleh pada report/section/item evidence; tidak boleh untuk selfie dan inventory mutation.
- Validasi berjalan SINKRON dalam request (sebelum file diterima final): client preflight size/format → auth/CSRF/site authorization → server-generated storage name → validasi magic byte/signature, size, decode-safe, dan hash SHA-256 → file yang lolos langsung berstatus `AVAILABLE`; file yang gagal ditolak pada response request dengan pesan aman dan audit event.
- Thumbnail/preview dibuat ASINKRON via queued job (Laravel Queue, database driver): preview WebP max 2048 px dan thumbnail WebP max 480 px menggunakan Intervention Image (Imagick + libheif untuk HEIC); PDF original tidak direcompress. Derivative belum selesai tidak menghalangi status `AVAILABLE`.
- Bila HEIC gagal decode/validasi, file ditolak dengan pesan aman.
- Tidak ada pipeline quarantine async dan tidak ada malware scan (tanpa ClamAV — ADR-044): file yang lolos validasi tipe/ukuran/decode langsung `AVAILABLE`.
- Original attachment immutable dan private (local disk di luar public/); semua download melalui controller terkontrol dan access/download sensitive diaudit.

### FR-13 — PWA

- PWA installable; meng-cache app shell dan static assets.
- Tidak ada penyimpanan draft offline: tidak ada IndexedDB draft, tombol sync, atau state gagal sinkronisasi.
- Penulisan Daily Report online-only; tanpa konektivitas aplikasi tidak dapat dipakai.
- Attachment tidak dapat di-queue offline; report tidak dapat submit bila evidence wajib belum diupload/`AVAILABLE`.
- Kehadiran tidak dapat offline/queued; kehadiran baru valid setelah diterima server.

### FR-14 — Inventaris

- Inventaris melekat pada site, bukan EOS; terdiri dari asset unit dan stock material/sparepart.
- Model data: `assets` + `inventory_items` (catalog) + `inventory_stock` (saldo per site+item) + `inventory_transactions` (ledger) + `inventory_findings` (terpisah).
- Aset diregistrasi oleh EOS saat barang datang dari gudang Comtronics (bukan Supervisor). Asset tag dan serial number sudah ada dari gudang; EOS menginput apa adanya dan sistem memvalidasi format (regex CMX) serta keunikan tag.
- Asset tag format `CMX.{SITE_CODE}.{CATEGORY}.{SEQ}` (immutable); `CMX` adalah fixed company prefix; `SITE_CODE` unik tetap menjadi identitas lokasi.
- Foto aset wajib saat registrasi.
- Contoh aset: router, firewall, switch, access point, UPS, rack, dan perangkat aktif lain.
- Barang tidak berpindah antar site; aset rusak dikembalikan ke gudang.
- Status aset enam nilai: `IN_USE`, `SPARE`, `RETURNED`, `DAMAGED`, `LOST`, `DISPOSED`. Perubahan status = transaksi beralasan + audit + foto wajib bila rusak/hilang.
- Material dan sparepart dikelola sebagai stok kuantitas per site.
- Supervisor dapat membuat dan post stock mutation langsung untuk site scope tanpa approval Manager pada MVP.
- Tipe mutasi: `RECEIPT`, `USAGE`, `ADJUSTMENT`, `DAMAGED`, `LOST`, `RETURN`, `TRANSFER_IN`, `TRANSFER_OUT`.
- `ADJUSTMENT`, `DAMAGED`, `LOST`, dan `TRANSFER` wajib catatan.
- Mutasi posted immutable, tidak dapat diedit/dihapus; koreksi menggunakan compensating new mutation dengan `reversal_of_mutation_id`.
- Stok tidak boleh negatif; sistem memakai database transaction/row lock.
- Mutasi menyimpan tipe transaksi, kuantitas, alasan, pelaku, waktu, site, dan lampiran bila diwajibkan (maksimum 5).
- Inventory Finding terpisah dari stock mutation.
- EOS dapat melihat inventaris site dan melaporkan perubahan/temuan.
- Aset rusak/hilang tidak dihapus; status dan histori perubahan disimpan.

### FR-15 — Notification

Notification MVP in-app only, memakai tabel `notifications` Laravel (database channel) dengan polling:

- Daily Report reopened.
- Attachment rejected (gagal validasi sinkron).
- Export selesai.

Notification bukan authority; source record dan audit adalah authority. Email/WhatsApp out of scope.

### FR-16 — Export (Excel, PDF, CSV)

- Tiga format export: **Excel (xlsx) styled** (header bold, border, lebar kolom auto, judul dan periode pada header sheet, filename dinamis), **PDF formal** (header instansi, siap cetak), dan **CSV** (data mentah).
- Kustomisasi: pemilihan kolom per export (checkbox per kolom), filter periode (tahun/bulan/minggu/hari), site, status, dan EOS.
- Konfigurasi pilihan kolom + filter dapat disimpan sebagai **preset per user** dan dipakai ulang.
- Export berjalan sinkron: response men-stream file langsung (streamed response), tanpa job async untuk pembuatan file.
- Audit event export ditulis sinkron sebelum stream dimulai, mencatat: actor, role, data type, format, filter/kolom, scope, timestamp, dan outcome.
- Export tidak boleh melanggar privacy visibility; Manager tidak boleh export raw selfie/precise GPS/sensitive attachment.
- Data type export mencakup data kehadiran (bukti kehadiran per periode/site/EOS), Daily Report, dan inventaris, sesuai role dan scope.

### FR-17 — Legal Hold (deferred — fase 2)

- Modul legal hold ditunda sampai retention/purge automation diimplementasikan; entitas dan route legal hold ditandai deferred — built when retention/purge automation is implemented.
- Visibility matrix data sensitif tetap berlaku penuh; catatan ini adalah scope decision, bukan open operational item.

### FR-18 — Dashboard dan Analitik

- Manager dan Supervisor dapat memfilter data berdasarkan rentang tanggal lokal, site, EOS, status laporan, dan kategori inventaris.
- Dashboard kehadiran memperlihatkan rekap check-in/clock-out per site dan hari, kelengkapan bukti (selfie/GPS/latitude-longitude), dan status record (`NOT_CHECKED_IN`/`CHECKED_IN`/`COMPLETED`); tanpa metrik keterlambatan.
- Dashboard Daily Report memperlihatkan histori, status pemeriksaan, AP offline, status konektivitas dual-link, dan temuan/catatan.
- Dashboard inventaris memperlihatkan aset per site, aset berstatus rusak/hilang, stok material/sparepart, serta histori mutasi.
- HR fokus pada dashboard dan rekap kehadiran.

### FR-19 — Audit

- Audit event aplikasi memakai `spatie/laravel-activitylog` (tabel `activity_log`), dipasang di titik-titik kritis eksplisit.
- Semua create/update/delete/approval/reject untuk data kritis menghasilkan audit log yang immutable secara aplikasi.
- Semua login sukses/gagal, lockout, reset, disable user, perubahan role, logout penting, akses/download sensitive evidence, dan export diaudit.
- Lampiran menyimpan metadata file, pemilik, relasi bisnis, hash SHA-256, waktu unggah, dan storage key; file tidak disimpan langsung di database utama.

## 7. Business Rules

### BR-01 — Server time dan timezone

- Semua timestamp disimpan canonical UTC; server timestamp penerimaan adalah waktu resmi check-in/clock-out.
- Tanggal operasional (`work_date_local`) dievaluasi menggunakan timezone site; nomor laporan memakai site local date saat submit.
- `work_date_local` dan `site_timezone_snapshot` wajib disimpan pada record kehadiran dan Daily Report sebagai snapshot tanggal bisnis.
- UI lintas-site wajib menampilkan label timezone, misalnya WIB/WITA/WIT, dengan waktu lokal site.

### BR-02 — Bukti lokasi

- Backend, bukan browser, menghitung jarak geodesic (Haversine) dari koordinat EOS ke titik site; hasil `distance_m` DISIMPAN SEBAGAI INFORMASI pada record, bersama `accuracy_m` GPS.
- GPS diambil sekali pada momen jepret dengan toleransi longgar; tidak ada penolakan berdasarkan radius, akurasi, atau freshness lokasi.
- Radius site (default 100 meter, dapat diubah per site) disimpan sebagai informasi konteks, bukan gerbang validasi.
- Maps (Leaflet + react-leaflet) digunakan untuk input/ubah koordinat site, draggable marker, dan review titik check-in/clock-out; maps bukan authority kehadiran.
- PWA tidak mengklaim anti-fake GPS 100%; sistem menggunakan bukti selfie + GPS + audit + review manual untuk kasus mencurigakan.

### BR-03 — Kehadiran

- Satu record kehadiran final per EOS + site + `work_date_local` (unique constraint database); maksimum satu check-in final dan satu clock-out final; attempt gagal tetap diaudit.
- Check-in dan clock-out harus berada pada local calendar date yang sama; cross-midnight shift tidak didukung.
- Maksimum satu selfie check-in dan satu selfie clock-out.
- Tidak ada window jam, klasifikasi EARLY/ON_TIME/LATE, perhitungan late minutes, atau periode absensi; disiplin kehadiran ditangani absensi vendor EOS.
- Hari tanpa absen tidak membentuk record dan tidak memicu job apa pun.

### BR-04 — Status kehadiran

Lifecycle utama:

```text
NOT_CHECKED_IN → CHECKED_IN → COMPLETED
```

`NOT_CHECKED_IN` adalah keadaan awal record yang dibuat saat check-in pertama; hari tanpa record berarti EOS tidak absen (urusan vendor). Record menyimpan data check-in (selfie, GPS, server timestamp, `distance_m`) dan clock-out dengan struktur yang sama.

### BR-05 — Daily Report sebelum clock-out

- Daily Report harus berstatus `SUBMITTED` sebelum clock-out.
- Semua required evidence harus `AVAILABLE` sebelum clock-out.
- Laporan berelasi dengan record kehadiran yang aktif dan site assignment pada tanggal tersebut.
- Gangguan dicatat sebagai item/catatan Daily Report; tidak membentuk ticket/SLA pada MVP.

### BR-06 — Nomor dokumen

- Sequence Daily Report dialokasikan atomik dalam database transaction hanya pada submit sukses.
- Tidak boleh ada dua report memakai `report_number` sama.
- Draft tidak menerima nomor dokumen resmi; nomor tidak berubah saat reopen/revision.

### BR-07 — Batas attachment per context

| Context | Batas |
|---|---|
| Per file | 10 MB |
| Daily Report (per report) | 10 |
| Section evidence | 5 |
| Item evidence | 5 |
| Inventory mutation | 5 |
| Check-in selfie | 1 |
| Clock-out selfie | 1 |

## 8. Workflow Utama

### Kehadiran dan Daily Report

```text
EOS login
→ melihat site assignment aktif
→ check-in: buka kamera (overlay lingkaran; FaceDetector opsional) → jepret selfie + ambil GPS sekali
→ server menyimpan server timestamp UTC + GPS + selfie + distance Haversine (informasi)
→ status CHECKED_IN
→ mengisi draft Daily Report
→ melengkapi item wajib, catatan, dan lampiran (termasuk dua speedtest dual-link)
→ submit Daily Report
→ sistem menerbitkan report number resmi
→ clock-out: bila report belum SUBMITTED, diarahkan melengkapi report (flow kontinu)
→ clock-out dengan selfie + GPS + server timestamp
→ status kehadiran COMPLETED
```

### Registrasi Aset dari Gudang

```text
Barang datang dari gudang Comtronics (asset tag + SN sudah ada)
→ EOS meregistrasi aset: input tag/SN apa adanya, kategori, foto wajib
→ sistem memvalidasi format tag (regex CMX) dan keunikan
→ aset berstatus IN_USE/SPARE pada site
→ perubahan status berikutnya = transaksi beralasan + audit (+ foto bila rusak/hilang)
→ aset rusak/hilang: status DAMAGED/LOST, dikembalikan ke gudang (RETURNED) bila rusak
```

### Penggantian EOS

```text
Supervisor mengakhiri assignment EOS lama
→ Supervisor membuat assignment EOS baru pada site
→ inventaris tidak berpindah karena tetap terikat ke site
→ EOS baru melanjutkan kehadiran, laporan, dan pemeriksaan inventaris site
```

## 9. Kebutuhan Nonfungsional

### Keamanan

- Seluruh aplikasi dan Geolocation/kamera API wajib melalui HTTPS.
- Server-side authorization wajib pada setiap route/action (Laravel Policy + middleware), tidak mengandalkan pembatasan UI.
- Password hash Argon2id; panjang password 12–128 karakter.
- Session cookie opaque: Secure, HttpOnly, SameSite=Lax; idle timeout 30 menit; absolute timeout 8 jam; session diputar saat login dan password berubah; logout/reset/disable user merevoke semua active session.
- Login protection: maksimum 5 percobaan gagal per identifier/IP dalam 15 menit; lockout sementara 15 menit; error login generik untuk mencegah enumeration.
- `must_change_password`: user baru/user yang direset oleh Super Admin dipaksa mengganti password pada login berikutnya via middleware.
- Reset password MVP dilakukan Super Admin (memaksa change password); SMTP reset password ditunda.
- Semua endpoint mutasi dengan cookie auth wajib CSRF protected; CORS explicit allowlist per environment.
- Upload proteksi: validasi sinkron magic byte, size, hash SHA-256, dan decode-safe pada request; file ditolak (fail closed) bila validasi gagal.
- URL/objek lampiran dilindungi otorisasi (local disk private di luar `public/`, download via controller terkontrol); tidak bersifat publik secara default.
- Audit log mencatat actor, action, entity, entity ID, nilai before/after yang relevan, request metadata, dan timestamp UTC; tidak menyimpan password, secret, session token raw, atau binary attachment.

### Kinerja dan keandalan

- Operasi kehadiran idempotent untuk mencegah double submit akibat koneksi tidak stabil (unique constraint + penolakan `ALREADY_CHECKED_IN`/`ALREADY_CLOCKED_OUT`).
- Submit report dan alokasi sequence transactional.
- Tidak ada penyimpanan draft report lokal; report authoring online-only dan kehadiran baru valid setelah diterima server.
- Sistem menampilkan status jelas: tersimpan server (DRAFT/SUBMITTED) dan status attachment (`AVAILABLE`/`REJECTED`; derivative preview bisa sedang diproses queue tanpa memblokir).
- Session/cache/queue/rate-limit/atomic-lock memakai driver `database` (tanpa Redis); kapasitas dirancang untuk 200 site aktif dan ~1–2 EOS per site.
- Background job via Laravel Queue (database driver) + Scheduler, dijalankan worker terpisah (`php artisan queue:work`, `php artisan schedule:work`); kegagalan job tidak memblokir operasi sinkron pengguna.

### Data dan retensi

- Database PostgreSQL; timestamp canonical UTC; record operasional memakai UUID primary key.
- Lampiran disimpan pada storage private (local persistent volume `storage/app`, di luar `public/`) dengan backup dan akses terotorisasi.
- Purge/retention akan mengecek legal hold saat diimplementasikan; modul legal hold dan purge automation ditunda ke fase 2 (deferred).
- Kebijakan retensi final ditentukan perusahaan; sistem harus mendukung archival tanpa menghilangkan audit referensi.

### Browser dan device

Dukungan minimum: dua major version terbaru Android Chrome, iOS Safari, dan desktop Chrome, Edge, Firefox. FaceDetector API bersifat progressive enhancement — aplikasi tetap berfungsi penuh pada browser tanpa API tersebut.

### Deployment

- Tiga environment terpisah: local development, staging, production; Docker Compose (Sail) dengan worker container terpisah untuk queue + scheduler.
- Staging wajib sebelum pilot dan production release; staging tidak boleh memakai raw production data.
- Promotion branch: `faizaldev -> staging -> production`; production deploy hanya dari branch `production`.
- Secrets terpisah per environment; database migrations dijalankan terkontrol sebagai bagian deploy.
- Monitoring: health endpoint sederhana + structured log stdout.
- Backup: `pg_dump` harian + rsync attachment terjadwal + restore drill berkala.

## 10. Integrasi Masa Depan

Tidak wajib untuk MVP, tetapi rancangan perlu siap untuk:

- Zabbix/SNMP untuk otomatisasi uptime, CPU, RAM, traffic link, dan status perangkat.
- Cloud controller Access Point untuk status AP, jumlah AP offline, dan bukti monitoring.
- Notifikasi email/WhatsApp untuk pengingat report.
- Tile provider production (menggantikan public OSM tiles yang hanya untuk development/pilot kecil, dengan attribution dan compliance policy), dipilih berdasarkan quota/SLA/terms/privacy/cost.
- QR dinamis atau aplikasi native Android khusus kehadiran jika fraud location membutuhkan kontrol perangkat lebih kuat.

PostGIS, Google Maps/Mapbox, dan external SSO bukan dependency MVP.

## 11. Open Operational Configuration

Item berikut belum diputuskan dan bukan blocker; konfigurasi final ditetapkan terpisah:

- Threshold CPU/RAM/suhu untuk warning/critical.
- Threshold utilisasi Main/Secondary dibanding subscribed capacity.
- SOP aman routing/failover speedtest Secondary per jenis router/site.
- Isi master data awal (region/city, site, user, assignment, kategori aset, provider/kapasitas network link).
- Teks final privacy notice/consent oleh HR/legal.
- Tile provider production.
- Detail implementasi purge arsip 2/3 tahun.

## 12. Acceptance Criteria MVP

- Check-in berhasil menyimpan: server timestamp UTC, koordinat GPS beserta accuracy, jarak Haversine `distance_m` ke site (sebagai informasi), dan tepat satu selfie; jarak/radius tidak pernah menjadi alasan penolakan.
- Form check-in menampilkan overlay lingkaran; bila FaceDetector API tersedia, tombol jepret aktif setelah kamera stabil dan wajah terdeteksi; bila tidak tersedia, overlay panduan tetap ditampilkan dan check-in tetap dapat dilakukan.
- Check-in kedua pada EOS + site + tanggal lokal yang sama ditolak `ALREADY_CHECKED_IN` (unique constraint teruji, termasuk percobaan double submit bersamaan); clock-out kedua ditolak `ALREADY_CLOCKED_OUT`.
- Check-in dan clock-out pada tanggal lokal site yang sama; record bertransisi `NOT_CHECKED_IN` → `CHECKED_IN` → `COMPLETED`.
- Hari tanpa absen tidak menghasilkan record, job, atau status apa pun di sistem.
- EOS tidak dapat clock-out tanpa Daily Report `SUBMITTED` dan required evidence `AVAILABLE`; saat gate terpicu, UI mengarahkan EOS ke form Daily Report ("laporkan pekerjaan hari ini untuk melanjutkan clock-out"), bukan penolakan mentah.
- Site tidak dapat aktif untuk Daily Report tanpa tepat satu `MAIN` dan satu `SECONDARY` link aktif (unique constraint `site_network_links`).
- Daily Report konektivitas berisi `LINK_TRAFFIC` Main dan Secondary (avg/peak inbound/outbound, Kbps/Mbps, `normalized_kbps`) serta dua speedtest terpisah `SPEEDTEST_RESULT` per link.
- Submit Daily Report gagal bila section operasional belum punya minimal satu attachment `AVAILABLE`, atau total attachment melebihi 10 per report; seluruh rules checklist v1 (MINOR→note, MAJOR→note+evidence, NOT_CHECKED→reason, AP offline vs status, suhu wajib, kelistrikan/lingkungan, LINK_TRAFFIC, SPEEDTEST) divalidasi server-side per rule eksplisit.
- Nomor report `CMX.WR.YYYYMM.SEQUENCE` dialokasikan atomik hanya saat submit sukses; sequence global tidak reset; nomor tidak berubah saat reopen/revision.
- Reopen Daily Report hanya oleh Supervisor site scope/Super Admin, dengan reason, maksimal 7 hari kalender; resubmit menambah `revision` tanpa mengubah nomor.
- Attachment divalidasi sinkron pada request (magic byte, size ≤ 10 MB, hash SHA-256, decode-safe); file yang gagal ditolak pada response dan tidak pernah `AVAILABLE`; file valid langsung `AVAILABLE` dengan derivative preview/thumbnail WebP dibuat queued job.
- Aset tidak dapat didaftarkan tanpa asset tag unik format `CMX.{SITE_CODE}.{CATEGORY}.{SEQ}` (validasi regex + keunikan) dan foto aset; status aset terbatas pada enam nilai `IN_USE`/`SPARE`/`RETURNED`/`DAMAGED`/`LOST`/`DISPOSED`; perubahan status rusak/hilang memerlukan foto.
- Material/sparepart memiliki saldo stok per site yang tidak boleh negatif; mutasi posted immutable dan reversal memakai compensating mutation `reversal_of_mutation_id`.
- Export mendukung tiga format (Excel styled, PDF formal, CSV) dengan pemilihan kolom, filter periode/site/status/EOS, dan preset tersimpan per user yang dapat dipakai ulang; export berjalan sinkron (file di-stream pada response), teraudit sebelum stream dimulai, dan Manager tidak dapat mengekspor raw selfie/precise GPS/sensitive attachment.
- Semua akses/download sensitive evidence menghasilkan audit event.
- Tampilan waktu dan tanggal operasional benar untuk site WIB, WITA, dan WIT (timestamp UTC canonical, penyajian lokal per timezone site).
