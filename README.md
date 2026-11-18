# Monitor v0.2.4

Self-hosted native PHP + MySQL/MariaDB HTTP/HTTPS uptime monitoring, incident tracking, admin/Portal User roles, personal status pages, Telegram notifications and OTP login. Designed for shared hosting without Node, Composer, Docker or Redis.

**Current release:** `v0.2.4`, cumulative on accepted `v0.2.3` with Portal Add/Edit Monitor hotfix. The updated behavior is **exclusive owner-based Telegram routing**, not an additional Telegram linking implementation. **Release Candidate**: source-level tests passed; live Telegram owner routing must be verified on the deployment.

## Requirements

PHP 8.1+ with PDO MySQL, cURL/TLS, fileinfo, OpenSSL streams for SMTP, sessions, PHP CLI for cron, a writable protected `storage/`, and MySQL/MariaDB. Apache/LiteSpeed with `.htaccess` or equivalent web server protection. HTTPS is required for Portal Telegram account linking. Portal email OTP needs a configured mail relay or SMTP account.

## Current features

- Scheduled HTTP/HTTPS checks, retries, timeouts, expected-status checks and diagnostic Test Now.
- Confirmed DOWN and RECOVERY incidents, history and response/uptime analytics.
- Multiple Admin/Super Admin accounts, per-user permissions, System Health and Maintenance.
- Portal User OTP-only sign-in, separate sessions, monitor ownership, per-user quota and owner-scoped CRUD/history.
- Global `/status/` for platform monitors and personal `/status/{slug}/` for opt-in public Portal User monitors.
- Telegram multi-chat destinations for **platform-owned monitors only**, with individual delivery history and retries.
- Telegram webhook and private account linking for Portal Users; **user-owned monitor alerts go only to the owning user, never to Admin/global destinations**, even when owner connection fails.
- SMTP transactional email (STARTTLS, SSL/TLS, plain unauthenticated relay), From/Reply-To, test email and OTP integration.
- White-label branding, configurable OG, resource limits/retention and installable shared-hosting operation.

## Fresh installation

1. Create an **empty** database and a database user.
2. Extract `monitor-full-v0.2.4.zip` at the site Document Root with no wrapper directory.
3. Open `https://YOUR-DOMAIN/install/`, follow the wizard and create the first Super Admin (8-character minimum password).
4. Configure the Base URL, branding, timezone and outgoing email at `/admin/settings/mail/`; send a test email, then switch OTP transport to SMTP if desired.
5. Configure scheduled monitoring cron, preferably every minute (adjust executable/path for your host):

```bash
# cPanel
/usr/local/bin/php /home/USERNAME/public_html/monitor/cron/monitor.php
# Plesk
/opt/plesk/php/8.2/bin/php /var/www/vhosts/DOMAIN/httpdocs/cron/monitor.php
```

6. To enable Telegram, enter Bot Token and admin Chat IDs in `/admin/notifications/`. Configure the HTTPS webhook there so Portal Users can connect using `/account/telegram/`.
7. Verify `/status/`, `/admin/` and `/account/login/`. Do not rerun the installer after installation.

## Upgrade from accepted v0.2.3 (Portal Hotfix included)

1. Back up files and database and **pause cron**.
2. Run `monitor-sql-patch-v0.2.4.sql` once through phpMyAdmin. This marks incorrectly scoped *unsent* Telegram deliveries terminal and updates version metadata. It does not change table structure or delete history.
3. Extract `monitor-patch-v0.2.4.zip` into the current Document Root and overwrite matching source files. Do **not** replace `config/database.php`, `storage/installed.lock`, uploaded branding assets, or server runtime logs.
4. Resume cron; if OPcache is active, invalidate old PHP bytecode. Verify login/SMTP/Telegram and test platform-owned and portal-owned DOWN and RECOVERY separately.

**Earlier than v0.2.3?** Apply the intervening migrations/releases first. Never import `database/schema.sql` over an existing installation.

## Telegram delivery policy

| Monitor owner | Alerts delivered to | Other destination |
|---|---|---|
| Platform (`portal_user_id IS NULL`) | Configured Admin Chat ID(s) | No Portal User |
| Portal User (`portal_user_id = N`) | Only connected Telegram of user `N` | No Admin fallback; no other Portal User |

Personal delivery is independent of the global Telegram toggle, but requires the shared Bot Token, connected/active owner, personal notification preference and per-monitor alert setting. If the owner is unavailable, the user-scoped notification is logged as failed; admins are **not** substituted. Old pending cross-owner rows are rejected before they can be delivered. A repeated outage does not create a new logical event.

## Security, support and limitations

Passwords use hashing and session revalidation, admin actions enforce roles and CSRF, user monitor queries require ownership checks, public status data is opt-in, and the Telegram webhook validates its secret header. Never publish database credentials, Telegram tokens, SMTP passwords, installed locks or session/log files. The database settings table holds protected-but-not-encrypted-at-rest SMTP/Telegram secrets: secure DB access and backups.

SMTP acceptance does not guarantee delivery to inboxes: verify SPF, DKIM, DMARC and provider policies. Telegram delivery retries are at-least-once, so a crash between Telegram acceptance and outbox commit can repeat a message. Real shared-host testing and production Telegram webhook verification are still required for this release.

See `MONITOR-FULL-PROJECT-v0.2.4.md` for full routes, file tree, schema, migration rules and test coverage.
