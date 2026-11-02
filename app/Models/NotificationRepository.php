<?php
declare(strict_types=1);
namespace Monitor\Models;

use Monitor\Core\Database;
use PDO;

final class NotificationRepository {
    /** Always provide a valid filter, including requests without ?filter=. */
    public static function normalizeFilter(mixed $requested): string {
        return is_string($requested) && in_array($requested, ['all', 'sent', 'failed', 'pending'], true)
            ? $requested : 'all';
    }
    public static function count(string $filter = 'all'): int {
        $where = self::where($filter);
        $stmt = Database::connection()->query('SELECT COUNT(*) FROM notification_logs l WHERE ' . $where);
        return (int) $stmt->fetchColumn();
    }
    private static function where(string $filter): string {
        return match ($filter) {
            'sent' => "l.status = 'sent'",
            'failed' => "l.status = 'failed'",
            'pending' => "l.status IN ('pending','processing')",
            default => '1 = 1',
        };
    }
    public static function page(string $filter, int $page, int $limit = 20): array {
        $where = self::where($filter);
        $limit = max(1, min(100, $limit));
        $offset = max(0, ($page - 1) * $limit);
        return Database::connection()->query('SELECT l.*, m.name AS monitor_name, p.name AS recipient_name FROM notification_logs l '
            . 'JOIN monitors m ON m.id = l.monitor_id LEFT JOIN portal_users p ON p.id=l.recipient_id AND l.recipient_type=\'portal_user\' WHERE ' . $where
            . ' ORDER BY l.created_at DESC, l.id DESC LIMIT ' . $limit . ' OFFSET ' . $offset)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function forIncident(int $incidentId): array {
        $stmt = Database::connection()->prepare('SELECT channel, recipient_type, destination_chat_id, event_type, status, attempt_count, sent_at FROM notification_logs WHERE incident_id = ? ORDER BY id');
        $stmt->execute([$incidentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function recentFailureCount(): int {
        return (int) Database::connection()->query("SELECT COUNT(*) FROM notification_logs WHERE status = 'failed' AND updated_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)")->fetchColumn();
    }
}
