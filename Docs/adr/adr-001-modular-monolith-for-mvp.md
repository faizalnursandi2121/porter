# ADR-001 — Modular Monolith for MVP

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

MVP has strongly transactional workflows—attendance, Daily Report, document sequencing, inventory stock, and audit—but runs in one deployment environment.

## Decision

Use a modular monolith: one Go application codebase with isolated domain modules; API and worker run as separate commands/services from the same codebase.

## Consequences

Simpler transactions, deployment, and testing; module boundaries must be enforced to avoid a big ball of mud.

## Alternatives considered

Microservices deferred until scale/integration/availability requirements justify distributed complexity.

## References

architecture.md §2, §6
