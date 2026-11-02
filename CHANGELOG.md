# Changelog

The entries below summarize the current cumulative implementation. This public repository starts with the **v0.2.2 maintainer-tested release**; earlier stage names refer to the development history rather than a supported set of GitHub release tags.

## v0.2.2 - Telegram account linking

- One-time portal Telegram deep links (15-minute expiry) and secret-verified webhook.
- Connected account status, alert toggle, disconnect/reconnect.
- Owner-scoped DOWN and RECOVERED alerts with per-recipient delivery logs.
- Existing multi-chat global/admin Telegram notifications preserved.

## v0.2.1 - Personal status pages

- Owner-scoped `/status/{slug}/` pages with status and incident history.
- Portal status-page controls and per-monitor visibility.
- Admin Portal Users form layout changes and earlier navigation hotfix retained.

## v0.2.0 - Portal users

- Email OTP login and independent portal sessions.
- Monitor ownership, quotas, and customer monitor/incident/analytics pages.
- Admin Portal User management.

## v0.1.0 - Core release candidate

- Super Admin/Admin access control, maintenance and system diagnostics.
- Scheduled monitoring, incidents, recovery, Telegram notifications, analytics, retention and global public status page.
- Installation wizard, branding and shared-hosting cron support.
