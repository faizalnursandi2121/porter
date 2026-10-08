# ADR-027 — PWA and Attendance Integrity Policy

**Status:** Accepted (amended 2026-10-05 — offline report draft removed from scope)  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
EOS needs resilience in poor connectivity without weakening attendance evidence.

## Decision
PWA is installable with app-shell cache only. No offline report draft storage: no IndexedDB drafts, no 14-day retention rule, no sync button, no failed-sync UI state. Report authoring is online-only; with no connectivity the app is unusable. Attendance is online-only and cannot be queued.

## Consequences
A day without connectivity means no report and no attendance; recovery is through an Attendance Request (ADR-024). This removes dual-write draft state, sync conflict handling, and draft expiry logic from the MVP surface. Superseded: the prior offline draft policy (IndexedDB, 14 days, manual sync) is removed as over-engineering for MVP.

## References
ADR-002; ADR-010; ADR-024.
