# ADR-003 — Go and Gin for Backend HTTP API

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

The backend needs a performant, simple REST authority with clear middleware and multipart upload support.

## Decision

Use Go with Gin as the HTTP delivery adapter. Keep business rules outside Gin handlers.

## Consequences

Gin handles routing/binding/middleware; application/domain remains testable and independent of HTTP.

## Alternatives considered

Chi and other frameworks were considered; do not mix multiple HTTP frameworks.

## References

tech-stack.md §3; architecture.md §7
