# docs-consistency-report.md

**Project:** PORTER (Portal Operasional Terpadu Sekolah Rakyat)
**Tanggal konsolidasi:** 2026-10-06
**Sumber keputusan binding:** sesi coaching revisi stack (matriks keputusan `local://decisions.md`, ADR-045–ADR-053)

## 0. Konteks Revisi

Revisi menyeluruh 2026-10-06: stack berpindah dari Go+Gin+pgx/sqlc+React SPA terpisah+Redis+transactional outbox+Dokploy+OpenAPI ke **Laravel 13 + Inertia v3 + React 19 single-app**. Domain absensi disederhanakan dari sistem disiplin kehadiran menjadi **bukti kehadiran** (selfie+GPS+timestamp; jarak ke site informasional tanpa gerbang) karena EOS diambil dari vendor dan disiplin kehadiran ditangani absensi vendor. Scope 200 site aktif.

## 1. File yang diubah

| File | Perubahan utama |
|---|---|
| `prd.md` | PRD canonical ditulis ulang: absensi bukti kehadiran (FR check-in/out baru), seluruh FR/BR/AC disiplin dihapus dan dipindah ke out-of-scope; FR export baru (Excel styled + PDF formal + CSV, pilih kolom + preset per user); FR inventaris aset registrasi oleh EOS dengan tag gudang + 6 status; FR-10 checklist rules v1 dipertahankan utuh; auth policy tetap |
| `architecture.md` | Modular monolith Laravel: single Inertia app, Eloquent+Postgres, queue/scheduler database driver (worker + scheduler container terpisah), spatie permission+activitylog, Policies+ScopeService, transaction pattern Eloquent (submit report + sequence, mutasi stok row lock), Compose 3 environment, backup pg_dump+rsync, health `/up` |
| `tech-stack.md` | Tabel stack Laravel penuh + bagian package sudah-terinstall vs harus-di-install (spatie permission/activitylog, Intervention Image+Imagick/libheif, maatwebsite/excel, barryvdh/laravel-dompdf, vite-plugin-pwa, Leaflet) |
| `erd.md` | `attendance_records` sederhana (unique user+site+work_date_local; jarak/akurasi informasional; tanpa late/klasifikasi/window); checklist JSON versioned (`checklist_templates` ringkas + `checklist_versions` struktur JSONB) menggantikan 5–6 tabel; `asset_status_history` baru; `export_presets` baru; hapus `national_calendar_events`, `site_calendar_overrides`, `attendance_requests`, `idempotency_records`, `outbox_events`; spatie tables + `activity_log` + Laravel bawaan (sessions/notifications/jobs) |
| `data-dictionary.md` | Semua field/enum versi Laravel; enum domain lama dipertahankan (Log Anomali, AP, Kelistrikan, Lingkungan, link role, connection_medium, mutation types, report status, checklist version status, throughput unit, status aset 6 nilai, status attendance 3 nilai); katalog `attachment_type` 13 nilai; `export_presets.data_type` 5 nilai |
| `api-contract.md` | Diganti penuh dari REST/OpenAPI menjadi **Kontrak Halaman & Aksi Inertia**: route matrix per domain (Route/Method/Page-Action/Role/Validasi kunci), form fields + rules, error bag Inertia, katalog kunci pesan error; tanpa idempotency key header |
| `ux.md` | Journey EOS baru (login → status → check-in selfie overlay lingkaran + FaceDetector opsional → report → submit → clock-out gate kontinu → selesai); journey registrasi aset EOS + mutasi stok EOS; journey export preset; hapus journey Attendance Request/kalender/window |
| `ui-spec.md` | Route baseline halaman Inertia + screen-to-action matrix; §6a overlay lingkaran + GPS toleransi longgar + jarak info; §6b attachment 2 status (AVAILABLE/REJECTED sinkron); §6c privacy-only |
| `security.md` | Semua kebijakan lama di-map ke mekanisme Laravel (Fortify, RateLimiter dua counter anti-enumeration, session database driver, middleware must_change_password, spatie activitylog, validasi upload sinkron fail-closed); hapus Redis/outbox/REST-specific |
| `test-strategy.md` | Pest feature test per domain (absensi model baru, FR-10 rules per-rule eksplisit, dual-link, lifecycle report, attachment sinkron+thumbnail, inventaris, export 3 format+preset+privacy); route+FormRequest test menggantikan OpenAPI contract test; E2E journey baru; CI gate pint/larastan/pest |
| `backlog.md` | Epics E1–E7 versi Laravel; E3 attendance sederhana; story export preset + registrasi aset EOS; hapus story kalender/attendance-request/outbox/contract-first |
| `delivery-workflow.md` | Full-stack satu dev per story (migration → route/controller/FormRequest → Inertia page → Pest); hapus contract-first paralel/OpenAPI/MSW |
| `deployment-runbook.md` | Topologi Compose Laravel (web+worker+scheduler+pgsql, volume postgres_data+storage_data); migrate service `php artisan migrate --force`; smoke post-deploy journey baru; tanpa Redis/Dokploy/ClamAV |
| `operations-runbook.md` | Prosedur spatie role; assignment change (info timezone/radius); section Attendance Exception dihapus (tidak ada recovery sistem); attachment REJECTED sinkron; Export Ops + preset; audit activitylog |
| `release-checklist.md` | Quality gate Pint/Larastan/Pest/typecheck/build Vite; smoke-E2E journey baru; WIB/WITA/WIT tetap diuji |
| `backup-restore-drill.md` | Drill pg_restore via container postgres scratch (isolasi volume/network dipertahankan); verifikasi stack Laravel throwaway `/up` + login smoke; attachment rsync/tar; tanpa redis scratch |
| `bmad-integration.md` | Mapping persona ke dokumen revisi; Dev = full-stack Inertia story; QA = test-strategy baru; 53 ADR; kontrak = `api-contract.md` (halaman Inertia) |
| `adr/` | 9 ADR baru (045–053) + `README.md` index 53 ADR dengan peta supersession |

File dihapus: `PRD-Sekolah-Rakyat-EOS-Operations-Platform-Revised.md` (duplikat; seluruh isi domainnya yang masih berlaku terverifikasi sudah tercakup di `prd.md`), `api/openapi/` (OpenAPI tidak dipakai lagi), `*:Zone.Identifier`.

## 2. Verifikasi konsistensi (audit lintas dokumen 2026-10-06)

Diverifikasi via audit read-only menyeluruh + perbaikan konsistensi:

| Pemeriksaan | Status |
|---|---|
| Jejak stack lama (Gin/pgx/sqlc/outbox/OpenAPI/Dokploy/Redis) | ✅ Hanya negasi eksplisit dan sejarah ADR |
| Jejak fitur absensi terhapus (window/late/kalender/Attendance Request/ABSENT/21–20) | ✅ Hanya negasi/out-of-scope/sejarah ADR |
| Naming 17 entity utama lintas erd/dictionary/api-contract/architecture | ✅ Identik |
| Enum: status aset 6, report 4, attendance 3, checklist version 4, mutasi 8 | ✅ Identik semua dokumen |
| Role & scope (5 role, single role, EOS=assignment, non-EOS=semua site) | ✅ Identik |
| Export (3 format + preset + audit pre-stream + privacy) | ✅ Identik 12+ dokumen |
| Aturan domain inti (FR-10 rules, evidence limits 10/5/5/1+1/5, dual-link, nomor CMX.WR, reopen 7 hari, gate clock-out) | ✅ Terpelihara penuh di prd/test/backlog/api-contract/architecture |

## 3. Perbaikan konsistensi pasca-audit

9 inkonsistensi kecil (hasil penulisan paralel) telah diperbaiki dan diverifikasi ulang:

1. `ALREADY_CLOCK_OUT` → `ALREADY_CLOCKED_OUT` di `prd.md` (3 lokasi).
2. Window submit 05:00–23:59 di FR-08 dihapus (tidak ada dalam keputusan).
3. `eos_user_id` → `user_id` pada `attendance_records` di data-dictionary (tabel `eos_site_assignments`/`daily_reports` tetap memakai `eos_user_id` — sesuai semantik masing-masing).
4. Kolom `export_presets` disatukan `data_type` (5 nilai: ATTENDANCE, DAILY_REPORT, INVENTORY, ASSET, INVENTORY_FINDING) di erd/dict/api-contract.
5. Katalog `attachment_type` disatukan 13 nilai identik di erd/dict/api-contract.
6. Nilai `ATTENDANCE` dihapus dari `intended_entity_type` api-contract (selfie via FK langsung).
7. Tabel `idempotency_records` dihapus dari erd (tidak ada idempotency key di MVP — security §8.3).
8. `export_presets.name` disatukan varchar(100).
9. `adr/README.md`: ADR-043 ditandai `Amended by ADR-045`.

## 4. Prinsip yang tetap binding

- Semua ADR lama (001–044) tidak diubah isinya (sejarah); peta supersession ada di `adr/README.md`.
- `prd.md` = PRD canonical tunggal; tidak ada file PRD lain.
- `api-contract.md` = kontrak halaman/aksi Inertia (bukan REST); tidak ada openapi.yaml.
- Sumber keputusan coaching tersimpan di `local://decisions.md` (matriks final).
