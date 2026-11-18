# Monitor Project | Full Development Reference | v0.0.5.2 (Notifications Page Error Hotfix)

**Stage 05: Telegram Notifications, Delivery Logs, DOWN/RECOVERY Alerts and Notification Settings**

Updated: 2026-10-31. This document and `monitor-full-v0.0.5.2.zip` are the authoritative source of truth for Stage 06, subject to acceptance of the deployed build. Implemented cumulatively from the accepted `monitor-full-v0.0.5.zip` binary package. The server's active database/config, branding uploads and installed lock are intentionally not distributed.


## v0.0.5.2 change record (2026-10-31)

**Root cause from deployed `b1.zip`:** `admin/_dispatch.php` handled the missing `filter` query parameter by evaluating `($_GET['filter'] ?? 'all')`, then reading `$_GET['filter']` directly. On a normal visit to `/admin/notifications/` without `?filter=`, this produces an undefined key and `null`; passing that to `NotificationRepository::count(string)` under `strict_types=1` throws `TypeError`. The global exception handler shows `The application encountered an error. Please try again later.`

**Second bug:** `NotificationRepository::page()` joined `notification_logs` to `monitors` and used `WHERE status = ...`, but both tables have a `status` column. Filters `sent`, `failed`, `pending` would therefore fail with ambiguous-column SQL errors.

**Fix:**
- `NotificationRepository::normalizeFilter(mixed): string` always resolves missing/invalid filter values to `all` and preserves known filters.
- Physical route `/admin/notifications/` uses this method before requesting counts and results.
- Notification listing and count both qualify `l.status`; joined rows retain the monitor name.
- Existing monitor, incident, check, notification and settings data remains untouched.
- `VERSION` and fresh installer markers are updated to `v0.0.5.2`.
- Optional `database/patches/v0.0.5.2.sql` updates only `system_meta.installed_version`; **no schema changes** are needed, and the previous multi-chat SQL patch must NOT be re-run.
- Added `tests/notification-page.php` for missing/invalid filter, allowed filters and SQL qualification.

**Deployment:** Extract `monitor-patch-v0.0.5.2.zip` over the installed v0.0.5.1 application. No reinstall. Back up before replacing. Optional metadata SQL is safe to rerun. The existing configuration and Telegram Chat IDs are retained.

**Verified in generation environment:** source comparison between `b1.zip` and authoritative v0.0.5.1 FULL ZIP for distributable files; source-level regression, PHP syntax and ZIP/permission checks. **Not verified:** live web request with the deployed MySQL, live Telegram delivery, server cron or the hosting PHP extension set. These must be checked on Plesk.

## 1. Purpose, constraints and release scope

- Self-hosted HTTP/HTTPS endpoint monitor for shared cPanel/Plesk hosting.
- Native PHP 8.1+, MySQL/MariaDB via PDO, cURL, HTML, CSS, vanilla JS and CLI cron. No Composer, Node, Docker, Redis, root or workers required.
- Product UI and generated messages **English only** and fully **white-label**. Do not insert developer credits; use `settings.app_name`, branding and configured timezone.
- All admin navigation uses physical folder-style routes; no visible `?page=` routing. IDs may be `?id=12` only.
- Account sessions, password hashing, CSRF, PDO prepared statements, escape-on-output and admin guards from earlier releases remain.
- Retains Stage 01 installer/auth/branding/OG, Stage 02 monitor CRUD and manual diagnostics, Stage 03 check history/Cron, and Stage 04 transactional Incident Manager and public statuses.
- **New:** Telegram configuration, token safety, per-monitor alert opt-in, test message, outbox, delivery retries/logs, admin delivery history, System Health and incident delivery status.
- **Not in this version:** email/webhooks/SMS/other channels, uptime analytics, SSL expiry/maintenance windows, user management UI, billing, teams or multi-tenancy.

## 2. File structure (full release)

```text
.htaccess
MONITOR-FULL-PROJECT-v0.0.3.md
MONITOR-FULL-PROJECT-v0.0.4.md
MONITOR-FULL-PROJECT-v0.0.5.1.md
MONITOR-FULL-PROJECT-v0.0.5.md
MONITOR-FULL-PROJECT-v0.0.5.2.md
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
app/Models/NotificationRepository.php
app/Models/Settings.php
app/Models/SystemMeta.php
app/Services/Auth.php
app/Services/CronRunner.php
app/Services/HttpMonitor.php
app/Services/IncidentManager.php
app/Services/MonitorInput.php
app/Services/NotificationManager.php
app/Services/TelegramNotifier.php
app/Services/TelegramDestinations.php
app/Services/Upload.php
app/Views/admin/branding.php
app/Views/admin/dashboard.php
app/Views/admin/general.php
app/Views/admin/incident-detail.php
app/Views/admin/incidents.php
app/Views/admin/monitor-detail.php
app/Views/admin/monitor-form.php
app/Views/admin/monitors.php
app/Views/admin/notifications.php
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
database/patches/v0.0.5.sql
database/patches/v0.0.5.1.sql
database/patches/v0.0.5.2.sql
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
tests/smoke.php
tests/stage04.php
tests/stage05.php
tests/multichat.php
tests/notification-page.php
```

`/config/database.php`, `/storage/installed.lock`, runtime session files, uploaded customer logos, credentials and runtime logs are generated on the server only and **must not** ship in any ZIP. The root `/index.php`, `/admin/*/index.php`, `/status/index.php` and `/install/index.php` are real shared-hosting entrypoints. `/public/assets/` holds UI styles, JS and default image.

## 3. Routing and page map

- `/` -> status entry, based on existing root route implementation.
- `/status/` -> public service overview; public monitors and their confirmed states only; never shows Telegram configuration.
- `/admin/login/` -> admin sign-in; `/admin/logout/` POST + CSRF.
- `/admin/` -> dashboard with actual Up/Down and active Incident counts, monitor preview, cron summary.
- `/admin/monitors/` -> list, POST toggle/Test Now/delete. `/admin/monitors/add/` and `/admin/monitors/edit/?id=N` -> configuration. `/admin/monitors/view/?id=N` -> latest scheduled state, checks, incidents.
- `/admin/incidents/` -> active or resolved incidents with bounded history. `/admin/incidents/view/?id=N` -> details, related checks and notification delivery statuses.
- `/admin/notifications/` -> Telegram configuration, **Send Test Message** and paginated/filtered delivery log. This replaces the previous placeholder.
- `/admin/settings/general/`, `/admin/settings/branding/`, `/admin/status-page/`, `/admin/system/` -> retained. `/admin/system/` now includes Telegram enabled/disabled, token saved/missing, Chat ID saved/missing, and failure count (24h).

All URLs use `admin_url()`/`status_url()` and `monitor_base_path()` for subdirectory compatibility. Previous query router remains only a backwards-compatibility redirect and is not used by normal navigation.

## 4. Database schema (exact fresh-install DDL)

The browser installer reads `database/schema.sql` directly, so new installations create all Stage 01 through Stage 05 tables without running incremental SQL patches. The schema is reproduced below for precise reference:

```sql
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(32) NOT NULL DEFAULT 'admin',
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY users_email_unique (email)
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
```

The separate incremental upgrade for existing installations is `database/patches/v0.0.5.sql`, also shipped as `monitor-sql-patch-v0.0.5.sql`. It adds `monitors.telegram_notifications_enabled` (default 1), creates `notification_logs`, seeds default settings without overwriting existing values, and sets `system_meta.installed_version=v0.0.5`. **Run only once** after a successful v0.0.4 installation. It never drops or truncates the prior tables.

`notification_logs` has `UNIQUE(channel, incident_id, event_type, destination_chat_id)` so each incident has at most one logical DOWN and one RECOVERY notification **per configured destination**. Foreign keys cascade with monitor and incident deletion, as does historical check data. Important indexes: `(status,next_attempt_at)`, `(monitor_id,created_at)` and `(created_at)`. Settings continue in the existing `settings(setting_key,setting_value)` table.

## 5. Telegram settings and secret storage

Configuration defaults from `Settings::defaults()`:

| Setting key | Initial value | Meaning |
|---|---|---|
| `telegram_enabled` | `0` | Global delivery off by default |
| `telegram_notify_down` | `1` | Down event enabled if global on |
| `telegram_notify_recovery` | `1` | Recovered event enabled if global on |
| `telegram_bot_token` | empty | Bot token, only entered in authenticated settings |
| `telegram_chat_id` | empty | Newline-separated, unique destinations, up to 10; a single legacy ID still works |

**Storage choice:** The Bot Token is stored as **plaintext in the protected MySQL `settings` table** under the key `telegram_bot_token`, *not* in application source, ZIP, documentation or SQL migration. This means anybody with unprotected database read access can see it. The web app must use DB privileges, TLS, restricted backup access and encrypted backups appropriate to that threat. No full token is ever rendered back into the HTML form; the input is empty with a Saved/Not configured indicator. A blank replacement keeps the saved token. Token removal requires a separate opt-in plus confirmation and global delivery disabled. Telegram API URL contains the token and is never logged. SQL patch contains only an empty default token.

Admin settings use POST + CSRF and strict server-side validation. Chat IDs are strings supporting positive/negative numeric IDs and `@channelname`, one per line, up to 10 distinct IDs; blank lines and duplicates are normalized, invalid entries rejected. The Test action sends independently to **all** saved destinations and reports successes/failures for each while global delivery is disabled; Test Message is POST + CSRF, does **not** create a fake incident, modify monitor state or write an incident notification log. The live Telegram API response, not the token's shape, establishes delivery success.

Per-monitor `telegram_notifications_enabled` defaults to 1 (new and migrated rows). A checkbox in Add/Edit controls it. Global enabled, both credentials, monitor enabled, event-specific global switch, and a real `down`/`recovered` transition must all hold before a notification is queued.

## 6. Monitoring, incident and notification flow

1. The CLI-only `/cron/monitor.php` acquires the existing OS `flock()` lock in `storage/cron-monitor.lock` and selects a bounded batch of active due monitors. Default cron schedule is every minute. Each monitor's interval still determines whether a check is due.
2. The shared `HttpMonitor::check()` performs configured cURL retry/timeout/status checking; the manual Test Now remains diagnostic and never creates incidents or Telegram events.
3. `CheckRepository::saveScheduled()` starts a transaction, locks the monitor row, stores the scheduled check/snapshot, and calls `IncidentManager::apply()`.
4. Only when the state service returns `transition=down` or `transition=recovered` does `NotificationManager::enqueue()` consider writing one notification record **for each configured Chat ID**. It runs **before the same transaction commits**, preventing committed incident state with a lost event if the process stops between saving an incident and queuing it. The enqueue error is caught so Telegram setup issues cannot roll back scheduled incident state under ordinary database error handling.
5. After monitor checks, `CronRunner` invokes `NotificationManager::deliverDue(10)`. This runs even with zero due monitors. A failed Telegram request does not cancel a check, interrupt later monitors, reopen an incident or block the Cron heartbeat.
6. `NotificationManager` claims pending or expired processing rows using a conditional `UPDATE`, increments attempt count and sets a 5-minute processing lease. This is also compatible with a future separate worker but not required now.
7. `TelegramNotifier::send()` performs HTTPS POST to Telegram Bot API `/sendMessage` using PHP cURL with redirect following disabled, TLS certificate and hostname verification enabled, short timeouts and bounded JSON response buffering. Dynamic HTML is escaped with `htmlspecialchars` before using `parse_mode=HTML`; messages are truncated to avoid the provider's length limit.
8. A successful provider response marks the notification sent, records provider message ID and UTC sent time. Failed attempts store a short redacted error, numeric error code if present, and a next-attempt timestamp. No entire response body or secret URL is stored.

**Event rules:** HTTP `retry_count=2` means up to 3 cURL attempts **within one scheduled check**. The `failure_threshold` (default 2) requires two *failed scheduled checks* before a monitor is confirmed Down. First failed check -> pending confirmation -> no Telegram event; confirmed Down -> one queued Down event per destination; continued failures -> same Incident and no additional Down event; one successful **scheduled** check -> closed Incident and one queued Recovery event per destination; repeated Up checks -> no additional event. Paused monitors do not run scheduled checks. A manual Test Now does not change incidents or Telegram delivery.

**Times:** All stored check, incident and notification timestamps are UTC. User-visible Telegram times convert to the app's configured timezone and display a timezone abbreviation; admin UI uses existing `display_date()`. Down uses original first failure as `started_at` and confirmation as `detected_at`. Recovery uses `recovered_at`, `duration_seconds`, the recovery check's HTTP status and response time.

### Down message example

```text
🔴 Main Website is DOWN
URL: https://example.com
Started: Oct 31, 2026 02:15 PDT
Detected: Oct 31, 2026 02:20 PDT
Reason: Request Timeout
Attempts: 3
[configurable app_name]
```

### Recovery message example

```text
🟢 Main Website is back online
URL: https://example.com
Recovered: Oct 31, 2026 02:42 PDT
Downtime: 27 minutes
Last response: HTTP 200
Response time: 184 ms
[configurable app_name]
```

These are examples, not actual monitoring measurements or user credentials. Private monitors may send URLs to the administrator-configured Telegram group, but their data is never placed on the public status page. The current implementation does not add public status links to private messages.

## 7. Delivery retries, idempotency and limits

- `channel='telegram'`, `event_type='down'|'recovered'`, `incident_id`, and `destination_chat_id` identify each event and destination. A unique DB key avoids duplicate logical sends per destination on repeated Cron processing. Each destination has independent status, attempts, retry scheduling, provider message ID and last error. Removed destinations no longer receive queued deliveries; their pending rows become terminal failures.
- Newly queued records have `status='pending'`, `attempt_count=0`, `next_attempt_at=UTC_TIMESTAMP()`.
- A conditional claim changes to `processing`, increments attempts, and sets a **5-minute lease**. Two simultaneous workers will not normally claim the same row because the WHERE condition and update are atomic.
- Failure on attempt 1 -> pending for **1 minute**. Failure on attempt 2 -> pending for **5 minutes**. Failure on attempt 3 -> terminal `failed`, no further automatic retry. HTTP 400/401/403/404 are terminal immediately. Stale exhausted processing rows become failed after their lease expires.
- Pending deliveries can be skipped while global/event-specific delivery is disabled, and are considered again if settings are re-enabled. A per-monitor disabled flag prevents sending an existing pending alert.
- Provider accepted but PHP crashed before marking `sent` is inherently ambiguous. **Telegram Bot API does not guarantee exactly-once delivery,** so a duplicate actual message is possible on recovery of the processing lease. This release offers logical idempotency and crash recovery, not a mathematical exactly-once guarantee.
- No new daemon/queue is needed; the one-minute server Cron drains a maximum of ten due notification records per run.
- `notification_logs` statuses: `pending`, `processing`, `sent`, `failed`. The UI groups `processing` with Pending in filters and displays the processing label on individual records.
- There is no manual retry button in Stage 05. Terminal errors remain visible to administrators in Notifications history.

## 8. UI, white-label and safety details

Notifications page has a multiline Chat IDs input and per-destination delivery history, using the same panels, inputs, pills, tables and responsive layout as other admin pages; its CSS lives in `public/assets/css/app.css`. Status page is unchanged and never includes Telegram token, Chat ID, errors or logs. Incident details read associated delivery outcomes via `NotificationRepository::forIncident()`. System Health shows configured destination count, token presence and last-24-hour failure count.

Admin session checks, CSRF on every settings/Test POST, output escaping, validation and folder routes remain. Token-bearing URLs and response bodies are never logged. Server-side `Settings::get()` contains the raw token only for backend delivery and never passes it to a template. Errors are reduced to safe summaries. Bot API TLS verification is mandatory and cURL redirects are not followed. No shell command executes a user-supplied URL.

## 9. Historical Stage 05 upgrade: deployed v0.0.4 to v0.0.5

1. **Back up files and MySQL database.** Pause your existing Plesk/cPanel Cron task during the upgrade.
2. Extract `monitor-patch-v0.0.5.zip` over the existing installation's **actual Document Root**. Overwrite changed files, but do not replace server runtime files. The ZIP contains no redundant wrapper directory, has file modes 644 and directory modes 755.
3. Run `monitor-sql-patch-v0.0.5.sql` **once** on the installed v0.0.4 database using phpMyAdmin. This is an incremental SQL update, not the fresh installer schema.
4. Resume the same server cron task (recommended every minute). **Do not rerun `/install/`.** Existing administrator users, monitor config, incidents, check history, branding, uploaded images and site settings are preserved.
5. Open `/admin/notifications/`; enter Telegram Bot Token and Chat ID and Save. Send Test Message using the saved credentials. Then enable Telegram globally and leave per-monitor checkbox enabled for desired monitors.
6. On a controlled staging endpoint, confirm exactly one logical Down event after the failure threshold, no repeated Down alerts during continued failures, then one Recovery event. Review Delivery History and `/admin/system/`.
7. Confirm `/admin/`, `/admin/monitors/`, `/admin/incidents/`, `/status/` and the installed version `v0.0.5`.

For a fresh deployment, extract `monitor-full-v0.0.5.zip`, open `/install/`, supply new database credentials and administrator details. The installer loads the complete Stage 05 schema. Do **not** run incremental SQL patches after a fresh installation. Telegram is optional and disabled by default.

### Cron configuration examples

```bash
# Plesk example, where the subdomain's Document Root is the project root:
/opt/plesk/php/8.2/bin/php /var/www/vhosts/monitoring.example.com/httpdocs/cron/monitor.php

# cPanel example:
/usr/local/bin/php /home/USERNAME/public_html/monitor/cron/monitor.php
```

Set a Scheduled Task **every minute** if supported. These PHP binary and project paths are examples, not verified for the user's server. Do not include an extra `/monitor/` segment if Monitor is installed directly in the subdomain's Document Root. Keep Cron CLI-only.

## 10. Code-level verification vs live hosting

Code and local deterministic tests in this build cover:

- `php -l` syntax for all PHP files;
- inherited `php tests/smoke.php` and `php tests/stage04.php` suites;
- new `php tests/stage05.php` tests for event gating, no duplicate transitions, URL/message escaping, timezone conversion, token shape, settings UI, schema constraints, retry strategy, transaction ordering, CSRF and SSL safety;
- ZIP file modes, relative paths, forbidden sensitive artifacts and archive integrity.

**Live verification still required:** This generation environment does **not** provide the `pdo_mysql` or `curl` PHP extensions, a live MySQL server, a Telegram Bot Token/Chat ID or an accessible deployed Cron. End-to-end SQL migration, actual Telegram delivery and recovery, network error handling, deployment permissions and concurrent Cron behavior must be tested on Plesk. Offline test success is not proof of live notification delivery.

## 11. Known limitations and operational cautions

- Token is plaintext in the protected DB, not encrypted at the application layer. Protect DB read access and backups accordingly.
- Actual exactly-once Telegram delivery cannot be guaranteed in crash-after-send scenarios.
- An existing active incident before installing Stage 05 **does not** generate a retroactive Down event; only future real transitions enqueue notifications. A future recovery of that incident can enqueue a Recovery event if eligible then.
- Disabling Telegram or per-event delivery can leave an existing queued record pending until re-enabled. Per-monitor disabled flags similarly suppress pending deliveries.
- Outbound HTTPS from the host must permit reaching `api.telegram.org` with trusted CA certificates. Token and Chat ID validity must be verified by Send Test Message.
- No separate escalation, custom text templates, test-message history, UI manual retry, or analytics beyond safe delivery logs.
- Failure to apply Stage 05 SQL before deploying the PHP files may cause notification pages to fail; keep Cron paused until patch and database migration are complete.
- Persistent configuration and uploads must be retained on upgrade. Do not upload FULL ZIP over a live install in place of a patch unless you understand file ownership and environment-specific assets.
- PHP errors are summarized, not displayed raw in the normal UI. Investigate failed delivery logs and Plesk PHP error log for incidents without leaking token-bearing URLs.

## 12. Future Super Admin compatibility

The existing `users.role` column and admin authentication are retained. Stage 05 creates no user or role management UI and does not assume the installed admin will always be the only user. Future role controls can restrict notification/token management without refactoring the notification service.

## 13. Completed stages and Stage 06 recommendation

- Stage 01 / v0.0.1: installer, authentication, branding and public shell.
- Stage 02 / v0.0.2: monitors CRUD, reusable HTTP tester, physical folder routing.
- Stage 03 / v0.0.3: due checks, Cron locking, results, retention foundation, history and heartbeat.
- Stage 04 / v0.0.4: Down confirmation, one incident per outage, recovery, public confirmed status.
- Stage 05 / v0.0.5: Telegram settings, event outbox, provider delivery, retry/logging and admin UI.

**Stage 06: Uptime & Response-Time Analytics, 24h/7d/30d data, aggregation and raw-check retention.** Preserve actual checks/incidents/notification data and avoid fake metrics. Base Stage 06 on the accepted `monitor-full-v0.0.5.1.zip` and this precise FULL MD, not any older source.


## 14. v0.0.5.1 multi-destination hotfix (CURRENT)

**Baseline:** Exact released `monitor-full-v0.0.5.zip` and `MONITOR-FULL-PROJECT-v0.0.5.md`. This is a cumulative hotfix of Stage 05 and does not implement Stage 06. The new definitive source is `monitor-full-v0.0.5.1.zip` plus this document.

**Input and storage:** `/admin/notifications/` accepts up to **10 unique Chat IDs**, one per line in its textarea. Numeric user IDs, negative group IDs and `@channelname` targets are supported. Whitespace and repeated IDs are normalized. Bad targets or more than 10 destinations reject the form. The original single saved `settings.telegram_chat_id` value remains valid without manual conversion; the same key now stores newline-separated IDs (UTF-8 TEXT). The Bot Token stays in `settings.telegram_bot_token` as a protected plaintext secret, never printed in HTML or bundled. The app's English-only, white-label behavior and physical folder URLs are unchanged.

**Database:** Fresh `database/schema.sql` includes `notification_logs.destination_chat_id VARCHAR(128) NOT NULL DEFAULT ''`. Unique identity is now `(channel, incident_id, event_type, destination_chat_id)`, replacing the old unique `(channel, incident_id, event_type)`. There is no separate destinations table, since the existing protected settings text is sufficient. Incremental `database/patches/v0.0.5.1.sql` preserves existing monitor, incident and notification rows, adds the column, backfills older rows from the currently saved single Chat ID, replaces the unique index and updates `system_meta.installed_version` to `v0.0.5.1`. The historical destination backfill is best-effort: if the destination was changed before migration, the stored old Chat ID may not reflect the original historical recipient. No message is recreated for previous incidents when new destinations are subsequently added.

**Notification pipeline:** `TelegramDestinations::parse()` validates, normalizes and deduplicates. `NotificationManager::eligible()` requires at least one destination. Inside the Stage 04 transaction, `NotificationManager::enqueue()` inserts an outbox row per configured target for `down` or `recovered` transitions; `ON DUPLICATE KEY UPDATE id=id` preserves idempotency per target. `NotificationManager::deliverDue(10)` claims each due row with its existing 5-minute lease; it calls `TelegramNotifier::send()` with **that record's destination_chat_id**, never the whole multiline string. Status (`pending`, `processing`, `sent`, `failed`), max 3 attempts, retry at 1 then 5 minutes, and provider message/error details remain **per row**. A failed target does not stop other rows. When a target is removed before a queued notification sends, that row becomes failed with `destination_removed`, and is not sent elsewhere. No SMS, email, new public data or non-transition messages are added. Existing manual Test Now stays diagnostic only.

**Test Telegram:** The POST + CSRF Send Test Message action attempts each saved target independently and reports a total and failures per Chat ID in admin. It does not create an incident or notification log record. A host can time out on many unreachable destinations: tests and cron HTTP delivery still rely on the existing 9-second per-request cap, and the cron sender processes up to 10 destination rows per invocation. Actual throughput depends on the host.

**UI:** Delivery history and incident detail now show the destination next to the individual result. The System Health panel shows the destination count. There is no public display of recipients, credentials or delivery history. Plesk cron remains the same one-minute CLI task; no additional cron job or reinstall is needed.

**Upgrade from currently installed v0.0.5:**

1. Back up current files and database and temporarily pause the existing cron task.
2. Extract `monitor-patch-v0.0.5.1.zip` in the current domain Document Root, overwriting changed files. No new top-level wrapper folder or installation rerun.
3. Run `monitor-sql-patch-v0.0.5.1.sql` **once** against the existing v0.0.5 database (it uses MySQL/MariaDB `ALTER TABLE`; do not rerun it).
4. Resume the existing one-minute cron task. Open `/admin/notifications/`; confirm the original saved Chat ID is present; add one new Chat ID per line, save and send a test message.
5. Confirm System Health shows the destination count, then trigger a safe staging outage/recovery to inspect separate delivery history rows. Existing incident logs and settings should remain.

**Fresh installation:** Extract `monitor-full-v0.0.5.1.zip` on a new host and use `/install/`. It creates the complete schema including multi-destination support. Existing installations **must not** be reinstalled.

**Tested locally:** PHP lint; existing `tests/smoke.php`, `tests/stage04.php`, `tests/stage05.php`; new `tests/multichat.php` for parsing, normalization, schema identities, per-target enqueue/delivery structure, secure POST, and UI. ZIP consistency and 644/755 modes were checked during packaging. **Not tested live:** MySQL migration and transactional records, cURL Telegram deliveries to real Chat IDs, concurrent cron on Plesk. Runtime verification is still required; offline source checks do not prove live delivery. Exactly-once external Telegram delivery is still not strictly guaranteed if PHP crashes between API acknowledgement and marking its DB row sent.

**Next Stage 06:** Build analytics on `v0.0.5.1`, keeping per-destination notification history and not regressing any Stage 01-05 features.

## Current source-of-truth note

The current release is **v0.0.5.2**, hotfix to v0.0.5.1. Historical references to older versions in this FULL MD describe their original release and migration history, not the currently deployed version. Future Stage 06 development must start from `monitor-full-v0.0.5.2.zip` and this updated FULL MD.
