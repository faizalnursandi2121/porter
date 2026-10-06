# ADR-024 — Attendance Request Integrity Model

**Status:** Accepted (amended 2026-10-05)  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Attendance exceptions must not become an untraceable geofence bypass.

## Decision
EOS submits an Attendance Request (`attendance_requests`; UI label "Pengajuan Absensi") for missed check-in, missed clock-out, check-in/clock-out time, or invalid/unreadable selfie/evidence — covering both foreseeable (permission, duty) and unforeseeable (connectivity, GPS, camera) failure. The form uses time pickers for claimed check-in and/or clock-out time; reason and evidence attachments are mandatory in all cases (max 5). Original GPS point, geofence result, calculated distance, and server timestamp cannot be edited. Supervisor in scope or Super Admin accepts or rejects on the daily-reviewed dashboard queue (no system-enforced SLA); original/requested/approved snapshots remain. The approved time equals the EOS-selected time; the system does not recompute or clamp it.

## Consequences
An Attendance Request is an auditable exception, not a replacement for attendance integrity. It is not a leave module: no leave balance, no payroll linkage. Terminology superseded: "Attendance Correction" is renamed Attendance Request; the integrity model is unchanged.

## References
ADR-010; PRD; security.
