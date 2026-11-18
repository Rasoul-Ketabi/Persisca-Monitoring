# Monitor Project | Full Source of Truth v0.0.4

Version: **v0.0.4**  
Latest completed stage: **04 | Incident Detection & Recovery Engine**  
Base: actual **v0.0.3** full source archive. Stages 01–03 are preserved.  
Scope: native PHP, PDO MySQL/MariaDB, cURL, CLI cron, shared hosting (Plesk/cPanel).

## Product & core rules

Self-hosted HTTP/HTTPS monitoring for websites and endpoints. Admin dashboard and a public status center. All user-facing application copy is **English only**. Branding (product names, icons, footer, OG tags and imagery) is white-label and configured through `settings`. No Laravel, Node.js, Redis, Composer, or persistent workers. Users keep their account and database on upgrades. Existing `users.role` remains available for a later multi-Super-Admin stage.

## Source files (complete project tree)

```text
.htaccess
MONITOR-FULL-PROJECT-v0.0.3.md
MONITOR-FULL-PROJECT-v0.0.4.md
README.md
VERSION
admin/.htaccess
admin/_dispatch.php
admin/incidents/index.php
admin/incidents/view/index.php
admin/index.php
admin/login/index.php
admin/logout/index.php
admin/monitors/add/index.php
admin/monitors/edit/index.php
admin/monitors/index.php
admin/monitors/view/index.php
admin/notifications/index.php
admin/settings/branding/index.php
admin/settings/general/index.php
admin/status-page/index.php
admin/system/index.php
app/.htaccess
app/Core/Database.php
app/Core/Security.php
app/Helpers/functions.php
app/Models/CheckRepository.php
app/Models/IncidentRepository.php
app/Models/MonitorRepository.php
app/Models/Settings.php
app/Models/SystemMeta.php
app/Services/Auth.php
app/Services/CronRunner.php
app/Services/HttpMonitor.php
app/Services/IncidentManager.php
app/Services/MonitorInput.php
app/Services/Upload.php
app/Views/admin/branding.php
app/Views/admin/dashboard.php
app/Views/admin/general.php
app/Views/admin/incident-detail.php
app/Views/admin/incidents.php
app/Views/admin/monitor-detail.php
app/Views/admin/monitor-form.php
app/Views/admin/monitors.php
app/Views/admin/placeholder.php
app/Views/admin/system.php
app/Views/auth/login.php
app/Views/partials/admin-bottom.php
app/Views/partials/admin-top.php
app/Views/public/status.php
bootstrap.php
config/.htaccess
cron/.htaccess
cron/monitor.php
database/.htaccess
database/patches/v0.0.1.sql
database/patches/v0.0.2.sql
database/patches/v0.0.3.sql
database/patches/v0.0.4.sql
database/schema.sql
index.php
install/index.php
public/assets/css/app.css
public/assets/images/status-og-default.jpg
public/assets/js/app.js
public/assets/uploads/.htaccess
public/assets/uploads/index.html
public/index.php
status/index.php
storage/.htaccess
storage/cache/.htaccess
storage/logs/.htaccess
storage/logs/monitor-cron.log
tests/.htaccess
tests/smoke.php
tests/stage04.php
```

`config/database.php`, `storage/installed.lock`, runtime logs/locks, sessions, and uploaded customer branding assets **must not be included** in source archives. The FULL package contains versioned source only. The root SQL migration is distributed separately as `monitor-sql-patch-v0.0.4.sql` and is also included in the FULL archive under `database/patches/v0.0.4.sql`.

## Architecture and key classes

- `bootstrap.php`: namespace autoload, safe errors, secure session cookies, CSP and headers.
- `app/Core/Database.php`: PDO connection, prepared statements, UTC convention.
- `app/Models/Settings.php`: white-label settings and monitoring retention defaults.
- `app/Services/Auth.php`, `app/Core/Security.php`: admin login, logout, sessions and CSRF.
- `app/Services/MonitorInput.php`: form validation, HTTP status allowlist, URL safety, and **1–5 failure_threshold**.
- `app/Models/MonitorRepository.php`: CRUD, activation, configuration and separate manual check snapshots.
- `app/Services/HttpMonitor.php`: existing safe cURL checker, SSL verify enabled, DNS/private-target policy, retry semantics, redirects and GET request handling.
- `app/Services/CronRunner.php`: CLI execution, bounded batches, filesystem flock, heartbeat, cleanup, and per-monitor exception isolation.
- `app/Models/CheckRepository.php`: due selection, one-transaction scheduled result and runtime state persistence. Now returns an **array of transition data** for saved results, or false when the monitor disappears/is paused before commit.
- `app/Services/IncidentManager.php`: reusable state machine (`decide()` and `apply()`); operates within the caller's PDO transaction under `SELECT ... FOR UPDATE` of the monitor row. Does not create notifications.
- `app/Models/IncidentRepository.php`: active/resolved counts and pages, incident detail, recent incidents, bounded related checks, filtered public states and incidents.
- `app/Views/admin/*` and `app/Views/public/status.php`: retained admin design and updated status surfaces.

### Current folder-style routes

| Public/admin URL | Purpose |
|---|---|
| `/` | Public entry point (delegates to `public/index.php`) |
| `/status/` | Public status page, **public monitors only** |
| `/install/` or `/install/index.php` | Fresh browser installer; locks when complete |
| `/admin/` | Dashboard, confirmed Up/Down, active incident counts |
| `/admin/login/` | Login |
| `/admin/logout/` | POST logout |
| `/admin/monitors/` | Manage monitors, Up/Down/Unknown/Paused |
| `/admin/monitors/add/` | Add monitor |
| `/admin/monitors/edit/?id=N` | Edit monitor and run manual diagnostic |
| `/admin/monitors/view/?id=N` | Scheduled checks, runtime snapshot, recent incidents |
| `/admin/incidents/` | Active incidents, 20/page |
| `/admin/incidents/?tab=history&p=N` | Resolved incident history |
| `/admin/incidents/view/?id=N` | Incident details and up to 30 linked check rows |
| `/admin/settings/general/` | Branding/timezone/cron retention options |
| `/admin/settings/branding/` | Logo/favicon/OG and metadata uploads |
| `/admin/system/` | Cron heartbeat, storage, PHP and system health |
| `/admin/status-page/`, `/admin/notifications/` | Existing admin placeholders |

All normal route navigation uses **physical folder entry points**, not `?page=`. Query parameters are used only for record IDs and pagination/filtering. Legacy Stage 01 route redirects may exist for compatibility.

## Database schema and indexes

`database/schema.sql` is the complete source of truth for clean installations. The existing tables remain:

- `users`: id, name, email, password_hash, role, status, last_login_at, created_at, updated_at. Unique email.
- `settings`: setting_key, setting_value, updated_at.
- `system_meta`: meta_key, meta_value, updated_at. Stores installed_version and cron heartbeat.
- `login_attempts`: id, email_hash, ip_hash, attempted_at, indexes by hashed identity/time.
- `monitors`: original config and manual result columns, Stage 03 scheduled check snapshot plus Stage 04 operational fields.
- `monitor_checks`: scheduled check records (manual tests continue Stage 02 separate diagnostic persistence), Stage 04 optional `incident_id`.
- `incidents`: one row per confirmed outage.

### Stage 04 additions to `monitors`

| Column | Meaning |
|---|---|
| `operational_status VARCHAR(12) DEFAULT 'unknown'` | `unknown`, `up`, `down`. Paused is **not** an operational state |
| `failure_threshold TINYINT UNSIGNED DEFAULT 2` | Consecutive failed **scheduled checks** required before confirming down, range 1–5 |
| `consecutive_failures INT UNSIGNED DEFAULT 0` | Number of consecutive failed scheduled checks |
| `failure_sequence_started_at DATETIME NULL` | First failed scheduled check in current consecutive sequence |
| `last_success_at DATETIME NULL` | Last successful scheduled check |
| `last_failure_at DATETIME NULL` | Last failed scheduled check |
| `current_incident_id BIGINT UNSIGNED NULL` | Open incident ID; intentionally no circular FK |

Indexes `idx_monitors_operational`, `idx_monitors_active_incident` complement existing `idx_monitors_due (is_active, next_check_at)` and Stage 02 indexes.

### `incidents` fields

`id BIGINT UNSIGNED PK`, `monitor_id BIGINT UNSIGNED` FK cascade on delete, `status VARCHAR(12)` (`open`, `resolved`), `started_at`, `detected_at`, `recovered_at`, `duration_seconds BIGINT UNSIGNED`, `opening_check_id`, `closing_check_id`, `failure_count`, `last_error_type`, `last_error_message`, `last_http_status`, `created_at`, `updated_at`.

Indexes: `(monitor_id,status)`, `(status,started_at)`, `(monitor_id,started_at)`, `(recovered_at)`. The `monitor_checks.incident_id` field is indexed with `checked_at` but has no separate FK, avoiding circular deletion/retention problems. `opening_check_id`/`closing_check_id` are logical references; old raw checks may expire while the permanent incident row remains.

**Deletion:** A confirmed, CSRF-protected POST delete of a monitor hard-deletes its associated incident history and check rows via foreign-key cascades. UI explicitly warns the user. There is no soft delete yet.

### Existing monitoring tables

The Stage 02 monitor settings include URL, name, active/public flags, internal-network opt-in, interval, timeout, expected HTTP status codes, retries, retry delay and separate last manual diagnostic fields. Stage 03 includes `last_checked_at`, `last_check_success`, `last_http_status`, `last_response_time_ms`, `last_error_type/message`, `last_attempt_count`, and `next_check_at`.

The `monitor_checks` table stores check_type, success, HTTP status, response time, error type/message, attempts, effective URL, checked_at, created_at and optional incident_id. Indexes include `(monitor_id,checked_at)`, `(checked_at)`, `(success,checked_at)`, `(incident_id,checked_at)`. Only stored scheduled checks influence the state machine. UTC timestamps are used in database, displayed in configured timezone.

## State machine (confirmed incident semantics)

`retry_count` is **HTTP retry attempts after the initial HTTP attempt** in *one* scheduled check. For example `retry_count=2` allows 3 HTTP attempts. `failure_threshold` counts **failed completed scheduled checks**, not HTTP attempts. Example: `retry_count=2`, `failure_threshold=2` requires two separate failed scheduled checks to open an outage, each potentially having three HTTP attempts.

- `unknown` is the initial state for fresh and upgraded monitors. No old incidents are inferred from historical v0.0.3 checks.
- First success: `unknown -> up`; no incident.
- First failure below threshold: remains `unknown` or the last confirmed `up`, increments consecutive_failures and records `failure_sequence_started_at`.
- Success before threshold: sets `up`, resets failure counter and sequence timestamp.
- Reaching threshold: **one** `down` transition, opens one `incidents` row, marks operational status `down`, assigns current_incident_id. Incident `started_at` = **first failed scheduled check in the sequence**. `detected_at` = timestamp of threshold-reaching check. This yields more truthful duration than starting at detection.
- Failure while down: updates the same open incident's failure_count and latest error. No duplicate outage row or second transition.
- First successful scheduled check while down: `recovered`, closes incident, records recovered_at, closing_check_id, computes `duration_seconds = max(0,recovered_at-started_at)`, resets consecutive failures and clears current_incident_id. Repeated success does not modify the resolved incident.
- `manual Test Now` never changes operational status, failure counters, incidents, or scheduled snapshot. It runs even when paused.
- Pausing while down does **not** close the incident; the incident stays open until scheduled monitoring resumes and a success occurs. Admin receives a clear warning. Public display uses `Monitoring paused` for paused monitors.
- `IncidentManager::apply(PDO, monitor, checkId, result, checkedAt)` outputs `{transition,monitor_id,incident_id,previous_status,current_status,started_at,detected_at,recovered_at,duration_seconds}` with transition `none`, `down`, or `recovered`. `CheckRepository::saveScheduled()` forwards this payload after commit, or returns false for a missing/paused monitor. Telegram integration is **not** implemented.

### Transaction and concurrency safety

The cron's filesystem `flock` serializes normal runs. `CheckRepository::saveScheduled()` opens a PDO transaction and row-locks the monitor (`SELECT ... FOR UPDATE`). It inserts the scheduled check, updates the Stage 03 snapshot/next_check_at, calls `IncidentManager::apply()` to create/update/resolve the incident and update Stage 04 state, then commits. Exceptions roll back the **whole transaction**. Application-level row locking prevents two incident transitions for the same monitor on concurrent processes. `current_incident_id` deliberately avoids a circular FK; the service checks the referenced incident is still open. Stage 05 may add a durable notification outbox using this transition return without embedding Telegram calls into the state machine.

## Cron, schedule and retention

`cron/monitor.php` stays CLI-only. Run **once per minute** if hosting supports it; a monitor configured for five-minute intervals is only selected when its UTC `next_check_at` is due. Paused monitors never receive scheduled checks. Next due = completed check time + interval; missed checks are not backfilled. Default batch is 25 (configurable to 100); a soft 50-second guard stops adding more work after a run has taken time, but never interrupts an HTTP check mid-request. Configured long timeouts/retries may exceed one minute; flock protects against concurrent execution. A single monitor error is isolated and logged. `system_meta` stores last cron started, completed, status, processed_count and safe error summary. `System Health` reports heartbeat. Default check history retention is 90 days, with up to 1,000 expired checks removed per cleanup run, at most once per hour. **Incidents are not subject to this raw-check cleanup.**

Cron command for a site installed at the root of `monitoring.persisca.com` in Plesk (replace actual vhost path and PHP version):

```bash
/opt/plesk/php/8.2/bin/php /var/www/vhosts/monitoring.persisca.com/httpdocs/cron/monitor.php
```

Another Plesk path layout may differ. For cPanel:

```bash
/usr/local/bin/php /home/USERNAME/public_html/monitor/cron/monitor.php
```

Recommended cron expression: `* * * * *`. From application root a manual CLI run is `php cron/monitor.php`. The public web route must never execute cron.

## UI and public privacy

- Dashboard displays real Total Monitors, Up, Down, Active Incidents and recent confirmed outages, plus recent check diagnostics and cron health. No fake uptime statistics.
- Monitors list shows Up/Down/Unknown/Paused separately from last check; below-threshold failures have a pending warning.
- Monitor detail has current state, failure progress, last success/failure, active incident link, recent incident list, and paginated scheduled check history.
- Incidents screen offers Active and History; History has 20/page pagination. Incident detail shows first failure, detection, recovery, duration, check IDs, error metadata, linked checks.
- Public `/status/` lists only `is_public=1` monitors and their factual confirmed state. `up` maps to Operational, `down` to Outage, `unknown` to Status pending, `paused` to Monitoring paused. Overall public state is based solely on these public monitors and is conservative when unchecked. Only **public, active** incident details are shown; private names/URLs/errors are never queried for the page.
- Design remains responsive, uses existing CSS variables, white-label logos and OG metadata.

## Installer, permissions, upgrade from v0.0.3

**Existing installations must NOT run the installer again.** They keep `config/database.php` and `storage/installed.lock` on the server. Steps:

1. Backup site files and database, ensure deployed Stage 03 is functioning, and temporarily pause the server cron during the upgrade.
2. Extract `monitor-patch-v0.0.4.zip` over the existing installation root (`monitoring.persisca.com` Document Root if installed there). The patch has no wrapper folder.
3. Apply `monitor-sql-patch-v0.0.4.sql` **once**, after backing up the DB. It adds only Stage 04 columns, creates incidents, extends monitor_checks, and sets installed_version to v0.0.4. It does not delete/reset existing monitors, raw checks, accounts, settings or cron metadata.
4. Check `/admin/` displays v0.0.4 and current monitors are still present. Confirm `/admin/incidents/`, `/admin/monitors/`, and `/status/`.
5. Resume the Plesk cron and confirm it runs. Perform controlled test with a known reachable endpoint and (separately) an intentionally failing endpoint, with confirmation threshold=2 and short interval, then restore configuration. Verify first failure pending, second failure opens incident, recovery closes once.
6. Do not re-upload config, installer lock, customer uploads, or server logs from a FULL archive.

Fresh install from the FULL v0.0.4 archive uses `database/schema.sql` directly, with the complete schema and browser installer `install/index.php` creating the first administrator. The installer locks after a successful run. The standalone SQL migration is **not** applied to a fresh v0.0.4 database. Use 644 file permissions and 755 directories in ZIP archives; sensitive config can be 600/640 when supported by the hosting user. Directories containing secrets have `.htaccess` deny rules; Nginx-only hosts require equivalent server rules.

## Verification and limitations

**Code-level verification:** PHP syntax lint on the packaged source, Stage 03 compatibility smoke checks, and deterministic Stage 04 state/validation/route/privacy checks; ZIP integrity and 644/755 permission checks. Detailed test output is in the build notes/tests scripts. Actual migration compatibility and state transitions need testing on an installed MySQL host.

**Live host verification still required:** a real MySQL upgrade preserving existing data, cron/check insertion, row lock/concurrent execution, actual cURL HTTP/HTTPS including SSL verification, incident open/continuation/recovery, public status, mobile screens and Plesk permissions on `https://monitoring.persisca.com/`. The generation environment lacks PDO MySQL, cURL, and a MySQL server. Do not present offline assertions as live host verification.

Known limits: no Telegram/email/webhook alerts or notification logs; no long-term uptime percentages, retention aggregation, or public historical timeline; no administrative manual incident resolution, maintenance windows, SLA analytics, SSL/domain expiry checks, multi-admin user UI or RBAC. If a DOWN monitor is paused, its incident remains open by design until a successful scheduled check after resuming. Removing a monitor permanently removes its incident and raw-check history. Raw checks may expire while incident summaries are preserved.

## Completed stages & next stage

- Stage 01 v0.0.1: native PHP, installer, admin auth, white-label branding, admin/public shell, metadata.
- Stage 02 v0.0.2: monitor CRUD, manual cURL check, private IP safeguards, real folder-style URLs.
- Stage 03 v0.0.3: CLI cron engine, due checks, history, snapshots, cron heartbeat/lock and retention.
- **Stage 04 v0.0.4:** confirmed operational status, failure confirmation threshold, one open incident, recovery, history, dashboard/public state.
- Next **Stage 05:** Telegram bot/token/Chat ID settings, connection test, per-monitor notification preferences, DOWN/RECOVERED alerts from committed state transitions, deduplicated delivery, failure logs, and ideally durable delivery retry/outbox. Do not send Telegram from IncidentManager itself.

**Authoritative baseline for Stage 05: `monitor-full-v0.0.4.zip` plus `MONITOR-FULL-PROJECT-v0.0.4.md` once deployed and accepted.**
