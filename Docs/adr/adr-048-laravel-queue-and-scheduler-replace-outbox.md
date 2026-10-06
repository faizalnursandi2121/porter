# ADR-048 — Laravel Queue and Scheduler Replace Transactional Outbox

**Status:** Accepted  
**Date:** 2026-10-06  
**Decision scope:** PORTER (Portal Operasional Terpadu Sekolah Rakyat) — asynchronous work

## Context

ADR-017 memilih transactional outbox pattern dengan Go worker yang memindai record outbox dan mengirim side effect (notifikasi, derivative attachment, dsb) untuk menjamin exactly-once-ish delivery di atas Redis queue. Dengan migrasi ke Laravel 13 tanpa Redis (ADR-047), queue memakai driver `database`; Laravel sudah menyimpan job di dalam database dan menyediakan mekanisme transaction-safe dispatch (`dispatchSync`/`afterCommit`), sehingga outbox tabel terpisah + poller menjadi duplikasi infrastruktur tanpa nilai tambah pada skala platform.

## Decision

Transactional outbox dan Go worker dihapus. Asynchronous work ditangani oleh:

- Laravel Queue (driver `database`, ADR-047): job dikirim dari service layer; dispatch di dalam transaksi database memakai `afterCommit` agar job hanya masuk setelah commit data — menggantikan jaminan konsistensi outbox dengan mekanisme bawaan Laravel.
- Laravel Scheduler (`php artisan schedule:work`) untuk pekerjaan terjadwal (backup attachment, housekeeping, dsb).
- Worker dijalankan sebagai container/service terpisah: `php artisan queue:work` dan `php artisan schedule:work`.
- Notifikasi memakai tabel notifications Laravel (database channel) + polling (ADR-033 konteksnya tetap, mekanisme menyusut).

Supersedes ADR-017 sepenuhnya.

## Consequences

- Tidak ada tabel outbox, tidak ada poller/relay, tidak ada worker Go; kode async = job class Laravel standar.
- Jaminan delivery bergantung pada job database Laravel: retry bawaan, failed_jobs table, dan idempotensi yang tetap wajib diimplementasikan di handler job (side effect harus aman dieksekusi ulang).
- `afterCommit` mencegah job berjalan sebelum commit; job yang gagal setelah commit di-retry oleh queue worker.
- Operasional worker menjadi standar Laravel (queue:work restart, horizon tidak dipakai karena tanpa Redis; monitoring via jobs/failed_jobs + health endpoint).
- Kehilangan observabilitas relay outbox; gantinya failed_jobs + log structured.

## References

ADR-017 (superseded), ADR-033 (notifikasi — mekanisme Laravel notifications), ADR-044 (topologi worker), ADR-047 (queue driver database), architecture.md, tech-stack.md, deployment-runbook.md.
