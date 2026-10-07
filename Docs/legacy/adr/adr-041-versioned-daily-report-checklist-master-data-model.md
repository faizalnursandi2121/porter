# ADR-041 — Versioned Daily Report Checklist Master Data Model

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
The Daily Report baseline will grow and requires controlled, historical, data-driven checklist behavior.

## Decision
Use global `DAILY_SITE_REPORT` template version 1 as versioned master data. Daily Report snapshots template/version. Every section ends in Section Evidence: Info Umum is optional; Router & Firewall, Access Point, Infrastruktur & Lingkungan, and Konektivitas require 1-5 `AVAILABLE` attachments. Baseline includes structured `LINK_TRAFFIC` and `SPEEDTEST_RESULT` types.

## Consequences
Published templates are immutable. Super Admin drafts/publishes future versions. New standard enum/rules do not require schema changes; new renderer type requires software release.

## References
ADR-013; ADR-026.
