# ADR-031 — Backup, Recovery, and Restore-Test Policy

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Operational evidence and database records require recoverability.

## Decision
Run daily PostgreSQL and attachment-volume backups with 30-day retention. Set RPO to 24 hours and RTO to 8 hours. Run a monthly restore test on staging and record operator, duration, and outcome. Alert on backup failure, disk/storage at 80%, Redis outage, and worker failure/backlog.

## Consequences
Redis is not a source-of-truth recovery dependency.

## References
Operations runbook; security.
