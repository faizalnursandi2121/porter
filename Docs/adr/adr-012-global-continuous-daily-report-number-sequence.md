# ADR-012 — Global Continuous Daily Report Number Sequence

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

Comtronics requires report ID format with YYYYMM label but sequence must never reset.

## Decision

Allocate `CMX.WR.YYYYMM.SEQUENCE` only on successful submit; `SEQUENCE` is one global PostgreSQL bigint counter, padded to minimum four digits, never reset.

> **Amendment (2026-10-01, product owner decision):** Originally the format was `CMX.SR.DR.YYYYMM.SEQUENCE`. The report number format is revised to `CMX.WR.YYYYMM.SEQUENCE` (WR = Working Report). `CMX` is the fixed company prefix (not configurable; there is no system settings table for prefixes); `SR` is removed so the number is neutral for non-SR sites. Existing numbers issued under the original format are immutable — any future prefix change affects only new allocations.

## Consequences

Sequence allocation requires a database transaction/row lock; draft has no official number.

## Alternatives considered

Monthly/site/EOS reset sequence and client/Redis counter rejected.

## References

prd.md §6; erd.md §6; architecture.md §9
