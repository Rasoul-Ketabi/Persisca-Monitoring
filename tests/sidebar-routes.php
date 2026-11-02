<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap.php';

$failures = [];
$check = static function (bool $pass, string $message) use (&$failures): void {
    echo ($pass ? 'PASS ' : 'FAIL ') . $message . PHP_EOL;
    if (!$pass) { $failures[] = $message; }
};

$_SERVER['SCRIPT_NAME'] = '/monitor/admin/index.php';
foreach (['portal-users', 'portal-users/add', 'portal-users/edit', 'portal-users/view'] as $route) {
    $check(admin_url($route) === '/monitor/admin/' . $route . '/', 'Resolve admin route ' . $route);
    $check(is_file(monitor_root() . '/admin/' . $route . '/index.php'), 'Physical route exists ' . $route);
}

$settings = [
    'app_name' => 'Monitor QA',
    'app_short_name' => 'Monitor QA',
    'favicon_path' => '',
    'logo_path' => '',
    'logo_compact_path' => '',
];
$page = 'dashboard';
$pageTitle = 'Dashboard';
$pageSubtitle = 'Dashboard QA';
$flash = null;
$error = null;
$user = ['name' => 'QA', 'role' => 'super_admin'];
ob_start();
require monitor_root() . '/app/Views/partials/admin-top.php';
$html = (string) ob_get_clean();
$check(str_contains($html, 'href="/monitor/admin/portal-users/"'), 'Super Admin sidebar renders Portal Users link');
$check(str_contains($html, 'href="/monitor/admin/maintenance/"'), 'Super Admin sidebar completes after Portal Users');
$check(str_contains($html, '<main class="main-content">'), 'Super Admin dashboard header renders fully');
$check(!str_contains($html, 'Service Temporarily Unavailable'), 'Sidebar does not inject 500 error response');

$user = ['name' => 'QA', 'role' => 'admin'];
ob_start();
require monitor_root() . '/app/Views/partials/admin-top.php';
$html = (string) ob_get_clean();
$check(!str_contains($html, 'href="/monitor/admin/portal-users/"'), 'Standard Admin sidebar does not show restricted Portal Users');
$check(str_contains($html, '<main class="main-content">'), 'Standard Admin dashboard header renders fully');

echo (14 - count($failures)) . '/14 passed' . PHP_EOL;
exit($failures ? 1 : 0);
