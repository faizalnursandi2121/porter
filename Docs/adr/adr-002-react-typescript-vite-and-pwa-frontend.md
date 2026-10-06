# ADR-002 — React, TypeScript, Vite, and PWA Frontend

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

The product needs desktop backoffice and mobile-first EOS access without native mobile application scope.

## Decision

Build a React SPA with TypeScript and Vite; ship as installable PWA.

## Consequences

Single web codebase; PWA supports home-screen install and static caching. Attendance remains server-confirmed, not offline-valid.

## Alternatives considered

Native Android/iOS and Next.js are out of scope MVP.

## References

tech-stack.md §3–4; ux.md
