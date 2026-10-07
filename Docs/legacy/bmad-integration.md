# BMAD Integration — PORTER (Portal Operasional Terpadu Sekolah Rakyat)

> **⚠️ ARSIP — BUKAN KEBENARAN AKTIF (sejak 2026-10-07).**
> Dokumen ini merujuk baseline lama yang sudah diganti: `Docs/prd.md`, `Docs/backlog.md`, `Docs/api-contract.md`, `Docs/data-dictionary.md`, dan ADR kini berada di `Docs/legacy/` dan sebagian besar tidak berlaku (5 role, kontrak lama, stack lama).
> Sumber kebenaran aktif: **`Docs/PRD/prd-porter-portal-operasional-terpadu.md`** + task list **`Docs/PRD/tasks-task-list-baru.md`** — lihat `Docs/README.md`.
> Keputusan eksekusi saat ini: cukup omp (Oh My Pi), tanpa BMAD — lihat diskusi 2026-10-07.

Panduan menjalankan BMAD Method di repository ini. **Dokumentasi existing adalah single source of truth.** Agent BMAD tidak menghasilkan ulang PRD/architecture — mereka mengonsumsi dokumen yang sudah final.

## 1. Aturan utama (berlaku untuk semua persona BMAD)

1. **Dokumen existing = authoritative input.** Jangan generate PRD, architecture, atau data model baru. Jika agent menemukan konflik/kebutuhan baru: **stop, laporkan, usulkan revisi dokumen + ADR** (lihat `AGENTS.md` §1). Jangan tulis kontrak paralel.
2. **Istilah & enum terkunci**: gunakan persis nama di `Docs/data-dictionary.md` §12 (enum catalog). Jangan sinonim.
3. **Keputusan binding**: 53 ADR di `Docs/adr/` (`adr/README.md` = index, termasuk tabel peta ADR superseded → pengganti; ADR-007, 038, dan 001/002/003/018/010/024/036–040/017/016/029/021/022/026/041/035 Superseded/amended per masing-masing ADR 045–053). Semua revisi keputusan wajib lewat ADR, bukan editoran diam-diam.
4. **Bahasa**: konten produk/UX Bahasa Indonesia; field, enum, route path, error code English (lihat `Docs/prd.md` konvensi).
5. **Bahasan per-story** merujuk: nomor FR (PRD), story ID (backlog), route/halaman (api-contract.md), ADR number — bukan narasi bebas.

## 2. Mapping persona BMAD → dokumen sumber

| Persona BMAD | Peran di repo ini | Baca dulu |
|---|---|---|
| **Analyst** | SKIP generasi PRD. Hanya dipakai untuk *validasi* coverage: setiap FR punya story, setiap story punya AC. PRD final = `Docs/prd.md` (Laravel 13 + Inertia) | `Docs/prd.md`, `Docs/backlog.md` |
| **Architect** | SKIP generasi architecture. Validasi implementasi story terhadap boundary modul Laravel, aturan dependency, prohibited anti-patterns. Sumber = `Docs/architecture.md` + `Docs/tech-stack.md` | `Docs/architecture.md`, `Docs/tech-stack.md`, ADR-045–053 |
| **PM / Product Owner** | Prioritas & scope decisions. PRD §scope & out-of-scope binding (disiplin kehadiran/Attendance Request, offline draft, email/WhatsApp, face matching, PostGIS — semuanya TIDAK dibangun) | `Docs/prd.md` (out-of-scope), `Docs/backlog.md` |
| **SM (Scrum Master)** | **Sharding** `Docs/backlog.md` → story files. Satu story file per baris tabel (ST-x.yz), AC disalin verbatim + referensi dokumen pendukung | `Docs/backlog.md`, `Docs/delivery-workflow.md` |
| **Dev (Implementer)** | Implementasi per story **full-stack Inertia**: satu dev menulis migration/model, route + controller + FormRequest, Inertia page (React 19), dan Pest test dalam satu story. Tidak ada contract-first OpenAPI, tidak ada generated types/mock server — kontrak lintas layer hidup di codebase dan diverifikasi test | `Docs/api-contract.md` (kontrak halaman Inertia), `Docs/erd.md`, `Docs/data-dictionary.md`, `Docs/delivery-workflow.md` |
| **QA** | Test plan dari `Docs/test-strategy.md` (unique constraint double submit, gate report clock-out, tanggal lokal sama WIB/WITA/WIT, attachment validasi sinkron + thumbnail queued, E2E split smoke/regression) | `Docs/test-strategy.md` |

## 3. Alur eksekusi yang disarankan (ilustratif; story ID backlog authoritative)

```
1. Shard E1 Foundation   → story files (SM)
2. Review story files    → user approve
3. Dev per story (full-stack Inertia slice) → PR ke branch faizaldev (ADR-030: promotion faizaldev → staging → production)
4. Shard E2 Master Data  → ulangi
   ... (E3 Attendance, E4 Daily Report+Attachment, E5 Inventory, E6 Governance, E7 Quality)
```

## 3a. Validasi coverage sebelum sharding (SM)

Sebelum shard per epic, SM memvalidasi **coverage route/halaman**: setiap halaman/route Inertia di `Docs/api-contract.md` (kontrak halaman & aksi) dimiliki **tepat satu story**. Checklist ownership:

1. Ekstrak daftar halaman/route (path + method) dari `Docs/api-contract.md`.
2. Petakan setiap route/halaman ke tepat satu story ID di `Docs/backlog.md`.
3. Route tanpa owner → laporkan sebagai gap backlog (stop, revisi backlog sebelum shard).
4. Route dimiliki lebih dari satu story → pilih owner tunggal, hapus duplikat klaim.
5. Hasil mapping disimpan sebagai lampiran story terkait epic tersebut.

Group halaman/route yang wajib sudah dimiliki: auth (login, logout, ganti password, must_change_password flow), admin/users + reset-password, site master + network link, assignment, checklist version, attendance (check-in/clock-out + riwayat), daily report + attachment, inventory (asset registrasi, stock, finding), notification, export, analytics, audit log.

- Story dalam satu epic boleh paralel **hanya jika** migration/controller/route/file-ownership tidak bertabrakan (`AGENTS.md` §2; `Docs/delivery-workflow.md` §6).
- Deferred jangan di-shard: backlog section `Deferred (Fase 2)` (legal hold ST-6.08) di luar MVP.

## 4. Story template (WAJIB per story full-stack/integration)

Setiap story file harus memuat blok:

```markdown
## Contract
- Routes: (path + method + nama halaman Inertia dari api-contract.md, exact)
- FormRequest/validation: (aturan validasi + error copy dari api-contract.md §17)
- DB objects: (tabel/kolom dari Docs/erd.md)
- Enums: (dari Docs/data-dictionary.md §12)
- Policies/scope: (Policy + ScopeService; visibility matrix)
- Audit: (event activity_log apa yang wajib dicatat)
- ADR terkait: (nomor)
```

## 4a. Story file convention (path & template)

**Path**: `Docs/stories/{EPIC}/ST-x.yz.md` — contoh `Docs/stories/E2/ST-2.02a.md`, `Docs/stories/E3/ST-3.09b.md`. `{EPIC}` = kode epic backlog (E1–E7). Story ID backlog (ST-x.yz termasuk suffix huruf) adalah **authoritative**; alur eksekusi §3 hanya ilustratif, bukan sumber ID.

Template lengkap per story file (WAJIB):

```markdown
# Story — {ID} ({Type})

## AC (verbatim dari backlog)
(Salin verbatim kolom Acceptance criteria baris story di Docs/backlog.md — jangan parafrase.)

## Contract
(Blok Contract per §4: Routes, FormRequest/validation, DB objects, Enums, Policies/scope, Audit, ADR terkait.)

## Scope & actor
(Apa yang dibangun + role actor utama: EOS/Supervisor/Manager/HR/Super Admin.)

## Dependencies
(Depends on dari backlog + catatan paralel-safe.)

## Test expectation
(Boundary, E2E smoke vs regression sesuai Docs/test-strategy.md.)

## Reference docs
(Nomor FR Docs/prd.md; screen ID Docs/ux.md §9; ADR; route/halaman api-contract.md; section Docs/erd.md/data-dictionary.md.)
```

## 5. Definition of Done per story (dari AGENTS.md §2, delivery-workflow.md §8 & backlog DoD)

- [ ] Implementasi sesuai kontrak di atas, tanpa deviasi diam-diam
- [ ] Pest unit/integration test AC story lulus (boundary termasuk); journey user-visible terverifikasi pada aplikasi nyata (web + worker bila terlibat)
- [ ] Full-stack slice lengkap: migration/model + route/controller/FormRequest + Inertia page dalam satu PR (tanpa mock/MSW yang tersisa)
- [ ] Authorization & scope object diverifikasi (visibility matrix `Docs/security.md`)
- [ ] Audit event (activity_log) untuk mutasi kritis
- [ ] Formatter/lint/typecheck lulus (Pint, Larastan, Pest, typecheck, build)
- [ ] Dokumen terkait di-update bila ada deviasi yang di-approve (dengan ADR bila perlu)
