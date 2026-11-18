# Monitor: Full Project Reference v0.0.3

**Current authoritative source:** `monitor-full-v0.0.3.zip`  
**Current incremental patch:** `monitor-patch-v0.0.3.zip`  
**Upgrade SQL:** `monitor-sql-patch-v0.0.3.sql`  
**Previous installed baseline:** `v0.0.2`, specifically the corrected-permissions Stage 02 FULL ZIP.  
**Next stage:** Stage 04: Incident & Recovery Engine.

## 1. Purpose, implementation status, and history

Monitor is a self-hosted HTTP/HTTPS availability monitoring product for PHP/MySQL shared hosting. It is **English-only**, **fully white-label**, and intended to run on a server separate from the monitored sites whenever possible. Its planned scope includes a management panel, status page, check scheduling, incident tracking, recovery records, alerts, and history. At v0.0.3 it performs real scheduled HTTP checks when the hosting provider runs the CLI cron command.

**Cumulative stages:**

- **Stage 01 / v0.0.1:** PHP application foundation, installer, database configuration, initial admin, password/session/CSRF protection, settings, branding image uploads, OG/meta defaults, responsive admin UI, public status shell, health page.
- **Stage 02 / v0.0.2:** actual folder-style routing, monitor CRUD, Active/Paused and Public/Private, validated check preferences, HTTP cURL diagnostic with retries, private-target opt-in, manual Test Now, persisted latest manual diagnostics, DB-backed dashboard counts. The original Stage 02 archive accidentally included restrictive file permissions. The corrected-permissions archive (644 files, 755 directories) was made authoritative before Stage 03.
- **Stage 03 / v0.0.3:** CLI scheduled monitoring engine, global cron lock, UTC scheduling and runtime snapshots, `monitor_checks` persistent history, actual response-time/failure records, cron heartbeat and logging, recent checks and history pagination, health status, check retention foundation, upgrade-safe schema migration.

**Not implemented:** incident creation/closure, sustained DOWN confirmation, recovery detection, DOWN/recovery Telegram delivery, email/webhook alerts, public operational state, uptime percentages, SSL expiry or domain expiry. A failed check is NOT a confirmed outage. No fake uptime statistics or incidents are displayed.

## 2. Technical architecture

- PHP **8.1+**, PDO MySQL, MySQL/MariaDB InnoDB, cURL, fileinfo, JSON, PHP sessions, HTML, CSS, and vanilla JavaScript. No Laravel, Symfony, Node/npm, Composer, Redis, root access, daemons or long-running workers required.
- Shared-hosting-friendly physical route directories, each with an `index.php`. No normal route requires `?page=` or URL rewrite rules. `?id=N` selects an individual record without routing by query parameter.
- `bootstrap.php` registers PSR-like `Monitor\` class autoloading, helpers, error handling, and browser security headers. Normal web sessions remain; CLI cron defines `MONITOR_CRON` and avoids starting a session.
- Database: `app/Core/Database.php` creates a PDO MySQL connection. Every dynamic SQL value uses prepared statements, except for validated/clamped static numeric `LIMIT`/`OFFSET` interpolation.
- HTTP: `app/Services/HttpMonitor.php` performs GET checks, redirect handling with limits, validated HTTP(S) URLs, cURL TLS verification and DNS-pinned `CURLOPT_RESOLVE` to enforce the private-network policy. Requests discard response bodies.
- Scheduling: `cron/monitor.php` is CLI-only and delegates to `app/Services/CronRunner.php`. It reuses the same `HttpMonitor::check()` service as the manual check UI. `app/Models/CheckRepository.php` handles due queries and transactionally persists the history and monitor runtime snapshot.
- Cron health: `app/Models/SystemMeta.php` maintains heartbeat keys in the existing `system_meta` table. `System Health` shows status and timestamps with application timezone conversion.
- Incident and notification routing remain deliberate placeholders; do not conflate individual check results with incidents.

## 3. Full source tree

```text
/
  .htaccess
  VERSION                              v0.0.3
  README.md
  MONITOR-FULL-PROJECT-v0.0.3.md
  index.php
  bootstrap.php
  app/
    .htaccess
    Controllers/                       reserved
    Core/Database.php
    Core/Security.php
    Helpers/functions.php
    Models/Settings.php
    Models/MonitorRepository.php
    Models/CheckRepository.php         NEW
    Models/SystemMeta.php              NEW
    Services/Auth.php
    Services/HttpMonitor.php           shared manual + scheduled checks
    Services/MonitorInput.php
    Services/Upload.php
    Services/CronRunner.php            NEW
    Views/
      admin/dashboard.php
      admin/monitors.php
      admin/monitor-form.php
      admin/monitor-detail.php         NEW
      admin/general.php
      admin/branding.php
      admin/system.php
      admin/placeholder.php
      auth/login.php
      public/status.php
      partials/admin-top.php
      partials/admin-bottom.php
  admin/
    .htaccess
    _dispatch.php
    index.php
    login/index.php
    logout/index.php
    monitors/index.php
    monitors/add/index.php
    monitors/edit/index.php
    monitors/view/index.php            NEW
    incidents/index.php
    notifications/index.php
    status-page/index.php
    settings/general/index.php
    settings/branding/index.php
    system/index.php
  status/index.php
  public/
    index.php
    assets/
      css/app.css
      js/app.js
      images/status-og-default.jpg
      uploads/.htaccess
      uploads/index.html
  install/index.php
  cron/
    .htaccess
    monitor.php                        real CLI worker
  config/.htaccess
  database/
    .htaccess
    schema.sql                         latest complete schema
    patches/v0.0.1.sql
    patches/v0.0.2.sql
    patches/v0.0.3.sql                 NEW incremental migration
  storage/
    .htaccess
    logs/.htaccess
    cache/.htaccess
  tests/
    .htaccess
    smoke.php
```

**Runtime files excluded from archives:** `config/database.php`, `storage/installed.lock`, `storage/cron-monitor.lock`, `storage/logs/monitor-cron.log`, runtime upload files, sessions, passwords and tokens. The first two are created by the installer; the cron creates its own lock and log as needed. `storage/` and its subfolders must be writable by the actual PHP CLI user. ZIP distribution uses **644 files / 755 directories**. Do not change file permissions to 600 as occurred in the first Stage 02 packaging attempt.

## 4. Routes

| Route | Actual purpose |
|---|---|
| `/` | public root, uses the Stage 01/02 public controller |
| `/status/` | public status shell, without fabricated operational states |
| `/admin/` | dashboard, live monitor and check counts, last cron, recent failures |
| `/admin/login/` | session-backed administrator login |
| `/admin/logout/` | logout form, POST/CSRF |
| `/admin/monitors/` | list with scheduled result, next check, Test Now, Edit, Pause/Resume, Delete |
| `/admin/monitors/add/` | POST/CSRF create |
| `/admin/monitors/edit/?id=N` | POST/CSRF monitor config, latest manual diagnostic |
| `/admin/monitors/view/?id=N&p=2` | NEW, monitor runtime and scheduled check history, 20 per page |
| `/admin/incidents/` | placeholder; not yet an incident engine |
| `/admin/notifications/` | placeholder |
| `/admin/status-page/` | placeholder |
| `/admin/settings/general/` | branding/general/timezone, retention and cron settings |
| `/admin/settings/branding/` | logo/icon, OG and meta configuration |
| `/admin/system/` | environment checks, monitor/check table health, cron heartbeat |
| `/install/` | 6-step installer for **fresh installation only** |
| `/cron/monitor.php` | **CLI only**, HTTP requests rejected with 404 |

`admin/_dispatch.php` validates a fixed route allowlist and requires an authenticated administrator except for login; all data changes use POST plus CSRF. `app/Helpers/functions.php` provides `admin_url()`, `monitor_base_path()`, `status_url()`, `asset_url()`, `display_date()` and related helpers. Subfolder installs remain supported. Legacy Stage 01 query-route bookmarks can redirect to physical routes using `admin/index.php` compatibility logic. Do not reintroduce a visible `?page=` router. The `?id=` and `?p=` parameters select a record and history page only.

## 5. Database: full schema and indexes

`database/schema.sql` is the canonical **fresh-install** schema. It defines the six tables below. The browser installer loads it directly and sets `system_meta.installed_version` to `v0.0.3`.

1. `users`: `id`, `name`, `email`, `password_hash`, `role`, `status`, `last_login_at`, `created_at`, `updated_at`. Both existing `admin` and future `super_admin` roles remain compatible with authentication. No account management UI yet.
2. `settings`: `setting_key` PK, `setting_value`, `updated_at`. Branding and cron preferences, not DB credentials.
3. `system_meta`: `meta_key` PK, `meta_value`, `updated_at`. Install version, cron heartbeat and optional cleanup stamp.
4. `login_attempts`: account and IP hashes for login throttling, indexed by hash and attempted time.
5. `monitors`: Stage 02 configuration and manual diagnostic columns preserved; Stage 03 adds scheduled snapshot fields and due time.
6. `monitor_checks`: NEW, fact-based per-run check history.

### 5.1 `monitors`: Stage 02 columns retained

- `id` BIGINT UNSIGNED PK; `name` VARCHAR(160); `url` VARCHAR(2048).
- `status` VARCHAR(24) DEFAULT `not_monitored`: legacy/reserved for Stage 04 transition logic. **Stage 03 does not update it.**
- `is_active` TINYINT: 1 active / 0 paused. Only active rows are scheduled.
- `is_public` TINYINT: future public eligibility. Stage 03 public status output does not expose live check outcomes.
- `allow_private_network` TINYINT: explicit trusted-target opt-in; default 0.
- `check_interval_minutes` SMALLINT: allowed 1, 2, 5, 10, 15, 30, 60; default 5.
- `timeout_seconds` TINYINT, default 10; `expected_status_codes` VARCHAR(255), default `200`; `retry_count` TINYINT, default 2; `retry_delay_seconds` TINYINT, default 2.
- `last_manual_test_at`, `last_manual_test_success`, `last_manual_test_http_status`, `last_manual_test_response_ms`, `last_manual_test_error_type`, `last_manual_test_error`, `last_manual_test_effective_url`, `last_manual_test_attempts`: retained, separate diagnostic snapshot. Never overwritten by cron.
- `created_at`, `updated_at` DATETIME, UTC.

### 5.2 `monitors`: Stage 03 added columns

| Column | Purpose |
|---|---|
| `last_checked_at DATETIME NULL` | UTC completion time of latest **scheduled** check |
| `last_check_success TINYINT(1) NULL` | Latest individual scheduled result (1/0); NOT confirmed incident status |
| `last_http_status SMALLINT UNSIGNED NULL` | HTTP code if observed |
| `last_response_time_ms INT UNSIGNED NULL` | Last measured cURL time in milliseconds |
| `last_error_type VARCHAR(100) NULL` | Category from HTTP checker or Check Exception |
| `last_error_message TEXT NULL` | Short safe error text; escaped in admin |
| `last_attempt_count SMALLINT UNSIGNED NULL` | Total attempts for the latest scheduled check |
| `next_check_at DATETIME NULL` | UTC due time; NULL means eligible next cron run if active |

Added index: `idx_monitors_due (is_active, next_check_at)`. Stage 02 indexes `idx_monitors_active`, `idx_monitors_public`, `idx_monitors_updated` remain.

### 5.3 `monitor_checks` (NEW)

| Column | SQL type | Meaning |
|---|---|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT PK | Unique check ID |
| `monitor_id` | BIGINT UNSIGNED NOT NULL | References `monitors.id` |
| `check_type` | VARCHAR(12) | `scheduled` currently; `manual` reserved for possible future unification |
| `success` | TINYINT(1) | Final check result |
| `http_status` | SMALLINT UNSIGNED NULL | Final HTTP code if available |
| `response_time_ms` | INT UNSIGNED NULL | cURL elapsed milliseconds |
| `error_type` | VARCHAR(100) NULL | Friendly error category |
| `error_message` | TEXT NULL | Admin-only diagnostic, max 2,000 chars in service |
| `attempt_count` | SMALLINT UNSIGNED NOT NULL | Initial request + retries used |
| `effective_url` | TEXT NULL | Final URL if known, max 2,048 chars in service |
| `checked_at` | DATETIME NOT NULL | UTC completion time |
| `created_at` | DATETIME NOT NULL | UTC row creation time |

Indexes: `idx_checks_monitor_date (monitor_id, checked_at)` for detail/history; `idx_checks_date (checked_at)` for retention and date queries; `idx_checks_success_date (success, checked_at)` for failed-check filtering. Foreign key `fk_monitor_checks_monitor` uses `ON DELETE CASCADE`; deleting a monitor intentionally removes its raw check history. No soft-delete or incident retention in this stage.

### 5.4 `system_meta` runtime keys

- `installed_version = v0.0.3`
- `cron_last_started_at` UTC time, updated before due selection
- `cron_last_completed_at` UTC time, updated on normal completion or handled fatal error
- `cron_last_status`: `running`, `success`, `partial`, `failed`
- `cron_last_processed_count`: count actually saved this invocation
- `cron_last_error`: short non-secret summary; never stack traces
- `cron_last_cleanup_at`: UTC timestamp of last successful hourly retention attempt

## 6. Scheduling and cron engine logic

Entry: `cron/monitor.php` checks `PHP_SAPI === 'cli'` before requiring bootstrap. HTTP requests exit 404. If installation is missing, CLI exits with code 1. It invokes `CronRunner::run()` and prints a short summary. The entry process is **not** callable by web request. No additional daemons, websockets or background services are required.

### 6.1 Global lock, concurrent invocations, exceptions

`CronRunner` opens `storage/cron-monitor.lock` with `fopen(..., 'c')`, sets mode 0600 where possible, and attempts `flock(LOCK_EX | LOCK_NB)`. A second invocation returns immediately with a skipped summary without running checks or overwriting the active run's heartbeat. The lock is tied to the open file handle and released in `finally` even on errors, so stale lock files do not prevent new runs. Permissions for the user running cron must allow access to `storage/` and `storage/logs/`.

One monitor failure is isolated inside a try/catch. If the HTTP service itself throws, a failed `Check Exception` result is generated, with no low-level exception details exposed to public pages. Database failures on one record are logged with the monitor numeric ID and exception class, and subsequent monitors continue. Fatal global errors mark the last run `failed` where database writes remain possible. Log file is `storage/logs/monitor-cron.log` and contains timestamps, run counts, and generalized error types, but not credentials or entire website response bodies.

The global `flock` protects multiple CLI invocations on one shared file system. Stage 03 is not a distributed multi-host job scheduler. Before each target runs, `CheckRepository::dueNow()` re-reads the active/due state to honor changes between batch selection and execution. Check persistence uses a DB transaction with `SELECT id ... FOR UPDATE`, INSERT and monitor UPDATE so history and snapshot commit together. This protects against partially saved check data and concurrent monitor deletion. A separate cron from a different filesystem could still cause duplicate checks: do not operate multiple independent Monitor installations against the same DB without additional distributed coordination.

### 6.2 Due logic

```sql
SELECT * FROM monitors
WHERE is_active = 1
  AND (next_check_at IS NULL OR next_check_at <= UTC_TIMESTAMP())
ORDER BY next_check_at ASC, id ASC
LIMIT 25;
```

The batch limit is configurable between 1 and 100, default 25. This query is backed by `idx_monitors_due`. When a new monitor is created or any monitor is edited or resumed, `next_check_at` remains or is set to NULL so active monitors are eligible on the next invocation. Paused monitors never enter the due selection, but admins may still use manual Test Now while paused.

### 6.3 Shared HTTP check behavior

`HttpMonitor::check()` uses the same code path for scheduled and manual checks. An HTTP response counts as successful only if the final HTTP code is among the monitor's explicitly configured `expected_status_codes` after following up to five redirects. A final HTTP 500 with accepted status `200` stores `success=0`, `error_type='HTTP Status Error'`, `http_status=500`. SSL verification remains on (`CURLOPT_SSL_VERIFYPEER=true`, `CURLOPT_SSL_VERIFYHOST=2`). Only HTTP and HTTPS URLs are accepted; private/reserved destinations and redirects are blocked unless that monitor's `allow_private_network=1` setting opts in. IP addresses are DNS-resolved and pinned for cURL to mitigate rebinding. No shell commands or response-body persistence are used.

**Retries:** `retry_count` counts extra attempts after the initial request. A count of 2 permits up to 3 total attempts, with `retry_delay_seconds` between attempts. Success ends the retry loop. Last-result `response_time_ms` represents the final attempt's total cURL time across redirect hops, not the sum of retries and sleeps. `attempt_count` captures the number of attempts. `checked_at` is the UTC completion timestamp.

### 6.4 Scheduling completion strategy

After a result is saved, `next_check_at = UTC_TIMESTAMP() + INTERVAL check_interval_minutes MINUTE` if the monitor is still active; otherwise NULL. This avoids catch-up storms after outages of the cron scheduler. The monitor snapshot and check history update in one transaction. The next cron execution selects only due records. A cron run set for every minute can support a 5-minute monitor interval without checking that monitor every minute. On hosts that permit a cron run only every five minutes, checks may run later than their ideal due time. Existing config counts remain real, not simulated.

**Per-run execution safety:** default batch size 25 (configurable 1–100). There is a **soft target** to finish around 50 seconds: the runner stops starting additional checks after it reaches 50 seconds, but it never aborts a request mid-flight. A single check with long timeouts, redirect chains or retries may exceed 50 seconds (and even exceed a minute). While it runs, overlapping cron jobs will exit due to the global lock. This is not a hard runtime guarantee and is documented so operators can set sane timeout/retry settings.

### 6.5 Cron health / heartbeat

`SystemMeta::putMany()` writes `cron_last_started_at`, `cron_last_status='running'` before due selection and `cron_last_completed_at`, `cron_last_status`/counts after the batch. `System Health` displays `Never Run`, `Healthy`, `Delayed`, `Stale`, `Running`, or `Last Run Failed`. It compares last completion with UTC; thresholds are based on `cron_stale_after_minutes` (default 10), with `Stale` after twice the configured threshold. A partial run with per-monitor DB errors is classified as Last Run Failed. No Telegram alerts for cron exist in this stage.

### 6.6 Retention foundation

`check_retention_days` defaults to 90 (allowed 7–3650). At most once per hour, cron deletes **up to 1,000** `monitor_checks` rows older than the retention threshold, using an indexed `checked_at` query. The previous successful cleanup timestamp is stored in `cron_last_cleanup_at`. Errors in cleanup are logged but do not prevent scheduled check results from being saved. The bounded cleanup protects shared hosting, but can fall behind when more than 24,000 rows expire daily. This is a foundation, not a strict high-volume retention guarantee. Future stages can increase throughput or add a dedicated cleanup job. Removing old raw checks does not currently affect incident history, since incident functionality is not yet implemented.

## 7. Admin screens and dashboard

- **Dashboard (`/admin/`)**: Total Monitors, Active, Paused, and Checks Today (UTC) all derive from real DB queries. Recent monitors show the latest scheduled-check result, not an incident state. Side panels include last cron completion and Recent Failed Checks. An individual failed result is explicitly NOT called an outage.
- **Monitors (`/admin/monitors/`)**: Active or Paused, Public/Private indicator, Last Scheduled Check (Successful/Failed/Never Checked), HTTP status, response time, Next Check (due/UTC converted to configured timezone), interval, and actions (History, Edit, manual Test Now, Pause/Resume, Delete). Mobile view supports horizontal table scrolling and safe URL wrapping.
- **Monitor details (`/admin/monitors/view/?id=N`)**: configuration, visibility, timeout/retry/status preferences, latest scheduled result, HTTP/response/attempts, last/next check time, safe error text, manual Test Now action, and scheduled history table. Check-history page size is 20 with previous/next pagination via `&p=N`; errors are escaped. Manual checks remain visible as a latest diagnostic in the Edit screen.
- **Add/Edit (`/admin/monitors/add/`, `/admin/monitors/edit/?id=N`)**: Stage 02 form preserved, including server validation, private network opt-in, and manual test diagnostics. Saving monitor configuration makes active monitor due next cron. Toggling pause/resume and deletion continue to require POST+CSRF; deletion includes a confirmation.
- **System Health (`/admin/system/`)**: PHP/MySQL/cURL, config and lock, storage/log permissions, CLI cron script presence, folder entries, monitor and check tables, cron heartbeat and safe error summary, configured batch and retention.
- **General Settings**: adds Check retention days, Cron stale threshold, and Maximum monitors per run, with server-side range validation. Existing brand/app/timezone/footer/link preferences are preserved.
- **Branding and login**: preserved from Stage 01. Logos, favicon, OG image/meta, product names, and footer remain driven by the settings table. The public page remains status-neutral until Stage 04/07.

No UP/DOWN incident transition engine, Telegram notification, historical uptime percentage, or public outage feed is implemented. The future Stage 04 engine should build on `last_checked_at`, `last_check_success`, `monitor_checks` and `status`, but avoid mistaking a single failure for a confirmed incident.

## 8. Security model and white labeling

- Every admin page except login requires an active authenticated administrator. `Auth` uses password hashing, session renewal, login throttling and the retained `users.role` field, including future `super_admin` compatibility. Admin Users UI not yet implemented.
- All monitor edits, tests, pause/resume, deletion, logout and settings saves are POST and CSRF protected. IDs must be positive integers. All database values with user inputs use prepared statements. Values printed into HTML use `e()` escaping.
- `cron/monitor.php` rejects web access before loading application bootstrap. `.htaccess` blocks access to private app, config, database, cron, logs/storage and tests paths for Apache/LiteSpeed. For Nginx-only hosting, corresponding location deny rules must be configured separately; `.htaccess` does not protect Nginx by itself.
- No API credentials or DB credentials in distributed ZIPs. Runtime sensitive files and custom branding uploads must not be overwritten by an upgrade. Installer lock remains in place and must never be deleted to force an upgrade.
- `HttpMonitor` follows redirect policy and pins resolved IPs. Default SSL certificate and hostname verification remain enabled. Private-network targets require per-monitor opt-in and should be enabled only for trusted administrators. This is an admin-only tool, not a public URL fetch API.
- Cron logs use error classes, numeric monitor IDs and aggregate counts. No full response body or secret tokens are logged or placed in the admin public page.
- All product UI text remains **English-only**. No visible developer credits or permanent branding are hard-coded.

## 9. Installation and upgrade

### 9.1 Upgrade existing v0.0.2 (including the repaired-permissions v0.0.2)

1. **Back up all files and the database.** The installed `config/database.php`, `storage/installed.lock`, existing user accounts, branding/uploads and monitors must be preserved.
2. Extract `monitor-patch-v0.0.3.zip` over the currently installed application; do not add a wrapper folder. ZIP permissions are corrected **644 files and 755 directories**. The patched source includes the new physical history route and cron engine.
3. Apply `monitor-sql-patch-v0.0.3.sql` **once** using phpMyAdmin to the v0.0.2 database. It adds eight nullable monitor runtime columns, one composite monitor due index, `monitor_checks` and its indexes/FK, updates installed-version metadata, and inserts missing cron settings without overriding any existing settings. It does not truncate/drop users, settings, branding or monitors.
4. Do **not** rerun `/install/` or delete the installation lock. Keep existing config and uploads intact. Optional cleanup: delete the obsolete root `MONITOR-FULL-PROJECT-v0.0.2.md` from the live server; it is protected by `.htaccess` and does not influence functionality.
5. Verify `/admin/login/`, `/admin/`, `/admin/monitors/`, `/admin/settings/general/`, `/admin/settings/branding/`, `/admin/system/` and `/status/`, confirming that legacy data remains.
6. Schedule `cron/monitor.php` from a CLI PHP binary. Run it manually once if terminal access exists. Verify `monitor_checks` rows, `last_checked_at`, `next_check_at`, and System Health heartbeat. Manually test a paused monitor and verify scheduled state does not change.
7. Verify `VERSION` reports `v0.0.3`. Backup before SQL import because MySQL DDL auto-commits; if import partially fails, examine which columns/tables exist before attempting another import.

**Important:** The Stage 03 SQL patch is an incremental **once-only** migration from v0.0.2, not a standalone fresh schema. Do not import Stage 01/02 patches again, and do not reinstall Monitor. No Stage 03 code intentionally resets monitor data.

### 9.2 Fresh v0.0.3 installation

1. Extract `monitor-full-v0.0.3.zip` directly into the desired site directory with normal file permissions.
2. Create an empty MySQL/MariaDB database and open `/install/index.php`.
3. Complete the existing six-step wizard. It loads `database/schema.sql` with all six tables and writes `system_meta.installed_version='v0.0.3'`, the administrator, app settings, config and installer lock.
4. Open `/admin/login/`, add monitors and configure cron as below. **No historical SQL patches** are required for fresh installation.

## 10. Cron setup for cPanel/Plesk

Choose the host's **Run a command** scheduled-task type and the shortest available frequency, preferably every minute (`* * * * *`). Use the CLI PHP binary, NOT a browser URL. Examples below are placeholders; your host's actual PHP version and document root can differ.

**Plesk:**

```bash
/opt/plesk/php/8.2/bin/php /var/www/vhosts/DOMAIN/httpdocs/cron/monitor.php
```

**cPanel:**

```bash
/usr/local/bin/php /home/USERNAME/public_html/cron/monitor.php
```

If installed in a `/monitor/` subdirectory, include `/monitor/` in the physical file path. On a subdomain such as `monitoring.persisca.com` with the app installed directly in that domain's document root, do not automatically append a second `/monitor/` folder. Verify the correct physical path in Plesk Files / Hosting Settings. A working cron must run under a user able to read the site's config, access PHP extensions `pdo_mysql` and `curl`, and write the protected `storage/` directories.

**Local manual verification from app root:**

```bash
php cron/monitor.php
```

The CLI prints `Monitor Cron v0.0.3`, selected due count, processed count, successful, failed, processing errors and `Completed.`. Overlapping runs print `Another cron run is active. Skipped.` (exit code 0). CLI errors exit nonzero and show a sanitized error class, not stack traces. Logging and health information are available under `/admin/system/` and `storage/logs/monitor-cron.log`.

## 11. Verification and known limitations

### 11.1 Code-level verification in artifact build environment

- PHP `-l` syntax check of all current PHP files (no errors).
- `php tests/smoke.php`: **76/76** tests passed, including the prior Stage 02 validation/security/routing checks plus new SQL/schema, due logic, transaction/FK assertions, cron locking, CLI restriction, route/history presence, health states, and retention defaults.
- ZIP archives inspected for correct project-relative layout and permissions. Patch contents are only added/changed files; the FULL ZIP is the complete current source, excluding runtime secrets.

### 11.2 Live host verification **NOT completed here**

- MySQL/MariaDB schema migration against a previously installed v0.0.2 database; fresh installer against an empty live database; foreign-key behavior with monitor deletion.
- Actual running CLI PHP with `pdo_mysql`, `curl`, DNS, TLS, a real outbound reachable URL, a refused URL, expected HTTP error, retry behavior and check result persistence.
- Actual host cron schedule, overlapping cron process behavior under real Plesk CLI user, device/mobile visual testing, and storage/log write permissions.

The build environment does not provide a configured application database or PHP `pdo_mysql`/`curl`; static assertions do not replace end-to-end integration. The user must check the real host before treating automated monitoring as operational.

### 11.3 Remaining limitations

- No incident confirmation, recovery, outage history, Telegram alerts, emailed alerts, uptime analytics or public operational states. These are explicitly deferred to Stage 04 and subsequent work.
- A single scheduled check failure is shown as `Last Check: Failed` and, if recent, in `Recent Failed Checks`; it is not called a confirmed outage.
- Cron's 50-second limit is a **soft** target; long timeout/retry configurations can extend a run beyond a minute. The lock prevents concurrent launches from processing while that run is active.
- Cron and manual checks are synchronous and serial. Monitor batches above the configured cap are deferred to the next invocation. If cron frequency is slower than per-monitor intervals, scheduling occurs later than desired.
- Best-effort check retention is capped at 1,000 deletions/hour; large installations may require a dedicated maintenance job in later stages.
- Multi-server distributed cron locking, teams/multi-tenancy, and future Super Admin user-management UI are not present. The `users.role` column and authentication compatibility are preserved.
- Physical record IDs use `?id=N` and pagination `?p=N` but no `?page=` routing.
- Nginx-only hosts must replicate `.htaccess` protections; file mode 644/755 alone does not secure sensitive paths.

## 12. Stage 04 handoff: Incident & Recovery Engine

Use **this** FULL ZIP and FULL MD as the authoritative starting point, never an earlier v0.0.1 or v0.0.2 archive. Stage 04 will add confirmed DOWN state transitions, one active incident per outage, continued failures inside that same incident, recovery detection/closure, downtime duration, active incidents and incident history, and dashboard incident reporting. Stage 05 will introduce Telegram notifications. Keep `HttpMonitor`, `CronRunner`, `CheckRepository` and its persisted UTC scheduled checks as the input to Stage 04's state machine. Protect all prior features, installer, folder routes, English-only UI, white-label branding, security and future Super Admin compatibility.
