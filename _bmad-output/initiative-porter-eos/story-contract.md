# Story Contract — PORTER v1

> Cara memecah epic menjadi story, memparalelkan frontend/backend dengan mock, dan menyatukannya dengan E2E. Berlaku untuk SEMUA epic (0–7) dan epic masa depan.
> Sumber: `spec-porter/` (kernel + companions). Jika dokumen ini bertentangan dengan spec/PRD, spec/PRD menang.

## 1. Struktur Wajib

```
Initiative (PORTER v1)
└── Epic = 1 grup build (0…7) dari spec-porter/delivery.md
    ├── E-1 Foundation (1.1–1.7)     ← TIDAK bisa diparalel: fondasi skema/helper/disk
    ├── E-2 Auth & RBAC (2.1–2.7)
    ├── E-3 Master & Placement (3.1–3.6)
    ├── E-4 Attendance (4.1–4.12)
    ├── E-5 Daily Report (5.1–5.13)
    ├── E-6 Inventory (6.1–6.7)
    └── E-7 Dashboard/Audit/Export (7.1–7.8)
         └── Story = 1 subtask (x.y) → STORY-E{g}-{yy}
              Contoh: STORY-E4-04a, STORY-E4-04b, STORY-E5-10
```

Aturan:
- **1 subtask task-list = 1 story.** Jangan menggabung dua subtask dalam satu story; jangan memecah satu subtask jadi dua story (kalau terasa terlalu besar, itu sinyal subtask-nya perlu direvisi di task list, bukan dipecah saat eksekusi).
- Setiap story **wajib** menyatakan: epic, FR yang dilayani, CAP yang disentuh, dan **kontrak antar-mukanya** (§3).
- Story "fondasi" (migration/model/route) = **contract story**: boleh jalan duluan dan menghasilkan kontrak yang dipakai mock.

## 2. Siklus Kehidupan Story

```
DRAFT → CONTRACTED → READY (frontend+backend boleh start paralel)
      → IN-PROG-FE / IN-PROG-BE   (jalur paralel, mock di tengah)
      → INTEGRATION (mock dilepas, sambung nyata)
      → E2E-PENDING → DONE
```

- `CONTRACTED`: kontrak API + shape props Inertia sudah ditulis dan disetujui (lihat §3). **Frontend TIDAK BOLEH mulai sebelum story berstatus CONTRACTED.**
- `DONE` hanya setelah: FE nyata + BE nyata + test feature lulus + E2E jalur happy & minimal 1 failure path lulus + Pint/`npm run build` bersih.

## 3. Kontrak Story (bagian WAJIB di setiap story file)

Setiap story file wajib punya blok `## Contract`. Ini perjanjian antara FE dan BE — ditulis SEKALI, diubah hanya lewat persetujuan pemilik produk (catat di memlog):

```markdown
# STORY-E4-06 — Clock-out gate on Submitted report

- Epic: E-4 Attendance · FR: FR-9a, FR-9b · CAP: CAP-2
- Depends: STORY-E5-04 (report model + status), STORY-E4-04a (check-in)

## Contract
- Route: POST /eos/attendance/check-out (name: attendance.check-out)
- Request: multipart { selfie: image, latitude: number, longitude: number, accuracy_m: number }
- Success response (Inertia redirect back): props.attendance = { status: 'Completed', checked_out_at: 'HH:mm TZ' }
- Failure paths (page props.errors / flash):
  - report not submitted → redirect route('eos.daily-report.current') + flash { notice: 'report_gate' }
  - already checked out  → flash { error: 'already_checked_out' }
  - validation fail      → errors { selfie | latitude | longitude }
- BE exposes (before real impl): fake controller returning the shapes above (fixed data)
- FE consumes: Inertia form + ui-design §5.1 states; no other data fetching

## Tests
- BE feature: gate blocks non-submitted (redirect target asserted), double check-out rejected, same-day rule
- FE: states render per mock fixture (offline banner, retry preserved)
- E2E (integration): login EOS → check-in → submit report → check-out → status Completed in history
```

Aturan kontrak:
- Route + method + nama route + bentuk request + SEMUA path gagal + bentuk props sukses = wajib ada.
- Error memakai **kode stabil** (`report_gate`, `already_checked_out`, `already_checked_in`) — FE menguji kode, bukan kalimat. Kalimat copy UI hidup di `lang/en/`.
- Kontrak perubahan setelah READY = hentikan kedua jalur, revisi kontrak, baru lanjut.

## 4. Paralel FE/BE dengan Mock

Setelah `CONTRACTED`, satu story dibuka dua tiket kerja:

| Jalur | Kerjakan | Definisi selesai jalur |
|---|---|---|
| **BE** | Migration (bila perlu) + action/service + controller nyata + FormRequest + **test feature** (test hitung: kode error, redirect target, isi DB) | Feature test lulus; endpoint nyata; mock dimatikan |
| **FE** | Halaman Inertia + komponen + state (loading/empty/error) + **test komponen/fixture** | Semua state ter-render dari fixture mock; tidak ada fetch di luar kontrak |

- **Mock BE untuk FE**: `routes/web.php` blok `if (env('MOCK_MODE'))` — controllers palsu yang mengembalikan props sesuai kontrak dengan data fixture tetap (di `tests/Fixtures/` atau `database/factories`). Mock hidup di repo, dicabut saat integrasi. Tidak ada MSW/interceptor pihak ketiga — Inertia + fixture saja.
- **Mock FE untuk BE** (bila perlu): fixture JSON dari kontrak dipakai BE untuk uji bentuk respons.
- Paralel aman karena kedua jalur hanya berpegang pada blok `## Contract`, bukan pada kode satu sama lain.

## 5. Integrasi & E2E (penutup story)

Urutan wajib di akhir story:

1. **Lepas mock**: hapus cabang `MOCK_MODE` untuk story itu (jangan dibiarkan menggantung), sambungkan FE ↔ BE nyata.
2. **Feature tests BE** lulus penuh (jumlah & kode error sesuai kontrak).
3. **E2E happy path** (Pest + browser / Playwright): alur pengguna nyata menurut ui-design — contoh E-4: login → check-in (selfie+GPS) → isi laporan → submit → check-out → riwayat menunjukkan `Completed`.
4. **E2E minimal 1 failure path**: satu dari — double check-in ditolak; gate clock-out mengarahkan ke laporan; upload gagal → data bertahan (FR-12).
5. **Smoke manual layar kecil** (HP/emulator) untuk story UI-lapangan.
6. Tandai `DONE`; catat 1 baris di memlog epic.

## 6. Aturan Paralel Antar-Epic

- Epic `0, 1` = serial, fondasi semua.
- Epic `2, 3` boleh paralel setelah `1` (beda domain, kontrak jelas).
- Epic `4, 5, 6` boleh paralel setelah `2, 3`, **kecuali** pasangan yang saling kunci: STORY-E4-06 (gate) butuh kontrak status report dari E-5 — kontraknya dibuat lebih awal di masa CONTRACTED, implementasi menyusul.
- Epic `7` terakhir (memakai semua data + endpoint foto).
- Maksimum paralel yang disarankan: 2 jalur (1 BE + 1 FE) per epic — lebih dari itu, biaya koordinasi kontrak melebihi manfaatnya di skala ini.

## 7. Template Story File

Path: `_bmad-output/initiative-porter-eos/stories/E{g}/STORY-E{g}-{yy}.md`

```markdown
# STORY-E{g}-{yy} — {judul singkat}
status: DRAFT | CONTRACTED | READY | IN-PROG-FE | IN-PROG-BE | INTEGRATION | DONE
epic: E-{g} · fr: FR-x, FR-y · cap: CAP-n · depends: STORY-…

## AC (dari subtask task list, verbatim)
{salin persis butir task list x.y — jangan parafrase}

## Contract
{blok wajib §3}

## FE track
{halaman/komponen, state yang dibangun, fixture yang dipakai}

## BE track
{migration? service? controller, FormRequest rules, audit event}

## Tests
- BE feature: …
- FE: …
- E2E happy: …
- E2E failure: …

## Definition of Done
- [ ] Mock dicabut, FE↔BE nyata
- [ ] BE feature tests lulus
- [ ] E2E happy + failure path lulus
- [ ] Pint --dirty bersih; npm run build sukses
- [ ] Smoke layar kecil (bila UI lapangan)
- [ ] Empty/error state pakai string FR verbatim
- [ ] Audit event tercatat (bila termasuk FR-46)
- [ ] Komentar kode: hanya WHY (FR/BR), tanpa slop — AGENTS.md
```
