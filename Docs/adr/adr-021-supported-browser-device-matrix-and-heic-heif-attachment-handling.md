# ADR-021 — Supported Browser/Device Matrix and HEIC/HEIF Attachment Handling

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
EOS uses mobile browsers and iOS devices may produce HEIC/HEIF camera files.

## Decision
Support the two latest major Android Chrome, iOS Safari, and desktop Chrome/Edge/Firefox releases. Accept JPEG, PNG, WebP, HEIC/HEIF, and PDF. Preserve private HEIC originals and create WebP preview/thumbnail in a worker using libvips with libheif support. Verify support with CI fixtures.

## Consequences
Unsupported browsers receive a support notice.

## References
ADR-016; attachment policy; test strategy.
