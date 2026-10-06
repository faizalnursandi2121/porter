# ADR-050 — Spatie Permission (RBAC) and Spatie Activity Log (Audit)

**Status:** Accepted  
**Date:** 2026-10-06  
**Decision scope:** PORTER (Portal Operasional Terpadu Sekolah Rakyat) — authorization dan audit

## Context

ADR-006 mendefinisikan model otorisasi dan kebijakan auth (local account, secure cookie session) yang tetap berlaku; implementasi authorizationnya ditujukan untuk middleware Go. Stack baru Laravel 13 membutuhkan pilihan implementasi RBAC dan audit yang idiomatik. Model role platform sederhana dan fixed: 5 role (SUPER_ADMIN, MANAGER, SUPERVISOR, HR, EOS), satu role per user, tanpa role dinamis/permission management oleh end user. Audit event aplikasi harus terpasang di titik kritis eksplisit (login, sensitive access, master change, export, dsb).

## Decision

- RBAC: `spatie/laravel-permission` dengan 5 role fixed — SUPER_ADMIN, MANAGER, SUPERVISOR, HR, EOS — single role per user (tanpa multi-role). Authorization detail per model memakai Laravel Policies + ScopeService (EOS: assignment aktif; non-EOS: semua site aktif) — prinsip scope data ADR-006/ADR-014 tetap.
- Audit: `spatie/laravel-activitylog` dengan tabel `activity_log` untuk audit event aplikasi — dipasang eksplisit di titik kritis (login, sensitive access, perubahan master, export, transisi status report, mutasi inventaris, perubahan status aset, dsb), bukan auto-log semua model.

Amends ADR-006 sebagian: kebijakan auth/session/password/lockout dan model role tetap; implementasi authorization middleware/authorization Go digantikan spatie/laravel-permission + Laravel Policies, dan audit trail digantikan spatie/laravel-activitylog.

## Consequences

- Konvensi standar ekosistem: middleware role, Blade/Inertia directive, Policy per model — mudah di-onboard-kan oleh developer Laravel lain.
- Role fixed + single role menyederhanakan query scope; menambah role = migrasi + revisi kebijakan (keputusan sadar, bukan konfigurasi runtime).
- Audit eksplisit berarti titik kritis harus didaftar dan dijaga di review; tidak ada jaminan capture otomatis semua perubahan.
- Tabel spatie (roles, permissions, model_has_roles, activity_log) masuk skema; permissions granular dapat dipetakan per role di seeder, tidak dikelola end user.

## References

ADR-006 (amended — implementasi authorization), ADR-014 (scope site), ADR-034 (sensitive data visibility), security.md, prd.md, erd.md, api-contract.md.
