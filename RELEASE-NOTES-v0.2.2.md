# Monitor v0.2.2

**Maintainer-tested open-source release**

Monitor is a free, self-hosted HTTP/HTTPS uptime monitor for PHP and MySQL/MariaDB. The project owner reports that the application has been deployed and tested. Cross-host compatibility and an independent security audit have not been established.

## Included

- Scheduled HTTP/HTTPS checks with incident and recovery tracking
- Telegram alert delivery to multiple global destinations
- Portal user linking through one-time Telegram deep links and a secret-authenticated webhook
- Portal accounts with email OTP sign-in and monitor quotas
- Global and optional personal public status pages
- Check-based uptime analytics and response-time history
- Super Admin controls, monitoring diagnostics, retention, and maintenance

## Installation

For a new installation, download **`monitor-install-v0.2.2.zip`**, extract its contents directly into your website document root, create an empty MySQL/MariaDB database, and open `https://YOUR-DOMAIN/install/`. Configure a PHP CLI cron task (recommended once per minute). See [docs/INSTALLATION.md](docs/INSTALLATION.md).

## Upgrading

Back up your database and files first. Follow the migration sequence for the version you currently run. `database/patches/v0.2.2.sql` upgrades the accepted v0.2.1 schema, and **must not** be applied to an unrelated version or a fresh install. See [docs/UPGRADING.md](docs/UPGRADING.md).

## Important notes

- Requires PHP 8.1+, relevant extensions, MySQL/MariaDB, Apache/LiteSpeed rewrite support (or equivalent nginx rules), and cron.
- OTP email depends on PHP `mail()` delivery. Telegram account linking requires a reachable HTTPS webhook.
- Telegram delivery cannot guarantee exactly once if a crash occurs between provider acceptance and saving delivery state.
- Uptime percentages are based on recorded scheduled checks, not continuous real-time observation.
- Maintain backups and perform the [deployment checklist](docs/DEPLOYMENT-CHECKLIST.md) on your own host.

License: MIT.
