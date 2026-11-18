# Monitor — FULL Project Reference v0.1.0

## 1. Release identity and source of truth

- **Version:** `v0.1.0` (Stage 09, stabilization and production release candidate)
- **Authoritative baseline for development:** `monitor-full-v0.0.8.zip` and `MONITOR-FULL-PROJECT-v0.0.8.md`, incorporating all previous upgrades including multi-target Telegram, notification-page fixes, analytics, status page, and Admin Users.
- **Current authoritative release artifacts:** `monitor-full-v0.1.0.zip`, `monitor-patch-v0.1.0.zip`, `MONITOR-FULL-PROJECT-v0.1.0.md`, `monitor-sql-patch-v0.1.0.sql`, `MONITOR-v0.1.0-RELEASE-NOTES.md`.
- **Release status:** **Release Candidate — Live Host Verification Required**. Static/lint and isolated PHP regression tests pass; no verified live MySQL/Plesk/Telegram/browser QA in this environment.

No completed features have intentionally been dropped. This stage emphasizes hardening and quality rather than new monitoring protocols. The product is neutral and fully white-label: real installation values come from `settings`, not built-in agency names.

## 2. Runtime and architecture

Native **PHP 8.1+**, PDO MySQL, MySQL/MariaDB (InnoDB), PHP cURL, fileinfo, sessions, HTTPS recommended. Works with normal Plesk/cPanel shared hosting and CLI scheduled tasks; no Composer, Node, npm, Docker, queue daemon, Supervisor, Redis or root access required.

`bootstrap.php` registers the `Monitor\` autoloader, safe exception handler, secure session defaults and HTTP security headers. `app/Helpers/functions.php` owns routing and UI helpers. `app/Core/Database.php` uses PDO prepared statements (`ATTR_EMULATE_PREPARES=false`). `app/Core/Security.php` owns CSRF. Each public/admin route has a physical `index.php` entrypoint. `admin/_dispatch.php` implements central admin authorization, POST handlers, database-backed read models and view selection. Public content uses `PublicStatus`/`MonitorAnalytics` read services. All stored time values use UTC; display times use the configured timezone.

The app may be installed at domain root or in a subdirectory, but the physical `monitor_base_path()` detection and web-server configuration must agree. `.htaccess` blocks internal folders under Apache/LiteSpeed; deployments where nginx serves them directly need equivalent server-level rules.

## 3. Full source tree

The tree below is relative to the document root. Writable runtime directories are present with protected placeholder files. Server-generated secrets, session data and operational logs must not be distributed.

```text
.htaccess
MONITOR-FULL-PROJECT-v0.0.3.md
MONITOR-FULL-PROJECT-v0.0.4.md
MONITOR-FULL-PROJECT-v0.0.5.1.md
MONITOR-FULL-PROJECT-v0.0.5.2.md
MONITOR-FULL-PROJECT-v0.0.5.md
MONITOR-FULL-PROJECT-v0.0.6.md
MONITOR-FULL-PROJECT-v0.0.7.md
MONITOR-FULL-PROJECT-v0.0.8.md
MONITOR-FULL-PROJECT-v0.1.0.md
MONITOR-v0.1.0-RELEASE-NOTES.md
README.md
VERSION
admin/.htaccess
admin/_dispatch.php
admin/account/index.php
admin/incidents/index.php
admin/incidents/view/index.php
admin/index.php
admin/login/index.php
admin/logout/index.php
admin/maintenance/index.php
admin/monitors/add/index.php
admin/monitors/analytics/index.php
admin/monitors/edit/index.php
admin/monitors/index.php
admin/monitors/view/index.php
admin/notifications/index.php
admin/settings/branding/index.php
admin/settings/general/index.php
admin/status-page/index.php
admin/system/index.php
admin/users/add/index.php
admin/users/edit/index.php
admin/users/index.php
admin/users/password/index.php
app/.htaccess
app/Core/Database.php
app/Core/Security.php
app/Helpers/functions.php
app/Models/CheckRepository.php
app/Models/IncidentRepository.php
app/Models/MonitorRepository.php
app/Models/NotificationRepository.php
app/Models/Settings.php
app/Models/SystemMeta.php
app/Services/AdminUsers.php
app/Services/Auth.php
app/Services/Authorization.php
app/Services/CronRunner.php
app/Services/HourlyAggregator.php
app/Services/HttpMonitor.php
app/Services/IncidentManager.php
app/Services/Maintenance.php
app/Services/MonitorAnalytics.php
app/Services/MonitorInput.php
app/Services/NotificationManager.php
app/Services/PublicStatus.php
app/Services/RetentionManager.php
app/Services/TelegramDestinations.php
app/Services/TelegramNotifier.php
app/Services/Upload.php
app/Views/admin/access-denied.php
app/Views/admin/account.php
app/Views/admin/branding.php
app/Views/admin/dashboard.php
app/Views/admin/general.php
app/Views/admin/incident-detail.php
app/Views/admin/incidents.php
app/Views/admin/maintenance.php
app/Views/admin/monitor-analytics.php
app/Views/admin/monitor-detail.php
app/Views/admin/monitor-form.php
app/Views/admin/monitors.php
app/Views/admin/notifications.php
app/Views/admin/placeholder.php
app/Views/admin/status-page.php
app/Views/admin/system.php
app/Views/admin/user-form.php
app/Views/admin/user-password.php
app/Views/admin/users.php
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
database/patches/v0.0.5.1.sql
database/patches/v0.0.5.2.sql
database/patches/v0.0.5.sql
database/patches/v0.0.6.sql
database/patches/v0.0.7.sql
database/patches/v0.0.8.sql
database/patches/v0.1.0.sql
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
tests/.htaccess
tests/multichat.php
tests/notification-page.php
tests/smoke.php
tests/stage04.php
tests/stage05.php
tests/stage06.php
tests/stage07-render.php
tests/stage07.php
tests/stage08.php
tests/stage09.php
```

## 4. Folder-style routes

| Route | Function | Access |
|---|---|---|
| `/install/` | One-time six-step installer | Uninstalled only; fails closed after config/lock |
| `/` | Public entry (internally forwards) | Public |
| `/status/` | Canonical public status | Public, if enabled |
| `/admin/` | Dashboard | Both roles |
| `/admin/login/` | Login | Guest |
| `/admin/logout/` | CSRF-protected POST logout | Logged-in |
| `/admin/monitors/` | Monitors list | Both roles |
| `/admin/monitors/add/` | Add monitor | Both roles; private networks Super Admin only |
| `/admin/monitors/edit/?id=N` | Edit / Test Now | Both roles; private networks Super Admin only |
| `/admin/monitors/view/?id=N` | Check history | Both roles |
| `/admin/monitors/analytics/?id=N` | Availability/response analytics | Both roles |
| `/admin/incidents/` | Active and resolved incident lists | Both roles |
| `/admin/incidents/view/?id=N` | Incident detail | Both roles |
| `/admin/notifications/` | Telegram settings / delivery logs | Super Admin settings; standard Admin read-only |
| `/admin/status-page/` | Public status display options | Both roles |
| `/admin/settings/general/` | Branding-independent platform and monitoring settings | Super Admin |
| `/admin/settings/branding/` | Branding, SEO/OG and uploads | Super Admin |
| `/admin/system/` | Environment and Cron Health | Super Admin |
| `/admin/users/`, `/add/`, `/edit/?id=N`, `/password/?id=N` | Admin User management | Super Admin |
| `/admin/account/` | Current account/password | Both roles |
| `/admin/maintenance/` | Safe diagnostics and bounded operations | Super Admin |

Record IDs use `?id=...` only; there is no visible `?page=...` navigation. `admin/index.php?page=...` may redirect **legacy** bookmarks, not serve new query-routed pages. All destructive actions use POST + CSRF. Unauthorized routes enforce server-side role checks, not merely hidden menus.

## 5. Authentication, user roles, passwords and session invalidation

The `users` table supports `role` = `super_admin` or `admin`, `status` = `active` or `disabled`, `session_version` and UTC `last_login_at`. Fresh installer creates one active **Super Admin**. Existing Stage 08 installations already have this structure; the v0.1.0 SQL patch does not re-promote or reset accounts. Legacy Stage 07 migration logic remains in historical patch `database/patches/v0.0.8.sql` only for appropriate upgrades.

The installer, new users, Admin password resets and My Account password changes use **minimum 8 / maximum 256 characters** and `password_hash()` / `password_verify()`. Login is rate-limited. Successful login regenerates session ID, saves user id/session version and updates last-login time. A disabled user or changed session version loses access on the next protected request. Own password change rotates the session ID, increments the DB session version and retains the current session only. Last active Super Admin cannot be disabled/demoted; self-disable and self-demote are blocked. All role transitions are transaction-protected. Permanent user deletion is not exposed; disable accounts instead.

Standard Admin may operate ordinary public-network monitors, review incidents and analytics, inspect notification history, manage public status display, and change their own password. Super Admin alone may manage users, credentials, branding, General/System/Maintenance and **private-network monitoring**. Source routing authorization runs before user target records are retrieved to prevent unauthorized user-ID probing. Admin cannot turn on `allow_private_network`, edit an existing private-network-enabled monitor or execute its Test Now/toggle/delete operations.

## 6. Monitor CRUD and HTTP checks

`MonitorInput::fromPost()` validates name, http(s) target without credentials/fragment, expected HTTP status list, interval (1/2/5/10/15/30/60 min), timeout (1–60 sec), retries (0–5), retry delay (0–30 sec), failure threshold (1–5, default 2), activity/public flag and Telegram-per-monitor flag. Private network targets are opt-in and restricted to Super Admin.

`HttpMonitor::check()` uses PHP cURL, validates redirects hop-by-hop (up to 5), resolves host addresses and uses `CURLOPT_RESOLVE` to pin checked IPs against DNS rebinding. Public-target policy rejects private/reserved IPs unless explicitly permitted. TLS peer and hostname verification remain enabled. It drops body data using write callback and stores only HTTP status, response-time milliseconds, error class/message, attempts, effective URL and UTC check time. A retry count of 2 means **3 total requests** per check. `Test Now` saves its diagnostic snapshot in monitor fields and **does not** open/close incidents, affect the scheduled state or enqueue alerts.

## 7. Cron engine and schedule

CLI entry: `cron/monitor.php`. It requires installation and executes `CronRunner::run()` under nonblocking OS `flock` using `storage/cron-monitor.lock`. An old lock **file** alone does not block execution. Every invocation:

1. Acquires lock and records start heartbeat in `system_meta`.
2. Retrieves up to `cron_batch_size` due active monitors (default **25**). Paused and not-yet-due monitors are skipped.
3. Runs each HTTP check with configured retries and persists its scheduled result and monitor snapshot transactionally.
4. Calls `IncidentManager` for state transitions; enqueues Telegram event rows in the same transaction where eligible.
5. Dispatches a **bounded maximum of 3 Telegram deliveries** in this cron pass (subject to a 50-second soft elapsed budget), then aggregates up to 1,500 remaining scheduled checks and performs bounded daily retention.
6. Updates final heartbeat/status and releases lock.

After a scheduled check, `next_check_at` advances from completion/current UTC time by its interval; missed schedules are **not** replayed as a catch-up storm. One monitor failure is isolated from the next. Cron target is 50 seconds, not a hard kill: long HTTP timeout/retries or an in-progress Telegram request may exceed it. Dispatch deadline is checked **before** starting each outbound request. This avoids routinely starting ten 9-second Telegram calls in one minute. A queue backlog clears across future cron invocations.

Recommended scheduled task: **every minute** or shortest available. Example cPanel:

```bash
/usr/local/bin/php /home/USERNAME/public_html/monitor/cron/monitor.php
```

Plesk at domain document root:

```bash
/opt/plesk/php/8.2/bin/php /var/www/vhosts/DOMAIN/httpdocs/cron/monitor.php
```

Use the real domain root and PHP executable; add `/monitor/` only when actually installed inside that directory. Never run cron by HTTP. `CronRunner::health()` uses heartbeat/completion/status and a configurable stale threshold to report `Never Run`, `Healthy`, `Delayed`, `Stale`, `Last Run Failed`, `Running` or clock mismatch.

## 8. Incidents and recovery

`IncidentManager` executes inside the scheduled check transaction. Operational states are `unknown`/`up`/`down`; paused is a separate configuration setting. A monitor starts unknown; the first successful scheduled check confirms UP. Consecutive failed **scheduled checks** reach `failure_threshold` (default 2) before opening an outage. HTTP retry attempts inside one scheduled check do **not** count as separate threshold failures. Once opened, the incident uses `started_at` from the first failure in the sequence and `detected_at` from threshold confirmation. Continued failures update the same incident and never create another open incident. One successful scheduled check while DOWN resolves the incident, calculates `duration_seconds` from started to recovered, and clears `current_incident_id`. Pausing a down monitor does not fake recovery. Incidents and check history are FK-linked to monitor deletion; deleting a monitor intentionally deletes its dependent histories.

## 9. Telegram notifications, multi-chat outbox and retries

`TelegramNotifier` owns HTTPS POST to Telegram Bot API with TLS verification. `TelegramDestinations` accepts up to **10** unique destination Chat IDs, one per line, including negative group IDs and `@channel`. Secret Bot Token is stored in the protected `settings` table under `telegram_bot_token` and is **never rendered back into HTML** after save. Database access and DB backups can expose this token; protect them accordingly. Global `telegram_enabled`, global down/recovery toggles and per-monitor `telegram_notifications_enabled` gate delivery. Sending a Test Message never creates an incident and is CSRF-protected.

The real `down` and `recovered` transitions alone enqueue notifications. The outbox `notification_logs` has one logical row per **channel + incident_id + event_type + destination_chat_id**, enforced with `uniq_notification_target_event`. Each destination has independent sent/failed/pending status, attempt count (max **3**), provider message ID, safe error summary and retry timestamp. A processing lease permits retry after a crash. A provider success followed by PHP termination before DB status update can cause a duplicate; Telegram does **not** provide mathematically exactly-once delivery guarantees. Messages use the configured white-label app name and timezone. Repeated failed checks while already DOWN or manual Test Now do not trigger additional alerts.

## 10. Analytics and historical data

`MonitorAnalytics` reports observed **successful scheduled check samples / all scheduled check samples × 100** for 24-hour, 7-day and 30-day intervals; this is **sample availability**, not a claim that every unobserved minute was available. No-data periods show no fabricated percentage. Response-time averages/min/max use real observed measurements. `MonitorAnalytics::responseSeries()` uses bucketed points for charts instead of unbounded raw rows; `HourlyAggregator` persists hourly counts/sums/min/max with transactional cursor in `system_meta` for efficient long-lived analytics. Incident downtime is separately calculated from unioned time intervals clipped to the requested window, so overlaps are not double-counted. Current open incidents are clipped to the current time. Data before the first check and gaps are **unknown**. Exact paused-time interval accounting is limited by the absence of a persisted pause/resume event timeline; sample availability excludes unobserved periods. Older aggregated-only data can have small edge/timezone precision limits for non-hour UTC boundaries.

`RetentionManager` runs daily or manually, deletes at most **1,000** fully aggregated old raw check rows and **1,000** eligible resolved incident notification rows per pass, and never deletes incidents or hourly aggregates as part of routine retention. `check_retention_days` and `notification_log_retention_days` both default to **90** and are configurable (7–3,650). Large backlogs are handled in future runs. Aggregates remain indefinitely by current policy.

## 11. Public status page and Open Graph

`/status/` is the canonical public route. `PublicStatus` fetches **only** `is_public=1` monitors and incidents belonging to currently public monitors. It computes `All Systems Operational`, `Some Systems Experiencing Issues`, `Major Service Disruption`, `Status Pending` or `No Public Monitors` based on real active/paused/up/down state. Public cards show name, status, observed uptime, response-time information and accessible 30-day day-bars with `No Data` for days without scheduled observations. Public incidents show safe wording, active duration, and resolved history without raw cURL/database errors. Monitor infrastructure URLs, private monitor IDs/records, Telegram settings and notification logs are not exposed publicly.

Page title, meta description and OG title/description follow actual overall status and configured `app_name`. `og:url` and canonical point to `/status/`; OG image uses the configured image if present, otherwise the shipped neutral default image. The OG **image is static**, while OG text changes dynamically. Social networks can cache previews and may require manual re-scraping. Footer, support link, favicon, logo and timezone use saved white-label settings. The page is responsive and functional without JavaScript. No WebSockets or external CDN required.

## 12. Maintenance, health and diagnostics

Super Admin-only `/admin/maintenance/` reports Cron stale/failed state, schema-version mismatch (`VERSION` vs `system_meta.installed_version`), DB tables, storage writability, overdue/missing schedules, duplicate open incidents, broken incident pointers, invalid statuses, pending/failed notifications, active admin counts and read-only integrity counts. Actions are POST + CSRF + confirmation, bounded and share the nonblocking cron lock: run retention cleanup, process up to 500 unaggregated scheduled checks, repair up to 100 active monitors with NULL `next_check_at`. No arbitrary SQL, shell or terminal commands; no automatic incident closure. Changes are recorded as safe structured `maintenance_logs` events. The System Health screen reports Cron, checks, retention, Telegram destination counts and installed version without exposing Bot Token.

## 13. Security and release hardening

- Protected config/installer lock, `.htaccess` internal folder restrictions, non-executable uploads, random uploaded image names and file MIME/dimension/size checks.
- Secure session cookie flags, session rotation, active/role/version recheck, CSRF on POST, PDO prepared statements and escaped dynamic HTML.
- Every test PHP entry starts with a **CLI-only guard**, returning 404 even if nginx ignores Apache `.htaccess`. Cron is CLI-only. Host nginx restrictions should still deny direct access to internal folders and private files.
- The installer refuses an already configured/locked installation. Before executing **any** fresh-install DDL it checks that the target database contains **zero tables**, avoiding accidental schema writes to an unrelated database. Fresh-install DDL is not automatically reversible if the host fails midway; use a new empty DB to retry.
- Admin Users lookup now follows role enforcement; ordinary Admins cannot enable or operate private-network checks.
- Bounded Telegram delivery avoids routinely hogging the cron soft runtime. Dashboard preview uses a LIMIT 5 query rather than loading the full monitor list.
- Generic 403/404/500 error states do not render secrets, stack traces, database errors or raw exceptions. Safe 500 rendering uses an external local stylesheet compatible with the restrictive CSP.
- FULL ZIP excludes `config/database.php`, `storage/installed.lock`, operational logs, sessions, customer branding uploads and real Telegram credentials. Packaged files have UNIX 0644 and folders 0755. The uploaded file directory must remain writable by the PHP user.

## 14. Upgrade from accepted v0.0.8

1. **Back up** complete document root and database; temporarily pause CLI cron.
2. Extract `monitor-patch-v0.1.0.zip` directly into the current document root and overwrite changed files. **No wrapper folder**. Keep server DB config, `storage/installed.lock`, customer branding, logs and sessions.
3. Run `monitor-sql-patch-v0.1.0.sql` once via phpMyAdmin. This release SQL **only upserts installed_version to `v0.1.0`** and does not modify data/schema. Code and SQL should be deployed in the same maintenance window. If both are not deployed, Maintenance will report version mismatch.
4. Re-enable Cron; log in with existing credentials. Verify System Health, user roles, monitor checks, incidents, Telegram Test, status page, analytics and data retention.
5. Do **not** reinstall or run `database/schema.sql` on a populated installation. It is only for fresh setup.

Fresh v0.1.0 installation uses exactly `database/schema.sql`; no historical migration patches are required. `install/index.php` writes `installed_version = v0.1.0`, creates `storage/installed.lock` and fails closed on subsequent access. It does not require Telegram configuration to install.

## 15. Complete fresh-install SQL schema and indexes

This exact SQL is the `database/schema.sql` shipped with FULL ZIP. The release SQL upgrade above is separate and nondestructive.

```sql
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(32) NOT NULL DEFAULT 'admin',
  session_version INT UNSIGNED NOT NULL DEFAULT 1,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY users_email_unique (email),
  INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
  setting_value TEXT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_meta (
  meta_key VARCHAR(100) NOT NULL PRIMARY KEY,
  meta_value TEXT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email_hash CHAR(64) NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  attempted_at DATETIME NOT NULL,
  KEY attempts_email_time (email_hash, attempted_at),
  KEY attempts_ip_time (ip_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monitors (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  url VARCHAR(2048) NOT NULL,
  status VARCHAR(24) NOT NULL DEFAULT 'not_monitored',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_public TINYINT(1) NOT NULL DEFAULT 1,
  allow_private_network TINYINT(1) NOT NULL DEFAULT 0,
  check_interval_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 5,
  timeout_seconds TINYINT UNSIGNED NOT NULL DEFAULT 10,
  expected_status_codes VARCHAR(255) NOT NULL DEFAULT '200',
  retry_count TINYINT UNSIGNED NOT NULL DEFAULT 2,
  retry_delay_seconds TINYINT UNSIGNED NOT NULL DEFAULT 2,
  operational_status VARCHAR(12) NOT NULL DEFAULT 'unknown',
  failure_threshold TINYINT UNSIGNED NOT NULL DEFAULT 2,
  telegram_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1,
  consecutive_failures INT UNSIGNED NOT NULL DEFAULT 0,
  failure_sequence_started_at DATETIME NULL,
  last_success_at DATETIME NULL,
  last_failure_at DATETIME NULL,
  current_incident_id BIGINT UNSIGNED NULL,
  last_checked_at DATETIME NULL,
  last_check_success TINYINT(1) NULL,
  last_http_status SMALLINT UNSIGNED NULL,
  last_response_time_ms INT UNSIGNED NULL,
  last_error_type VARCHAR(100) NULL,
  last_error_message TEXT NULL,
  last_attempt_count SMALLINT UNSIGNED NULL,
  next_check_at DATETIME NULL,
  last_manual_test_at DATETIME NULL,
  last_manual_test_success TINYINT(1) NULL,
  last_manual_test_http_status SMALLINT UNSIGNED NULL,
  last_manual_test_response_ms INT UNSIGNED NULL,
  last_manual_test_error_type VARCHAR(60) NULL,
  last_manual_test_error TEXT NULL,
  last_manual_test_effective_url VARCHAR(2048) NULL,
  last_manual_test_attempts TINYINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_monitors_active (is_active),
  INDEX idx_monitors_public (is_public),
  INDEX idx_monitors_updated (updated_at),
  INDEX idx_monitors_due (is_active, next_check_at),
  INDEX idx_monitors_operational (operational_status),
  INDEX idx_monitors_active_incident (current_incident_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS monitor_checks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  monitor_id BIGINT UNSIGNED NOT NULL,
  check_type VARCHAR(12) NOT NULL DEFAULT 'scheduled',
  success TINYINT(1) NOT NULL,
  http_status SMALLINT UNSIGNED NULL,
  response_time_ms INT UNSIGNED NULL,
  error_type VARCHAR(100) NULL,
  error_message TEXT NULL,
  attempt_count SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  incident_id BIGINT UNSIGNED NULL,
  effective_url TEXT NULL,
  checked_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_checks_monitor_date (monitor_id, checked_at),
  INDEX idx_checks_date (checked_at),
  INDEX idx_checks_success_date (success, checked_at),
  INDEX idx_checks_incident_date (incident_id, checked_at),
  CONSTRAINT fk_monitor_checks_monitor FOREIGN KEY (monitor_id) REFERENCES monitors (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incidents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  monitor_id BIGINT UNSIGNED NOT NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'open',
  started_at DATETIME NOT NULL,
  detected_at DATETIME NOT NULL,
  recovered_at DATETIME NULL,
  duration_seconds BIGINT UNSIGNED NULL,
  opening_check_id BIGINT UNSIGNED NULL,
  closing_check_id BIGINT UNSIGNED NULL,
  failure_count INT UNSIGNED NOT NULL DEFAULT 1,
  last_error_type VARCHAR(100) NULL,
  last_error_message TEXT NULL,
  last_http_status SMALLINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_incident_monitor_status (monitor_id, status),
  INDEX idx_incident_status_start (status, started_at),
  INDEX idx_incident_monitor_start (monitor_id, started_at),
  INDEX idx_incident_recovered (recovered_at),
  CONSTRAINT fk_incidents_monitor FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS notification_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  monitor_id BIGINT UNSIGNED NOT NULL,
  incident_id BIGINT UNSIGNED NOT NULL,
  channel VARCHAR(24) NOT NULL DEFAULT 'telegram',
  destination_chat_id VARCHAR(128) NOT NULL DEFAULT '',
  event_type VARCHAR(24) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  provider_message_id VARCHAR(100) NULL,
  error_code VARCHAR(24) NULL,
  error_message VARCHAR(220) NULL,
  next_attempt_at DATETIME NULL,
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_notification_target_event (channel, incident_id, event_type, destination_chat_id),
  INDEX idx_notification_due (status, next_attempt_at),
  INDEX idx_notification_monitor_date (monitor_id, created_at),
  INDEX idx_notification_created (created_at),
  CONSTRAINT fk_notification_monitor FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE,
  CONSTRAINT fk_notification_incident FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Monitor v0.0.6 upgrade from the accepted v0.0.5.2 baseline.
-- Apply once after a database backup, while cron is temporarily paused.
-- Never rerun installation or import database/schema.sql over existing data.
CREATE TABLE IF NOT EXISTS monitor_hourly_stats (
  monitor_id BIGINT UNSIGNED NOT NULL,
  hour_start DATETIME NOT NULL,
  scheduled_check_count INT UNSIGNED NOT NULL DEFAULT 0,
  successful_check_count INT UNSIGNED NOT NULL DEFAULT 0,
  failed_check_count INT UNSIGNED NOT NULL DEFAULT 0,
  response_sample_count INT UNSIGNED NOT NULL DEFAULT 0,
  sum_response_time_ms BIGINT UNSIGNED NOT NULL DEFAULT 0,
  min_response_time_ms INT UNSIGNED NULL,
  max_response_time_ms INT UNSIGNED NULL,
  first_check_at DATETIME NOT NULL,
  last_check_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (monitor_id, hour_start),
  INDEX idx_hourly_hour (hour_start),
  CONSTRAINT fk_hourly_monitor FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- v0.0.7 public status page defaults are seeded by Settings::defaults() during
-- browser installation, so they are not inserted here before the first admin is created.

-- Stage 08 administration and safe operational audit.
CREATE TABLE IF NOT EXISTS maintenance_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  status VARCHAR(20) NOT NULL,
  summary VARCHAR(240) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_maintenance_created (created_at),
  INDEX idx_maintenance_user_created (user_id,created_at),
  CONSTRAINT fk_maintenance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 16. Regression review and verification evidence

**Verified in this environment:** the FULL ZIP baseline was checked byte-for-byte against the previously accepted v0.0.8 source before edits. All packaged PHP files were syntax-checked with the installed PHP CLI. The historical source regression suites, updated only for release-version assertions and current bounded Telegram dispatch behavior, passed **473/473**. New Stage 09 structural/hardening checks passed **52/52**. Total **525/525 code-level assertions**. Release package extracted files and permissions are checked during final packaging. These automated tests cover documented code structure, pure functions, and some source assertions, **not live integration across database, browser, TLS or PHP-FPM.**

**Live production verification required:** actual MySQL/MariaDB fresh installation and in-place upgrade, administrator login and invalidation across sessions/devices, real monitor HTTP/cURL success and failure, overlapping Cron invocations and real cron heartbeat, incident creation/recovery in a staging outage, Telegram test/down/recovery deliveries across multiple recipients, larger data retention/aggregation performance, desktop/mobile browser rendering, Open Graph crawler preview and nginx/Plesk Apache permissions. PHP CLI in this development container does not have `pdo_mysql` or `curl`, so the listed live cases were **not executed or claimed passed**.

## 17. Known limitations and operational caveats

- Sample-based uptime does not represent precise continuous uptime across unobserved time. Paused intervals are not tracked as a timeline.
- No guaranteed exactly-once Telegram delivery if the process dies after Telegram accepts a message but before DB update. A retry may duplicate that one event; the logical row remains unique per destination.
- Soft 50-second Cron target is not a hard timeout; one HTTP attempt with retries or one active Telegram call can exceed the budget. The OS lock prevents overlapping work.
- A fresh installer can leave tables after a failed partial DDL; to retry safely, supply a new **empty** database after assessing the failed installation.
- Only HTTP(s) checks exist. No SSL expiry, domain expiry, TCP/Ping, email/webhook channel, billing, teams or advanced multi-tenant SaaS.
- Social networks cache dynamic OG text/image previews and may not immediately reflect a status transition.
- Standard hosting must honor internal folder protections; explicitly configure nginx location restrictions if Apache `.htaccess` does not apply to the actual public file serving path.

## 18. Release notes and next steps

**v0.1.0** is the first code-level release candidate. It retains all cumulative functionality: install, white-label branding, secure multiple admins, monitors, Cron/check history, incidents/recovery, multi-chat Telegram logs/retry, analytics/retention, public status/OG, and maintenance. Release-specific fixes: stronger installer empty-DB check, limited Cron notification dispatch, privileged private-target configuration and operation, authorization-before-lookup, bounded dashboard preview, safe error responses and CLI-only test endpoints.

**Next:** verify deployment on Plesk, run a staged outage/recovery and multi-chat delivery test, validate mobile and OG metadata, then mark the same build **Release Ready** after operational acceptance. Future optional modules may include SSL/domain expiry, TCP/ping, additional channels, service groups, reports and subscription/team workflows. Build future patches on the accepted `v0.1.0` FULL ZIP and this FULL MD, not on old Stage 08 packages.
