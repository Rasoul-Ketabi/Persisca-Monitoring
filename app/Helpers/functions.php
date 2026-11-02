<?php
declare(strict_types=1);

function mb_safe_substr(string $value, int $bytes): string { return function_exists('mb_substr') ? mb_substr($value, 0, $bytes, 'UTF-8') : (function_exists('iconv_substr') ? (iconv_substr($value, 0, $bytes, 'UTF-8') ?: '') : substr($value, 0, $bytes)); }
function monitor_root(): string { return dirname(__DIR__, 2); }
function monitor_installed(): bool {
    return is_file(monitor_root() . '/config/database.php') && is_file(monitor_root() . '/storage/installed.lock');
}
function monitor_install_blocked(): bool {
    return is_file(monitor_root() . '/config/database.php') || is_file(monitor_root() . '/storage/installed.lock');
}
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function monitor_base_path(): string {
    // Physical folder entry points have varying depths. Match the first application entry.
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    if (preg_match('~^(.*?)/(?:admin|account|install|public|status|telegram)(?:/|$)~', $script, $match)) {
        return rtrim($match[1], '/');
    }
    if (str_ends_with($script, '/index.php')) { return rtrim(substr($script, 0, -10), '/'); }
    return '';
}
function monitor_cookie_path(): string { return (monitor_base_path() ?: '') . '/'; }
function url(string $path = ''): string {
    return rtrim(monitor_base_path(), '/') . '/' . ltrim($path, '/');
}
function admin_url(string $page = 'dashboard'): string {
    $routes = [
        'dashboard' => 'admin/', 'login' => 'admin/login/', 'logout' => 'admin/logout/',
        'monitors' => 'admin/monitors/', 'monitors/add' => 'admin/monitors/add/',
        'monitors/edit' => 'admin/monitors/edit/', 'monitors/view' => 'admin/monitors/view/', 'monitors/analytics' => 'admin/monitors/analytics/', 'incidents' => 'admin/incidents/', 'incidents/view' => 'admin/incidents/view/',
        'status-page' => 'admin/status-page/', 'notifications' => 'admin/notifications/',
        'settings/general' => 'admin/settings/general/', 'settings/branding' => 'admin/settings/branding/',
        'system' => 'admin/system/',
        'users'=>'admin/users/', 'users/add'=>'admin/users/add/', 'users/edit'=>'admin/users/edit/',
        'users/password'=>'admin/users/password/', 'account'=>'admin/account/', 'maintenance'=>'admin/maintenance/',
        'portal-users' => 'admin/portal-users/',
        'portal-users/add' => 'admin/portal-users/add/',
        'portal-users/edit' => 'admin/portal-users/edit/',
        'portal-users/view' => 'admin/portal-users/view/',
    ];
    if (!isset($routes[$page])) { throw new InvalidArgumentException('Unknown admin route.'); }
    return url($routes[$page]);
}
function portal_url(string $path = ''): string { return url('account/' . ($path === '' ? '' : trim($path, '/') . '/')); }
function status_url(): string { return url('status/'); }
function asset_url(string $path): string { return url('public/assets/' . ltrim($path, '/')); }
function to(string $location): never {
    header('Location: ' . $location, true, 303);
    exit;
}
function display_date(?string $date, string $format = 'M j, Y g:i A'): string {
    if (!$date) { return 'Never'; }
    try {
        $zone = \Monitor\Models\Settings::get('timezone', 'UTC');
        return (new DateTimeImmutable($date, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone($zone))->format($format);
    } catch (Throwable $e) { return 'Unavailable'; }
}
function absolute_url(string $path): string {
    $base = rtrim(\Monitor\Models\Settings::get('base_url', ''), '/');
    if ($base && preg_match('~^https?://[^/]+~i', $base)) { return $base . '/' . ltrim($path, '/'); }
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host)) { $host = 'localhost'; }
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $proto . '://' . $host . url($path);
}
function flash(string $type, string $message): void { $_SESSION['flash'] = ['type' => $type, 'message' => $message]; }
function pull_flash(): ?array { $value = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $value; }

/** Format a measured duration or active incident elapsed time. */
function duration_label(int $seconds): string {
    $seconds = max(0, $seconds);
    if ($seconds < 60) { return $seconds . ' second' . ($seconds === 1 ? '' : 's'); }
    $parts = [];
    foreach (['day' => 86400, 'hour' => 3600, 'minute' => 60] as $name => $unit) {
        $value = intdiv($seconds, $unit);
        if ($value) { $parts[] = $value . ' ' . $name . ($value === 1 ? '' : 's'); $seconds %= $unit; }
    }
    return implode(' ', $parts) ?: '0 minutes';
}
function incident_duration(array $incident): string {
    if ($incident['status'] === 'resolved') { return duration_label((int) ($incident['duration_seconds'] ?? 0)); }
    $start = strtotime((string) $incident['started_at'] . ' UTC');
    return $start ? duration_label(max(0, time() - $start)) . ' so far' : 'Unavailable';
}
function monitor_state(array $monitor): string {
    if (!(int) $monitor['is_active']) { return 'Paused'; }
    return match ($monitor['operational_status'] ?? 'unknown') { 'up' => 'Up', 'down' => 'Down', default => 'Unknown' };
}
function monitor_state_class(array $monitor): string {
    return match (monitor_state($monitor)) { 'Up' => 'pill-success', 'Down' => 'pill-danger', default => 'pill-neutral' };
}

/** Observed availability is measured only across completed scheduled check samples. */
function uptime_label(?float $value): string {
    if ($value === null) { return 'Not enough data'; }
    return ($value >= 100 ? '100' : number_format($value,2,'.','')) . '%';
}
function response_label(?float $milliseconds): string {
    if ($milliseconds === null) { return 'No data'; }
    return $milliseconds >= 1000 ? number_format($milliseconds/1000,2,'.','') . ' s' : number_format($milliseconds,0,'.','') . ' ms';
}

/** Minimal error response; never includes exception details or database state. */
function monitor_error_page(int $status, bool $admin = false): never {
    $messages = [403 => ['Access Denied', 'You do not have permission to open this page.'],
                 404 => ['Page Not Found', 'This page is unavailable or has moved.'],
                 500 => ['Service Temporarily Unavailable', 'The application encountered an error. Please try again later.']];
    [$title, $description] = $messages[$status] ?? $messages[500];
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, private');
    $css = asset_url('css/app.css');
    $link = $admin ? admin_url() : status_url();
    $label = $admin ? 'Dashboard' : 'Status page';
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($title) . '</title><link rel="stylesheet" href="' . e($css) . '"></head><body class="release-error-body"><main class="release-error-card"><span class="release-error-code">' . $status . '</span><h1>' . e($title) . '</h1><p>' . e($description) . '</p><a class="button primary" href="' . e($link) . '">Return to ' . e($label) . '</a></main></body></html>';
    exit;
}
