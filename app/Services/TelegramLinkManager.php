<?php
declare(strict_types=1);
namespace Monitor\Services;

use Monitor\Core\Database;
use Monitor\Models\Settings;
use PDO;
use RuntimeException;
use Throwable;

/** Deep-link authentication for a single private Telegram account per Portal User. */
final class TelegramLinkManager {
    private const TTL_MINUTES = 15;
    public static function botUsername(): string {
        $username = Settings::get('telegram_bot_username');
        if (preg_match('/^[A-Za-z][A-Za-z0-9_]{4,31}$/D', $username)) { return $username; }
        $token = Settings::get('telegram_bot_token');
        if (!TelegramNotifier::validTokenShape($token)) { throw new RuntimeException('Telegram bot is not configured. Contact an administrator.'); }
        $result = TelegramNotifier::api($token, 'getMe');
        $username = (string)($result['data']['result']['username'] ?? '');
        if (!$result['success'] || !preg_match('/^[A-Za-z][A-Za-z0-9_]{4,31}$/D', $username)) { throw new RuntimeException('Cannot verify the Telegram bot. Contact an administrator.'); }
        Settings::putMany(['telegram_bot_username' => $username]);
        return $username;
    }
    /** Raw one-time value is returned once; only its SHA-256 digest enters persistent storage. */
    public static function connect(int $portalUserId): string {
        if (Settings::get('telegram_webhook_configured') !== '1' || Settings::get('telegram_webhook_secret') === '') {
            throw new RuntimeException('Telegram account linking is not ready. Ask an administrator to configure the webhook.');
        }
        $username = self::botUsername();
        $token = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $query = $pdo->prepare("SELECT id FROM portal_users WHERE id=? AND status='active' FOR UPDATE");
            $query->execute([$portalUserId]);
            if (!$query->fetchColumn()) { throw new RuntimeException('Account unavailable.'); }
            $pdo->prepare('UPDATE telegram_link_tokens SET consumed_at=UTC_TIMESTAMP() WHERE portal_user_id=? AND consumed_at IS NULL')->execute([$portalUserId]);
            $pdo->prepare('INSERT INTO telegram_link_tokens(portal_user_id,token_hash,expires_at,created_at) VALUES(?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE),UTC_TIMESTAMP())')
                ->execute([$portalUserId, hash('sha256',$token)]);
            $pdo->commit();
        } catch(Throwable $error) { if ($pdo->inTransaction()) {$pdo->rollBack();} throw $error; }
        return 'https://t.me/'.$username.'?start='.$token;
    }
    public static function disconnect(int $portalUserId): void {
        $pdo=Database::connection();$pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE portal_users SET telegram_chat_id=NULL,telegram_user_id=NULL,telegram_username=NULL,telegram_connected_at=NULL,telegram_notifications_enabled=0,updated_at=UTC_TIMESTAMP() WHERE id=?')->execute([$portalUserId]);
            $pdo->prepare('UPDATE telegram_link_tokens SET consumed_at=UTC_TIMESTAMP() WHERE portal_user_id=? AND consumed_at IS NULL')->execute([$portalUserId]);
            // Old pending deliveries are never redirected to a disconnected/replaced account.
            $pdo->prepare("UPDATE notification_logs SET status='failed',error_code='disconnected',error_message='Recipient disconnected.',next_attempt_at=NULL,updated_at=UTC_TIMESTAMP() WHERE recipient_type='portal_user' AND recipient_id=? AND status IN ('pending','processing')")->execute([$portalUserId]);
            $pdo->commit();
        } catch(Throwable $error) {if($pdo->inTransaction()) {$pdo->rollBack();}throw $error;}
    }
    public static function setAlerts(int $portalUserId, bool $enabled): void {
        $q=Database::connection()->prepare('UPDATE portal_users SET telegram_notifications_enabled=?,updated_at=UTC_TIMESTAMP() WHERE id=? AND telegram_chat_id IS NOT NULL AND status=\'active\'');
        $q->execute([$enabled?1:0,$portalUserId]);
    }
    /** The webhook has already authenticated its secret header. All linking is transactional. */
    public static function consumeStart(string $start, string $chatId, string $userId, ?string $username): bool {
        if (!preg_match('/^[A-Za-z0-9_-]{32}$/D',$start) || !preg_match('/^[0-9]{1,22}$/D',$userId) || $chatId!==$userId) {return false;}
        $pdo=Database::connection();$pdo->beginTransaction();
        try {
            $q=$pdo->prepare("SELECT t.id,t.portal_user_id FROM telegram_link_tokens t JOIN portal_users p ON p.id=t.portal_user_id WHERE t.token_hash=? AND t.consumed_at IS NULL AND t.expires_at>UTC_TIMESTAMP() AND p.status='active' LIMIT 1 FOR UPDATE");
            $q->execute([hash('sha256',$start)]);$row=$q->fetch(PDO::FETCH_ASSOC);
            if (!$row) {$pdo->commit();return false;}
            // A Telegram identity cannot be linked silently to several portal accounts.
            $q=$pdo->prepare('SELECT id FROM portal_users WHERE telegram_user_id=? AND id<>? LIMIT 1');
            $q->execute([$userId,(int)$row['portal_user_id']]);
            if ($q->fetchColumn()) {$pdo->commit();return false;}
            $cleanUsername = $username!==null && preg_match('/^[A-Za-z0-9_]{1,32}$/D',$username) ? $username : null;
            $pdo->prepare('UPDATE portal_users SET telegram_chat_id=?,telegram_user_id=?,telegram_username=?,telegram_connected_at=UTC_TIMESTAMP(),telegram_notifications_enabled=1,updated_at=UTC_TIMESTAMP() WHERE id=? AND status=\'active\'')
                ->execute([$chatId,$userId,$cleanUsername,(int)$row['portal_user_id']]);
            $pdo->prepare('UPDATE telegram_link_tokens SET consumed_at=UTC_TIMESTAMP() WHERE portal_user_id=? AND consumed_at IS NULL')->execute([(int)$row['portal_user_id']]);
            // In-flight retries to an earlier connection are invalidated (even if same user changed chats).
            $pdo->prepare("UPDATE notification_logs SET status='failed',error_code='reconnected',error_message='Recipient connection changed.',next_attempt_at=NULL,updated_at=UTC_TIMESTAMP() WHERE recipient_type='portal_user' AND recipient_id=? AND destination_chat_id<>? AND status IN ('pending','processing')")
                ->execute([(int)$row['portal_user_id'],$chatId]);
            $pdo->commit();return true;
        } catch(Throwable $error) {if($pdo->inTransaction()){$pdo->rollBack();}throw $error;}
    }
    public static function configureWebhook(): array {
        $token=Settings::get('telegram_bot_token');
        if (!TelegramNotifier::validTokenShape($token)) {throw new RuntimeException('Save a valid Telegram Bot Token first.');}
        $base=rtrim(Settings::get('base_url'),'/');
        $parts=parse_url($base);
        if (!$parts || strtolower((string)($parts['scheme']??''))!=='https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {throw new RuntimeException('Set an HTTPS Base URL in General Settings before configuring the webhook.');}
        $bot=TelegramNotifier::api($token,'getMe');
        $username=(string)($bot['data']['result']['username']??'');
        if (!$bot['success'] || !preg_match('/^[A-Za-z][A-Za-z0-9_]{4,31}$/D',$username)) {throw new RuntimeException('Telegram bot identity could not be verified.');}
        $secret=bin2hex(random_bytes(32));
        // Persist before registering: Telegram can deliver immediately after setWebhook.
        Settings::putMany(['telegram_webhook_secret'=>$secret,'telegram_bot_username'=>$username,'telegram_webhook_configured'=>'0']);
        $result=TelegramNotifier::api($token,'setWebhook',[
            'url'=>$base.'/telegram/webhook/', 'secret_token'=>$secret,
            'allowed_updates'=>json_encode(['message']), 'drop_pending_updates'=>'false'
        ]);
        if ($result['success']) {Settings::putMany(['telegram_webhook_configured'=>'1']);}
        return ['success'=>$result['success'],'error_message'=>$result['error_message'],'username'=>$username];
    }
    /** Return a generic 200 for authenticated but irrelevant Telegram update content. */
    public static function handleUpdate(array $update): void {
        $msg=$update['message']??null;
        if (!is_array($msg)) {return;}
        $chat=$msg['chat']??null;$from=$msg['from']??null;
        if (!is_array($chat)||!is_array($from) || ($chat['type']??'')!=='private' || !empty($from['is_bot'])) {return;}
        $chatId=(string)($chat['id']??'');$fromId=(string)($from['id']??'');
        if ($chatId==='' || $fromId==='' || $chatId!==$fromId || !is_string($msg['text']??null)) {return;}
        if (!preg_match('~^/start(?:@[A-Za-z0-9_]+)?\s+([A-Za-z0-9_-]{32})\s*$~D',(string)$msg['text'],$m)) {return;}
        $linked=self::consumeStart($m[1],$chatId,$fromId,is_string($from['username']??null)?$from['username']:null);
        if ($linked) {
            $message='✅ <b>Your Telegram account is connected to '.TelegramNotifier::escape(Settings::get('app_name','Monitor')).'.</b>'."\n\n".'You will receive alerts only for monitors assigned to your account.';
            // Message failure must not roll back successful linking.
            TelegramNotifier::send(Settings::get('telegram_bot_token'),$chatId,$message);
        }
    }
}
