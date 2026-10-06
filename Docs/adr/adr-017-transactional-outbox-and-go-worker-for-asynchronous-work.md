# ADR-017 — Transactional Outbox and Go Worker for Asynchronous Work

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

Business events, attachment processing, retry, cleanup, and future notification must survive worker/Redis restart.

## Decision

Write outbox events in the same PostgreSQL transaction as critical business and audit writes; Go worker processes events idempotently with retry. Delivery semantics are at-least-once. Operational parameters (amended 2026-10-01): poll interval 5 seconds; lease TTL 60 seconds with automatic reclaim of expired leases; exponential backoff 5 seconds doubling to a 10-minute cap; after 8 attempts an event becomes `DEAD_LETTER` and raises an operations alert. Ordering is guaranteed per aggregate (aggregate_type + aggregate_id, processed in sequence) and not guaranteed across events.

## Consequences

Reliable event trail and eventual async work; Redis may accelerate queue/coordination but is not reliable event ledger.

## Alternatives considered

Redis-only queue and synchronous heavy image processing in HTTP request rejected.

## References

architecture.md §14; tech-stack.md §6–7
