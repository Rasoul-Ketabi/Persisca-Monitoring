# Contributing

Thank you for helping improve Monitor.

## Before you begin

- Search existing issues and pull requests for duplicates.
- Discuss broad feature changes in an issue before starting a large patch.
- Submit security issues privately using the process in [SECURITY.md](SECURITY.md).

## Development setup

Use PHP 8.1+, MySQL/MariaDB and a disposable local test database. For installation and cron setup, see [docs/INSTALLATION.md](docs/INSTALLATION.md). Do not commit real server credentials, Telegram tokens, OTP codes, customer data, `.env` files, database exports or runtime logs.

## Pull requests

1. Fork and create a branch from the current main branch.
2. Keep changes focused and preserve the native PHP / shared-hosting architecture.
3. Keep user-facing app text in English and preserve white-label settings.
4. Preserve CSRF protection, prepared SQL, strict ownership and authorization checks, private/public data isolation and CLI-only cron behavior.
5. If schema changes, update `database/schema.sql`, include a non-destructive migration and describe upgrade ordering.
6. Run PHP syntax checks and relevant CLI-only regression scripts under `tests/`. Tests do not replace live database and browser validation.
7. Update README and the relevant documentation when behavior or setup changes.
8. Explain what changed, how you tested it, and what remains unverified in the pull request.

```bash
php -l bootstrap.php
php tests/stage10c.php
```

The repository uses no Composer or npm build requirement. Please avoid adding mandatory dependencies or daemons without prior discussion.

## Style

Use the existing project structure and small services. Prefer bounded SQL queries, safe output escaping, UTC timestamps and configured timezone display. Do not introduce branding into user-facing defaults.
