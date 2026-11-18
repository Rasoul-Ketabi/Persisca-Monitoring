# Monitor | Full Project Reference v0.0.6

## 1. Authority and version

**Version:** `v0.0.6`  
**Stage:** 06, Uptime Analytics, Response-Time Analytics, Hourly Aggregation, Dashboard Metrics and Retention  
**Parent baseline:** actual hotfix `monitor-full-v0.0.5.2.zip` and `MONITOR-FULL-PROJECT-v0.0.5.2.md`, not the older Stage 05 filename in the Stage 06 planning prompt.  
**Released as:** `monitor-patch-v0.0.6.zip`, `monitor-full-v0.0.6.zip`, `MONITOR-FULL-PROJECT-v0.0.6.md`, `monitor-sql-patch-v0.0.6.sql`.

This FULL ZIP and this FULL MD become the next authoritative source after deployment and acceptance. Every earlier working change, including the Notification page `filter` fix in v0.0.5.2 and Telegram multiple destinations v0.0.5.1, is preserved.

## 2. Product purpose and stack

Monitor is a shared-hosting-friendly, self-hosted HTTP/HTTPS website and endpoint monitor with a browser installer, a white-label admin, cron-driven checks, confirmed incidents, recovery detection, multi-recipient Telegram alerting, status page, and admin analytics. Stack: PHP 8.1+, native PDO MySQL/MariaDB, PHP cURL, HTML/CSS/vanilla JS, no Composer/Node/queue server/root services. English-only UI and notification copy. White-label name, logo, metadata, Open Graph and timezone remain editable in admin settings.

Configuration and installed lock are generated on the *host*, not in distribution ZIP. Database credentials are in `config/database.php`; `storage/installed.lock` prevents reinstalls. Store timestamps in UTC in MySQL; user-facing formats use configured timezone. Normal setup is a domain document root with the whole archive extracted without a wrapper folder.

## 3. Actual source file tree (FULL ZIP)

```text
  .htaccess
  MONITOR-FULL-PROJECT-v0.0.3.md
  MONITOR-FULL-PROJECT-v0.0.4.md
  MONITOR-FULL-PROJECT-v0.0.5.1.md
  MONITOR-FULL-PROJECT-v0.0.5.2.md
  MONITOR-FULL-PROJECT-v0.0.5.md
  MONITOR-FULL-PROJECT-v0.0.6.md
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
  admin/monitors/analytics/index.php
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
  app/Services/HourlyAggregator.php
  app/Services/HttpMonitor.php
  app/Services/IncidentManager.php
  app/Services/MonitorAnalytics.php
  app/Services/MonitorInput.php
  app/Services/NotificationManager.php
  app/Services/RetentionManager.php
  app/Services/TelegramDestinations.php
  app/Services/TelegramNotifier.php
  app/Services/Upload.php
  app/Views/admin/branding.php
  app/Views/admin/dashboard.php
  app/Views/admin/general.php
  app/Views/admin/incident-detail.php
  app/Views/admin/incidents.php
  app/Views/admin/monitor-analytics.php
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
  database/patches/v0.0.5.1.sql
  database/patches/v0.0.5.2.sql
  database/patches/v0.0.5.sql
  database/patches/v0.0.6.sql
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
```

Directories `.htaccess` protect app source, configuration, database, cron scripts, storage and test scripts. Script entry points physically reside in folder-style routes; no visible `?page=` router is required. User-uploaded branding is placed under `public/assets/uploads/`, excluded from distributable source.

## 4. Database: exact current structures

The authoritative fresh-install schema is **`database/schema.sql`**, retained in the FULL ZIP. Previous schema, authentication and notification tables are preserved:

- **`users`**: id, name, email, password_hash, role, status, last_login_at, created_at, updated_at. The role field is retained for future Super Admin functionality, with no new user-management UI in this stage.
- **`settings`**: setting_key (primary), setting_value, updated_at. General, branding, monitoring and notification preferences. `telegram_bot_token` remains sensitive DB data, never bundled or echoed into saved HTML.
- **`system_meta`**: meta_key, meta_value, updated_at. Installed version, cron heartbeat, and aggregation/maintenance progress.
- **`login_attempts`**: id, email_hash, ip_hash, attempted_at, indexed by hash/time.
- **`monitors`**: name/URL, active/public/private-network switches, interval/timeout/expected statuses/retries, failure_threshold, Telegram per-monitor toggle, operational_status, consecutive failures, incident reference, last scheduled check and manual diagnostic fields, and next_check_at.
- **`monitor_checks`**: id, monitor_id, check_type, success, http_status, response_time_ms, error_type/message, attempt_count, incident_id, effective_url, checked_at, created_at. FK monitor ON DELETE CASCADE. Indexed by monitor/date, date, success/date, incident/date.
- **`incidents`**: id, monitor_id, status, started_at, detected_at, recovered_at, duration_seconds, opening/closing check IDs, failure_count and last error/HTTP metadata, timestamps. Monitor FK cascade; indexed by status/date and monitor/date.
- **`notification_logs`**: id, monitor_id, incident_id, channel, destination_chat_id, event_type, status, attempt_count, provider_message_id, error_code/message, next_attempt_at, sent_at, timestamps. FK cascades. UNIQUE `(channel,incident_id,event_type,destination_chat_id)` safeguards per-recipient Down/Recovery events.
- **`monitor_hourly_stats` (new)**: monitor_id BIGINT UNSIGNED, hour_start DATETIME, scheduled_check_count INT, successful_check_count INT, failed_check_count INT, response_sample_count INT, sum_response_time_ms BIGINT, min_response_time_ms INT NULL, max_response_time_ms INT NULL, first_check_at DATETIME, last_check_at DATETIME, updated_at DATETIME. Composite PRIMARY KEY `(monitor_id,hour_start)`, `idx_hourly_hour(hour_start)` and `fk_hourly_monitor` ON DELETE CASCADE. This table contains *check aggregates*; downtime is read from incidents and is not silently synthesized into aggregate rows.

Stage 06 SQL is **non-destructive** and creates only the hourly table, inserts an optional notification retention default and updates installed_version. It does not reset existing monitors, checks, Telegram destinations, incident history or accounts. Fresh v0.0.6 installer uses the complete updated `database/schema.sql`, not an incremental patch chain.

Existing source indexes are retained. Bounded analytics use the existing `monitor_checks(monitor_id,checked_at)` and `incidents(monitor_id,started_at)` indexes. Hourly analytics use the new primary key; no redundant overlapping indexes were added.

## 5. What uptime means here

**Primary Stage 06 displayed uptime is the observed scheduled-check success rate**, not a full continuous-time availability SLA. Formula:

`uptime_percentage = successful scheduled HTTP checks / all scheduled checks in selected period × 100`.

- Only persisted rows with `check_type='scheduled'` count. Manual **Test Now** remains diagnostic and does not affect availability or incidents.
- Failed requests, including unexpected HTTP statuses, count as failed checks even if a response time is measured.
- Never-checked, paused and cron-offline time is **unknown and excluded from the count**, not counted as 100% uptime. A 30-day window with two hours of monitoring is shown as *30-day selected window, 2 hours of samples*, with a disclaimer, not as proven 30-day full-time uptime.
- A period without checks returns **null** and UI text `Not enough data`, never invented 100% uptime.
- For privacy/accuracy, Stage 06 does not publish new public-uptime percentages. Stage 07 can add that once observation coverage semantics are designed.
- Monitoring starts at the first *real scheduled check*. The selected summary provides `first_observed_at` for that period.
- Pause durations and extended offline cron gaps from older releases cannot be reconstructed precisely from existing rows. This is why continuous incident-duration-based SLA is *not* falsely claimed. Historical gaps remain a documented limitation. Avoid reconstructing a timeline from deleted raw rows.

A separate factual **Confirmed Incident Downtime** value is calculated by intersecting `incidents` with the selected UTC interval. Open incidents are capped at current time. Intervals are clipped to the window and merged before summing, preventing double counts even if erroneous overlapping rows exist. The incident duration is deliberately **not** used as the denominator for the observed sample percentage. The UI explains the two concepts.

Periods (strict whitelist): `24h`, `7d`, `30d`; other inputs default to `24h`. Numeric percentage formatter has two decimal places except exact 100% displayed as `100%`.

## 6. Response-time analytics

`MonitorAnalytics::summary()` computes average, min and max from persisted **numeric response times** of scheduled HTTP checks within the period. Failed HTTP status checks with measured response times are included; NULL response times are excluded. The chart shows time-bucket averages only from real measurements, not fake lines. `response_label()` renders milliseconds under 1000 and seconds at/over 1000. Last measured response remains available from the monitor runtime snapshot.

The chart is locally rendered **SVG** with safe numeric coordinates and escaped attributes; no CDN, node build, or third-party JS is required. For 24h, SQL groups raw data into **15-minute** buckets, max 100 points. For 7d, it groups into hourly buckets; 30d into 6-hour buckets, max 200. When historical rollup backfill has not caught up, it temporarily derives bounded grouped series from existing raw checks. When fewer than two buckets are available, the UI displays `Not enough response-time data to draw this chart yet.`

## 7. Hourly aggregation and cron flow

Service: `app/Services/HourlyAggregator.php::process(1500)` executes from the existing CLI-only `CronRunner`. It reads a **bounded ascending ID batch** of scheduled checks after `system_meta.hourly_aggregated_check_id`, combines rows by monitor and UTC hour, and increments `monitor_hourly_stats`. It writes both aggregated rows and the cursor within the **same MySQL transaction**, so a failed or interrupted batch is retried safely without double-counting. `hourly_last_aggregated_at` tracks successful work. During initial migration, old retained scheduled checks are backfilled incrementally over subsequent cron runs.

Cron execution sequence:

1. Acquire global filesystem `flock` and cron heartbeat.
2. Check due active monitors via the existing cURL/Retry service and persist results.
3. Apply confirmed Down/Recovery state transitions transactionally. Write notification outbox events for real transitions.
4. Deliver bounded pending Telegram notifications independently of monitoring state.
5. Run bounded hourly aggregation, independently caught/logged on failure.
6. Run bounded retention maintenance if due, independently caught/logged on failure.
7. Write completion heartbeat and release lock.

No second daemon or cron is needed; run the existing `/cron/monitor.php` as CLI every minute, or the shortest available shared-hosting interval. High check timeout/retries may cause a run longer than a minute; the existing global lock prevents overlap. The soft 50-second check guard remains. No incidents or alerts are generated from analytics.

**Summary query strategy:** 24h uses indexed SQL aggregates directly on raw checks. For 7d/30d, when the rollup cursor is caught up with scheduled IDs, full UTC hourly buckets come from the aggregate table and **partial boundary hours** are read from raw check rows to avoid rounding an incomplete hour into a full one. Until backfill catches up, metrics are computed from indexed raw rows. With raw retention shorter than 30 days, older *complete* hourly buckets remain available, although deleted partial boundary rows and precise historical pause coverage cannot be reconstructed.

## 8. Data retention and safety

Configurable settings:

| Setting key | Default | Allowed |
|---|---:|---:|
| `check_retention_days` | 90 | 7–3650 days |
| `notification_log_retention_days` | 90 | 7–3650 days |
| `cron_stale_after_minutes` | 10 | 2–1440 minutes |
| `cron_batch_size` | 25 | 1–100 |

`RetentionManager::runIfDue()` is called from cron. Maintenance is scheduled approximately **once per day** using `system_meta.last_retention_cleanup_at`. Deletes are limited to **1000 rows per table per pass**. If one table fills the batch, another scheduled cron run continues the backlog before setting the daily marker. `monitor_checks` deletion is gated by `id <= hourly_aggregated_check_id` and an age cutoff. A check that has not been rolled up is not eligible for deletion. This guarantees aggregate data is committed first. Old raw check rows lost in releases *before* Stage 06 cannot be backfilled.

Notification cleanup removes only sufficiently old **sent/failed records belonging to resolved incidents**. Active-outage delivery records and pending notifications are not removed, retaining normal per-destination idempotency. Incidents are never automatically deleted. Hourly aggregates are retained indefinitely for now (they cascade only if an administrator explicitly deletes the parent monitor). Monitor deletion itself already requires POST + CSRF with explicit confirmation and cascades check, incident, notification and hourly histories. A cleanup failure is caught and logged; scheduled checks and incident state continue.

## 9. Routes and UI

All routes remain physical folder-style entry points and require the admin session except `/status/` and `/install/` before installation. Record IDs in query strings are permitted only as resource IDs:

- `/admin/` dashboard, including real 24h fleet sample availability, mean measured response time and failed check count.
- `/admin/login/` and `/admin/logout/` secure auth flow.
- `/admin/monitors/` configuration and operational-state list.
- `/admin/monitors/add/` new monitor; `/admin/monitors/edit/?id=N` CRUD and Test Now.
- `/admin/monitors/view/?id=N` scheduled check history and incident detail, now linking to analytics.
- **`/admin/monitors/analytics/?id=N&range=24h|7d|30d`** authenticated analytics view with sample-success percent, HTTP timing, incident overlap, SVG chart and period comparison.
- `/admin/incidents/` and `/admin/incidents/view/?id=N` actual incidents and recovery history.
- `/admin/notifications/` unchanged multi-chat Telegram configuration, Send Test and filtered delivery log, including Stage 05.2 fix for missing filter and SQL ambiguous columns.
- `/admin/settings/general/` now includes *notification log retention* alongside existing monitoring options (POST + CSRF + server-side 7–3650 validation).
- `/admin/settings/branding/` branding, meta, OG images, favicon.
- `/admin/status-page/` status management shell; `/status/` actual public states for public monitors only, no new public metrics in this stage.
- `/admin/system/` now shows hourly aggregate table readiness, last aggregation time/cursor, check and notification retention days and last daily cleanup.

Admin chart uses the existing CSS design system in `public/assets/css/app.css`, with responsive single-column metric/card layout on narrow screens. Null metrics have explicit empty states.

## 10. Security model

Keep existing password hashing, login throttling, secure sessions and CSRF on writes. Authenticated analytics route validates monitor ID with the same numeric policy as other detail routes. Range is strictly whitelisted; no caller SQL snippets or arbitrary grouping are accepted. HTML/labels/SVG coordinates are escaped or produced from server-side numeric measurements. No raw exception traces, passwords, response bodies, Telegram tokens, or database credentials are exposed. Private monitor analytics do not appear on public pages. TLS verification remains enabled for HTTP checks and Telegram API. Notification log retention never deletes active incident deduplication records. Files are distributed with 0644 permissions and directories 0755, avoiding the prior ZIP permission regression.

Telegram: global enable/disable, bot token stored in DB settings, **up to 10 distinct Chat IDs / channel targets**, per-target idempotent outbox/delivery history, 3-attempt bounded retries, Down/Recovered alerts only on confirmed state transitions. Telegram `sendMessage` is not strictly exactly-once in a crash between provider acceptance and local sent-state persistence. No new notification logic or token exposure is introduced by analytics.

## 11. Upgrade on an installed v0.0.5.2 host (Plesk/cPanel)

1. **Back up** current files and MySQL/MariaDB, especially `config/database.php`, existing `/storage`, and uploads.
2. Temporarily pause the server cron during upgrade.
3. Extract **`monitor-patch-v0.0.6.zip`** over the application root / subdomain document root. No wrapper directory. Overwrite changed project source files, not host-generated config/storage.
4. Run **`monitor-sql-patch-v0.0.6.sql` ONCE** in the existing database (phpMyAdmin is fine). It is non-destructive and sets `system_meta.installed_version = v0.0.6`.
5. Resume the **same** CLI cron task, typically every minute. **Do not run `/install/` again.** Do not import `database/schema.sql` onto existing data. Do not rerun any Stage 05 migration.
6. Sign in and verify General Settings retention values, `/admin/system/` new table and cursor, `/admin/monitors/analytics/?id=1` for an existing monitor and Notifications page. See sample availability and response chart develop as the current and historical checks are aggregated.
7. Confirm old monitors, incidents, Telegram Bot Token and all Chat IDs are intact. Use Test Message only if desired; it is unrelated to the analytics migration.

Generic Plesk cron command for a subdomain installed at the document root (replace exact path and PHP binary):

```bash
/opt/plesk/php/8.2/bin/php /var/www/vhosts/DOMAIN/httpdocs/cron/monitor.php
```

Generic cPanel example:

```bash
/usr/local/bin/php /home/USERNAME/public_html/cron/monitor.php
```

Schedule: `* * * * *`. An individual monitor configured for 5 minutes is still checked only when due, even though the master cron runs each minute. On a 5-minute-only host, some checks run later than their ideal due timestamps.

## 12. Fresh installation v0.0.6

Extract `monitor-full-v0.0.6.zip` into the chosen web root, visit `/install/`, complete environment check/DB/admin/branding/timezone steps. Installer creates the current complete schema including `monitor_hourly_stats`. No incremental SQL patches are required on a fresh install. After installing, create monitors and configure CLI cron. Telegram is optional and disabled by default. `/install/` remains locked after the first successful install.

## 13. Verification and limitations

**Code-level checks completed:** all PHP source files pass `php -l`; existing test suites (`smoke.php`, `stage04.php`, `stage05.php`, `multichat.php`, `notification-page.php`) and new `stage06.php` pass after version expectations were updated. New tests cover actual sample arithmetic, empty states, range whitelisting, duration clipping and union, formatting, generated SQL/schema, aggregate cleanup guards, Cron integration, code-level safety, legacy features. ZIP structure and permissions are checked separately when packaging.

**Live-host verification still required:** PDO MySQL/MariaDB migration execution, historical backfill and large-data performance, cURL checks, hourly aggregation with live transactional MySQL, 24h/7d/30d query correctness against a real dataset, browser SVG rendering, exact timezone display and cron on Plesk. This build environment lacks PDO MySQL and cURL extensions and cannot honestly claim end-to-end integration testing. Before rollout, take backups and verify the SQL patch with the active hosting database.

**Known limitations:** sample-based uptime is not a proven time-weighted SLA. Paused/unobserved intervals from earlier versions are not reconstructible. Snapshot timestamps are UTC, dashboard recent-fleet summary is for the last rolling 24h (not local midnight today), 7d/30d charts rely on bounded hourly aggregation, and availability history cannot restore records previously deleted by earlier cleanup. Raw and delivery log retention is conservative and bounded. No public 30/90-day status bars yet. No custom PDF reports or external analytics dependency.

## 14. Completed stages and next recommendation

- v0.0.1: foundation, installer, white-label admin and public shell.
- v0.0.2: monitor CRUD, HTTP Test Now, folder-style routing.
- v0.0.3: Cron, scheduled checks and check history.
- v0.0.4: confirmed operational state, incident and recovery engine.
- v0.0.5: Telegram with delivery tracking.
- v0.0.5.1: multiple Telegram Chat IDs and per-target send state.
- v0.0.5.2: Notification page missing-filter / ambiguous SQL hotfix.
- **v0.0.6: observed uptime, response analytics, hourly aggregation, safe retention and System Health visibility.**

**Next:** Stage 07, Public Status Page Redesign and Historical Availability Experience. Reuse `MonitorAnalytics` as the server-side source of truthful per-monitor data, improve public incident timeline and 30-/90-day bar visualization while preserving strict public/private separation and white-label OG rules. Before displaying time-weighted 30/90-day uptime, add explicit observed-interval/paused-duration coverage or label the percentage as check-sample availability to avoid implying unobserved 100% uptime. Future role-restricted analytics/retention controls must preserve `users.role` and support a Super Admin management stage.
