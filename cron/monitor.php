<?php
declare(strict_types=1);
// A browser request must never start the monitoring worker.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('MONITOR_CRON', true);
require dirname(__DIR__) . '/bootstrap.php';

use Monitor\Services\CronRunner;

if (!monitor_installed()) { fwrite(STDERR, "Monitor is not installed.\n"); exit(1); }
try {
    $result = CronRunner::run();
    echo 'Monitor Cron ' . trim((string) file_get_contents(monitor_root() . '/VERSION')) . PHP_EOL;
    if ($result['locked']) { echo "Another cron run is active. Skipped.\n"; exit(0); }
    echo 'Due monitors (selected): ' . $result['due'] . PHP_EOL;
    echo 'Processed: ' . $result['processed'] . PHP_EOL;
    echo 'Successful: ' . $result['successful'] . PHP_EOL;
    echo 'Failed: ' . $result['failed'] . PHP_EOL;
    echo 'Processing errors: ' . $result['errors'] . PHP_EOL;
    echo "Completed.\n";
    exit($result['errors'] ? 1 : 0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Cron failed (' . get_class($exception) . '). Check database migration and storage permissions.' . PHP_EOL);
    exit(1);
}
