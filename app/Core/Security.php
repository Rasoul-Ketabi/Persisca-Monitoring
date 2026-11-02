<?php
declare(strict_types=1);
namespace Monitor\Core;

final class Security {
    public static function token(): string {
        if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
        return $_SESSION['csrf'];
    }
    public static function check(): bool {
        return isset($_POST['_token']) && is_string($_POST['_token']) &&
            hash_equals(self::token(), $_POST['_token']);
    }
    public static function requireCsrf(): void {
        if (!self::check()) { http_response_code(419); exit('Your session expired. Reload the page and try again.'); }
    }
    public static function csrfField(): string {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
    public static function post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
}
