<?php
declare(strict_types=1);
// Loaded only by the physical admin route entry points; never directly reachable.
require dirname(__DIR__) . '/bootstrap.php';
use Monitor\Core\Database;
use Monitor\Core\Security;
use Monitor\Models\Settings;
use Monitor\Models\MonitorRepository;
use Monitor\Models\CheckRepository;
use Monitor\Models\IncidentRepository;
use Monitor\Models\SystemMeta;
use Monitor\Services\Auth;
use Monitor\Services\Upload;
use Monitor\Services\MonitorInput;
use Monitor\Services\HttpMonitor;
use Monitor\Services\TelegramNotifier;
use Monitor\Services\TelegramDestinations;
use Monitor\Services\NotificationManager;
use Monitor\Models\NotificationRepository;
use Monitor\Services\MonitorAnalytics;
use Monitor\Services\RetentionManager;
use Monitor\Services\PublicStatus;
use Monitor\Services\Authorization;
use Monitor\Services\AdminUsers;
use Monitor\Services\Maintenance;
use Monitor\Services\HourlyAggregator;
header('Cache-Control: no-store, private');
if (!monitor_installed()) { to(url('install/index.php')); }
if (!isset($page) || !in_array($page, [
    'login', 'logout', 'dashboard', 'monitors', 'monitors/add', 'monitors/edit', 'monitors/view', 'monitors/analytics',
    'incidents', 'incidents/view', 'status-page', 'notifications', 'settings/general', 'settings/branding', 'system',
    'users', 'users/add','users/edit','users/password','account','maintenance',
    'portal-users','portal-users/add','portal-users/edit','portal-users/view',
], true)) { monitor_error_page(404, true); }

if ($page === 'login') {
    if (Auth::user()) { to(admin_url()); }
    $error = '';
    if (Security::post()) {
        Security::requireCsrf();
        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($email) > 190 || strlen($password) > 512 || $email === '' || $password === '') {
            $error = 'Enter your email and password.';
        } else {
            try { $error = Auth::login($email, $password); }
            catch (Throwable $e) { error_log('Monitor login error: ' . get_class($e)); $error = 'Sign in is temporarily unavailable.'; }
            if ($error === '') { to(admin_url()); }
        }
    }
    $settings = Settings::all();
    $flash = pull_flash();
    require monitor_root() . '/app/Views/auth/login.php';
    exit;
}

$user = Auth::user();
if (!$user) { to(admin_url('login')); }
if ($page === 'logout') {
    if (!Security::post()) { http_response_code(405); header('Allow: POST'); exit('Method not allowed.'); }
    Security::requireCsrf();
    Auth::logout();
    to(admin_url('login'));
}

// Authorization precedes all admin target lookup so standard Admins cannot enumerate accounts.
Authorization::requireAccess($user, $page, $_SERVER['REQUEST_METHOD'] ?? 'GET');
if (str_starts_with($page,'portal-users')) {require __DIR__.'/portal-users/_controller.php';exit;}


if (in_array($page,['users/edit','users/password'],true)) {
    $rawUserId=(string)($_GET['id']??'');
    if (!preg_match('/^[1-9][0-9]{0,17}$/D',$rawUserId)) {monitor_error_page(404, true);}
    $userTarget=AdminUsers::find((int)$rawUserId);
    if (!$userTarget) {monitor_error_page(404, true);}
}
// Account and user changes have their own authenticated, CSRF-protected handlers.
if (in_array($page,['users/add','users/edit','users/password','account','maintenance'],true) && Security::post()) {
    Security::requireCsrf();
    try {
        if ($page === 'users/add') {
            AdminUsers::create($_POST,(int)$user['id']);flash('success','Administrator account created.');to(admin_url('users'));
        }
        if ($page === 'users/edit') {
            if (($userTarget['id']??0) < 1) { throw new RuntimeException('Administrator not found.'); }
            if ((string)($_POST['confirm_change']??'') !== 'yes') { throw new RuntimeException('Confirm the account changes.'); }
            AdminUsers::update((int)$userTarget['id'],$_POST,(int)$user['id']);flash('success','Administrator updated.');to(admin_url('users'));
        }
        if ($page === 'users/password') {
            if (($userTarget['id']??0)<1) { throw new RuntimeException('Administrator not found.'); }
            if ((int)$userTarget['id']===(int)$user['id']) {throw new RuntimeException('Change your own password from My Account.');}
            AdminUsers::resetPassword((int)$userTarget['id'],(string)($_POST['password']??''),(string)($_POST['confirm_password']??''),(int)$user['id']);
            flash('success','Password updated. Existing sessions were invalidated.');to(admin_url('users'));
        }
        if ($page === 'account') {
            AdminUsers::selfPassword((int)$user['id'],(string)($_POST['current_password']??''),(string)($_POST['password']??''),(string)($_POST['confirm_password']??''));
            flash('success','Password changed. Your current session remains active.');to(admin_url('account'));
        }
        if ($page === 'maintenance') {
            if ((string)($_POST['confirm_action']??'') !== 'yes') { throw new RuntimeException('Confirm the maintenance action before continuing.'); }
            $action=(string)($_POST['action']??'');$result='';
            if ($action==='cleanup') {
                $cleanup=Maintenance::withCronLock(static fn()=>RetentionManager::runNow());$result='Cleanup removed '.(int)$cleanup['checks'].' checks and '.(int)$cleanup['notifications'].' delivery records (bounded batch).';
            } elseif ($action==='schedules') {
                $result='Repaired '.Maintenance::withCronLock(static fn()=>Maintenance::repairSchedules()).' active monitor schedules. Paused monitors were unchanged.';
            } elseif ($action==='aggregates') {
                $result='Processed '.Maintenance::withCronLock(static fn()=>HourlyAggregator::process(500)).' pending scheduled checks into hourly aggregates.';
            } else {throw new RuntimeException('Unsupported maintenance action.');}
            Maintenance::audit((int)$user['id'],$action,$result);
            flash('success',$result);to(admin_url('maintenance'));
        }
    } catch (RuntimeException $e) { $error=$e->getMessage(); }
    catch (Throwable $e) {error_log('Admin operation failed: '.get_class($e));$error='This operation could not be completed. Please try again.';}
}

if (Security::post() && !str_starts_with($page, 'monitors') && !in_array($page,['users/add','users/edit','users/password','account','maintenance'],true)) {
    Security::requireCsrf();
    try {
        if ($page === 'notifications') {
            $action = (string) ($_POST['action'] ?? 'save');
            if ($action === 'save') {
                $token = trim((string) ($_POST['telegram_bot_token'] ?? ''));
                $chat = TelegramDestinations::normalize((string) ($_POST['telegram_chat_id'] ?? ''));
                $remove = isset($_POST['remove_token']);
                if ($remove && $token !== '') { throw new RuntimeException('Choose either a new token or removal, not both.'); }
                if ($remove && (string) ($_POST['confirm_remove'] ?? '') !== 'yes') { throw new RuntimeException('Confirm token removal first.'); }
                if ($token !== '' && !TelegramNotifier::validTokenShape($token)) { throw new RuntimeException('Enter a valid Telegram Bot Token.'); }
                // TelegramDestinations::normalize validates every line and deduplicates IDs.
                $enabled = isset($_POST['telegram_enabled']) ? '1' : '0';
                $savedToken = Settings::get('telegram_bot_token');
                if ($enabled === '1' && ($remove || ($token === '' && $savedToken === '') || $chat === '')) {
                    throw new RuntimeException('Save a Bot Token and Chat ID before enabling Telegram.');
                }
                $new = ['telegram_enabled' => $enabled, 'telegram_chat_id' => $chat,
                    'telegram_notify_down' => isset($_POST['telegram_notify_down']) ? '1' : '0',
                    'telegram_notify_recovery' => isset($_POST['telegram_notify_recovery']) ? '1' : '0'];
                if ($remove) { $new['telegram_bot_token'] = ''; }
                elseif ($token !== '') { $new['telegram_bot_token'] = $token; }
                if ($remove || ($token !== '' && $token !== $savedToken)) {
                    // Existing webhook belongs to the old token. Reconfigure explicitly for the new bot.
                    $new['telegram_webhook_secret']='';
                    $new['telegram_bot_username']='';
                    $new['telegram_webhook_configured']='0';
                }
                Settings::putMany($new);
                flash('success', 'Telegram settings saved. Use Send Test Message to verify delivery.');
                to(admin_url('notifications'));
            }
            if ($action === 'configure_webhook') {
                $result = \Monitor\Services\TelegramLinkManager::configureWebhook();
                if (!$result['success']) {throw new RuntimeException('Webhook configuration failed. Check the bot token, Base URL and outbound HTTPS connectivity.');}
                flash('success','Telegram webhook configured for @'.$result['username'].'.');
                to(admin_url('notifications'));
            }
            if ($action === 'test') {
                $token = Settings::get('telegram_bot_token');
                $targets = TelegramDestinations::parse(Settings::get('telegram_chat_id'));
                if ($token === '' || !$targets) { throw new RuntimeException('Save a Bot Token and at least one Chat ID before testing.'); }
                $message = '✅ <b>Telegram notification test successful.</b>' . "\n\n" .
                    'Notifications are connected for ' . TelegramNotifier::escape(Settings::get('app_name', 'Monitor')) . '.';
                $delivered = 0;
                $failures = [];
                foreach ($targets as $target) {
                    try { $result = TelegramNotifier::send($token, $target, $message); }
                    catch (Throwable $error) { $result = ['success' => false, 'error_message' => 'Telegram connection error.']; }
                    if (!empty($result['success'])) { $delivered++; }
                    else { $failures[] = $target . ': ' . ($result['error_message'] ?? 'Delivery failed.'); }
                }
                $message = 'Test delivered to ' . $delivered . ' of ' . count($targets) . ' destination(s).';
                if ($failures) { $message .= ' Failed: ' . implode(' | ', $failures); }
                flash(!$failures ? 'success' : 'error', $message);
                to(admin_url('notifications'));
            }
            http_response_code(405); exit('Unsupported notification action.');
        }
        if ($page === 'status-page') {
            $values = [];
            foreach (['status_page_enabled','status_show_uptime','status_show_response_time','status_show_incidents','status_show_footer'] as $option) {
                $values[$option] = isset($_POST[$option]) ? '1' : '0';
            }
            Settings::putMany($values);
            flash('success', 'Public status page settings saved.');
            to(admin_url('status-page'));
        }
        if ($page === 'settings/general') {
            $cronOptions = [];
            foreach (['check_retention_days' => [7, 3650], 'notification_log_retention_days' => [7,3650], 'cron_stale_after_minutes' => [2, 1440], 'cron_batch_size' => [1, 100]] as $key => [$min, $max]) {
                $value = (string) ($_POST[$key] ?? Settings::get($key));
                if (!ctype_digit($value) || (int) $value < $min || (int) $value > $max) {
                    throw new RuntimeException('Check the monitoring setting: ' . str_replace('_', ' ', $key) . '.');
                }
                $cronOptions[$key] = (int) $value;
            }
            $appName = trim((string) ($_POST['app_name'] ?? ''));
            $shortName = trim((string) ($_POST['app_short_name'] ?? ''));
            $company = trim((string) ($_POST['company_name'] ?? ''));
            $footer = trim((string) ($_POST['footer_text'] ?? ''));
            $zone = (string) ($_POST['timezone'] ?? 'UTC');
            $support = trim((string) ($_POST['support_url'] ?? ''));
            $baseUrl = rtrim(trim((string) ($_POST['base_url'] ?? '')), '/');
            $accent = trim((string) ($_POST['accent_color'] ?? '#536dfe'));
            if ($appName === '' || strlen($appName) > 120 || strlen($shortName) > 40 || strlen($company) > 160 || strlen($footer) > 400) {
                throw new RuntimeException('Please check the name and footer lengths.');
            }
            if (!in_array($zone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true) && $zone !== 'UTC') { throw new RuntimeException('Choose a supported timezone.'); }
            if ($support !== '' && (!filter_var($support, FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $support) || strlen($support) > 500)) {
                throw new RuntimeException('The support link must be a valid HTTP or HTTPS URL.');
            }
            if ($baseUrl !== '' && (!filter_var($baseUrl, FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $baseUrl) || preg_match('~[@?#]~', $baseUrl) || strlen($baseUrl) > 500)) {
                throw new RuntimeException('The base URL must be a valid HTTP or HTTPS address with no query or fragment.');
            }
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) { throw new RuntimeException('Use a six-digit HEX accent color.'); }
            Settings::putMany(array_merge(['app_name' => $appName, 'app_short_name' => $shortName ?: $appName, 'company_name' => $company, 'footer_text' => $footer, 'timezone' => $zone, 'support_url' => $support, 'base_url' => $baseUrl, 'accent_color' => $accent], $cronOptions));
            flash('success', 'General settings saved.');
            to(admin_url($page));
        }
        if ($page === 'settings/branding') {
            $values = [];
            foreach (['app_name' => 120, 'company_name' => 160, 'meta_title' => 200, 'meta_description' => 500, 'og_title' => 200, 'og_description' => 500, 'footer_text' => 400] as $key => $max) {
                $value = trim((string) ($_POST[$key] ?? ''));
                if (strlen($value) > $max || ($key === 'app_name' && $value === '')) { throw new RuntimeException('Check the text length for ' . str_replace('_', ' ', $key) . '.'); }
                $values[$key] = $value;
            }
            if (Settings::get('app_short_name') === Settings::get('app_name')) {
                $values['app_short_name'] = substr($values['app_name'], 0, 40);
            }
            $uploaded = [];
            $brandingPdo = Database::connection();
            $brandingPdo->beginTransaction();
            try {
                $directory = monitor_root() . '/public/assets/uploads';
                foreach (['logo' => 'logo_path', 'compact_logo' => 'logo_compact_path', 'favicon' => 'favicon_path', 'og_image' => 'og_image_path'] as $field => $key) {
                    if (isset($_FILES[$field]) && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $filename = Upload::save($_FILES[$field], $field, $directory);
                        $uploaded[] = $directory . '/' . $filename;
                        $values[$key] = 'public/assets/uploads/' . $filename;
                    }
                }
                Settings::putMany($values);
                $brandingPdo->commit();
            } catch (Throwable $e) {
                if ($brandingPdo->inTransaction()) { $brandingPdo->rollBack(); }
                foreach ($uploaded as $file) { @unlink($file); }
                throw $e;
            }
            flash('success', 'Branding updated across the platform.');
            to(admin_url($page));
        }
        http_response_code(405);
        exit('This page does not accept changes.');
    } catch (Throwable $e) {
        error_log('Monitor settings error: ' . get_class($e));
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'Settings could not be saved. Please try again.';
    }
}

// Monitor changes are never accepted over GET and always require CSRF.
if (str_starts_with($page, 'monitors')) {
    $monitorId = null;
    if (in_array($page, ['monitors/edit', 'monitors/view', 'monitors/analytics'], true)) {
        $rawId = (string) ($_GET['id'] ?? '');
        if (!preg_match('/^[1-9][0-9]{0,17}$/D', $rawId)) {
            monitor_error_page(404, true);
        }
        $monitorId = (int) $rawId;
        $editingMonitor = MonitorRepository::find($monitorId);
        if (!$editingMonitor) { monitor_error_page(404, true); }
    }
    if (Security::post()) {
        Security::requireCsrf();
        try {
            $action = (string) ($_POST['action'] ?? 'save');
            if ($page === 'monitors/add' && $action === 'save') {
                $data = MonitorInput::fromPost($_POST);
                if (!Authorization::isSuperAdmin($user) && !empty($data['allow_private_network'])) {
                    throw new InvalidArgumentException('Only a Super Admin can configure private-network monitoring.');
                }
                $id = MonitorRepository::create($data);
                flash('success', 'Monitor added. You can now run a manual test.');
                to(admin_url('monitors/edit') . '?id=' . $id);
            }
            if ($page === 'monitors/edit' && $action === 'save') {
                $data = MonitorInput::fromPost($_POST);
                if (!Authorization::isSuperAdmin($user) && (!empty($data['allow_private_network']) || !empty($editingMonitor['allow_private_network']))) {
                    throw new InvalidArgumentException('Only a Super Admin can configure private-network monitoring.');
                }
                MonitorRepository::update($monitorId, $data);
                if (!(int) $data['is_active'] && $editingMonitor['operational_status'] === 'down') { flash('error', 'Monitoring paused while an incident is open. Automatic recovery detection is stopped until resumed.'); to(admin_url('monitors/edit') . '?id=' . $monitorId); }
                flash('success', 'Monitor settings saved.');
                to(admin_url('monitors/edit') . '?id=' . $monitorId);
            }
            if (in_array($page, ['monitors', 'monitors/edit', 'monitors/view'], true) && in_array($action, ['test', 'toggle', 'delete'], true)) {
                $id = $monitorId;
                if ($id === null) {
                    $rawId = (string) ($_POST['id'] ?? '');
                    if (!preg_match('/^[1-9][0-9]{0,17}$/D', $rawId)) {
                        throw new InvalidArgumentException('Invalid monitor selection.');
                    }
                    $id = (int) $rawId;
                }
                $existing = MonitorRepository::find($id);
                if (!$existing) { throw new InvalidArgumentException('Monitor not found.'); }
                if (!Authorization::isSuperAdmin($user) && !empty($existing['allow_private_network'])) {
                    throw new InvalidArgumentException('Only a Super Admin can operate private-network monitors.');
                }
                if ($action === 'configure_webhook') {
                $result = \Monitor\Services\TelegramLinkManager::configureWebhook();
                if (!$result['success']) {throw new RuntimeException('Webhook configuration failed. Check the bot token, Base URL and outbound HTTPS connectivity.');}
                flash('success','Telegram webhook configured for @'.$result['username'].'.');
                to(admin_url('notifications'));
            }
            if ($action === 'test') {
                    $result = HttpMonitor::check($existing);
                    MonitorRepository::saveManualResult($id, $result);
                    flash($result['success'] ? 'success' : 'error', $result['success']
                        ? 'Manual test succeeded. HTTP ' . $result['http_status'] . ', ' . $result['response_time_ms'] . ' ms.'
                        : 'Manual test failed: ' . $result['error_type'] . '. See the saved diagnostics below.');
                    to(admin_url('monitors/edit') . '?id=' . $id);
                }
                if ($action === 'toggle') {
                    $active = !(bool) $existing['is_active'];
                    MonitorRepository::toggle($id, $active);
                    flash($active || $existing['operational_status'] !== 'down' ? 'success' : 'error', $active ? 'Monitor resumed.' : ($existing['operational_status'] === 'down' ? 'Monitor paused with an active incident. Recovery requires resuming scheduled checks.' : 'Monitor paused.'));
                }
                if ($action === 'delete') {
                    MonitorRepository::delete($id);
                    flash('success', 'Monitor and its check, incident and notification history deleted.');
                }
                to(admin_url('monitors'));
            }
            http_response_code(405);
            exit('This action is not supported.');
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log('Monitor action error: ' . get_class($exception));
            $error = 'Monitor changes could not be completed. Please try again.';
        }
    }
    if ($page === 'monitors') { $monitorList = MonitorRepository::all(); }
    if ($page === 'monitors/view') {
        $historyPage = filter_var($_GET['p'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]) ?: 1;
        $historyCount = CheckRepository::countFor($monitorId);
        $historyPages = max(1, (int) ceil($historyCount / 20));
        $historyPage = min($historyPage, $historyPages);
        $historyRows = CheckRepository::pageFor($monitorId, $historyPage, 20);
        $monitorIncidents = IncidentRepository::forMonitor($monitorId, 8);
    }
    if ($page === 'monitors/analytics') {
        $analyticsRange = MonitorAnalytics::period(is_string($_GET['range'] ?? null) ? $_GET['range'] : '24h');
        $analytics = MonitorAnalytics::summary($monitorId, $analyticsRange);
        $analyticsSeries = MonitorAnalytics::responseSeries($monitorId, $analyticsRange);
        $analyticsAll = [];
        foreach (array_keys(MonitorAnalytics::PERIODS) as $periodKey) { $analyticsAll[$periodKey] = $periodKey === $analyticsRange ? $analytics : MonitorAnalytics::summary($monitorId, $periodKey); }
        $monitorIncidents = IncidentRepository::forMonitor($monitorId, 8);
    }
    if ($page === 'monitors/add') {
        $editingMonitor = [
            'name' => '', 'url' => '', 'is_active' => 1, 'is_public' => 1,
            'allow_private_network' => 0, 'check_interval_minutes' => 5,
            'timeout_seconds' => 10, 'expected_status_codes' => '200',
            'retry_count' => 2, 'retry_delay_seconds' => 2, 'failure_threshold' => 2, 'telegram_notifications_enabled' => 1,
            'last_manual_test_at' => null,
        ];
    }
    if (in_array($page, ['monitors/add', 'monitors/edit'], true) && Security::post() && isset($error)) {
        $editingMonitor = array_merge($editingMonitor, array_intersect_key($_POST, $editingMonitor));
        foreach (['is_active', 'is_public', 'allow_private_network', 'telegram_notifications_enabled'] as $checkbox) {
            $editingMonitor[$checkbox] = isset($_POST[$checkbox]) ? 1 : 0;
        }
    }
}
if ($page === 'incidents') {
    $incidentTab = ($_GET['tab'] ?? '') === 'history' ? 'history' : 'active';
    $incidentStatus = $incidentTab === 'history' ? 'resolved' : 'open';
    $incidentPage = filter_var($_GET['p'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]) ?: 1;
    $incidentCount = IncidentRepository::count($incidentStatus);
    $incidentPages = max(1, (int) ceil($incidentCount / 20));
    $incidentPage = min($incidentPage, $incidentPages);
    $incidentRows = IncidentRepository::page($incidentStatus, $incidentPage, 20);
    $incidentCounts = IncidentRepository::counts();
}
if ($page === 'incidents/view') {
    $rawId = (string) ($_GET['id'] ?? '');
    if (!preg_match('/^[1-9][0-9]{0,17}$/D', $rawId)) { monitor_error_page(404, true); }
    $incidentDetail = IncidentRepository::find((int) $rawId);
    if (!$incidentDetail) { monitor_error_page(404, true); }
    $incidentChecks = IncidentRepository::checks((int) $rawId, 30);
}
if ($page === 'notifications') {
    $notificationFilter = NotificationRepository::normalizeFilter($_GET['filter'] ?? null);
    $notificationPage = filter_var($_GET['p'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]) ?: 1;
    $notificationCount = NotificationRepository::count($notificationFilter);
    $notificationPages = max(1, (int) ceil($notificationCount / 20));
    $notificationPage = min($notificationPage, $notificationPages);
    $notificationRows = NotificationRepository::page($notificationFilter, $notificationPage, 20);
}
if ($page==='users') { $adminUsers=AdminUsers::all(); }
if ($page==='maintenance') { $maintenance=Maintenance::overview();$maintenanceLogs=Maintenance::recentLogs(); }
if ($page === 'dashboard') {
    $monitorCounts = MonitorRepository::counts();
    $recentMonitors = MonitorRepository::recent(5);
    $checksToday = CheckRepository::countToday();
    $fleetAnalytics = MonitorAnalytics::dashboardSummary();
    $incidentCounts = IncidentRepository::counts();
    $activeIncidents = IncidentRepository::recentActive(6);
    $recentFailedChecks = CheckRepository::recentFailed(6);
    $cronBrief = SystemMeta::getMany(['cron_last_completed_at', 'cron_last_status']);
}

$settings = Settings::all();
$flash = pull_flash();
$titleByPage = [
    'dashboard' => ['Dashboard', 'Your monitor configuration overview.'],
    'monitors/add' => ['Add Monitor', 'Configure a website or HTTP endpoint.'],
    'monitors/edit' => ['Edit Monitor', 'Review your configuration and run diagnostics.'],
    'monitors/view' => ['Monitor Details', 'Scheduled check history and runtime diagnostics.'],
    'monitors/analytics' => ['Monitor Analytics', 'Observed availability and response performance based on real scheduled checks.'],
    'monitors' => ['Monitors', 'Add, configure, and test your endpoints.'],
    'incidents' => ['Incidents', 'Confirmed outages and recovery history.'],
    'incidents/view' => ['Incident Details', 'Timeline and related checks for a confirmed outage.'],
    'status-page' => ['Status Page', 'Manage your public service status.'],
    'notifications' => ['Notifications', 'Manage alerts and delivery channels.'],
    'settings/general' => ['General Settings', 'Manage your platform preferences.'],
    'settings/branding' => ['Branding', 'Make the platform look like yours.'],
    'system' => ['System Health', 'Review your installation and environment.'],
    'users'=>['Admin Users','Manage administrator accounts and access.'],
    'users/add'=>['Add Administrator','Create a new Admin or Super Admin account.'],
    'users/edit'=>['Edit Administrator','Manage account details, role and status.'],
    'users/password'=>['Reset Password','Change another administrator password.'],
    'account'=>['My Account','View your account and change your password.'],
    'maintenance'=>['Maintenance','Production health and bounded maintenance operations.'],
    'not-found' => ['Page Not Found', 'That page does not exist.'],
];
[$pageTitle, $pageSubtitle] = $titleByPage[$page];
if ($page === 'not-found') { http_response_code(404); }
require monitor_root() . '/app/Views/partials/admin-top.php';
$view = match ($page) {
    'dashboard' => 'dashboard',
    'monitors' => 'monitors',
    'monitors/add', 'monitors/edit' => 'monitor-form',
    'monitors/view' => 'monitor-detail',
    'monitors/analytics' => 'monitor-analytics',
    'incidents' => 'incidents',
    'incidents/view' => 'incident-detail',
    'settings/general' => 'general',
    'settings/branding' => 'branding',
    'system' => 'system',
    'notifications' => 'notifications',
    'status-page' => 'status-page',
    'users'=>'users', 'users/add'=>'user-form','users/edit'=>'user-form', 'users/password'=>'user-password',
    'account'=>'account', 'maintenance'=>'maintenance',
    default => 'placeholder',
};
require monitor_root() . '/app/Views/admin/' . $view . '.php';
require monitor_root() . '/app/Views/partials/admin-bottom.php';
