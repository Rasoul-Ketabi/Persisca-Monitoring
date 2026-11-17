# Monitor | Full Project Reference v0.2.3

**Version:** `v0.2.3`, focused Stage 10A.1 SMTP upgrade (cumulative on accepted v0.2.2).  
**Baseline:** actual accepted `monitor-full-v0.2.2.zip` and `MONITOR-FULL-PROJECT-v0.2.2.md`, retaining Stage 10A / 10B / 10C, portal-user Telegram linking and all earlier features. The original SMTP prompt specified a historical v0.2.0-r1 branch; the version number has been advanced to prevent any rollback from v0.2.2.  
**Runtime:** native PHP 8.1+, PDO MySQL/MariaDB, cURL/TLS, PHP stream/socket/OpenSSL support, fileinfo and sessions; standard shared hosting, Apache/LiteSpeed `.htaccess` or equivalent nginx. No Composer, Docker, Node, Redis or daemon.  
**Release status:** Release Candidate, code-level verified; live SMTP submission and inbox acceptance on the target hosting are still required.

## 1. Product architecture

Monitor includes Super Admin/Admin accounts and session invalidation, OTP Portal Users with monitor quotas, owner-specific monitoring and incident access, status pages, operational HTTP checks and incidents, global multi-chat Telegram alerts, private Telegram account linking for Portal Users, native analytics, retention, maintenance and white-label branding. The interface and generated email messages are in English. The Admin password minimum remains eight characters.

Stage 10A.1 upgrades the **existing** `app/Services/Mailer.php`, adds `app/Services/SmtpClient.php` for native RFC-style SMTP requests and creates Super Admin-only `/admin/settings/mail/`. Portal OTP still calls `Mailer::otp()`; delivery is selected by `mail_transport` and a delivery failure invalidates the undelivered OTP. The source of truth for all settings is the pre-existing `settings` key/value table. No previously accepted SQL table or monitoring behavior is removed.

## 2. Complete distribution file tree

Paths are relative to the archive root. Deployment-specific `config/database.php`, `storage/installed.lock`, customer uploads, secrets, sessions and runtime logs are intentionally excluded.

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
MONITOR-FULL-PROJECT-v0.2.2.md
MONITOR-FULL-PROJECT-v0.2.3.md
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
account/telegram/index.php
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
admin/settings/mail/index.php
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
app/Services/SmtpClient.php
app/Services/TelegramDestinations.php
app/Services/TelegramLinkManager.php
app/Services/TelegramNotifier.php
app/Services/Upload.php
app/Views/admin/access-denied.php
app/Views/admin/account.php
app/Views/admin/branding.php
app/Views/admin/dashboard.php
app/Views/admin/general.php
app/Views/admin/incident-detail.php
app/Views/admin/incidents.php
app/Views/admin/mail.php
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
database/patches/v0.2.2.sql
database/patches/v0.2.3.sql
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
telegram/webhook/index.php
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
tests/stage10a1.php
tests/stage10b.php
tests/stage10c.php
```

## 3. Routes and access

| Route | Purpose | Access |
|---|---|---|
| `/install/` | Fresh installation on empty DB | Before installation only |
| `/admin/` | Dashboard | Admin, Super Admin |
| `/admin/monitors/`, `/admin/incidents/` | Monitoring and incidents | Admin, Super Admin |
| `/admin/notifications/` | Global Telegram logs and settings | Read: Admin; configure: Super Admin |
| `/admin/settings/mail/` | SMTP/PHP mail configuration, test delivery | **Super Admin only**, GET + CSRF-protected POST |
| `/admin/settings/general/`, `/admin/settings/branding/` | Core configuration | Super Admin |
| `/admin/system/`, `/admin/maintenance/` | Health and controlled maintenance | Super Admin |
| `/admin/users/`, `/admin/portal-users/` | Account management | Super Admin |
| `/account/login/`, `/account/verify/` | Portal email OTP | Public login flow |
| `/account/`, `/account/monitors/`, `/account/incidents/`, `/account/analytics/` | Portal-only owner-scoped data | Authenticated Portal User |
| `/account/status-page/`, `/account/telegram/` | Personal status and Telegram settings | Authenticated Portal User |
| `/telegram/webhook/` | Bot API callback, validated secret header | Telegram only |
| `/status/`, `/status/{slug}/` | Platform and personal public status pages | Anonymous, published data only |

All browser URLs use physical folder-style entry points, except personal slug rewrite already implemented in Stage 10B. No `?page=` routing. Standard Admin cannot reach the Mail page or run mail settings POST handlers even via a manual URL request.

## 4. Telegram link flow, webhook setup, and security

1. Super Admin opens `/admin/notifications/`, saves a valid shared Bot Token and ensures General Settings has an absolute **HTTPS** Base URL. The Bot Token itself is never shown after save.
2. Super Admin clicks **Configure Webhook** (authenticated POST + CSRF). `TelegramLinkManager::configureWebhook()` calls Telegram `getMe` over HTTPS, saves the discovered username, generates a cryptographically random 32-byte-hex webhook secret and registers `https://{configured-domain}/telegram/webhook/` using Telegram `setWebhook(secret_token=...)`, restricted to `message` updates. The settings keys `telegram_bot_username`, `telegram_webhook_secret`, and `telegram_webhook_configured` store the resulting configuration. The boolean is the **last known configuration result**, not independent live Telegram status monitoring. Saving a different Bot Token clears cached username and webhook configuration, requiring setup again.
3. Logged-in Portal User opens `/account/telegram/` and presses **Connect Telegram** (POST, CSRF). This requires the webhook to have been configured and the bot identity to be known/verified. A 24-byte CSPRNG token is base64url-encoded into 32 URL-safe characters; only `SHA256(token)` is stored as `telegram_link_tokens.token_hash`. Previous unused tokens for that account are consumed.
4. A deep link `https://t.me/{bot_username}?start={token}` appears **once** in the authenticated response. The token expires 15 minutes after generation. Treat the link as a temporary login-equivalent secret; do not paste it in public chat or diagnostics.
5. The Telegram Bot API POSTs the `/start TOKEN` private-chat update to `/telegram/webhook/`. The handler requires `X-Telegram-Bot-Api-Secret-Token`, compares it using `hash_equals`, rejects invalid methods and oversized bodies, parses only bounded JSON, and does not log raw message contents. Group/channel chat messages are ignored. The Telegram sender ID must equal the private chat ID, and bots are ignored.
6. In a database transaction, a still-active Portal User and an unconsumed, unexpired hashed token are looked up with a row lock. It refuses Telegram identities linked to another Portal User. A successful link stores `telegram_chat_id`, `telegram_user_id` as strings, an optional validated `telegram_username`, `telegram_connected_at` UTC and enables personal alerts. All unused tokens for that user are consumed. TelegramNotifier sends a confirmation using the configured white-label app name. If this confirmation fails, the valid database link remains.
7. Disconnect is authenticated POST + CSRF + confirmation. It nulls Telegram identity fields, disables personal alerts, invalidates unused tokens and terminates unsent old-recipient notifications. The historical delivery logs remain. Reconnect repeats the flow. The portal alert toggle is POST + CSRF and can be changed without disconnecting.

A Portal User has **one** Telegram identity, no chat-ID entry field, and cannot connect another user or a Telegram group. Private Telegram chat IDs and identities never appear on public status pages or in other Portal Users' pages. Admin Portal User detail shows Connected/Not Connected, Enabled/Disabled and connection time, not raw Chat ID.

## 5. Notification eligibility, idempotency, delivery and retries

On a Stage 04 incident **down** or **recovered** transition, `NotificationManager::enqueue()` can create two types of recipient rows:

- `admin_global`, recipient_id `0`: only if global Telegram is enabled, configured bot token and admin Chat IDs exist, per-monitor Telegram enabled, and corresponding global alert type enabled. Multiple admin Chat IDs each get their own outbox row as before.
- `portal_user`, recipient_id `portal_users.id`: only if the monitor belongs to that account (`monitors.portal_user_id` matches), the Portal User is active and connected, personal alerts enabled, per-monitor Telegram enabled, and the shared bot token exists. **The global enable/down/recovery checkboxes do not govern personal delivery**; personal alerts are independent of global destinations.

Outbox rows include `destination_chat_id` as an immutable delivery snapshot. The database uniqueness key is `(channel, incident_id, event_type, recipient_type, recipient_id, destination_chat_id)`. It differentiates global and personal delivery, even if both use the same Telegram chat ID. Existing rows default to `recipient_type='admin_global'`, `recipient_id=0`. There is no duplicate incident transition on continued failed checks, recovery/healthy checks, or manual Test Now.

`CronRunner` still takes an `flock` and processes checks and incident state before attempting **at most 3** due Telegram messages per cron run with its existing soft runtime budget. Each outbox row is claimed with a 5-minute processing lease and attempt counter, sent via PHP cURL over verified HTTPS, then marked sent or pending/failed. Failed attempts are scheduled after 1 then 5 minutes, with **3 total attempts**. Fatal/invalid recipient conditions become terminal. Before each send, the dispatcher rechecks monitor-level flag, current monitor owner, active Portal User state, personal preference, and **exact connected chat ID**. Changing/disconnecting a connection never redirects an old pending message to a new destination. A failed personal send does not prevent global alerts, and Telegram failures do not invalidate the incident transaction.

Telegram has no idempotency key for `sendMessage`. If Telegram accepts a message but PHP terminates before recording sent status, a retry may generate a duplicate. Exactly-once external delivery is **not guaranteed**. Admin/global and personal recipients have separate logs; the admin history masks personal destination Chat IDs. The incident page can identify recipients without publicly exposing user Telegram identities.

Personal messages use the existing `TelegramNotifier::message()` white-label DOWN/Recovery templates. Dynamic monitor names, URLs, timestamps and errors are HTML-escaped; local display time uses configured app timezone, DB event/log times stay UTC. A link to `/status/{slug}/` is attached only if that user's public page and the specific monitor are both public. Private monitor URLs may still appear in an intentionally connected personal Telegram account, as expected by the owner.

## 6. Database: table definitions and indexes

Full authoritative fresh-install schema from `database/schema.sql` follows. Fresh installs need **no historic migrations**:

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
  recipient_type VARCHAR(20) NOT NULL DEFAULT 'admin_global',
  recipient_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
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
  UNIQUE KEY uniq_notification_recipient_event (channel, incident_id, event_type, recipient_type, recipient_id, destination_chat_id),
  INDEX idx_notification_recipient_history (recipient_type, recipient_id, created_at),
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
  telegram_chat_id VARCHAR(128) NULL,
  telegram_user_id VARCHAR(32) NULL,
  telegram_username VARCHAR(64) NULL,
  telegram_connected_at DATETIME NULL,
  telegram_notifications_enabled TINYINT(1) NOT NULL DEFAULT 0,
  public_slug VARCHAR(120) NOT NULL,
  public_status_enabled TINYINT(1) NOT NULL DEFAULT 0,
  status_page_title VARCHAR(190) NULL,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  UNIQUE KEY idx_portal_email (email), UNIQUE KEY uniq_portal_public_slug (public_slug), UNIQUE KEY uniq_portal_telegram_user (telegram_user_id), KEY idx_portal_status (status)
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

-- Stage 10C: private, expiring, one-time Telegram account linking.
CREATE TABLE IF NOT EXISTS telegram_link_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  portal_user_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  consumed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_telegram_link_token_hash (token_hash),
  KEY idx_telegram_link_user_expiry (portal_user_id, expires_at),
  CONSTRAINT fk_telegram_link_portal FOREIGN KEY (portal_user_id) REFERENCES portal_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


### Stage 10A.1 upgrade patch

The following file is supplied separately as `monitor-sql-patch-v0.2.3.sql` and stored in the FULL ZIP at `database/patches/v0.2.3.sql`. It performs only additive key/value defaults and metadata changes, not a schema reset. The MySQL schema is unchanged because `settings(setting_key, setting_value, updated_at)` already accommodates SMTP options.

```sql
-- Monitor SMTP Stage 10A.1, cumulatively applied to actual v0.2.2 baseline.
-- Upgrade v0.2.2 -> v0.2.3. Back up the database before applying once.
-- No schema DDL is required: settings is already a key/value table.
-- Existing OTP, Telegram, portal, branding and monitoring data are retained.
INSERT INTO settings(setting_key,setting_value,updated_at) VALUES
('mail_transport','php_mail',UTC_TIMESTAMP()),
('smtp_host','',UTC_TIMESTAMP()),
('smtp_port','587',UTC_TIMESTAMP()),
('smtp_encryption','tls',UTC_TIMESTAMP()),
('smtp_auth_enabled','1',UTC_TIMESTAMP()),
('smtp_username','',UTC_TIMESTAMP()),
('smtp_password','',UTC_TIMESTAMP()),
('mail_reply_to','',UTC_TIMESTAMP()),
('smtp_timeout','15',UTC_TIMESTAMP()),
('mail_last_test_at','',UTC_TIMESTAMP()),
('mail_last_test_status','never',UTC_TIMESTAMP()),
('mail_last_test_error','',UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE setting_key=setting_key;

INSERT INTO system_meta(meta_key,meta_value,updated_at)
VALUES ('installed_version','v0.2.3',UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value),updated_at=VALUES(updated_at);
```

## 7. Other existing services retained

- **Authentication:** Admin and Super Admin have password authentication, a shared minimum of 8 characters, active-user checks, role authorization and session invalidation. Portal Users are OTP-only, six-digit random code, 10-minute expiry, five verification attempts, 60-second resend cooldown and per-email/IP throttling; sender configured in `mail_from_email` / `mail_from_name`; `Mailer` uses either the configured native SMTP transport or legacy PHP `mail()`.
- **Monitors:** Admin platform monitors have null owner, Portal User monitors use a real owner foreign key and transactional per-user quota. Existing user dashboards, CRUD, Test Now, incidents and analytics keep strict ID/owner scoping.
- **Scheduled monitoring:** CLI cron checks due monitors (including portal owned) with HTTP validation, retries and SSL checking. The existing incident engine confirms outages with failure thresholds and closes on recovery. Manual tests never alter incidents or trigger alert messages.
- **Analytics:** Real scheduled-check samples drive 24h/7d/30d check success percentage, min/max/avg response time, hourly rollups and retention. Missing observations are not asserted as uptime; incident overlaps are separate. Bounded cleanup preserves incident history.
- **Public status:** Global `/status/` lists only platform-owned, public monitors. `/status/{slug}/` requires active, published Portal User and only public monitors/incidents belonging to that owner; factual 30-day history with no-data periods, configured branding, Canonical and Open Graph. No personal Telegram fields are included.
- **Maintenance:** Super Admin-only diagnostics, cron health, scheduling repair, retention cleanup, hourly aggregate processing, role/security tools and audit summaries. No shell, Node, persistent daemon or arbitrary SQL endpoint.


## 8. SMTP implementation

### Mailer and transport selection

`Mailer::send(recipient, subject, body, optionalTransportOverride)` is the single entry point for transactional mail; it reads from `Settings::all()` and returns a structured result containing `success`, `stage`, `smtp_code` (when known) and `safe_message`. `Mailer::otp()` builds the 10-minute English OTP message and calls `Mailer::send()`. The caller consumes an undelivered code and does not display transport errors publicly. The default transport is `php_mail` and is unchanged on an existing installation unless Super Admin explicitly changes it. A test can override **just the transport** to SMTP without changing the live OTP selection.

### Native SMTP protocol

`SmtpClient::send()` uses `stream_socket_client()` and checks 220 greeting, multiline 250 EHLO response, optional 220 STARTTLS followed by verified `stream_socket_enable_crypto()` and a second EHLO, optional advertised AUTH PLAIN or LOGIN (235 success), MAIL FROM 250, RCPT TO 250/251, DATA 354, body 250 and best-effort QUIT. Socket read/write operations use the configured 3–60-second timeout. Messages use CRLF, SMTP dot-stuffing, Date, From, To, UTF-8 Subject (RFC 2047 encoded-words), unique Message-ID, MIME-Version, Content-Type text/plain UTF-8 and base64 Content-Transfer-Encoding. Long subjects are split at UTF-8 code point boundaries into valid encoded-words. The body is not recorded in the database.

Supported encryption: `tls` STARTTLS (typically 587), `ssl` implicit SSL/TLS (typically 465), `none` for **unauthenticated** local or private relays. TLS certificate and hostname verification are always enabled. The application **refuses to transmit SMTP credentials over a cleartext connection**. SMTP AUTH PLAIN and LOGIN are selected from advertised server capabilities after TLS negotiation. OAuth2 is not included.

### Settings and credential storage

Existing `settings` table keys:

| Key | Default | Meaning |
|---|---|---|
| `mail_transport` | `php_mail` | `smtp` or `php_mail` |
| `smtp_host` | empty | Provider host/IP |
| `smtp_port` | `587` | Port 1–65535 |
| `smtp_encryption` | `tls` | `tls`, `ssl`, `none` |
| `smtp_auth_enabled` | `1` | SMTP auth flag |
| `smtp_username` | empty | SMTP login name |
| `smtp_password` | empty | Protected secret, not returned in UI |
| `mail_from_email` | existing/empty | Validated sender, required for SMTP |
| `mail_from_name` | existing/empty | Display name, defaults to app name if empty |
| `mail_reply_to` | empty | Optional Reply-To address |
| `smtp_timeout` | `15` | Connection/read/write timeout seconds |
| `mail_last_test_at` | empty | UTC safe test timestamp |
| `mail_last_test_status` | `never` | `success` or `failed` |
| `mail_last_test_error` | empty | Safe diagnostic only |

**Secret strategy:** SMTP password is stored in `settings.setting_value` under the key `smtp_password`, consistent with the pre-existing Telegram Bot Token storage design. The password is **not encrypted at rest**; a database administrator or someone with database backups can read it. Protect database access, backup files, and administrator accounts. Passwords never appear in the HTML source, JavaScript, logs, FULL MD, default SQL/ZIP or error text. Leaving the password input blank retains the saved credential; clearing it requires a separate checkbox and explicit Yes confirmation. The UI indicates only **Configured** or **Missing**.

### Validation and safe errors

Super Admin POST requires the existing CSRF field and validates host/port, encryption, authentication, username, sender/Reply-To, timeout and transport. It rejects CR/LF header injection, unsupported modes and auth with no encryption. The SMTP client maps provider failures to safe categories: connect, greeting, EHLO, TLS, authentication, MAIL FROM, RCPT TO, DATA, send. No raw SMTP transcript, auth string, exception trace or token-containing URL is ever stored or displayed. For a failed OTP send, the original generic portal response remains and the unreceived code is consumed.

### Send Test Email

Super Admin opens `/admin/settings/mail/`, saves a provider configuration, then uses **Send Test Email** to a validated recipient (default current Super Admin email). The POST action is CSRF-protected and does not create OTP, login sessions, monitors or incidents. The UI allows testing the **saved SMTP settings** even while OTP uses `php_mail`. The result and UTC test timestamp are stored in safe settings keys; diagnostic text is fixed and sanitized rather than copied from SMTP server responses. A success indicates acceptance by the SMTP relay or PHP mail transport, **not guaranteed inbox placement**.

### Deliverability

Check SPF, DKIM, DMARC, PTR/rDNS, receiving-provider filters and sending reputation when Gmail, Outlook or other providers do not deliver. Native SMTP cannot itself guarantee inbox delivery. Using authenticated TLS SMTP with a reputable provider typically gives better control than an unconfigured PHP mail relay. This stage intentionally does not implement DNS changes, inbound mail, newsletters, mail queues, OAuth2 or incident email alerts.

### System Health

`/admin/system/` safely lists active mail transport, SMTP host status, port, encryption, username status, password status, From Email, and last test result/time, plus a link to Mail / SMTP settings. No password values or transcripts are rendered.

## 9. Installation and upgrade

**Fresh install v0.2.3:** create an empty database and deploy `monitor-full-v0.2.3.zip` in the document root without a wrapper folder. Open `/install/` and create the first Super Admin, branding and timezone. `database/schema.sql` creates the existing full schema; the installer seeds current `Settings::defaults()` including SMTP settings and `system_meta.installed_version=v0.2.3`. SMTP is optional during installation. Configure `/admin/settings/mail/` and send a test before changing the active transport; set cron (every minute or host minimum) and verify OTP.

**Upgrade from the latest accepted v0.2.2:**

1. Back up database and files and pause Cron during deployment.
2. Run `monitor-sql-patch-v0.2.3.sql` **once**, preserving all older settings. Because no column DDL is needed, this patch is compatible with live v0.2.2 code.
3. Extract `monitor-patch-v0.2.3.zip` over the installed root, replacing source files only. Do not overwrite `config/database.php`, `storage/installed.lock`, runtime folders or customer branding.
4. Restart Cron and sign in as Super Admin. Open `/admin/settings/mail/` and save the SMTP provider details. The existing `php_mail` transport stays active until explicitly changed.
5. Test saved SMTP against an external mailbox while PHP mail is active, switch to SMTP when ready, and test a real Portal OTP email. Verify admin, Telegram alerts and both `/status/` and personal status routes.

Cron examples (actual paths vary):

```bash
/usr/local/bin/php /home/USERNAME/public_html/monitor/cron/monitor.php
/opt/plesk/php/8.2/bin/php /var/www/vhosts/DOMAIN/httpdocs/cron/monitor.php
```

**No deletions** from v0.2.2. Existing users/passwords, portal quota/ownership, OTP records, incidents, monitor checks, notification history, hourly statistics, Telegram settings and personal status pages remain intact. Do not reinstall and do not apply an old v0.2.0-r1 patch after v0.2.2.

## 10. Verification and known limitations

**Code-level verification:** All PHP files are syntax-checked; legacy version checks are updated to v0.2.3. Deterministic tests exercise SMTP settings validation, secret masking, role/CSRF, OTP integration, TLS flags, schema/version and prior features. Loopback SMTP test server verified the RFC dialogue, multiline replies, server accept/reject behavior, dot-stuffing and generated MIME headers without connecting to the internet. The package is checked for no secrets, ZIP wrapper and correct 644/755 file modes.

**Live verification still required:** actual TLS handshake with the intended SMTP provider, successful authenticated SMTP submission and real external inbox receipt, OTP login through the selected SMTP server, Plesk/cPanel route/permissions, SQL patch execution on production MySQL, and any provider-specific SMTP policy limitations. This environment lacks the real SMTP credentials and remote network access; it cannot claim delivery to Gmail/Outlook or production installation was tested here.

**Known limitations:** native SMTP supports standard username/password AUTH PLAIN/LOGIN, not OAuth2; complex provider SMTP auth policies may require app passwords or relay configuration; no mail queues or bounce processing; SMTP data acceptance is not inbox acceptance. Credentials in the settings database are protected by permissions and UI masking but not application-layer encryption at rest. Existing Telegram external delivery also remains at-least-once in crash-after-accept scenarios. All previous monitoring, public status, Portal User and admin-role limitations remain as in v0.2.2.

**Suggested next development:** production SMTP interoperability/OTP deliverability QA as a focused patch after deployment, with future provider-specific enhancements only if needed. Keep v0.2.3 as the newest cumulative FULL ZIP + FULL MD baseline for further 10D work. No billing, newsletters, bulk email, OAuth2 or email incident alerts in this SMTP stage.

## Hotfix: Portal User Add/Edit Monitor HTTP 500

**Hotfix ID:** `v0.2.3-portal-hotfix` (same application/database version `v0.2.3`; cumulative on the accepted v0.2.3 FULL ZIP).

**Reported symptom:** Portal User opens `/account/monitors/add/` or `/account/monitors/edit/?id=...` and sees a `500 Service Temporarily Unavailable` message embedded after the portal heading. The HTML form never appears.

**Root cause:** `app/Views/portal/page.php` invoked `Security::post()` without a fully qualified class name. The `use Monitor\Core\Security;` import in the dispatcher does not apply to a separately included view. PHP therefore throws `Error: Class "Security" not found` during view rendering, including for HTTP GET requests. The application's production error handler replaces the remaining HTML with a safe 500 error page.

**Fix:** The form branch now calls `\Monitor\Core\Security::post()` explicitly, consistently with the existing `\Monitor\Core\Security::csrfField()` calls. Both Add and Edit forms now use the same qualified class. Request validation, CSRF protection, monitor quota checks, ownership checks and form field values remain unchanged.

**Affected deployment path:** `app/Views/portal/page.php`. The bundled `MONITOR-FULL-PROJECT-v0.2.3.md` has been updated with this note. No other PHP, schema or infrastructure files are changed. No application version bump, migrations, or SQL patch are required. No deletions.

**Install/upgrade:** Back up the current file, then extract `monitor-hotfix-v0.2.3-portal-add-monitor.zip` into the existing document root, keeping the path hierarchy and overwriting matching files. Do not re-run `/install/` or any SQL migration. Refresh `/account/monitors/add/` and `/account/monitors/edit/?id=...` as a Portal User; test both an empty GET and validation error POST. On a host with PHP OPcache, refresh/invalidate the OPcache if stale output continues. Cron is unaffected.

**Code verification:** The original error was reproduced with a PHP CLI render harness and then fixed. Four render cases passed: Add GET, Edit GET, Add POST with validation error and Edit POST with validation error. All four retain the CSRF field. The entire existing regression suite passed **792/792** assertions after the fix. All distributable PHP source files passed `php -l`, ZIP permissions and file integrity were validated. The patch was applied to the exact accepted v0.2.3 FULL ZIP baseline and compared byte-for-byte to the new FULL ZIP.

**Live verification still required:** Reload the portal routes on the installed Plesk host, submit a real monitor under a user's quota, verify persisted ownership and scheduled checking, and confirm Edit Monitor works. The development environment does not have a live copy of the production database, so this is a source-level, not a live-host, fix verification.

**Baseline rule:** For all subsequent Monitor development, use `monitor-full-v0.2.3-portal-hotfix.zip` and the updated FULL MD rather than the pre-hotfix `monitor-full-v0.2.3.zip`.



## GitHub public release information

The repository contains a curated source tree; archived milestone documents from prior development stages are not required to install v0.2.3. The live application is defined by the v0.2.3 source files and `database/schema.sql`. Keep prior migration scripts to upgrade installations at older versions.
