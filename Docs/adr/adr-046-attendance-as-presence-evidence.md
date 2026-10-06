# ADR-046 — Attendance as Presence Evidence (Selfie + GPS + Timestamp)

**Status:** Accepted  
**Date:** 2026-10-06  
**Decision scope:** PORTER (Portal Operasional Terpadu Sekolah Rakyat) — modul absensi

## Context

Model absensi baseline (ADR-010, ADR-024, ADR-036, ADR-037, ADR-038, ADR-039, ADR-040) membangun disiplin kehadiran lengkap: geofence gate, window jam, late minutes, kalender kerja/holiday, Attendance Request, job ABSENT, dan cross-midnight scope. Konteks bisnis berubah: EOS diambil dari vendor, dan disiplin/potongan kehadiran sepenuhnya ditangani oleh sistem absensi vendor EOS. Platform Comtronics hanya membutuhkan BUKTI KEHADIRAN EOS di site — bukan sistem penegakan disiplin.

## Decision

Absensi direduksi menjadi bukti kehadiran:

- Check-in/clock-out = selfie + koordinat GPS + server timestamp UTC. GPS diambil sekali dengan toleransi longgar; jarak ke site dihitung Haversine di PHP dan disimpan sebagai INFORMASI, bukan gerbang — tidak ada penolakan radius.
- Selfie UI: overlay lingkaran "posisikan wajah di dalam lingkaran"; deteksi wajah opsional via browser FaceDetector API bila tersedia (tombol jepret aktif setelah kamera stabil); fallback overlay panduan saja. Tanpa penyimpanan biometrik, tanpa face-matching server-side.
- Unique constraint satu record per EOS + site + tanggal lokal; check-in dan clock-out harus tanggal lokal yang sama; maksimum 1 selfie check-in + 1 selfie clock-out.
- Gate yang tetap: clock-out hanya valid setelah Daily Report tanggal tersebut `SUBMITTED` dan required evidence `AVAILABLE` (ADR-011 tetap; flow kontinu mengarahkan EOS melengkapi report).
- Status record: `NOT_CHECKED_IN` → `CHECKED_IN` → `COMPLETED`. Kehadiran hari tanpa absen = kosongnya record (urusan vendor).
- Timezone site (IANA) tetap disimpan untuk PENAMPILAN waktu lokal di UI; timestamp tetap UTC canonical (ADR-009 tetap).

Dihapus dari scope: window jam (05:00–11:00 / 16:00–23:59), late minutes, klasifikasi EARLY/ON_TIME/LATE/EARLY_CLOCK_OUT, geofence gate + borderline flag, kalender kerja/holiday/override/EFFECTIVE_WORKING_DAY, periode 21–20, job ABSENT, modul Attendance Request seluruhnya, dan semua aturan hari kerja. Semua fitur tersebut dipindah ke out of scope dengan alasan "disiplin kehadiran ditangani absensi vendor EOS".

## Consequences

- Supersedes ADR-010 sebagian (server time + selfie tetap; geofence sebagai gate dihapus — jarak hanya informasi); supersedes penuh ADR-024 (Attendance Request), ADR-036 (site-local workday/calendar), ADR-037 (attendance window/klasifikasi; unique-per-day dan gate report tetap di ADR ini), ADR-038 (attendance exception — sudah superseded sebelumnya, kini seluruh model exception hilang), ADR-039 (cross-midnight — tetap out of scope, kini tanpa konteks shift), ADR-040 (national holiday/effective working-day).
- Skema database dan ERD absensi menyusut drastis; job scheduler ABSENT dan alur approval Attendance Request dihapus.
- Audit tetap pada event absensi (check-in/clock-out) sebagai bukti.
- Jarak dan koordinat tetap direkam sehingga analisis manual tetap mungkin tanpa menutup akses.
- Potongan gaji/disiplin tidak bisa diturunkan dari sistem ini; kebutuhan tersebut dilayani vendor absensi EOS.

## References

ADR-009 (tetap — UTC canonical), ADR-010 (partially superseded), ADR-011 (tetap — gate daily report), ADR-024/036/037/038/039/040 (superseded), ADR-019 (mapping/geolocation — jarak jadi informasi), prd.md, erd.md, ux.md.
