# Configuration

Configuration is performed through the web administration UI. The installer writes `config/database.php` for DB access. Application settings reside in the database `settings` table.

## General and branding

- `/admin/settings/general/`: product name, Base URL, timezone and global settings.
- `/admin/settings/branding/`: logo, favicon, accent color and Open Graph image.
- `/admin/status-page/`: public global status visibility and history display options.
- `/admin/settings/monitoring/` may not have a standalone URL in this release; retention/monitoring controls are surfaced in the existing administrator settings/maintenance interface.

The software ships with neutral white-label defaults. You can use a different product name after installing.

## Monitoring

Create or edit checks at `/admin/monitors/`. Configure the URL, expected HTTP codes, interval, timeout, retries, failure threshold, whether the monitor is active, and public visibility. Scheduled monitoring is performed by PHP CLI Cron; **Test Now** is diagnostic only and must not create incidents or send alerts.

When a threshold is reached, the incident changes to DOWN. A subsequent scheduled success marks it RECOVERED. Logs and incidents are retained according to their respective settings.

## Telegram alerts

1. Create a Telegram bot using Telegram's official BotFather.
2. Under `/admin/notifications/`, save the Bot Token and one or more destinations. Global alerts support up to **10 unique Chat IDs**, one per line. Negative group IDs and supported `@channel` names are accepted.
3. Send a **Test Message** and confirm it arrives.
4. Enable global Telegram notifications and choose which incident transitions to send.
5. For portal-linked alerts, the Super Admin must click **Configure Webhook** using a valid HTTPS Base URL. The application calls `getMe` to find the bot username and registers the endpoint `/telegram/webhook/` using Telegram's secret token header.
6. A Portal User signs in and visits `/account/telegram/`, requests a one-time link (15-minute expiry), starts the bot in a private chat, and can enable or disable personal alerts.

**Admin alerts and personal alerts are independent.** Personal alerts only cover the linked owner's monitors, if the owner and the monitor permit notifications. Disabled users, disconnected identities and stale destinations must not receive personal messages.

Bot Token and webhook secret are stored in DB settings. They are not encrypted at rest by the application. Protect database backups. The UI does not show the stored full token. Telegram cannot guarantee exactly-once delivery after a process crash.

## Portal OTP email

Set a sender email address and sender name under `/admin/portal-users/` as Super Admin. The mailer uses the PHP `mail()` function with these settings. This project does **not** provide SMTP provider integration in v0.2.2. Ensure the hosting provider permits sending and that SPF/DKIM/DMARC align with your sender domain where appropriate.

Admin-created portal users log in at `/account/login/` with a **6-digit code valid for 10 minutes**. OTP requests are throttled; codes are hashed and consumed once. Unknown email addresses receive a generic response. Portal users do not have passwords.

## Portal quotas and ownership

Super Admin creates portal accounts and sets their monitor limits under `/admin/portal-users/`. A limit of **0 means no new monitors**, not unlimited. Lowering the limit does not delete existing monitors. Portal views and operations are scoped to the signed-in owner. The global public page uses platform-owned public monitors only.

Each Portal User may optionally publish `/status/{slug}/`. The slug is controlled by the admin; the Portal User may enable/disable its page and set public visibility of owned monitors. Existing migrated portal pages default to disabled. Only owned public monitors and their incidents are included.

## Retention and health

Check `/admin/system/` and `/admin/maintenance/` for Cron heartbeat, queue state, storage status and cleanup diagnostics. Defaults include **90-day** raw check and notification-log retention. Hourly aggregate statistics and incident history are preserved according to their separate behavior. Use only the bounded maintenance actions provided in the admin UI.
