# ADR-004 — PostgreSQL with pgx, sqlc, and SQL Migrations

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

Critical workflows require explicit transactions, constraints, sequence allocation, auditability, and PostgreSQL capabilities.

## Decision

Use PostgreSQL as system of record, pgx/v5 driver/pool, sqlc generated query code, and versioned SQL migrations. Do not use a full ORM as the core persistence layer.

## Consequences

Explicit SQL/type safety; migration/query review is required. Developers must not place raw SQL in Gin handlers.

## Alternatives considered

GORM/full ORM rejected as core due to reduced control for critical transactional domains.

## References

tech-stack.md §6; architecture.md §9
