# ADR-008 — Local Persistent Attachment Storage with FileStorage Port

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

MVP needs private evidence storage while avoiding object-storage operational overhead.

## Decision

Store binary attachments in a private persistent local volume; store metadata in PostgreSQL; access through a FileStorage abstraction.

## Consequences

Files survive container recreation and can migrate later to MinIO/S3 without changing domain use cases.

## Alternatives considered

Direct database BLOB storage and public static file directories are rejected; MinIO/S3 deferred.

## References

tech-stack.md §9; architecture.md §13
