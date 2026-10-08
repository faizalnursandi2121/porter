# ADR-022 — Master Site and Asset Identifier Standards

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Sites and assets need stable human-readable operational identifiers.

## Decision
Site code is immutable: `SR-{REGION}-{CITY}-{NNN}`. Asset tag is immutable: `CMX.{SITE_CODE}.{CATEGORY}.{SEQ}` (`SR` segment removed per 2026-10-01 product owner decision so the tag is neutral for non-SR sites; `CMX` is the fixed company prefix; `SITE_CODE` remains the unique location identity). Site names may change but site code cannot. Retired assets retain their tag and history.

## Consequences
Creation validates controlled region/city/category and uniqueness.

## References
Data dictionary; inventory; PRD.
