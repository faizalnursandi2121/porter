# ADR-032 — Password Recovery and Session Revocation Policy

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
No transactional-email provider/domain is available in MVP.

## Decision
Super Admin performs controlled password reset. The resulting temporary/reset flow forces password change at next login and revokes all active user sessions. SMTP recovery is deferred.

## Consequences
Passwords are never sent in plaintext through email or notification.

## References
ADR-006; ADR-020.
