# ADR-049 — Checklist Master: One Versioned Table with JSON Structure

**Status:** Accepted  
**Date:** 2026-10-06  
**Decision scope:** PORTER (Portal Operasional Terpadu Sekolah Rakyat) — Daily Report checklist master

## Context

ADR-041 (master data model) dan ADR-026 (controlled-value model) memodelkan checklist master sebagai 5-6 tabel relasional (checklist version, section, item, option, rule, dsb) dengan relasi penuh. Praktiknya: struktur checklist berubah per versi secara keseluruhan, satu-satunya template adalah DAILY_SITE_REPORT global, dan rule validasi tetap harus dievaluasi eksplisit di kode (bukan interpreter rule generik) — relasi penuh menambah beban model/join tanpa kebutuhan query per-baris rule/option.

## Decision

Checklist master dimodelkan sebagai satu tabel versi dengan struktur JSON: `checklist_versions` (version, status DRAFT/PUBLISHED/SUPERSEDED/RETIRED, struktur JSON berisi section/item/option/rule). Super Admin yang publish; versi published bersifat immutable. Satu template global: DAILY_SITE_REPORT.

- Report tetap punya snapshot JSONB (definisi checklist + site + EOS + network links) pada header `daily_reports`, plus `daily_report_answers` (jawaban per item, nilai terstruktur JSON per item), dan `checklist_version_id` pada header — jadi konten published tetap ter-freeze per report (prinsip snapshot ADR-013 tetap).
- Rules validasi TETAP dievaluasi eksplisit per rule di kode (FormRequest/service), bukan interpreter JSON generik: rules v1 sesuai dokumen lama (MINOR→note wajib, MAJOR→note+evidence, NOT_CHECKED→reason, AP offline vs status, suhu wajib, dsb — semua rules FR-10 lama dipertahankan).
- Checklist v1 sections tetap: Info Umum (read-only), Router & Firewall, Access Point, Infrastruktur & Lingkungan, Konektivitas (dual-link + LINK_TRAFFIC + 2 SPEEDTEST_RESULT terpisah); semua enum/range/rules FR-10 lama dipertahankan.
- Amends/supersedes ADR-041 (model relasional diganti 1 tabel + JSON; lifecycle versi DRAFT/PUBLISHED/SUPERSEDED/RETIRED tetap) dan ADR-026 (controlled value tetap hidup di dalam struktur JSON sebagai daftar option per item — bukan tabel option relasional).

## Consequences

- Skema lebih sederhana: 1 tabel master vs 5-6 tabel; publikasi versi = satu INSERT; tidak ada sinkronisasi relasi section/item/option.
- Query/validasi struktur berjalan di level aplikasi; integritas struktur JSON dijaga validasi publish (schema sederhana internal) — tidak ada FK per item/option.
- Rules tetap di kode: menambah rule baru = revisi kode + versi checklist baru; tidak ada rule engine.
- Snapshot report tetap JSONB sehingga report lama tidak terpengaruh perubahan versi master.
- Report snapshot & answer model dari ADR-013 tetap berlaku (amended hanya pada sumber master).

## References

ADR-013 (amended sebagian — sumber snapshot), ADR-026 (amended/superseded — controlled value dalam JSON), ADR-041 (superseded — model master), ADR-012 (nomor report tetap), prd.md FR-10, erd.md, api-contract.md.
