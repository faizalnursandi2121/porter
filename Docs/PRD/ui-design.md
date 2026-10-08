# UI Design — PORTER (Portal Operasional Terpadu)

**Status:** Baseline UI untuk eksekusi `tasks-task-list-baru.md`
**Stack UI:** Inertia v3 + React 19 + Tailwind 4 + shadcn/ui (`resources/js/components/ui/`)
**Copy UI:** Bahasa Inggris (translation string `lang/en/` + `__()`); komunikasi produk Bahasa Indonesia

---

## 1. Prinsip Layout

1. **Satu pola layout untuk semua role: App Launcher.** Dashboard = kumpulan tile modul (sudah terimplementasi di `resources/js/pages/dashboard.tsx`). Tidak ada sidebar untuk role mana pun.
2. **Header persisten; module nav = launcher tiles.** `LauncherHeader` tetap seperti existing (logo, command palette Ctrl+K, notifikasi, theme switch, avatar menu) — TANPA baris nav modul. Nav modul dirender oleh dashboard sebagai tiles ber-ikon (existing pattern `resources/js/pages/dashboard.tsx`); link per modul sesuai role (lihat §3). (Amended 2026-10-07: subtask 0.5 awalnya menyuruh menambah baris nav di header; keputusan pemilik produk — pattern launcher tiles yang sudah ada dipertahankan, header tidak diubah.)
3. **Dalam modul:** breadcrumb (`Breadcrumb`) + tombol Back. Tidak ada nav samping. Pindah modul = nav header atau Ctrl+K.
4. **Mobile-first.** Breakpoint dasar 1 kolom; grid tile 2 kolom di HP, 3–4 di desktop. Target sentuh ≥ 44px.
5. **Dark mode** via `useAppearance` (sudah ada) — semua warna memakai token Tailwind (`bg-background`, `text-muted-foreground`, dst.), tidak ada warna hardcode.
6. **Semua daftar punya empty state** (string sesuai FR) dan **error state** dengan langkah berikutnya.
7. **Status badge konsisten** (shadcn `Badge` variant): `Submitted` = default, `Draft` = secondary, `Needs Revision` = destructive, `Completed` = outline.

## 2. Anatomi Halaman

### 2.1 Dashboard (Home / Launcher)

```
LauncherHeader (logo · ⊕Ctrl+K · 🔔 · theme · avatar)
─────────────────────────────────────────────
Good Afternoon, {name}
{Weekday, D Month YYYY}

[tile] [tile] [tile]      ← Card: icon + title, klik → modul
[tile] [tile]             ← hanya tile sesuai role
```

### 2.2 Halaman List (pola tunggal untuk semua daftar)

```
Header: judul + aksi utama (mis. "Submit Report")
Filter bar: Select/Popover (periode, site, EOS, status) + tombol reset
Table (shadcn Table) atau Card-list di mobile
Pagination / load more
Empty state → teks FR terkait
```

Pemetaan shadcn: `Table` (desktop) / `Card` list (mobile), `Select`, `Popover`+`Calendar` untuk periode, `Badge` status, `Skeleton` loading.

### 2.3 Halaman Form (absensi, daily report, inventory, master data)

```
Header: judul + breadcrumb
Form sections (Card per section, judul + indikator kelengkapan ✓/✗)
Input: Input/Textarea/Select sesuai tipe; foto → Card upload area + preview thumbnail
Sticky footer action bar (mobile): tombol utama (Save Draft / Submit)
Validasi error inline (bawah field) + ringkasan error di atas form
```

Pemetaan shadcn: `Card`, `Input`, `Textarea`, `Label`, `Select`, `Checkbox`, `Alert` (ringkasan error), `Progress` (kelengkapan section), `Dialog` (konfirmasi submit/ubah status), `Sonner` (toast sukses).

### 2.4 Halaman Detail (laporan, aset, attendance record)

```
Header: judul + status Badge + aksi kontekstual (Revise / Reopen / Change Status)
Grid info: definition list (label kiri, nilai kanan)
Foto: thumbnail grid (aspectsquare), klik → Dialog preview besar + metadata + tombol download (teraudit)
Audit trail: Collapsible/Timeline sederhana di bawah
```

## 3. Module Nav per Role

Sumber: tile `dashboard.tsx` + task list. Nav menampilkan hanya modul milik role:

| Role | Nav modul |
|---|---|
| EOS | Attendance · Daily Report · Inventory |
| Supervisi | Attendance · Daily Reports · Inventory · Master Data |
| HR | Attendance |
| Administrator | Attendance · Daily Reports · Inventory · Master Data · Users · Templates · Audit Log |

Catatan: label tile现有 memakai bahasa campuran (`Presensi`, `Laporan Harian`) — saat implementasi nav, seragamkan ke English (`Attendance`, `Daily Report`, `Inventory`, `Master Data`, `Users`, `Templates`, `Audit Log`, `Analytics`) karena copy UI English. Tile dashboard ikut diseragamkan (subtask 4.12/4.1).

## 4. Halaman per Role (peta → task list)

| Modul | Halaman | Role | Task |
|---|---|---|---|
| Auth | Login, forgot password, reset, force-change-password | semua | 2.1–2.4 |
| Account | Akun EOS baru (form + penempatan wajib), daftar user | Supervisi/Admin | 2.6 |
| Attendance | Check-in/check-out (kamera+GPS), Riwayat Kehadiran (filter per EOS/site/periode) | EOS; riwayat +Supervisi/HR/Admin | 4.1–4.10, 4.12 |
| Daily Report | Form 5 section + draft, daftar laporan (filter), detail + revise/reopen | EOS; daftar+reopen +Supervisi/Admin | 5.3–5.12 |
| Template | CRUD template + section (versi) | Administrator | 5.1–5.2 |
| Inventory | Form barang datang, daftar aset (status+alasan), stok material + mutasi | EOS input; koreksi +Supervisi/Admin | 6.1–6.6 |
| Master Data | CRUD site + koneksi internet | Administrator (lihat: Supervisi) | 3.1–3.2 |
| Assignment | Buat/akhiri/pindah penugasan + riwayat | Supervisi/Admin | 3.3–3.4 |
| Dashboard KPI | KPI 6 kategori + filter + export Excel/PDF | Supervisi/Admin (HR: kehadiran) | 7.1–7.4 |
| Audit Log | Daftar + filter periode/pelaku/aksi | Administrator | 7.5 |
| Notifikasi | Indikator + daftar (belum dibaca) | EOS (report reopened) | 5.11a |

## 5. Pola Khusus

### 5.1 Absensi (kamera + GPS) — halaman paling kritis, mobile-only feel

- Layar penuh preview kamera dengan overlay lingkaran ("posisikan wajah di dalam lingkaran").
- **Face guide aktif**: bila `FaceDetector` API tersedia, indikator overlay berubah warna (mis. merah → hijau) saat wajah terdeteksi di dalam lingkaran; tombol jepret baru aktif saat terdeteksi. Tanpa API → overlay tetap tampil, jepret aktif setelah kamera stabil.
- Tombol jepret besar di bawah; setelah jepret → preview + konfirmasi → submit (selfie + GPS terkirim bersama).
- GPS: tombol indikator lokasi (menampilkan akurasi); error izin → halaman panduan (FR-10).
- Offline → banner atas (FR-6) + tombol disabled.
- Setelah check-in sukses → state card "Checked In at {time}" + CTA "Fill Daily Report" (gate FR-9a sudah diarahkan sistem, tapi UI mempermudah).
- Clock-out: pola sama; bila gate terpicu → redirect ke form laporan dengan banner penjelas, bukan error.
- **Watermark server-side (FR-5b)**: foto yang tersimpan & tampil di riwayat membekas baris teks bawah: `{site name} · {dd/mm/YYYY HH:mm} · {lat, lng} (±{accuracy} m)` — dicap server saat terima; bukan di client.

### 5.2 Upload foto (dipakai di absensi, report, inventory)

- Komponen bersama `PhotoUploader`: area drop/pilih file (Card dashed), preview thumbnail, hapus sebelum submit, counter "3 / 5".
- Validasi client (format/size) + tampilkan error server verbatim (FR-20a).
- Thumbnail hasil server (derivatif) ditampilkan bila tersedia; selama proses → Skeleton.

### 5.3 Form Daily Report

- Stepper/accordion 5 section (Info Umum collapsed read-only; 4 section operasional) dengan indikator kelengkapan per section (Progress ✓/✗).
- Item enum → Select; item angka → Input number; item keterangan wajib muncul otomatis saat pilihan memicu (mis. `MINOR/MAJOR`).
- Evidence section: `PhotoUploader` (min 1, max 5) + counter.
- Sticky bottom bar: `Save Draft` (secondary) + `Submit` (primary, disabled sampai lengkap) — mobile.

### 5.4 Dashboard KPI

- Baris filter sticky di atas (Popover periode, Select site, Select EOS, reset).
- Grid kartu KPI; tiap kartu: judul, angka utama, chart kecil (line/bar) — chart library bebas asal react-native-friendly dan dark-mode token; preferensi: `recharts`.
- Tabel pendukung di bawah (mis. daftar site belum lapor hari ini).
- Tombol Export (DropdownMenu: Excel / PDF) → mengikuti filter aktif.

## 6. Konvensi Teknis UI

- **Ikon**: `lucide-react` saja (sudah terpakai).
- **Tanggal/jam**: tampil dalam zona waktu site + label zona (`14:05 WIB`) — format konsisten `Intl.DateTimeFormat('en-GB', …)`.
- **Angka**: locale `en-GB` untuk konsistensi desimal.
- **Halaman Inertia** di `resources/js/pages/{module}/…`; komponen bersama di `resources/js/components/` — ikuti struktur starter kit, jangan buat folder baru di luar konvensi tanpa persetujuan.
- **Komponen shadcn yang belum ada** (mis. `Calendar`, `Progress`, `Table`, `Form`, `Alert`, `Timeline`): install via `npx shadcn@latest add <name>` — jangan tulis manual.
- **Route**: pakai Wayfinder (`@/routes`, `@/actions`) bila tersedia; else helper `route()`.

## 7. Keputusan Visual Terkunci (jangan diubah agent)

1. Launcher untuk semua role; tanpa sidebar.
2. Nav modul di header; mobile → Sheet.
3. Copy UI English; istilah produk terkunci (EOS, Supervisi, HR, Administrator).
4. Dark mode via token Tailwind; tanpa warna hardcode.
5. Status badge: Submitted=default, Draft=secondary, Needs Revision=destructive, Completed=outline.
6. Mobile-first; grid tile 2 kolom HP.
7. Semua list punya empty state verbatim dari FR.
