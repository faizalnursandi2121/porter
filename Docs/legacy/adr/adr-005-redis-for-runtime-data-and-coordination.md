# ADR-005 — Redis for Runtime Data and Coordination

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

The system needs cache, rate limiting, session runtime, and optional queue/lock coordination.

## Decision

Run one private Redis instance for `cache:*`, `session:*`, `ratelimit:*`, `queue:*`, and `lock:*`; enable AOF persistence.

## Consequences

Redis improves runtime behavior but is not source of truth for attendance, report, inventory, sequence, or audit.

## Alternatives considered

No Redis deferred; separate Redis instances/HA deferred until load/reliability requires it.

## References

tech-stack.md §7; architecture.md §12
