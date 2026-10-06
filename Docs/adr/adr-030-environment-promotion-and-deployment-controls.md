# ADR-030 — Environment Promotion and Deployment Controls

**Status:** Accepted (amended 2026-10-06: ClamAV dropped — see ADR-044)  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Pilot and production require isolation and repeatable promotion.

## Decision
Use separate local development, staging, and production environments. Staging is mandatory before pilot and production. Promotion is `faizaldev -> staging -> production`. Production deploys only from production branch. Secrets are environment-specific and staging never uses raw production data.

## Mechanics (implementation, not new decision)
Production runs `deploy/compose/docker-compose.production.yml` (7 services including mandatory `clamav`; volumes `postgres_data`/`redis_data`/`uploads_data`/`clam_db`; no host ports). Deploy is manual HITL: Dokploy Compose git-connected to branch `production` only, auto-deploy OFF, Deploy clicked after the pipeline is green (runbook §4). On branch `production` the CI `clamav-eicar` job runs automatically and blocks the pipeline; on other branches it stays manual. Branch protection for `staging`/`production` is configured in the GitLab UI (MR-only, pipeline must succeed) — documented as a HITL checklist in runbook §8, not config-as-code.

## Consequences
This ADR supersedes ADR-007.

## References
Deployment runbook; release checklist.
