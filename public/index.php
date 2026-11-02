<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use Monitor\Models\Settings;
use Monitor\Services\PublicStatus;
if (!monitor_installed()) { to(url('install/index.php')); }
$settings = Settings::all();
$enabled = ($settings['status_page_enabled'] ?? '1') === '1';
$ownerId = null;
$pageHeading = 'System status';
$pageIntro = 'Availability and incident updates for our services.';
$canonical = absolute_url('status/');
$brandLink = status_url();
if (isset($_GET['personal_slug'])) {
    $slug = is_string($_GET['personal_slug']) ? $_GET['personal_slug'] : ''; 
    // Fail closed before querying or rendering anything for unknown/disabled slugs.
    if (!$enabled || !\Monitor\Services\PortalUsers::isValidSlug($slug)) { monitor_error_page(404); }
    $owner = \Monitor\Services\PortalUsers::publicBySlug($slug);
    if (!$owner) { monitor_error_page(404); }
    $ownerId = (int)$owner['id'];
    $pageHeading = trim((string)($owner['status_page_title'] ?? '')) ?: ($owner['name'].' Status');
    $pageIntro = 'Availability and incident updates for published services.';
    $canonical = absolute_url('status/'.$slug.'/');
    $brandLink = status_url().$slug.'/';
}
if (!$enabled) { http_response_code(404); }
$data = $enabled ? PublicStatus::snapshot($settings, $ownerId) : ['monitors'=>[], 'active'=>[], 'resolved'=>[], 'state'=>'none', 'last_checked_at'=>null];
[$stateTitle, $metaDescription] = $enabled
    ? PublicStatus::metadata($data['state'], $settings['app_name'], '')
    : [($settings['meta_title'] ?: 'System Status') . ' | ' . $settings['app_name'], ($settings['meta_description'] ?: 'System status information.')];
$metaTitle = $ownerId !== null ? $pageHeading.' | '.$settings['app_name'] : $stateTitle;
$ogTitle = $metaTitle;
$ogDescription = $metaDescription;
$ogImage = ($settings['og_image_path'] ?? '') ? absolute_url($settings['og_image_path']) : absolute_url('public/assets/images/status-og-default.jpg');
// Personal pages are never cached, so disabling a page or hiding a monitor
// takes effect without stale public incident cards from shared caches.
header('Cache-Control: '.($ownerId !== null ? 'no-store' : 'public, max-age=30'));
require monitor_root() . '/app/Views/public/status.php';
