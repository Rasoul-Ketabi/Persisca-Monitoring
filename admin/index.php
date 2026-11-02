<?php
declare(strict_types=1);
// Legacy Stage 01 bookmarks are redirected to the canonical folder routes.
require dirname(__DIR__) . '/bootstrap.php';
$legacy = trim((string) ($_GET['page'] ?? ''), '/');
if ($legacy !== '') {
    try { $destination = admin_url($legacy); }
    catch (InvalidArgumentException $exception) { monitor_error_page(404, true); }
    header('Location: ' . $destination, true, 302);
    exit;
}
$page = 'dashboard';
require __DIR__ . '/_dispatch.php';
