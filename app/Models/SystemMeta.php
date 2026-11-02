<?php
declare(strict_types=1);
namespace Monitor\Models;

use Monitor\Core\Database;
use PDO;

final class SystemMeta {
    public static function getMany(array $keys): array {
        if (!$keys) { return []; }
        $holders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = Database::connection()->prepare("SELECT meta_key, meta_value FROM system_meta WHERE meta_key IN ($holders)");
        $stmt->execute($keys);
        $result = array_fill_keys($keys, null);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) { $result[$row['meta_key']] = $row['meta_value']; }
        return $result;
    }
    public static function putMany(array $data): void {
        $statement = Database::connection()->prepare('INSERT INTO system_meta (meta_key, meta_value, updated_at) VALUES (?, ?, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = VALUES(updated_at)');
        foreach ($data as $key => $value) { $statement->execute([$key, $value === null ? null : (string) $value]); }
    }
}
