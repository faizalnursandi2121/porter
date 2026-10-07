# ADR-038 — Attendance Exception and Temporary Field-Work Site Policy

**Status:** Superseded (product decision: EOS placement is permanent)  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
EOS can be assigned outside normal site but MVP does not include HR leave workflow.


**Superseded by:** product owner decision (2026-10-01) — EOS placement is permanent per site; no temporary field-work assignment in MVP. Out-of-site attendance is handled exclusively via Attendance Request. This ADR is retained for historical record.

## Decision
~~Supervisor or Super Admin may assign one temporary field-work site per EOS/work date with coordinate, radius, timezone, assignee, reason, and audit.~~ **Removed from MVP:** EOS placement is permanent (`eos_site_assignments`); attendance outside the assigned site is handled via Attendance Request after the fact.

## Consequences
There is no automatic geofence exception.

## References
Attendance policy; API.
