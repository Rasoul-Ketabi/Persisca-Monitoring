# Frequently Asked Questions

### Is Monitor free and open source?

Yes. Monitor is published under the [MIT License](../LICENSE). You may use, modify, and redistribute the source, including for commercial purposes, as long as the copyright and license notices are retained.

### Does GitHub run my monitor?

No. GitHub hosts the source code and release downloads. The software runs on your own hosting server with PHP and MySQL/MariaDB, plus a scheduled CLI cron job.

### Is Composer or Docker required?

No. The application uses native PHP and standard hosting features. A supported database and PHP extensions are required.

### Will it check sites every minute?

The cron task can run every minute. Each monitor's configured interval determines when it is checked. A monitor with a five-minute interval does not necessarily run every minute.

### How are uptime percentages calculated?

They describe successful *observed scheduled checks*, not continuous service availability. Time without checks does not count as proof of uptime.

### How do users sign in?

Super Admin and Admin accounts use passwords. Portal Users sign in using emailed six-digit one-time codes. The server must deliver outbound mail correctly.

### Can multiple people receive Telegram alerts?

Global notifications support up to ten Telegram chat destinations. A Portal User can also connect one private Telegram account to receive alerts for their own monitors.

### Can customers publish status pages?

Yes. Platform monitors are shown at `/status/`, while each enabled Portal User can publish their own public monitors at `/status/{slug}/`. Private monitors remain hidden.

### Why is there a 500 error after upgrading?

Check that the PHP code and database schema are at matching versions, and review private PHP error logs. Never run the installation wizard against an existing database. For upgrades see [UPGRADING.md](UPGRADING.md).

### Can I install from the GitHub **Code > Download ZIP** option?

Yes, but that ZIP normally adds a repository-name wrapper directory. Extract its **contents** into the website document root. The `monitor-install-v0.2.2.zip` release asset is more convenient because the app files are already at the archive root.

### Can I get support or report a vulnerability?

Open a GitHub Issue for normal bugs and feature requests. Follow [SECURITY.md](../SECURITY.md) for private vulnerability reports. Do not publish server secrets or user data in tickets.
