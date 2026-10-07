---
title: '1.3 — Migrations and models: inventory_items, inventory_photos, material_stocks, stock_movements, audit_logs'
type: 'feature'
ticket: '3'
created: '2026-10-07'
status: 'built'
baseline_revision: '9a65f9d'
route: 'full'
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

**Problem:** Subtask 1.3: migrations + models for the inventory domain and the audit log — `inventory_items`, `inventory_photos`, `material_stocks`, `stock_movements`, `audit_logs` — closing epic 1's schema set (CAP-10 foundation).

**Approach:** FK-safe order; inventory bound to site not EOS (FR-31); item status values as Indonesian locked terms per FR-28 (`dipakai`, `cadangan`, `rusak`, `dikembalikan`, `hilang`); stock balance never negative enforced by DB CHECK constraint (FR-29); audit log append-only by design (no updates in app code; DB UPDATE blocked via rule/trigger is epic-7's enforcement decision — schema stores actor/role/action/object/UTC+local time/before-after JSON per FR-46).

</frozen-after-approval>

## Code Map

- `app/Models/Site.php`, `User.php` -- FK targets from 1.1; `ReportSection`-style jsonb payload pattern from 1.2 reused for audit before/after.
- FR sources: FR-27 (goods received ≥1 photo), FR-28 (status set + reason for rusak/hilang), FR-29 (balance never negative), FR-31 (school-bound), FR-46/47 (audit log shape).
- Verification target: `tests/Feature/InventoryAuditSchemaTest.php`.

## Tasks & Acceptance

**Execution:**
- [ ] `database/migrations/` (5 files) -- inventory_items → inventory_photos → material_stocks → stock_movements → audit_logs -- rationale: FK dependency order.
- [ ] `app/Models/` (5 new) -- InventoryItem, InventoryPhoto, MaterialStock, StockMovement, AuditLog -- rationale: Eloquent surface for epics 6-7.
- [ ] `database/factories/` (5 new) -- rationale: conventions.md; epic 6 tests need them.
- [ ] `tests/Feature/InventoryAuditSchemaTest.php` -- rationale: verify = feature tests pass.

**Acceptance Criteria:**
- Given fresh migrate, then 5 tables exist with FK constraints.
- Given an inventory item, then `site_id` required (school-bound, FR-31) and status restricted to the FR-28 set at app level (consts) — `rusak`/`hilang` carry `status_reason`.
- Given material stock with balance 0, then a movement that would drive balance negative fails at DB level (CHECK `balance >= 0` on material_stocks, movements apply inside a transaction in later stories).
- Given stock movements, then `before_balance`/`after_balance` columns exist for the ledger trail (FR-29 semantics, movement types RECEIPT/USAGE/ADJUSTMENT/DAMAGED/LOST/RETURN as consts).
- Given an audit entry, then it stores actor_id, actor_role, action, object_type/object_id, occurred_at UTC + local date, before/after jsonb; table has no updated_at (append-only by schema shape).
- Given an inventory item or report deletion cascade, then photos cascade with their parent.

## Implementation Notes

- 5 migrations, FK order: inventory_items → inventory_photos → material_stocks → stock_movements → audit_logs.
- Invariants: `material_stocks_balance_non_negative` CHECK (enforced on UPDATE too — verified); unique(site_id, name) per material per school; inventory_items indexed (site_id,status)/(received_at); stock_movements indexed (material_stock_id, created_at); audit_logs indexed (object_type,object_id)/(actor_id,occurred_at)/action/occurred_at.
- FR-28 statuses as consts (`DIPAKAI/CADANGAN/RUSAK/DIKEMBALIKAN/HILANG`); rusak/hilang reason rule = app-level (FormRequest in epic 6), column always present.
- FR-29 ledger: movements carry before_balance/after_balance (immutable rows; corrections = new movements per FR-32).
- FR-46: audit_logs append-only by shape — no updated_at (model `$timestamps = false`), actor_id nullOnDelete (row survives actor deletion, attribution kept in actor_role), before/after jsonb, occurred_at UTC + occurred_date_local for filtering (FR-47).
- Gotchas: (1) Laravel query exceptions on raw probe connections surface as PDOException with message only — assert on constraint name in message, not getCode(). (2) RefreshDatabase wraps each test in a transaction — a DB-level constraint probe from a second connection can't see uncommitted rows; commit first (DB::commit()) before probing, then read via the main connection.
- Verification: migrate up 5/5; rollback step=5 + re-migrate clean; 8/8 InventoryAuditSchemaTest; full suite 68/68; Pint clean.

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `php artisan migrate --force` (sail) -- expected: 5 migrations clean
- `php artisan migrate:rollback --step=5` + re-migrate -- expected: FK-safe
- `vendor/bin/pest tests/Feature/InventoryAuditSchemaTest.php` -- expected: green
- `vendor/bin/pint --dirty --format agent` -- expected: clean

**Manual checks:**
- audit_logs has no updated_at column; before/after jsonb.
- material_stocks balance CHECK present.
