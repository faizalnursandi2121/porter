# ADR-010 — Attendance Integrity Uses Server Time, Geofence, and Selfie

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

Location from a PWA/OS cannot prove anti-fake GPS with absolute certainty; attendance evidence must be layered.

## Decision

Use server timestamp, backend geofence computation, location freshness/accuracy rules, camera selfie, idempotency, audit, and Attendance Request review.

## Consequences

Evidence is stronger and auditable; PWA must not claim 100% anti-fake GPS.

## Alternatives considered

Client clock, frontend radius calculation, or GPS-only attendance rejected.

## References

prd.md §6–7; security.md §9; api-contract.md §5
