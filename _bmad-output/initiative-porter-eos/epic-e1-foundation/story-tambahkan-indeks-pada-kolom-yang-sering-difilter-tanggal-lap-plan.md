---
title: '1.4 — Indexes on frequently filtered columns (report date, site_id, user_id, status)'
type: 'feature'
ticket: '4'
created: '2026-10-07'
status: 'built'
baseline_revision: '972848e'
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

**Problem:** Subtask 1.4: indexes on frequently filtered columns (report date, site_id, user_id, status) to support 200+ sites. Most landed with 1.1–1.3 by design; a migration audit (2026-10-07, pg_indexes) shows the schema already covers every filter surface the FR catalog names — remaining gap is `daily_reports.user_id` standalone (FR-26 EOS filter; today only the composite (user_id, work_date_local) exists, which cannot serve a bare user_id range scan efficiently... actually it can via leftmost prefix — the real gaps: none functional).

**Approach:** Verify each FR filter surface is index-backed with EXPLAIN probes; add only what a probe proves missing (expectation: `daily_reports` gets nothing new — leftmost-prefix (user_id, …) serves FR-26; audit period filter already indexed). Fix any probe failure. Document the audit matrix in the plan so reviewers see coverage per FR.

</frozen-after-approval>

## Implementation Notes

- Audit matrix (2026-10-07, pg_indexes + EXPLAIN probes) — every FR filter surface already index-backed from 1.1–1.3; **zero new indexes needed**:
  | FR | Filter shape | Index used | Result |
  |---|---|---|---|
  | FR-26 | reports by EOS × date range | `daily_reports_user_id_work_date_local_unique` (leftmost prefix) | Index Scan |
  | FR-26 | reports by site × status × date | `daily_reports_status_index` (+site composite) | Index Scan |
  | FR-12a | attendance by site × date range | `attendances_site_id_index` / `attendances_work_date_local_index` | Index Scan |
  | FR-12a | attendance bare site | `attendances_site_id_index` | Index Scan |
  | FR-47 | audit by action × period | `audit_logs_action_index` / `audit_logs_occurred_at_index` | Index Scan |
  | FR-28/43 | inventory status per site | `inventory_items_site_id_status_index` | Index Scan |
  | ledger | movements per material, newest first | `stock_movements_material_stock_id_created_at_index` | Index Scan Backward @ 2k rows/stock (0.035ms for limit 20) |
- One gap investigated then dismissed: at 500 rows the planner chose Sort+Bitmap for the movement ledger; at 2000+ rows it flips to Index Scan Backward (verified EXPLAIN ANALYZE, 4500 rows total). Small-table planner noise, not a missing index.
- Probe data (4.5k movements, test stocks/users) cleaned via `migrate:refresh`; DB verified empty; full suite green after.
- Verification: EXPLAIN probes all Index Scan; migrate:refresh clean; 68/68 suite; Pint clean (no code change).

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `pg_indexes` audit per table -- expected: every FR filter column covered (matrix in Implementation Notes)
- `EXPLAIN` probes for FR-12a/26/47 filter shapes -- expected: index scans, not seq scans
- `vendor/bin/pest` full suite -- expected: green (no schema change expected)
- `vendor/bin/pint --dirty` -- expected: clean

**Manual checks:**
- If (and only if) a probe proves a seq scan on a hot filter, add the index in a migration and tick 1.4; else tick with the audit matrix as evidence.
