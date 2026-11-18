# Monitor | FULL Project Reference v0.0.7

## Authority and deployment

**Version:** `v0.0.7`  
**Stage:** 07, Public Status Page, 30-Day History, Incident Display and Dynamic Open Graph  
**Authoritative parent:** actual accepted `monitor-full-v0.0.6.zip` and `MONITOR-FULL-PROJECT-v0.0.6.md` (itself based on Stage 05 hotfix `v0.0.5.2`, including multi-destination Telegram and Notifications page fixes).  
**Stage outputs:** `monitor-patch-v0.0.7.zip`, `monitor-full-v0.0.7.zip`, `MONITOR-FULL-PROJECT-v0.0.7.md`, `monitor-sql-patch-v0.0.7.sql`.

**Stage 07 is the source of truth after successful deployment and acceptance.** Do not reconstruct Stage 08 from a prior version. The current source includes all existing Stages 01–06 features and fixes. No reinstall is needed for upgrades.

## Project purpose and runtime

Monitor is a self-hosted HTTP/HTTPS availability monitor suitable for cPanel, Plesk, Apache, LiteSpeed, PHP-FPM, MySQL and MariaDB. Requirements: PHP 8.1+, PDO MySQL, cURL, filesystem storage and standard PHP sessions. There is no Laravel, Node, Composer, Redis, WebSockets, external worker, root process or queue daemon. Admin, installer and public UI remain **English only** and fully **white label**. Do not insert developer branding.

The six-step browser installer creates `config/database.php`, `storage/installed.lock`, admin identity, schema and default settings. These installation-specific files, credentials, sessions, runtime logs and uploaded branding assets are **never distributed** inside the FULL ZIP. The installer stays locked after setup. Password verification, login throttling, CSRF, safe sessions, input escaping, role column, prepared SQL and restricted upload storage remain intact. The `users.role` column stays available for future Super Admin management.

The application expects its files extracted directly into the document root or an installation folder. `/status/` and all `/admin/.../` paths have real folder entry points. Routing does not depend on visible `?page=` parameters. Query strings are used only for individual resource IDs and whitelisted analytics display ranges.

## Complete current source tree

```text
  .htaccess
  MONITOR-FULL-PROJECT-v0.0.3.md
  MONITOR-FULL-PROJECT-v0.0.4.md
  MONITOR-FULL-PROJECT-v0.0.5.1.md
  MONITOR-FULL-PROJECT-v0.0.5.2.md
  MONITOR-FULL-PROJECT-v0.0.5.md
  MONITOR-FULL-PROJECT-v0.0.6.md
  MONITOR-FULL-PROJECT-v0.0.7.md
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
  app/Services/PublicStatus.php
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
  app/Views/admin/status-page.php
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
  database/patches/v0.0.7.sql
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
  storage/logs/monitor-cron.log
  tests/.htaccess
  tests/multichat.php
  tests/notification-page.php
  tests/smoke.php
  tests/stage04.php
  tests/stage05.php
  tests/stage06.php
  tests/stage07-render.php
  tests/stage07.php
```

Protected `.htaccess` rules remain in source, config, cron, database, storage and tests. `public/assets/uploads` only contains safe static uploads on the deployed host. The included neutral `public/assets/images/status-og-default.jpg` is a fallback image. All source files in ZIP are 0644 and directories 0755.

## Database tables

Authoritative fresh-install schema: `database/schema.sql`. Tables and significant fields:

1. `users`: id, name, email, password_hash, role, status, last_login_at, created_at, updated_at; unique email. Future Super Admin capability is not implemented yet.
2. `login_attempts`: hashed email/IP and timestamp with time indexes; login rate limiting.
3. `settings`: primary setting_key, setting_value, updated_at. Application, branding, Telegram, retention and now status-page options. `telegram_bot_token` is sensitive database data and is never output to saved HTML.
4. `system_meta`: key/value/timestamp. Installed version, cron heartbeat, aggregation cursor and cleanup progress.
5. `monitors`: id, name, URL, active/public/private-network switches, check interval, timeout, expected HTTP codes, retry count/delay, failure threshold, per-monitor Telegram toggle, operational status, consecutive failures, failure sequence start, current incident pointer, last success/failure, last scheduled check and response snapshot, next check, last manual diagnostics, timestamps. State is initially `unknown`.
6. `monitor_checks`: id, monitor_id, check_type, success, HTTP status, response_time_ms, classified error, attempt_count, effective URL, checked_at, incident_id and created_at. Monitor FK on delete cascade, indexed monitor/date and incident/date.
7. `incidents`: monitor_id, status (`open`/`resolved`), started_at, detected_at, recovered_at, duration_seconds, opening/closing check IDs, failure_count and last failure classification; monitor FK cascade and indexes.
8. `notification_logs`: monitor_id, incident_id, channel, **destination_chat_id**, event_type, delivery status, attempt_count, Telegram provider message ID, safe error info, next_attempt_at, sent_at and timestamps. Unique `(channel,incident_id,event_type,destination_chat_id)` prevents normal duplicate events per recipient. FKs cascade. Supports up to 10 Telegram chat destinations.
9. `monitor_hourly_stats`: primary `(monitor_id,hour_start)` with counts of successful/failed scheduled checks, response sample count/sum/min/max, first/last checks and updated timestamp. Monitor FK cascade; hourly aggregates remain after raw check cleanup.

**Stage 07 changes no table columns.** The SQL migration inserts five new status settings only if absent and updates the installed-version metadata. It does not change existing monitor data or Telegram destinations and includes no destructive reset. A fresh v0.0.7 browser install seeds the new defaults via `Settings::defaults()`; `database/schema.sql` retains the complete current schema. The installer must not be used to upgrade an existing site.

### New settings keys

| Key | Default | Meaning |
|---|---|---|
| `status_page_enabled` | `1` | Publish `/status/`. If disabled, return HTTP 404 and generic unavailable content. |
| `status_show_uptime` | `1` | Show 30-day daily bars and sampled availability summary. |
| `status_show_response_time` | `1` | Show latest measured scheduled HTTP response time. |
| `status_show_incidents` | `1` | Show public active and resolved incidents. |
| `status_show_footer` | `1` | Display configured company and footer text. |

The admin route `/admin/status-page/` saves these via POST + CSRF, with all values constrained to binary `1`/`0`. Current design fixes history to 30 days, so there is no arbitrary history-range SQL setting.

## Stage 07 public status architecture

`public/index.php` bootstraps, checks installation, reads settings, calls `PublicStatus::snapshot()` when publishing is enabled, constructs dynamic metadata, and renders `app/Views/public/status.php`. Physical folder route `/status/index.php` includes `public/index.php`; `/status/` is the **only preferred public canonical URL**. The compatibility root `index.php` may load the same public controller but canonical and OG URL remain `/status/`.

`app/Services/PublicStatus.php` owns public database reads and state mapping. It deliberately queries *only* public monitors: `SELECT id,name,is_active,operational_status,last_checked_at,last_response_time_ms,current_incident_id FROM monitors WHERE is_public=1`. Active and resolved incident queries explicitly join the parent monitor and include `m.is_public=1`; making a monitor private hides its entire incident history instantly. Neither monitor URLs, private IDs, low-level errors, Telegram destinations, token information, logs, admin URLs nor filesystem/server details are returned to public templates. There is no public JSON endpoint in this stage.

`PublicStatus::overall()` returns an internal state `none`, `pending`, `operational`, `issues` or `major`, displayed as:

- `none`: No Public Monitors, when the public monitor list is empty.
- `pending`: Status Pending when no confirmed status exists, all monitors paused, or some active services unknown/paused (without a confirmed active outage). Never show false all-green status.
- `operational`: All Systems Operational when every public monitor is active and confirmed Up, and no active public incident exists.
- `issues`: Some Systems Experiencing Issues when at least one public monitor is confirmed down or has a still-open public incident, but not all actively monitored public services are down.
- `major`: Major Service Disruption when **all active public monitors** are confirmed down.

A paused monitor renders `Monitoring Paused`; an unknown active monitor renders `Status Pending`; confirmed Up shows `Operational`; confirmed Down shows `Outage`. A paused monitor with an open incident does not silently count as healthy: the still-open public incident appears and affects the overall status. Active incident counts are limited to 30 public records on initial page; resolved history shows the 12 newest public resolved incidents recovered within 30 days. Neither active nor resolved public cards display raw error messages. Active copy: `Availability issue under investigation.` and status `Investigating`. Resolved cards use actual stored start/recovery timestamps and duration, rendered using the existing `incident_duration()` helper.

The latest scheduled check displayed at top is the maximum `last_checked_at` of **public** monitor snapshots, not the page-load time. There is **no WebSocket or automatic refresh**; visitors can refresh. HTTP public responses have `Cache-Control: public, max-age=30`, so changes may be delayed up to ~30 seconds; this is documented. No cron, incident or Telegram modification is performed by page reads.

## 30-day history calculations

The 30 vertical bars represent the **30 local calendar days ending today** in `settings.timezone`. `PublicStatus::days()` calculates local midnight boundaries using PHP `DateTimeZone` (DST-aware), not naive UTC midnight. Each day initially has no observations. The public history service combines:

1. Durable `monitor_hourly_stats` counts for UTC-hourly time buckets.
2. Unaggregated raw scheduled-check rows **strictly after** `system_meta.hourly_aggregated_check_id`. This prevents counting a sample twice while the hourly backfill is catching up.
3. A separate confirmed-incident query to calculate each day's overlap with real outage intervals. Open incidents extend only to now; overlapping incident intervals are merged by `MonitorAnalytics::unionSeconds`, preventing double counting. Each day's incident_count uses the incident start date in application timezone.

Daily states:

| State | Condition | Rendering |
|---|---|---|
| `operational` | Observed scheduled checks exist, all successful and no confirmed incident overlaps | Green bar |
| `partial` | Mixed successful and failed observed checks, or confirmed incident overlaps a day with otherwise successful/no checks | Amber bar |
| `outage` | Observed checks exist and all failed | Red bar |
| `no_data` | No scheduled checks and no confirmed incident overlap | Grey bar |
| `paused` | Currently paused and no checks on the current local date | Grey paused bar |

The bar's accessible `aria-label`, HTML `title`, and focus/tap tooltip include local date, successful/total observed checks, and factual incident duration if present. It does **not** assume that an unobserved hour/day was operational. Keyboard focus is enabled with `tabindex=0`. On narrow mobile screens a focused bar can show a fixed tooltip rather than overflowing the screen. Key data is still available in semantic label text without JavaScript.

**Timezone caveat:** Historical aggregates use UTC-hour buckets. For most integral-hour timezone offsets (such as America/Vancouver), converting an hourly bucket start to a local date accurately assigns the whole bucket. In non-integral-hour timezones (such as Asia/Kolkata), a UTC-hour bucket can straddle local midnight. As the old raw data can be deleted, exact split of that historical hourly count between local dates cannot always be reconstructed; the affected day is marked `approximate` and the tooltip says `UTC hourly aggregation used`. No false precision is asserted for those boundary bars. Exact incident overlaps are still computed with local-day timestamp boundaries. Historical paused intervals are not stored, so days during old pauses remain No Data unless incident evidence exists.

The headline 30d observed success percentage comes from **the existing `MonitorAnalytics::summary($id,'30d')`**. Formula: successful scheduled checks divided by scheduled checks in the rolling 30x24-hour window. This is a *sample ratio*, **not** a continuous-availability SLA. The 30 daily bars cover 30 calendar days including today; their starting boundary can differ from the 30x24-hour rolling summary. The rendered label qualifies it as *Observed check success*, gives the number of checks and first observed check date, and returns `Not enough data` with no samples. No fake 100% uptime on the basis of two hours of checks. URL visibility defaults to hidden; no new public URL setting is implemented.

Latest response time comes from the monitor's last scheduled snapshot and is rendered via existing `response_label()`; NULL remains No Data. Manual Test Now never affects public status, incidents, or public percentages.

## Open Graph, SEO and branding

For an enabled status page `PublicStatus::metadata()` generates the `<title>`, `og:title`, `og:description`, standard description, and Twitter card text using actual overall state and current configurable `settings.app_name`. Its values are HTML-escaped. Branding's configured static `og_image_path` remains in effect and its absolute URL is used as `og:image` and `twitter:image`; if absent, a neutral fallback image is selected. Dynamic image generation is **not** implemented, avoiding server GD and on-host image-generation dependencies. Existing editable branding meta copy remains stored, though the public status title/description intentionally become status-aware when enabled. If disabled, generic configured metadata is used and the route returns 404.

Canonical link and `og:url` always use `absolute_url('status/')` based on `settings.base_url` where configured, never `public/index.php` nor `?page=`. The admin can configure logo, favicon, organization name, support URL and footer text, all of which are respected by the public page. No developer credits. Social platforms may cache the Open Graph preview and require rescraping. All source images must be publicly accessible to the crawler; configured base_url must be correct.

`public/assets/css/app.css` includes an isolated `.status-public-v7` stylesheet for the new public layout. It does not change the admin design tokens, sidebar or scripts, and remains responsive at 820px and 500px breakpoints. There is no inline JavaScript, remote font or CDN dependency. The responsive status layout includes a hero, public service cards, 30-day bar grids, incident cards, and an information note about the meaning of samples.

## Existing monitoring and incident behavior retained

- Each monitor has HTTP/HTTPS URL, optional private-network policy, check interval (typically 5 minutes), request timeout, expected response codes, retry_count (retries **after** first attempt) and retry delay. The service uses PHP cURL with SSL verification enabled. Manual Test Now uses POST + CSRF and is diagnostic only.
- The master cron `cron/monitor.php` is CLI-only, scheduled preferably every minute; the configured monitor interval controls when checks occur. `CronRunner` uses filesystem `flock`, heartbeat, a configurable bounded monitor batch and a soft runtime guard. Only due active monitors are checked.
- Scheduled results are persisted to `monitor_checks` and update the monitor's latest scheduled snapshot. One failed scheduled check counts toward consecutive failures after configured HTTP retries, not one failure per HTTP attempt. The default `failure_threshold=2` opens an incident after two failed scheduled checks, transaction-safely. `started_at` records first failed check; `detected_at` records confirmation. Continued failed checks update the same open incident; one successful scheduled check closes it and computes duration.
- Telegram delivery is independent of incident correctness. NotificationManager builds outbox records per incident transition, channel, event and destination; global + per-monitor enables are observed, up to 10 configured Chat IDs supported (one per line). The token is stored in `settings` but never echoed to HTML. Retries max three per event/recipient; exactly-once Telegram provider delivery is not guaranteed after a crash between API acceptance and DB update.
- HourlyAggregator incrementally rolls completed scheduled checks into hourly statistics with a transaction-protected ID cursor. The daily retention worker runs bounded cleanup, max 1000 records per pass, after aggregation is committed; raw checks and old resolved notification delivery logs default to 90 days. Incidents and hourly statistics are preserved indefinitely unless their monitor is explicitly deleted.
- `MonitorAnalytics` powers admin observed success percentages (24h/7d/30d), min/avg/max response duration, SVG chart, confirmed incident downtime and fleet dashboard. Stage 07 reuses it; it does not invent time-based SLA percentages or rewrite that service.

## Routes and UI

### Public routes

- `/status/`: enabled public status, public monitors only, active and 30-day resolved public incidents, 30 local day availability bars, dynamic metadata and white-label branding.
- `/`: compatibility status controller entrypoint; canonical/OG always `/status/`.
- `/public/index.php`: legacy fallback, **not** linked as canonical or shared route.

### Protected admin routes

- `/admin/`: dashboard with real monitor states, incidents, checks and Stage 06 analytics.
- `/admin/login/`, `/admin/logout/`: secure authentication and POST logout.
- `/admin/monitors/`, `/admin/monitors/add/`, `/admin/monitors/edit/?id=N`, `/admin/monitors/view/?id=N`, `/admin/monitors/analytics/?id=N&range=24h|7d|30d`.
- `/admin/incidents/`, `/admin/incidents/view/?id=N`: active/history/details.
- `/admin/notifications/`: per-destination Telegram config, test send, filtered delivery history; Stage 05.2 `filter` page fix retained.
- `/admin/settings/general/`: app, timezone, base URL, retention and cron preferences.
- `/admin/settings/branding/`: logo, favicon, OG image/meta and footer text.
- **`/admin/status-page/`: new real publishing settings page**, no longer a placeholder; save via POST + CSRF, link to public page, hide private data by query.
- `/admin/system/`: environment health, cron heartbeat, Telegram configuration health, analytics/retention status.

Only the admin can change settings. Public route exposes no login/session info. All new settings are validated server side and persisted without changes to the existing users/roles structure.

## Upgrade v0.0.6 to v0.0.7 on Plesk/cPanel

1. Back up **both** the database and all application files including server-generated `config/database.php`, storage locks, and branding uploads. Pause cron briefly while upgrading.
2. Extract **`monitor-patch-v0.0.7.zip`** over the existing web Document Root. No wrapper directory. Overwrite modified source files. Do not overwrite host secrets and do not run the installer.
3. Run **`monitor-sql-patch-v0.0.7.sql` once** against the existing v0.0.6 DB in phpMyAdmin. It inserts five status keys without overwriting already configured values and advances installed_version to `v0.0.7`. There are **no new tables or columns**.
4. Resume the existing CLI cron (typically every minute). No changes to cron syntax, Telegram settings, bot token, Chat IDs, monitor settings or incident data.
5. Test `/status/`, `/admin/status-page/`, `/admin/notifications/` and `/admin/system/`, confirm old monitors and incidents are intact, and inspect a private monitor to ensure it never appears publicly. Check actual OG source title/image, mobile layout and local day bars.
6. If an old social preview persists, use the social network's official rescraping/debugger workflow; this is a cache effect, not necessarily a page failure.

Example Plesk task for an installation directly in the subdomain Document Root (substitute actual host path and PHP executable):

```bash
/opt/plesk/php/8.2/bin/php /var/www/vhosts/DOMAIN/httpdocs/cron/monitor.php
```

Recommended master schedule: `* * * * *`. This is independent of each monitor's 5-minute default. Never run the setup wizard again after an upgrade.

## Verification and limitations

**Code-level verification in build environment:** all PHP files linted with `php -l`; historical stage smoke/compatibility test scripts run; new Stage 07 state/privacy/daily/date/metadata checks and a rendered safe HTML fixture exercise unknown states, 30 bars, escaping, canonical OG tags and accessibility. Distribution archives are ZIP-tested with permissions 0644 (files)/0755 (dirs). No private configuration, token or customer-specific uploaded assets are included.

**Production verification still needed:** MySQL/MariaDB joins and 30-day queries on a real dataset, high-volume query cost, Plesk cron continuity, private/public transitions, real active incident history, actual brand image availability to crawlers, mobile device interactions and Open Graph social cache behavior. The build environment has no live MariaDB, external Telegram credentials or production cron. The Stage 07 application was not asserted to have been exercised against live production data here.

**Known limitations:** 30 days only; 12 latest resolved incidents, 30 active displayed; no public incident detail route, subscriptions, RSS or JSON API; no auto-refresh; static white-label OG image (title and description are dynamic); uptime is a sample success ratio rather than continuous-time SLA; historical pause time cannot be reconstructed; UTC-hour bucket assignment to fractional-offset local days may be approximate when detailed raw checks no longer exist; some public status responses may remain cached for up to 30 seconds. Private data restrictions are enforced by public SQL filters, not a link-hiding strategy.

## Recommended Stage 08

**Stage 08: Reliability, Maintenance, Admin Users, Super Admin Management and Production Operations**. Introduce Admin Users with appropriate roles and safeguarding the last Super Admin; secure password change and account activation; stronger cron diagnostics/staleness checks; operational cleanup and integrity checks; production safeguards. Do **not** rewrite working Monitoring, Incident, Telegram, Analytics or PublicStatus modules to achieve unrelated admin work. Preserve the source-of-truth upgrade chain.
