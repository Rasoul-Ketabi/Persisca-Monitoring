# Monitor

**Self-hosted website uptime monitoring for PHP and MySQL.**

[![Version](https://img.shields.io/badge/version-v0.2.2-blue)](#releases-and-project-status)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4)](#requirements)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Monitor checks HTTP and HTTPS endpoints on a schedule, records incidents and recoveries, and publishes status pages. It also supports Telegram alerts, administrator accounts, and an email-OTP customer portal. You can run it on a typical PHP hosting account without Docker, Node.js, a background daemon, or a paid monitoring service.

> **Release status: v0.2.2, maintainer-tested.** The project owner has deployed and tested the application. Hosting environments differ, so every new installation should still run the [deployment checklist](docs/DEPLOYMENT-CHECKLIST.md). The code has not been independently security audited. See [Security](SECURITY.md).

## Features

| Area | Included |
| --- | --- |
| Website monitoring | HTTP/HTTPS checks, expected status codes, timeout, interval, retry and failure threshold |
| Incidents | Confirmed DOWN / RECOVERED transitions, durations, active incidents, history |
| Alerts | Telegram Bot API, up to 10 global destinations, independent delivery logs and retries |
| Telegram linking | Portal users can connect one private Telegram account using a one-time deep link and a secret-validated webhook |
| Analytics | 24-hour, 7-day and 30-day observed check-success statistics, response-time summaries and charts |
| Storage | Hourly aggregation, raw check retention, notification log retention, bounded cleanup |
| Public pages | Global `/status/` and optional personal `/status/{slug}/` pages, 30-day history, incident visibility and Open Graph metadata |
| Administration | Super Admin and Admin roles, account management, session invalidation, health checks and maintenance controls |
| Customer portal | Email OTP login, monitor quotas, owner-scoped monitors, incidents, analytics and public-status settings |
| Appearance | App name, logo, favicon, accent, footer and Open Graph image are configurable |

**Important:** Uptime percentages are based on *observed scheduled check results*. They do not prove that the service was up during unobserved minutes. No-data days remain unknown. Telegram sends are retry-safe in normal operation, but exactly-once delivery cannot be guaranteed after a crash.

## Requirements

- **PHP 8.1+** with PDO, `pdo_mysql`, cURL, OpenSSL/TLS support, JSON, `fileinfo`, and sessions.
- **MySQL or MariaDB**, with InnoDB and permission to create tables.
- Apache or LiteSpeed with `.htaccess` rewrite and access controls. nginx requires equivalent server rules.
- **PHP CLI and Cron** for scheduled checks. A one-minute cron schedule is recommended.
- Writable `config/`, `storage/`, `storage/cache/`, `storage/logs/`, and `public/assets/uploads/` directories under the PHP account. Do not make them world-writable.
- **Outbound email using PHP `mail()`** for customer OTP login. Your hosting provider must allow and deliver mail.
- **Public HTTPS URL** for Telegram webhook account linking.

No Composer, Node/npm, Redis, Docker, WebSocket server or root access is required.

## Install in 7 steps

1. Download the installable `monitor-install-v0.2.2.zip` asset from [Releases](#releases-and-project-status) or the repository source files. Extract the contents directly into an **empty website document root** (for example `public_html/monitor/` or a subdomain's `httpdocs/`). Do **not** upload the ZIP as a web page or add an extra wrapper folder.
2. Create a **new empty database** and a database user in your hosting control panel. Grant access to that database. **Do not import old migration patches for a fresh installation.**
3. Ensure PHP 8.1+ and the required extensions are enabled. Keep all `.htaccess` files, including the dotfiles in the root and protected directories.
4. Open `https://YOUR-DOMAIN/install/` and follow the setup wizard: database, first **Super Admin**, branding and timezone. The first password must have **at least 8 characters**.
5. Sign in at `https://YOUR-DOMAIN/admin/login/`. Verify the configured **Base URL** matches the public HTTPS address, including any subdirectory.
6. Add one CLI Cron task for `cron/monitor.php`, ideally **every minute**, and confirm its heartbeat under **System Health / Maintenance**. See [Cron setup](docs/INSTALLATION.md#scheduled-monitoring-cron).
7. Add an HTTP/HTTPS monitor, run **Test Now**, then confirm scheduled checks and incidents. Configure [Telegram](docs/CONFIGURATION.md#telegram-alerts) or [OTP email](docs/CONFIGURATION.md#portal-otp-email) only when those services are available.

Detailed instructions for **cPanel**, **Plesk**, nginx, permissions and common installation problems: [INSTALLATION.md](docs/INSTALLATION.md).

### Cron examples

```bash
# cPanel example. Replace USERNAME and the application path.
/usr/local/bin/php /home/USERNAME/public_html/monitor/cron/monitor.php

# Plesk example. Use the installed PHP CLI binary and actual document root.
/opt/plesk/php/8.2/bin/php /var/www/vhosts/EXAMPLE.COM/httpdocs/cron/monitor.php
```

These are examples, not commands that can run unchanged. Set your scheduler to run the matching command every minute. **The file is CLI-only** and cannot be run through a browser.

## App URLs

| URL | Purpose |
| --- | --- |
| `/admin/` | Administrator dashboard |
| `/admin/login/` | Admin / Super Admin sign-in |
| `/admin/monitors/` | Monitor management |
| `/admin/incidents/` | Incidents |
| `/admin/notifications/` | Telegram setup, delivery history, webhook setup |
| `/admin/portal-users/` | Portal accounts, quotas and sender address (Super Admin) |
| `/admin/maintenance/` | Operational tools (Super Admin) |
| `/account/login/` | Portal email-OTP sign-in |
| `/account/` | Customer dashboard |
| `/account/status-page/` | Personal status visibility settings |
| `/account/telegram/` | Telegram account linking |
| `/status/` | Global public status page |
| `/status/{slug}/` | Enabled personal public status page |
| `/telegram/webhook/` | Telegram Bot API callback |

## Roles and data separation

Super Admin can manage global settings and user accounts. Standard Admin can perform allowed operational tasks but cannot manage credentials or privileged settings. Portal users authenticate by email OTP, own only their assigned monitors, and have a defined monitor quota. Admin-owned monitors are separate from portal-owned monitors. Each personal public status page shows only its owner's *public* monitors and incidents.

## Configuration and security notes

- The installation wizard creates `config/database.php` and `storage/installed.lock`. **Never commit either file.** Both are excluded by `.gitignore`.
- Telegram bot and webhook secrets are stored in the database `settings` table. Protect **database access and backups**. Secrets are not supplied in this repository.
- Do not upload real logs, OTP codes, chat IDs, database exports or customer branding files to GitHub.
- Use HTTPS in production. Verify PHP `mail()` delivery before creating portal accounts.
- Keep `.htaccess` protection. nginx administrators must configure equivalent deny rules for `app`, `config`, `storage`, `database`, `cron`, `tests` and internal dispatch scripts.
- Some hosting providers block `mail()` or outbound HTTPS requests; test before enabling account login/notifications.

Read [SECURITY.md](SECURITY.md) and the [deployment checklist](docs/DEPLOYMENT-CHECKLIST.md) before operating a public installation.

## Upgrade an existing installation

**Back up the site and database first.** Use the version-specific migration instructions and patch SQL for the version you are upgrading from. Do not import the fresh-install `database/schema.sql` into an existing installation, and do not rerun `/install/`.

See [UPGRADING.md](docs/UPGRADING.md). Upgrades involving new database columns often require running the patch SQL *before* updating the PHP files.

## Documentation

- [Installation and Cron](docs/INSTALLATION.md)
- [Configuration, email and Telegram](docs/CONFIGURATION.md)
- [Deployment checklist](docs/DEPLOYMENT-CHECKLIST.md)
- [Upgrading](docs/UPGRADING.md)
- [Architecture and database reference](docs/TECHNICAL-REFERENCE.md)
- [Security policy](SECURITY.md)
- [Contributing](CONTRIBUTING.md)
- [Changelog](CHANGELOG.md)
- [Frequently asked questions](docs/FAQ.md)

## Releases and project status

`v0.2.2` adds customer Telegram account linking with owner-scoped alerts, on top of the v0.2.1 personal status pages and v0.2.0 email-OTP portal. Check the project's **Releases** tab for `monitor-install-v0.2.2.zip` and release notes. The maintainer has tested the deployed application; compatibility with other hosting providers has not been independently established.

## Support and contributions

Issues and pull requests are welcome. Check [CONTRIBUTING.md](CONTRIBUTING.md) before submitting changes. For security reports, follow [SECURITY.md](SECURITY.md) instead of opening a public issue.

## License

MIT. See [LICENSE](LICENSE). Confirm that you have the right to redistribute any images or assets you replace or include.
