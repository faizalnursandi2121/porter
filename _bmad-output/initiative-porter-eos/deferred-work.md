
## Deferred from: epic-e2-auth-rbac account management (2026-10-08)

- User account edit (name/email/role) + account deactivation — PRD 2.6 only requires create + password reset; edit/deactivate agreed out of epic 2 scope. Deactivation must follow the FR-36 pattern (no hard delete — audit logs and attendance/report history reference users). A peer draft existed but was discarded by owner decision; restart from scratch when picked up. Likely home: epic 3 placement work (3.3/3.4) or a dedicated follow-up subtask.

## Deferred from: code review of epic-e1-foundation plans (2026-10-07)

- Plan-doc hygiene: 1.4 frozen intent self-contradiction, stale "5 roles" in 1.1 approach, empty Plan Change Logs, dangling OK? in 1.2, risk/review front-matter mismatch — fixes edit plan docs (workflow rule).
- Factory tz snapshots use server date/'Asia/Jakarta' (AttendanceFactory, AuditLogFactory, DailyReportFactory::forAttendance) — epics 4/5 rewrite these creation paths.
- InitialSeeder::INITIAL_ADMIN_PASSWORD plaintext in VCS — epic 2 first-login forced rotation closes the window before real deploy.
- `/manager/analytics/overview` route vocabulary + HR role with zero tiles — epic 7 owns Analytics; HR gets its module then.
- Case-variant free-text bypass (site_connections.kind, status/timezone columns unconstrained) — settle in epic-7 DB-constraint review.
- `'throw' => false` on storage disks — pre-existing baseline config; epic 7 storage endpoint hardening.
