# Delivery — PORTER v1

Execution order and scope mapping from `Docs/PRD/tasks-task-list-baru.md` (68 subtasks, sequential inside groups). This companion tells build sessions where a ticket sits in the whole.

## Build order (8 groups, 0→7)

| Group | Scope | CAPs touched | Subtasks |
|---|---|---|---|
| 0 | Branch, local env (DB/private storage/mail), PR description, shadcn installs (`table`, `calendar`, `progress`, `form`, `alert`, `timeline`), module nav + English tile labels | — | 0.0–0.5 |
| 1 | Schema/models migrations (roles, users, sites, site_connections, assignments, attendances, templates, section_templates, daily_reports, report_sections, report_photos, inventory_items, inventory_photos, material_stocks, stock_movements, audit_logs), indexes, seeders, UTC↔local helper + tests, private disk | CAP-10 foundation | 1.1–1.7 |
| 2 | Session login + generic error, 5-fail/15-min lockout, forced password change, email reset, RBAC middleware/policies, account management UI (EOS with placement), role-scope tests | CAP-1 | 2.1–2.7 |
| 3 | Site CRUD + deactivation, assignment create/move/end with history + invariants + audit, tests | CAP-8, CAP-1 | 3.1–3.6 |
| 4 | Attendance UI (camera+GPS, face guide + FaceDetector, server watermark), offline/permission/loading/retry states, check-in/out rules + same-day rule, PWA manifest/SW, attendance history + photo access | CAP-2, CAP-3, CAP-9, CAP-10 | 4.1–4.12 |
| 5 | Template CRUD versioned + snapshot, report form 5 sections + conditional validation + photo rules, drafts, submit + number allocation, retry, same-day revision, reopen + in-app notification + unread indicator, report list + filters, tests | CAP-4, CAP-7 | 5.1–5.13 |
| 6 | Inventory received-goods form, status changes + reasons + audit, material stock movements + negative rejection, school ownership + corrections, list + empty state, tests | CAP-5, CAP-7 | 6.1–6.7 |
| 7 | Dashboard filters + 6 KPI groups + loading/empty states, Excel/PDF export, audit log page + append-only, photo access endpoint (FR-48/49) + tests, Docs (photo rules, role guide, backup/restore) | CAP-6, CAP-7 | 7.1–7.8 |

## Cross-cutting notes for slicing

- Sequential inside a group; groups 1→2 are strict prerequisites for everything; 4 and 5 interlock at the check-out gate (FR-9a) — 4.6 depends on report status from group 5's model, plan the interface early.
- Photo pipeline (private disk + audited endpoint) appears in groups 1, 4, 5, 6, 7 — the endpoint and FR-48 role matrix land finally at 7.6/7.7; interim photo pages must not bypass it.
- Empty-state strings are verbatim FR text (FR-26/33/45 + FR-6/9 messages) — copy from `fr-catalog.md`, don't paraphrase.
- Documentation deliverable 7.8 lands in `Docs/` before go-live: photo rules, role guide, backup/restore.

## Story decomposition & parallel tracks

All epics are decomposed and run per `_bmad-output/initiative-porter-eos/story-contract.md`:
1 subtask = 1 story with a mandatory `## Contract` block (routes, error codes, Inertia props).
After a story is CONTRACTED, its FE (against mock fixtures) and BE (feature tests) run in parallel;
integration = mocks removed + E2E happy path + ≥1 failure path. This applies to every epic, current and future.
