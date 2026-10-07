# ADR-028 — Attachment Quota, Format, and Context Policy

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Evidence must be bounded, usable, and consistently associated to operational context.

## Decision
Maximum file size is 10 MB. A Daily Report permits 10 attachments total (simple count, revised 2026-10-01: originally "10 unique files"), with maximum five per section evidence or item evidence. Attendance Request and inventory mutation permit five attachments. Operational storage quota is 2 GB/site; warn at 80% and reject upload at 100%. PDF is allowed in report/section evidence but not selfie, Attendance Request, or inventory mutation.

## Consequences
~~One physical file may link to multiple authorized contexts without duplicate storage.~~ **Revised 2026-10-01 (single-context decision):** one attachment links to exactly one context (`attachment_links` is unique per attachment; a second link returns `422 ATTACHMENT_LINK_ALREADY_LINKED`). Evidence for a different context is uploaded separately; the same file may be uploaded again. Storage duplication is cheaper than multi-context link complexity at MVP scale.

## References
ADR-008; ADR-016; ADR-029.
