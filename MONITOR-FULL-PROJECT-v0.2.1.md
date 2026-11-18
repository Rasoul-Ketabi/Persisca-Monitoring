# Monitor — Full Project Reference v0.2.1

**Release:** v0.2.1 | **Stage:** 10B, Personal Public Status Pages, together with Portal Users Admin Design Fix  
**Authoritative baseline:** accepted `monitor-full-v0.2.0-sidebar-hotfix.zip`, not the original unpatched v0.2.0 ZIP.  
**Runtime:** native PHP 8.1+, PDO MySQL/MariaDB, InnoDB, cURL, OpenSSL, sessions, fileinfo; Apache/LiteSpeed or correctly configured nginx, shared hosting compatible. No Node, Redis, Docker, daemon, or framework requirement.  
**Installation status:** release candidate until live host validation.

## 1. Product architecture and components

The project is a self-hosted white-label HTTP uptime-monitoring application. It includes a browser installer, a password-based administration system (`super_admin` and `admin`), user-owned portal monitors using email OTP, scheduled cron checks, a threshold-based incident engine, Telegram notification delivery for configured global recipients, hourly analytics, retention, and public status pages. Existing global status `/status/` remains available and serves only admin/platform-owned public monitors. Personal pages `/status/{slug}/` serve only public monitors of the matching active, published Portal User.

Entry points: `bootstrap.php` loads environment, session and class autoloading; `/admin/` is the administrative front end with physical folder routes; `/account/` is the customer portal with its own cookie and OTP-only authentication; `/public/index.php` is the public status controller invoked by `/status/`; root `.htaccess` rewrites `/status/{slug}/` internally to `status/index.php?personal_slug={slug}`; `cron/monitor.php` is CLI-only. Dynamic SQL and incident calculations are in services/models, never in public views. `.htaccess` denies direct web access to `app`, `database`, `config`, `storage`, `cron`, `tests` and internal dispatchers. For nginx configure equivalent restrictions.

## 2. Complete source file tree

The tree below is the distribution after Stage 10B (normal PHP runtime/upload data is excluded):

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
MONITOR-FULL-PROJECT-v0.2.1.md
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
account/status-page/index.php
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
database/patches/v0.2.1.sql
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
storage/logs/monitor-cron.log
tests/.htaccess
tests/multichat.php
tests/notification-page.php
tests/sidebar-routes.php
tests/smoke.php
tests/stage04.php
tests/stage05.php
tests/stage06.php
tests/stage07-render.php
tests/stage07.php
tests/stage08.php
tests/stage09.php
tests/stage10a.php
tests/stage10b.php
```

## 3. Core URLs and roles

| URL | Function | Access |
|---|---|---|
| `/install/` | Initial installer, protected by installed lock | Before installation only |
| `/admin/` | Dashboard, fleet status | Admin / Super Admin |
| `/admin/monitors/`, `/add/`, `/edit/?id=N`, `/view/?id=N`, `/analytics/?id=N` | Monitor management, history and analytics | Admin / Super Admin |
| `/admin/incidents/`, `/view/?id=N` | Incident history and detail | Admin / Super Admin |
| `/admin/notifications/` | Telegram configuration and delivery history | Admin read-only / Super Admin configuration |
| `/admin/status-page/` | Global public-status preferences | Admin / Super Admin |
| `/admin/settings/general/`, `/admin/settings/branding/`, `/admin/settings/monitoring/` | General, branding, retention | Super Admin |
| `/admin/system/`, `/admin/maintenance/` | Diagnostics and maintenance | Super Admin |
| `/admin/users/`, `/add/`, `/edit/?id=N`, `/password/?id=N` | Admin users | Super Admin |
| `/admin/account/` | Own admin profile and password | Admin / Super Admin |
| `/admin/portal-users/`, `/add/`, `/edit/?id=N`, `/view/?id=N` | Account and personal status publishing configuration | Super Admin |
| `/account/login/`, `/account/verify/`, `/account/logout/` | Portal OTP sign-in/out | Public requests, portal sessions |
| `/account/` | Portal dashboard, public page summary | Portal User |
| `/account/monitors/`, `/add/`, `/edit/?id=N`, `/view/?id=N` | Owned monitor CRUD | Portal User / owned rows |
| `/account/incidents/`, `/account/analytics/?id=N`, `/account/profile/` | Owned data | Portal User / owned rows |
| `/account/status-page/` | Enable own page, set own monitor visibility | Portal User / owned rows |
| `/status/` | Platform-owned public monitor data only | Public |
| `/status/{slug}/` | One published Portal User's public monitors | Public, enabled active owner only |

All page routes use folder-style URLs. An `id` query parameter may select an owned record but may not select a page. The server enforces authorization even if the sidebar link is hidden. The original admin and portal sessions remain separate.

## 4. Stage 10B database and migration

New `portal_users` fields:

- `public_slug VARCHAR(120) NOT NULL`, globally unique by `uniq_portal_public_slug`.
- `public_status_enabled TINYINT(1) NOT NULL DEFAULT 0`.
- `status_page_title VARCHAR(190) NULL`.

Upgrading from v0.2.0: add nullable slug plus enabled/title fields, backfill each existing account as `user-{id}` for unique, privacy-safe values, then make slug non-null and unique. **All existing personal pages remain disabled by default.** Existing name, email, OTP data, quota, users, monitors, incidents, analytics, Telegram settings, cron metadata and branding are unchanged. A unique slug is generated for newly created accounts using a lowercase ASCII name slug (with collision suffix based on ID). Admin-supplied slugs must be lowercase ASCII and unique. Invalid, reserved or duplicate custom slugs are rejected. No old SQL migrations or installer re-run required for upgrades.

The Stage 10B SQL patch is `monitor-sql-patch-v0.2.1.sql` (same contents as `database/patches/v0.2.1.sql`). Run it **once** after a database backup **before** replacing application files because the new admin/portal pages read the added columns. `system_meta.installed_version` is updated to `v0.2.1`. Fresh installs use updated `database/schema.sql` directly.

## 5. Full fresh-install database schema

The following is the actual complete `database/schema.sql` shipped with v0.2.1:

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
  public_slug VARCHAR(120) NOT NULL,
  public_status_enabled TINYINT(1) NOT NULL DEFAULT 0,
  status_page_title VARCHAR(190) NULL,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  UNIQUE KEY idx_portal_email (email), UNIQUE KEY uniq_portal_public_slug (public_slug), KEY idx_portal_status (status)
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

## 6. OTP customer login and monitor ownership

`portal_users` has no password field. Portal login requests generate a six-digit `random_int` OTP, store only a password hash in `portal_otp_codes`, expire it after 10 minutes and consume it after one successful use or five incorrect attempts. Request limits include a 60-second account cooldown, five requests per address per 15 minutes and an IP rate limit. Unknown or disabled accounts see the same generic response. PHP `mail()` sends a white-label email from configured `mail_from_email` and `mail_from_name` settings. SMTP is not built in. A functioning email transport must be tested on the destination host.

Portal sessions use a separate cookie, `portal_user_id`, `portal_session_version` and active-user checks. The admin cookie is distinct. Portal users can only access monitors with `monitors.portal_user_id` matching their current account. Counts and quota enforcement occur in a database transaction with a user row lock. Zero monitor slots means no creation. Decreasing the quota does not delete existing monitors. Portal checks, incidents and analytics are filtered by ownership; changing an ID must return a safe 404. Platform monitors have `portal_user_id IS NULL`. Cron continues checking both platform and portal monitors without special ownership branches.

## 7. Personal status pages, publishing and routing

- Admin owns the personal slug; portal users cannot change it. The slug must match `^[a-z0-9]+(?:-[a-z0-9]+)*$` and be at most 120 characters. Reserved: `admin`, `account`, `login`, `assets`, `api`, `incidents`, `notifications`, `telegram`, `webhook`, `status`, `public`, `install`, `logout`, `verify`, `monitors`, `settings`, `system`, `maintenance`, `users`, `cron`, `dashboard`.
- The admin can edit slug, page title, enabled flag, account status and quota on `/admin/portal-users/edit/?id=N`. The admin detail shows URL, slug, enabled state, public monitor count and a view link when enabled.
- The portal user can toggle **only their own page's** `public_status_enabled` via authenticated POST and CSRF on `/account/status-page/`; individual public/private monitor flags are similarly changed only with an owner-scoped database update. Disabled account cannot publish.
- A personal page resolves only `portal_users.status='active' AND public_status_enabled=1` with exact `public_slug`, and only when the global status service is enabled. Unknown, disabled or invalid slugs receive 404. Pages have no public user email or private account fields.
- The global `/status/` filters `is_public=1 AND portal_user_id IS NULL`. The personal `/status/{slug}/` filters `is_public=1 AND portal_user_id=?`, including both active and resolved incidents through `monitors.portal_user_id`. If a monitor becomes private, its historical incidents immediately disappear from that page. Platform-owned and other portal users' monitors never appear.
- The same `PublicStatus::snapshot(settings, ?ownerId)` and `PublicStatus::history()` services serve both views. The scoped monitor identifiers are used in `MonitorAnalytics::summary()` and 30-day history. No separate public SQL in templates. Output has current state, 30-day daily bars, observed check-success percentage, last response, active/resolved incidents and no-data states.
- Overall state rules: no public monitor -> No Public Monitors; all active known public monitors up and none paused -> All Systems Operational; all active down -> Major Service Disruption; some down/active incident -> Some Systems Experiencing Issues; otherwise Status Pending. Paused monitors are not automatically called healthy.
- The page title defaults to `{Portal User Name} Status` if the admin leaves `status_page_title` empty. The page uses globally configured product name, logo, favicon, accent, support/footer and OG image. `og:title`/HTML title contain the page title and app name; current-state description is generated from visible owner data. Canonical and `og:url` are the same `https://site.example/status/{slug}/`. OG image is the configured image or neutral fallback. Social media may cache old OG previews.
- Public data is rendered server-side and works without JavaScript. Personal public responses use `Cache-Control: no-store` so disabling a page or marking a monitor private does not retain cached public incident listings; the global page retains a short 30-second cache. Dates use the configured timezone. Missing checks do not imply uptime; sampled check success is not a continuous-time SLA. No raw cURL errors, backend endpoints, admin URLs, notification logs, portal email or Telegram settings are publicly rendered. The public view is mobile responsive and keyboard-readable.

## 8. Admin Portal Users visual correction

The original Stage 10A Portal Users form rendered inline browser-default input fields because its standalone `field-grid` did not inherit input/label rules scoped to the `settings-form` class. Stage 10B refactors the admin view to semantic `.portal-admin-form`/`.portal-admin-fields` sections, full-width fields, explicit labels, responsive 2-to-1-column layout, gap/padding, normal empty state, a separated Email Delivery Settings card and consistent action buttons. No image assets or new design dependencies. The corrected design is part of both the cumulative Patch and FULL ZIP.

## 9. Existing uptime, Telegram and operational services

Scheduled HTTP checks use cURL, validated HTTP(S) URLs and TLS verification, monitor intervals/retries/timeouts, CLI Cron lock via `flock`, heartbeat and due selection. Manual Test Now is diagnostic and never drives incidents. Confirmed down/recovery transitions are persisted using the incident manager. Notifications use the global Telegram Bot API settings, up to ten Chat IDs and per-destination `notification_logs` uniqueness, limited retries and a bounded cron delivery batch. Bot tokens are stored in the private database `settings` row, are never prefilled in HTML and are not distributed. Exactly-once external delivery is not guaranteed if Telegram accepts a message immediately before PHP crashes. No per-user bot linking exists yet.

Stage 06 hourly statistics aggregate scheduled checks in `monitor_hourly_stats`, include response-time figures and preserve records after raw retention. Displayed uptime is **successful observed scheduled checks / all observed scheduled checks × 100**, not unobserved wall-clock time. Confirmed incident overlap is displayed separately. Raw checks and notification logs are cleaned in bounded, scheduled retention tasks; incident history is preserved. Stage 08 user administration supports Super Admin and Admin roles, minimum eight-character admin passwords, session invalidation, protection of the last Super Admin and CSRF-protected maintenance. Admins have only operational privileges; Super Admin manages platform secrets and accounts.

## 10. Security, runtime and limitations

The PHP views escape dynamic content using `e()` and use CSRF-protected POST actions for all mutations. Personal-page routing whitelists characters and validates against reserved slugs. Public monitor/incident reads are scoped by owner ID using prepared statements. Visibility writes explicitly check the current session owner. MySQL unique constraint prevents duplicated public slugs. `.htaccess` is provided for Apache/LiteSpeed; an nginx server must reproduce rewrites and blocks. Files `config/database.php`, session data, actual bot tokens, runtime logs, installed lock and uploaded customer assets are never packaged. Production runs should use HTTPS.

No Telegram user linking, per-user Telegram destinations, webhook, per-user branding, self-registration, billing, custom domains or status subscriptions are provided. Public status pages can list up to 500 public monitors per owner to bound shared-hosting response time. Social preview validation, full browser mobile QA, production network access, actual SMTP/mail delivery, MySQL transactions and Cron remain **live-host verification requirements**. Hourly history in unusual offset timezones may approximate some boundaries after raw check retention.

## 11. Install and upgrade

**Fresh v0.2.1:** Upload `monitor-full-v0.2.1.zip` without wrapper to document root, create an empty MySQL database, visit `/install/` and create first Super Admin (password >=8 characters). Configure Cron to execute `cron/monitor.php` each minute. Set up OTP sender in Admin → Portal Users, test a real OTP email, create Portal Users with monitor quotas. Personal pages are disabled by default; explicitly enable, set per-monitor visibility and open the personal status URL. Fresh installs use complete `database/schema.sql` directly, not old migrations.

**Upgrade from hotfixed v0.2.0:** (1) backup files and DB, pause Cron; (2) apply `monitor-sql-patch-v0.2.1.sql` exactly once in phpMyAdmin; (3) extract `monitor-patch-v0.2.1.zip` into the existing document root with overwrite; (4) restart Cron and log in; (5) verify `/admin/portal-users/` form, `/account/status-page/`, `/status/` and a published `/status/{slug}/`. The Stage 10A admin 500 sidebar hotfix is included in the FULL ZIP; it is not removed. Preserve all runtime files and do not reinstall.

cPanel CLI example: `/usr/local/bin/php /home/USERNAME/public_html/monitor/cron/monitor.php`  
Plesk CLI example: `/opt/plesk/php/8.2/bin/php /var/www/vhosts/DOMAIN/httpdocs/cron/monitor.php`  
The PHP executable and document root depend on the host.

## 12. Verification performed and outstanding

Development verification: PHP syntax pass over the full PHP tree, CLI regression tests from Stages 01–10B (including the previous Sidebar 500 hotfix), direct slug validation, code-level inspection of owner-scoped select/update queries, and full-vs-patch file comparison. ZIP entries are mode 644 files and 755 directories, without a wrapper. No files deleted from the accepted baseline.

Not verified on a live server: a MariaDB migration and fresh installation, real slug routing in Apache/Plesk/nginx, multi-account ownership in separate browser sessions, email OTP delivery, live scheduled checks/Telegram alerts, dynamic social previews and mobile browser rendering. The product remains a release candidate until those are tested.

## 13. Exact next stage

**Stage 10C — Telegram Account Linking and Per-User Notifications.** It should add a secure bot linking and verification process, per-owner destination routing and alerts, with privacy protections and preservation of global admin alerts. Do not implement those features in Stage 10B.
