---
tracker_id: ""
key: ""
type: epic
title: "Daily Report: template, form, drafts, numbering, revision, reopen"
parent: initiative-porter-eos
covers: [CAP-4, CAP-7]
after: []
assignee: ""
risk: high
---

# Daily Report: template, form, drafts, numbering, revision, reopen

## Description

Group 5 of `Docs/PRD/tasks-task-list-baru.md`, delivered as one epic with one story per subtask (refs equal the subtask ids x.y). Scope: CAP-4, CAP-7. See the initiative envelope and the spec for the full contract.

## Outcome

The spec's capabilities for this group are live and verified — the epic's stories are the subtasks, each closed only by its tests and E2E per story-contract.md §5.

## Requirements

Subtask ids 5.x from `Docs/PRD/tasks-task-list-baru.md` are the stable requirement lines; each entry's `covers` cites its subtask id plus the CAP ids it serves. AC are carried verbatim in the story file at pull time.

## Done when

1. Every entry 5.* is built, its feature tests pass, and its story contract's integration+E2E step is closed (story-contract §5).
2. The group's FR strings appear verbatim in the UI (empty states, gate messages) per fr-catalog.md.
3. Production-ready on Dokploy path: Pint clean, build green, no mock branches left for this group.

## Boundaries

Only the modules named by subtasks 5.x. Touch points on other epics' data go through their published contracts; cross-epic needs are declared in the initiative breakdown's `after`.

## References

- parent — _bmad-output/initiative-porter-eos/initiative-porter-eos.md
- spec — _bmad-output/initiative-porter-eos/spec-porter/spec-porter.md (kernel + companions)
- tasks — Docs/PRD/tasks-task-list-baru.md, group 5
- method — _bmad-output/initiative-porter-eos/story-contract.md
- baseline UI — Docs/PRD/ui-design.md

## Notes

- Waits on epic 1, 2, 3 because: report schema and RBAC, plus site/EOS names from epic 3 for report binding and list filters.
- Tracer: entry 1 (template CRUD) — a template version created by entry 1 is what the form in entry 3 consumes.
- Handoff: photos go to the private disk through the shared uploader; viewing stays behind epic 7's audited FR-48 endpoint (entry 6) — interim pages must not bypass it.
- Handoff: entries 4 and 8 expose the report-status contract consumed by epic 4 entry 6's clock-out gate (story-contract section 3); fix shapes early.
