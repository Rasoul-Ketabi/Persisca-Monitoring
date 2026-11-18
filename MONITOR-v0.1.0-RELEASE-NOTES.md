# Monitor v0.1.0 — Release Notes

**Version:** v0.1.0  
**Stage:** 09, Production Hardening and QA  
**Status:** **Release Candidate — Live Host Verification Required**

## Summary

First consolidated production release candidate built cumulatively on accepted v0.0.8, retaining all previous stages. This is a stability and security update, not a new feature release.

## Features included

- One-time installer, white-label branding and Open Graph metadata
- Multiple Admin / Super Admin accounts with RBAC, session invalidation and 8-character minimum passwords
- HTTP/HTTPS monitors, scheduled CLI cron, checks/retries/history and operational health
- Confirmed incident opening, recovery and incident history
- Telegram DOWN/RECOVERY notifications to up to 10 destinations, per-destination logs and bounded retries
- 24h/7d/30d observed availability, response-time charts and hourly aggregates
- Bounded raw-check/notification retention, public service status/history and maintenance controls

## v0.1.0 improvements

1. Fresh installer refuses a database with existing tables **before** creating any schema objects.
2. Super Admin restriction for enabling/operating private-network monitor targets; authorization precedes user target lookups.
3. Cron sends at most three queued Telegram messages per run within its soft time budget; pending deliveries continue in later runs.
4. Dashboard recent monitors use a bounded SQL query.
5. Generic safe 403/404/500 handling and cleaner System Health messages.
6. Every test script is CLI-only, even if a server ignores `.htaccess`.
7. Documentation refreshed, previous regression suites re-run and release packaging checked for runtime files and permissions.

## Upgrade from v0.0.8

Back up files and DB, pause cron, extract `monitor-patch-v0.1.0.zip` into current document root with overwrite, then run `monitor-sql-patch-v0.1.0.sql` **once**. Resume cron and verify via System Health, Notifications, Users, Maintenance and `/status/`. **Do not reinstall.** Existing config, uploads, users, roles, Telegram tokens, monitors, incidents and history are preserved. Release SQL updates the version metadata only.

For a fresh installation extract the FULL ZIP into a new document root and use an entirely empty database. Visit `/install/` and set the Super Admin, then configure cron.

## Cron

Schedule `cron/monitor.php` as a **CLI task every minute**, or shortest supported host interval. Example Plesk PHP executable: `/opt/plesk/php/8.2/bin/php`; the full path to your installation differs by host.

## Security and limitations

Protect `config/database.php`, `storage/installed.lock`, user uploads, DB backups and Telegram credentials. Test files are CLI-only; ensure web-server protections are enforced by nginx if relevant. Monitor availability percentages reflect **sample checks**, not unobserved minutes. Telegram Bot API does not guarantee mathematically exactly-once delivery across abrupt crashes.

## Verification

Code-level regression checks: **525/525 passed** (473 existing + 52 release checks), PHP syntax validation of packaged scripts completed. This is **not** a claim of live MySQL, cURL, Telegram, cron scheduling, mobile-browser or crawler tests: those remain to be verified on the host before labeling the release ready.
