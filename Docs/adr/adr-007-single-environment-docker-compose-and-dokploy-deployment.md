# ADR-007 — Single-Environment Docker Compose and Dokploy Deployment

**Status:** Superseded (by ADR-030)  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

MVP prioritizes simple operations and all components are initially hosted together.

**Superseded by:** [ADR-030 — Environment Promotion and Deployment Controls](adr-030-environment-promotion-and-deployment-controls.md) — local, staging, and production environments with controlled promotion.

## Decision

Deploy web, API, worker, PostgreSQL, and Redis through Docker Compose/Dokploy in one environment/server; retain isolated services and volumes.

## Consequences

Fast deployment but single point of failure; backup/restore discipline is mandatory.

## Alternatives considered

Kubernetes, multi-node, managed services, and HA deferred.

## References

tech-stack.md §11; security.md §13, §15
