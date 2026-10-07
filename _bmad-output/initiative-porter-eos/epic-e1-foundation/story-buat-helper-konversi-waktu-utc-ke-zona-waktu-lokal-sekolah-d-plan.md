---
title: '1.6 — UTC↔site-local time helper with unit tests'
type: 'feature'
ticket: '6'
created: '2026-10-07'
status: 'built'
baseline_revision: '39dc637'
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

**Problem:** Subtask 1.6: a helper converting UTC (canonical storage time) to the school's local timezone and back, with unit tests — FR-7 semantics (UTC stored; site tz authoritative for local dates/labels).

**Approach:** `App\Support\SiteTime` static helpers: `toSite`, `toUtc`, `localDate` (YYYY-MM-DD), `localLabel` (HH:MM T), `tzLabelToIana` (WIB/WITA/WIT → IANA). Unit tests cover cross-tz date flips, round-trip losslessness, and `Carbon::setTestNow()` determinism (server clock only).

</frozen-after-approval>

## Implementation Notes

- `App\Support\SiteTime`: toSite, toUtc, localDate, localLabel (`H:i T`), tzLabelToIana (passthrough for already-IANA values). Static Carbon helpers, no dependencies — KISS per conventions.md.
- 7 unit tests incl. cross-tz date flip (17:30 UTC = next day in WITA/WIT), round-trip losslessness, `Carbon::setTestNow()` determinism (legacy §10: server clock only).
- Verification: 7/7 unit; suite 83/83; Pint clean.

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `vendor/bin/pest tests/Unit/SiteTimeTest.php` -- expected: 7/7 green
- `vendor/bin/pest` full + `pint --dirty` -- expected: green/clean
