# ADR-042 — Dual-Link Site Network Configuration and Per-Link Test Policy

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
All sites must operate with main and backup connectivity, and each route must be independently reported and tested.

## Decision
Every active site has exactly one active `MAIN` and one active `SECONDARY` network link, each with provider and connection medium. Report traffic for each link from 08:00-17:00 local time: average/peak inbound/outbound, source, and evidence. Accept Kbps or Mbps; backend derives normalized Kbps. EOS runs distinct Speedtests for MAIN and SECONDARY. `SUCCESS` requires download/upload, latency, jitter, and screenshot; `FAILED`/`NOT_TESTED` requires reason. `NOT_AVAILABLE` is invalid.

## Consequences
Secondary test follows safe route/failover SOP. Application records route declaration but does not automatically verify the actual route in MVP.

## References
ADR-041; data dictionary; API; UX; tests.
