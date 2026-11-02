<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Monitor\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
require_once __DIR__ . '/app/Helpers/functions.php';

ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_exception_handler(static function (Throwable $error): void {
    error_log('Monitor uncaught exception: ' . get_class($error));
    if (PHP_SAPI !== 'cli') {
        monitor_error_page(500, str_contains((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/admin/'));
    } else {
        fwrite(STDERR, 'Application error: ' . get_class($error) . PHP_EOL);
    }
});

if (!defined('MONITOR_CRON') && !defined('MONITOR_NO_SESSION') && session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name(preg_match('~(?:^|/)account(?:/|$)~', (string)($_SERVER['SCRIPT_NAME'] ?? '')) ? 'monitor_portal_session' : 'monitor_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => monitor_cookie_path(),
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
}
