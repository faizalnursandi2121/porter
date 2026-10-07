# ADR-013 — Versioned Checklist Templates and Report Snapshots

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

Checklist questions may change but historical Daily Reports must retain their original meaning.

## Decision

Version checklist templates and snapshot the applied template/item definitions on each report; submitted reports are immutable unless explicitly reopened.

## Consequences

Historical rendering/audit is stable; master template changes do not rewrite prior report meaning.

## Alternatives considered

Hardcoded report columns and mutable historical templates rejected.

## References

erd.md §6; data-dictionary.md §6; ux.md §6.3
