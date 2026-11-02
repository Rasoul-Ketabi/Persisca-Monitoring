<?php
declare(strict_types=1);
namespace Monitor\Core;

use PDO;
use RuntimeException;

final class Database {
    private static ?PDO $connection = null;

    public static function connection(): PDO {
        if (self::$connection) { return self::$connection; }
        $path = dirname(__DIR__, 2) . '/config/database.php';
        if (!is_file($path)) { throw new RuntimeException('Database configuration not available.'); }
        $config = require $path;
        if (!is_array($config)) { throw new RuntimeException('Invalid database configuration.'); }
        return self::$connection = self::connect($config);
    }

    public static function connect(array $config): PDO {
        $host = (string) ($config['host'] ?? 'localhost');
        $port = (int) ($config['port'] ?? 3306);
        $name = (string) ($config['name'] ?? '');
        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
        return new PDO($dsn, (string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 8,
        ]);
    }
}
