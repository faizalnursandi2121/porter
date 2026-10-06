# ADR-025 — Inventory Mutation and Stock Integrity Model

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Stock mutation must be fast for operations but immutable and safe.

## Decision
Supervisor can post site-scoped mutation without Manager approval in MVP. Allowed types: receipt, usage, adjustment, damaged, lost, return, transfer in, transfer out. Posted records cannot be edited/deleted. Reversal is a compensating mutation. Stock cannot become negative; database transaction/row locking enforces consistency.

## Consequences
Adjustment, damaged, lost, and transfer require a note.

## References
ADR-014; ERD; API; tests.
