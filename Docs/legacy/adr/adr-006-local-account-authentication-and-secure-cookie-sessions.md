# ADR-006 — Local Account Authentication and Secure Cookie Sessions

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

MVP has internal users and no existing company SSO/IdP.

## Decision

Use local email or employee code plus password; Argon2id hash; opaque Secure HttpOnly SameSite cookie session; Redis session runtime and PostgreSQL lifecycle/audit.

## Consequences

No external auth dependency; CSRF protection, rate limiting, session rotation/revocation are mandatory.

## Alternatives considered

Clerk, WorkOS, and SSO/OIDC deferred; OIDC adapter remains future-ready.

## References

security.md §5–7; tech-stack.md §8
