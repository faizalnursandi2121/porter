---
tracker_id: ""
key: ""
type: epic
title: "KPI dashboard, export, audit log, photo access, ops docs"
parent: initiative-porter-eos
covers: [CAP-6, CAP-7]
after: []
assignee: ""
risk: high
---

# KPI dashboard, export, audit log, photo access, ops docs

## Description

Group 7 of `Docs/PRD/tasks-task-list-baru.md`, delivered as one epic with one story per subtask (refs equal the subtask ids x.y). Scope: CAP-6, CAP-7. See the initiative envelope and the spec for the full contract.

## Outcome

The spec's capabilities for this group are live and verified — the epic's stories are the subtasks, each closed only by its tests and E2E per story-contract.md §5.

## Requirements

Subtask ids 7.x from `Docs/PRD/tasks-task-list-baru.md` are the stable requirement lines; each entry's `covers` cites its subtask id plus the CAP ids it serves. AC are carried verbatim in the story file at pull time.

## Done when

1. Every entry 7.* is built, its feature tests pass, and its story contract's integration+E2E step is closed (story-contract §5).
2. The group's FR strings appear verbatim in the UI (empty states, gate messages) per fr-catalog.md.
3. Production-ready on Dokploy path: Pint clean, build green, no mock branches left for this group.

## Boundaries

Only the modules named by subtasks 7.x. Touch points on other epics' data go through their published contracts; cross-epic needs are declared in the initiative breakdown's `after`.

## References

- parent — _bmad-output/initiative-porter-eos/initiative-porter-eos.md
- spec — _bmad-output/initiative-porter-eos/spec-porter/spec-porter.md (kernel + companions)
- tasks — Docs/PRD/tasks-task-list-baru.md, group 7
- method — _bmad-output/initiative-porter-eos/story-contract.md
- baseline UI — Docs/PRD/ui-design.md

## Notes

- Waits on epic 1, 2, 3, 4, 5, 6 because: schema/RBAC/data dependencies declared in the initiative tickets.toml.
- Tracer: entry 1 (dashboard filters) — KPI cards render from data the earlier epics already wrote.
- Decision: the audited FR-48 photo-serving endpoint is owned by entry 6; epics 4-6 write photos to the private disk via the shared uploader and never serve them directly (2026-10-07).
- Decision: ops ownership resolved (owner, 2026-10-07) — entries 9-11 added outside the 1:1 subtask set: 7.9 CI, 7.10 Dokploy deploy, 7.11 daily backup + tested restore (agent builds the pipeline; a person supplies credentials and executes the drill). Entry 11 closes initiative Done-when #3. Epic total is now 11 entries, initiative 71.
