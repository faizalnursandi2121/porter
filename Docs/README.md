# Docs — PORTER (Portal Operasional Terpadu)

Dokumentasi produk PORTER: aplikasi pemantauan operasional harian Engineer On Site (EOS) di site Sekolah Rakyat (±200 site, WIB/WITA/WIT) untuk Comtronics.

## Struktur

```
Docs/
├── README.md            ← file ini
├── PRD/                 ← SATU-SATUNYA SUMBER KEBENARAN AKTIF
│   └── prd-porter-portal-operasional-terpadu.md
└── legacy/              ← ARSIP REFERENSI — bukan kebenaran aktif, jangan dikutip sebagai requirement
    ├── prd.md           (PRD baseline lama, 5 role)
    ├── adr/             (53 ADR keputusan desain lama)
    ├── architecture.md, erd.md, data-dictionary.md, api-contract.md,
    ├── security.md, ux.md, ui-spec.md, test-strategy.md, backlog.md,
    └── runbook & checklist (deployment, operations, backup-restore, delivery, release)
```

## Dokumen Aktif

**[`PRD/prd-porter-portal-operasional-terpadu.md`](PRD/prd-porter-portal-operasional-terpadu.md)** — PRD final hasil konfirmasi pemilik produk:

- 55 FR, 0 pertanyaan terbuka; 15 keputusan produk terkunci di bagian "Keputusan Produk (Hasil Konfirmasi)"
- 4 role: **EOS, Supervisi, HR, Administrator**
- Keputusan kunci: online-only (tanpa draft offline), Daily Report wajib sebelum absen pulang, EOS revisi sendiri di hari yang sama + Supervisi reopen, nomor laporan `CMX.WR.YYYYMM.SEQUENCE`, inventory tanpa ambang batas, 1 EOS = 1 site aktif
- Arsitektur: satu aplikasi full-stack **Laravel 13 + Inertia + React** (mengikuti project existing), PostgreSQL, deployment **GitHub → Dokploy**
- Kebijakan akun mengikuti implementasi starter kit yang sudah terverifikasi: password policy 12 karakter kompleks, lockout 5x/15 menit, reset via email (Fortify)

## Status Dokumen Lama (legacy/)

Direktori `legacy/` dipindahkan pada 2026-10-07 karena baseline produk berubah. **Semua file di dalamnya sudah digantikan keputusan di PRD baru**, terutama:

| Perubahan | Lama (legacy) | Baru (PRD aktif) |
|---|---|---|
| Role | 5 role (Super Admin, Manager, Supervisor, HR, EOS) | 4 role (Administrator, Supervisi, HR, EOS) |
| Revisi laporan | Reopen Supervisor, maks 7 hari | EOS revisi mandiri hari yang sama; Supervisi reopen tanpa batas waktu |
| Stack backend | Go + Gin + sqlc, Redis, outbox worker | Laravel 13 + Inertia full-stack, queue database driver |
| Deployment | Docker Compose + 3 environment (dev/staging/prod) | GitHub → Dokploy, tanpa staging terpisah |
| Absensi | Geofence gerbang, window jam kerja, job ABSENT | Bukti kehadiran saja; tanpa geofence gerbang, tanpa batas jam |
| ADR 029/044 | Quarantine + malware scan → dicabut (ADR-044) | Validasi sinkron tanpa malware scan |

### Cara memakai legacy/

- **Boleh**: membaca sebagai bahan mentah saat menulis dokumen turunan baru (arsitektur, ERD, security, runbook, test strategy) — banyak detail operasional matang yang belum tercover PRD (aturan attachment, keamanan session, backup/restore, mutasi inventory).
- **Jangan**: mengutipnya sebagai requirement aktif atau melanjutkan implementasi dari file di dalamnya. Jika isi legacy bertentangan dengan PRD aktif, **PRD yang menang**.

## Dokumen Turunan (direncanakan)

Belum ada. Rencananya di-generate dari PRD aktif saat implementasi dimulai:

| Dokumen | Kapan dibutuhkan | Bahan mentah dari legacy |
|---|---|---|
| `architecture.md` | Sebelum sprint pertama | legacy/architecture.md (domain modules, transaction boundaries), legacy/adr/001 |
| `erd.md` + `data-dictionary.md` | Bersamaan dengan migration pertama | legacy/erd.md, legacy/data-dictionary.md, legacy/adr/022-026 |
| `security.md` | Sebelum fitur auth disentuh | legacy/security.md, legacy/adr/006, 015, 020, 032 |
| `test-strategy.md` | Sebelum test pertama ditulis | legacy/test-strategy.md |
| `operations-runbook.md` + `backup-restore-drill.md` | Sebelum go-live | legacy/operations-runbook.md, legacy/backup-restore-drill.md, legacy/adr/031 |

> Catatan: hanya ambil bagian dari legacy yang masih relevan dengan PRD aktif; sisanya (geofence gerbang, window jam kerja, outbox worker, Redis, multi-environment) sudah tidak berlaku.
