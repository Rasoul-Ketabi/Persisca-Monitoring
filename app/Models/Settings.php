<?php
declare(strict_types=1);
namespace Monitor\Models;

use Monitor\Core\Database;
use PDO;

final class Settings {
    private static ?array $cache = null;

    public static function defaults(): array {
        return [
            'app_name' => 'Monitor', 'app_short_name' => 'Monitor',
            'company_name' => '', 'base_url' => '', 'timezone' => 'UTC', 'date_format' => 'M j, Y',
            'logo_path' => '', 'logo_compact_path' => '', 'favicon_path' => '',
            'og_image_path' => 'public/assets/images/status-og-default.jpg', 'meta_title' => 'System Status',
            'meta_description' => 'View the current service status and operational updates.',
            'og_title' => '', 'og_description' => '', 'footer_text' => '',
            'support_url' => '', 'accent_color' => '#536dfe',
            'check_retention_days' => '90', 'notification_log_retention_days' => '90', 'cron_stale_after_minutes' => '10', 'cron_batch_size' => '25',
            'status_page_enabled' => '1', 'status_show_uptime' => '1', 'status_show_response_time' => '1', 'status_show_incidents' => '1', 'status_show_footer' => '1',
            'telegram_enabled' => '0', 'telegram_notify_down' => '1', 'telegram_notify_recovery' => '1',
            'telegram_bot_token' => '', 'telegram_chat_id' => '', 'telegram_bot_username' => '', 'telegram_webhook_secret' => '', 'telegram_webhook_configured' => '0', 'mail_from_email' => '', 'mail_from_name' => '',
        ];
    }

    public static function all(): array {
        if (self::$cache !== null) { return self::$cache; }
        $data = self::defaults();
        $stmt = Database::connection()->query('SELECT setting_key, setting_value FROM settings');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data[$row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
        return self::$cache = $data;
    }
    public static function get(string $key, string $fallback = ''): string {
        return (string) (self::all()[$key] ?? $fallback);
    }
    public static function putMany(array $data): void {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)');
        foreach ($data as $key => $value) { $stmt->execute([(string) $key, (string) $value]); }
        self::$cache = null;
    }
}
