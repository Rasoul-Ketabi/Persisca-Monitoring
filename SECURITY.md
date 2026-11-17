# Security Policy

## Status and supported versions

The latest code distributed here is **v0.2.3, code-verified**. The project owner reports testing earlier installations; live verification of the v0.2.3 SMTP changes remains advisable. It has not received an independent security audit, and you should validate it in your own hosting environment before using it with sensitive data.

| Version | Status |
| --- | --- |
| v0.2.3 | Code-verified update, best-effort security reports |
| Older versions | Upgrade recommended; not actively maintained here |

## Reporting a vulnerability

**Do not disclose vulnerabilities, credentials, or private user data in public issues.** If enabled, use GitHub's **Security > Advisories > Report a vulnerability** for the repository. Otherwise contact the maintainer privately through a method listed on the repository owner's profile. Avoid including raw secrets in the initial report.

Useful report details: affected version, reproduction steps in a test installation, security impact, and a proposed mitigation. Please allow the maintainer to evaluate the report before public disclosure.

## Deployment security baseline

- Run on HTTPS. Use HTTPS for Telegram Bot API webhooks.
- Retain Apache/LiteSpeed `.htaccess` access restrictions. On nginx, deny direct HTTP access to `app/`, `config/`, `cron/`, `database/`, `storage/`, `tests/`, source PHP helpers, and internal dispatcher scripts. Do not expose `.git/`.
- Do not publicly expose `config/database.php`, real environment files, `storage/installed.lock`, runtime caches, sessions, uploads or database dumps.
- Restrict database permissions to one application database. Keep backups private.
- Use distinct Super Admin credentials and an 8-character or longer password. Enable TLS and appropriate session protection.
- Portal email may use SMTP or PHP `mail()`, and requires a valid From address. SMTP authentication must use STARTTLS or SSL/TLS. Set appropriate SPF/DKIM/DMARC on your mail domain.
- Allow only intended outbound HTTP/HTTPS destinations. Network-internal monitor access is privileged because it can create server-side network requests. Avoid enabling it for untrusted users.
- Disable PHP execution in writable uploads. Verify the web server enforces file access restrictions after every hosting migration.
- Store Telegram bot and webhook secrets securely. They are in the **database settings table**, so database access and exports expose them.
- After an incident, rotate any exposed Telegram Bot Token, change database passwords and revoke compromised sessions as appropriate.

### Limitations

Telegram's Bot API does not offer a client-supplied exactly-once idempotency key. A crash after acceptance and before delivery-log commit may cause a duplicate message. Observed check-success percentages are not continuous availability guarantees. OTP email availability depends on the configured SMTP or PHP mail transport. This software has not been independently audited.

## SMTP credentials (v0.2.3)

The SMTP password is not returned to the browser after saving, but the application currently stores it in the database `settings` table **without application-level encryption at rest**. Treat database exports and backups as sensitive. Use an SMTP-specific credential, rotate it if exposed, and prefer STARTTLS or SSL/TLS with a verified certificate. The SMTP form does not support disabling TLS certificate validation.
