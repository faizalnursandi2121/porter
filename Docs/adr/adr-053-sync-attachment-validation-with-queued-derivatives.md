# ADR-053 — Attachment: Synchronous Request Validation with Queued Thumbnail Derivatives

**Status:** Accepted  
**Date:** 2026-10-06  
**Decision scope:** PORTER (Portal Operasional Terpadu Sekolah Rakyat) — attachment pipeline

## Context

ADR-016 (original immutable + optimized derivatives), ADR-021 (HEIC/HEIF handling), dan ADR-029 (quarantined evidence processing) mendesain pipeline async: upload → QUARANTINED → worker memvalidasi + memproses derivatives → AVAILABLE. ADR-044 sudah menghapus ClamAV; tanpa scan malware, satu-satunya pekerjaan berat yang tersisa adalah derivatif (thumbnail/preview). Pada stack Laravel, validasi tipe/ukuran/hash/decode-safe murah untuk dilakukan sinkron di request, sehingga state QUARANTINED/PROCESSING dan gate async tidak lagi punya justifikasi.

## Decision

- **Validasi sinkron di request** (FormRequest/service): magic byte, size maksimum, hash SHA-256, decode aman. File yang lolos validasi langsung berstatus `AVAILABLE`; file gagal → `REJECTED`.
- **Derivatif (thumbnail/preview) via queued job**: WebP 2048px (preview) dan 480px (thumbnail) di-generate oleh job queue memakai Intervention Image dengan Imagick + libheif untuk HEIC. Derivatif di-generate ulang bila job gagal; availability file asli tidak tergantung derivatif.
- Local disk private (storage/app, di luar public/), download via controller terkontrol + audit (ADR-008 tetap). Format JPG/PNG/WebP/HEIC/PDF, max 10MB, limit per context tetap: report 10, section 5, item 5, selfie 1+1, mutation 5 (ADR-028 tetap).

Amends ADR-016 sebagian (original immutable tetap; derivatives sekarang queued job, bukan worker pipeline gate), ADR-021 sebagian (HEIC tetap didukung; handling via Imagick+libheif pada queued job), ADR-029 sebagian (lifecycle menyusut menjadi `AVAILABLE | REJECTED` setelah validasi sinkron — status QUARANTINED/PROCESSING dihapus).

## Consequences

- UX lebih sederhana: upload sukses = file tersedia dan bisa dipakai (mis. required evidence AVAILABLE untuk clock-out) tanpa menunggu pipeline async.
- Derivatif mungkin belum siap sesaat setelah upload; UI menangani state "preview belum tersedia" (fallback placeholder/original) secara grace.
- Request upload membayar biaya validasi (hash + decode probe) — kecil untuk max 10MB, diterima.
- Worker queue (ADR-048) memproses derivatif; kegagalan derivatif terlihat di failed_jobs dan dapat di-retry tanpa memengaruhi record attachment.
- Tanpa ClamAV tetap (ADR-044); risiko malware diterima dengan mitigasi validasi + akses terkontrol.

## References

ADR-008 (tetap — local disk private), ADR-016 (amended), ADR-021 (amended), ADR-028 (tetap — quota/format), ADR-029 (amended — lifecycle menyusut), ADR-044 (tetap — tanpa ClamAV), ADR-048 (queue), security.md, prd.md, api-contract.md.
