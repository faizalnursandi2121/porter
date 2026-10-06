# ADR-011 — Daily Report Submission Is Required Before Clock-Out

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

Operational reporting must be completed daily before EOS concludes the workday.

## Decision

Allow clock-out only when attendance is CHECKED_IN, Daily Report is SUBMITTED, server time is within 16:00–23:59 site-local (16:00–16:59 yields `EARLY_CLOCK_OUT`; before 16:00 rejected), and fresh geofence/selfie evidence passes.

## Consequences

Report becomes critical attendance workflow dependency; UX must show report blocker explicitly.

## Alternatives considered

Optional report submission or report approval before clock-out rejected for MVP.

## References

prd.md §6–8; ux.md §6.4; architecture.md §9; ADR-037
