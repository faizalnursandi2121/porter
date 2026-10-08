# Stack — PORTER v1

Locked platform decisions (PRD "Pertimbangan Teknis" + AGENTS.md). Implementation prescription lives here so kernel intents stay WHAT-only.

## Core

- **Framework**: Laravel 13 full-stack; PHP 8.3. Follow Laravel Boost guidelines and `.ai/rules` when present.
- **Frontend**: Inertia v3 + React 19 + Tailwind 4 + shadcn/ui (`resources/js/components/ui/`). Never hand-roll a component shadcn provides; install missing ones via `npx shadcn@latest add <name>`.
- **Routing**: server-side routing through Inertia only; no separate REST API layer. Wayfinder (`@/routes`, `@/actions`) when available, else `route()`.
- **Auth**: session cookie, server-side. RBAC 4 roles (EOS, Supervisi, HR, Administrator) enforced by middleware + policies on every request; UI hiding is never the enforcement point. Administrator ⊇ Supervisi. Session lifetime: starter-kit default 120 min (config, decision 14).
- **Database**: relational; runtime data (accounts, attendance, reports, inventory, stock, master data, audit). Store timestamps UTC; convert in application layer from site `zona_waktu`. History-bearing tables: assignments, template versions, stock movements. Index filtered columns (report date, site_id, user_id, status) for 200+ sites.

## Files & media

- **Photo storage**: private disk outside public root; access only via app endpoints that check role (FR-48), write audit row (FR-49), then stream. Thumbnail derivatives when available.
- **Selfie watermark (FR-5b)**: server-side at receive; Laravel `Image` facade (Intervention v4, GD driver); text line `{site name} · {dd/mm/YYYY HH:mm} · {lat, lng} (±{accuracy} m)`; bundle TTF font (e.g. DejaVuSans) under `resources/fonts/` — never OS fonts.
- **Upload efficiency**: compress photos client-side before upload (slow-connection design), minimal asset sizes, clear upload progress indicator (PRD design consideration 3).
- **Export**: server-generated Excel and PDF per active filter, with sane size/time limits; PDF simple letterhead + table.

## UI conventions (summary — full baseline is `ui-design.md`, adopted companion)

- Launcher home for all roles; no sidebar; module nav in `LauncherHeader` (mobile Sheet/scroll); breadcrumb + back inside modules.
- Mobile-first, 2-col tile grid on phones, touch targets ≥ 44px, dark mode via Tailwind tokens only, icons `lucide-react` only.
- Copy UI English via `lang/en/` + `__()`; product terms locked (EOS, Supervisi, HR, Administrator, Daily Report, Inventory).
- Status badges: Submitted=default, Draft=secondary, Needs Revision=destructive, Completed=outline.
- Lists: `Table` desktop / Card list mobile, filter bar + reset, pagination, verbatim FR empty states, `Skeleton` loading.
- Dashboard charts: `recharts` preferred; line for trends, bar for cross-site comparison.
- Dates: site timezone + tz label (`14:05 WIB`), `Intl.DateTimeFormat('en-GB', …)`; numbers locale `en-GB`.

## Ops

- Deployment: GitHub → Dokploy; no separate staging environment (decision 15).
- Daily automated backup of DB + photos; restore procedure tested before go-live.
- Performance: dashboard < 3 s on common filters; aggregation via summary tables or materialized views refreshed periodically is acceptable.
- Passwords strongly hashed; login throttling; input validation against injection.
