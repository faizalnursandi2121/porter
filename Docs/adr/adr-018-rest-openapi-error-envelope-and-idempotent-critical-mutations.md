# ADR-018 — REST/OpenAPI, Error Envelope, and Idempotent Critical Mutations

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

React PWA and Go API need a stable contract for implementation, QA, and future generated clients.

## Decision

Expose versioned REST JSON `/api/v1`, maintain OpenAPI 3.x source, standard error envelope, request correlation ID, CSRF, pagination allowlists, and durable idempotency key for critical mutation.

## Consequences

Frontend/backend can progress against a contract; API changes are versioned/reviewed.

## Alternatives considered

Undocumented ad-hoc endpoints, GraphQL for MVP, and retry-unsafe critical POST operations rejected.

## References

api-contract.md; api/openapi/openapi.yaml; security.md §8
