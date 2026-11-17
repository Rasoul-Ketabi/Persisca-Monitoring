# Monitor v0.2.3 | SMTP & Portal Monitor Fix

**Code-verified update to maintainer-tested earlier versions** | PHP 8.1+ | MySQL/MariaDB | MIT License

This release combines the last two fixes since **v0.2.2** into one update:

### 1. SMTP Email Infrastructure

- New **Admin > Mail Settings** page (`/admin/settings/mail/`) for Super Admin.
- Native PHP SMTP transport with STARTTLS, SSL/TLS, AUTH LOGIN/PLAIN, configurable sender and Reply-To.
- **Send Test Email**, delivery diagnostics and Mail System Health.
- Customer OTP emails use the selected transport (SMTP or PHP `mail()`).
- Existing accounts continue using PHP `mail()` until an administrator switches transport.

### 2. Customer Portal Add/Edit Monitor Hotfix

- Fixes a 500 error when a Portal User opened Add Monitor or Edit Monitor.
- Corrects the namespaced `Security::post()` reference in the Portal form template.
- No SQL/database change is necessary for this hotfix itself.

### Download the right file

| Asset | Purpose |
| --- | --- |
| `monitor-install-v0.2.3.zip` | **Fresh installation** on an empty database; contains both fixes |
| `monitor-update-v0.2.2-to-v0.2.3.zip` | **Existing GitHub v0.2.2** installations; one cumulative patch with both fixes |
| `monitor-sql-patch-v0.2.3.sql` | Run **once before the cumulative patch** when upgrading from v0.2.2 |
| `monitor-hotfix-v0.2.3-portal-add-monitor.zip` | Existing v0.2.3 SMTP installations **missing only the Portal hotfix** |

### Upgrade from v0.2.2

1. Back up site files and database; pause the monitoring Cron.
2. Run `monitor-sql-patch-v0.2.3.sql` **once** through phpMyAdmin.
3. Extract `monitor-update-v0.2.2-to-v0.2.3.zip` at the existing application root and allow overwrite.
4. Resume Cron. Verify Admin Mail Settings, Test Email, portal OTP and Add/Edit Monitor.

**Already on v0.2.3 with both fixes?** No action is required. **On v0.2.3 SMTP only?** Install only the small Portal hotfix ZIP; no SQL.

### Fresh installation

Download `monitor-install-v0.2.3.zip`, extract directly into a website document root, create a new empty database and open `https://YOUR-DOMAIN/install/`. Configure a PHP CLI Cron job (recommended once per minute). Then configure SMTP and send a test email. See [Installation Guide](docs/INSTALLATION.md).

### Notes

- SMTP credentials are stored in DB settings without at-rest encryption; keep database exports private.
- SMTP server acceptance does not guarantee Inbox delivery; SPF/DKIM/DMARC matter.
- Scheduled monitoring, admin and personal Telegram alerts, personal public pages, analytics and retention remain available.
- Earlier versions were installed/tested by the maintainer; live SMTP and Portal form verification of this update is still recommended on each deployment. The software has not been independently audited.

License: MIT.
