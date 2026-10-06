# ADR-039 — Cross-Midnight Shift Explicitly Out of Scope for MVP

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Cross-midnight shifts alter work-date, report deadline, period, and attendance association semantics.

## Decision
Do not support cross-midnight shifts in MVP. Check-in and clock-out must be within the same site-local calendar day.

## Consequences
Cross-midnight actions are rejected. Future support needs separate work-schedule ADR.
