---
title: '0.4 — ui-design.md baseline reference + install shadcn components'
type: 'chore'
ticket: '4'
created: '2026-10-07'
status: 'built'
baseline_revision: 'f458856'
route: 'oneshot'
route_source: 'auto'
risk: 'medium'
review: 'quick'
review_source: 'pinned'
lenses_ran: ['quick']
review_loop_iteration: 0
context: []
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Two gaps: (a) `Docs/PRD/ui-design.md` must be on record as the layout/pattern baseline for every following UI task, and (b) six shadcn components the upcoming UI stories need (`table`, `calendar`, `progress`, `form`, `alert`, `timeline`) are missing from `resources/js/components/ui/` — only `alert.tsx` of that list exists today.

**Approach:** Install the missing components via the shadcn CLI against the existing `components.json` (new-york style, lucide icons) so they land with the project's established aliases and styling; record the baseline reference where the repo expects UI decisions (AGENTS.md already names ui-design.md as binding; add no second convention).

</frozen-after-approval>

## Implementation Notes

(oneshot route: CLI-driven component installs + generated files; no hand-written logic.)

- `npx shadcn@latest add table calendar progress form alert` — 4 new files (`table/calendar/form/progress.tsx`) + refreshed `alert/button/label` to registry v4 conventions (package `cn`, `Slot.Root`, new `xs`/`icon-*` variants). Build green.
- `timeline` is absent from every shadcn registry style (404 probed on `new-york-v4`, `new-york`, `default`, `new-york-v2`; v4 index lists 63 items, no timeline). Cross-checked via Exa + GitHub API: request open since 2023 (Discussion #4285, 211👍); PR #9188 (v4 timeline) still unmerged; older PRs #3374/#5897/#8893 closed unmerged; issues #7371/#6993 closed by stale-bot. The subtask targeted a component that never shipped upstream.
- User decision (2026-10-07, chat "1"): copy the shadcn-style community timeline from Creative Tim's registry (`apps/www/registry/creative-tim/ui/timeline.tsx`, MIT) into `resources/js/components/ui/timeline.tsx`; single import adaptation `@/lib/utils` → `cn` (v4 convention, 7 of 29 ui files already use it). No new runtime dependency. Recorded as PRD "Keputusan Produk" #16.
- `npm run build` green after timeline install (9.6s, 2344 modules).

## Review Triage Log

- 2026-10-07 quick lens cancelled by user (subagent latency >8 min on a small diff); replaced by direct self-checks: timeline has zero `forwardRef` (plain functions, React 19-safe), exports namespaced `Timeline*` (no collisions), no existing consumer touched, `"use client"` inert under Vite, colors all via theme tokens (`bg-primary`, `bg-destructive`, …) — no hardcoded palette outside the token system. Build green (`npm run build` 9.6s).

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `ls resources/js/components/ui/` -- expected: `table.tsx`, `calendar.tsx`, `progress.tsx`, `form.tsx`, `alert.tsx`, `timeline.tsx` all present
- `npm run build` -- expected: green build (generated components compile with the app's TS/Tailwind setup)
- `git status --porcelain` -- expected: only intended file changes (new ui components, package.json/lock updates for new radix/react-day-picker deps)

**Manual checks:**
- `- [ ] 0.4` → `- [x]` in `Docs/PRD/tasks-task-list-baru.md`; parent `- [ ] 0.0` stays open (0.5 pending).
- ui-design.md baseline reference: no new layout convention documented anywhere — AGENTS.md + ui-design.md remain the single source.
