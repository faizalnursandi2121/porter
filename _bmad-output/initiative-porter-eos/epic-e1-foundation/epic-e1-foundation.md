---
tracker_id: ""
key: ""
type: epic
title: "Schema, models, seeders, timezone helper, private disk"
parent: initiative-porter-eos
covers: [CAP-10]
after: []
assignee: ""
risk: medium
status: done
---

# Schema, models, seeders, timezone helper, private disk

## Description

Group 1 of `Docs/PRD/tasks-task-list-baru.md`, delivered as one epic with one story per subtask (refs equal the subtask ids x.y). Scope: CAP-10 (foundation for all). See the initiative envelope and the spec for the full contract.

## Outcome

The spec's capabilities for this group are live and verified — the epic's stories are the subtasks, each closed only by its tests and E2E per story-contract.md §5.

## Requirements

Subtask ids 1.x from `Docs/PRD/tasks-task-list-baru.md` are the stable requirement lines; each entry's `covers` cites its subtask id plus the CAP ids it serves. AC are carried verbatim in the story file at pull time.

## Done when

1. Every entry 1.* is built, its feature tests pass, and its story contract's integration+E2E step is closed (story-contract §5).
2. The group's FR strings appear verbatim in the UI (empty states, gate messages) per fr-catalog.md.
3. Production-ready on Dokploy path: Pint clean, build green, no mock branches left for this group.

## Boundaries

Only the modules named by subtasks 1.x. Touch points on other epics' data go through their published contracts; cross-epic needs are declared in the initiative breakdown's `after`.

## References

- parent — _bmad-output/initiative-porter-eos/initiative-porter-eos.md
- spec — _bmad-output/initiative-porter-eos/spec-porter/spec-porter.md (kernel + companions)
- tasks — Docs/PRD/tasks-task-list-baru.md, group 1
- method — _bmad-output/initiative-porter-eos/story-contract.md
- baseline UI — Docs/PRD/ui-design.md

## Notes

- Waits on epic 0 because: the branch and configured local env must exist before migrations run.
- Tracer: entry 1 (roles/users/sites schema) — migrations run and seeders produce a role row, proving the schema path end to end.
