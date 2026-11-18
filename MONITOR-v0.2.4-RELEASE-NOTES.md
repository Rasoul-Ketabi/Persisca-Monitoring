# Monitor v0.2.4 | Exclusive Telegram Owner Routing

## Release summary

This cumulative release updates Telegram notification routing to match monitor ownership exactly. It builds on **v0.2.3 including the Add/Edit Monitor Portal Hotfix**, without removing SMTP, OTP, the public status pages, multi-admin or existing monitoring features.

## Changes

- **Platform-owned monitors**: DOWN/RECOVERY alerts go only to the configured Admin/Global Chat IDs, retaining independent multi-chat retries.
- **Portal User-owned monitors**: DOWN/RECOVERY alerts go only to that monitor owner's connected Telegram account. No Admin/global fallback, even if the user's Telegram is missing, disabled or has failed.
- **Delivery safety**: Each queued message is checked against current ownership again before sending. Old queued cross-owner messages are rejected, and the SQL upgrade marks misrouted pending rows terminal. Historical sent records are preserved.
- **Audit**: Owner-not-connected, disabled and other unavailable personal destinations are logged under the correct user recipient, not sent elsewhere.
- **Compatibility**: Original Portal Add/Edit Monitor Hotfix, SMTP, OTP, Telegram account deep-link/webhook, analytics, status pages and admin permissions retained.

## Upgrade from v0.2.3

Back up files/database, pause cron, run **`monitor-sql-patch-v0.2.4.sql`** in phpMyAdmin, extract **`monitor-patch-v0.2.4.zip`** into your Document Root with overwrite, resume cron. Do not rerun the installer. Keep config, installed lock, uploads and live logs.

## New installations

Deploy `monitor-full-v0.2.4.zip` into an empty site and run `/install/`, configure cron and SMTP/Telegram after first Super Admin creation.

## Verification

Local source-level tests cover the exclusive routing matrix, missing/disabled users, no fallback, multi-admin IDs, wrong-owner outbox rows, no-data monitoring behavior and previous product tests. **Live MySQL, Telegram webhook, production DOWN/RECOVERY and hosting-side SMTP delivery are not verified by this release build.**

## Limitations

Existing Telegram notification processing remains at-least-once in crash/retry cases. A previously sent notification cannot be recalled. Some provider SMTP accounts require special authentication settings. Credentials are stored in the protected settings database and should be protected via DB access and backups.
