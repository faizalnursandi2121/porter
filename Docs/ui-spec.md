# UI Specification Handoff

## 1. Purpose

This document is the handoff bridge from `ux.md` to visual design/prototype and React implementation. It does not prescribe a visual library or template (basis komponen: React 19 + shadcn/ui pada Inertia v3). Visual artifacts are produced in pen.dev and reviewed before implementation.

## 2. Required Handoff Per Screen

Every approved screen/frame must identify:

```text
- Screen ID from ux.md
- Halaman Inertia (route Laravel) and supported viewport(s)
- Page component (React) and layout
- Actor/role and permission boundary
- Primary/secondary action
- Data props (halaman) / form submit target and loading/empty/error states
- Validation, server error (Inertia error bag), and retry behavior
- Attachment and evidence state if applicable
- Accessibility labels/focus behavior
- Acceptance criteria and linked story
```

## 3. Route Baseline (Halaman Inertia)

Route baseline memakai server-side routing Laravel + Inertia v3. Setiap entri: `path → page component` (semua page React; role yang memiliki akses). Nama route Laravel memakai konvensi `role.path` (contoh `eos.attendance.show`) kecuali common. Action (submit form, action button) memakai POST/PATCH/DELETE route Laravel — bukan REST resource terpisah; detail pada api-contract.md (kontrak halaman/route Inertia).

```text
/login                              → Auth/Login                       (guest)
/change-password                    → Profile/ChangePassword           (semua role; must_change_password gate)

/eos                                → EOS/Dashboard                    (EOS)
/eos/attendance                     → EOS/Attendance/Index             (EOS; riwayat kehadiran)
/eos/attendance/{record}            → EOS/Attendance/Show              (EOS)
/eos/daily-report/current           → EOS/DailyReport/Form            (EOS; form harian)
/eos/daily-reports/{report}         → EOS/DailyReports/Show           (EOS; read-only submitted/reopened)
/eos/inventory/assets               → EOS/Inventory/Assets/Index       (EOS)
/eos/inventory/assets/create        → EOS/Inventory/Assets/Create      (EOS; registrasi aset dari gudang)
/eos/inventory/assets/{asset}       → EOS/Inventory/Assets/Show        (EOS)
/eos/inventory/stock                → EOS/Inventory/Stock/Index        (EOS; mutasi stok dari sini)
/eos/inventory/findings             → EOS/Inventory/Findings/Index     (EOS)
/eos/inventory/findings/create      → EOS/Inventory/Findings/Create    (EOS)
/eos/inventory/findings/{finding}   → EOS/Inventory/Findings/Show      (EOS)

/supervisor/dashboard               → Supervisor/Dashboard             (SUPERVISOR)
/supervisor/attendance              → Supervisor/Attendance/Index      (SUPERVISOR; bukti kehadiran)
/supervisor/daily-reports           → Supervisor/DailyReports/Index    (SUPERVISOR)
/supervisor/daily-reports/{report}  → Supervisor/DailyReports/Show     (SUPERVISOR; review + reopen)
/supervisor/inventory/assets        → Supervisor/Inventory/Assets/Index
/supervisor/inventory/stock         → Supervisor/Inventory/Stock/Index
/supervisor/inventory/findings      → Supervisor/Inventory/Findings/Index
/supervisor/master/sites            → Supervisor/Master/Sites
/supervisor/master/assignments      → Supervisor/Master/Assignments
/supervisor/master/checklists       → Supervisor/Master/Checklists     (lihat published + usulan draft)
/supervisor/master/asset-categories → Supervisor/Master/AssetCategories
/supervisor/master/catalog          → Supervisor/Master/Catalog
/supervisor/analytics/{type}        → Supervisor/Analytics/{type}

/manager/dashboard                  → Manager/Dashboard                (MANAGER)
/manager/analytics/{type}           → Manager/Analytics/{type}

/hr/dashboard                       → HR/Dashboard                     (HR)
/hr/attendance                      → HR/Attendance/Index              (HR; bukti kehadiran harian)
/hr/eos-history                     → HR/EosHistory/Index              (HR)

/admin/users                        → Admin/Users/Index                (SUPER_ADMIN)
/admin/roles                        → Admin/Roles/Index                (SUPER_ADMIN)
/admin/audit-logs                   → Admin/AuditLogs/Index            (SUPER_ADMIN)
/admin/checklists                   → Admin/Checklists/Index           (SUPER_ADMIN; publish)
/admin/storage-usage                → Admin/StorageUsage/Index         (SUPER_ADMIN)
/admin/config                       → Admin/Config/Index               (SUPER_ADMIN)

/notifications                      → Notifications/Index              (semua role)

Export (panel pada halaman data backoffice; SUPER_ADMIN/MANAGER):
POST /exports                       → streamed download (xlsx/pdf/csv) — bukan halaman
POST /export-presets                → simpan preset
PATCH /export-presets/{preset}      → ubah/ganti nama preset
DELETE /export-presets/{preset}     → hapus preset
```

Halaman 403 (`Akses Ditolak`), 404, session expired, dan error page memakai halaman error Inertia standar (screen C-02–C-05).

## 4. Screen-to-Action Matrix

| Screen group | Primary action/props (halaman) | Critical states |
|---|---|---|
| Login | POST `/login` (Fortify) | Invalid login, rate limit (lockout 5 gagal/15 menit), session expired |
| EOS Dashboard (`/eos`) | CTA state machine: check-in → report → clock-out → selesai | Not checked-in, checked-in + report draft/submitted/reopened, completed, dual-link belum lengkap (tanpa CTA report) |
| Check-in/out (E-05–E-08, E-17–E-19) | POST check-in / clock-out (selfie + GPS + server timestamp UTC) | Location/camera denied (denied flow copy), GPS gagal, upload retry, unconfirmed server, `ALREADY_CHECKED_IN`/`ALREADY_CLOCKED_OUT` (409), tanggal lokal berganti (clock-out), report gate kontinu (redirect ke form Daily Report) |
| Riwayat kehadiran (E-20) | Attendance history/detail props | Status `NOT_CHECKED_IN`/`CHECKED_IN`/`COMPLETED`, check-in/clock-out time (local site), jarak (role berwenang), evidence visibility per role |
| Daily Report | save draft / submit (FormRequest; snapshot + versioned checklist) | Local/server draft, incomplete, section evidence required (max 5 per section, 10 per report), LINK_TRAFFIC validation, dual Speedtest cards, dual-link snapshot, submitted, reopened (revision) |
| Attachment lifecycle | upload → sinkron valid (magic byte/size/hash) → `AVAILABLE`/`REJECTED` | `AVAILABLE`, `REJECTED` (safe copy, no technical detail), format/size rejection |
| PWA | app shell cache only | Offline badge (`Offline — aplikasi memerlukan koneksi`), no offline authoring (online-only, no IndexedDB draft/sync/expiry/conflict UI — see ux.md 8.5); attendance offline → urusan vendor, tidak ada CTA pengajuan |
| Notification | mark read per item / mark all read (idempotent) | Header badge unread (polling / props halaman), list (report REOPENED, attachment rejected, export selesai), link ke record sumber, empty `Belum ada notifikasi` |
| Change password (C-10, `/change-password`) | POST ganti password | `must_change_password` gate: CTA/navigasi disabled, middleware menolak request lain; inline policy (12–128) + konfirmasi mismatch; sukses → revoke semua session → re-login (`Password berhasil diganti. Silakan login kembali.`) |
| Storage usage (A-06, `/admin/storage-usage`) | Storage usage listing | Site list dengan penggunaan, persentase, status; tanpa kuota per-site (—); alert disk global |
| Inventory Finding | finding list/create/detail; action resolve/reject (alasan wajib) | Evidence required, open/review/resolve/reject |
| Registrasi aset (E-21) | POST asset (EOS) | Asset tag regex/unique violation (error bag inline), SN optional dari gudang, foto wajib |
| Mutasi stok (E-22) | POST inventory transaction (EOS) | Saldo negatif ditolak server, preview saldo sebelum/sesudah, reversal reference |
| Supervisor report review (S-04) | POST reopen (alasan wajib) | Batas 7 hari kalender (ditolak server), dampak confirmation, notifikasi EOS, revision |
| Export (S-16/M-05) | POST `/exports` (streamed xlsx/pdf/csv) + preset CRUD | Filter periode/site/status/EOS, pilih kolom (checkbox), ringkasan sebelum unduh, `Menyiapkan berkas…` disabled state, error + retry tanpa file parsial, 403 pelanggaran scope privacy (`Export tidak diizinkan untuk cakupan data ini`), preset disimpan/dipakai ulang per user |
| Backoffice tables | list page + filter query (URL state shareable) | Filter, pagination, empty/error/access denied, loading skeleton |

## 5. Responsive Rules

- EOS flows are mobile-first: single-column, large touch target, sticky status/primary CTA where safe. On desktop viewport, the EOS workspace keeps the centered single-column (max-width e.g. 720px) by default — no separate desktop dashboard for the whole workspace. Exceptions with a wide desktop layout: **Daily Report** (section sidebar navigation, wider note-editing area) and **read-only history screens** (attendance history, report history, notification list). Camera/GPS flows (check-in, clock-out, selfie capture, evidence capture) keep the mobile interaction pattern at every screen size.
- Backoffice is desktop-first: table + filter + detail drill-down, with responsive fallback for smaller screen.
- Do not hide critical data without an explicit expandable/detail affordance.
- All time displays include local timezone label when cross-site ambiguity exists.

## 6. Visual State Requirements

Every interactive screen includes approved visual states for:

```text
Loading / skeleton
Empty
Permission denied
Not found
Offline or request unconfirmed
Validation error
Server error + retry
Success confirmation
Disabled/precondition blocker
```

## 6a. Attendance and Camera/Location UI Rules

- Camera permission notice shown before the browser prompt; denied flow provides actionable Indonesian copy and a retry (see ux.md 4.6).
- Selfie capture uses `MediaDevices.getUserMedia()` with live preview and a **circle overlay** with guide text `Posisikan wajah di dalam lingkaran`; fallback `<input type="file" accept="image/*" capture="user">` when unavailable. No gallery upload is offered in the normal flow.
- **FaceDetector (opsional)**: bila browser FaceDetector API tersedia, frame preview diperiksa berkala; indikator `Wajah terdeteksi` dan tombol jepret aktif setelah kamera stabil; bila API tidak tersedia atau tidak mendeteksi wajah, panduan overlay saja dan tombol jepret tetap dapat digunakan. Tidak ada biometrik tersimpan / face-matching server-side — indikasi hanya framing.
- Location permission notice mirrors the camera notice; denied/failed states display actionable reason. GPS diambil **sekali** dengan **toleransi longgar**: akurasi buruk atau jarak jauh tidak menolak submit — jarak ke site (Haversine, server) disimpan dan ditampilkan sebagai **informasi** (contoh `Jarak Anda ke site: 35 m`), bukan gate; tidak ada state outside-geofence/borderline.
- Check-in tersedia sepanjang tanggal lokal site berjalan — tidak ada window 05:00–11:00, kategori EARLY/ON_TIME/LATE, late_minutes, maupun pembatasan hari kerja; hasil check-in menampilkan waktu server + timezone + jarak info.
- Clock-out tersedia setelah report `SUBMITTED` (required evidence `AVAILABLE`) selama tanggal lokal site belum berganti — tidak ada window 16:00–23:59 maupun `EARLY_CLOCK_OUT`. Report gate bersifat kontinu: blocker message `Laporkan pekerjaan hari ini untuk melanjutkan clock-out.` + CTA ke Daily Report form, lalu kembali ke alur clock-out.
- Double submit dicegah: tombol disabled saat request berjalan; server menolak record kedua (unique EOS+site+tanggal lokal) dengan pesan `Anda sudah check-in/clock-out hari ini.`

## 6b. Daily Report and Attachment UI Rules

- Every section ends with an Evidence Section block: Info Umum optional (0–5), operational sections required (1–5), max 5 attachments per section/item, max 10 attachments per report (aggregate counter in report header). One attachment belongs to exactly one context — no re-link action between contexts; evidence for another context is uploaded separately (same file may be re-uploaded).
- Attachment cards show lifecycle status `AVAILABLE | REJECTED`; only `AVAILABLE` satisfies submit. Validation is synchronous in the upload request (magic byte, size, hash, decode-safe) — hasil langsung final, tanpa `PROCESSING`/`QUARANTINED` state; rejected copy is safe and actionable without pipeline internals.
- Konektivitas section: one `LINK_TRAFFIC` form per link (status `AVAILABLE | DOWN | NOT_CHECKED`, avg/peak inbound/outbound, `KBPS`/`MBPS` unit selector, source, note, evidence) and two separate Speedtest cards (Main/Secondary) with status `SUCCESS | FAILED | NOT_TESTED`, throughput values, latency/jitter, and `route_declaration` (`TESTED_VIA_MAIN_LINK` / `TESTED_VIA_SECONDARY_LINK`). Dual-link snapshot is displayed read-only.
- Connectivity: global offline badge; no offline report authoring (online-only; no draft-local/sync/expiry UI); attendance never offline — hari tanpa koneksi menjadi urusan vendor absensi, tidak ada CTA pengajuan.

## 6c. Privacy UI Rules

- Privacy visibility is enforced in UI: Manager sees no raw selfie/precise GPS/sensitive evidence (summary only, termasuk pada export), HR sees attendance data without Daily Report technical evidence, Supervisor is limited to site scope, EOS sees own data only.
- Selfie, GPS presisi, dan jarak hanya tampil pada detail kehadiran bagi role berwenang; akses/download tercatat sebagai audit event.

## 7. Design-to-Code Rules

- Pen.dev design is source of visual intent; code is source of executable behavior.
- Generated export is reviewed and refactored into reusable React components (shadcn/ui base, Inertia v3 pages); never copied blindly as production code.
- UI cannot add fields, roles, states, or route assumptions not in UX/route contract.
- Production implementation matches approved states, accessibility behavior, and responsive requirement.
