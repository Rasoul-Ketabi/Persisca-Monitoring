<?php
declare(strict_types=1);
namespace Monitor\Services;

final class Authorization {
    public static function isSuperAdmin(array $user): bool { return ($user['role'] ?? '') === 'super_admin'; }
    public static function allowed(array $user, string $route, string $method = 'GET'): bool {
        if (self::isSuperAdmin($user)) { return true; }
        if (($user['role'] ?? '') !== 'admin') { return false; }
        // Operational work remains available to Admin. Sensitive configuration does not.
        if (in_array($route, ['users','users/add','users/edit','users/password','maintenance','system','settings/general','settings/branding'], true)) { return false; }
        if ($route === 'notifications' && strtoupper($method) !== 'GET') { return false; }
        return in_array($route, ['dashboard','monitors','monitors/add','monitors/edit','monitors/view','monitors/analytics','incidents','incidents/view','status-page','notifications','account'], true);
    }
    public static function requireAccess(array $user, string $route, string $method='GET'): void {
        if (self::allowed($user,$route,$method)) { return; }
        http_response_code(403);
        $settings=\Monitor\Models\Settings::all();
        $pageTitle='Access Denied'; $pageSubtitle='Your account does not have permission to open this area.';
        $page='access-denied'; $flash=null; $error=null;
        require monitor_root().'/app/Views/partials/admin-top.php';
        require monitor_root().'/app/Views/admin/access-denied.php';
        require monitor_root().'/app/Views/partials/admin-bottom.php';
        exit;
    }
}
