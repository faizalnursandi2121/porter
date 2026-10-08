---
tracker_id: ""
key: ""
type: initiative
title: "PORTER v1 — Portal Operasional Terpadu"
parent: none
covers: [CAP-1, CAP-2, CAP-3, CAP-4, CAP-5, CAP-6, CAP-7, CAP-8, CAP-9, CAP-10]
assignee: ""
risk: high
---

# PORTER v1 — Portal Operasional Terpadu

## Description

Comtronics fields one EOS per school across 200+ Sekolah Rakyat sites with no visibility into attendance, device condition, or stock; PORTER joins attendance proof, structured Daily Reports, per-site inventory, and a supervision KPI dashboard in one Laravel 13 + Inertia PWA. The spec owns the capabilities, constraints, and non-goals.

## Outcome

Supervisi gets an auditable, filterable operational picture — the spec's success signal: check-in → report → inventory → clock-out at a site, visible and exportable from the dashboard in under five minutes, every step and photo view audited.

## Done when

1. All ten capabilities (CAP-1…CAP-10) are live for all four roles, not behind a flag, on production (Dokploy).
2. The critical business rules (double check-in, report gate, double check-out, number allocation, photo ACL) are enforced and covered by automated tests.
3. Daily backup runs with a restore procedure tested before go-live; `Docs/` carries the operational docs (7.8).
4. No P0/P1 defect open on the attendance → report → clock-out path during the first week after release.

## Boundaries

The single PORTER app, all four roles. Not the vendor attendance system, not procurement, not device monitoring — see the spec's Non-goals. Tracer path: EOS checks in with watermarked selfie → files and submits a Daily Report → records an arrived item → clocks out; Supervisi filters, reviews, exports. Touch point: `Docs/` operational documents — owner: epic 7.

## References

- spec — `_bmad-output/initiative-porter-eos/spec-porter/spec-porter.md` (companions: fr-catalog, stack, conventions, delivery, success-metrics, ui-design)
- constraint — same spec, sections Constraints and Non-goals
- tasks — `Docs/PRD/tasks-task-list-baru.md` (stable ids x.y, 68 subtasks)
- method — `_bmad-output/initiative-porter-eos/story-contract.md` (sharding, FE/BE parallel via mock, E2E closure)
- baseline UI — `Docs/PRD/ui-design.md`

## Notes

- Decision: epic split follows the task-list build groups 0–7 verbatim; 1 subtask = 1 entry, no merges, no splits (user decision, 2026-10-07, via story-contract.md approval).
- Decision: ticket ref `<group>.<sub>` equals the subtask id (epic id = group number, entry id = sub-minor); story files keep the STORY-E{g}-{yy} name (user-approved mapping, 2026-10-07).
- Decision: no per-epic "Refactor sweep" entries — the 1:1 subtask contract wins; cleanup rides each story's DoD (user decision, 2026-10-07).
- Decision: FE/BE parallel work inside a story via `config('porter.mock_mode')` fixtures in-repo, removed per story at integration; never `env()` and never MSW (story-contract §4 as reconciled, 2026-10-07).
- Decision: story leaf files live inside their epic folder, not a separate `stories/` tree (user-approved reconciliation, 2026-10-07).
- Waits on epic 0 because: every epic builds on the tooling/env/shadcn baseline.
- Decision: ops entries 7.9-7.11 added in epic 7 (owner, 2026-10-07) — CI, Dokploy deploy, daily backup with tested restore; 71 entries total; entry 7.11 closes Done-when #3.
- Unknown: FaceDetector API availability across target devices — progressive enhancement per FR-5a settles it; epic 4 entry 1a owns the fallback.
- Decision: entry-level cross-epic `after` uses the epic slug string; epic-level `after` in this breakdown uses the {epic, needs} table — both accepted forms (2026-10-07).
- Decision: epic 3 stays after epic 2 in build order (RBAC gates admin-only CRUD), stricter than story-contract section 6; parallelism remains among epics 4/5/6 (2026-10-07).
- Decision: in-group entries stay fully chained per tasks-task-list-baru.md's sequential-in-group instruction; weak links are deliberate (2026-10-07).
- Decision: CAP part-allocation across epics lives in spec-porter/delivery.md — CAP-1 split epics 2/3, CAP-7 split epics 5/6/7 (writers vs log UI/photo endpoint), CAP-10 split epics 1/4 (2026-10-07).
