# Monitor | FULL Project Reference v0.0.8

## Version, scope and authority

**Version:** `v0.0.8`  
**Stage:** 08, Super Admin Management, Admin Users, Reliability and Production Operations  
**Authoritative source:** actual accepted `monitor-full-v0.0.7.zip` and `MONITOR-FULL-PROJECT-v0.0.7.md`, including all Telegram multi-destination and Notifications repairs.  
**Deliverables:** `monitor-patch-v0.0.8.zip`, `monitor-full-v0.0.8.zip`, `MONITOR-FULL-PROJECT-v0.0.8.md`, `monitor-sql-patch-v0.0.8.sql`.

This reference describes the current source code, not planned features. All Stage 01 through Stage 07 capabilities are preserved. A deployed installation must **upgrade** rather than reinstall; never run the installer or import the full fresh-install schema over the live database.

## Runtime and architecture

Native PHP 8.1+, PDO MySQL, cURL, MySQL/MariaDB, file-based PHP sessions, Apache or LiteSpeed rewrites and CLI cron. Compatible with Plesk and cPanel. No Composer, Laravel, Node, Redis, queues, daemon, root access or WebSocket dependencies. HTML UI is English-only and white label. `bootstrap.php` installs the `Monitor\` autoloader and safe session/header defaults. `app/Core/Database.php` holds the PDO connection. `app/Core/Security.php` provides CSRF and POST detection. Real directory entry points in `admin/` and `status/` bootstrap routed actions. `admin/_dispatch.php` validates route/role/CSRF and renders views.

Public status is at `/status/`; admin at `/admin/`. There is no visible `?page=...` navigation (legacy bookmarks may redirect). `?id=N` and whitelisted analytics date ranges are acceptable record/UI parameters. The root `.htaccess` blocks `app/`, `config/`, `database/`, `cron/`, `storage/` and `tests/` from public HTTP. Additional nested `.htaccess` files provide protection. No shell/SQL execution UI is provided.

## Complete distribution tree

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
```

The displayed tree lists distributed source; runtime database config `config/database.php`, `storage/installed.lock`, cron logs, user-uploaded images, active sessions, passwords and Telegram secrets must **not** be included in ZIPs. Runtime folders exist in deployment and are writable according to host ownership.

## Roles and access policy

`users.role` supports `super_admin` and `admin`; users additionally have `status` active/disabled and `session_version`. The installer creates the first account as `super_admin`. SQL migration **promotes the lowest-ID active legacy Admin only if no active Super Admin exists**; existing email/name/password hash is unchanged. This ensures an existing single-admin installation retains control. In multi-admin custom installations, other legacy accounts remain standard Admin and may be promoted using the UI. Existing disabled accounts are not automatically promoted. If there are no active users, manual administrator recovery using a DB backup is necessary.

`Authorization::allowed()` and `Authorization::requireAccess()` enforce access in `admin/_dispatch.php` for **both GET and POST**. It is not enough to hide a sidebar link. Unauthorized signed-in users receive an admin-styled **403 Access Denied** page without secrets. Non-authenticated visitors are sent to Login.

| Protected feature | Super Admin | Admin |
|---|---|---|
| Dashboard, Monitors and Test Now, Incidents, Analytics | Full | Full operational access |
| Public Status Page settings | Full | Full |
| Notifications delivery history | View | View |
| Telegram credentials/settings/test sends | Full | Denied (GET settings form hidden, POST blocked) |
| My Account and password change | Own account | Own account |
| Admin Users, add/edit/reset | Full | 403 |
| Branding, General Settings, System Health | Full | 403 |
| Maintenance & manual operations | Full | 403 |

`/admin/users/` lists name, email, role, status, last login, created and actions, limited to 300 users. `/admin/users/add/` accepts name, normalized case-insensitive email (DB uniqueness), role, status, password and confirmation. `/admin/users/edit/?id=N` supports name/email/role/status. `/admin/users/password/?id=N` resets another user's password. `/admin/account/` allows changing one's password after entering current password. All mutations use POST + CSRF. Password minimum **8 characters** and max 256; verified confirmation, `password_hash()` and `password_verify()`. Neither password nor hash is rendered or logged. No permanent user deletion is implemented; use `Disabled` to retain history.

**Last Super Admin protection:** `AdminUsers::update()` runs inside a database transaction, locks user rows with `SELECT ... FOR UPDATE`, and rejects disabling/demoting the last active Super Admin. A Super Admin also cannot disable or demote their **own** account, even with another Super Admin present. Standard Admin cannot reach privilege-edit actions. No hard-delete is available.

## Session invalidation

The `users.session_version` column is `INT UNSIGNED NOT NULL DEFAULT 1`. Upon login, session records `admin_user_id` and `admin_session_version`. Each protected request re-selects the current DB user, requiring `status='active'`, a supported role and the same session version. Disabling, demoting/promoting or otherwise changing role/status increments DB `session_version`, invalidating **all** of that user's active sessions on their next protected request. Resetting another user's password increments version too. On self password change, version increments, current session receives the new version and session ID regenerates, keeping **only this session** active. Other sessions are revoked on their next request. Existing pre-upgrade login sessions missing version may be required to log in again after code deployment. Login throttling and secure session settings remain in place. Authorization is checked before executing POST handlers.

## Maintenance architecture, actions and diagnostics

`/admin/maintenance/` is Super Admin-only. `Maintenance::overview()` uses bounded aggregate/count queries and safe labels. It reports: cron health and last completion, VERSION vs installed_version mismatch, database-required-table presence, writable storage/log/cache/upload folders; active user counts; active overdue monitors (more than 10 minutes late), active monitors with missing schedule; multiple open incidents; down without open current incident; invalid incident pointers; invalid operational statuses; orphaned checks/incidents/notifications; pending/failed Telegram records. Diagnostics are **read-only** and do not infer recovery or invent monitoring checks.

Existing `CronRunner::health` maps `Never Run`, `Healthy`, `Delayed`, `Stale`, `Last Run Failed`, `Running`, plus clock mismatch according to the persisted heartbeat, run status and configurable `cron_stale_after_minutes` (default 10). `CronRunner::run()` uses an advisory `flock()` on `storage/cron-monitor.lock`, batches due checks (default 25), saves checks and IncidentManager transitions, delivers Telegram outbox, aggregates statistics, runs bounded daily retention and writes heartbeat. A leftover lock **file** does not mean a permanently held lock. Web maintenance shares the same nonblocking lock and declines a request if cron is active.

Manual actions are POST + CSRF + confirmation and Super Admin only:

1. `cleanup`: call `RetentionManager::runNow()` once, honoring saved retention policy; delete at most **1000** old, already-aggregated raw check rows and **1000** eligible resolved-incident notification rows. Never delete incident history/active notification outbox.
2. `aggregates`: process up to **500** pending scheduled check rows with `HourlyAggregator::process(500)` using the transactional aggregate cursor. This is **not** a full historical rebuild and does not double-process already aggregated rows.
3. `schedules`: set `next_check_at=UTC_TIMESTAMP()` on up to **100** active monitors whose schedule is NULL. Paused monitors are untouched; no synthetic checks/incidents are generated. Overdue non-null schedules are diagnostics only, not automatically rewritten.

Each successful operation writes a structured, safe audit entry to `maintenance_logs` with actor ID, action, result summary, UTC timestamp. User creation/update/password actions also record safe audit events. If audit persistence fails, the primary operation still completes and a non-sensitive class-name error may be logged by PHP. Only the newest 30 audit entries appear; raw cron files are not exposed through the UI. There is **no** dangerous SQL console, arbitrary command endpoint or automatic incident repair. Manual operations may need repeated clicks for large backlogs. They are designed to avoid PHP-FPM timeout in ordinary shared hosting.

## Schema changes and indexes

For a database upgraded from v0.0.7: add `users.session_version`, add `idx_users_role_status(role,status)`, promote the first active legacy admin if needed, create `maintenance_logs`, and update `system_meta.installed_version`. No other existing table is removed or rewritten. Settings, branding, monitors, checks, incidents, hourly aggregates, notification records, Telegram credentials, cron heartbeat and all previous passwords are preserved. 

**New maintenance schema:** `maintenance_logs(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, action VARCHAR(60), status VARCHAR(20), summary VARCHAR(240), created_at DATETIME, indexes(created_at),(user_id,created_at), FK user_id → users.id ON DELETE SET NULL)`.

The complete fresh installation DDL is embedded verbatim below, identical to `database/schema.sql` in the FULL ZIP:

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

MySQL/MariaDB and UTC storage are required; no MariaDB 10.11-only functionality is used. Explicit `INSERT ... ON DUPLICATE KEY UPDATE` and prepared statements continue. Apply each numbered SQL migration once; `ALTER TABLE ADD COLUMN` is not repeat-safe. In-place upgrade patches are not a substitute for the schema in a **fresh** installation.

## Preserved monitoring, notifications, analytics, public surface

- Stage 02 reusable `HttpMonitor`: SSL verification remains enabled, safe URL validation and configured expected status handling, timeout, retry count/delay, manual Test Now diagnostics. Manual tests do not change incident state.
- Stage 03 due monitor cron: only active, due monitors, persisted `monitor_checks`, current runtime snapshot and `next_check_at`; global nonblocking cron lock and heartbeat. Default master schedule every **one minute**, monitor interval separate (e.g. five minutes).
- Stage 04 IncidentManager: per-monitor failure threshold default 2 **failed scheduled checks** (each check can retry internally), exactly one open incident per outage, `started_at` first failed check, `detected_at` threshold check, one scheduled success closes incident and stores duration. Pausing while down leaves incident open. Only scheduled checks trigger operational transitions.
- Stage 05 Notifications: Telegram Bot token in DB `settings.telegram_bot_token` (never reflected into saved-form value or distributed ZIP), up to ten chat IDs one per line including negative IDs and channels. Event records unique on `(channel,incident_id,event_type,destination_chat_id)`, independent per-chat delivery state/retry (max three), real API POST with TLS; failure does not roll back incident. Duplicate delivery is possible after a crash between Telegram acceptance and DB update; no guaranteed exactly-once. Admin GET sees delivery history without editable credential fields. Super Admin alone controls bot/token/test.
- Stage 06 Analytics: 24h/7d/30d *observed scheduled-check success ratio*, not continuous SLA uptime; real successful/failed samples only; avg/min/max response and SVG trend chart; incremental `monitor_hourly_stats` aggregates and safe retention. Confirmed incident duration is presented separately. No observed checks means No Data, not false healthy percentage. `Settings` stores `check_retention_days`, `notification_log_retention_days`, `cron_batch_size` and `cron_stale_after_minutes`.
- Stage 07 Public Status: clean `/status/`, public monitors only, 30 local-calendar-day availability blocks, active public incidents and recent resolved history, safe status labels, dynamic title/meta/OG based on actual state; configured static logo/OG image, no private IDs/URLs, raw curl errors, notification settings or admin links. Historical pause periods cannot be perfectly reconstructed and fractional-timezone UTC-hour buckets can be approximate at local day boundaries. Public caches up to ~30 seconds; no auto refresh, WebSockets or public subscriber endpoints.

All dates stored as UTC; localized display follows `settings.timezone`. Status labels, monitoring state, and access role are separate concepts. Public UI is not a system administration surface.

## Upgrade existing v0.0.7 installation

1. Back up **both database and files**, including protected live config, installer lock and uploaded branding. Pause CLI cron briefly.
2. In phpMyAdmin, run **`monitor-sql-patch-v0.0.8.sql` once** on the existing v0.0.7 database. **Run SQL first**, because the new Auth class immediately requires `users.session_version`. Old Stage 07 code already accepts a `super_admin` role during this short transition.
3. Immediately extract **`monitor-patch-v0.0.8.zip`** directly over the actual domain Document Root, overwriting only source files, with no wrapper directory. Do not overwrite `config/database.php`, `storage/installed.lock`, uploads or customer secrets. Use Plesk file permissions 0644 files / 0755 directories.
4. Sign out and sign back in if necessary. The original active legacy administrator should now be Super Admin with **unchanged password**. Verify `/admin/users/`; create a second Super Admin, then a standard Admin; test route permissions including direct restricted URLs (expected 403).
5. Verify `/admin/account/` password change and reset invalidation, `/admin/maintenance/` health and small manual action. Check installed/code version match, cron heartbeat, notification/monitor history, `/admin/notifications/` and `/status/`.
6. Resume the existing CLI cron. No changes to the cron task schedule, Telegram Token or Chat IDs are needed.

**Do not reinstall.** Do not run `database/schema.sql` on a live database, and do not repeat earlier version SQL patches. A user who deploys new code before SQL may see a generic 500 on login; apply the migration to resolve the missing column. Installation-specific files are preserved by the PATCH.

Fresh install: extract FULL ZIP into a new empty web root, run `/install/` and create an initial **Super Admin** with an 8-character-or-longer password; the installer creates full current schema directly. No SQL patch is needed for a fresh install. Configure CLI cron afterward.

Suggested Plesk cron command for an installation in the subdomain root (replace placeholders with actual PHP binary and document-root path):

```bash
/opt/plesk/php/8.2/bin/php /var/www/vhosts/DOMAIN/httpdocs/cron/monitor.php
```

Recommended cron schedule: `* * * * *`; monitor-specific intervals still govern individual checks.

## Verification and limitations

**Code-level verification:** PHP lint for all source; existing Stages 01–07 tests including public-render fixtures; Stage 08 static/authorization/validation/security tests. ZIPs verified, files 0644, directories 0755; FULL extracted and byte-compared. Existing monitor/incident/Telegram/analytics source preserved except the necessary access controls. No production credentials in generated files.

**Live production verification required:** real MySQL migration, live permissions and logins, multiple simultaneous browsers/sessions and session revocation, concurrent role changes, Plesk Cron/Maintenance file locking, retention and aggregation with large data sets, Telegram delivery and `/status/` behavior on real server. No live MariaDB or cURL extensions are available in this build environment. Tests do **not** establish end-to-end production success.

**Known limitations:** no hard deletion of users (disable only); no user-list pagination beyond max first 300; no email verification or MFA; no audit of failed operations; diagnostics are counts and do not automatically repair incident references; manual analytics processes pending aggregates rather than arbitrary historical rebuild; no notification queue reset control; current password change preserves the active browser but revocation of other sessions occurs on their next protected request. Current project does not include SSO, OAuth, billing, teams, email/webhook alerts, SSL expiry, domain expiry, TCP checks or maintenance windows.

## Recommended next step

**Stage 09: Production Hardening, Final QA, Release Preparation, v0.1.0.** Execute real database and browser regression tests, verify upgrade/fresh installer and cron/incident/Telegram workflows, review security/permissions and responsive UI, and package a stable first release. Do not add major unrelated product features.
