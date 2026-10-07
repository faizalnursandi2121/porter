# PORTER — Project Context (authoritative)

Project ini membangun **PORTER (Portal Operasional Terpadu)**: aplikasi Laravel 13 + Inertia + React (shadcn/ui) untuk pemantauan operasional EOS di site Sekolah Rakyat.

## Sumber Kebenaran Dokumen

- **PRD aktif**: `Docs/PRD/prd-porter-portal-operasional-terpadu.md` — 58 FR + 15 keputusan produk terkunci (bagian "Keputusan Produk (Hasil Konfirmasi)").
- **Execution plan**: `Docs/PRD/tasks-task-list-baru.md` — 68 subtask berurutan (0.0–7.8). Kerjakan subtask sesuai urutan; tandai `- [x]` hanya setelah test lulus dan perilaku terverifikasi.
- **Baseline UI**: `Docs/PRD/ui-design.md` — layout launcher semua role, pola halaman list/form/detail, pemetaan komponen shadcn, keputusan visual terkunci. Semua task UI WAJIB mengikutinya; jangan membuat pola layout baru di luar dokumen itu.
- **`Docs/legacy/**` = ARSIP** baseline lama (5 role, stack Go/Redis, geofence gerbang — sudah tidak berlaku). JANGAN jadikan requirement atau kutip sebagai kebenaran; jika bertentangan dengan PRD aktif, **PRD aktif yang menang**. Boleh dibaca sebagai bahan mentah teknis. Peta dokumen: `Docs/README.md`.
- Jika implementasi menemukan kasus yang tidak tercover PRD: jangan putuskan diam-diam — laporkan ke user, usulkan keputusan, lalu tambahkan ke bagian "Keputusan Produk" di PRD setelah disetujui.

## Bahasa

- **Komunikasi dengan user: Bahasa Indonesia.**
- **Implementasi kode: Bahasa Inggris** — nama class/method/variable, komentar kode, pesan commit, nama route, nama kolom DB, enum value, dan key pesan error dalam Bahasa Inggris.
- **Copy UI (teks tampil di layar): Bahasa Inggris** — label tombol, pesan validasi, empty state, heading. Simpan sebagai translation string (mis. `lang/en/*.json` + helper `__()`) agar mudah dilokalkan kelak.
- Istilah produk terkunci: EOS, Supervisi, HR, Administrator, Daily Report, Inventory — jangan buat sinonim.

## Frontend: shadcn/ui (WAJIB)

- Stack UI: Laravel 13 starter kit + Inertia v3 + React 19 + **shadcn/ui** (komponen ada di `resources/js/components/ui/`).
- SELALU gunakan komponen shadcn yang tersedia (Button, Card, Dialog, Table, Form, Select, Toast/Sonner, dll.) — JANGAN membuat komponen UI custom dari nol jika shadcn sudah menyediakan.
- Sebelum membuat komponen baru, cek dulu `resources/js/components/`; jika belum ada, generate via shadcn CLI (`npx shadcn@latest add <component>`).

## Prinsip Desain & Kode (WAJIB — mencegah codebase jadi sampah)

Terapkan pada SEMUA kode yang ditulis, tanpa kecuali:

- **KISS**: pilih solusi paling sederhana yang memenuhi requirement, acceptance criteria, security boundary, dan constraint saat ini. Jangan menambah layer, pattern, indirection, atau konfigurasi bila solusi lokal yang jelas cukup.
- **YAGNI**: jangan membangun capability untuk kemungkinan kebutuhan masa depan. Implementasikan hanya scope yang disetujui pada story/spec/AC saat ini. Jangan menambah kolom, endpoint, opsi config, atau "biar nanti berguna".
- **DIY**: gunakan primitive, library, convention, dan capability yang sudah ada di repository sebelum menambah dependency, framework, wrapper, atau platform baru. Tambahan dependency atau perubahan platform wajib Ask First (tanya user, jangan install sendiri).
- **DRY**: satu business rule, security rule, validation invariant, permission, event contract, atau schema knowledge harus punya satu source of truth. Jangan copy-paste knowledge yang akan berubah bersama.
- **DRY bukan larangan semua duplikasi kode.** Duplikasi lokal dengan intent, lifecycle, atau alasan perubahan yang berbeda boleh dipertahankan bila abstraction justru memperumit kode atau belum memiliki caller nyata.
- **Rule of three**: jangan extract abstraction hanya karena satu atau dua blok tampak mirip. Extract setelah minimal tiga penggunaan nyata, atau lebih awal hanya bila ada shared invariant security, transaction/concurrency, protocol, generated-code boundary, atau ownership domain yang konkret.

### Aturan Komentar (anti-comment-slop)

Codebase ini hampir tanpa komentar — JAGA tetap begitu. Komentar adalah bau kode, bukan dokumentasi:

- **DILARANG** komentar narasi yang menjelaskan APA yang kode lakukan — kalau perlu komentar itu, ganti nama variable/method/extract method. Contoh terlarang: `// Loop through users`, `// Check if user is admin`, `// Set up theme listener`, `/** Getter for name */`.
- **BOLEH** (dan diharapkan) komentar yang menjelaskan KENAPA — keputusan bisnis, constraint non-obvious, workaround yang tidak jelas dari kode: `// BR-03: DB unique constraint is the enforcement point for one-attendance-per-date`, `// Fortify throttles by email+IP, hence identifier differs`.
- Komentar yang merujuk nomor FR/BR dari PRD adalah bentuk yang diharapkan (`// FR-9a: gate clock-out on submitted report`).
- **PHPDoc pada method publik tetap wajib** sesuai aturan PHP di bawah (kontrak API + array shape) — itu bukan komentar naratif. PHPDoc yang mengulang nama method (`/** Get the name */`) terlarang.
- Jangan tinggalkan komentar TODO/FIXME/HACK tanpa persetujuan user; kalau ada yang belum selesai, laporkan di chat, jangan dibekas di kode.
- Jangan menulis kode "contoh", "fallback", "dead code", atau fitur yang di-comment-out. Hapus, jangan dikomentari.

## MCP Servers — WAJIB dipakai

- **Laravel Boost** (`database-schema`, `database-query`, `search-docs`, `get-absolute-url`, `browser-logs`, `last-error`, `read-log-entries`): SELALU pakai untuk inspeksi DB sebelum migration, mencari dokumentasi versi-spesifik, dan debugging. Prioritaskan di atas baca file manual / raw SQL.
- **Context7** (`resolve-library-id`, `query-docs`): pakai untuk mencari dokumentasi library/framework (React, Inertia, shadcn, Tailwind, Fortify) sebelum menulis API yang belum pasti — jangan mengandalkan ingatan training data.
- **Exa** (`web_search_exa`, `get_code_context_exa`, `crawling_exa`): pakai untuk mencari sumber kebenaran eksternal (contoh kode nyata, solusi error, best practice) sebelum mendebat pendekatan.
- **Codebase Memory** (`search_graph`, `get_architecture`, `index_status`, `trace_path`): pastikan project ini ter-index (`list_projects`/`index_status` dulu; `index_repository` bila belum/di luar tanggal) lalu gunakan graph tools untuk navigasi struktur kode — cari definisi/caller/callee lewat `search_graph`/`trace_path`, BUKAN grep buta.
- **gh_grep** (`searchGitHub`): cari contoh implementasi nyata di repo GitHub publik saat butuh referensi pola (mis. integrasi kamera/GPS di PWA, pola form shadcn kompleks, struktur test Pest). Prioritas setelah Context7/Exa; jangan copy mentah — sesuaikan dengan konvensi project ini.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.3. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
