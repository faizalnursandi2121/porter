# ADR-016 — Immutable Original Attachments with Optimized Derivatives

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

Photo evidence needs fast previews and lower bandwidth without losing original forensic evidence.

## Decision

Preserve original file private with SHA-256; generate separate preview/thumbnail variants. Use WebP quality 85–90 for photographs and lossless image variants for text-heavy screenshots; do not recompress PDFs automatically in MVP.

## Consequences

UI uses derivatives by default; original access is restricted. Processing uses worker limits/timeouts.

## Alternatives considered

Overwriting originals, storing only lossy compressed photo, and automatic PDF recompression rejected.

## References

api-contract.md §4; data-dictionary.md §7; security.md §10
