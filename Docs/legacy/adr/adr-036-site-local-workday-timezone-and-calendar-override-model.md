# ADR-036 — Site-Local Workday, Timezone, and Calendar Override Model

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Sites have operational calendars that may differ from national calendar and normal weekdays.

## Decision
Each site stores IANA timezone and default Monday-Friday workdays. Site Calendar Override resolves `WORKING`, `NON_WORKING`, or `HOLIDAY`. Attendance and report work date is site-local and stores timezone snapshot. Calendar override history is audited.

## Consequences
Operational workday policy is explicit per site.

## References
ADR-009.
