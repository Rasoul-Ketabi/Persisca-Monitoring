<?php
declare(strict_types=1);
namespace Monitor\Models;

use Monitor\Core\Database;
use PDO;

final class IncidentRepository {
    public static function counts(): array {
        $row = Database::connection()->query("SELECT (SELECT COUNT(*) FROM incidents WHERE status = 'open') AS active_incidents, (SELECT COUNT(*) FROM monitors WHERE is_active = 1 AND operational_status = 'up') AS up_count, (SELECT COUNT(*) FROM monitors WHERE is_active = 1 AND operational_status = 'down') AS down_count, (SELECT COUNT(*) FROM monitors WHERE is_active = 1 AND operational_status = 'unknown') AS unknown_count")->fetch(PDO::FETCH_ASSOC);
        return array_map('intval', $row);
    }
    public static function count(string $status): int {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM incidents WHERE status = ?');
        $stmt->execute([$status]);
        return (int) $stmt->fetchColumn();
    }
    public static function page(string $status, int $page = 1, int $pageSize = 20): array {
        $offset = (max(1, $page) - 1) * $pageSize;
        $pageSize = max(1, min(100, $pageSize));
        $stmt = Database::connection()->prepare("SELECT i.*, m.name AS monitor_name, m.url AS monitor_url, m.is_public AS monitor_public FROM incidents i JOIN monitors m ON m.id = i.monitor_id WHERE i.status = ? ORDER BY i.started_at DESC, i.id DESC LIMIT $pageSize OFFSET $offset");
        $stmt->execute([$status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function recentActive(int $limit = 6): array { return self::page('open', 1, $limit); }
    public static function forMonitor(int $monitorId, int $limit = 8): array {
        $stmt = Database::connection()->prepare('SELECT * FROM incidents WHERE monitor_id = ? ORDER BY started_at DESC, id DESC LIMIT ' . max(1, min(30, $limit)));
        $stmt->execute([$monitorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function find(int $id): ?array {
        $stmt = Database::connection()->prepare('SELECT i.*, m.name AS monitor_name, m.url AS monitor_url FROM incidents i JOIN monitors m ON m.id = i.monitor_id WHERE i.id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public static function checks(int $id, int $limit = 30): array {
        $stmt = Database::connection()->prepare('SELECT * FROM monitor_checks WHERE incident_id = ? ORDER BY checked_at DESC, id DESC LIMIT ' . max(1, min(100, $limit)));
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function publicMonitors(): array {
        return Database::connection()->query('SELECT id, name, is_active, operational_status, last_checked_at, current_incident_id FROM monitors WHERE is_public = 1 AND portal_user_id IS NULL ORDER BY name, id')->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function publicIncidents(): array {
        return Database::connection()->query("SELECT i.id, i.monitor_id, i.started_at, m.name AS monitor_name FROM incidents i JOIN monitors m ON m.id = i.monitor_id WHERE i.status = 'open' AND m.is_public = 1 AND m.portal_user_id IS NULL AND m.is_active = 1 ORDER BY i.started_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    }
}
