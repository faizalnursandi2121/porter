# Operations Runbook

## 1. Purpose

Runbook ini menjelaskan prosedur operasional aplikasi, bukan implementasi kode. Semua action penting memakai role, alasan, dan audit trail (spatie/laravel-activitylog) sesuai PRD/security.

## 2. User and Role Operations

### Create/disable account

- Super Admin creates user, assigns least-privilege role (5 role fixed: SUPER_ADMIN, MANAGER, SUPERVISOR, HR, EOS; single role per user), and records employee code/email.
- Initial password follows secure reset/bootstrap flow (reset password oleh Super Admin men-set `must_change_password`; tidak pernah dikirim/di-disimpan plaintext di luar channel yang disetujui).
- Disable account revokes active sessions (session driver database) without deleting business history.
- Semua aksi user/role (created, role changed, disabled, reset, lockout) tercatat di activity_log.

### EOS assignment change

- Supervisor ends prior active assignment with reason.
- Supervisor creates one active assignment to target site.
- Pastikan timezone (IANA WIB/WITA/WIT) dan koordinat/radius site terisi benar SEBELUM EOS mulai absen — keduanya dipakai untuk penampilan waktu lokal dan informasi jarak (bukan gerbang); tidak ada verifikasi kebijakan/policy lain.
- Inventory remains attached to site; no asset transfer occurs due solely to EOS change.
- Transisi assignment diaudit.

## 3. Kegagalan Absen (catatan)

Tidak ada Attendance Request dan tidak ada recovery kehadiran di sistem. Kegagalan absen hari itu (device/kamera/GPS/jaringan tidak berhasil sampai submit) tidak dapat direcover aplikasi — record hari tsb kosong dan terlihat kosong di laporan; disiplin/kehadiran ditangani absensi vendor EOS (ADR-046). Jangan meminta EOS mengirim selfie/lokasi via chat pribadi sebagai pengganti bukti.

## 4. Daily Report Operations

- EOS submits report sebelum clock-out (clock-out ter-gate pada report `SUBMITTED` + required evidence `AVAILABLE`).
- Submitted report is read-only.
- Authorized Supervisor/Super Admin may reopen with explicit reason (maks 7 hari kalender); resubmit menambah revision tanpa mengubah nomor.
- EOS corrects reopened report and resubmits; history is retained; tiap transisi diaudit.
- AP Cloud documentation requires evidence attachment.

## 5. Inventory and Finding

- EOS meregistrasi aset saat barang datang dari gudang Comtronics (tag + SN input apa adanya; foto wajib; sistem validasi format regex CMX + unik).
- EOS observes inventory and creates Inventory Finding with evidence; EOS does not directly change asset/stock.
- Perubahan status aset = transaksi beralasan + audit + foto bila rusak/hilang; barang tidak berpindah antar site (rusak → dikembalikan ke gudang).

## 6. Attachment Handling

- Evidence remains private (`storage/app`, di luar public/); access only through controller terkontrol + audit (sensitive access).
- Original is preserved; preview derivative is not evidence replacement.
- Rejected/invalid attachment is not used as final evidence; rejected tidak tersedia ke user biasa + audit event + notifikasi in-app.
- Do not copy sensitive selfie/evidence into public folder, chat, or unmanaged storage.
- Tidak ada malware scan (ClamAV dihapus — ADR-044): validasi berjalan SINKRON di request (tipe/format/size/decode + hash SHA-256); file valid langsung `AVAILABLE` (ADR-053). Rejected ditolak saat request — tidak ada state QUARANTINED/PROCESSING.
- Thumbnail/preview WebP dihasilkan queued job di worker container (bukan operasional manual; job gagal tercatat di `failed_jobs`).

## 7. Export Operations

- Export (Excel/PDF/CSV) berjalan sinkron streamed pada controller; audit event ditulis sebelum stream dimulai — tidak ada job export yang perlu dipantau operator.
- Preset per user (kolom + filter periode/site/status/EOS) disimpan dan dipakai ulang; penghapusan preset oleh pemiliknya tidak mengubah histori audit.
- Privacy visibility di-enforce saat export: Manager tanpa raw selfie/precise GPS; pelanggaran scope ditolak.

## 8. Backup, Recovery, dan Restore Test (ADR-031)

- `pg_dump` PostgreSQL backup harian (scheduler container), retention 30 hari.
- Attachment volume (`storage/app`) di-rsync harian, retention 30 hari.
- RPO maksimum 24 jam; RTO maksimum 8 jam.
- Tidak ada Redis di runtime; seluruh state di PostgreSQL — recovery hanya PostgreSQL + attachment volume.
- Restore test bulanan di staging (lihat `backup-restore-drill.md`); catat date, operator, duration, dan outcome.
- Bila restore test gagal atau RPO/RTO terlampaui, buat incident dan follow-up sebelum release berikutnya.

## 9. Monitoring dan Alert Minimal

```text
- Backup job failure (PostgreSQL pg_dump / attachment rsync).
- Health endpoint /up tidak 200.
- Disk usage > 80%.
- Worker backlog meningkat / repeated worker job failure (failed_jobs).
- Scheduled task scheduler gagal berjalan.
```

Tiap alert dibesarkan ke operator on-call/owner lingkungan terkait, dicatat waktunya, dan ditindak sesuai incident procedure.

## 10. Audit (activity_log)

- Audit aplikasi memakai `spatie/laravel-activitylog` (tabel `activity_log`), dipasang eksplisit di titik kritis: login sukses/gagal/lockout, reset/disable user, role change, sensitive access (selfie/GPS/download attachment), master change (site/koordinat/timezone/checklist publish), export, transisi report, mutasi inventaris.
- Super Admin dapat membaca audit log; audit tidak menyimpan password/secret/session token raw; properties disaring.
- Audit event untuk mutasi kritis ditulis pada transaksi yang sama dengan perubahan bisnis.

## 11. Incident Procedure (Ringkas)

```text
1. Catat incident time, reporter, symptom, dan immediate containment.
2. Stabilkan: rollback ke image sebelumnya bila deploy terkait (lihat deployment-runbook.md).
3. Escalate account/security issue ke Super Admin; preserve activity_log/log/evidence.
4. Bila restore diperlukan, ikuti deployment-runbook.md hanya melalui approved incident procedure.
5. Document final action, impact, recovery result, dan preventive follow-up.
```

## 12. Pilot dan Production Gate

- Pilot gate: staging wajib deployed dan verified sebelum pilot dan sebelum production release.
- Tidak ada ClamAV/EICAR gate (dihapus — ADR-044); attachment pipeline hanya validasi sinkron tipe/size/decode + SHA-256 (ADR-053).
- Production go-live mengikuti checklist di `release-checklist.md` dan deployment runbook production gate.
