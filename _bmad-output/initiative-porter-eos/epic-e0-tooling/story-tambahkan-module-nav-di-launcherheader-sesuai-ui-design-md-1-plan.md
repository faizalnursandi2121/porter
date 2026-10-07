---
title: '0.5 — Module nav in LauncherHeader + English dashboard tile labels'
type: 'feature'
ticket: '5'
created: '2026-10-07'
status: 'built'
baseline_revision: '998ae5f'
route: 'oneshot'
route_source: 'auto'
risk: 'medium'
review: 'quick'
review_source: 'pinned'
lenses_ran: []
review_loop_iteration: 0
context: []
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Per ui-design.md §1–§3, the persistent `LauncherHeader` must carry a role-scoped module nav row (desktop links; mobile → Sheet), and dashboard tile labels are still mixed-language (`Presensi`, `Laporan Harian`, `Inventaris`, `Kehadiran Site`, `Review Laporan`, `Inventaris Site`, `Master Site`, `Assignment EOS`, `Analitik`, `Manajemen User`) while UI copy must be English.

**Approach:** ~~Add a module nav row to `LauncherHeader`~~ (amended 2026-10-07 by product owner mid-implementation: launcher pattern stays as existing icon tiles on the dashboard; NO menu row in the header — recorded as PRD Keputusan Produk #17, ui-design.md §1.2/§2.1 amended). Final scope: English tile labels only; `LauncherHeader` untouched.

</frozen-after-approval>

## Code Map

- `resources/js/components/launcher-header.tsx` -- add nav row below the existing 14px bar: desktop `hidden md:flex` links; mobile hamburger button → `Sheet` with the same links; consume `auth.role`.
- `resources/js/pages/dashboard.tsx` -- English `title`s in `moduleTiles` (`Attendance`, `Daily Report`, `Inventory`, `Master Data`, `Assignments`, `Analytics`, `Users`); tiles remain the role-gating source.
- `resources/js/layouts/settings/layout.tsx` -- passes `searchItems={[]}`; keeps working with the new optional props.
- `resources/js/types/navigation.ts` -- `NavItem` reused for nav entries; no new types.
- Existing conventions: `Sheet` already in `resources/js/components/ui/`; role values `EOS | SUPERVISOR | HR | MANAGER | SUPER_ADMIN` (`types/auth.ts`).

## Tasks & Acceptance

**Execution:**
- [x] `resources/js/pages/dashboard.tsx` -- English titles in `moduleTiles` (`Attendance`, `Daily Report`, `Inventory`, `Master Data`, `Assignments`, `Analytics`, `Users`) -- rationale: UI copy must be English; tiles stay the single nav surface.
- [x] `Docs/PRD/ui-design.md` -- amend §1.2 + §2.1: module nav = launcher tiles, header unchanged -- rationale: baseline doc must match the approved pattern.
- [x] `resources/js/components/launcher-header.tsx` -- reverted to original (no nav row) -- rationale: product-owner decision mid-implementation.

**Acceptance Criteria:**
- Given any role, when dashboard tiles render, then tile labels are English (canonical module names).
- Given the dashboard, when it renders, then `LauncherHeader` is unchanged from `998ae5f` (logo, Ctrl+K, notifications, theme, avatar — no menu row).
- Given the settings layout, when it renders, then nothing breaks (header untouched).

## Implementation Notes

- Mid-implementation correction (2026-10-07): first pass added a nav row + mobile Sheet to `LauncherHeader` (built green, 41 tests passed) — user stopped it: "launcher itu module menu icon seperti existing code, jangan bikin menu di top". Header changes reverted via `git checkout`; only the English tile labels remain. PRD Keputusan Produk #17 + ui-design.md §1.2/§2.1 amended to lock the tiles-only pattern.

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `npm run build` -- expected: green
- `php artisan test --compact` (existing suite) -- expected: no regressions
- Browser smoke: dashboard as EOS (`/dashboard`) -- expected: nav row shows `Attendance · Daily Report · Inventory`; mobile viewport (<768px) shows hamburger → Sheet with same links

**Manual checks:**
- Tile labels English; nav matches ui-design §3 matrix per role.
- `- [ ] 0.5` → `- [x]`; parent `- [ ] 0.0` closes when 0.5 verified.
