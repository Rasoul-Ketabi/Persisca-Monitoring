<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap.php';

use Monitor\Services\MonitorInput;
use Monitor\Core\Security;

$checks = [];
$assert = static function (bool $pass, string $name) use (&$checks): void { $checks[] = [$pass, $name]; };
$rejects = static function (callable $call): bool {
    try { $call(); return false; } catch (InvalidArgumentException $e) { return true; }
};
$root = monitor_root();
$assert(trim((string) file_get_contents($root . '/VERSION')) === 'v0.2.2', 'Version matches v0.0.8');
$assert(!monitor_installed() && !monitor_install_blocked(), 'Packaged source has no installed configuration');
$assert(is_file($root . '/public/assets/images/status-og-default.jpg'), 'Default Open Graph image remains');
$assert(is_file($root . '/database/patches/v0.0.2.sql'), 'SQL migration is bundled');
$assert(is_file($root . '/database/patches/v0.0.3.sql'), 'Stage 03 upgrade SQL is bundled');
$schema = (string) file_get_contents($root . '/database/schema.sql');
$sqlPatch = (string) file_get_contents($root . '/database/patches/v0.0.4.sql');
$assert(str_contains($schema, 'CREATE TABLE IF NOT EXISTS monitor_checks'), 'Fresh install has check history table');
$assert(str_contains($schema, 'last_checked_at DATETIME NULL'), 'Fresh install includes last scheduled snapshot');
$assert(str_contains($schema, 'next_check_at DATETIME NULL'), 'Fresh install includes due scheduling');
$assert(str_contains($schema, 'idx_monitors_due (is_active, next_check_at)'), 'Due query uses composite index');
$assert(str_contains($schema, 'idx_checks_monitor_date (monitor_id, checked_at)'), 'History uses composite index');
$assert(str_contains($schema, 'ON DELETE CASCADE'), 'Check history cascades on monitor deletion');
$assert(str_contains($sqlPatch, 'ALTER TABLE monitors') && !str_contains($sqlPatch, 'DROP TABLE'), 'Upgrade migration is non-destructive');
$assert(str_contains($sqlPatch, "('installed_version', 'v0.0.4'"), 'Upgrade metadata updated');
$assert(str_contains((string) file_get_contents($root . '/install/index.php'), "['installed_version', 'v0.2.2']"), 'Fresh installer version updated');
$assert(is_file($root . '/admin/monitors/view/index.php'), 'Folder-style monitor history route exists');
$assert(is_file($root . '/app/Views/admin/monitor-detail.php'), 'Monitor history view exists');
$assert(is_file($root . '/app/Models/CheckRepository.php'), 'Scheduled check persistence model exists');
$assert(is_file($root . '/app/Services/CronRunner.php'), 'Cron service exists');
$assert(str_contains((string) file_get_contents($root . '/cron/monitor.php'), "PHP_SAPI !== 'cli'"), 'Cron endpoint is CLI only');
$assert(str_contains((string) file_get_contents($root . '/app/Services/CronRunner.php'), 'LOCK_EX | LOCK_NB'), 'Cron uses non-blocking file lock');
$assert(str_contains((string) file_get_contents($root . '/app/Models/CheckRepository.php'), 'FOR UPDATE'), 'Scheduled check persistence locks monitor row');
$assert(str_contains((string) file_get_contents($root . '/app/Models/CheckRepository.php'), 'beginTransaction()'), 'Check and snapshot save atomically');
$assert(str_contains((string) file_get_contents($root . '/app/Models/CheckRepository.php'), 'WHERE is_active = 1 AND (next_check_at IS NULL'), 'Only active due monitors selected');
$assert(str_contains((string) file_get_contents($root . '/app/Models/CheckRepository.php'), 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL check_interval_minutes MINUTE)'), 'Next check scheduled relative to completion');
$assert(str_contains((string) file_get_contents($root . '/app/Models/CheckRepository.php'), "'scheduled'"), 'Scheduled history explicitly typed');
$assert(str_contains((string) file_get_contents($root . '/app/Models/MonitorRepository.php'), 'next_check_at = NULL'), 'Monitor update and resume resets schedule');
$assert(str_contains((string) file_get_contents($root . '/app/Services/CronRunner.php'), 'HttpMonitor::check($current)'), 'Scheduled checks reuse Stage 02 HTTP service');
$assert(str_contains((string) file_get_contents($root . '/app/Views/admin/dashboard.php'), 'Active Incidents'), 'Dashboard shows real incident counts');
$assert(str_contains((string) file_get_contents($root . '/app/Views/admin/system.php'), 'Cron health'), 'System Health includes cron status');
$assert(str_contains((string) file_get_contents($root . '/app/Views/admin/monitor-detail.php'), 'historyPages'), 'History supports pagination');
$assert(str_contains((string) file_get_contents($root . '/app/Models/Settings.php'), "'check_retention_days' => '90'"), 'Retention default is 90 days');
$assert(!str_contains((string) file_get_contents($root . '/app/Views/admin/dashboard.php'), '99.99%'), 'Dashboard does not invent uptime');
$assert(\Monitor\Services\CronRunner::health(null, null) === 'Never Run', 'Cron health handles never-run state');
$assert(\Monitor\Services\CronRunner::health(gmdate('Y-m-d H:i:s',time()-7200), 'success', 10) === 'Stale', 'Cron health detects stale scheduler');
$assert(\Monitor\Services\CronRunner::health(gmdate('Y-m-d H:i:s'), 'partial', 10) === 'Last Run Failed', 'Cron health detects partial error');
$assert(\Monitor\Services\CronRunner::health(gmdate('Y-m-d H:i:s'), 'success', 10) === 'Healthy', 'Cron health detects recent completion');
$lock = fopen($root . '/storage/cron-monitor.lock', 'c');
if ($lock && flock($lock, LOCK_EX | LOCK_NB)) {
    $busy = \Monitor\Services\CronRunner::run();
    $assert($busy['locked'] === true && $busy['processed'] === 0, 'Global lock prevents concurrent cron even without DB');
    flock($lock, LOCK_UN);
    fclose($lock);
    @unlink($root . '/storage/cron-monitor.lock');
} else { $assert(false, 'Global lock could not be tested'); }

$assert(str_contains((string) file_get_contents($root . '/database/schema.sql'), 'CREATE TABLE IF NOT EXISTS monitors'), 'Fresh schema includes monitors');
$assert(str_contains((string) file_get_contents($root . '/database/schema.sql'), 'role VARCHAR'), 'Super Admin-compatible role column preserved');
$assert(str_contains(Security::csrfField(), 'name="_token"'), 'CSRF field remains available');
$assert(e('<script>') === '&lt;script&gt;', 'HTML escaping remains active');
$assert(MonitorInput::url(' https://example.com/health ') === 'https://example.com/health', 'HTTPS URL validation and trimming');
$assert(MonitorInput::url('http://localhost:8080/health') === 'http://localhost:8080/health', 'Private URL syntax supported for explicit opt-in');
$assert($rejects(static fn () => MonitorInput::url('file:///etc/passwd')), 'Non-HTTP schemes rejected');
$assert($rejects(static fn () => MonitorInput::url('http://user:pass@example.com')), 'Embedded URL credentials rejected');
$assert($rejects(static fn () => MonitorInput::url('http://example.com/#secret')), 'URL fragments rejected');
$assert($rejects(static fn () => MonitorInput::url('http://127.0.0.1/ evil')), 'Whitespace in URL rejected');
$assert(MonitorInput::codes(' 301, 200,204,200 ') === '200,204,301', 'Expected status codes normalized');
$assert($rejects(static fn () => MonitorInput::codes('200-399')), 'Unsupported status ranges rejected');
$assert($rejects(static fn () => MonitorInput::codes('200,700')), 'Invalid HTTP codes rejected');
$good = ['name'=>'API', 'url'=>'https://example.org/', 'is_active'=>'1', 'is_public'=>'1', 'check_interval_minutes'=>'5', 'timeout_seconds'=>'10', 'expected_status_codes'=>'200,204', 'retry_count'=>'2', 'retry_delay_seconds'=>'2'];
$input = MonitorInput::fromPost($good);
$assert($input['check_interval_minutes'] === 5 && $input['is_active'] === 1 && $input['allow_private_network'] === 0, 'Monitor defaults and configuration accepted');
$assert($rejects(static fn () => MonitorInput::fromPost(array_replace($good, ['timeout_seconds'=>'0']))), 'Out-of-range timeout rejected');
$assert($rejects(static fn () => MonitorInput::fromPost(array_replace($good, ['retry_count'=>'7']))), 'Out-of-range retries rejected');
$assert($rejects(static fn () => MonitorInput::fromPost(array_replace($good, ['check_interval_minutes'=>'4']))), 'Unsupported check interval rejected');
$assert($rejects(static fn () => MonitorInput::fromPost(array_replace($good, ['url'=>'javascript:alert(1)']))), 'Malicious URL rejected in form input');
$assert(is_file($root . '/admin/monitors/add/index.php') && is_file($root . '/admin/monitors/edit/index.php'), 'Real folder-style monitor entry points exist');
foreach (['/monitor/admin/index.php' => '/monitor', '/monitor/admin/monitors/add/index.php' => '/monitor', '/monitor/admin/settings/general/index.php' => '/monitor', '/monitor/status/index.php' => '/monitor', '/index.php' => ''] as $script => $expected) {
    $_SERVER['SCRIPT_NAME'] = $script;
    $assert(monitor_base_path() === $expected, 'Path base for ' . $script);
}
$_SERVER['SCRIPT_NAME'] = '/monitor/admin/monitors/index.php';
$assert(admin_url('monitors') === '/monitor/admin/monitors/', 'Monitor link is physical path');
$assert(admin_url('monitors/edit') === '/monitor/admin/monitors/edit/', 'Edit path is physical path');
$assert(admin_url('settings/general') === '/monitor/admin/settings/general/', 'General Settings uses path routing');
$assert(status_url() === '/monitor/status/', 'Status URL is a folder path');
$assert(!str_contains(admin_url('settings/branding'), '?page='), 'No visible parameter router');
$resolver = new ReflectionMethod(\Monitor\Services\HttpMonitor::class, 'resolve');
$assert((bool) $resolver->invoke(null, 'https://8.8.8.8/', false), 'Public IP allowed for manual testing');
$assert($rejects(static fn () => $resolver->invoke(null, 'http://127.0.0.1/', false)), 'Localhost IP blocked by default');
$assert($rejects(static fn () => $resolver->invoke(null, 'http://192.168.1.25/', false)), 'Private IPv4 blocked by default');
$assert((bool) $resolver->invoke(null, 'http://127.0.0.1/', true), 'Private IP permitted by explicit opt-in');
$assert($rejects(static fn () => $resolver->invoke(null, 'http://[::1]/', false)), 'IPv6 loopback blocked by default');
if (!extension_loaded('curl')) {
    $offline = \Monitor\Services\HttpMonitor::check(['url'=>'https://example.com/', 'timeout_seconds'=>2, 'retry_count'=>0, 'retry_delay_seconds'=>0, 'expected_status_codes'=>'200']);
    $assert($offline['error_type'] === 'Environment Error' && $offline['attempts'] === 1, 'Checker explains missing PHP cURL extension');
}
$assert(str_contains((string) file_get_contents($root . '/app/Services/HttpMonitor.php'), 'CURLOPT_SSL_VERIFYPEER => true'), 'SSL verification not disabled');
$assert(str_contains((string) file_get_contents($root . '/app/Services/HttpMonitor.php'), 'CURLOPT_RESOLVE'), 'Resolved target pinned for network policy');
$assert(str_contains((string) file_get_contents($root . '/app/Views/admin/monitors.php'), 'data-confirm'), 'Delete confirmation is present');
$assert(str_contains((string) file_get_contents($root . '/admin/_dispatch.php'), 'Security::requireCsrf()'), 'Monitor POST handlers validate CSRF');

$passed = 0;
foreach ($checks as [$pass, $name]) { echo ($pass ? 'PASS ' : 'FAIL ') . $name . PHP_EOL; if ($pass) { $passed++; } }
echo "$passed/" . count($checks) . " passed" . PHP_EOL;
exit($passed === count($checks) ? 0 : 1);
