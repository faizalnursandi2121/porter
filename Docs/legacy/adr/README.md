# Architecture Decision Records (ADR) Index

**Project:** PORTER (Portal Operasional Terpadu Sekolah Rakyat)  
**Status:** MVP baseline decisions accepted on 2026-10-01.

ADR are short, durable records of decisions that should not change silently. Any replacement/reversal requires a new ADR that explicitly supersedes the prior record.

| ADR | Decision | Status |
|---|---|---|
| ADR-001 | Modular Monolith for MVP | Accepted (partially superseded by ADR-045) |
| ADR-002 | React, TypeScript, Vite, and PWA Frontend | Superseded by ADR-045 |
| ADR-003 | Go and Gin for Backend HTTP API | Superseded by ADR-045 |
| ADR-004 | PostgreSQL with pgx, sqlc, and SQL Migrations | Accepted (data access now Eloquent — see ADR-045) |
| ADR-005 | Redis for Runtime Data and Coordination | Amended by ADR-047 (database drivers) |
| ADR-006 | Local Account Authentication and Secure Cookie Sessions | Accepted (authorization implementation amended by ADR-050) |
| ADR-007 | Single-Environment Docker Compose and Dokploy Deployment | Superseded by ADR-030 |
| ADR-008 | Local Persistent Attachment Storage with FileStorage Port | Accepted |
| ADR-009 | UTC Canonical Storage and Site-Local Timezone Rules | Accepted |
| ADR-010 | Attendance Integrity Uses Server Time, Geofence, and Selfie | Accepted (partially superseded by ADR-046) |
| ADR-011 | Daily Report Submission Is Required Before Clock-Out | Accepted (clock-out window/EARLY_CLOCK_OUT/geofence-gate parts superseded by ADR-046; report gate tetap) |
| ADR-012 | Global Continuous Daily Report Number Sequence | Accepted |
| ADR-013 | Versioned Checklist Templates and Report Snapshots | Accepted (snapshot source amended by ADR-049) |
| ADR-014 | Inventory Belongs to Site; Inventory Finding Is Separate | Accepted |
| ADR-015 | Security Baseline and Evidence Retention | Accepted |
| ADR-016 | Immutable Original Attachments with Optimized Derivatives | Accepted (amended by ADR-053) |
| ADR-017 | Transactional Outbox and Go Worker for Asynchronous Work | Superseded by ADR-048 |
| ADR-018 | REST/OpenAPI, Error Envelope, and Idempotent Critical Mutations | Superseded by ADR-045 |
| ADR-019 | Mapping, Geolocation, and Geofence Strategy for MVP | Accepted (geofence now informational — see ADR-046) |
| ADR-020 | Session Security and Login Protection Policy | Accepted |
| ADR-021 | Supported Browser/Device Matrix and HEIC/HEIF Attachment Handling | Accepted (amended by ADR-053) |
| ADR-022 | Master Site and Asset Identifier Standards | Accepted (amended by ADR-052) |
| ADR-023 | Daily Report Revision and Reopen Governance | Accepted |
| ADR-024 | Attendance Request Integrity Model (amended 2026-10-05; renamed from Attendance Correction) | Superseded by ADR-046 |
| ADR-025 | Inventory Mutation and Stock Integrity Model | Accepted |
| ADR-026 | Versioned Checklist Controlled-Value Model | Amended by ADR-049 (controlled values in JSON) |
| ADR-027 | PWA and Attendance Integrity Policy (amended 2026-10-05 — offline report draft removed) | Accepted |
| ADR-028 | Attachment Quota, Format, and Context Policy | Accepted (2GB/site storage quota dropped by ADR-053 — no per-site quota in Laravel MVP; format/context/limits tetap) |
| ADR-029 | Quarantined Evidence Processing and Malware-Scan Gate (amended 2026-10-05 — clamd health alerting; amended 2026-10-06 — ClamAV dropped, see ADR-044) | Accepted (amended by ADR-053 — lifecycle sync validation) |
| ADR-030 | Environment Promotion and Deployment Controls (amended 2026-10-06 — topology without clamav, see ADR-044) | Accepted (amended by ADR-047 — topology without redis) |
| ADR-031 | Backup, Recovery, and Restore-Test Policy | Accepted |
| ADR-032 | Password Recovery and Session Revocation Policy | Accepted |
| ADR-033 | In-App Notification Scope for MVP | Accepted |
| ADR-034 | Sensitive Operational Data Visibility and Legal Hold | Accepted |
| ADR-035 | Scoped CSV Export and Export Audit Policy | Amended by ADR-051 (Excel/PDF/CSV + presets) |
| ADR-036 | Site-Local Workday, Timezone, and Calendar Override Model | Superseded by ADR-046 |
| ADR-037 | Attendance Window, One-Attendance, and Daily Report Gate Policy | Superseded by ADR-046 |
| ADR-038 | Attendance Exception and Temporary Field-Work Site Policy | Superseded by ADR-046 (previously superseded by product decision) |
| ADR-039 | Cross-Midnight Shift Explicitly Out of Scope for MVP | Superseded by ADR-046 (remains out of scope) |
| ADR-040 | National Holiday Visibility and Effective Working-Day Resolution | Superseded by ADR-046 |
| ADR-041 | Versioned Daily Report Checklist Master Data Model | Superseded by ADR-049 |
| ADR-042 | Dual-Link Site Network Configuration and Per-Link Test Policy | Accepted |
| ADR-043 | shadcn/ui sebagai Komponen UI Frontend | Accepted (amended by ADR-045) |
| ADR-044 | Drop ClamAV Malware Scanning; Signature/Size/Hash Validation Only | Accepted |
| ADR-045 | Laravel 13 + Inertia Full-Stack Single App | Accepted |
| ADR-046 | Attendance as Presence Evidence (Selfie + GPS + Timestamp) | Accepted |
| ADR-047 | No Redis; Database Drivers for Session, Cache, Queue, Rate Limit, Lock | Accepted |
| ADR-048 | Laravel Queue and Scheduler Replace Transactional Outbox | Accepted |
| ADR-049 | Checklist Master: One Versioned Table with JSON Structure | Accepted |
| ADR-050 | Spatie Permission (RBAC) and Spatie Activity Log (Audit) | Accepted |
| ADR-051 | Export: Styled Excel, Formal PDF, CSV, Column Selection, and Per-User Presets | Accepted |
| ADR-052 | Asset Registration by EOS with Warehouse-Issued Tags | Accepted |
| ADR-053 | Attachment: Synchronous Request Validation with Queued Thumbnail Derivatives | Accepted |

## Supersession summary

- 2026-10-06 amendment (ADR-044): ClamAV malware scanning is dropped entirely from the platform — no clamav container, `clam_db` volume, `CLAMD_HOST`/`CLAMD_PORT`, EICAR CI job, or fail-closed scanner gate. The ClamAV part of ADR-029 and the clamav topology in ADR-030 are amended; attachment validation remains MIME/magic-byte + size + safe decode + SHA-256. (2026-10-06 Laravel migration, ADR-053: lifecycle further simplified to `AVAILABLE | REJECTED` — validation synchronous in request, thumbnails queued.)

- ADR-007 (Single-Environment Docker Compose and Dokploy Deployment) is superseded by ADR-030 (Environment Promotion and Deployment Controls). ADR-007 is retained for historical record; the local + staging + production topology defined in ADR-030 is binding.
- ADR-038 (Attendance Exception and Temporary Field-Work Site Policy) is superseded by product owner decision (2026-10-01): EOS placement is permanent per site; temporary field-work assignment is removed from the MVP. Attendance outside the assigned site has no in-system recovery path since ADR-046 removed the Attendance Request module entirely. ADR-038 is retained for historical record.
- 2026-10-05 amendments (brainstorm spec-gap-audit decisions): ADR-024 renamed to Attendance Request Integrity Model (evidence mandatory in all cases, approved time = EOS-selected time, queue reviewed on Supervisor daily dashboard); ADR-027 offline report draft removed from scope (PWA online-only authoring); ADR-029 clamd health alerting required (fail-closed availability risk; superseded by ADR-044 — ClamAV dropped). Clock-out UX flow is continuous (report-first guidance) with the ADR-011 gate unchanged.

## Laravel stack migration (2026-10)

Pada 2026-10, project berpindah stack ke Laravel 13 + Inertia v3 + React 19 (single app, tanpa REST/OpenAPI, tanpa Redis, tanpa transactional outbox). ADR-045 s.d. ADR-053 merekam keputusan migrasi tersebut; ADR lama yang ter-supersede/amended dipetakan pada tabel index di atas dan supersession summary. Isi file ADR lama (001–044) tidak diubah dan tetap sebagai catatan sejarah.

## Governance


- ADR status values: `Proposed`, `Accepted`, `Deprecated`, `Superseded`.
- An accepted ADR is binding for implementation unless a new ADR supersedes it.
- New ADR must state the owner, rationale, migration/compatibility impact, and references to affected PRD/ERD/API/security/test documents.
- Implementation exception without ADR approval is prohibited for architecture/security/data-contract decisions.
