# ADR-047 — No Redis; Database Drivers for Session, Cache, Queue, Rate Limit, Lock

**Status:** Accepted  
**Date:** 2026-10-06  
**Decision scope:** PORTER (Portal Operasional Terpadu Sekolah Rakyat) — runtime infrastructure

## Context

ADR-005 memilih Redis untuk session, cache, queue, rate limit, dan koordinasi (atomic lock) dengan topologi Docker Compose yang menyertakan container redis + volume redis_data (dipertahankan di ADR-030/ADR-044). Skala platform adalah 200 site aktif dengan ~1-2 EOS per site — beban konkurensi dan throughput rendah. Dengan migrasi ke Laravel 13, Redis akan menjadi infrastruktur tambahan yang harus dioperasikan, dibackup konfigurasinya, dan dimonitor tanpa kebutuhan performa yang mendasarinya.

## Decision

Tidak ada Redis. Semua kebutuhan runtime memakai driver `database` Laravel:

- Session: driver `database`.
- Cache: driver `database`.
- Queue: driver `database`.
- Rate limit / throttle: cache driver database.
- Atomic lock (mis. alokasi nomor report, lock stok): cache lock via database.

PostgreSQL menjadi satu-satunya stateful dependency (plus disk attachment lokal). Topologi Docker Compose (Sail) kehilangan container redis dan volume redis_data.

Amends ADR-005 sebagian: kebutuhan-kebutuhan koordinasi runtime yang didaftar ADR-005 tetap valid (session, cache, queue, rate limit, atomic lock), tetapi mekanisme pemenuhannya berubah dari Redis ke driver database. ADR-005 superseded pada bagian pemilihan Redis.

## Consequences

- Satu datastore untuk state aplikasi: lebih sedikit service, volume, konfigurasi, dan kegagalan; backup dan restore lebih sederhana (pg_dump mencakup session/cache/queue job/lock).
- Performa cache/session lebih rendah daripada Redis, diterima pada skala 200 site dan trafik rendah; pantau tabel-tabel database driver (sessions, cache, cache_locks, jobs) untuk pertumbuhan dan deadlock lock.
- Lock database (SELECT ... FOR UPDATE / cache lock) menggantikan Redis SETNX; latensi lock sedikit lebih tinggi, dapat diterima.
- Jika kelak beban tumbuh signifikan, migrasi ke Redis dilakukan dengan mengganti nilai konfigurasi driver — perubahan terbatas pada konfigurasi + topologi, bukan kode aplikasi.
- Worker queue tetap container/service terpisah (`php artisan queue:work` + `php artisan schedule:work`).

## References

ADR-005 (amended — Redis diganti database driver), ADR-030 (amended — topologi tanpa redis), ADR-044 (topologi), tech-stack.md, deployment-runbook.md, architecture.md.
