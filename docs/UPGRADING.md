# Upgrading Monitor

Current GitHub version: **v0.2.3**, including native SMTP and the Portal Add/Edit Monitor hotfix.

## General rules

- Back up **both** the database and the installed files, including server-local configuration, and verify your backup is usable.
- Pause Cron during any application and database upgrade.
- Use the patch SQL **only** for the indicated starting version. Never import `database/schema.sql` into an existing installation.
- Extract ZIP files **at the application root** with no wrapper directory; do not replace your `config/database.php`, `storage/installed.lock`, uploaded files, logs or sessions.
- Do not rerun `/install/` to upgrade.

## From GitHub v0.2.2 directly to v0.2.3 (both fixes)

1. Download `monitor-sql-patch-v0.2.3.sql` and **`monitor-update-v0.2.2-to-v0.2.3.zip`** from the v0.2.3 GitHub release.
2. Back up files and database; pause Cron.
3. In phpMyAdmin, run `monitor-sql-patch-v0.2.3.sql` **once**. It adds SMTP default settings with `INSERT ... ON DUPLICATE KEY` and updates installed_version; existing values are retained.
4. Extract the cumulative update ZIP inside your existing website root, using **Overwrite**. It includes the SMTP layer and Portal Add/Edit Monitor fix in one patch.
5. Resume Cron. Confirm application version, user access, SMTP settings page, portal Add/Edit Monitor forms, personal status pages and Telegram alerts.
6. Enter SMTP settings, **Send Test Email**, and only after verifying receipt switch Mail Transport to SMTP. Try the portal OTP login.

### From v0.2.3 SMTP without the Portal form hotfix

If you already ran `monitor-sql-patch-v0.2.3.sql` and installed the **earlier SMTP ZIP** but not the Portal fix, install **only** `monitor-hotfix-v0.2.3-portal-add-monitor.zip`. No additional SQL is required. The small patch corrects the `Security::post()` reference inside the Portal form.

### From v0.2.3 with both changes already installed

No upgrade is necessary. Do not reapply SQL merely to update GitHub release history. The v0.2.3 repository and installation assets now include both fixes.

### New installations

Download **`monitor-install-v0.2.3.zip`** and run `/install/` against a **new empty database**. Do **not** use migration SQL files. SMTP configuration is optional during installation.

## Verification checklist

- `/admin/settings/mail/` renders only for a Super Admin, accepts settings and sends a test email.
- `SMTP` and `PHP mail()` both use the central Mailer, without exposing saved SMTP passwords.
- `/account/login/` generates an OTP and only creates a portal session after successful verification.
- `/account/monitors/add/` and `/account/monitors/edit/` display a complete form instead of a 500 error.
- Cron, Telegram admin/portal alerts, dashboard, analytics, incidents and public status work as before.
- Database `system_meta.installed_version` and the file `VERSION` display `v0.2.3`.

If you upgraded from something older than v0.2.2, apply **each missing migration in sequence** using its own instructions before this update. Do not apply the cumulative v0.2.3 patch directly to an earlier schema.
