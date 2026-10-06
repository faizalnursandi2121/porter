# ADR-045 — Laravel 13 + Inertia Full-Stack Single App

**Status:** Accepted  
**Date:** 2026-10-06  
**Decision scope:** PORTER (Portal Operasional Terpadu Sekolah Rakyat) — migrasi stack

## Context

Dokumen baseline (ADR-001, ADR-002, ADR-003, ADR-018) mendesain platform sebagai Go+Gin HTTP API + React SPA dengan REST/OpenAPI contract dan worker terpisah dari codebase yang sama. Project berpindah stack ke Laravel 13 sebelum fase implementasi lanjut. Tim pengembang adalah satu tim kecil full-stack; memelihara dua codebase (Go backend + React SPA) plus REST layer terpisah menambah beban kontrak, duplikasi validasi, dan boilerplate API yang tidak memberi nilai pada skala produk (200 site aktif, ~1-2 EOS per site).

## Decision

Platform dibangun sebagai satu aplikasi Laravel 13 full-stack dengan Inertia v3 + React 19: server-side routing, tanpa REST API layer terpisah, tanpa OpenAPI. React starter kit resmi sudah ter-install (Fortify, Passkeys, Inertia, Tailwind 4, shadcn/ui, Wayfinder, Pest, Larastan, Pint) dan menjadi baseline. Ecosystem Laravel dipakai untuk auth (Fortify), queue, scheduler, validasi (FormRequest), dan Eloquent + migrations di atas PostgreSQL. Routing halaman Inertia, FormRequest, dan kontrak validasi/error handling didokumentasikan di api-contract.md (bukan REST/OpenAPI).

- Supersedes ADR-002 (React SPA terpisah — React tetap dipakai, tetapi sebagai Inertia page, bukan SPA standalone dengan Vite dev server dan REST client sendiri).
- Supersedes ADR-003 (Go dan Gin untuk backend HTTP API).
- Supersedes ADR-018 (REST/OpenAPI, error envelope, dan idempotent critical mutations — kontrak menjadi Inertia page + FormRequest; prinsip error konsisten dan idempotensi mutasi kritis tetap dijaga di level service).
- Partially supersedes ADR-001: prinsip modular monolith (satu codebase, domain terisolasi) tetap berlaku, tetapi "Go application codebase dengan API dan worker sebagai command terpisah" diganti Laravel single app dengan worker container terpisah.

## Consequences

- Satu codebase, satu bahasa (PHP + React page), satu pipeline CI; tidak ada kontrak REST/OpenAPI yang harus disinkronkan ganda.
- Validasi dan otorisasi hidup di server (FormRequest + Policy) dan otomatis terpakai oleh halaman Inertia; error handling mengikuti konvensi Laravel/Inertia.
- Ecosystem Laravel (Fortify, queue, scheduler, Eloquent) mengurangi kode yang harus ditulis dan dirawat sendiri.
- Keputusan teknologi Go (pgx/sqlc) pada ADR-004 untuk data access diganti Eloquent + migrations; PostgreSQL tetap database-nya (dikonfigurasi via Sail).
- Kunci idempotensi eksplisit ala ADR-018 tidak lagi berupa header API kontraktual; idempotensi mutasi kritis dijamin lewat unique constraint database dan transaksi service.
- Pindah stack membatalkan investasi tooling Go (sqlc, error envelope middleware); biaya ini diterima karena belum ada kode produksi berjalan.

## References

ADR-001 (partially superseded), ADR-002 (superseded), ADR-003 (superseded), ADR-004 (diganti implementasi data access), ADR-018 (superseded), tech-stack.md, architecture.md, api-contract.md, prd.md.
