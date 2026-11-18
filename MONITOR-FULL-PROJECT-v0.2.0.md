# Monitor | FULL Project Reference v0.2.0

## 1. Version, purpose, and release status

**Version:** `v0.2.0` | **Stage:** 10A, Portal Users, OTP and Quotas | **Source of truth:** `monitor-full-v0.2.0.zip` + `MONITOR-FULL-PROJECT-v0.2.0.md` after installation acceptance.

This version is cumulative on the actual accepted `monitor-full-v0.1.0.zip`. Stage 01–09 capabilities remain included. This release adds a second **Portal User** account domain and no customer registration, personal status links, SMTP transport, or per-user Telegram bots. **Code-reviewed release candidate; live host verification required.**

## 2. Runtime architecture

Native PHP 8.1+ and MySQL/MariaDB (InnoDB), PDO MySQL, PHP cURL, OpenSSL, fileinfo, PHP sessions, and a functioning `mail()` transport for OTP. Apache/LiteSpeed `.htaccess` provides an access barrier to internal directories; other servers require equivalent rules. All runtime times stored UTC and displayed in configured app timezone. No framework, Composer, Node, Redis, queue daemon or root access.

`bootstrap.php` supplies autoload, error handling, headers and session. `app/Core` supplies PDO and CSRF. `admin/_dispatch.php` supplies admin RBAC and controller. `account/_dispatch.php` handles portal controllers and portal-specific authentication. Services isolate monitor validation/HTTP, scheduled checks and incident transitions, alerts, analytics, retention and portal operations. `public/index.php` and `status/index.php` provide global public status. Configuration remains in database `settings`, version in `system_meta`, installation config/lock in private server files excluded from distributions.

## 3. Complete distributed file tree

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
MONITOR-FULL-PROJECT-v0.2.0.md
MONITOR-v0.1.0-RELEASE-NOTES.md
README.md
VERSION
account/.htaccess
account/_dispatch.php
account/analytics/index.php
account/incidents/index.php
account/index.php
account/login/index.php
account/logout/index.php
account/monitors/add/index.php
account/monitors/edit/index.php
account/monitors/index.php
account/monitors/view/index.php
account/profile/index.php
account/verify/index.php
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
admin/portal-users/.htaccess
admin/portal-users/_controller.php
admin/portal-users/add/index.php
admin/portal-users/edit/index.php
admin/portal-users/index.php
admin/portal-users/view/index.php
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
app/Services/Mailer.php
app/Services/Maintenance.php
app/Services/MonitorAnalytics.php
app/Services/MonitorInput.php
app/Services/NotificationManager.php
app/Services/PortalAuth.php
app/Services/PortalMonitors.php
app/Services/PortalUsers.php
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
app/Views/admin/portal-users.php
app/Views/admin/status-page.php
app/Views/admin/system.php
app/Views/admin/user-form.php
app/Views/admin/user-password.php
app/Views/admin/users.php
app/Views/auth/login.php
app/Views/partials/admin-bottom.php
app/Views/partials/admin-top.php
app/Views/portal/bottom.php
app/Views/portal/login.php
app/Views/portal/page.php
app/Views/portal/top.php
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
database/patches/v0.2.0.sql
database/schema.sql
index.php
install/index.php
public/assets/css/app.css
public/assets/css/portal.css
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
tests/stage10a.php
```

## 4. Routes and permissions

| Route | Purpose | Access |
| --- | --- | --- |
| `/install/` | Initial setup | Uninstalled only |
| `/admin/`, `/admin/login/`, `/admin/logout/` | Admin dashboard and password login | Administrators |
| `/admin/monitors/`, `/add/`, `/edit/?id=N`, `/view/?id=N`, `/analytics/?id=N` | Admin monitor management and analytics | Admin, Super Admin (private-network limits apply) |
| `/admin/incidents/`, `/admin/incidents/view/?id=N` | Incidents | Admin, Super Admin |
| `/admin/notifications/` | Delivery logs and Telegram settings | Admin read; Super Admin write |
| `/admin/settings/general/`, `/admin/settings/branding/`, `/admin/system/`, `/admin/maintenance/` | Platform controls | Super Admin |
| `/admin/users/`, `/add/`, `/edit/?id=N`, `/password/?id=N` | Admin account control | Super Admin |
| `/admin/account/` | Admin account/password | Signed-in Admin |
| `/admin/status-page/` | Global public status settings | Admin, Super Admin |
| `/admin/portal-users/`, `/add/`, `/edit/?id=N`, `/view/?id=N` | Portal accounts and quotas | Super Admin |
| `/account/login/`, `/account/verify/` | OTP email sign-in | Guest Portal User |
| `/account/logout/` | Portal logout | Portal User, CSRF POST |
| `/account/` | Portal dashboard | Portal User |
| `/account/monitors/`, `/add/`, `/edit/?id=N`, `/view/?id=N` | Scoped monitor CRUD, Test Now and check history | Owner only |
| `/account/incidents/` | Incident list | Owner only |
| `/account/analytics/?id=N` | Existing monitor analytics, pre-checked for ownership | Owner only |
| `/account/profile/` | Portal account details | Portal User |
| `/status/` | Global public status for platform-owned public monitors | Public |

All routes use physical folder `index.php` entrypoints. `?id=` is a record identifier, not page routing. All mutations use POST and CSRF. Portal routes never rely on the admin session; both use separate cookie names.

## 5. Portal User account model

`portal_users` is separate from password-based `users`. Columns: `id BIGINT UNSIGNED PK`, `name VARCHAR(140)`, `email VARCHAR(190) UNIQUE`, `status VARCHAR(20)` (`active`/`disabled`), `monitor_limit INT UNSIGNED DEFAULT 0`, `session_version INT UNSIGNED DEFAULT 1`, `last_login_at DATETIME NULL`, UTC `created_at`, `updated_at`. **No password column.** Super Admin creates/edits/disables accounts and sets limits. An email is normalized to lowercase; the unique index prevents duplicates. Disabling an account increments its session version, invalidating existing portal sessions on their next protected request; its monitors continue to be monitored.

Quota semantics: `monitor_limit=0` means *zero new monitors*, not unlimited. The add handler performs a transaction, locks `portal_users` row using `SELECT ... FOR UPDATE`, counts `monitors.portal_user_id`, inserts a new monitor only if usage is below quota, and commits. This serializes concurrent creates and quota changes. Lowering the quota does not delete anything; existing monitors remain operational, but further creation is blocked. The dashboard shows used, limit, available slots and over-limit warning.

## 6. Portal login, OTP and mail service

On `/account/login/`, a six-digit OTP is generated with `sprintf('%06d',random_int(0,999999))` for known active emails only, using the same generic response for unknown/disabled emails. The `portal_otp_codes` table stores `password_hash()` of the code, never plaintext, plus expiry, attempts, consumption time, IP hash, UTC creation time. The login form puts normalized requested email into the portal-only session and redirects to `/account/verify/`. The verify flow locks the latest unconsumed code row (`FOR UPDATE`), rejects expired/too-many-attempt records, uses `password_verify`, atomically increments attempts and consumes a matching or fifth-attempt code. Upon success, it updates last login, calls `session_regenerate_id(true)` and stores `portal_user_id` plus session version. OTPs expire in **10 minutes**, max **5** verification attempts and single-use. A newer request invalidates the earlier code.

Throttling: logged `portal_otp_requests` contain SHA-256 normalized email hash/IP hash for both known and unknown addresses. Up to **5 requests per 15 minutes per email**, plus **60-second per-email resend cooldown** and **20 requests per 15 minutes per IP** (bounded cleanup after one day). Email delivery is via the shared-hosting PHP `mail()` function, with `mail_from_email` and `mail_from_name` configured on `/admin/portal-users/`; messages use the white-label `app_name`, expiry and ignore-if-not-requested text. Invalid sender configuration or failed mail transport consumes the issued OTP and logs only a safe error category. Real delivery requires configured MTA/mail relay and must be tested on deployment. No SMTP or external email provider integration is included.

Session isolation: `monitor_session` for Admin, `monitor_portal_session` for Portal. Both sessions use HttpOnly, Lax SameSite, path-scoped cookies, HTTPS Secure when HTTPS is active, strict mode and idle timeout. Portal session revalidates the active account and version on every request, expires after 60 minutes inactivity and rotates session ID periodically. A disabled Portal User cannot log in or retain access; accounts are not deleted on disable.

## 7. Ownership, authorization and portal actions

`monitors.portal_user_id BIGINT UNSIGNED NULL` is a foreign key to `portal_users.id` with `ON DELETE SET NULL`, indexed. Existing monitors migrate with `NULL` and stay platform owned. Admin list shows `Platform` or the Portal User name. Admin keeps access to every monitor; scheduled cron uses its existing due-monitor query without discriminating by owner.

All Portal monitor read/write/test/toggle/delete operations require `id = ? AND portal_user_id = ?`. Incident reads join `incidents -> monitors` and constrain `portal_user_id`; checks join the same owner. Analytics first uses the scoped monitor lookup, then reuses `MonitorAnalytics` only after ownership validation. Any foreign `?id=N` yields a generic 404, including analytics. Portal monitor validation reuses `MonitorInput` and `HttpMonitor`, with private-network access forcibly disabled. Manual Test Now remains diagnostic only and does not create incidents. Hard monitor deletion also deletes dependent check, incident and notification history via existing FK cascades.

Portal-owned records stay **out of the platform-wide `/status/` page** even when their `is_public` flag is set. `is_public` is retained for future Stage 10B personal status pages. Telegram notification configuration stays global; per-user Telegram destination linking is deferred to Stage 10C, while the existing per-monitor toggle is preserved. Do not expose private HTTP error data or secrets on public pages.

## 8. Inherited admin, monitoring and incident architecture

- Roles: `super_admin` and `admin`, 8-character minimum passwords for admins, session version invalidation, login throttling, last Super Admin protection, CSRF/POST for edits and safe diagnostic views. Standard Admin access excludes sensitive platform settings.
- `MonitorRepository` handles admin monitor CRUD, list counts and manual check snapshots. `MonitorInput` validates names, URL scheme/host, intervals (1/2/5/10/15/30/60 minutes), timeouts (1–60 sec), retries (0–5), retry delays (0–30 sec), expected status codes (100–599), failure confirmation (1–5). `HttpMonitor` pins verified DNS to avoid SSRF and retains SSL verification, drops response body, follows protected redirects and uses retry count as *additional* attempts.
- `CronRunner` runs CLI-only at `cron/monitor.php`, uses nonblocking `flock`, due-monitor batches (default 25), heartbeat/status in `system_meta`, next check = completion + configured interval. One failed monitor cannot block all others; alerts/aggregation/retention are secondary stages.
- `IncidentManager` tracks `unknown/up/down`, consecutive failures (default threshold 2), first failure `started_at`, confirmation `detected_at`, one open incident per outage, recovery after one successful scheduled check and duration in seconds. Manual tests do not affect operational state.
- Telegram delivery uses global settings and up to 10 Chat IDs, one logical message per incident/event/destination, independent retries (maximum three), queue idempotency and safe error logs. Actual network delivery cannot offer exactly-once guarantees after process crash.
- `MonitorAnalytics` calculates factual sampled success-based availability for 24h/7d/30d and measured response times. `monitor_hourly_stats` preserves aggregates; `RetentionManager` removes bounded old raw checks and notification records, preserves incidents. Unknown/unobserved periods are not assumed Up.
- `/status/` displays public platform-owned services, safe incident messaging, recent incidents, 30-day daily history and dynamic OG title/description with static configured OG image. No private or Portal User data is exposed. `Maintenance` provides Super Admin-only health diagnostics, repair schedules, bounded cleanup and aggregate rebuild, with audit logs.

## 9. Database upgrade and full schema

Apply `database/patches/v0.2.0.sql` once to an **existing v0.1.0 database**, before PHP patch deployment. It creates `portal_users`, `portal_otp_codes`, `portal_otp_requests`, adds ownership field/index/FK to `monitors`, seeds `mail_from_email` and `mail_from_name` if absent, and advances `system_meta.installed_version`. It never drops existing users, accounts, monitors, incidents, checks, notifications or analytics. Fresh installer uses the combined `database/schema.sql` below and `Settings::defaults()` for initial settings.

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
  portal_user_id BIGINT UNSIGNED NULL DEFAULT NULL,
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
  INDEX idx_monitors_portal_owner (portal_user_id),
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

-- Stage 10A portal user accounts, OTP requests and ownership.
CREATE TABLE IF NOT EXISTS portal_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL, email VARCHAR(190) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active', monitor_limit INT UNSIGNED NOT NULL DEFAULT 0,
  session_version INT UNSIGNED NOT NULL DEFAULT 1, last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  UNIQUE KEY idx_portal_email (email), KEY idx_portal_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS portal_otp_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, portal_user_id BIGINT UNSIGNED NOT NULL,
  code_hash VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL,
  attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0, consumed_at DATETIME NULL,
  requested_ip_hash CHAR(64) NOT NULL, created_at DATETIME NOT NULL,
  KEY idx_portal_otp_user_date (portal_user_id,created_at),
  KEY idx_portal_otp_expiry (expires_at),
  CONSTRAINT fk_portal_otp_user FOREIGN KEY (portal_user_id) REFERENCES portal_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS portal_otp_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email_hash CHAR(64) NOT NULL, ip_hash CHAR(64) NOT NULL, requested_at DATETIME NOT NULL,
  KEY idx_portal_requests_email_date (email_hash,requested_at),
  KEY idx_portal_requests_ip_date (ip_hash,requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE monitors ADD CONSTRAINT fk_monitors_portal_owner FOREIGN KEY (portal_user_id) REFERENCES portal_users(id) ON DELETE SET NULL;

```

## 10. Database access and indexes

The complete DDL above includes primary keys, foreign keys and composite indexes. Due-monitor selection uses `monitors(is_active,next_check_at)`; history uses `(monitor_id,checked_at)`; portal ownership uses `monitors(portal_user_id)`. OTP verification uses `(portal_user_id,created_at)` and email lookups use `portal_users(email) UNIQUE`. OTP request throttles use `(email_hash,requested_at)` and `(ip_hash,requested_at)`. Historical check/incident/notification data remains in existing tables. No periodic user-owned-monitor special cron is required. All timestamps are stored as UTC in DATETIME fields.

## 11. Installation and upgrade

**Fresh install:** upload FULL ZIP to document root, prepare empty database, visit `/install/` to create first `super_admin`, configure server Cron every minute, then configure mail sender in Admin → Portal Users. Create portal accounts after installation. There is no self-registration. No prior SQL patch is necessary for fresh install.

**Upgrade:** back up files and DB; stop cron; apply `monitor-sql-patch-v0.2.0.sql` **once before** extracting Patch; extract Patch with overwrite into current root, preserving private `config/database.php`, `storage/installed.lock`, branding uploads, logs and PHP sessions; re-enable cron, log in with existing admin and verify existing monitors. Do not rerun installer or reimport schema.sql into existing database.

Cron examples: cPanel `/usr/local/bin/php /home/USERNAME/public_html/monitor/cron/monitor.php`; Plesk `/opt/plesk/php/8.2/bin/php /var/www/vhosts/DOMAIN/httpdocs/cron/monitor.php`. Use actual paths for your host and run every minute where available. Cron processes monitors only when due.

## 12. Security and limitations

All inputs are validated server-side, SQL uses prepared statements, HTML is escaped, write operations require CSRF, Portal User ownership is checked on each resource access, OTPs are hashed and consumed, and Portal User login does not share Admin authentication cookies. Some hosts do not enable `mail()`, so an email relay must be configured. OTP delivery is intentionally opaque for unrecognized addresses. Hosting software must block direct access to nonpublic PHP internals, config, logs and cron. `.htaccess` applies to Apache/LiteSpeed; configure equivalent protections on nginx. This environment did not provide a live MySQL dataset or verified outbound SMTP/Telegram; SQL migration behavior and end-to-end sign-in must be tested on the destination Plesk/cPanel host. Client-side mobile viewport behavior must be verified in a real browser.

**Known scope boundaries:** personal `/status/{slug}/` pages are not implemented; Portal Users cannot self-register, cannot set a password, cannot link a Telegram account, and cannot edit global branding or settings. No per-user billing or quota expiry. Admin per-user settings are manual. PHP `mail()` transport is the only OTP mail method in this stage.

## 13. QA and next stage

Code-level verification includes PHP syntax checks across the distribution, existing regression suite after adjusting current-version assertions, Stage 10A static/security tests, and ZIP contents/permissions verification. These checks do **not** prove real database transactions, mail sending, Telegram delivery, Plesk Cron, or browser mobile appearance.

**Stage 10B recommendation:** implement per-user public status page route `/status/{slug}/`, slug ownership and collision protections, public-only user monitor filtering, historical availability, and personal status metadata, without exposing other portal users or administrator infrastructure. Telegram account linking belongs to Stage 10C. v0.2.0 FULL ZIP and FULL MD become the development baseline after acceptance.
