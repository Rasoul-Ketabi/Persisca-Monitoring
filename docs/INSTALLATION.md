# Installation Guide

This guide applies to **Monitor v0.2.2**. For an existing installation, use [UPGRADING.md](UPGRADING.md) instead. The maintainer has installed and tested the application. New deployments should still verify the host-specific prerequisites and delivery integrations.

## 1. Confirm hosting support

| Dependency | Requirement |
| --- | --- |
| PHP | 8.1 or newer; CLI PHP for Cron |
| Extensions | PDO + pdo_mysql, cURL, JSON, fileinfo, sessions, OpenSSL/TLS support |
| Database | MySQL/MariaDB with InnoDB; ability to create tables and indexes |
| Web server | Apache/LiteSpeed with rewriting and `.htaccess`, or equivalent nginx rules |
| Storage | PHP account can write `config/`, `storage/`, `storage/cache/`, `storage/logs/`, `public/assets/uploads/` |
| Scheduler | Cron or hosting scheduled task that executes PHP CLI |
| Email | `mail()` configured for OTP users |
| Telegram linking | Accessible HTTPS site and outbound HTTPS to Telegram |

No SSH or Node/npm is needed for installation if your hosting panel can extract ZIPs and configure Cron.

## 2. Prepare an empty database

Create a new, empty database and a dedicated database user in **cPanel > MySQL Databases** or **Plesk > Databases**. Assign privileges for schema creation and normal operations. Keep the database name, user, password, hostname and port available for the web installer. Avoid using a database containing other application tables.

**Never commit these credentials.** The installer generates `config/database.php` on the server and writes an installer lock at `storage/installed.lock`.

## 3. Upload source files

Download the repository's installable ZIP from Releases (or download repository source ZIP) and **extract it** into the subdomain document root or chosen folder. The root must contain `.htaccess`, `bootstrap.php`, `install/index.php`, `database/schema.sql`, `admin/`, `account/`, `status/` and `public/` directly.

Correct:

```text
httpdocs/
  .htaccess
  bootstrap.php
  install/
  app/
  admin/
  account/
  status/
  database/
  public/
  storage/
```

Incorrect: `httpdocs/monitor-github-ready-v0.2.2/bootstrap.php` when the domain is pointed at `httpdocs/`.

On shared hosting use typical file permissions `0644` for files and `0755` for directories, with PHP owning or otherwise having write access to the required paths. **Do not set 0777**. Keep the root and protected-directory `.htaccess` files.

### nginx note

This repository relies on Apache-style access rules. nginx administrators must map clean routes (including `/status/{slug}/`), deny direct requests to private source/runtime paths and PHP helpers, and allow PHP only through intended entry points. **Do not publish on nginx until these equivalent rules have been set and tested.** The application does not include a ready-to-paste nginx server block because its correct configuration depends on the hosting topology.

## 4. Run the installer

Navigate to:

```text
https://YOUR-DOMAIN/install/
```

The wizard checks extensions and directories, asks for DB credentials, creates the first **Super Admin** with a password of at least **8 characters**, and stores branding/timezone settings. It imports `database/schema.sql` into a **new empty database**. Once installed, `storage/installed.lock` stops normal reinstallation. Never delete the lock to apply an upgrade.

Sign in at `/admin/login/` and verify the General Settings Base URL matches the real address. If your installation is in a subdirectory, include that subdirectory in Base URL.

## 5. Scheduled monitoring Cron

The CLI script is `cron/monitor.php`. Schedule it every minute, or the shortest interval available. The monitoring engine itself respects each monitor's configured check interval.

### cPanel Cron Jobs

In cPanel, open **Cron Jobs**, choose **Once Per Minute**, and use a command like:

```bash
/usr/local/bin/php /home/USERNAME/public_html/monitor/cron/monitor.php
```

### Plesk Scheduled Tasks

Open **Websites & Domains > Scheduled Tasks**, create a PHP script task (or a Run a Command task where supported), and point it at your real `cron/monitor.php`. For example:

```bash
/opt/plesk/php/8.2/bin/php /var/www/vhosts/EXAMPLE.COM/httpdocs/cron/monitor.php
```

The correct binary and filesystem path depend on the host. Avoid logging Cron output to a public folder. After scheduling, check `/admin/system/` and `/admin/maintenance/` for heartbeat status. `cron/monitor.php` refuses browser execution.

## 6. Complete optional setup

- **Telegram admin alerts:** `/admin/notifications/` (save bot token, chat destinations and test delivery).
- **Portal OTP:** `/admin/portal-users/` (set a valid sender address before issuing user logins).
- **Telegram webhook linking:** Super Admin configures webhook on the Notifications page; portal users connect at `/account/telegram/`.
- **Public status page:** `/status/`; personal pages use `/status/{slug}/` and require enablement.

See [CONFIGURATION.md](CONFIGURATION.md) for details.

## 7. Verify installation

Use [DEPLOYMENT-CHECKLIST.md](DEPLOYMENT-CHECKLIST.md). Check a monitor's successful scheduled check, a controlled DOWN/RECOVERED transition on a test endpoint, Telegram delivery, portal OTP and isolation, and private status visibility before making the site public.

## Common problems

| Symptom | Check |
| --- | --- |
| Installer unavailable / 403 | Document root, `.htaccess` and PHP handler |
| 500 / blank page | Correct PHP version/extensions; match schema to code version; private error logs |
| Cron never runs | Scheduler frequency, CLI path and PHP executable, cron lock and file permissions |
| OTP email never arrives | PHP `mail()` transport, `mail_from_email`, spam folder and DNS mail records |
| Telegram Connect unavailable | Saved Bot Token, valid HTTPS Base URL, successful Configure Webhook |
| User page 404 | Enabled flag, valid slug, matching rewrite rules |
| Logo/OG image missing | Upload directory write permissions and Base URL |

**No hosted demo and no provider-specific compatibility guarantee are included.**
