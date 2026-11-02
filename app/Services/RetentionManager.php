<?php
declare(strict_types=1);
namespace Monitor\Services;

use Monitor\Core\Database;
use Monitor\Models\Settings;
use Monitor\Models\SystemMeta;
use PDO;

final class RetentionManager {
    public static function validDays(string $value, int $fallback = 90): int {
        return ctype_digit($value) && (int)$value>=7 && (int)$value<=3650 ? (int)$value : $fallback;
    }
    public static function runIfDue(): array { return self::run(false); }
    public static function runNow(): array { return self::run(true); }
    private static function run(bool $force): array {
        $meta=SystemMeta::getMany(['last_retention_cleanup_at','hourly_aggregated_check_id']);
        $last=$meta['last_retention_cleanup_at'];
        if (!$force && $last && strtotime($last.' UTC') > time()-86400) { return ['skipped'=>true,'checks'=>0,'notifications'=>0]; }
        $pdo=Database::connection();
        $days=self::validDays(Settings::get('check_retention_days','90'));
        $notificationDays=self::validDays(Settings::get('notification_log_retention_days','90'));
        $safeId=(int)($meta['hourly_aggregated_check_id']??0);
        $q=$pdo->prepare("SELECT id FROM monitor_checks WHERE check_type='scheduled' AND id<=? AND checked_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? DAY) ORDER BY checked_at ASC LIMIT 1000");
        $q->bindValue(1,$safeId,PDO::PARAM_INT);$q->bindValue(2,$days,PDO::PARAM_INT);$q->execute();
        $ids=$q->fetchAll(PDO::FETCH_COLUMN);
        if ($ids) { $holders=implode(',',array_fill(0,count($ids),'?'));$pdo->prepare("DELETE FROM monitor_checks WHERE id IN ($holders)")->execute($ids); }
        // Only resolved incident notifications can expire: active incidents keep deduplication state.
        $q=$pdo->prepare("SELECT l.id FROM notification_logs l JOIN incidents i ON i.id=l.incident_id WHERE i.status='resolved' AND l.status IN ('sent','failed') AND l.created_at<DATE_SUB(UTC_TIMESTAMP(),INTERVAL ? DAY) ORDER BY l.created_at LIMIT 1000");
        $q->bindValue(1,$notificationDays,PDO::PARAM_INT);$q->execute();$notificationIds=$q->fetchAll(PDO::FETCH_COLUMN);
        if ($notificationIds) { $holders=implode(',',array_fill(0,count($notificationIds),'?'));$pdo->prepare("DELETE FROM notification_logs WHERE id IN ($holders)")->execute($notificationIds); }
        // OTP requests/codes are short-lived. Purge in bounded batches during daily retention.
        $pdo->exec("DELETE FROM portal_otp_requests WHERE requested_at<UTC_TIMESTAMP()-INTERVAL 1 DAY LIMIT 1000");
        $pdo->exec("DELETE FROM portal_otp_codes WHERE (consumed_at IS NOT NULL OR expires_at<UTC_TIMESTAMP()) AND created_at<UTC_TIMESTAMP()-INTERVAL 1 DAY LIMIT 1000");
        // Bounded backlog continues on the next cron run until fewer than one full batch remain.
        if (count($ids) < 1000 && count($notificationIds) < 1000) { SystemMeta::putMany(['last_retention_cleanup_at'=>gmdate('Y-m-d H:i:s')]); }
        return ['skipped'=>false,'checks'=>count($ids),'notifications'=>count($notificationIds)];
    }
}
