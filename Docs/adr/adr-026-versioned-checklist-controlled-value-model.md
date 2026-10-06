# ADR-026 — Versioned Checklist Controlled-Value Model

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Checklist fields, options, ordering, rules, and evidence requirements will evolve.

## Decision
Manage checklist options, order, conditional rules, required state, evidence policy, and thresholds as controlled master data within a versioned template. Super Admin publishes versions. Published versions are immutable and historical versions remain readable.

## Consequences
Option/rule changes need no schema change; new renderer/input types require a frontend/backend release.

## References
ADR-013; ADR-041.
