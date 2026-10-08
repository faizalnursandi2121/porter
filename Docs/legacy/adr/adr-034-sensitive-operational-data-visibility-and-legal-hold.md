# ADR-034 — Sensitive Operational Data Visibility and Legal Hold

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Selfies, precise GPS, and evidence are sensitive operational data.

## Decision
Super Admin has access for operational/audit need. Supervisor accesses only site scope. Manager receives aggregate views without raw selfie, precise GPS, or sensitive evidence by default. HR accesses attendance data within authorization without technical Daily Report evidence by default. EOS accesses own data as required. Sensitive access/download is audited. Retention purge checks legal hold.

## Consequences
The visibility matrix above is binding for the MVP. The legal-hold **module** (table, API endpoints create/list/release) is **deferred** (revised 2026-10-01): the MVP has no automated purge/retention process, so the hold gate has no enforcement point yet; the module will be built together with retention/purge automation. Until then, no data is purged automatically and manual retention decisions follow the documented policy.

## References
ADR-015; security.
