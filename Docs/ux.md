# UX Specification — PORTER (Portal Operasional Terpadu Sekolah Rakyat)

**Status:** Baseline MVP  
**Dokumen:** UX Specification  
**Produk:** PORTER — Portal Operasional Terpadu Sekolah Rakyat  
**Platform:** Responsive web application dan Progressive Web App (PWA)  
**Arsitektur:** Laravel 13 + Inertia v3 + React 19 (server-side routing, halaman Inertia; tidak ada REST API layer terpisah)  
**Artefak desain lanjutan:** Wireframe/prototype dibuat setelah dokumen ini disetujui.

## 1. Tujuan dan Batas UX

Dokumen ini mendefinisikan pengalaman pengguna untuk aplikasi operasional Engineer On Site (EOS) Sekolah Rakyat. UX harus mendukung proses operasional yang dapat diaudit, cepat digunakan di lapangan, aman terhadap kesalahan pengguna, dan konsisten dengan PRD, ERD, serta tech stack yang telah ditetapkan.

Fokus UX:

- EOS dapat menyelesaikan check-in, pemeriksaan harian, Daily Report, dan clock-out secara jelas melalui ponsel/PWA.
- Supervisor, Manager, HR, dan Super Admin dapat melihat data sesuai kewenangan tanpa salah mengartikan status, waktu, atau evidence.
- Sistem membedakan secara eksplisit antara status lokal/perangkat, status tersimpan di server, dan status resmi yang telah tervalidasi.
- Perubahan data kritis dapat ditelusuri melalui histori dan audit tanpa membuka akses edit yang tidak perlu.

Absensi pada sistem ini adalah **bukti kehadiran**, bukan alat disiplin: disiplin/potongan kehadiran ditangani oleh absensi vendor EOS. Konsekuensinya, tidak ada window jam, keterlambatan, kalender kerja, geofence gate, ataupun Pengajuan Absensi (Attendance Request) dalam UX; yang ada adalah alur bukti kehadiran yang cepat dan andal di lapangan (lihat §6).

Bukan bagian dari dokumen ini:

- Keputusan visual pixel-perfect: warna, typography, icon, dan layout final.
- Pemilihan template UI atau component library.
- Implementasi komponen React, definisi route Laravel, atau detail SQL schema.
- Native mobile app.

## 2. Prinsip UX Enterprise

### 2.1 Server-authoritative, bukan device-authoritative

Waktu absensi resmi (server timestamp UTC), keunikan record absensi, eligibilitas clock-out, nomor Daily Report, dan permission ditentukan backend. UI menyampaikan hasil keputusan server secara transparan; UI tidak boleh menyatakan proses berhasil sebelum server mengonfirmasi. Jarak GPS ke site dihitung server (Haversine) dan disimpan sebagai informasi — bukan gerbang validasi.

### 2.2 Status harus eksplisit

Setiap proses kritis memiliki state yang dapat dipahami. Sistem tidak boleh memakai label ambigu seperti “tersimpan” tanpa membedakan:

```text
Draft tersimpan di server
Upload sedang berjalan
Siap disubmit
Submitted / resmi
Gagal dikirim atau belum terkonfirmasi
```

### 2.3 Evidence-first untuk tindakan operasional

Absensi memerlukan lokasi dan selfie. Daily Report membutuhkan checklist dan bukti yang dipersyaratkan. Inventory Finding membutuhkan deskripsi dan bukti lampiran. UX memvalidasi kelengkapan sebelum submit serta mengarahkan pengguna langsung ke item yang belum valid.

### 2.4 Least privilege dan context-aware

Menu, tindakan, data, evidence, dan ekspor hanya tampil jika relevan terhadap role serta scope pengguna. Penyembunyian menu hanya meningkatkan kemudahan; backend (policy + middleware) tetap menjadi security boundary.

### 2.5 Mobile-first untuk EOS, desktop-first untuk backoffice

EOS bekerja dominan melalui PWA pada ponsel; action utama harus dapat diselesaikan dengan satu tangan, koneksi tidak selalu stabil, dan form harus tahan gangguan. Backoffice memakai tabel, filter, detail, histori, dan perbandingan data yang lebih efektif pada desktop.

### 2.6 Recovery over blame

Jika GPS buruk, kamera ditolak, upload gagal, atau koneksi terputus, sistem memberi langkah pemulihan yang jelas. Sistem tidak melabeli pengguna melakukan kecurangan; jarak GPS dan selfie hanyalah bukti kehadiran yang direkam apa adanya.

### 2.7 Auditability without clutter

Data kritis menampilkan ringkasan histori yang relevan bagi role berwenang. Audit detail tersedia pada halaman/detail khusus, bukan mengganggu alur EOS sehari-hari.

## 3. Persona, Role, dan Konteks

| Role | Perangkat utama | Tujuan utama | Batas UX utama |
|---|---|---|---|
| Engineer On Site (EOS) | Ponsel/PWA | Catat bukti kehadiran, isi report, registrasi/kelola aset, isi stok, buat temuan | Hanya site assignment aktif dan data sendiri |
| Supervisor | Desktop, responsif mobile | Mengawasi semua site, review & reopen Daily Report, kelola assignment/master/inventaris, review finding | Seluruh site aktif; reopen report maks 7 hari kalender |
| Manager | Desktop | Melihat bukti kehadiran, tren, dan drill-down operasional | Fokus baca/analitik dan ekspor (tanpa raw selfie/GPS presisi) |
| Human Resources | Desktop | Melihat data kehadiran (bukti kehadiran, bukan disiplin vendor) | Hanya domain attendance yang diizinkan |
| Super Admin | Desktop | Governance sistem, user/role, site/master, checklist publish, audit | Seluruh data untuk administrasi/troubleshooting; perubahan berisiko harus teraudit |

## 4. Global Interaction Standards

### 4.1 Timezone dan waktu server

- Semua halaman operasional menampilkan timezone site secara eksplisit: `WIB`, `WITA`, atau `WIT`.
- Absensi dan Daily Report menampilkan waktu yang diterima/diputuskan server (canonical UTC, ditampilkan dalam waktu lokal site), bukan hanya waktu perangkat.
- Saat data lintas-site ditampilkan, waktu utama adalah waktu lokal site beserta label zona; UTC tersedia sebagai detail audit bila relevan.
- Filter tanggal pada dashboard memakai tanggal operasional site dan menjelaskan timezone/filter scope.
- Pesan waktu bersifat operasional dan faktual, contoh: `Check-in tercatat pukul 08.12 WIT`, bukan penilaian tepat waktu/terlambat (tidak ada kategori keterlambatan dalam sistem ini).

### 4.2 Status, loading, dan response

- Button action utama menjadi disabled selama request kritis berjalan untuk mencegah double submit; server juga menolak submit ganda lewat unique constraint (satu record per EOS + site + tanggal lokal).
- Setelah timeout, status harus `Belum dikonfirmasi server`, bukan berhasil/gagal final tanpa bukti.
- Request penting menggunakan retry aman/idempotent dari sisi sistem.
- Gunakan loading skeleton untuk halaman/listing dan progress yang jelas untuk upload/form submission.
- Error harus berisi apa yang terjadi, dampaknya, serta tindakan berikutnya.
- Validasi form dilakukan oleh FormRequest server; Inertia error bag dikembalikan per field dan UI menampilkannya inline terhubung ke input terkait.

### 4.3 Permission states

UI membedakan dengan jelas:

```text
Tidak ada data       : Belum ada record pada filter/periode ini.
Akses ditolak        : Pengguna tidak mempunyai izin melihat resource.
Data tidak ditemukan : Resource telah dihapus/void/tidak tersedia.
Gagal memuat         : Gangguan jaringan/server; tampilkan retry.
```

### 4.4 Attachment experience

- Semua attachment memperlihatkan tipe, nama, ukuran, status lifecycle, uploader, dan waktu upload.
- Upload mempunyai progress, cancel/retry per file, serta kesalahan yang spesifik.
- Evidence wajib diberi indikator `Wajib` dan tidak boleh hanya disembunyikan dalam validasi submit akhir.
- Preview/download hanya tersedia kepada role yang diizinkan; akses sensitive evidence tercatat sebagai audit event.
- File sensitif seperti selfie dan koordinat diperlakukan sebagai evidence operasional; tidak ditampilkan lebih luas dari kebutuhan role.
- Selfie absensi diambil langsung melalui kamera depan; UI normal tidak menawarkan pilihan unggah dari galeri.
- Validasi file (magic byte, size, hash SHA-256, decode-safe) dilakukan sinkron saat request upload; hasil validasi langsung final — lihat lifecycle di bawah.
- Format yang didukung: JPG, PNG, WebP, HEIC/HEIF, PDF; maksimum 10 MB per file.

#### Status lifecycle attachment

Validasi berjalan sinkron di server saat request upload, sehingga hasil upload langsung final. Thumbnail/preview WebP (2048px/480px) dihasilkan oleh queued job; ketersediaan thumbnail tidak memengaruhi status berkas.

| Status | Arti bagi pengguna | Perilaku UI |
|---|---|---|
| `AVAILABLE` | Berkas lolos validasi server dan siap dipakai | Bisa dipreview/diunduh oleh role berwenang; menghitung sebagai evidence |
| `REJECTED` | Berkas tidak dapat diproses | Pesan penyebab umum (format tidak didukung, ukuran melebihi 10 MB, berkas rusak); tombol unggah ulang |

Ketentuan copy status:

- Hanya status `AVAILABLE` yang memenuhi syarat evidence untuk submit; UI menjelaskan hal ini di review sebelum submit.
- Pesan untuk `REJECTED` tidak memaparkan detail teknis pipeline (magic byte, hash) kepada pengguna akhir.

#### Kesalahan upload

- Format tidak didukung: `Format berkas tidak didukung. Gunakan JPG, PNG, WebP, HEIC/HEIF, atau PDF.` PDF tidak ditawarkan untuk selfie dan mutasi inventaris.
- Ukuran melebihi batas: `Ukuran berkas melebihi batas 10 MB. Kompres atau ganti berkas.` Validasi ukuran/format dilakukan client sebelum upload agar feedback cepat, tetapi keputusan akhir tetap di server.
- Kuota evidence terlampaui: `Maksimal 5 lampiran pada bagian ini` atau `Maksimal 10 lampiran pada satu Daily Report`.

### 4.5 Confirmation dan destructive action

- Submit Daily Report memakai halaman review sebelum aksi final.
- Resolve/close/reject Inventory Finding, reopen report, dan reversal mutasi stok mewajibkan alasan sesuai action.
- Perubahan master site (koordinat, timezone), checklist publish, role, atau assignment menggunakan confirmation yang menyebut dampak.
- Aset/stok tidak dihapus langsung; gunakan status, void, reversal, atau mutasi dengan alasan.

### 4.6 Izin kamera dan lokasi (permission notice)

Sebelum check-in/clock-out, aplikasi menampilkan penjelasan mengapa izin diperlukan, sebelum prompt permission browser muncul:

```text
Untuk mencatat kehadiran, aplikasi memerlukan:
- Izin lokasi: merekam posisi Anda sebagai bagian dari bukti kehadiran.
- Izin kamera: mengambil selfie sebagai bukti kehadiran.

Lokasi dan selfie hanya digunakan untuk absensi dan hanya dapat dilihat
oleh pihak yang berwenang meninjau kehadiran. Data tidak dibagikan ke pihak lain.
```

Alur ketika izin ditolak (denied flow):

| Kondisi | Pesan | Tindakan |
|---|---|---|
| Izin lokasi ditolak | `Izin lokasi diperlukan untuk absensi. Aktifkan izin lokasi untuk situs ini di pengaturan browser, lalu coba lagi.` | Tombol `Coba lagi` dan tautan/panduan singkat membuka pengaturan permission browser |
| Izin kamera ditolak | `Izin kamera diperlukan untuk mengambil selfie absensi. Aktifkan izin kamera untuk situs ini di pengaturan browser, lalu coba lagi.` | Tombol `Coba lagi` dan panduan izin kamera |
| Izin lokasi/kamera diblokir permanen oleh browser | `Izin diblokir permanen oleh browser. Ubah izin melalui ikon gembok/address bar, lalu muat ulang halaman.` | Panduan mengubah permission pada address bar |

Aplikasi tidak memakai izin yang ditolak sebagai bukti kesalahan pengguna; pesan selalu actionable dan netral. Jika izin tetap tidak dapat diberikan pada perangkat, arahkan EOS menghubungi Supervisor. Tidak ada mekanisme pengajuan absensi di sistem ini; pencatatan kehadiran yang terlewat adalah urusan vendor absensi EOS, di luar scope aplikasi.

### 4.7 Peta (Leaflet + react-leaflet)

Peta memakai Leaflet dan react-leaflet untuk kebutuhan berikut:

- Input/ubah koordinat site: marker site dapat digeser (draggable); koordinat terbaru tampil saat menggeser dan harus disimpan melalui form site dengan alasan bila merupakan perubahan koordinat.
- Circle radius site (informasi, default 100 meter, dapat diubah per site) digambar di sekitar marker.
- Review titik check-in/clock-out: marker titik kehadiran EOS ditampilkan relatif terhadap marker site dan circle radius, termasuk jarak (meter) pada detail attendance bagi role berwenang (Supervisor site scope dan Super Admin).
- Peta bersifat alat bantu visual; jarak dan koordinat resmi tetap authority backend (perhitungan Haversine di server). Radius tidak menjadi gerbang — jarak yang jauh tetap tersimpan sebagai informasi dan dapat direview.
- Attribution peta ditampilkan; untuk development/pilot memakai public OSM tiles sesuai policy, sementara tile provider production ditentukan terpisah.

## 5. Information Architecture

### 5.1 EOS workspace — mobile-first

```text
Beranda
Absensi
- Riwayat Kehadiran

Daily Report
- Laporan Hari Ini
- Riwayat Laporan

Inventaris Site
- Aset (termasuk registrasi aset baru dari gudang)
- Material & Sparepart
- Mutasi Stok
- Inventory Finding

Profil
- Profil dan Penugasan
- Logout
```

### 5.2 Supervisor workspace

```text
Dashboard
Kehadiran
- Bukti Kehadiran Hari Ini
- Riwayat Kehadiran

Daily Report
- Semua Laporan
- Detail Laporan (reopen)

Inventaris
- Aset per Site
- Material & Sparepart
- Mutasi Stok
- Inventory Findings

Master Data
- Penugasan EOS
- Site
- Checklist Laporan (lihat versi published + usulkan perubahan draft)
- Kategori Aset
- Katalog Material/Sparepart

Analitik
- Kehadiran
- Laporan
- Inventaris
```

### 5.3 Manager workspace

```text
Dashboard Eksekutif
Kehadiran & Kepatuhan
Daily Report
Inventaris
Inventory Findings
Analitik
Master Checklist (read-only)
Ekspor (Excel/PDF/CSV + preset)
```

### 5.4 HR workspace

```text
Dashboard Kehadiran
Bukti Kehadiran Harian
Riwayat EOS
```

### 5.5 Super Admin workspace

```text
Dashboard Sistem
Pengguna & Role
Site
Penugasan EOS
Master Data (termasuk Checklist: publish version)
Audit Log
Storage & Kuota
Konfigurasi Sistem
```

Menu ditampilkan berdasarkan permission (spatie/laravel-permission). Akses URL langsung tanpa izin menghasilkan halaman `Akses Ditolak` (403 Inertia), bukan halaman kosong atau error teknis.

## 6. EOS Journeys

Journey inti EOS dalam sehari: login → lihat status hari ini → check-in → isi Daily Report → submit → clock-out → selesai. Setiap tahap hanya menampilkan satu CTA utama yang relevan dengan state.

### 6.1 Beranda EOS dan state harian

Beranda EOS menjawab pertanyaan: **“Apa tindakan saya sekarang?”**

Komponen utama:

- Identitas site assignment aktif dan timezone.
- Jam server/site saat ini.
- Status kehadiran hari ini: `Belum check-in`, `Checked-in`, `Selesai`.
- Status Daily Report hari ini: `Belum diisi`, `Draft`, `Submitted`, `Reopened`.
- Satu CTA utama yang berubah berdasarkan state.
- Link cepat ke inventaris site dan Inventory Finding.

| Kondisi | CTA dan pesan utama |
|---|---|
| Belum check-in | CTA `Check-in` |
| Sudah check-in, report belum submitted | CTA `Isi Daily Report`; tampilkan jam check-in (waktu lokal site) |
| Report `REOPENED` | Peringatan `Laporan hari ini dibuka kembali (REOPENED): {reason}. Perbaiki dan kirim ulang — clock-out terblokir sampai laporan dikirim ulang.`; CTA `Perbaiki & Kirim Ulang Daily Report`; tampilkan revision saat ini |
| Report submitted, belum clock-out | CTA `Clock-out` |
| Report draft/submitted tidak tersedia karena konfigurasi link site belum lengkap | Peringatan `Laporan harian tidak tersedia karena konfigurasi jaringan site (Main/Secondary Link) belum lengkap. Hubungi Supervisor Anda; kehadiran Anda tetap tercatat.`; tanpa CTA report; clock-out tetap mengikuti aturan report gate |
| Kehadiran selesai (COMPLETED) | `Kehadiran hari ini selesai` dan link detail |

Waktu pada tabel mengikuti timezone site (`WIB`/`WITA`/`WIT`). Check-in dapat dilakukan kapan saja sepanjang hari berjalan (tanggal lokal site yang sama dengan clock-out); tidak ada window jam maupun pembatasan hari kerja — EOS bekerja setiap hari operasional site sesuai penugasan vendor.

### 6.2 Check-in EOS

#### Pre-check screen

Sebelum membuka kamera/lokasi, sistem menjelaskan data yang dibutuhkan:

```text
Untuk check-in, sistem memerlukan:
- Lokasi terkini, direkam sekali sebagai bagian bukti kehadiran.
- Selfie melalui kamera depan sebagai bukti kehadiran.
- Waktu server sebagai waktu absensi resmi.
```

Tampilkan site, jam server, dan timezone. CTA: `Mulai Check-in`.

#### Proses

```text
Mulai Check-in
→ Minta izin lokasi dan kamera (setelah permission notice)
→ Ambil satu sample lokasi (GPS sekali; toleransi longgar — akurasi buruk tetap diterima,
  jarak dihitung server dan disimpan sebagai informasi, bukan gerbang)
→ Buka kamera depan dengan overlay lingkaran "posisikan wajah di dalam lingkaran"
→ Bila browser mendukung FaceDetector API: indikasi wajah terdeteksi,
  tombol jepret aktif setelah kamera stabil; bila tidak tersedia: panduan overlay saja
→ Jepret selfie → review singkat evidence
→ Kirim ke server (idempotent; server timestamp UTC)
→ Berhasil / gagal dengan recovery
```

Overlay lingkaran selfie:

- Overlay lingkaran dengan teks panduan `Posisikan wajah di dalam lingkaran` di atas preview kamera depan.
- Deteksi wajah bersifat opsional via browser FaceDetector API bila tersedia: indikator `Wajah terdeteksi` dan tombol jepret aktif setelah kamera stabil beberapa saat; bila API tidak tersedia atau tidak mendeteksi, fallback ke panduan overlay saja dan tombol jepret tetap dapat digunakan.
- Tidak ada penyimpanan biometrik dan tidak ada face-matching server-side; deteksi wajah hanya membantu framing selfie.

Jika `getUserMedia` tidak tersedia atau gagal, aplikasi otomatis beralih ke fallback `<input type="file" accept="image/*" capture="user">` yang membuka kamera depan perangkat; pilihan unggah dari galeri tidak ditawarkan pada alur normal.

#### Hasil berhasil

Tampilkan:

- Status `Check-in berhasil`.
- Waktu resmi dari server dan label timezone.
- Jarak ke site sebagai **informasi**, contoh: `Jarak Anda ke site: 35 m` — tersimpan pada record, bukan penilaian valid/invalid.
- CTA utama `Isi Daily Report`.

#### Kondisi gagal dan recovery

| Kondisi | Pesan UX | Aksi pemulihan |
|---|---|---|
| Izin lokasi ditolak | `Izin lokasi diperlukan untuk mencatat kehadiran. Aktifkan izin lokasi untuk situs ini di pengaturan browser, lalu coba lagi.` | Coba lagi; panduan membuka pengaturan browser/perangkat |
| Lokasi tidak diperoleh | `Lokasi belum dapat diperoleh. Pastikan GPS aktif dan Anda berada di area terbuka, lalu coba lagi.` | Coba lagi; pindah ke area dengan sinyal lebih baik |
| Izin kamera ditolak | `Izin kamera diperlukan untuk mengambil selfie. Aktifkan izin kamera untuk situs ini di pengaturan browser, lalu coba lagi.` | Coba lagi; panduan izin kamera; fallback input capture |
| Upload selfie gagal | `Selfie belum terkirim ke server. Periksa koneksi lalu kirim ulang.` | Retry upload; jangan menghapus hasil foto sebelum ada tindakan pengguna |
| Check-in kedua | `Anda sudah check-in hari ini. Tidak diperlukan check-in ulang.` | Tampilkan status kehadiran hari ini |
| Timeout request | `Check-in belum dikonfirmasi server. Periksa status absensi hari ini sebelum mencoba lagi.` | Retry aman/idempotent; cek status absensi hari ini sebelum membuat request baru |

Akurasi GPS buruk atau jarak jauh **tidak menolak** check-in — keduanya direkam sebagai informasi pada record (jarak dihitung Haversine server-side). Pesan UX tidak menuduh kecurangan dan selalu menyertakan langkah berikutnya. Kehadiran hanya dapat dicatat online.

#### Riwayat kehadiran

Baris riwayat kehadiran (E-20) menampilkan jam check-in, jam clock-out, status record, dan jarak (bila ditampilkan bagi role berwenang) dalam waktu lokal site.

### 6.3 Daily Report EOS

#### Pola navigasi: hybrid section flow

Daily Report adalah satu halaman Inertia report dengan section navigation dan progress. Pada ponsel, satu section menjadi fokus utama; pengguna dapat melanjutkan dengan `Berikutnya` atau berpindah ke section lain kapan saja. Pada viewport desktop, Daily Report memakai layout lebar: sidebar section memungkinkan navigasi cepat, semua section dapat dibuka sesuai kebutuhan, dan area kerja catatan lebih lebar — lihat aturan responsive §10.

```text
Info Umum → Router & Firewall → Access Point
→ Infrastruktur & Lingkungan → Konektivitas → Review & Submit
```

Header sticky menampilkan:

- Status report: `Menyimpan`, `Draft tersimpan (server)`, `Siap disubmit`, `Submitted`.
- Progress section/item wajib.
- Aksi simpan draft bila relevan.
- Link ke site dan work date lokal.

#### Info Umum

Field read-only, dibuat sistem:

```text
ID Laporan       : “Akan diterbitkan saat laporan disubmit” untuk draft;
                   nomor CMX.WR... setelah submit.
Tanggal           : Tanggal lokal site.
Nama Sekolah      : Site assignment aktif.
Nama Petugas      : Akun EOS login.
```

Evidence Section Info Umum: **opsional**, 0–5 attachment.

#### Router & Firewall

- Uptime.
- Utilisasi CPU (%).
- Utilisasi RAM (%).
- Log Anomali: status + catatan/lampiran kondisional sesuai rules (`MINOR` wajib note; `MAJOR` wajib note + item evidence `AVAILABLE`; `NOT_CHECKED` wajib note alasan).

Nilai persentase divalidasi secara inline, menggunakan satuan yang tampak. Jika status anomali mengharuskan catatan/evidence, jelaskan syarat sebelum pengguna berpindah section.

Evidence Section Router & Firewall: **wajib**, minimal 1 dari maksimal 5 attachment `AVAILABLE`.

#### Access Point

- Status Monitoring AP.
- Jumlah AP Offline (0–10.000), dengan nilai `0` sebagai input valid.
- Dokumentasi AP di Cloud: status, lampiran evidence wajib, serta catatan wajib untuk `BELUM_LENGKAP` atau `ADA_KENDALA`.

Untuk item Dokumentasi AP di Cloud, tampilkan status upload dan preview file sebelum report dapat berstatus siap submit. Rules inline: `NORMAL` mengharuskan AP offline = 0; `WARNING`/`DOWN` mengharuskan AP offline >= 1; `UNKNOWN` wajib note alasan dan count boleh kosong.

Evidence Section Access Point: **wajib**, minimal 1 dari maksimal 5 attachment `AVAILABLE`.

#### Infrastruktur & Lingkungan

- Pengecekan kelistrikan.
- Suhu ruangan server dalam °C (satu desimal, rentang -20,0 sampai 80,0; alat suhu tersedia di site sehingga suhu wajib diisi).
- Kondisi lingkungan.

Untuk nilai tidak normal atau pilihan berstatus masalah, tampilkan prompt catatan/evidence kondisional sesuai rules.

Evidence Section Infrastruktur & Lingkungan: **wajib**, minimal 1 dari maksimal 5 attachment `AVAILABLE`.

#### Konektivitas

Section konektivitas memuat dua kelompok input: traffic utilization per link dan dua speedtest terpisah.

**Utilisasi Main Link / Secondary Link (`LINK_TRAFFIC`)**

Satu form per link (Main dan Secondary):

- Status: `AVAILABLE | DOWN | NOT_CHECKED`.
- Avg Inbound dan Peak Inbound (nilai + satuan).
- Avg Outbound dan Peak Outbound (nilai + satuan).
- Selector satuan `KBPS`/`MBPS` per nilai; server menghitung `normalized_kbps` (Mbps dikonversi otomatis).
- Sumber data (dashboard/monitoring).
- Catatan.
- Evidence item.

Rules yang ditampilkan inline:

| Status | Syarat |
|---|---|
| `AVAILABLE` | Keempat nilai traffic + source + item evidence `AVAILABLE` wajib |
| `DOWN` | Note + item evidence `AVAILABLE` wajib |
| `NOT_CHECKED` | Note alasan wajib |

Satuan yang diterima hanya `KBPS`/`MBPS`; input lain (Gbps, KB/s, MB/s) ditolak dengan pesan `Satuan harus Kbps atau Mbps`.

**Connection Test Main Link / Secondary Link (`SPEEDTEST_RESULT`)**

Dua kartu speedtest **terpisah**: satu untuk Main Link dan satu untuk Secondary Link. Keduanya wajib diisi sebelum submit. Setiap kartu memuat:

- Status: `SUCCESS | FAILED | NOT_TESTED`.
- Download dan Upload (nilai + satuan KBPS/MBPS).
- Latency (ms), Jitter (ms), Packet loss (%; opsional).
- Server name (opsional).
- Route declaration: pilihan `TESTED_VIA_MAIN_LINK` atau `TESTED_VIA_SECONDARY_LINK` — deklarasi manual EOS, bukan verifikasi otomatis oleh aplikasi.
- Catatan dan evidence (screenshot speedtest).

Rules per kartu:

| Status | Syarat |
|---|---|
| `SUCCESS` | Download, upload, latency, jitter, dan screenshot evidence `AVAILABLE` wajib |
| `FAILED` / `NOT_TESTED` | Note alasan wajib, contoh `Link down` |

Bila salah satu link sedang down, kartu speedtest link tersebut tetap wajib diisi dengan status `FAILED` atau `NOT_TESTED` dan alasan. Test Secondary wajib mengikuti SOP routing/failover yang aman sebelum dijalankan; aplikasi hanya mencatat route_declaration yang dipilih EOS.

**Dual-link snapshot**

Konfigurasi link Main dan Secondary (provider, service, medium, kapasitas subscribed) ditampilkan sebagai snapshot read-only pada bagian atas section Konektivitas dan pada report submitted, karena konfigurasi disimpan saat report dibuat. Setiap site aktif selalu memiliki tepat satu Main dan satu Secondary link aktif; jika konfigurasi belum lengkap, site tidak dapat membuka Daily Report dan UI menampilkan `Konfigurasi link Main/Secondary site belum lengkap. Hubungi Supervisor.`.

Evidence Section Konektivitas: **wajib**, minimal 1 dari maksimal 5 attachment `AVAILABLE`.

#### Evidence Section UI (pola umum)

Setiap section diakhiri blok Evidence Section dengan pola konsisten:

- Label `Evidence Section` + indikator `Wajib`/`Opsional` sesuai section (Info Umum opsional; seluruh section operasional wajib minimal 1 attachment `AVAILABLE`).
- Counter lampiran `3/5` per section/item; batas 5 attachment per Evidence Section dan 5 per item evidence.
- Setiap lampiran menampilkan status lifecycle (`AVAILABLE`, `REJECTED`); hanya `AVAILABLE` dihitung sebagai terpenuhi.
- Batas total: maksimal 10 attachment per Daily Report (hitungan sederhana), ditampilkan sebagai counter agregat di header report.
- Satu lampiran hanya berlaku untuk satu context; tidak ada mekanisme taut ulang lampiran antar context. Evidence untuk context berbeda (mis. section lain) diunggah terpisah — file sama boleh diunggah ulang. Screenshot Main Link tidak otomatis menjadi evidence Secondary Link; bukti Secondary Link wajib diunggah terpisah.
- Hapus/replace lampiran sebelum submit; setelah submit immutable kecuali reopen.

#### Review dan submit

Halaman review memperlihatkan:

- Ringkasan setiap section dan status lengkap/belum lengkap.
- Daftar field/lampiran penghambat submit dengan link `Perbaiki` ke item terkait, termasuk evidence yang belum `AVAILABLE` dan speedtest/link traffic yang belum valid.
- Ringkasan lampiran per section dan total attachment report.
- Ringkasan konfigurasi dual-link snapshot.
- Pernyataan bahwa data akan dikunci setelah submit dan nomor laporan resmi dibuat.
- CTA `Submit Daily Report`.

Saat submit:

```text
Validasi client untuk feedback cepat
→ submit via Inertia (POST; FormRequest validasi server)
→ validasi authoritative server
→ alokasi nomor report atomik (CMX.WR.YYYYMM.SEQUENCE via PostgreSQL sequence)
→ status SUBMITTED
→ tampilkan report_number dan waktu submit server
```

Submit ditolak dengan pesan blocker jelas bila ada evidence wajib yang belum `AVAILABLE`, section operasional belum punya minimal satu attachment `AVAILABLE`, atau kartu speedtest/link traffic belum valid; review menampilkan daftar spesifik penghambat beserta tautan `Perbaiki`.

#### Laporan submitted dan reopened

EOS dapat membuka laporan submitted sebagai **read-only**, meliputi nomor laporan, seluruh jawaban, evidence, waktu submit, dan histori reopen/re-submit. EOS tidak dapat mengedit langsung.

Jika pihak berwenang membuka kembali report:

```text
SUBMITTED
→ REOPENED dengan alasan yang terlihat bagi EOS (reopen oleh Supervisor/Super Admin,
  maks 7 hari kalender sejak submit)
→ EOS memperbaiki bagian yang relevan
→ EOS submit ulang (revision bertambah; nomor tidak berubah)
→ kembali SUBMITTED
```

### 6.4 Clock-out EOS

Clock-out menjadi tersedia hanya bila:

```text
- Attendance hari ini berstatus CHECKED_IN (check-in final hari yang sama).
- Daily Report hari ini telah SUBMITTED (semua required evidence AVAILABLE).
- Check-in dan clock-out berada pada tanggal lokal site yang sama.
```

Tidak ada window jam clock-out; satu-satunya batas adalah tanggal lokal site yang sama dengan check-in (berganti hari lokal = clock-out tidak lagi tersedia untuk hari tersebut).

Alur (kontinu — bukan penolakan mentah bila report belum submit):

```text
Pilih Clock-out
→ pre-check persyaratan terpenuhi (permission notice bila belum diberikan)
→ bila report belum SUBMITTED: tampilkan pengarahan
  "Laporkan pekerjaan hari ini untuk melanjutkan clock-out" + CTA ke form Daily Report
  → setelah submit berhasil, kembali ke alur clock-out
→ ambil satu sample lokasi (sekali; jarak dihitung server sebagai informasi)
→ selfie kamera dengan overlay lingkaran (getUserMedia; fallback input capture;
  FaceDetector opsional bila tersedia)
→ kirim ke server (idempotent; server timestamp UTC)
→ status COMPLETED
```

Jika belum bisa clock-out, layar menjelaskan alasan tepatnya:

| Blocker | Pesan |
|---|---|
| Report belum submitted | Bagian dari alur kontinu: `Laporkan pekerjaan hari ini untuk melanjutkan clock-out.` + CTA ke form Daily Report; setelah submit, clock-out dilanjutkan. |
| Belum check-in | `Anda belum check-in hari ini, sehingga clock-out tidak tersedia.` CTA `Check-in` (bila masih hari lokal yang sama). |
| Tanggal lokal sudah berganti | `Waktu clock-out untuk hari {tanggal} telah berakhir karena tanggal lokal site sudah berganti ({tanggal sekarang} {WIB/WITA/WIT}).` |
| Clock-out kedua | `Anda sudah clock-out hari ini. Tidak diperlukan clock-out ulang.` |
| Lokasi/kamera belum valid | Jelaskan evidence yang diperlukan dan sediakan retry |

Keberhasilan menampilkan waktu server, timezone, jarak ke site (informasi), evidence status, dan ringkasan Daily Report yang telah disubmit. Tidak ada kategori clock-out dini/normal.

### 6.5 Inventaris dan Inventory Finding EOS

EOS dapat melihat inventaris pada site assignment aktif:

- Aset dengan asset tag, nama/model, status, lokasi pemasangan, dan foto yang diizinkan.
- Material/sparepart dengan unit dan saldo yang ditampilkan sesuai permission.
- Riwayat mutasi yang relevan untuk operasional.

EOS tidak dapat mengedit aset atau saldo stok secara langsung.

#### Registrasi aset oleh EOS

Barang datang dari gudang Comtronics (asset tag + serial number sudah ada dari gudang); EOS yang meregistrasikannya:

```text
Inventaris Site → Aset → Registrasi Aset
→ sistem mengisi site dan EOS otomatis
→ input asset tag (divalidasi format regex CMX + unik oleh server)
→ input serial number bila perangkat punya SN (apa adanya dari gudang)
→ pilih kategori dan lokasi pemasangan
→ unggah foto registrasi (wajib)
→ submit → aset berstatus IN_USE
```

Pesan validasi server untuk asset tag ganda/format salah ditampilkan inline pada field terkait.

#### Mutasi stok material/sparepart

EOS dapat melakukan mutasi stok untuk item material/sparepart pada site assignment aktif:

```text
Inventaris Site → Material & Sparepart → Mutasi
→ pilih item dan tipe mutasi (RECEIPT/USAGE/ADJUSTMENT/DAMAGED/LOST/RETURN/TRANSFER_IN/TRANSFER_OUT)
→ input quantity dengan preview saldo sebelum/sesudah
→ alasan wajib; foto bila barang rusak/hilang (maks 5 lampiran)
→ submit → saldo diperbarui atomik; saldo tidak boleh negatif (divalidasi server)
```

#### Membuat Inventory Finding

```text
Inventaris Site
→ Inventory Finding
→ Buat Temuan
→ sistem mengisi site dan pelapor otomatis
→ pilih tipe finding
→ pilih aset/item stok terkait bila ada
→ isi deskripsi
→ unggah foto/lampiran bukti
→ submit
→ status OPEN
```

Tipe baseline:

```text
Aset rusak
Aset hilang
Aset tidak sesuai data
Material/sparepart kurang
Material/sparepart rusak
Lainnya
```

Evidence wajib ditunjukkan sebelum submit. Setelah dibuat, EOS dapat melihat detail, lampiran, status, catatan review, dan histori aktivitas secara read-only.

### 6.6 Notifikasi in-app EOS

Notifikasi membuat EOS mengetahui peristiwa penting tanpa harus membuka halaman terkait secara manual (FR-15). Notifikasi bukan authority — status sebenarnya selalu pada record sumber dan audit.

Komponen:

- Badge lonceng pada header aplikasi dengan counter unread; muncul di semua halaman setelah login.
- Klik badge membuka Notification Center: daftar kronologis (terbaru di atas) dengan tipe, teks singkat, waktu relative (`2 jam lalu`), dan tautan `Lihat` menuju record terkait.
  - Report reopened → Daily Report terkait (langsung ke state perbaiki & kirim ulang, lihat 6.1).
  - Attachment rejected → Daily Report bagian Evidence Section terkait.
  - Export selesai → halaman/hasil export terkait (backoffice).
- Badge diperbarui dari prop halaman Inertia / polling ringan; tidak ada push real-time di MVP. UI tidak menghitung/menyimpan nilai ini secara lokal.
- Tindakan: `Tandai dibaca` per item dan `Tandai semua dibaca` (idempotent).
- Empty state: `Belum ada notifikasi`.
- Item tidak terhapus dari daftar setelah dibaca (read-only history) selama retention notification aktif.

```text
Contoh salinan notifikasi:

Laporan harian Anda dibuka kembali (REOPENED): {reason}. Segera perbaiki dan kirim ulang.
Lampiran Anda ditolak karena file tidak valid. Periksa kembali lampiran pada laporan.
```

### 6.7 Forced change password (seluruh role)

Berlaku untuk semua role, bukan hanya EOS. Trigger: `must_change_password=true` pada user (reset password oleh Super Admin atau kebijakan yang memaksa pergantian).

Journey:

```text
Login sukses
→ banner persisten: `Anda harus mengganti password sebelum dapat melanjutkan.`
→ layar ganti password (password lama, password baru, konfirmasi password baru)
→ sukses
→ re-login (session lama di-revoke server karena password berubah)
→ lanjut ke aplikasi
```

Ketentuan:

- Selama `must_change_password=true`: semua CTA/navigasi lain disabled/hidden dan request selain ganti password ditolak server (middleware paksa ganti password; UI hanya menyampaikan).
- Form memvalidasi konfirmasi sama dan policy password (12–128 karakter) secara inline; keputusan akhir tetap server (FormRequest).
- Setelah sukses, server merevoke seluruh session aktif; UI mengarahkan re-login dengan pesan `Password berhasil diganti. Silakan login kembali.`
- Screen: C-10 (lihat §9.1); halaman `/change-password` (lihat ui-spec route baseline).

## 7. Backoffice Journeys

### 7.1 Dashboard dan data table standar

Dashboard backoffice memiliki filter server-side, URL state yang dapat dibagikan, loading/error state, empty state, pagination, sorting, serta drill-down ke detail record.

Standar list/table:

- Filter site, EOS, tanggal/periode, serta status sesuai domain.
- Filter tanggal menjelaskan basis timezone operasional.
- Badge status tidak hanya dibedakan oleh warna; ada label teks dan ikon bila perlu.
- Empty state memberi konteks tindakan, misalnya `Belum ada laporan pada rentang tanggal ini`.
- Error load menyediakan retry dan tidak menyamarkan kegagalan sebagai “tidak ada data”.
- Detail record memperlihatkan ringkasan, evidence terotorisasi, histori, dan action sesuai permission.

### 7.2 Supervisor: Daily Report review dan reopen

Daftar laporan memperlihatkan status (DRAFT/SUBMITTED/REOPENED/VOIDED), EOS, site, work date lokal, nomor report, revision, dan indikator evidence. Detail review menampilkan:

```text
- Seluruh jawaban checklist versi yang dipakai (snapshot).
- Evidence terotorisasi beserta lifecycle status.
- Histori submit/reopen/re-submit.
- Nomor report, revision, checklist version, dan waktu server.
```

Action reopen (maks 7 hari kalender sejak submit):

```text
Reopen → alasan wajib → confirmation (dampak: EOS harus memperbaiki & resubmit;
         revision bertambah; nomor tidak berubah) → status REOPENED + notifikasi EOS → teraudit.
```

### 7.3 Supervisor: Inventory Finding

Daftar finding mendukung filter site, tipe, status, pelapor, dan tanggal. Detail finding memperlihatkan asset/stock terkait, evidence, deskripsi, histori, serta tindakan terkait inventaris.

Workflow UX:

```text
OPEN
→ UNDER_REVIEW
→ RESOLVED atau REJECTED
→ CLOSED bila catatan final/administratif selesai
```

Supervisor dapat melakukan perubahan aset atau mutasi stok lewat alur inventory yang sah, bukan dengan mengubah finding secara diam-diam. Saat resolve, UI meminta referensi perubahan terkait dan catatan penyelesaian.

### 7.4 Manager: analitik dan drill-down

Manager memakai dashboard untuk melihat data lintas-site, bukan mengedit record operasional harian.

Dashboard minimum:

- Kehadiran hari ini dan historis (bukti check-in/clock-out per site).
- Kepatuhan submission Daily Report.
- Ringkasan temuan report: AP offline, konektivitas degradasi/down, anomali perangkat, dan kondisi lingkungan/kelistrikan jika tersedia.
- Asset berstatus bermasalah dan Inventory Finding terbuka.

Setiap KPI harus dapat di-drill-down ke daftar record yang membentuk angka tersebut. Filter menampilkan rentang lokal dan cakupan site agar angka tidak menyesatkan.

### 7.5 HR: bukti kehadiran

HR melihat domain attendance sebagai bukti kehadiran, bukan seluruh evidence/report teknis secara default, dan bukan alat disiplin (disiplin vendor).

Fokus halaman:

- Kehadiran per hari/site/EOS (check-in/check-out tersedia atau tidak).
- Status completion record harian.
- Riwayat EOS (assignment site, masa penugasan).

### 7.6 Super Admin: governance

Super Admin mengelola user, role, site, assignment, checklist, dan audit log. Form konfigurasi berisiko harus menampilkan dampak perubahan dan confirmation eksplisit.

#### Storage usage (Super Admin)

Halaman `Storage & Kuota` (screen A-06, halaman `/admin/storage-usage`) menampilkan daftar site beserta penggunaan penyimpanan attachment (byte human-readable), persentase pemakaian, dan status per baris. Detail per site menampilkan tren penggunaan ringkas dan jumlah attachment.

#### Export Excel/PDF/CSV dengan preset (Super Admin/Manager)

Export tersedia dari halaman data backoffice sesuai permission:

- Pilih format: **Excel (xlsx styled)** (header bold, border, lebar kolom auto, judul + periode di header, filename dinamis), **PDF formal** (header instansi, siap cetak), atau **CSV** (data mentah).
- Kustomisasi: **pilih kolom via checkbox per kolom**, filter periode/site/status/EOS.
- Konfigurasi pilihan kolom + filter dapat **disimpan sebagai preset per user** dan dipakai ulang; preset dikelola (simpan, ganti nama, hapus) pada panel export.
- Ringkasan filter + kolom terpilih ditampilkan sebelum aksi `Unduh`.
- Export berjalan sinkron (streamed): setelah tombol ditekan, browser langsung mengunduh file — tidak ada layar status job/polling.
- Saat proses berlangsung tombol dinonaktifkan dengan status `Menyiapkan berkas…`; error ditampilkan dengan retry tanpa file parsial.
- Audit export terjadi otomatis di server sebelum stream dimulai (actor, role, data type, filter, kolom, scope, timestamp, outcome); tidak ada input alasan tambahan dari user.
- Manager tetap tidak dapat mengekspor raw selfie/precise GPS/sensitive attachment; permintaan yang melanggar scope privacy ditolak dengan pesan `Export tidak diizinkan untuk cakupan data ini`.

## 8. Master Data UX

### 8.1 Site

Form Site mencakup identitas, alamat, koordinat (lat/lng), radius (informasional), timezone, dan status aktif. Pemilihan timezone menggunakan label manusiawi dan nilai IANA:

```text
WIB — Asia/Jakarta
WITA — Asia/Makassar
WIT — Asia/Jayapura
```

Radius site (default 100 meter) bersifat **informasional** — digunakan untuk visualisasi peta dan konteks review jarak, bukan gerbang absensi. Perubahan koordinat/timezone memerlukan alasan wajib dan membentuk histori/audit.

Koordinat site diinput/diubah melalui peta (marker draggable + circle radius) atau input koordinat manual; perubahan menampilkan confirmation dampak terhadap konteks jarak absensi berikutnya.

### 8.2 Penugasan EOS

Form assignment memperlihatkan EOS, site, tanggal mulai, status, dan assignment aktif yang berpotensi konflik. Jika EOS sudah memiliki assignment aktif, sistem harus memblokir submit dan mengarahkan pengguna menyelesaikan assignment sebelumnya.

### 8.3 Checklist Daily Report

Pengelola checklist melihat template version (1 template global `DAILY_SITE_REPORT`), status (DRAFT/PUBLISHED/SUPERSEDED/RETIRED), section, item, tipe input, satuan, required rule, catatan kondisional, lampiran wajib, serta preview form EOS.

Hak akses per role: Super Admin mengelola penuh (create version `DRAFT`, edit, dan publish; published immutable); Supervisor dapat melihat versi published serta membuat/usulkan perubahan pada version `DRAFT` (usulan menjadi input untuk Super Admin); Manager hanya melihat (read-only) versi dan histori. Tombol publish hanya muncul untuk Super Admin.

Perubahan template aktif tidak mengubah Daily Report lama (report menyimpan snapshot). UX menjelaskan versioning dengan jelas.

### 8.4 Aset dan stok

Form registrasi/edit aset mewajibkan asset tag unik (format regex CMX, divalidasi server), kategori, status, serta foto pendaftaran. Serial number ditampilkan bila perangkat memiliki serial number (input apa adanya dari gudang). Aset tidak dihapus langsung; gunakan perubahan status (IN_USE/SPARE/RETURNED/DAMAGED/LOST/DISPOSED) dengan alasan; foto wajib saat status berubah ke rusak/hilang. Barang tidak berpindah antar site; rusak → dikembalikan ke gudang (RETURNED).

Mutasi stok selalu berupa transaksi dengan tipe, quantity delta, alasan, evidence bila diwajibkan, dan preview saldo sebelum/sesudah. User tidak boleh mengedit saldo langsung dari table.

### 8.5 Konektivitas dan PWA

Aplikasi PWA installable; app shell dan static assets di-cache. **Tidak ada penyimpanan draft offline** (revised 2026-10-05): penulisan Daily Report online-only; tanpa konektivitas aplikasi tidak dapat dipakai untuk authoring.

Komponen UX konektivitas:

- Badge offline global pada header: `Offline — aplikasi memerlukan koneksi`, berganti menjadi `Online` saat koneksi pulih.
- Tanpa konektivitas tidak ada layar authoring report; UI menampilkan `Aplikasi memerlukan koneksi. Pulihkan koneksi atau hubungi Supervisor.` Tanpa draft lokal, tombol sync, atau state gagal sinkronisasi.
- Attachment tidak dapat diantrekan (queued) offline.
- Kehadiran tidak dapat dicatat offline atau diantrekan; kehadiran yang terlewat karena offline menjadi urusan vendor absensi EOS (di luar scope aplikasi).

## 9. Screen Inventory untuk Wireframe/Prototype

Screen inventory memakai halaman Inertia; nama route Laravel dan page component tercantum pada ui-spec.md (route baseline).

### 9.1 Common

```text
C-01 Login
C-02 Session expired / re-login
C-03 Access denied
C-04 Not found
C-05 Generic service error + retry
C-06 Attachment upload / preview state
C-07 Camera/location permission denied flow
C-08 Offline badge state
C-09 Notification center: badge unread di header + daftar notifikasi
C-10 Forced change password (must_change_password gate; lihat 6.7)
```

### 9.2 EOS mobile

```text
E-01 Beranda EOS: belum check-in
E-02 Beranda EOS: sudah check-in, report belum submitted
E-03 Beranda EOS: report submitted, belum clock-out
E-04 Beranda EOS: kehadiran selesai (COMPLETED)
E-05 Check-in pre-check / permission explainer
E-06 Check-in selfie capture: overlay lingkaran + indikasi FaceDetector (opsional)
E-07 Check-in validation success (waktu server + jarak info)
E-08 Check-in error/recovery variants
E-09 Daily Report section navigation + draft state
E-10 Daily Report Router & Firewall
E-11 Daily Report Access Point + cloud evidence upload
E-12 Daily Report Infrastruktur & Lingkungan
E-13 Daily Report Konektivitas (LINK_TRAFFIC + dua kartu Speedtest)
E-14 Daily Report review & validation errors
E-15 Daily Report submitted read-only
E-16 Daily Report reopened
E-17 Clock-out capture/review (overlay lingkaran + jarak info)
E-18 Clock-out blocked states (report gate, tanggal berganti, belum check-in)
E-19 Clock-out success
E-20 Riwayat kehadiran dan detail
E-21 Registrasi aset (EOS; asset tag + SN gudang + foto wajib)
E-22 Mutasi stok material/sparepart (EOS)
E-23 Inventaris site: aset
E-24 Inventaris site: material/sparepart
E-25 Inventory Finding list
E-26 Buat Inventory Finding
E-27 Detail Inventory Finding
E-28 Profil dan site assignment
E-29 Evidence Section + status lampiran (AVAILABLE/REJECTED)
E-30 Konektivitas hilang: aplikasi tidak dapat dipakai; arahkan hubungi Supervisor/pulihkan koneksi
E-31 Attachment REJECTED notice
E-32 Daily Report dual-link snapshot read-only
```

### 9.3 Supervisor / backoffice

```text
S-01 Dashboard Supervisor
S-02 Bukti kehadiran hari ini
S-03 Daftar Daily Report
S-04 Detail Daily Report + attachment/evidence + reopen
S-05 Inventory dashboard/listing aset
S-06 Detail/create/edit asset
S-07 Stock item dan histori mutasi
S-08 Inventory Finding list
S-09 Inventory Finding review/resolve/reject
S-10 Master site
S-11 Assignment EOS
S-12 Checklist template list/version
S-13 Checklist template editor/preview (usulan draft; publish Super Admin)
S-14 Asset category dan catalog material
S-15 Analitik kehadiran/report/inventory
S-16 Panel export: pilih kolom + filter + preset + format (Excel/PDF/CSV)
```

### 9.4 Manager, HR, dan Super Admin

```text
M-01 Dashboard Eksekutif
M-02 Kehadiran dan drill-down
M-03 Analitik Daily Report dan drill-down
M-04 Analitik inventaris/finding
M-05 Panel export (pilih kolom + preset + format)
H-01 Dashboard HR
H-02 Bukti kehadiran harian
H-03 Riwayat EOS
A-01 User management
A-02 Role assignment
A-03 Audit log list/filter
A-04 Audit log detail
A-05 System configuration
A-06 Storage usage: daftar site, penggunaan penyimpanan, status
A-07 Checklist publish (Super Admin)
```

## 10. Responsive, Accessibility, dan Privacy

### Responsive

- EOS flow harus usable pada layar ponsel kecil dengan target sentuh yang cukup, CTA utama fixed/sticky jika aman, dan input angka yang sesuai keyboard perangkat. Saat dibuka di viewport desktop (browser), workspace EOS memakai kolom tunggal terpusat dengan max-width (mis. 720px) secara default — tidak ada layout dashboard desktop terpisah untuk seluruh workspace. Pengecualian: **Daily Report** (sidebar navigasi section, area kerja lebih lebar untuk mengetik catatan) dan **halaman historis read-only** (riwayat kehadiran, riwayat report, daftar notifikasi) memakai layout lebar di viewport desktop. Flow kamera/GPS (check-in, clock-out, capture selfie, capture evidence) tetap memakai pola interaksi mobile di semua ukuran layar.
- Backoffice dioptimalkan desktop, tetapi detail/read-only tetap dapat dibuka di tablet/ponsel.
- Tabel lebar memakai pola responsive yang jelas: column priority, horizontal scroll yang terlihat, atau card/detail pattern; jangan memotong data kritis tanpa tanda.

### Accessibility

- Semua form mempunyai label eksplisit, error terhubung ke input, dan status tidak hanya berbasis warna.
- Keyboard navigation tersedia untuk workflow desktop.
- Fokus berpindah ke summary error saat submit gagal.
- Kontras, ukuran teks, touch target, dan status loading memenuhi standar aksesibilitas yang disepakati tim.
- File attachment dan selfie memiliki label konteks yang dapat dipahami screen reader.

### Privacy dan pembatasan visibilitas data

- Sebelum absensi, EOS diberi penjelasan mengapa lokasi dan selfie dibutuhkan (permission notice), dan alur izin ditolak tetap dapat dilanjutkan dengan panduan.
- Location/evidence hanya diperlihatkan sesuai permission dan kebutuhan operasional; setiap akses/download sensitive evidence tercatat sebagai audit event.
- Manager tidak melihat raw selfie, precise GPS, GPS accuracy, atau sensitive evidence Daily Report secara default; tampilan Manager berupa ringkasan status lintas-site. Ekspor oleh Manager juga tidak memuat data tersebut.
- HR melihat data attendance sesuai otorisasi, namun tidak otomatis melihat technical evidence Daily Report.
- Supervisor dibatasi pada site scope (assignment aktif EOS); dalam scope tersebut Supervisor dapat melihat selfie, evidence, dan GPS presisi yang diperlukan untuk review kehadiran.
- EOS hanya melihat data/evidence miliknya sendiri dan data site yang dibutuhkan untuk pekerjaannya.
- Dashboard eksekutif mengutamakan status/ringkasan; koordinat presisi tidak ditampilkan tanpa kebutuhan.
- Attachment disimpan pada local disk private (di luar public/); preview/download melalui controller terkontrol + audit, bukan URL publik.
- UI mendukung kebijakan retensi tanpa menghapus audit referensi secara tidak terkontrol; kebijakan legal hold (bersama purge automation) adalah fase 2 — deferred, bukan bagian UI MVP.

### Mobile-first behavior kamera

- Selfie absensi memakai `MediaDevices.getUserMedia()` dengan preview langsung dan overlay lingkaran `Posisikan wajah di dalam lingkaran`; bila API tidak tersedia/gagal, fallback `<input type="file" accept="image/*" capture="user">` membuka kamera depan perangkat.
- Deteksi wajah opsional via browser FaceDetector API bila tersedia: indikasi `Wajah terdeteksi`, tombol jepret aktif setelah kamera stabil; bila tidak tersedia, tombol jepret tetap tersedia dengan panduan overlay saja. Tidak ada penyimpanan biometrik / face-matching server-side.
- Alur normal tidak menawarkan unggah selfie dari galeri; pilihan sumber galeri tidak muncul pada form selfie.
- Maksimum satu selfie check-in dan satu selfie clock-out per hari (per tanggal lokal site).

## 11. UX Acceptance Criteria

### Kehadiran (bukti kehadiran)

- EOS dapat melakukan check-in kapan saja sepanjang hari lokal berjalan, tanpa window jam dan tanpa penilaian kategori waktu; UI menampilkan waktu server dan timezone site pada hasil.
- GPS diambil sekali dengan toleransi longgar; jarak ke site ditampilkan sebagai informasi dan tersimpan pada record — tidak ada penolakan berbasis radius/akurasi.
- UI selfie menampilkan overlay lingkaran dengan teks panduan; bila FaceDetector tersedia, indikasi wajah terdeteksi dan tombol jepret aktif setelah kamera stabil; bila tidak, alur tetap dapat diselesaikan.
- UI tidak menyatakan kehadiran berhasil sebelum respons sukses backend diterima; double submit dicegah UI (disabled) dan server (unique constraint).
- Clock-out diblok dengan alasan eksplisit hanya bila report belum `SUBMITTED`/evidence wajib belum `AVAILABLE`, belum check-in, atau tanggal lokal site telah berganti; alur report gate bersifat kontinu (CTA ke Daily Report).
- Riwayat menunjukkan status record (NOT_CHECKED_IN/CHECKED_IN/COMPLETED), waktu server/local site, jarak (role berwenang), dan evidence yang diizinkan.
- Offline tidak menawarkan check-in/clock-out; tidak ada mekanisme pengajuan absensi dalam sistem.

### Daily Report

- EOS dapat mengetahui progress checklist serta item/lampiran yang menghambat submit.
- Draft hanya tersimpan di server (online-only); tidak ada draft lokal, aturan kedaluwarsa, atau konflik sinkronisasi (lihat 8.5).
- Setiap section operasional menampilkan Evidence Section wajib dengan counter maksimal 5, dan total 10 attachment per report; lampiran tidak dapat ditaut ulang lintas context (upload terpisah).
- Dokumentasi AP di Cloud terlihat wajib dan memerlukan evidence sebelum submit.
- Form Konektivitas menampilkan LINK_TRAFFIC per link (status, avg/peak inbound/outbound, satuan KBPS/MBPS, source, note, evidence) dan dua kartu Speedtest terpisah Main/Secondary dengan status `SUCCESS`/`FAILED`/`NOT_TESTED` dan route_declaration.
- Dual-link snapshot site ditampilkan pada report.
- Submit berhasil menunjukkan nomor `CMX.WR.YYYYMM.SEQUENCE` dan waktu server.
- Report submitted dapat dilihat read-only; reopen memiliki alasan, batas 7 hari kalender, revision bertambah, dan histori yang terlihat.

### Attachment

- Semua lampiran menampilkan status lifecycle (`AVAILABLE`/`REJECTED`); hanya `AVAILABLE` memenuhi syarat evidence.
- Validasi file sinkron di request; hasil upload langsung final tanpa menunggu pemeriksaan async.
- Pesan `REJECTED` aman, actionable, dan tidak memaparkan detail teknis pipeline.
- Upload gagal format/ukuran menampilkan pesan penyebab dan tindakan perbaikan.

### Inventaris

- EOS dapat meregistrasi aset dari gudang (asset tag + SN divalidasi format & unik, foto wajib) tanpa akses edit langsung ke data lain.
- Mutasi stok beralasan dengan preview saldo; saldo tidak pernah negatif; saldo tidak diedit langsung dari table.
- EOS dapat membuat finding tanpa memperoleh akses edit langsung terhadap aset/stok.
- Finding merekam site, pelapor, tipe, deskripsi, object terkait bila ada, evidence, status, dan histori.
- Supervisor dapat review/resolve/reject dengan alasan dan referensi perubahan inventaris yang relevan.

### Backoffice dan governance

- Dashboard/listing membedakan empty, error, loading, dan access denied.
- Semua action kritis memiliki confirmation, alasan bila diperlukan, serta feedback hasil server.
- Filter dan timezone context terlihat pada data lintas-site.
- Role tidak melihat menu/action yang tidak relevan; direct URL tetap ditolak server dan ditampilkan aman oleh UX.
- Export menawarkan tiga format (Excel styled/PDF formal/CSV), pilih kolom, filter, dan preset per user yang dapat dipakai ulang; unduhan sinkron; audit otomatis; Manager tanpa raw selfie/precise GPS.
- Pembatasan visibilitas privacy diterapkan: Manager tanpa raw selfie/precise GPS, HR tanpa evidence Daily Report, Supervisor terbatas site scope.

## 12. Open UX Decisions untuk Fase Desain

Keputusan berikut tidak menghambat UX structure, tetapi harus ditetapkan saat wireframe/prototype:

- Teks final privacy notice/consent oleh HR/legal (struktur notice sudah ditetapkan di 4.6).
- Tile provider peta untuk production (OSM public hanya untuk development/pilot kecil).
- Detail SOP aman routing/failover Speedtest Secondary per jenis router/site yang akan direferensikan pada kartu Speedtest.
- Nilai ambang tampilan warning CPU/RAM/suhu/traffic pada dashboard backoffice.
- Apakah selfie memerlukan aturan liveness tambahan pada fase berikutnya (deteksi wajah browser opsional hanya framing, bukan liveness check).
- Bagaimana angka jarak GPS disajikan kepada role berwenang selain nilai meter (struktur pesan sudah ditetapkan di 6.2).
- Apakah Inventory Finding dapat ditautkan langsung dari jawaban Daily Report yang abnormal.
- Komponen visual peta (Leaflet) di-upload ke layout/section mana bila peta dimuat pada flow capture absensi — saat ini peta hanya untuk backoffice/master/review.
