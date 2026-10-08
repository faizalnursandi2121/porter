---
title: '1.7 — Private photo disk: unreachable from the public root'
type: 'feature'
ticket: '7'
created: '2026-10-07'
status: done
baseline_revision: '39dc637'
route: 'oneshot'
route_source: 'auto'
risk: 'medium'
review: 'quick'
review_source: 'pinned'
lenses_ran: []
review_loop_iteration: 0
context: []
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Subtask 1.7: the private storage disk for photos must be configured so no file is directly reachable from the public root. Gap found: the `local` disk shipped `serve => true`, which registers Laravel's `GET /storage/{path}` and `PUT /storage/{path}` routes (flagged back in ticket 0.2's review).

**Approach:** Flip `serve` to `false` (single config change, cited to FR-48/49 and the epic-7 audited endpoint); prove the lockdown with feature tests: no storage routes, no public symlink, private root outside public/, HTTP probe 404s, disk API still works.

</frozen-after-approval>

## Implementation Notes

- Fix: `config/filesystems.php` local disk `serve` true → false (comment cites FR-48/49 + epic 7.6/7.7). Removes BOTH serve routes (GET `storage.local` + PUT `storage.local.upload` flagged in 0.2).
- Verified: route:list = 0 storage/* routes; no public/storage symlink; private root outside public/; HTTP probe of private file 404; disk API write/read/delete OK.
- 5/5 PrivateStorageTest; suite 83/83; build green.

## Plan Change Log

## Review Triage Log

## Verification

**Commands:**
- `php artisan route:list | grep storage` -- expected: empty
- `vendor/bin/pest tests/Feature/PrivateStorageTest.php` -- expected: 5/5 green
- `vendor/bin/pest` full -- expected: green
