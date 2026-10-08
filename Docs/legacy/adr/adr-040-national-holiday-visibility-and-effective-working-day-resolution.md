# ADR-040 — National Holiday Visibility and Effective Working-Day Resolution

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Holiday/cuti bersama can occur exactly on period boundary dates but must remain visible without incorrectly producing attendance obligations.

## Decision
Display national holidays and collective leave in red, including dates 20 and 21. Keep period boundaries fixed at 21-20; never shift them to a next working day. Only `EFFECTIVE_WORKING_DAY` contributes to attendance, report obligation, and lateness. Holiday/cuti bersama is non-working by default. A site `WORKING` override preserves red informational marker plus `Operasional Site` badge and makes that day count.

## Consequences
National calendar events are distinct from site operational overrides.

## References
ADR-036; ADR-037.
