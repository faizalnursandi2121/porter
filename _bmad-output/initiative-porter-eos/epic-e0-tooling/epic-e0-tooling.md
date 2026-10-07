---
tracker_id: ""
key: ""
type: epic
title: "Tooling baseline: branch, env, shadcn, module nav"
parent: initiative-porter-eos
covers: []
after: []
assignee: ""
risk: medium
---

# Tooling baseline: branch, env, shadcn, module nav

## Description

Group 0 of `Docs/PRD/tasks-task-list-baru.md`, delivered as one epic with one story per subtask (refs equal the subtask ids x.y). Scope: none (platform baseline). See the initiative envelope and the spec for the full contract.

## Outcome

The spec's capabilities for this group are live and verified — the epic's stories are the subtasks, each closed only by its tests and E2E per story-contract.md §5.

## Requirements

Subtask ids 0.x from `Docs/PRD/tasks-task-list-baru.md` are the stable requirement lines; each entry's `covers` cites its subtask id (epic 0 serves no CAP — platform baseline; CAP citations begin in epic 1). AC are carried verbatim in the story file at pull time.

## Done when

1. Every entry 0.* is built, its feature tests pass, and its story contract's integration+E2E step is closed (story-contract §5).
2. Module nav renders role-scoped links per ui-design.md section 3 (Sheet on mobile) and dashboard tile labels are English (ui-design.md section 7).
3. The group's slice is releasable to production via Dokploy: Pint clean, build green, no mock branches left.

## Boundaries

Only the modules named by subtasks 0.x. Touch points on other epics' data go through their published contracts; cross-epic needs are declared in the initiative breakdown's `after`.

## References

- parent — _bmad-output/initiative-porter-eos/initiative-porter-eos.md
- spec — _bmad-output/initiative-porter-eos/spec-porter/spec-porter.md (kernel + companions)
- tasks — Docs/PRD/tasks-task-list-baru.md, group 0
- method — _bmad-output/initiative-porter-eos/story-contract.md
- baseline UI — Docs/PRD/ui-design.md

## Notes

- Decision: epic 0 goes first as the tooling baseline; it has no upstream dependencies (2026-10-07).
- Tracer: entries 1-2 (branch, then env) — entry 5 renders the module nav per ui-design.md section 3 as the first visible slice.
- Decision: nav links at this baseline render as inert links; route targets arrive with epics 2-7, no disabled state needed (2026-10-07).
- Decision: subtask 0.0 (feature branch) is absorbed into entry 1 as its starter step, run by the builder (2026-10-07).
