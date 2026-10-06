# Restore Drill

Dokumen drill restore PostgreSQL bulanan (ADR-031; runbook §8; test-strategy §7).
Drill membuktikan end-to-end bahwa backup benar-benar dapat direstore dan bahwa
stack Laravel yang memakai data hasil restore melayani health check `/up` 200
dan login smoke — bukan hanya selewat `pg_isready`.

## Prinsip isolasi

- Restore SELALU ke volume Docker scratch terisolasi (`postgres_scratch`) yang
  dibuat via `docker volume create` di luar compose project — bukan volume
  `postgres_data` staging/produksi, dan tidak pernah lewat compose project
  (volume compose terikat project name).
- Verifikasi stack memakai container Laravel throwaway pada network scratch
  internal; tidak ada port host baru yang dipublikasikan (PostgreSQL tetap
  internal-only).
- `pg_restore` host (mis. v16) dapat menolak header dump pg_dump 17
  ("unsupported version") — jalankan `pg_restore` SELALU via container
  `postgres:17`, bukan binary host.
- User dan database scratch HARUS sama dengan `.env` (`DB_USERNAME` /
  `DB_DATABASE`) — role yang beda menyebabkan error `ALTER OWNER` saat restore.
- Tidak ada Redis di stack — tidak ada scratch redis; seluruh state
  (session/cache/queue) berada di PostgreSQL hasil restore (ADR-047).
- Volume scratch dan network scratch selalu dibersihkan di akhir drill, baik
  saat sukses maupun gagal.

## Preconditions

```text
- Image aplikasi lokal tersedia (hasil `sail build` / build Compose lokal,
  mis. image aplikasi Laravel:local) — dipakai untuk verifikasi stack
  (bagian 4).
- File backup custom-format ada: backups/postgres/backup-YYYYMMDD-HHMMSS.dump
  (hasil job pg_dump scheduler / prosedur backup). Bila belum ada, jalankan
  backup manual via container pg_dump dari postgres yang berjalan.
- Docker tersedia.
- Nilai DB_USERNAME / DB_DATABASE / DB_PASSWORD / APP_KEY dibaca dari .env —
  jangan di-hardcode.
```

## Urutan drill

Semua command dijalankan dari root repo. Ganti `<file>.dump` dengan file backup
yang akan diuji (atau pakai yang paling baru):

```bash
DUMP=$(ls -t backups/postgres/*.dump | head -1)
```

Baca kredensial dari `.env` (tanpa men-echo password):

```bash
PG_USER=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2-)
PG_DB=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2-)
PG_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2-)
APP_KEY=$(grep -E '^APP_KEY=' .env | cut -d= -f2-)
```

### 1. Provision volume + network scratch

```bash
docker volume create postgres_scratch
docker network create cmx-drill
```

### 2. Restore via temp container postgres:17

```bash
docker run --rm -d \
  -v postgres_scratch:/var/lib/postgresql/data \
  -v "$PWD/backups/postgres:/backups:ro" \
  --network cmx-drill \
  --name postgres-scratch \
  -e POSTGRES_USER="$PG_USER" \
  -e POSTGRES_PASSWORD="$PG_PASSWORD" \
  -e POSTGRES_DB="$PG_DB" \
  postgres:17
```

Tunggu hingga ready (loop singkat):

```bash
until docker exec postgres-scratch pg_isready -U "$PG_USER" -d "$PG_DB" >/dev/null 2>&1; do sleep 1; done
```

Restore (jalankan pg_restore dari container postgres:17, `--clean --if-exists`
agar drill dapat diulang):

```bash
docker exec postgres-scratch pg_restore \
  -U "$PG_USER" -d "$PG_DB" \
  --clean --if-exists --no-owner --role="$PG_USER" \
  /backups/"$(basename "$DUMP")" ; echo "pg_restore exit: $?"
```

Expected: exit 0.

### 3. Verifikasi `pg_restore --list` + schema

```bash
docker run --rm -v "$PWD/backups/postgres:/backups:ro" postgres:17 \
  pg_restore --list /backups/"$(basename "$DUMP")"
```

Expected: daftar entry TOC `TABLE`/`TABLE DATA` (tabel bisnis: attendance
record, daily_reports, daily_report_answers, checklist_versions, assets,
inventory_*, activity_log, notifications, jobs, sessions, migrations) — exit
non-zero + pesan bila file rusak/korup (drill dicatat outcome gagal).

Verifikasi query row count (mis. tabel migration):

```bash
docker exec postgres-scratch psql -U "$PG_USER" -d "$PG_DB" \
  -tc "SELECT count(*) FROM migrations"
```

Expected: baris data ada (count > 0) — bukti data (bukan hanya schema)
ter-restore.

### 4. Verifikasi stack health — container Laravel throwaway

Jalankan container Laravel throwaway (image aplikasi hasil build lokal) pada
network scratch internal yang sama, dengan env menunjuk postgres scratch,
TANPA port host (serve internal via `php artisan serve`):

```bash
docker run --rm -d \
  --name laravel-drill \
  --network cmx-drill \
  -e APP_ENV=local \
  -e APP_KEY="$APP_KEY" \
  -e APP_DEBUG=false \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=postgres-scratch \
  -e DB_PORT=5432 \
  -e DB_DATABASE="$PG_DB" \
  -e DB_USERNAME="$PG_USER" \
  -e DB_PASSWORD="$PG_PASSWORD" \
  -e SESSION_DRIVER=database \
  -e QUEUE_CONNECTION=database \
  -e CACHE_STORE=database \
  <image-aplikasi-lokal> \
  php artisan serve --host=0.0.0.0 --port=8080
```

Cek `/up` via `docker exec` dari DALAM container (localhost:8080, tanpa port
host):

```bash
docker exec laravel-drill curl -sf http://localhost:8080/up ; echo " -> /up exit: $?"
```

Expected: HTTP 200 (Laravel health route; membuktikan koneksi database ke data
hasil restore benar-benar hidup, karena health check mengevaluasi dependency
PostgreSQL). Bila bukan 200, drill GAGAL — catat outcome gagal, lalu lakukan
cleanup bagian 6.

Login smoke tambahan (opsional bila kredensial test tersedia di dump): buka
session login via HTTP internal container (curl POST ke route login dengan
akun test) — expected session dibuat di tabel `sessions` pada database hasil
restore dan response bukan error autentikasi. Bagian ini membuktikan session
driver database bekerja pada data hasil restore.

### 5. (Sesuai kebijakan runbook §8) Smoke tambahan

Untuk drill bulanan staging penuh, runbook juga mencakup restore attachment
volume (`rsync`/`tar` dari backup `storage/app` ke volume scratch `storage_scratch`
dengan struktur direktori sama) + smoke login/report/attachment download —
bagian itu berjalan di environment recovery staging dan berada di luar lingkup
drill lokal ini.

### 6. Cleanup

```bash
docker rm -f laravel-drill 2>/dev/null
docker stop postgres-scratch
docker volume rm postgres_scratch
docker network rm cmx-drill
```

Pastikan stack utama tidak tersentuh:

```bash
docker compose ps
```

Expected: semua service stack utama tetap `running`/`healthy` (drill tidak
menyentuh `postgres_data`).

## Template catatan drill (ADR-031 / test-strategy §7)

Catat setiap drill di tabel di bawah (JANGAN commit isi tabel hasil ke repo —
salin ke release record/ops log):

```text
Drill record:
- Date (UTC): ______
- Operator: ______
- Backup file: backups/postgres/backup-YYYYMMDD-HHMMSS.dump
- Duration: mulai ____ selesai ____ (total ____ menit)
- Outcome: SUCCESS / FAILED — detail: ____________________
  (pg_restore exit, row count migrations, /up HTTP code, login smoke)
- Catatan/issue: ______
```

Contoh hasil terisi (drill lokal 2026-10-06):

```text
- Date (UTC): 2026-10-06
- Operator: rnd (lokal)
- Backup file: backups/postgres/backup-20261006-<HHMMSS>.dump
- Duration: ±2 menit
- Outcome: SUCCESS — pg_restore exit 0; migrations count = 1;
  /up 200 dari container scratch; session login test membuat baris di tabel
  sessions; stack utama tetap healthy.
```
