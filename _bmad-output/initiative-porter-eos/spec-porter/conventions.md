# Conventions — PORTER v1

Non-negotiable repo rules (from AGENTS.md) that every build session must follow.

> **AGENTS.md remains the governing repo constitution** (document map, language policy, shadcn rule, code principles, MCP usage, Laravel Boost guidelines). This spec supplements it; it never overrides it. When in doubt, AGENTS.md + the PRD win.

## Language

- Communication with product owner: Bahasa Indonesia.
- Code in English: class/method/variable names, code comments, commit messages, route names, DB column names, enum values, error message keys.
- UI copy in English, stored as translation strings (`lang/en/*.json` + `__()`).
- Locked product terms: EOS, Supervisi, HR, Administrator, Daily Report, Inventory — no synonyms.

## Comments

- Codebase stays nearly comment-free. No narrative comments (`// check if user is admin` is forbidden) — rename or extract instead.
- Allowed and expected: WHY comments citing PRD rules (`// FR-9a: gate clock-out on submitted report`, `// BR-03: DB unique constraint is the enforcement point...`).
- PHPDoc on public methods is required (API contract + array shapes) — not narrative comment. No TODO/FIXME/HACK without user approval; no example/dead/commented-out code.

## Design & code principles

- KISS: pilih solusi paling sederhana yang memenuhi requirement, acceptance criteria, security boundary, dan constraint saat ini. Jangan menambah layer, pattern, indirection, atau konfigurasi bila solusi lokal yang jelas cukup.
- YAGNI: jangan membangun capability untuk kemungkinan kebutuhan masa depan. Implementasikan hanya scope yang disetujui pada story/spec/AC saat ini — tidak ada kolom, endpoint, atau opsi config "biar nanti berguna".
- DIY: gunakan primitive, library, convention, dan capability yang sudah ada di repository sebelum menambah dependency, framework, wrapper, atau platform baru. Tambahan dependency atau perubahan platform wajib Ask First (tanya pemilik produk, jangan install sendiri).
- DRY: satu business rule, security rule, validation invariant, permission, event contract, atau schema knowledge harus punya satu source of truth. Jangan copy-paste knowledge yang akan berubah bersama.
- DRY bukan larangan semua duplikasi kode: duplikasi lokal dengan intent, lifecycle, atau alasan perubahan yang berbeda boleh dipertahankan bila abstraction justru memperumit kode atau belum memiliki caller nyata.
- Rule of three: jangan extract abstraction hanya karena satu atau dua blok tampak mirip. Extract setelah minimal tiga penggunaan nyata, atau lebih awal hanya bila ada shared invariant security, transaction/concurrency, protocol, generated-code boundary, atau ownership domain yang konkret.

### Aturan Komentar (anti-comment-slop)

Codebase ini hampir tanpa komentar — JAGA tetap begitu. Komentar adalah bau kode, bukan dokumentasi:

- **DILARANG** komentar narasi yang menjelaskan APA yang kode lakukan — kalau perlu komentar itu, ganti nama variable/method atau extract method. Contoh terlarang: `// Loop through users`, `// Check if user is admin`, `// Set up theme listener`, `/** Getter for name */`.
- **BOLEH** (dan diharapkan) komentar yang menjelaskan KENAPA — keputusan bisnis, constraint non-obvious, workaround: `// BR-03: DB unique constraint is the enforcement point for one-attendance-per-date`.
- Komentar yang merujuk nomor FR/BR dari PRD adalah bentuk yang diharapkan (`// FR-9a: gate clock-out on submitted report`).
- **PHPDoc pada method publik tetap wajib** (kontrak API + array shape) — itu bukan komentar naratif. PHPDoc yang mengulang nama method (`/** Get the name */`) terlarang.
- Jangan tinggalkan TODO/FIXME/HACK tanpa persetujuan pemilik produk; yang belum selesai dilaporkan di chat, bukan dibekas di kode.
- Jangan menulis kode contoh, fallback, dead code, atau fitur yang di-comment-out. Hapus.

## Workflow

- Use existing shadcn components; check `resources/js/components/` first.
- Laravel Boost MCP for DB inspection, docs search, debugging; Context7/Exa/gh_grep before debating approaches; codebase-memory graph tools for navigation.
- `php artisan make:` for new files; factories + seeders for models; Pest for tests (`php artisan make:test --pest`).
- After PHP changes: `vendor/bin/pint --dirty --format agent`.
- Run narrowest tests per change; full suite (`php artisan test --compact`) on request.
- Ask before destructive commands or deleting unrelated code.

## Test policy

- Feature tests for behavior + important failure modes; every list has empty-state coverage per FR-26/33/45 strings.
- Critical business rules (double check-in, report gate before check-out, double check-out, number allocation, photo access) must have automated tests — PRD marks these mandatory.
- Faker via `fake()`; use factories and their custom states; feature tests preferred over unit tests except for isolated logic (timezone helper, number allocator, photo validation).
