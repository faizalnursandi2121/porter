# ADR-009 — UTC Canonical Storage and Site-Local Timezone Rules

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

Sites operate in WIB, WITA, and WIT while attendance/report rules depend on local site time.

## Decision

Store audit timestamps in UTC; use IANA timezone per site (`Asia/Jakarta`, `Asia/Makassar`, `Asia/Jayapura`) for work date, schedules, period 21–20, and report YYYYMM.

## Consequences

Historical records snapshot timezone/policy context. UI must label timezone for cross-site data.

## Alternatives considered

Using WIB as global operational time or relying on device time is rejected.

## References

prd.md §5, §7; architecture.md §11; data-dictionary.md §2
