# Security Policy

## Status and supported versions

The latest code distributed here is **v0.2.2, maintainer-tested**. The project owner reports installing and testing the application. It has not received an independent security audit, and you should validate it in your own hosting environment before using it with sensitive data.

| Version | Status |
| --- | --- |
| v0.2.2 | Maintainer-tested release, best-effort security reports |
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
- PHP `mail()` requires a working mail transport and a valid From address. Set appropriate SPF/DKIM/DMARC on your mail domain.
- Allow only intended outbound HTTP/HTTPS destinations. Network-internal monitor access is privileged because it can create server-side network requests. Avoid enabling it for untrusted users.
- Disable PHP execution in writable uploads. Verify the web server enforces file access restrictions after every hosting migration.
- Store Telegram bot and webhook secrets securely. They are in the **database settings table**, so database access and exports expose them.
- After an incident, rotate any exposed Telegram Bot Token, change database passwords and revoke compromised sessions as appropriate.

### Limitations

Telegram's Bot API does not offer a client-supplied exactly-once idempotency key. A crash after acceptance and before delivery-log commit may cause a duplicate message. Observed check-success percentages are not continuous availability guarantees. OTP email availability depends on the host's mail transport. This software has not been independently audited.
