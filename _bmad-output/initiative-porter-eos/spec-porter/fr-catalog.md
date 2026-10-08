# FR Catalog — PORTER v1

Preservation catalog of all functional requirements, business rules, and confirmed product decisions. Downstream skills cite FR ids from here; the kernel holds only capability level.

## Authentication & user management

- **FR-1**: Account auth (email/username + password) for 4 roles: EOS, Supervisi, HR, Administrator; users reach only their role's features.
- **FR-2**: Supervisi/Administrator create accounts; EOS account requires school placement; no public self-registration; new accounts get a temporary password and must change it at first login.
- **FR-3**: Move EOS to another school, end assignments; placement history (school, start, end) stored; one active assignment per EOS, one active EOS per school; sequential historical assignments allowed.
- **FR-4**: Wrong credentials → generic error "Email atau kata sandi salah" (no hint which part failed); 5 consecutive failures → 15-minute temporary lockout.
- **FR-4a**: Password reset via emailed link to registered address; Supervisi/Administrator can also reset a user's password — user must change it at next login.

## Attendance (selfie + GPS)

- **FR-5**: Check-in and check-out, each with one selfie and device GPS coordinates (two separate photos).
- **FR-5a**: Camera screen shows a face guide overlay; with FaceDetector API, a face must be detected inside the guide before shutter enables; without it, proceed with overlay only. No face recognition, no biometric storage — presence detection only.
- **FR-5b**: Stored selfie watermarked permanently at bottom: server receive timestamp (site local timezone), GPS lat/lng + accuracy, site name. Server-stamped, not client. Original unwatermarked photo need not be stored.
- **FR-6**: Attendance requires active internet; offline → "No internet connection. Attendance requires an internet connection." and button disabled.
- **FR-7**: Attendance time recorded in school-local timezone (WIB/WITA/WIT) with server UTC time as reference.
- **FR-8**: No double check-in per EOS per day; after check-in the button disables with "Checked In" status.
- **FR-9**: No check-out before check-in; if not checked in, button disabled with "You haven't checked in today."
- **FR-9a**: Check-out blocked until the Daily Report for that local date is "Submitted"; UI guides the user to complete the report instead of a raw rejection.
- **FR-9b**: After successful check-out, status "Completed"; further same-day check-out attempts rejected.
- **FR-9c**: Check-in and check-out must fall on the same local school date; overnight shifts unsupported.
- **FR-10**: Denied camera/location permission → guidance message, attendance halted.
- **FR-11**: Upload in progress → loading indicator, double submission prevented.
- **FR-12**: Upload failure (timeout/server error) → error + "Try Again" without losing captured selfie/GPS.
- **FR-12a**: Supervisi/Administrator/HR view attendance history per EOS and per site (date, local check-in/out times, status, location, selfie) with period filter; EOS sees only own history; this page is the main selfie access context per FR-48.
- **FR-13**: Evidence storage only — no lateness computation, no salary deductions, no shift-time enforcement, no vendor attendance integration.

## Daily Report

- **FR-14**: Administrator-managed template, 5 sections: Info Umum, Router & Firewall, Access Point, Infrastruktur & Lingkungan, Konektivitas Dual-Link.
- **FR-15**: Info Umum auto-filled: report number, school-local date, school name, EOS name.
- **FR-16**: Router & Firewall: device uptime, CPU %, RAM %, anomaly-log status (none/minor/major/not-checked); minor or major anomaly → mandatory explanation + ≥1 photo.
- **FR-17**: Access Point: monitoring status (normal/warning/down/unknown), AP offline count (≥ 0), ≥1 monitoring screenshot from cloud.
- **FR-18**: Infrastruktur & Lingkungan: electricity condition (normal/unstable/out/backup-active), server room temperature °C, room condition (good/needs-attention/unusable); non-normal electricity or non-good room → mandatory explanation + ≥1 photo.
- **FR-19**: Dual-Link connectivity per link (main and backup, separately): average and peak traffic (down/up), speedtest results (download, upload, latency, jitter, packet loss), ≥1 speedtest screenshot per link.
- **FR-20**: ≥1 photo per section; submit rejected when any section empty or photo missing.
- **FR-20a**: Photo rules: JPG/JPEG, PNG, WebP, HEIC/HEIF, PDF (not for selfie); ≤10 MB/file; 1 selfie per check-in and 1 per check-out; ≤5 photos/section; ≤10/report; rule-violating uploads rejected with explicit reason.
- **FR-21**: Server-side drafts, resumable pre-submit; drafts don't count as submitted on dashboards.
- **FR-22**: Successful submit → "Report submitted successfully" with report number; status "Submitted".
- **FR-22a**: Official number `CMX.WR.YYYYMM.SEQUENCE` (e.g. `CMX.WR.202610.0001`); YYYYMM from school-local submit date; global sequence, no reset, past 9999 → 10000; issued only on successful submit; unique, never reused, never changed on revision/reopen; drafts unnumbered.
- **FR-23**: Submit failure from dropped connection → error, all fields and photos preserved for retry.
- **FR-24**: EOS revises own "Submitted" report only on the report's local date; each revision increments the revision count; number unchanged. Supervisi/Administrator reopen (→ "Needs Revision") any time with mandatory reason; all status/content changes audited (who, when, which field, before/after); EOS receives in-app notification on reopen.
- **FR-25**: Administrator revises template (add/change/remove questions); changes apply to new reports only; each report snapshots its template; cross-version dashboard comparison uses stable core fields only (CPU/RAM, AP offline, temperature, speedtest); removed items stay stored but unused for new KPIs.
- **FR-26**: Report list with filters date, school, EOS, status (draft/submitted/needs-revision); empty state "No reports found for this filter."

## Inventory per site

- **FR-27**: EOS records goods received from central warehouse: name, category, quantity, receive date, ≥1 photo.
- **FR-28**: Item statuses: `dipakai`, `cadangan`, `rusak`, `dikembalikan`, `hilang`; EOS changes status; `rusak`/`hilang` require a reason; all status changes audited.
- **FR-29**: Consumable stock per school with in/out/usage movements; balance never negative — violating transactions rejected with a message stating the current balance.
- **FR-30**: Low-stock recap shown only on dashboard (lowest quantities), no minimum thresholds, no push notifications.
- **FR-31**: Inventory bound to the school, not the EOS; EOS transfer leaves inventory at the origin school.
- **FR-32**: Supervisi/Administrator correct inventory data; every correction audited.
- **FR-33**: Empty inventory → "No items recorded at this site yet."

## Master site data

- **FR-34**: Administrator manages site master data: name, address, lat/lng, timezone (WIB/WITA/WIT), primary and backup internet providers.
- **FR-35**: All dates/times displayed per school-local timezone everywhere (attendance, reports, dashboard, exports).
- **FR-36**: Administrator deactivates inactive sites without deleting history.

## Dashboard & KPI

- **FR-37**: Filters: period (date range), school, EOS — combinable.
- **FR-38**: Attendance KPIs: % EOS checked in per day, count not yet checked out, per-site attendance trend.
- **FR-39**: Report completeness KPIs: % submitted vs not, list of sites missing today's report.
- **FR-40**: Network health KPIs: CPU/RAM trend per site, AP offline count, traffic and speedtest trend per link, cross-site performance comparison.
- **FR-41**: Environment KPIs: server-room temperature trend per site, problem-electricity incidents.
- **FR-42**: Anomaly recap: major anomaly logs, AP down, electricity issues, unusable environment — signals for follow-up.
- **FR-43**: Inventory KPIs: `rusak`/`hilang` asset counts per site, low-stock recap.
- **FR-44**: Supervisi/Administrator export dashboard recap to Excel and PDF honoring active filters.
- **FR-45**: Dashboard loading indicator; empty → "No data for this filter."

## Audit log

- **FR-46**: Audit log for: login, check-in/out, report submit, report correction, inventory changes, master data changes, EOS placement changes, template changes. Each entry: actor, role, action, object, time (UTC + local), before/after values where relevant.
- **FR-47**: Administrator views and filters audit log by period, actor, action type.

## Photo access

- **FR-48**: Per data type: (a) selfie — owner EOS, Supervisi, Administrator, HR (workforce purposes); (b) report evidence + inventory photos — owner EOS, Supervisi, Administrator; HR excluded. Roles without access to the related data cannot see the photo.
- **FR-49**: Every selfie and evidence photo access is audited.

## Confirmed product decisions (locked)

1. No minimum-stock thresholds this version; dashboard lowest-stock recap only (FR-30).
2. EOS same-day self-revision; Supervisi/Admin reopen with mandatory reason; in-app notification on reopen (FR-24).
3. Template snapshot per report; dashboard compares stable core fields only (FR-25).
4. No check-in/out time windows — evidence only; work-hour discipline is the vendor system's job (FR-13).
5. Photos retained indefinitely this version; retention/purge deferred (FR-48 context).
6. HR sees attendance + selfies + profile/placement; not report/inventory photos (FR-48).
7. Multi-EOS per site unsupported; one active assignment each way (FR-3).
8. PDF export: simple letterhead (company identity, title, period, filters) + table; no digital signature.
9. Manual goods recording; warehouse import deferred until format known (FR-27).
10. "Lost" is an asset status requiring reason, audited; inventory KPIs count `rusak` + `hilang` per site (FR-28/43).
11. Photo/attachment rules as FR-20a.
12. Report numbers per FR-22a.
13. No self-registration; forced first-login password change; reset via email; Administrator can reset users (FR-2/4a).
14. Session length: starter-kit default (120 min) — application config, not a product decision.
15. Deployment GitHub→Dokploy, no staging; daily backup with restore tested before go-live.

## Data model (conceptual entities, from PRD ERD)

USER, ROLE, SITE, SITE_CONNECTION, ASSIGNMENT, ATTENDANCE, TEMPLATE, SECTION_TEMPLATE, DAILY_REPORT, REPORT_SECTION, REPORT_PHOTO, INVENTORY_ITEM, INVENTORY_PHOTO, MATERIAL_STOCK, STOCK_MOVEMENT, AUDIT_LOG — see PRD ERD for conceptual relations; migrations in task group 1.1–1.3 are the authoritative physical schema work.
