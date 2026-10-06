# ADR-051 — Export: Styled Excel, Formal PDF, CSV, Column Selection, and Per-User Presets

**Status:** Accepted  
**Date:** 2026-10-06  
**Decision scope:** PORTER (Portal Operasional Terpadu Sekolah Rakyat) — export data

## Context

ADR-035 menetapkan CSV export terbatas scope (filter, privacy visibility, audit event). Requirement baru dari product owner melebihi PRD lama: export harus mendukung tiga format dengan kualitas siap pakai — Excel styled untuk operasional, PDF formal untuk dokumen cetak instansi, CSV untuk data mentah — plus kustomisasi kolom, filter, dan preset yang dapat dipakai ulang per user.

## Decision

- Tiga format export:
  - **Excel (xlsx) styled**: header bold, border, lebar kolom auto, judul + periode pada header sheet, filename dinamis.
  - **PDF formal**: header instansi, siap cetak.
  - **CSV**: data mentah.
- Kustomisasi: pemilihan kolom (checkbox per kolom), filter periode/site/status/EOS, dan penyimpanan konfigurasi sebagai **preset per user** yang dapat dipakai ulang.
- Proses sinkron (streamed) — tidak ada job async; audit event dicatat sebelum stream dimulai.
- Privacy visibility tetap di-enforce: Manager tidak menerima raw selfie/precise GPS (ADR-034 tetap).

Amends ADR-035: prinsip scope filter, audit, dan privacy tetap; format dan kustomisasi diperluas melampaui CSV PRD lama.

## Consequences

- Requirement baru di luar PRD lama didokumentasikan sebagai keputusan binding; prd.md FR export direvisi sesuai ADR ini.
- Preset per user menambah tabel kecil (preset export per user) — konfigurasi tercatat, mudah dihapus.
- Export sinkron streamed: beban memory terkendali untuk volume MVP; export sangat besar bisa menjadi lambat — dapat ditunda ke job bila kelak dibutuhkan (perubahan via ADR baru).
- Audit event tetap tercatat sebelum stream, sehingga export yang gagal di tengah stream tetap ter-audit.
- Render PDF formal menambah dependency render (mis. library PDF Laravel) — dipilih saat implementasi dengan mempertimbangkan kualitas header instansi.

## References

ADR-035 (amended), ADR-034 (privacy visibility), ADR-050 (audit via activity log), prd.md, api-contract.md, security.md.
