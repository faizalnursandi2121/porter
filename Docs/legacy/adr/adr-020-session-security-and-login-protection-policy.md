# ADR-020 — Session Security and Login Protection Policy

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
The MVP needs bounded sessions and protection against credential attacks.

## Decision
Set idle timeout to 30 minutes and absolute timeout to 8 hours. Use opaque Secure, HttpOnly, SameSite=Lax session cookies. Rotate sessions at login and password change; revoke all active sessions at logout, password reset, and account disable. Allow five failed attempts per 15 minutes, then lock for 15 minutes; counters run **independently per normalized identifier and per IP** (Redis) — lockout triggers when either reaches five. Unknown identifiers increment only the IP counter (anti-enumeration; response stays generic 401). A successful login resets that user's identifier counter. Return generic errors. CSRF uses the double-submit pattern: token = HMAC(server_secret, session_token_hash) in a non-HttpOnly `cmx_sr_csrf` cookie echoed via `X-CSRF-Token` header, validated as a pair; tokens are invalidated automatically on session rotate/revoke. Login is protected by an Origin/Referer allowlist (reject on mismatch, accept when absent) and its response includes the CSRF token.

## Consequences
Redis keeps runtime session state; PostgreSQL retains lifecycle/audit.

## References
ADR-006; security; API; tests.
