# Changelog

Public GitHub tags start at v0.2.2. Version v0.2.3 includes two subsequent fixes in one installable build.

## v0.2.3 - SMTP infrastructure and Portal monitor hotfix

- Native SMTP transport in the central Mailer, alongside existing PHP `mail()`.
- STARTTLS, SSL/TLS and unauthenticated relay, with AUTH LOGIN/PLAIN, TLS verification and safe diagnostics.
- Super Admin Mail Settings page at `/admin/settings/mail/`, Test Email, transport selection and System Health status.
- Portal OTP uses the configured mail transport; failed delivery invalidates an undelivered code.
- Corrected the `Security::post()` class resolution in Portal Add/Edit Monitor forms to prevent the 500 error.
- Existing monitor, incident, Telegram, status page and portal features retained.
- Upgrade SQL adds mail settings defaults without resetting existing values.

## v0.2.2 - Telegram account linking

- One-time user Telegram deep links and secret-verified webhook.
- Owner-scoped DOWN and RECOVERED alerts, with per-recipient delivery logs.
- Existing multi-chat admin notifications preserved.

## v0.2.1 - Personal public status pages

- Owner-scoped `/status/{slug}/`, history, public/private controls.
- Portal Users form styling and dashboard navigation fixes.

## v0.2.0 - Portal users

- OTP sign-in, monitor ownership and quotas.
- Portal monitor/incident/analytics pages and administrator account management.

## v0.1.0 - Core monitoring

- Scheduled HTTP checks, incident state/recovery, Telegram notifications, analytics, retention, public status, branding, Super Admin and maintenance.
