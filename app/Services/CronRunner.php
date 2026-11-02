<?php
declare(strict_types=1);
namespace Monitor\Services;

use Monitor\Models\CheckRepository;
use Monitor\Models\Settings;
use Monitor\Models\SystemMeta;
use Monitor\Services\NotificationManager;
use Throwable;

/** Runs in a single CLI process. An advisory OS flock prevents overlapping executions. */
final class CronRunner {
    private const TARGET_SECONDS = 50;
    public static function run(): array {
        $lockPath = dirname(__DIR__, 2) . '/storage/cron-monitor.lock';
        $lock = @fopen($lockPath, 'c');
        if (!$lock) { throw new \RuntimeException('Cron lock storage is not writable.'); }
        @chmod($lockPath, 0600);
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            self::log('Another cron run is already active.');
            return ['locked' => true, 'due' => 0, 'processed' => 0, 'successful' => 0, 'failed' => 0, 'errors' => 0];
        }
        $started = microtime(true);
        $summary = ['locked' => false, 'due' => 0, 'processed' => 0, 'successful' => 0, 'failed' => 0, 'errors' => 0];
        try {
            SystemMeta::putMany([
                'cron_last_started_at' => gmdate('Y-m-d H:i:s'),
                'cron_last_status' => 'running',
                'cron_last_error' => '',
            ]);
            self::log('Cron started.');
            $batch = self::range(Settings::get('cron_batch_size', '25'), 1, 100, 25);
            $due = CheckRepository::due($batch);
            $summary['due'] = count($due);
            foreach ($due as $row) {
                // Soft guard: never abort a check mid-request. A single lengthy check can exceed the target.
                if ($summary['processed'] > 0 && microtime(true) - $started >= self::TARGET_SECONDS) { break; }
                $id = (int) $row['id'];
                try {
                    $current = CheckRepository::dueNow($id);
                    if (!$current) { continue; }
                    try { $result = HttpMonitor::check($current); }
                    catch (Throwable $e) {
                        self::log('Checker exception for monitor #' . $id . ': ' . get_class($e));
                        $result = [
                            'success' => false, 'http_status' => null, 'response_time_ms' => null,
                            'effective_url' => '', 'error_type' => 'Check Exception',
                            'error_message' => 'The check could not be completed.',
                            'attempts' => 1, 'checked_at' => gmdate('Y-m-d H:i:s'),
                        ];
                    }
                    $transition = CheckRepository::saveScheduled($id, $result);
                    if ($transition !== false) {
                        // The check transaction stores transitions and queues notification events.
                        $summary['processed']++;
                        $summary[$result['success'] ? 'successful' : 'failed']++;
                    }
                } catch (Throwable $e) {
                    $summary['errors']++;
                    self::log('Processing failed for monitor #' . $id . ': ' . get_class($e));
                }
            }
            // Outbox delivery is independent of due-monitor counts. Never let it block check state.
            try {
                if (microtime(true) - $started < self::TARGET_SECONDS) {
                    NotificationManager::deliverDue(3, $started + self::TARGET_SECONDS);
                }
            }
            catch (Throwable $deliveryError) { self::log('Notification dispatch failed: ' . get_class($deliveryError)); }
            try { HourlyAggregator::process(1500); }
            catch (Throwable $analyticsError) { self::log('Hourly aggregation failed: ' . get_class($analyticsError)); }
            try { RetentionManager::runIfDue(); }
            catch (Throwable $cleanupError) { self::log('Retention cleanup failed: ' . get_class($cleanupError)); }
            $state = $summary['errors'] ? 'partial' : 'success';
            SystemMeta::putMany([
                'cron_last_completed_at' => gmdate('Y-m-d H:i:s'),
                'cron_last_status' => $state,
                'cron_last_processed_count' => $summary['processed'],
                'cron_last_error' => $summary['errors'] ? 'Some monitor checks could not be saved. Review the cron log.' : '',
            ]);
            self::log('Cron completed: ' . $summary['processed'] . ' processed, ' . $summary['errors'] . ' errors.');
            return $summary;
        } catch (Throwable $e) {
            self::log('Cron failed: ' . get_class($e));
            try {
                SystemMeta::putMany([
                    'cron_last_completed_at' => gmdate('Y-m-d H:i:s'),
                    'cron_last_status' => 'failed',
                    'cron_last_processed_count' => $summary['processed'],
                    'cron_last_error' => 'Cron encountered an application error.',
                ]);
            } catch (Throwable $secondary) { self::log('Failed to write cron failure state.'); }
            throw $e;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public static function health(?string $lastCompleted, ?string $status, int $staleAfterMinutes = 10): string {
        if (!$lastCompleted) { return $status === 'running' ? 'Running (first run)' : 'Never Run'; }
        if ($status === 'failed' || $status === 'partial') { return 'Last Run Failed'; }
        $age = time() - (strtotime($lastCompleted . ' UTC') ?: 0);
        if ($age < 0) { return 'Clock mismatch'; }
        if ($age > $staleAfterMinutes * 120) { return 'Stale'; }
        if ($age > $staleAfterMinutes * 60) { return 'Delayed'; }
        return $status === 'running' ? 'Running' : 'Healthy';
    }

    private static function range(string $value, int $min, int $max, int $default): int {
        return ctype_digit($value) ? min($max, max($min, (int) $value)) : $default;
    }

    private static function log(string $message): void {
        $file = dirname(__DIR__, 2) . '/storage/logs/monitor-cron.log';
        @file_put_contents($file, '[' . gmdate('Y-m-d H:i:s') . ' UTC] ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
