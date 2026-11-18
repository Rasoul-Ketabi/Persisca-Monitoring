# Monitor | Full Project Reference v0.2.4

**Version:** `v0.2.4`
**Baseline:** accepted `v0.2.3` with Portal Add/Edit Monitor hotfix.
**Scope:** revised Stage 10C, exclusive owner-based Telegram notification routing.
**Runtime:** PHP 8.1+, PDO MySQL/MariaDB, cURL/TLS, fileinfo, sessions, standard shared hosting.

## 1. Product architecture

Monitor is a self-hosted native PHP application with MySQL/MariaDB, a browser installer, role-based Admin/Super Admin accounts, password policies, a separate email-OTP Portal User layer, per-user monitor quotas, HTTP/HTTPS checks, incident transitions, Telegram delivery, analytics/retention, maintenance tools and public status pages. It is English-only and white-label, compatible with standard shared hosting. The first administrator is Super Admin, and the admin password minimum is eight characters.

**This v0.2.4 stage is a cumulative security/behavior change, not a new Telegram linking implementation.** It is built on the actual `monitor-full-v0.2.3-portal-hotfix.zip` version, preserving all SMTP functionality, monitor forms, public pages and the existing Telegram webhook/deep-link implementation. The revised Stage 10C prompt asked for v0.2.2 on an older baseline, but that version was already shipped and superseded by v0.2.3; a fresh v0.2.4 version prevents downgrade.

**Release state:** source-level verification passed. Live Telegram, cron, migrations and cross-owner routing must be confirmed on the deployed host before marking this upgrade production-verified.

## 2. Complete distribution file tree

The FULL ZIP contains this standalone root structure. Source-only configuration templates, access-denial files and empty runtime directories remain included. Real database credentials, installed locks, sessions, runtime logs, customer uploads and bot/SMTP secrets are **not** distributed. PHP tests under `tests/` have a CLI-only guard and Apache-level access denial.

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
MONITOR-FULL-PROJECT-v0.2.4.md
MONITOR-v0.1.0-RELEASE-NOTES.md
MONITOR-v0.2.4-RELEASE-NOTES.md
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
database/patches/v0.2.4.sql
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
tests/stage10a1.php
tests/stage10b.php
tests/stage10c-exclusive.php
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

## 5. Strict owner-based Telegram routing, outbox and retries

**Exclusive rule:** `NotificationManager::recipientForMonitor()` makes the sole recipient-type decision at the start of an incident transition, while the scheduled check and incident are saved inside one database transaction.

| Monitor ownership | Recipient type | Eligible destination | Not allowed |
|---|---|---|---|
| `monitors.portal_user_id IS NULL` | `admin_global`, ID `0` | Existing global/admin Telegram Chat IDs | Any Portal User |
| `monitors.portal_user_id = N` | `portal_user`, ID `N` | The one Telegram chat connected to owner `N` | Every admin/global Chat ID and every other Portal User |

**There is no fanout and no fallback between scopes.** In particular, when the Portal User is disabled, unconnected, has disabled alerts, or Telegram delivery fails, **NO** global/admin notification is enqueued. Conversely, platform checks never query a personal destination. A monitor with invalid non-null owner ID is rejected rather than treated as platform-owned.

- Admin eligibility: globally enabled Telegram, valid shared bot token, configured admin chat ID(s), per-monitor Telegram flag, and corresponding global DOWN/RECOVERY option. Multi-Chat-ID delivery is retained for platform-owned monitors only. Each admin Chat ID has its own outbox row and retry cycle.
- Portal eligibility: owning Portal User active, connected chat ID, personal alert toggle enabled, monitor Telegram flag enabled, shared Bot Token valid, real scheduled `down` or `recovered` event. The global admin enable/down/recovery toggles do **not** govern a Portal User's own alert preferences.
- Missing/disabled Portal recipient or unconfigured bot: one terminal `failed` history row is stored with `recipient_type='portal_user'`, `recipient_id=N`, blank destination, and a non-sensitive reason such as `not_connected`, `owner_disabled`, `alerts_disabled`, `owner_unavailable`, or `bot_unavailable`. Nothing is sent elsewhere. When a user later connects Telegram, historical incident transitions are not replayed automatically.
- Only real incident `down` and `recovered` transitions create new messages. Initial UP, continued outage, continued healthy checks, and manual Test Now create no incident notification.
- Existing database uniqueness remains `(channel, incident_id, event_type, recipient_type, recipient_id, destination_chat_id)` to suppress duplicate logical outbox rows. Platform Chat IDs are distinct deliveries; personal recipient IDs identify their owner. A personal recipient gets one destination snapshot for each event.
- `CronRunner` uses its existing nonblocking `flock`, saves monitoring first, then handles a maximum of three Telegram attempts per cron execution with a soft run-time deadline. Notification failures cannot roll back the successful check/incident transaction. Each row receives at most three tries and terminal failure when retries are exhausted.
- **Critical retry safeguard:** `NotificationManager::matchesOwnership()` is called for every claimed due row **before** calling the Bot API. If a previously queued `admin_global` record belongs to a Portal-owned monitor (or the opposite, or a Portal ownership transfer changed owner ID), the row becomes `failed` with `wrong_owner` and is never transmitted. Personal deliveries also re-check active status, alert preference and exact previously connected destination. These checks apply to old queues as well as new ones.
- The v0.2.4 SQL migration proactively marks any still-`pending` or `processing` cross-owner Telegram records `failed` with a safe `wrong_owner` reason. It preserves previously `sent`/`failed` historical records and all legitimate pending messages. **Pause cron before executing the migration** to avoid a concurrent old-version process delivering a queued message.
- The dispatcher uses `incidents.monitor_id = monitors.id` as an extra row-consistency requirement. Admin log pages preserve historical delivery events and mask Portal User Chat IDs.

The actual Telegram link flow is unchanged: `/account/telegram/` creates a 15-minute hashed one-use private deep link, `/telegram/webhook/` validates `X-Telegram-Bot-Api-Secret-Token`, `getMe` discovers bot username, and disconnect/reconnect invalidates stale recipient destinations. A personal `/status/{slug}/` link is added only when the page and monitor are both public. Bot credentials never appear in public HTML.

**At-least-once delivery limitation:** Telegram Bot API has no request idempotency key. If Telegram accepts a message but PHP terminates before marking its log as sent, a subsequent retry may create a duplicate. This does not change the strict recipient identity choice. Concurrent ownership transfers during the short window between final eligibility check and the external Bot API request also require careful production testing; owner changes are uncommon but network I/O cannot be atomically committed with MySQL.

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

-- SMTP defaults are seeded by app/Models/Settings.php during fresh installation.
-- Mail configuration uses this existing key/value table; no new table is needed.
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


-- v0.2.4 routing invariant: NULL monitor owner => admin_global only; non-NULL => that portal_user only.
-- Retain historic notification records and recipient-aware event uniqueness.
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

### v0.2.4 SQL patch (no structural schema change)

`monitor-sql-patch-v0.2.4.sql` is the same source as `database/patches/v0.2.4.sql` in FULL ZIP. It does **not** change or delete schema objects, records, users, monitors, incidents, SMTP settings, OTP records, Telegram credentials or sent notification history. It marks **only unsent, cross-owner Telegram rows** terminally failed and updates `system_meta.installed_version` to `v0.2.4`. The SQL is safe to rerun; nevertheless apply it during a controlled, cron-paused upgrade. Fresh installations already have all necessary Stage 10C tables and indexes, so only `database/schema.sql` is used by the installer.

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

### Fresh v0.2.4 installation

1. Create a new, **empty** MySQL/MariaDB database and an application DB user with required rights.
2. Extract `monitor-full-v0.2.4.zip` at the web Document Root, with **no wrapper directory**. Verify `storage/` is writable and your host applies the provided `.htaccess` protections (or equivalent server rules).
3. Open `https://YOUR-DOMAIN/install/`, complete environment/DB checks and create your first Super Admin. The installer stores `system_meta.installed_version=v0.2.4` and creates `storage/installed.lock`. Never run the installer again on an existing deployment.
4. Configure application Base URL, branding, timezone, `/admin/settings/mail/` and OTP Test Email. The installer keeps PHP mail as default; SMTP must be enabled explicitly after configuring it.
5. Save Telegram Bot Token and admin Chat IDs at `/admin/notifications/`, then **Configure Webhook** for HTTPS `/telegram/webhook/` if Portal Users will link accounts.
6. Configure the existing CLI cron to execute `cron/monitor.php` about once per minute (or the shortest interval the host supports):

```bash
# cPanel sample (actual path depends on your account)
/usr/local/bin/php /home/USERNAME/public_html/monitor/cron/monitor.php
# Plesk sample (actual PHP path and domain vary)
/opt/plesk/php/8.2/bin/php /var/www/vhosts/DOMAIN/httpdocs/cron/monitor.php
```

### Upgrade from v0.2.3 with Portal Add/Edit hotfix

1. Back up all application files and the full MySQL database. **Pause cron** so no previous-code process can transmit queued cross-owner alerts during upgrade.
2. Execute **`monitor-sql-patch-v0.2.4.sql`** in phpMyAdmin once. It marks unsent wrong-owner notification rows failed and updates the installed version; existing already-sent delivery history is untouched.
3. Extract **`monitor-patch-v0.2.4.zip`** in the current Document Root with overwrite. Never replace `config/database.php`, `storage/installed.lock`, user branding uploads, runtime logs, or session files. No wrapper folder is used.
4. Resume cron, reload PHP OPcache if your host caches old PHP bytecode, and verify Admin, Portal, OTP/SMTP, public status and Telegram settings. Confirm the Maintenance/System Health version reads `v0.2.4`.
5. Trigger a controlled DOWN then RECOVERY for one **platform-owned** and one **Portal-owned** monitor. Confirm platform events reach only configured admin IDs and Portal events reach only the connected owner. Repeat with a disabled Portal alert toggle / disconnected account to confirm **no admin fallback**. Inspect `/admin/notifications/` delivery history.

**If currently on v0.2.2 or earlier**, first apply the prior v0.2.3 SMTP update and the Portal Add/Edit Monitor hotfix, then use this versioned migration. Alternatively perform a new install in an empty database. Do not apply the historic `database/schema.sql` over a running installation.

### GitHub release note

This is a cumulative v0.2.4 release following the published v0.2.3 baseline. Its salient change is exclusive Telegram owner routing. Keep the v0.2.3 Release for download history and add this release tag only after validation.

## 10. Verification and known limitations

**Completed in this development environment:**

- All 16 existing and new CLI test suites run against the extracted/source tree. Their aggregate assertions include Stage 01-10A.1/10C compatibility, SMTP settings, HTTP monitoring, event-transition guards, role isolation, public status, OTP and Telegram link code.
- Dedicated v0.2.4 routing tests use a PDO-compatible test double to capture actual enqueue SQL parameters and exercise: multi-Chat-ID admin-only routing, active/disabled/unconnected Portal User behavior, global settings independent of personal alerts, DOWN/RECOVERY, monitoring preference, no fallback, and wrong-owner retry rejection.
- `php -l` checks all source PHP files; the distribution ZIP content/permissions and Patch-on-baseline match are verified during packaging.
- `database/patches/v0.2.4.sql` and downloadable SQL patch are byte-identical. No SQL schema migration is necessary.

**Still requires live production verification:** real MySQL/MariaDB execution and rerun of migration; external Telegram `/start` and webhook; live DOWN/RECOVERY over the running cron; changing/disabling recipients with pending retries; actual SMTP/OTP delivery if not already verified on the specific server; hosting permissions and browser render. No live Telegram API call or real production DB access was used for this stage.

**Known limitations:** outbox is at-least-once, not guaranteed exactly once on transport crash; in-flight network sends cannot be canceled atomically with ownership edits; SMTP passwords and Telegram token remain plain settings values in the protected database rather than encrypted at rest; provider-specific SMTP restrictions can affect OTP delivery; no Telegram monitor-management commands, user multi-destination accounts, billing, teams, or email incident alerts in this release. Private status pages remain owner-scoped as previously implemented.

**Subsequent development:** use `monitor-full-v0.2.4.zip` plus `MONITOR-FULL-PROJECT-v0.2.4.md` as authoritative baseline. Future work may harden owner-change transactions and delivery auditing, and run production webhook/SMTP interoperability checks before new feature phases.
