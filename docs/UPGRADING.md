# Upgrading Monitor

Current repository version: **v0.2.2**. The maintainer reports successful deployment and testing; new upgrades should still be validated against backups on each hosting environment.

## Rules for every upgrade

1. Back up the existing **database and site files**, including private server configuration. Store backups outside public web directories.
2. Record the exact installed version in the admin System Health or `system_meta.installed_version`.
3. Stop the Cron scheduled task for the maintenance window.
4. Review every SQL migration required between your exact installed version and target version. Do not skip intermediate schema migrations when jumping across releases.
5. Follow the ordering of the specific release. When a new column is required by the new code, apply its SQL **before** deploying PHP. Do not repeat already applied ALTER migrations.
6. Extract the version-specific Patch ZIP over the existing document root, not inside an extra folder. Do not overwrite `config/database.php`, `storage/installed.lock`, uploads or runtime data.
7. Restore Cron, log in and complete the live verification checklist.
8. If something fails, restore **both** matching database and file backups rather than running the installer.

## v0.2.1 -> v0.2.2

- Apply `database/patches/v0.2.2.sql` **once**, while the old application is still installed.
- Deploy the corresponding updated source files or the official `monitor-patch-v0.2.2.zip` from a release.
- Confirm `/admin/notifications/`, `/account/telegram/` and `/telegram/webhook/` are routed correctly.
- Register the webhook as Super Admin, then test a Portal User link and controlled incident notifications.
- Check the migration did not alter existing admin-global notification recipients, old portal sessions, monitor ownership or public status pages.

This repository includes earlier historical SQL patch files for reference, but they are not a one-click universal updater. **Never import them blindly or run `database/schema.sql` against an existing installation.**

## Fresh installs

Always use the full `database/schema.sql` through the installer, not the upgrade SQL files. See [INSTALLATION.md](INSTALLATION.md).
