# ADR-015 — Security Baseline and Evidence Retention

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

The system processes credentials, location, selfies, and operational evidence.

## Decision

Apply local auth, Argon2id, secure cookies, CSRF, RBAC/object authorization, private files, rate limiting, audit, backups, and defined retention: attendance/report evidence 2 years, audit logs minimum 3 years.

## Consequences

Release must pass security gate; purge is controlled/audited.

## Alternatives considered

Public attachment URLs, plaintext credentials, unbounded retention, and frontend-only authorization rejected.

## References

security.md
