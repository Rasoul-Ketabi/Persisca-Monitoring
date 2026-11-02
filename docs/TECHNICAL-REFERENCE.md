# Monitor | Full Project Reference v0.2.2

**Release:** v0.2.2, Stage 10C: Telegram Account Linking and Per-User Telegram Alerts.  
**Authoritative source:** accepted v0.2.1 FULL ZIP and FULL MD, including the v0.2.0 sidebar-500 hotfix and Stage 10B form correction.  
**Runtime:** PHP 8.1+, PDO MySQL/MariaDB, InnoDB, PHP cURL, TLS, sessions and fileinfo; Apache/LiteSpeed or appropriately configured nginx. No Node/Redis/WebSockets/daemon/root required.  
**Status:** Maintainer-tested v0.2.2. The owner reports deploying and testing the application. Developer-side verification did not include direct MySQL, mail or Telegram integration, and independent security auditing is not claimed.

## 1. Architecture and preservation

The application is a self-hosted, fully white-label HTTP monitor. It retains password-based Super Admin/Admin accounts; per-user OTP Portal accounts and quotas; scheduled HTTP monitoring and manual diagnostics; incidents and recovery; global multi-chat Telegram alerts; hourly analytics and retention; a global `/status/` and owner-scoped personal `/status/{slug}/` pages; and admin maintenance tools. All interface and generated text remains English. Admin password minimum remains 8 characters.

Stage 10C adds a **private, connected Telegram identity per Portal User**, an authenticated Bot API webhook, one-time deep-link tokens, independent per-recipient notification outbox entries and portal controls. IncidentManager does not talk to Telegram. During a scheduled check, the existing check/incident transaction calls NotificationManager::enqueue, which records eligible admin-global and connected user-recipient events. Cron then delivers a bounded set of due messages after committing operational state. No manual Test Now notifications.

Secrets (Bot Token and webhook secret) reside in the **database `settings` table** as `telegram_bot_token` and `telegram_webhook_secret`. Database administrators and database backups can read those secrets, so DB access and backup files must be protected. Neither secret is rendered in saved HTML or exported in distribution defaults, logs, FULL MD or patch SQL.

## 2. Complete distribution file tree

The tree contains source files in the shipped FULL ZIP. Runtime locks, configuration, sessions, uploaded branding, and logs are excluded.

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
tests/stage10b.php
tests/stage10c.php
MONITOR-FULL-PROJECT-v0.2.2.md
```

## 3. Routes and access control

| Route | Purpose | Audience |
|---|---|---|
| `/admin/` | Operational dashboard | Admin and Super Admin |
| `/admin/users/` | Manage administrator accounts | Super Admin |
| `/admin/portal-users/` | Create portal accounts and quotas | Super Admin |
| `/admin/portal-users/view/?id=N` | Public page settings and Telegram connection state | Super Admin |
| `/admin/notifications/` | Global destinations, delivery logs, bot webhook setup | GET: Admin; change: Super Admin |
| `/admin/maintenance/`, `/admin/system/`, `/admin/settings/branding/` | Sensitive operations | Super Admin |
| `/account/login/`, `/account/verify/` | Email OTP | Unauthenticated Portal User |
| `/account/` | Personal monitoring dashboard | Logged-in Portal User |
| `/account/monitors/`, `/account/incidents/`, `/account/analytics/?id=N` | Owner-scoped monitoring | Logged-in Portal User |
| `/account/status-page/` | Personal page publication and monitor privacy | Logged-in Portal User |
| `/account/telegram/` | Telegram connect, alerts, disconnect | Logged-in Portal User |
| `/telegram/webhook/` | Telegram message updates, secret-header authenticated | Telegram Bot API only |
| `/status/` | Public platform monitor status | Anonymous |
| `/status/{slug}/` | Published personal status page | Anonymous, only active published owner |

Apache folder entry points are physical PHP `index.php` files except the existing internal rewrite for personal slugs. Webhook is an actual `/telegram/webhook/index.php`. It does not create an admin/portal PHP session (`MONITOR_NO_SESSION`). `bootstrap.php` and source/config/runtime paths are protected by webserver rules; nginx must mirror Apache protections.

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

### Stage 10C upgrade migration

The separate file `monitor-sql-patch-v0.2.2.sql` is the **one-time** migration from accepted v0.2.1. It adds five nullable/defaulted Telegram identity/preference columns and unique user identity to `portal_users`, replaces the Stage 05 notification key with recipient-aware uniqueness, creates `telegram_link_tokens`, seeds empty/non-secret Telegram settings keys, and updates installed version metadata. It does not reset any account, password, monitor, check, incident, personal slug/page setting, existing Telegram Bot Token/Chat IDs, analytics or prior notification row. There is no automatic Telegram account linking. Historic notifications retain admin-global identity with default recipient ID `0`.

## 7. Other existing services retained

- **Authentication:** Admin and Super Admin have password authentication, a shared minimum of 8 characters, active-user checks, role authorization and session invalidation. Portal Users are OTP-only, six-digit random code, 10-minute expiry, five verification attempts, 60-second resend cooldown and per-email/IP throttling; sender configured in `mail_from_email` / `mail_from_name`, transported through host PHP `mail()`.
- **Monitors:** Admin platform monitors have null owner, Portal User monitors use a real owner foreign key and transactional per-user quota. Existing user dashboards, CRUD, Test Now, incidents and analytics keep strict ID/owner scoping.
- **Scheduled monitoring:** CLI cron checks due monitors (including portal owned) with HTTP validation, retries and SSL checking. The existing incident engine confirms outages with failure thresholds and closes on recovery. Manual tests never alter incidents or trigger alert messages.
- **Analytics:** Real scheduled-check samples drive 24h/7d/30d check success percentage, min/max/avg response time, hourly rollups and retention. Missing observations are not asserted as uptime; incident overlaps are separate. Bounded cleanup preserves incident history.
- **Public status:** Global `/status/` lists only platform-owned, public monitors. `/status/{slug}/` requires active, published Portal User and only public monitors/incidents belonging to that owner; factual 30-day history with no-data periods, configured branding, Canonical and Open Graph. No personal Telegram fields are included.
- **Maintenance:** Super Admin-only diagnostics, cron health, scheduling repair, retention cleanup, hourly aggregate processing, role/security tools and audit summaries. No shell, Node, persistent daemon or arbitrary SQL endpoint.

## 8. Installation and upgrade

**Fresh install:** upload `monitor-full-v0.2.2.zip` into an empty web root, create an empty MySQL database, open `/install/`, create initial Super Admin, configure application branding/timezone/URL, then schedule Cron every minute. Setup mail sender in `/admin/portal-users/` for OTP and Telegram bot/webhook in `/admin/notifications/`. Create Portal Users later; public pages remain disabled by default. Ensure configured HTTPS URL is actually reachable from Telegram.

**Upgrade from v0.2.1:**

1. Back up all files **and database**, stop cron and avoid admin/portal writes during upgrade.
2. Execute `monitor-sql-patch-v0.2.2.sql` **once** in phpMyAdmin before deploying code. The migration adds columns that the new code references. Do not rerun installer or old migration scripts.
3. Extract `monitor-patch-v0.2.2.zip` directly over the installed app and overwrite changed source, preserving server `config/database.php`, `storage/installed.lock`, uploads, sessions and logs.
4. Resume cron. Log in as Super Admin, open `/admin/notifications/`, save/verify bot token, click **Configure Webhook**. The configured `base_url` must use HTTPS.
5. Log in as a Portal User, open `/account/telegram/`, press **Connect Telegram**, open the deep link and send `/start` in the bot's private chat. Refresh Portal to verify the connected state. Test user-only DOWN and Recovery in staging, admin global alert independence, disconnect/reconnect and disable preferences.
6. Check `/status/`, `/status/{slug}/`, OTP, analytics, incidents and System Health after the upgrade.

Cron examples (actual binary/folder vary):

```bash
/usr/local/bin/php /home/USERNAME/public_html/monitor/cron/monitor.php
/opt/plesk/php/8.2/bin/php /var/www/vhosts/DOMAIN/httpdocs/cron/monitor.php
```

## 9. Verification, limitations and next stage

**Development verification:** PHP lint across application, new Stage 10C deterministic source/pure-function assertions, and compatibility regression tests for Stages 01–10B. ZIP contents are compared with the actual v0.2.1 baseline and archive modes validated. No historical files are intentionally removed.

**Verification record:** The project owner reports that the application was installed and tested on their server. The build environment used for this source did not independently execute MySQL/Plesk integration or use real Telegram and mail credentials. Each operator should validate database migrations, outgoing mail, Telegram `getMe`/`setWebhook`, incoming webhook authentication, account linking, dual-recipient alerts, cron timing, permission enforcement and mobile layout on their own hosting environment. There is no independent security certification.

**Known limits:** one connected private Telegram account per Portal User; no Telegram polls/commands for managing monitors; admin multiple Chat IDs still supported; webhook setup state is last successful registration, not live status monitoring. Telegram's lack of sendMessage idempotency prevents exactly-once delivery in crash-after-accept conditions. PHP `mail()` depends on host transport. Database access can reveal bot/webhook secrets, so protect DB and backups. Cleanup of expired link tokens is not yet automated; reconnect/disconnect invalidates outstanding tokens and expiry rejects stale links.

**Next recommendation:** After confirming v0.2.2 on the actual host, continue with a focused **Stage 10D: Portal Telegram UX & Production Reliability QA**, covering real webhook/retry integration tests, secret rotation/health checks, expired token cleanup, and optional richer per-user alert preferences, while preserving the accepted FULL ZIP/MD as the sole source of truth. Do not implement billing, teams, email incident alerts or unsupported Telegram commands as part of 10C.
