---
tracker_id: ""
key: ""
type: epic
title: "Site master data and EOS placement"
parent: initiative-porter-eos
covers: [CAP-8, CAP-1]
after: []
assignee: ""
risk: medium
---

# Site master data and EOS placement

## Description

Group 3 of `Docs/PRD/tasks-task-list-baru.md`, delivered as one epic with one story per subtask (refs equal the subtask ids x.y). Scope: CAP-8, CAP-1. See the initiative envelope and the spec for the full contract.

## Outcome

The spec's capabilities for this group are live and verified — the epic's stories are the subtasks, each closed only by its tests and E2E per story-contract.md §5.

## Requirements

Subtask ids 3.x from `Docs/PRD/tasks-task-list-baru.md` are the stable requirement lines; each entry's `covers` cites its subtask id plus the CAP ids it serves. AC are carried verbatim in the story file at pull time.

## Done when

1. Every entry 3.* is built, its feature tests pass, and its story contract's integration+E2E step is closed (story-contract §5).
2. The group's FR strings appear verbatim in the UI (empty states, gate messages) per fr-catalog.md.
3. Production-ready on Dokploy path: Pint clean, build green, no mock branches left for this group.

## Boundaries

Only the modules named by subtasks 3.x. Touch points on other epics' data go through their published contracts; cross-epic needs are declared in the initiative breakdown's `after`.

## References

- parent — _bmad-output/initiative-porter-eos/initiative-porter-eos.md
- spec — _bmad-output/initiative-porter-eos/spec-porter/spec-porter.md (kernel + companions)
- tasks — Docs/PRD/tasks-task-list-baru.md, group 3
- method — _bmad-output/initiative-porter-eos/story-contract.md
- baseline UI — Docs/PRD/ui-design.md

## Notes

- Waits on epic 1, epic 2 because: schema/RBAC/data dependencies declared in the initiative tickets.toml.
- Tracer: entry 1 (site CRUD) — Administrator creates one site and it lists back.
- Decision: epic 3 entry 5 owns the shared audit-write helper contract (FR-46 row shape); epics 4-7 audit-writing entries consume it via story-contract section 3 (2026-10-07).
