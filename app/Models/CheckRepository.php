<?php
declare(strict_types=1);
namespace Monitor\Models;

use Monitor\Core\Database;
use PDO;
use Monitor\Services\IncidentManager;
use Monitor\Services\NotificationManager;
use Throwable;

/** Scheduled check history. Manual Test Now retains its Stage 02 diagnostic-only storage. */
final class CheckRepository {
    public static function due(int $limit): array {
        $query = 'SELECT * FROM monitors WHERE is_active = 1 AND (next_check_at IS NULL OR next_check_at <= UTC_TIMESTAMP()) ORDER BY next_check_at ASC, id ASC LIMIT ' . max(1, min(100, $limit));
        return Database::connection()->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Recheck at execution time in case an administrator paused or edited it. */
    public static function dueNow(int $id): ?array {
        $stmt = Database::connection()->prepare('SELECT * FROM monitors WHERE id = ? AND is_active = 1 AND (next_check_at IS NULL OR next_check_at <= UTC_TIMESTAMP()) LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Record a result and the monitor snapshot together, with no partial writes. */
    public static function saveScheduled(int $id, array $result): array|false {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            // Concurrent deletion is allowed. A deleted monitor must not be resurrected.
            $exists = $pdo->prepare('SELECT * FROM monitors WHERE id = ? FOR UPDATE');
            $exists->execute([$id]);
            $current = $exists->fetch(PDO::FETCH_ASSOC);
            if (!$current || !(int) $current['is_active']) { $pdo->rollBack(); return false; }
            $error = $result['error_message'] ? mb_safe_substr((string) $result['error_message'], 2000) : null;
            $effective = $result['effective_url'] ? mb_safe_substr((string) $result['effective_url'], 2048) : null;
            $checkedAt = (string) ($result['checked_at'] ?? gmdate('Y-m-d H:i:s'));
            $success = !empty($result['success']) ? 1 : 0;
            $http = $result['http_status'] ?: null;
            $response = $result['response_time_ms'] ?? null;
            $type = $result['error_type'] ? mb_safe_substr((string) $result['error_type'], 100) : null;
            $attempts = max(1, (int) ($result['attempts'] ?? 1));
            $insert = $pdo->prepare("INSERT INTO monitor_checks (monitor_id, check_type, success, http_status, response_time_ms, error_type, error_message, attempt_count, effective_url, checked_at, created_at) VALUES (?, 'scheduled', ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())");
            $insert->execute([$id, $success, $http, $response, $type, $error, $attempts, $effective, $checkedAt]);
            $checkId = (int) $pdo->lastInsertId();
            $snapshot = $pdo->prepare('UPDATE monitors SET last_checked_at = ?, last_check_success = ?, last_http_status = ?, last_response_time_ms = ?, last_error_type = ?, last_error_message = ?, last_attempt_count = ?, next_check_at = CASE WHEN is_active = 1 THEN DATE_ADD(UTC_TIMESTAMP(), INTERVAL check_interval_minutes MINUTE) ELSE NULL END WHERE id = ?');
            $snapshot->execute([$checkedAt, $success, $http, $response, $type, $error, $attempts, $id]);
            // The state machine is part of this same transaction: no check without its state transition.
            $transition = IncidentManager::apply($pdo, $current, $checkId, $result, $checkedAt);
            // Record the outbound event in the *same transaction* as the incident.
            // A Telegram failure or migration mismatch must never undo a real outage state.
            if (in_array($transition['transition'], ['down', 'recovered'], true)) {
                try { NotificationManager::enqueue($pdo, $current, $transition); }
                catch (Throwable $notificationError) {
                    error_log('Monitor notification enqueue failed: ' . get_class($notificationError));
                }
            }
            $pdo->commit();
            return $transition;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $error;
        }
    }

    public static function countToday(): int {
        return (int) Database::connection()->query("SELECT COUNT(*) FROM monitor_checks WHERE check_type = 'scheduled' AND checked_at >= UTC_DATE() AND checked_at < DATE_ADD(UTC_DATE(), INTERVAL 1 DAY)")->fetchColumn();
    }

    public static function recentFailed(int $limit = 6): array {
        $sql = "SELECT c.*, m.name AS monitor_name FROM monitor_checks c JOIN monitors m ON m.id = c.monitor_id WHERE c.check_type = 'scheduled' AND c.success = 0 ORDER BY c.checked_at DESC, c.id DESC LIMIT " . max(1, min(30, $limit));
        return Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function countFor(int $id): int {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM monitor_checks WHERE monitor_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }

    public static function pageFor(int $id, int $page = 1, int $pageSize = 20): array {
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $offset = ($page - 1) * $pageSize;
        $stmt = Database::connection()->prepare("SELECT * FROM monitor_checks WHERE monitor_id = ? ORDER BY checked_at DESC, id DESC LIMIT $pageSize OFFSET $offset");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
