# Deployment Verification Checklist

Use a staging domain with test data before putting a real installation online. Check each item against your hosting environment. Code-level checks do not count as live verification.

## Before publishing

- [ ] Confirm legal rights to redistribute any logo, photo, font, or image bundled with the project.
- [ ] Ensure no real `config/database.php`, Telegram Bot Token, OTP, password hash, Chat ID, mail credential, personal data, database dump, upload or runtime log is in Git history.
- [ ] Confirm `LICENSE` lists the correct copyright holder.
- [ ] Confirm `.gitignore`, `.htaccess` and restricted directory access remain effective.
- [ ] Decide how private vulnerability reports will be received.

## Live installation

- [ ] Fresh install on an **empty** MariaDB/MySQL database completes.
- [ ] Original administrator is Super Admin and password minimum is eight characters.
- [ ] Installer cannot be rerun without intentionally removing private lock/config.
- [ ] PHP extensions and CLI scheduler work.
- [ ] Database/schema version and application `VERSION` agree.
- [ ] HTTPS redirects and cookie/session flags are appropriate for the hosting environment.
- [ ] Direct browser access to `config/`, `storage/`, `tests/`, `cron/` and internal dispatch files is denied.
- [ ] File uploads do not permit PHP execution.

## Monitoring and messages

- [ ] One scheduled check persists for a test monitor and respects its interval.
- [ ] Test Now does not affect scheduled state or incident notification.
- [ ] Confirm a controlled outage produces one DOWN and one RECOVERED event.
- [ ] Global admin Telegram destinations independently receive the alerts.
- [ ] Invalid Bot Token/Chat ID errors do not stop monitoring or later monitors.
- [ ] Webhook secret rejects invalid/missing header and accepts valid `/start TOKEN` once.
- [ ] Portal account links, disconnects, reconnects and changes notification preference correctly.
- [ ] A disabled portal account receives no personal alerts.

## Privacy, portal and charts

- [ ] OTP mail arrives using the actual hosting mail transport.
- [ ] Portal login validates code expiry, one-time use and throttling.
- [ ] Account A cannot view or edit Account B's monitors, incidents or analytics.
- [ ] Monitor quota prevents extra creation on the server.
- [ ] Global public `/status/` lists only platform-owned public monitors.
- [ ] `/status/{slug}/` shows only that active owner's published monitors.
- [ ] Disabled personal page and private incidents are not accessible.
- [ ] Analytics and history show no-data states rather than invented uptime.
- [ ] Layout and controls work on desktop and mobile browsers.
- [ ] Open Graph previews and absolute URLs match configured Base URL.

## Maintenance

- [ ] Cron lock prevents overlapping workers.
- [ ] Stale Cron and error states are visible in System Health.
- [ ] Retention cleanup uses bounded batches and preserves incident history.
- [ ] Database backups are private and restorable.
- [ ] Maintenance actions require Super Admin permission and CSRF.

**Run these checks for your own installation.** Maintainer testing does not replace validation on a different host.
