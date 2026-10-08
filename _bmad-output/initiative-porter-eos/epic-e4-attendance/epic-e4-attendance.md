---
tracker_id: ""
key: ""
type: epic
title: "Attendance with selfie+GPS, watermark, gates, PWA"
parent: initiative-porter-eos
covers: [CAP-2, CAP-3, CAP-9, CAP-10]
after: []
assignee: ""
risk: high
---

# Attendance with selfie+GPS, watermark, gates, PWA

## Description

Group 4 of `Docs/PRD/tasks-task-list-baru.md`, delivered as one epic with one story per subtask (refs equal the subtask ids x.y). Scope: CAP-2, CAP-3, CAP-9, CAP-10. See the initiative envelope and the spec for the full contract.

## Outcome

The spec's capabilities for this group are live and verified — the epic's stories are the subtasks, each closed only by its tests and E2E per story-contract.md §5.

## Requirements

Subtask ids 4.x from `Docs/PRD/tasks-task-list-baru.md` are the stable requirement lines; each entry's `covers` cites its subtask id plus the CAP ids it serves. AC are carried verbatim in the story file at pull time.

## Done when

1. Every entry 4.* is built, its feature tests pass, and its story contract's integration+E2E step is closed (story-contract §5).
2. The group's FR strings appear verbatim in the UI (empty states, gate messages) per fr-catalog.md.
3. Production-ready on Dokploy path: Pint clean, build green, no mock branches left for this group.

## Boundaries

Only the modules named by subtasks 4.x. Touch points on other epics' data go through their published contracts; cross-epic needs are declared in the initiative breakdown's `after`.

## References

- parent — _bmad-output/initiative-porter-eos/initiative-porter-eos.md
- spec — _bmad-output/initiative-porter-eos/spec-porter/spec-porter.md (kernel + companions)
- tasks — Docs/PRD/tasks-task-list-baru.md, group 4
- method — _bmad-output/initiative-porter-eos/story-contract.md
- baseline UI — Docs/PRD/ui-design.md

## Notes

- Waits on epic 1, 2, 3 because: schema/RBAC/data dependencies declared in the initiative tickets.toml.
- Tracer: entry 1 (attendance page, camera+GPS) — thinnest FE+BE path; entries 1a/1b deepen it, 4-7 add the gates.
- Handoff: photos go to the private disk through the shared uploader; viewing stays behind epic 7's audited FR-48 endpoint (entry 6) — interim pages must not bypass it.
- Handoff: entry 6's clock-out gate consumes epic 5's report-status contract (Draft/Submitted/Needs Revision per CAP-4); shapes fixed early via story-contract section 3 — no hard after, the E2E joins both epics.
