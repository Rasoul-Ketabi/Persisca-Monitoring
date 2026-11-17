# Monitor

**Free, self-hosted website uptime monitoring for PHP and MySQL/MariaDB.**

[![Version](https://img.shields.io/badge/version-v0.2.3-blue)](#releases)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4)](#requirements)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Monitor watches HTTP and HTTPS endpoints, tracks outages and recoveries, sends Telegram alerts, and publishes status pages. It includes an admin dashboard and a separate email-OTP portal for customers. The app runs on shared hosting without Docker, Node.js, Redis, Composer, or a dedicated daemon.

> **Status: v0.2.3, code-verified update.** The project owner has tested earlier Monitor installations. This cumulative update passed code-level checks; live SMTP delivery and the Portal form fix should still be confirmed on the target host. Compatibility with every hosting provider and an independent security audit have not been established. See the [deployment checklist](docs/DEPLOYMENT-CHECKLIST.md).

## Features

| Area | What is included |
| --- | --- |
| Monitoring | Scheduled HTTP/HTTPS checks, expected response codes, configurable intervals, timeouts, retries and failure thresholds |
| Incidents | Confirmed DOWN and RECOVERED transitions, outage duration, history, manual Test Now separated from scheduled monitoring |
| Telegram | Up to 10 global admin destinations, delivery logs and retries; optional private-account linking and owner-scoped portal alerts |
| Customer portal | Portal users with 6-digit email OTP login, monitor ownership, per-user quotas, monitor management, incidents and analytics |
| Status pages | Global `/status/`, enabled personal `/status/{slug}/` pages, 30-day visual history, incident display, Open Graph metadata |
| Analytics | 24h/7d/30d observed-check success metrics, response-time statistics/charts, hourly aggregation, data retention |
| Administration | Super Admin and Admin roles, authorization, account management, system health and bounded maintenance operations |
| Email delivery | **Native SMTP** (STARTTLS, SSL/TLS, AUTH PLAIN/LOGIN) or PHP `mail()`, sender settings, optional Reply-To, test email |
| Appearance | Configurable brand name, logo, favicon, colors, footer and social preview |

**Metric limitation:** Uptime displayed by Monitor is based on recorded scheduled checks, not a guarantee of continuous availability in unobserved minutes. No-data periods stay unknown. Telegram provider acceptance may occasionally be followed by a duplicate send if the worker crashes before logging delivery.

## Requirements

- PHP **8.1 or later**, with `pdo_mysql`/PDO, cURL, JSON, `fileinfo`, OpenSSL/TLS and sessions.
- MySQL/MariaDB with InnoDB; a new empty database for first installation.
- Apache/LiteSpeed with rewrite and `.htaccess` rules, or a correctly configured nginx equivalent.
- Access to a PHP CLI Cron task. **Every minute** is recommended.
- Writable `config/`, `storage/`, `storage/cache/`, `storage/logs/` and `public/assets/uploads/`, using restrictive permissions.
- For portal OTP: working SMTP relay (recommended) or server PHP `mail()` with a valid sender address.
- For Telegram account linking: a publicly reachable HTTPS domain and Telegram webhook access.

## New installation

1. From [Releases](#releases), download **`monitor-install-v0.2.3.zip`**. Extract its **contents** directly into an empty website document root. Do not leave an extra ZIP-name wrapper folder.
2. Create a **new empty** MySQL/MariaDB database and its user through cPanel/Plesk.
3. Set PHP 8.1+ and required extensions; keep all `.htaccess` files.
4. Open `https://YOUR-DOMAIN/install/`. Enter database details, configure branding and create the first **Super Admin** (minimum 8-character password).
5. Log in at `/admin/login/` and check the Base URL and timezone.
6. Create a CLI Cron task for `cron/monitor.php`, preferably **every minute**. Confirm the heartbeat in System Health.
7. Add a monitor and run Test Now. For OTP delivery, configure **Mail Settings** and send a test email before switching portal emails to SMTP.

A fresh install uses `database/schema.sql` automatically. **Do not import upgrade SQL files during fresh installation.** Detailed instructions: [Installation Guide](docs/INSTALLATION.md).

### Cron examples

```bash
# cPanel (adjust username, directory and PHP CLI path)
/usr/local/bin/php /home/USERNAME/public_html/monitor/cron/monitor.php

# Plesk (adjust the document root and installed PHP version)
/opt/plesk/php/8.2/bin/php /var/www/vhosts/EXAMPLE.COM/httpdocs/cron/monitor.php
```

## SMTP and OTP setup

1. Sign in as Super Admin and open **`/admin/settings/mail/`**.
2. Select **SMTP** and provide Host, Port, Encryption, Authentication, Username and Password. Typical configurations: port **587 + STARTTLS** or **465 + SSL/TLS**.
3. Set **From Email** and **From Name**, plus an optional Reply-To address. Save.
4. Use **Send Test Email** and check that the receiving mailbox gets the message. SMTP test delivery can be performed before using SMTP for OTP.
5. After verification, choose SMTP as the active mail transport and test login at **`/account/login/`**.

**Existing installations stay on PHP `mail()` until you explicitly change the mail transport.** Saved SMTP passwords are not shown in the form. They are stored in the application database without at-rest encryption; protect DB access and backups. SMTP acceptance does not guarantee Inbox placement. Configure SPF, DKIM and DMARC for your sender domain.

## Main routes

| Route | Description |
| --- | --- |
| `/admin/` | Admin dashboard |
| `/admin/monitors/` | Monitor management |
| `/admin/incidents/` | Incidents |
| `/admin/notifications/` | Telegram settings, multi-destination alerts and webhook setup |
| `/admin/settings/mail/` | **Super Admin SMTP and test email settings** |
| `/admin/portal-users/` | Portal account and quota management |
| `/admin/users/` | Admin user management |
| `/admin/maintenance/` | Maintenance and health checks |
| `/account/login/` | Portal OTP login |
| `/account/monitors/add/` | **Portal Add Monitor form (v0.2.3 hotfix included)** |
| `/account/monitors/edit/` | Portal Edit Monitor form (v0.2.3 hotfix included) |
| `/account/telegram/` | Personal Telegram linking |
| `/account/status-page/` | Personal public-page settings |
| `/status/` | Global public status |
| `/status/{slug}/` | Enabled owner-scoped public page |

## Upgrading from v0.2.2

The v0.2.3 update combines **SMTP infrastructure** and the **Portal Add/Edit Monitor 500-error fix**. To upgrade directly from **v0.2.2**:

1. Back up site files and DB; pause Cron during the upgrade.
2. Apply `monitor-sql-patch-v0.2.3.sql` **once** in phpMyAdmin.
3. Extract `monitor-update-v0.2.2-to-v0.2.3.zip` into the **existing application root**, allowing overwrite.
4. Resume Cron and test Mail Settings, Send Test Email, OTP login, Portal Add Monitor and Edit Monitor.

**Already installed v0.2.3 with the Portal Hotfix?** No upgrade is required. **Installed v0.2.3 SMTP but not the Portal Hotfix?** Use only the small `monitor-hotfix-v0.2.3-portal-add-monitor.zip` and skip SQL. See [UPGRADING.md](docs/UPGRADING.md).

Do not rerun `/install/`, import `database/schema.sql` into an existing DB, or upload the full installer over a running installation without a backup.

## Security and deployment

- Never commit `config/database.php`, `storage/installed.lock`, database dumps, SMTP/Telegram tokens, OTP codes, customer uploads or runtime logs. `.gitignore` excludes common local files.
- Protected source and runtime folders must not be web-accessible. Apache protections are provided; nginx requires equivalent deny rules.
- Only Super Admin can modify SMTP credentials. Portal users can access only their owned monitors.
- Keep HTTPS enabled. Avoid enabling network-private monitor targets for untrusted users.
- See [SECURITY.md](SECURITY.md) and [Deployment Checklist](docs/DEPLOYMENT-CHECKLIST.md).

## Documentation

- [Installation and Cron](docs/INSTALLATION.md)
- [Configuration and SMTP](docs/CONFIGURATION.md)
- [Upgrading](docs/UPGRADING.md)
- [Deployment Checklist](docs/DEPLOYMENT-CHECKLIST.md)
- [FAQ](docs/FAQ.md)
- [Technical Reference](docs/TECHNICAL-REFERENCE.md)
- [Security](SECURITY.md)
- [Contributing](CONTRIBUTING.md)
- [Changelog](CHANGELOG.md)

## Releases

The current tag is **`v0.2.3`**, titled **"Monitor v0.2.3 | SMTP & Portal Monitor Fix"**. It is an update to the earlier GitHub v0.2.2 release. Download the install ZIP for a new site or the cumulative update ZIP and SQL for an existing v0.2.2 site. See [Release Notes](RELEASE-NOTES-v0.2.3.md).

## License

Open source under the [MIT License](LICENSE). The copyright line in LICENSE belongs to Persisca Technologies. Verify redistribution rights for any bundled images or assets and replace them if necessary.
