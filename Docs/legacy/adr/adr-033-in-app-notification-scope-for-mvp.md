# ADR-033 — In-App Notification Scope for MVP

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Users need visibility of important workflow outcomes without adding external messaging dependencies.

## Decision
Provide in-app notifications only for Attendance Request approval/rejection, report reopen, attachment rejection/malware failure, attachment still processing near the submit/clock-out window close (early warning), and site storage warning to Super Admin. Email and WhatsApp are deferred.

## Consequences
Notifications are supplemental; source record and audit remain authority.

## References
ADR-017; API.
