<?php
use Monitor\Models\SystemMeta;
use Monitor\Models\Settings;
use Monitor\Services\CronRunner;
$checks = [
    ['Application version', trim((string) file_get_contents(monitor_root() . '/VERSION')), true],
    ['PHP runtime', PHP_VERSION, version_compare(PHP_VERSION, '8.1', '>=')],
    ['Database connection', 'Connected', true],
    ['PDO MySQL extension', extension_loaded('pdo_mysql') ? 'Available' : 'Missing', extension_loaded('pdo_mysql')],
    ['cURL extension', extension_loaded('curl') ? 'Available' : 'Missing', extension_loaded('curl')],
    ['Configuration file', is_file(monitor_root() . '/config/database.php') ? 'Protected file present' : 'Missing', is_file(monitor_root() . '/config/database.php')],
    ['Installer lock', is_file(monitor_root() . '/storage/installed.lock') ? 'Locked' : 'Unlocked', is_file(monitor_root() . '/storage/installed.lock')],
    ['Storage writable', is_writable(monitor_root() . '/storage') ? 'Writable' : 'Not writable', is_writable(monitor_root() . '/storage')],
    ['Log directory writable', is_writable(monitor_root() . '/storage/logs') ? 'Writable' : 'Not writable', is_writable(monitor_root() . '/storage/logs')],
    ['Cron entry', is_file(monitor_root() . '/cron/monitor.php') ? 'Available (CLI only)' : 'Missing', is_file(monitor_root() . '/cron/monitor.php')],
    ['Folder routes', is_file(monitor_root() . '/admin/monitors/view/index.php') && is_file(monitor_root() . '/status/index.php') ? 'Entry points present' : 'Route missing', is_file(monitor_root() . '/admin/monitors/view/index.php') && is_file(monitor_root() . '/status/index.php')],
    ['Uploads writable', is_writable(monitor_root() . '/public/assets/uploads') ? 'Writable' : 'Not writable', is_writable(monitor_root() . '/public/assets/uploads')],
];
try { \Monitor\Core\Database::connection()->query('SELECT 1'); }
catch (Throwable $e) { $checks[2] = ['Database connection', 'Unavailable', false]; }
foreach (['monitors' => 'Monitor table', 'monitor_checks' => 'Check history table', 'incidents' => 'Incident engine table', 'monitor_hourly_stats' => 'Hourly analytics table'] as $table => $label) {
    try { \Monitor\Core\Database::connection()->query("SELECT 1 FROM $table LIMIT 1"); $checks[] = [$label, 'Available', true]; }
    catch (Throwable $e) { $checks[] = [$label, 'Database table unavailable; check the current migration', false]; }
}
$meta = SystemMeta::getMany(['cron_last_started_at','cron_last_completed_at','cron_last_status','cron_last_processed_count','cron_last_error','last_retention_cleanup_at','hourly_last_aggregated_at','hourly_aggregated_check_id']);
$telegramEnabled = Settings::get('telegram_enabled') === '1';
try { $telegramDestinations = \Monitor\Services\TelegramDestinations::parse(Settings::get('telegram_chat_id')); }
catch (\InvalidArgumentException $e) { $telegramDestinations = []; }
$telegramReady = Settings::get('telegram_bot_token') !== '' && (bool) $telegramDestinations;
try { $recentDeliveryFailures = \Monitor\Models\NotificationRepository::recentFailureCount(); }
catch (Throwable $unused) { $recentDeliveryFailures = -1; }
$health = CronRunner::health($meta['cron_last_completed_at'], $meta['cron_last_status'], (int) Settings::get('cron_stale_after_minutes', '10'));
$healthy = in_array($health, ['Healthy', 'Running'], true);
?>
<div class="system-grid"><section class="panel"><div class="panel-header"><div><h2>Environment overview</h2><p>Safe health checks for your installation.</p></div><span class="pill pill-success">Installed</span></div><div class="health-list"><?php foreach ($checks as [$name,$value,$ok]): ?><div class="health-item"><span><?= e($name) ?></span><div><strong><?= e((string) $value) ?></strong><span class="pill <?= $ok ? 'pill-success' : 'pill-danger' ?>"><?= $ok ? 'OK' : 'Check' ?></span></div></div><?php endforeach; ?></div></section>
<aside class="panel system-aside"><div class="panel-header"><div><h2>Cron health</h2><p>Server-scheduled monitoring</p></div><span class="pill <?= $healthy ? 'pill-success' : 'pill-danger' ?>"><?= e($health) ?></span></div>
<div class="health-list">
<?php foreach ([['Last Started',display_date($meta['cron_last_started_at'])],['Last Completed',display_date($meta['cron_last_completed_at'])],['Last Run Result',$meta['cron_last_status'] ?: 'Never Run'],['Monitors Processed',$meta['cron_last_processed_count'] ?? '0'],['Last Error Summary',$meta['cron_last_error'] ?: 'None']] as [$name,$value]): ?><div class="health-item"><span><?= e($name) ?></span><div><strong><?= e((string) $value) ?></strong></div></div><?php endforeach; ?>
</div><div class="divider"></div><span class="eyebrow">TELEGRAM</span>
<div class="health-list">
<?php foreach ([['Notifications', $telegramEnabled ? 'Enabled' : 'Disabled'],['Bot token', Settings::get('telegram_bot_token') !== '' ? 'Configured' : 'Missing'],['Telegram destinations', count($telegramDestinations) . ' configured'],['Delivery failures (24h)', $recentDeliveryFailures < 0 ? 'Unavailable' : (string) $recentDeliveryFailures]] as [$name,$value]): ?><div class="health-item"><span><?= e($name) ?></span><strong><?= e((string) $value) ?></strong></div><?php endforeach; ?></div>
<a class="monitor-back-link" href="<?= e(admin_url('notifications')) ?>">Notification delivery →</a>
<div class="divider"></div><span class="eyebrow">ANALYTICS & RETENTION</span><div class="health-list">
<?php foreach ([['Check retention', Settings::get('check_retention_days','90').' days'],['Delivery log retention',Settings::get('notification_log_retention_days','90').' days'],['Last daily cleanup',display_date($meta['last_retention_cleanup_at'])],['Hourly stats updated',display_date($meta['hourly_last_aggregated_at'])],['Aggregation cursor',($meta['hourly_aggregated_check_id']??'Not started')]] as [$name,$value]): ?><div class="health-item"><span><?= e($name) ?></span><strong><?= e((string)$value) ?></strong></div><?php endforeach; ?></div>
<div class="divider"></div><span class="eyebrow">CRON SETUP</span><p>Set a scheduled task in Plesk or cPanel to run the CLI file <code>cron/monitor.php</code> every minute, or as often as your hosting allows. Never use the public URL as a cron command.</p><p class="field-help">Current settings: <?= (int) Settings::get('cron_batch_size', '25') ?> checks max per run, <?= (int) Settings::get('check_retention_days', '90') ?> days of raw check history. Change in General Settings.</p></aside></div>

<div class="panel" style="margin-top:16px;padding:18px"><h2>Operations</h2><p>Account security and maintenance controls are available to Super Admins.</p><?php if (\Monitor\Services\Authorization::isSuperAdmin($user)): ?><a class="button secondary" href="<?= e(admin_url('maintenance')) ?>">Open Maintenance</a><?php else: ?><span class="pill pill-neutral">Super Admin access required</span><?php endif; ?></div>
