<?php
declare(strict_types=1);
namespace Monitor\Models;

use Monitor\Core\Database;
use PDO;

final class MonitorRepository {
    private const COLUMNS = [
        'name', 'url', 'is_active', 'is_public', 'allow_private_network', 'check_interval_minutes',
        'timeout_seconds', 'expected_status_codes', 'retry_count', 'retry_delay_seconds', 'failure_threshold', 'telegram_notifications_enabled',
    ];

    public static function all(): array {
        return Database::connection()->query('SELECT m.*, p.name AS owner_name FROM monitors m LEFT JOIN portal_users p ON p.id=m.portal_user_id ORDER BY m.created_at DESC, m.id DESC')->fetchAll(PDO::FETCH_ASSOC);
    }
    /** Dashboard previews do not need to materialize every monitor. */
    public static function recent(int $limit = 5): array {
        $limit = max(1, min(50, $limit));
        return Database::connection()->query('SELECT m.*, p.name AS owner_name FROM monitors m LEFT JOIN portal_users p ON p.id=m.portal_user_id ORDER BY m.created_at DESC, m.id DESC LIMIT ' . $limit)->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function find(int $id): ?array {
        $stmt = Database::connection()->prepare('SELECT * FROM monitors WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public static function counts(): array {
        $row = Database::connection()->query('SELECT COUNT(*) AS total, COALESCE(SUM(is_active = 1),0) AS active, COALESCE(SUM(is_active = 0),0) AS paused, COALESCE(SUM(is_public = 1 AND portal_user_id IS NULL),0) AS public_count FROM monitors')->fetch(PDO::FETCH_ASSOC);
        return array_map('intval', $row);
    }
    public static function create(array $data, ?int $portalUserId = null): int {
        $columns = implode(', ', self::COLUMNS);
        $holders = implode(', ', array_fill(0, count(self::COLUMNS), '?'));
        $stmt = Database::connection()->prepare("INSERT INTO monitors ($columns, portal_user_id, created_at, updated_at) VALUES ($holders, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())");
        $stmt->execute([...array_map(static fn ($column) => $data[$column], self::COLUMNS), $portalUserId]);
        return (int) Database::connection()->lastInsertId();
    }
    public static function update(int $id, array $data): bool {
        $set = implode(', ', array_map(static fn ($column) => "$column = ?", self::COLUMNS));
        $stmt = Database::connection()->prepare("UPDATE monitors SET $set, next_check_at = NULL, updated_at = UTC_TIMESTAMP() WHERE id = ?");
        $stmt->execute([...array_map(static fn ($column) => $data[$column], self::COLUMNS), $id]);
        return $stmt->rowCount() > 0;
    }
    public static function toggle(int $id, bool $active): void {
        $stmt = Database::connection()->prepare('UPDATE monitors SET is_active = ?, next_check_at = NULL, updated_at = UTC_TIMESTAMP() WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
    }
    public static function delete(int $id): void {
        Database::connection()->prepare('DELETE FROM monitors WHERE id = ?')->execute([$id]);
    }
    public static function saveManualResult(int $id, array $result): void {
        $stmt = Database::connection()->prepare('UPDATE monitors SET last_manual_test_at = ?, last_manual_test_success = ?, last_manual_test_http_status = ?, last_manual_test_response_ms = ?, last_manual_test_error_type = ?, last_manual_test_error = ?, last_manual_test_effective_url = ?, last_manual_test_attempts = ? WHERE id = ?');
        $stmt->execute([
            $result['checked_at'], $result['success'] ? 1 : 0, $result['http_status'], $result['response_time_ms'],
            $result['error_type'] ?: null, substr((string) $result['error_message'], 0, 2000) ?: null,
            substr((string) $result['effective_url'], 0, 2048) ?: null, $result['attempts'], $id,
        ]);
    }
}
