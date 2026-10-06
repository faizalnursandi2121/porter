# ADR-035 — Scoped CSV Export and Export Audit Policy

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Operational data needs export while respecting scope and privacy.

## Decision
Provide CSV export to Super Admin and Manager only within permitted scope/filter, with year/month/week/day filters and site scope. Export is delivered **synchronously** as a streamed CSV response (revised 2026-10-01; originally allowed as worker/outbox job): pilot-scale volumes complete in under a second, so a job table, polling, and expiry handling are unnecessary. The audit event (actor, role, data type, filter, scope, timestamp, outcome) is written synchronously before the stream starts. Export cannot bypass sensitive-data visibility. An async export pipeline may be reintroduced later if volume proves necessary.

## Consequences
Export completes within the request; no export job state machine, polling UI, or file expiry in the MVP.

## References
ADR-017; ADR-034.
