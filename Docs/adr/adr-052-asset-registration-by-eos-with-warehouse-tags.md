# ADR-052 — Asset Registration by EOS with Warehouse-Issued Tags

**Status:** Accepted  
**Date:** 2026-10-06  
**Decision scope:** PORTER (Portal Operasional Terpadu Sekolah Rakyat) — inventaris aset

## Context

ADR-022 menetapkan standar identifier site dan aset, mengasumsikan sistem mengelola siklus aset antar site dan tag/registrasi aset oleh Supervisor. Operasional aktual: tag asset + serial number sudah ada dari gudang Comtronics sebelum barang dikirim ke site; barang aset tidak berpindah antar site — bila rusak, dikembalikan ke gudang. Registrasi oleh Supervisor menjadi langkah duplikatif di lapangan; pihak yang tepat menerima barang adalah EOS di site.

## Decision

- Registrasi aset dilakukan oleh **EOS** saat barang datang dari gudang (bukan Supervisor).
- Tag asset + SN yang diinput EOS adalah tag yang **sudah ada dari gudang Comtronics** — EOS input apa adanya; sistem memvalidasi format (regex CMX) + keunikan (unique constraint), TIDAK meng-generate tag.
- Barang aset **tidak berpindah antar site**; aset rusak → dikembalikan ke gudang (status RETURNED/DAMAGED).
- Status aset menjadi 6: `IN_USE`, `SPARE`, `RETURNED`, `DAMAGED`, `LOST`, `DISPOSED`. Perubahan status = transaksi beralasan + audit + foto bila rusak/hilang. Foto wajib saat registrasi.
- Barang material/sparepart tetap dikelola sebagai stok kuantitas per site (ADR-025 tetap).

Amends ADR-022: standar format identifier tetap (regex CMX, SN, kode site); perubahan pada pihak registran (EOS, bukan Supervisor), sumber tag (gudang, bukan generate), dan set status aset.

## Consequences

- Tidak ada generator tag di sistem; kualitas data tag bergantung pada disiplin input EOS + validasi format/unik.
- Tidak ada alur transfer aset antar site; ERD inventaris menyusut (tanpa tabel transfer aset); mutasi inventory tetap untuk material (TRANSFER_IN/TRANSFER_OUT per ADR-025 untuk barang stok).
- RETURNED/DAMAGED sebagai status eksplisit menjelaskan aset yang kembali ke gudang; DISPOSED mengakhiri lifecycle.
- Foto registrasi wajib memberi buti visual kondisi awal; perubahan status rusak/hilang wajib foto + alasan + audit.
- Kolaborasi dengan gudang menjadi prasyarat: tag harus sudah tercetak/tertempel sebelum barang dikirim.

## References

ADR-022 (amended), ADR-014 (inventaris melekat site), ADR-025 (mutasi stok material), ADR-050 (audit), prd.md, erd.md.
