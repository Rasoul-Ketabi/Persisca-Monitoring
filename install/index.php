<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

use Monitor\Core\Database;
use Monitor\Core\Security;
use Monitor\Models\Settings;
use Monitor\Services\Upload;

header('Cache-Control: no-store, private');
if (monitor_install_blocked()) {
    http_response_code(monitor_installed() ? 200 : 403);
    $done = monitor_installed();
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Monitor Setup</title><link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>"></head><body class="install-body"><main class="install-card"><div class="brand-glyph">M</div><h1><?= $done ? 'Setup complete' : 'Setup unavailable' ?></h1><p><?= $done ? 'Monitor has already been installed. The installer is locked.' : 'An existing configuration or lock was detected. To protect existing data, setup is blocked.' ?></p><?php if ($done): ?><a class="button primary" href="<?= e(admin_url('login')) ?>">Go to admin login</a><?php endif; ?></main></body></html><?php
    exit;
}

$requirements = [
    'PHP 8.1 or newer' => version_compare(PHP_VERSION, '8.1', '>='),
    'PDO extension' => extension_loaded('pdo'),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'cURL' => extension_loaded('curl'),
    'JSON' => extension_loaded('json'),
    'Fileinfo' => extension_loaded('fileinfo'),
    'Sessions' => session_status() === PHP_SESSION_ACTIVE,
    'Configuration directory writable' => is_writable(monitor_root() . '/config'),
    'Storage directory writable' => is_writable(monitor_root() . '/storage'),
    'Storage cache writable' => is_writable(monitor_root() . '/storage/cache'),
    'Uploads directory writable' => is_writable(monitor_root() . '/public/assets/uploads'),
];
$ready = !in_array(false, $requirements, true);
$maxStep = max(1, min(6, (int) ($_SESSION['install_step'] ?? 1)));
$requestedStep = (int) ($_GET['step'] ?? $maxStep);
$step = max(1, min($requestedStep, $maxStep));
$error = '';
$input = [];

if (Security::post()) {
    if (!Security::check()) { http_response_code(419); exit('Your session expired. Reload the installer.'); }
    $postedStep = (int) ($_POST['step'] ?? 0);
    if ($postedStep !== $step) { to(url('install/index.php?step=' . $maxStep)); }
    $input = $_POST;
    try {
        if ($step === 1) {
            if (!$ready) { throw new RuntimeException('Resolve the failed checks before continuing.'); }
        } elseif ($step === 2) {
            $db = [
                'host' => trim((string) ($_POST['host'] ?? '')),
                'port' => (int) ($_POST['port'] ?? 3306),
                'name' => trim((string) ($_POST['name'] ?? '')),
                'username' => trim((string) ($_POST['username'] ?? '')),
                'password' => (string) ($_POST['password'] ?? ''),
            ];
            if (!preg_match('/^[a-zA-Z0-9._:-]{1,255}$/', $db['host']) || $db['port'] < 1 || $db['port'] > 65535 || !preg_match('/^[a-zA-Z0-9_\-]{1,64}$/', $db['name']) || $db['username'] === '') {
                throw new RuntimeException('Enter valid database connection information.');
            }
            Database::connect($db)->query('SELECT 1');
            $_SESSION['install_db'] = $db;
        } elseif ($step === 3) {
            $name = trim((string) ($_POST['admin_name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['admin_email'] ?? '')));
            $password = (string) ($_POST['admin_password'] ?? '');
            if (strlen($name) < 2 || strlen($name) > 140 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
                throw new RuntimeException('Enter a name and valid administrator email.');
            }
            if (strlen($password) < 8 || strlen($password) > 256 || !hash_equals($password, (string) ($_POST['confirm_password'] ?? ''))) {
                throw new RuntimeException('Use a matching password of at least 8 characters.');
            }
            $_SESSION['install_admin'] = ['name' => $name, 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)];
        } elseif ($step === 4) {
            $name = trim((string) ($_POST['app_name'] ?? ''));
            $company = trim((string) ($_POST['company_name'] ?? ''));
            if ($name === '' || strlen($name) > 120 || strlen($company) > 160) { throw new RuntimeException('Enter a product name (up to 120 characters).'); }
            $logo = $_SESSION['install_logo'] ?? '';
            if (isset($_FILES['logo']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $newLogo = Upload::save($_FILES['logo'], 'staged-logo', monitor_root() . '/storage/cache');
                if ($logo) { @unlink(monitor_root() . '/storage/cache/' . $logo); }
                $logo = $newLogo;
                $_SESSION['install_logo'] = $logo;
            }
            $_SESSION['install_brand'] = ['app_name' => $name, 'company_name' => $company];
        } elseif ($step === 5) {
            $timezone = (string) ($_POST['timezone'] ?? 'UTC');
            if (!in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true) && $timezone !== 'UTC') {
                throw new RuntimeException('Choose a valid timezone.');
            }
            $_SESSION['install_timezone'] = $timezone;
        } elseif ($step === 6) {
            if (!$ready || empty($_SESSION['install_db']) || empty($_SESSION['install_admin']) || empty($_SESSION['install_brand']) || !isset($_SESSION['install_timezone'])) {
                throw new RuntimeException('Some setup information is missing. Please restart setup.');
            }
            $installMutex = fopen(monitor_root() . '/storage/install-running.lock', 'c');
            if (!$installMutex || !flock($installMutex, LOCK_EX | LOCK_NB)) { throw new RuntimeException('An installation is already running.'); }
            if (monitor_install_blocked()) { flock($installMutex, LOCK_UN); fclose($installMutex); throw new RuntimeException('Installation is already configured.'); }
            $pdo = Database::connect($_SESSION['install_db']);
            // Installation is only allowed on an empty database. Check BEFORE any DDL.
            // Existing tables must never be modified by the first-install wizard.
            $tableCount = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
            if ($tableCount > 0) {
                throw new RuntimeException('The database contains existing tables. Use a new, empty database for installation.');
            }
            $schema = file_get_contents(monitor_root() . '/database/schema.sql');
            if ($schema === false) { throw new RuntimeException('Database schema file is missing.'); }
            foreach (explode(';', $schema) as $statement) {
                if (trim($statement) !== '') { $pdo->exec(trim($statement)); }
            }
            $pdo->beginTransaction();
            $cfg = monitor_root() . '/config/database.php';
            $lock = monitor_root() . '/storage/installed.lock';
            $tmp = $cfg . '.' . bin2hex(random_bytes(6)) . '.tmp';
            $logoTarget = '';
            $createdConfig = false;
            $createdLock = false;
            try {
                if (monitor_install_blocked()) { throw new RuntimeException('Installation is already configured.'); }
                if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
                    throw new RuntimeException('This database already contains administrator records. Use a fresh database.');
                }
                $admin = $_SESSION['install_admin'];
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())');
                $stmt->execute([$admin['name'], $admin['email'], $admin['password_hash'], 'super_admin', 'active']);
                $defaults = Settings::defaults();
                $defaults = array_merge($defaults, $_SESSION['install_brand'], ['timezone' => $_SESSION['install_timezone']]);
                $defaults['app_short_name'] = substr($defaults['app_name'], 0, 40);
                $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
                if (preg_match('/^[a-zA-Z0-9.\-:\[\]]+$/', $host)) {
                    $defaults['base_url'] = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . $host . monitor_base_path();
                }
                if (!empty($_SESSION['install_logo'])) {
                    $staged = monitor_root() . '/storage/cache/' . basename((string) $_SESSION['install_logo']);
                    if (!is_file($staged)) { throw new RuntimeException('Staged logo was not found.'); }
                    $newName = 'logo-' . bin2hex(random_bytes(12)) . '.' . strtolower(pathinfo($staged, PATHINFO_EXTENSION));
                    $logoTarget = monitor_root() . '/public/assets/uploads/' . $newName;
                    if (!copy($staged, $logoTarget)) { throw new RuntimeException('Could not save the logo.'); }
                    $defaults['logo_path'] = 'public/assets/uploads/' . $newName;
                }
                $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, UTC_TIMESTAMP())');
                foreach ($defaults as $key => $value) { $stmt->execute([$key, $value]); }
                $pdo->prepare('INSERT INTO system_meta (meta_key, meta_value, updated_at) VALUES (?, ?, UTC_TIMESTAMP())')->execute(['installed_version', 'v0.2.2']);
                $data = "<?php\nreturn " . var_export($_SESSION['install_db'], true) . ";\n";
                if (file_put_contents($tmp, $data, LOCK_EX) === false || !@chmod($tmp, 0600) || !rename($tmp, $cfg)) {
                    throw new RuntimeException('Could not create the protected configuration file.');
                }
                $createdConfig = true;
                if (file_put_contents($lock, "installed:v0.2.2\n", LOCK_EX) === false) { throw new RuntimeException('Could not lock the installer.'); }
                $createdLock = true;
                @chmod($lock, 0600);
                $pdo->commit();
                flock($installMutex, LOCK_UN);
                fclose($installMutex);
                if (!empty($_SESSION['install_logo'])) { @unlink(monitor_root() . '/storage/cache/' . $_SESSION['install_logo']); }
                unset($_SESSION['install_db'], $_SESSION['install_admin'], $_SESSION['install_brand'], $_SESSION['install_timezone'], $_SESSION['install_logo'], $_SESSION['install_step']);
                session_regenerate_id(true);
                flash('success', 'Installation completed. Sign in to your dashboard.');
                to(admin_url('login'));
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                @unlink($tmp);
                if ($createdConfig) { @unlink($cfg); }
                if ($createdLock) { @unlink($lock); }
                flock($installMutex, LOCK_UN);
                fclose($installMutex);
                if ($logoTarget) { @unlink($logoTarget); }
                throw $ex;
            }
        }
        if ($step < 6) {
            $_SESSION['install_step'] = $step + 1;
            to(url('install/index.php?step=' . ($step + 1)));
        }
    } catch (Throwable $ex) {
        error_log('Monitor setup failure: ' . get_class($ex) . ' at step ' . $step);
        $error = $step === 2 ? 'Database connection failed. Check the credentials and that the database exists.' : ($step === 6 ? 'Installation could not finish. Check database permissions and directory access, then retry.' : $ex->getMessage());
    }
}

$steps = [1 => 'Requirements', 2 => 'Database', 3 => 'Administrator', 4 => 'Branding', 5 => 'Preferences', 6 => 'Complete'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup · Monitor</title><link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>"></head>
<body class="install-body"><main class="install-layout">
<div class="install-heading"><div class="brand-glyph">M</div><div><span class="eyebrow">MONITOR SETUP</span><h1>Make it yours.</h1><p>Configure your monitoring platform in a few steps.</p></div></div>
<ol class="install-steps"><?php foreach ($steps as $number => $label): ?><li class="<?= $number === $step ? 'current' : ($number < $step ? 'complete' : '') ?>"><span><?= $number ?></span><?= e($label) ?></li><?php endforeach; ?></ol>
<section class="install-panel"><div class="panel-header"><div><span class="eyebrow">STEP <?= $step ?> OF 6</span><h2><?= e($steps[$step]) ?></h2></div><span class="pill pill-neutral">v0.2.2</span></div>
<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= e(url('install/index.php?step=' . $step)) ?>" enctype="multipart/form-data">
<?= Security::csrfField() ?><input type="hidden" name="step" value="<?= $step ?>">
<?php if ($step === 1): ?>
<p class="muted">All checks must pass before setup can continue.</p><div class="check-list"><?php foreach ($requirements as $label => $ok): ?><div class="check-row"><span><?= e($label) ?></span><span class="pill <?= $ok ? 'pill-success' : 'pill-danger' ?>"><?= $ok ? 'Passed' : 'Failed' ?></span></div><?php endforeach; ?></div>
<?php elseif ($step === 2): $db = $_SESSION['install_db'] ?? []; ?>
<p class="muted">Enter credentials for an existing, empty MySQL database.</p><div class="field-grid"><label>Database host<input name="host" required value="<?= e((string) ($input['host'] ?? $db['host'] ?? 'localhost')) ?>"></label><label>Database port<input type="number" name="port" min="1" max="65535" required value="<?= e((string) ($input['port'] ?? $db['port'] ?? '3306')) ?>"></label></div><label>Database name<input name="name" required value="<?= e((string) ($input['name'] ?? $db['name'] ?? '')) ?>"></label><label>Database username<input name="username" required value="<?= e((string) ($input['username'] ?? $db['username'] ?? '')) ?>"></label><label>Database password<input type="password" name="password" autocomplete="new-password" value="" placeholder="Enter database password"></label>
<?php elseif ($step === 3): $a = $_SESSION['install_admin'] ?? []; ?>
<p class="muted">This account will have full administrative access.</p><label>Your name<input name="admin_name" required maxlength="140" value="<?= e((string) ($input['admin_name'] ?? $a['name'] ?? '')) ?>"></label><label>Email address<input type="email" name="admin_email" required maxlength="190" autocomplete="email" value="<?= e((string) ($input['admin_email'] ?? $a['email'] ?? '')) ?>"></label><label>Password<input type="password" name="admin_password" autocomplete="new-password" required minlength="8" placeholder="At least 8 characters"></label><label>Confirm password<input type="password" name="confirm_password" autocomplete="new-password" required minlength="8"></label>
<?php elseif ($step === 4): $b = $_SESSION['install_brand'] ?? []; ?>
<p class="muted">The name and logo appear in the dashboard and public status page.</p><label>Product name<input name="app_name" maxlength="120" required value="<?= e((string) ($input['app_name'] ?? $b['app_name'] ?? 'Monitor')) ?>"></label><label>Company or organization<input name="company_name" maxlength="160" value="<?= e((string) ($input['company_name'] ?? $b['company_name'] ?? '')) ?>"></label><label>Logo (optional)<input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,.gif,image/*"></label><p class="help">JPG, PNG, WebP or GIF. Up to 3 MB. You can change this later.</p>
<?php elseif ($step === 5): ?>
<p class="muted">All timestamps are stored in UTC and shown in this timezone.</p><label>Application timezone<select name="timezone" required><?php $selected = (string) ($_POST['timezone'] ?? $_SESSION['install_timezone'] ?? 'UTC'); foreach (DateTimeZone::listIdentifiers() as $timezone): ?><option value="<?= e($timezone) ?>" <?= $selected === $timezone ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $timezone)) ?></option><?php endforeach; ?></select></label>
<?php else: ?>
<p class="muted">Everything is ready. We will create the tables, save your settings, and lock the installer.</p><div class="summary-list"><div><span>Platform</span><strong><?= e((string) ($_SESSION['install_brand']['app_name'] ?? 'Monitor')) ?></strong></div><div><span>Administrator</span><strong><?= e((string) ($_SESSION['install_admin']['email'] ?? '')) ?></strong></div><div><span>Timezone</span><strong><?= e((string) ($_SESSION['install_timezone'] ?? 'UTC')) ?></strong></div></div>
<?php endif; ?>
<div class="form-actions"><?php if ($step > 1): ?><span class="muted">Step <?= $step ?> of 6</span><?php else: ?><span class="muted">Shared hosting ready</span><?php endif; ?><button class="button primary" type="submit" <?= ($step === 1 && !$ready) ? 'disabled' : '' ?>><?= $step === 6 ? 'Install Monitor' : 'Continue' ?><span aria-hidden="true">→</span></button></div>
</form></section><p class="install-note">Your configuration and administrator credentials stay on your server.</p></main></body></html>
