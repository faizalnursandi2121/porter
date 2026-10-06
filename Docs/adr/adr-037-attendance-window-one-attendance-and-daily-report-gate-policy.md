# ADR-037 — Attendance Window, One-Attendance, and Daily Report Gate Policy

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Attendance must have unambiguous windows, minute-based lateness, and a report completion gate.

## Decision
Allow one final check-in and one final clock-out per EOS/site/local work date. Check-in: 05:00-07:59 `EARLY`, 08:00 `ON_TIME`, 08:01-11:00 `LATE`; later than 11:00 is rejected. Business precision is minute: convert server timestamp to site-local time then truncate to `HH:MM`. Clock-out is allowed 16:00-23:59 only after the same-day report is `SUBMITTED` and required evidence is `AVAILABLE`. Late period is fixed from the 21st previous month through the 20th current month; display is capped at 60 minutes.

## Consequences
Duplicate final attendance actions are rejected; failed attempts remain auditable.

## References
ADR-010; ADR-011; ADR-040.
