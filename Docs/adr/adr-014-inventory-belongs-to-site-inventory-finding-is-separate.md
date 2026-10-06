# ADR-014 — Inventory Belongs to Site; Inventory Finding Is Separate

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

Assets/material remain at a site when EOS changes; EOS needs a safe way to report discrepancies without direct stock edits.

## Decision

Attach assets and stock to site. EOS creates Inventory Findings; Supervisor uses authorized inventory workflow to mutate asset/stock and references the finding.

## Consequences

No silent stock edits; finding lifecycle is auditable and not a customer SLA/ticketing system.

## Alternatives considered

Inventory ownership by EOS and direct EOS inventory mutation rejected.

## References

prd.md §6; ux.md §6.6, §7.3; data-dictionary.md §8–9
