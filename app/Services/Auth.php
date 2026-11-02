<?php
declare(strict_types=1);
namespace Monitor\Services;

use Monitor\Core\Database;
use PDO;

final class Auth {
    private static ?array $user = null;
    public static function clearCachedUser(): void { self::$user = null; }

    public static function user(): ?array {
        if (self::$user !== null) { return self::$user; }
        $id = (int) ($_SESSION['admin_user_id'] ?? 0);
        if ($id < 1) { return null; }
        $now = time();
        if ($now - (int) ($_SESSION['admin_last_activity'] ?? $now) > 3600) {
            unset($_SESSION['admin_user_id'], $_SESSION['admin_last_activity']);
            return null;
        }
        $_SESSION['admin_last_activity'] = $now;
        if ($now - (int) ($_SESSION['admin_last_rotation'] ?? $now) >= 900) {
            session_regenerate_id(true);
            $_SESSION['admin_last_rotation'] = $now;
        }
        $stmt = Database::connection()->prepare('SELECT id, name, email, status, role, last_login_at, session_version FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || $user['status'] !== 'active' || !in_array($user['role'], ['admin', 'super_admin'], true) || (int)($_SESSION['admin_session_version'] ?? 0) !== (int)$user['session_version']) {
            unset($_SESSION['admin_user_id'], $_SESSION['admin_session_version']);
            self::$user = null;
            return null;
        }
        return self::$user = $user;
    }
    public static function login(string $email, string $password): string {
        $pdo = Database::connection();
        $email = strtolower(trim($email));
        $emailHash = hash('sha256', $email);
        $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $cutoff = gmdate('Y-m-d H:i:s', time() - 15 * 60);
        $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < ?')->execute([gmdate('Y-m-d H:i:s', time() - 86400)]);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE attempted_at >= ? AND (email_hash = ? OR ip_hash = ?)');
        $stmt->execute([$cutoff, $emailHash, $ipHash]);
        if ((int) $stmt->fetchColumn() >= 7) { return 'Too many login attempts. Please try again in 15 minutes.'; }
        $stmt = $pdo->prepare("SELECT id, password_hash, status, session_version FROM users WHERE email = ? AND role IN ('admin', 'super_admin') LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['status'] !== 'active' || !password_verify($password, $row['password_hash'])) {
            $pdo->prepare('INSERT INTO login_attempts (email_hash, ip_hash, attempted_at) VALUES (?, ?, UTC_TIMESTAMP())')->execute([$emailHash, $ipHash]);
            return 'The email or password was not recognized.';
        }
        session_regenerate_id(true);
        unset($_SESSION['csrf']);
        $_SESSION['admin_user_id'] = (int) $row['id'];
        $_SESSION['admin_session_version'] = (int) $row['session_version'];
        $_SESSION['admin_last_activity'] = time();
        $_SESSION['admin_last_rotation'] = time();
        self::$user = null;
        $pdo->prepare('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$row['id']]);
        $pdo->prepare('DELETE FROM login_attempts WHERE email_hash = ?')->execute([$emailHash]);
        return '';
    }
    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $params['path'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
        }
        session_destroy();
        self::$user = null;
    }
}
