## Relevant Files

- `database/migrations/` - Migrasi skema untuk users, roles, sites, assignments, attendances, templates, reports, inventory, stock, audit log.
- `database/seeders/` - Seeder peran, akun Administrator awal, dan data master site contoh.
- `app/Models/` - Model Eloquent: User, Role, Site, SiteConnection, Assignment, Attendance, Template, SectionTemplate, DailyReport, ReportSection, ReportPhoto, InventoryItem, InventoryPhoto, MaterialStock, StockMovement, AuditLog.
- `app/Http/Middleware/` - Middleware RBAC dan pemeriksaan hak akses per peran.
- `app/Http/Controllers/` - Controller server-side untuk auth, absensi, laporan, inventory, dashboard, audit log, dan foto.
- `app/Http/Requests/` - Form Request untuk validasi server-side tiap aksi penting.
- `app/Services/` - Logika bisnis: alokasi nomor laporan, gate absen pulang, konversi zona waktu, penulisan audit log.
- `app/Policies/` - Policy otorisasi per model (laporan, inventory, foto, master data).
- `app/Exports/` - Kelas ekspor Excel dan PDF.
- `resources/js/pages/` - Halaman Inertia React: Login, Beranda EOS, Absensi, Riwayat Kehadiran, Daily Report, Inventory, Dashboard, Audit Log, Master Data.
- `resources/js/components/` - Komponen bersama: form section, indikator kelengkapan, empty state, upload foto, filter dashboard.
- `routes/web.php` - Definisi seluruh route server-side.
- `config/` - Konfigurasi session, filesystem privat, zona waktu, dan batas unggah.
- `tests/Feature/` - Test fitur untuk aturan bisnis kritikal dan RBAC.
- `tests/Unit/` - Test unit untuk service nomor laporan, konversi waktu, dan validasi foto.
- `Docs/` - Dokumentasi operasional: backup/restore, aturan foto, dan panduan peran.
- `Docs/PRD/ui-design.md` - Baseline layout launcher, pola halaman list/form/detail, pemetaan komponen shadcn, dan keputusan visual terkunci — WAJIB diikuti semua task UI.
- `resources/fonts/` - Font TTF internal (mis. DejaVuSans) untuk server-side watermark selfie — jangan andalkan font OS (FR-5b).

### Notes

- Aturan bisnis kritikal (absen ganda, gate Daily Report sebelum absen pulang, absen pulang ganda, alokasi nomor laporan, hak akses foto) wajib punya test otomatis.
- Semua waktu disimpan UTC di basis data; konversi ke zona waktu lokal sekolah dilakukan di lapisan aplikasi.
- Foto disimpan di storage privat dan hanya diakses melalui endpoint yang memeriksa peran lalu mencatat audit log.
- Validasi wajib di klien dan server; server adalah sumber kebenaran.
- Setiap daftar harus punya empty state dan error state yang informatif sesuai FR-26, FR-33, dan FR-45.

## Instructions for Completing Tasks

Setiap subtask yang sudah selesai harus ditandai dengan mengubah `- [ ]` menjadi `- [x]`. Jangan menandai subtask selesai sebelum test terkait lulus dan perilaku terverifikasi secara manual pada layar kecil. Kerjakan subtask secara berurutan di dalam satu parent task karena subtask berikutnya sering bergantung pada hasil subtask sebelumnya.

## Tasks

- [x] 0.0 Membuat feature branch
  - [x] 0.1 Buat branch baru dari `main` dengan nama yang mencerminkan modul PORTER yang dikerjakan.
  - [x] 0.2 Pastikan konfigurasi environment lokal (database, storage privat, mail) siap sebelum menulis kode.
  - [x] 0.3 Catat ringkasan ruang lingkup branch di deskripsi pull request agar reviewer memahami konteks.
  - [x] 0.4 Rujuk `Docs/PRD/ui-design.md` sebagai baseline layout/pola UI untuk seluruh task berikutnya; install komponen shadcn yang dibutuhkan (`table`, `calendar`, `progress`, `form`, `alert`, `timeline`) via `npx shadcn@latest add <name>`.
  - [x] 0.5 Tambahkan module nav di `LauncherHeader` sesuai ui-design.md §1–§3 (link modul per role; mobile → Sheet) dan seragamkan label tile dashboard ke English.

- [x] 1.0 Menyiapkan fondasi proyek: skema basis data, model, migrasi, seeder, dan konfigurasi zona waktu
  - [x] 1.1 Buat migrasi dan model untuk `roles`, `users`, `sites`, dan `site_connections` sesuai ERD.
  - [x] 1.2 Buat migrasi dan model untuk `assignments`, `attendances`, `templates`, `section_templates`, `daily_reports`, `report_sections`, dan `report_photos`.
  - [x] 1.3 Buat migrasi dan model untuk `inventory_items`, `inventory_photos`, `material_stocks`, `stock_movements`, dan `audit_logs`.
  - [x] 1.4 Tambahkan indeks pada kolom yang sering difilter (tanggal laporan, site_id, user_id, status) untuk mendukung 200+ site.
  - [x] 1.5 Buat seeder peran (EOS, Supervisi, HR, Administrator), akun Administrator awal, dan beberapa site contoh dengan zona waktu berbeda.
  - [x] 1.6 Buat helper konversi waktu UTC ke zona waktu lokal sekolah dan sebaliknya, lalu tulis test unit untuk helper tersebut.
  - [x] 1.7 Konfigurasikan disk storage privat untuk foto dan pastikan tidak ada file yang dapat diakses langsung dari direktori publik.

- [ ] 2.0 Membangun autentikasi, otorisasi berbasis peran (RBAC), dan manajemen akun pengguna
  - [ ] 2.1 Implementasikan login berbasis session cookie dengan pesan error generik "Email atau kata sandi salah" sesuai FR-4.
  - [ ] 2.2 Implementasikan penguncian akun sementara 15 menit setelah 5 kali gagal berturut-turut beserta test fiturnya.
  - [ ] 2.3 Implementasikan alur wajib ganti kata sandi pada login pertama dan setelah reset oleh Supervisi/Administrator.
  - [ ] 2.4 Implementasikan reset kata sandi via email terdaftar dan pastikan tidak ada pendaftaran mandiri.
  - [ ] 2.5 Buat middleware dan policy RBAC untuk 4 peran, dengan pemeriksaan hak akses di server pada setiap request.
  - [ ] 2.6 Buat halaman manajemen akun bagi Supervisi dan Administrator, termasuk pembuatan akun EOS dengan penempatan sekolah wajib.
  - [ ] 2.7 Tulis test fitur yang memverifikasi setiap peran hanya dapat mengakses fitur sesuai kewenangannya.

- [ ] 3.0 Mengelola master data site dan penempatan EOS beserta riwayatnya
  - [ ] 3.1 Buat CRUD master data site (nama, alamat, koordinat, zona waktu, provider utama/backup) khusus Administrator.
  - [ ] 3.2 Implementasikan penonaktifan site tanpa menghapus data historis sesuai FR-36.
  - [ ] 3.3 Implementasikan pembuatan penempatan EOS dengan validasi satu EOS satu penugasan aktif dan satu sekolah satu EOS aktif.
  - [ ] 3.4 Implementasikan pemindahan EOS dan pengakhiran penugasan dengan penyimpanan riwayat (tanggal mulai dan selesai).
  - [ ] 3.5 Catat setiap perubahan penempatan ke audit log beserta nilai sebelum dan sesudah.
  - [ ] 3.6 Tulis test fitur untuk aturan penempatan tunggal dan penyimpanan riwayat penugasan.

- [ ] 4.0 Mengimplementasikan absensi EOS (selfie + GPS) dengan aturan gate Daily Report
  - [ ] 4.1 Buat halaman absensi mobile-first dengan tombol besar, pengambilan selfie, dan pembacaan koordinat GPS.
  - [ ] 4.1a Implementasikan overlay panduan wajah (lingkaran) + deteksi ada-wajah via FaceDetector API (progressive enhancement): tombol jepret aktif saat wajah terdeteksi; tanpa API, jepret aktif setelah kamera stabil (FR-5a).
  - [ ] 4.1b Implementasikan server-side watermark pada selfie sebelum simpan (FR-5b): baris teks bawah foto berisi nama site + tanggal-jam lokal site + koordinat GPS + accuracy, dicap saat server menerima. Gunakan Laravel `Image` facade (Intervention v4, driver GD — sudah tersedia) + bundle font TTF internal (mis. DejaVuSans); jangan andalkan font OS. Tulis test fitur: file tersimpan berbeda dari original (watermark applied) & metadata teks sesuai data record.
  - [ ] 4.2 Tampilkan pesan "No internet connection. Attendance requires an internet connection." dan nonaktifkan tombol absen saat offline.
  - [ ] 4.3 Tampilkan panduan aktivasi izin saat izin kamera atau lokasi ditolak, dan hentikan alur absensi.
  - [ ] 4.4 Implementasikan absen masuk dengan validasi anti absen ganda per hari dan status "Checked In".
  - [ ] 4.5 Implementasikan absen pulang dengan penolakan jika belum check-in dan pesan "You haven't checked in today."
  - [ ] 4.6 Implementasikan gate absen pulang yang mengarahkan EOS melengkapi Daily Report bila laporan belum berstatus "Submitted".
  - [ ] 4.7 Implementasikan status "Completed" setelah absen pulang dan tolak percobaan absen pulang berikutnya pada hari yang sama.
  - [ ] 4.8 Tambahkan indikator loading, pencegahan kirim ganda, dan tombol "Try Again" yang mempertahankan selfie/GPS saat unggah gagal.
  - [ ] 4.9 Tambahkan validasi server-side: absen masuk dan absen pulang harus pada tanggal lokal sekolah yang sama; tolak absen pulang yang melewati tengah malam dengan pesan yang jelas.
  - [ ] 4.10 Simpan waktu absen dalam UTC dan tampilkan waktu lokal sekolah, lalu tulis test fitur untuk seluruh aturan absensi (absen ganda, gate laporan, pulang ganda, tanggal sama).
  - [ ] 4.11 Jadikan aplikasi PWA installable: manifest (nama, ikon, tema), splash screen, dan service worker untuk caching aset statis — tanpa fungsi offline untuk absensi/laporan.
  - [ ] 4.12 Buat halaman Riwayat Kehadiran: EOS melihat riwayat miliknya; Supervisi/Administrator/HR melihat riwayat per EOS/per site dengan filter periode, menampilkan jam lokal, status, lokasi, dan akses foto selfie sesuai FR-48/FR-12a, beserta test hak aksesnya.

- [ ] 5.0 Membangun modul Daily Report: template, pengisian, submit, revisi, dan reopen
  - [ ] 5.1 Buat CRUD template dan section template khusus Administrator dengan mekanisme versi.
  - [ ] 5.2 Simpan snapshot definisi template pada setiap laporan baru agar laporan lama tidak berubah saat template direvisi.
  - [ ] 5.3 Buat form Daily Report dengan lima section dan indikator kelengkapan visual per section.
  - [ ] 5.4 Isi otomatis section Info Umum (nomor laporan, tanggal lokal, nama sekolah, nama EOS) sesuai FR-15.
  - [ ] 5.5 Implementasikan validasi kondisional wajib penjelasan dan foto pada section Router & Firewall serta Infrastruktur & Lingkungan, dan validasi Konektivitas Dual-Link: traffic diisi untuk link utama dan backup, hasil speedtest terpisah per link, minimal 1 screenshot speedtest per link.
  - [ ] 5.6 Implementasikan validasi foto/lampiran (format, ukuran maksimal 10 MB, batas jumlah per section dan total) dengan pesan penolakan yang jelas.
  - [ ] 5.7 Implementasikan penyimpanan draf di server dan lanjutkan draf sebelum submit.
  - [ ] 5.8 Implementasikan submit dengan alokasi nomor `CMX.WR.YYYYMM.SEQUENCE` unik permanen dan konfirmasi "Laporan berhasil dikirim".
  - [ ] 5.9 Pertahankan seluruh isian dan foto saat submit gagal karena koneksi terputus, dan sediakan tombol coba lagi.
  - [ ] 5.10 Implementasikan revisi laporan sendiri pada hari yang sama dengan penambahan hitungan revisi tanpa mengubah nomor laporan.
  - [ ] 5.11 Implementasikan reopen oleh Supervisi/Administrator dengan alasan wajib dan notifikasi in-app ke EOS.
  - [ ] 5.11a Buat indikator notifikasi in-app di beranda EOS (daftar notifikasi belum dibaca: laporan dibuka ulang), dengan penanda sudah dibaca — polling sederhana, tanpa push.
  - [ ] 5.12 Buat daftar laporan dengan filter tanggal, sekolah, EOS, dan status, serta empty state "No reports found for this filter."
  - [ ] 5.13 Tulis test fitur untuk alokasi nomor laporan, validasi kelengkapan section, revisi, dan reopen.

- [ ] 6.0 Membangun modul Inventory per site dan stok material habis pakai
  - [ ] 6.1 Buat form pencatatan barang datang (nama, kategori, jumlah, tanggal terima, minimal 1 foto) yang terikat pada sekolah.
  - [ ] 6.2 Implementasikan perubahan status barang (`dipakai`, `cadangan`, `rusak`, `dikembalikan`, `hilang`) dengan alasan wajib untuk `rusak` dan `hilang`.
  - [ ] 6.3 Catat setiap perubahan status inventory ke audit log beserta nilai sebelum dan sesudah.
  - [ ] 6.4 Implementasikan pencatatan stok material habis pakai dengan transaksi barang masuk, keluar, dan pemakaian; tolak transaksi yang membuat saldo stok negatif dengan pesan yang menyebut saldo saat ini.
  - [ ] 6.5 Pastikan inventory tetap milik sekolah asal saat EOS dipindahkan, dan sediakan koreksi inventory oleh Supervisi/Administrator.
  - [ ] 6.6 Buat daftar inventory dengan empty state "No items recorded at this site yet."
  - [ ] 6.7 Tulis test fitur untuk kepemilikan inventory per sekolah, validasi alasan status, penolakan stok negatif, dan pencatatan pergerakan stok.

- [ ] 7.0 Membangun dashboard KPI, ekspor Excel/PDF, audit log, dan pengelolaan akses foto
  - [ ] 7.1 Buat dashboard dengan filter periode, sekolah, dan EOS yang dapat dikombinasikan serta dapat direset.
  - [ ] 7.2 Implementasikan KPI kehadiran, kelengkapan laporan, kesehatan jaringan, kondisi lingkungan, anomali, dan inventory sesuai FR-38 sampai FR-43.
  - [ ] 7.3 Tampilkan indikator loading dan empty state "No data for this filter." saat dashboard tidak memiliki data.
  - [ ] 7.4 Implementasikan ekspor Excel dan PDF sesuai filter aktif dengan kop sederhana tanpa tanda tangan digital.
  - [ ] 7.5 Buat halaman audit log dengan filter periode, pelaku, dan jenis aksi, serta pastikan audit log bersifat append-only.
  - [ ] 7.6 Implementasikan endpoint akses foto yang memeriksa peran sesuai FR-48 dan mencatat setiap akses ke audit log.
  - [ ] 7.7 Tulis test fitur untuk hak akses foto per peran, pencatatan akses foto, dan filter audit log.
  - [ ] 7.8 Tulis dokumentasi singkat di `Docs/` untuk aturan foto, panduan peran, dan prosedur backup/restore sebelum go-live.