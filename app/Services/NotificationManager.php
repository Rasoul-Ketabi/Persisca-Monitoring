<?php
declare(strict_types=1);
namespace Monitor\Services;

use Monitor\Core\Database;
use Monitor\Models\Settings;
use PDO;
use Throwable;

/** Independent outbox recipients, with claims and at most three send attempts per record. */
final class NotificationManager {
    public static function eligible(array $monitor, array $transition, array $settings): bool {
        $event=$transition['transition']??'none';
        if (!in_array($event,['down','recovered'],true) || empty($transition['incident_id']) || empty($monitor['telegram_notifications_enabled'])
            || ($settings['telegram_enabled']??'0')!=='1' || empty($settings['telegram_bot_token'])
            || ($settings['telegram_notify_'.($event==='down'?'down':'recovery')]??'0')!=='1') {return false;}
        try {return TelegramDestinations::parse((string)($settings['telegram_chat_id']??''))!==[];}
        catch (\InvalidArgumentException $e) {return false;}
    }
    /** Called INSIDE the scheduled-check + incident transaction, never from manual Test Now. */
    public static function enqueue(PDO $pdo,array $monitor,array $transition): void {
        $event=(string)($transition['transition']??'none');
        if (!in_array($event,['down','recovered'],true) || empty($transition['incident_id']) || empty($monitor['telegram_notifications_enabled'])) {return;}
        $config=Settings::all();
        if (empty($config['telegram_bot_token'])) {return;}
        $stmt=$pdo->prepare("INSERT INTO notification_logs(monitor_id,incident_id,channel,destination_chat_id,recipient_type,recipient_id,event_type,status,attempt_count,next_attempt_at,created_at,updated_at) VALUES (?,?,'telegram',?,?,?,?,'pending',0,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE id=id");
        if (self::eligible($monitor,$transition,$config)) {
            foreach (TelegramDestinations::parse((string)$config['telegram_chat_id']) as $destination) {
                try {$stmt->execute([(int)$monitor['id'],(int)$transition['incident_id'],$destination,'admin_global',0,$event]);}
                catch(Throwable $e) {error_log('Global notification enqueue failed: '.get_class($e));}
            }
        }
        // The owner's status and connected recipient are checked in the data layer.
        $ownerId=(int)($monitor['portal_user_id']??0);
        if ($ownerId<1) {return;}
        $q=$pdo->prepare("SELECT id,telegram_chat_id,telegram_notifications_enabled FROM portal_users WHERE id=? AND status='active' LIMIT 1");
        $q->execute([$ownerId]);$owner=$q->fetch(PDO::FETCH_ASSOC);
        if (!$owner || !(int)$owner['telegram_notifications_enabled'] || empty($owner['telegram_chat_id'])) {return;}
        $chat=(string)$owner['telegram_chat_id'];
        if (!TelegramNotifier::validChatIdShape($chat)) {return;}
        $stmt->execute([(int)$monitor['id'],(int)$transition['incident_id'],$chat,'portal_user',$ownerId,$event]);
    }
    public static function deliverDue(int $limit=10,?float $deadline=null): int {
        $config=Settings::all();
        $token=(string)($config['telegram_bot_token']??'');
        if (!TelegramNotifier::validTokenShape($token)) {return 0;}
        $pdo=Database::connection();$limit=max(1,min(20,$limit));
        $pdo->exec("UPDATE notification_logs SET status='failed',error_code='max_attempts',error_message='Delivery attempts exhausted.',next_attempt_at=NULL,updated_at=UTC_TIMESTAMP() WHERE channel='telegram' AND status='processing' AND attempt_count>=3 AND next_attempt_at<=UTC_TIMESTAMP()");
        // Select more than the attempt budget so a disabled destination cannot starve others.
        $candidates=$pdo->query("SELECT id FROM notification_logs WHERE channel='telegram' AND status IN ('pending','processing') AND next_attempt_at<=UTC_TIMESTAMP() ORDER BY next_attempt_at,id LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);
        $count=0;
        foreach($candidates as $id) {
            if ($count >= $limit || ($deadline!==null && microtime(true)>=$deadline)) {break;}
            $id=(int)$id;
            try {
                $q=$pdo->prepare("UPDATE notification_logs SET status='processing',attempt_count=attempt_count+1,next_attempt_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 5 MINUTE),updated_at=UTC_TIMESTAMP() WHERE id=? AND status IN ('pending','processing') AND next_attempt_at<=UTC_TIMESTAMP() AND attempt_count<3");
                $q->execute([$id]);if($q->rowCount()!==1){continue;}
                $count++;
                $q=$pdo->prepare("SELECT l.id,l.attempt_count,l.event_type,l.destination_chat_id,l.recipient_type,l.recipient_id,
                    m.name AS monitor_name,m.url AS monitor_url,m.is_public,m.portal_user_id,m.telegram_notifications_enabled,
                    p.status AS portal_status,p.telegram_chat_id AS portal_chat_id,p.telegram_notifications_enabled AS portal_alerts,p.public_status_enabled,p.public_slug,
                    i.started_at,i.detected_at,i.recovered_at,i.duration_seconds,i.last_error_type,i.last_http_status,
                    oc.attempt_count AS opening_attempts,cc.http_status AS recovery_http,cc.response_time_ms AS recovery_response
                    FROM notification_logs l JOIN monitors m ON m.id=l.monitor_id JOIN incidents i ON i.id=l.incident_id
                    LEFT JOIN portal_users p ON p.id=l.recipient_id AND l.recipient_type='portal_user'
                    LEFT JOIN monitor_checks oc ON oc.id=i.opening_check_id LEFT JOIN monitor_checks cc ON cc.id=i.closing_check_id WHERE l.id=? LIMIT 1");
                $q->execute([$id]);$row=$q->fetch(PDO::FETCH_ASSOC);
                if (!$row || !(int)$row['telegram_notifications_enabled'] || !in_array($row['event_type'],['down','recovered'],true)) {
                    self::complete($pdo,$id,['success'=>false,'error_code'=>'disabled','error_message'=>'This notification is no longer eligible.'],3);continue;
                }
                if ($row['recipient_type']==='portal_user') {
                    if ((int)$row['portal_user_id']!==(int)$row['recipient_id'] || $row['portal_status']!=='active'
                        || !(int)$row['portal_alerts'] || $row['portal_chat_id']!==$row['destination_chat_id']) {
                        self::complete($pdo,$id,['success'=>false,'error_code'=>'disconnected','error_message'=>'Portal recipient is no longer eligible.'],3);continue;
                    }
                } elseif($row['recipient_type']==='admin_global') {
                    if (($config['telegram_enabled']??'0')!=='1' || ($config['telegram_notify_'.($row['event_type']==='down'?'down':'recovery')]??'0')!=='1') {
                        self::complete($pdo,$id,['success'=>false,'error_code'=>'disabled','error_message'=>'Global notifications are disabled.'],3);continue;
                    }
                    try {$targets=TelegramDestinations::parse((string)($config['telegram_chat_id']??''));}
                    catch(\InvalidArgumentException $e){$targets=[];}
                    $normalize=static fn(string $s):string=>str_starts_with($s,'@')?strtolower($s):$s;
                    if(!in_array($normalize((string)$row['destination_chat_id']),array_map($normalize,$targets),true)) {
                        self::complete($pdo,$id,['success'=>false,'error_code'=>'destination_removed','error_message'=>'Destination removed.'],3);continue;
                    }
                } else {
                    self::complete($pdo,$id,['success'=>false,'error_code'=>'invalid_recipient','error_message'=>'Unknown recipient.'],3);continue;
                }
                $message=TelegramNotifier::message($row);
                if ($row['recipient_type']==='portal_user' && !empty($row['public_status_enabled']) && !empty($row['public_slug']) && !empty($row['is_public'])) {
                    $message.="\nStatus: ".TelegramNotifier::escape(absolute_url('status/'.$row['public_slug'].'/'));
                }
                $outcome=TelegramNotifier::send($token,(string)$row['destination_chat_id'],$message);
                self::complete($pdo,$id,$outcome,(int)$row['attempt_count']);
            } catch(Throwable $e) {
                error_log('Telegram outbox failure: '.get_class($e));
                try {$q=$pdo->prepare('SELECT attempt_count FROM notification_logs WHERE id=?');$q->execute([$id]);$tries=(int)($q->fetchColumn()?:3);
                    self::complete($pdo,$id,['success'=>false,'error_code'=>'runtime','error_message'=>'Delivery encountered an error.'],$tries);
                } catch(Throwable $ignored) {/* Lease expires for next eligible cron. */}
            }
        }
        return $count;
    }
    private static function complete(PDO $pdo,int $id,array $outcome,int $attempt):void {
        $success=!empty($outcome['success']);
        $code=(string)($outcome['error_code']??'');
        $terminal=!$success && ($attempt>=3 || in_array($code,['400','401','403','404','disabled','disconnected','destination_removed','invalid_recipient'],true));
        $status=$success?'sent':($terminal?'failed':'pending');
        $delay=$attempt<=1?1:5;
        $q=$pdo->prepare("UPDATE notification_logs SET status=?,provider_message_id=?,error_code=?,error_message=?,sent_at=CASE WHEN ?=1 THEN UTC_TIMESTAMP() ELSE sent_at END,next_attempt_at=CASE WHEN ?=1 OR ?=1 THEN NULL ELSE DATE_ADD(UTC_TIMESTAMP(),INTERVAL ? MINUTE) END,updated_at=UTC_TIMESTAMP() WHERE id=? AND status='processing'");
        $q->execute([$status,$success?($outcome['provider_message_id']??null):null,$success?null:$code,$success?null:mb_safe_substr((string)($outcome['error_message']??'Delivery error.'),200),$success?1:0,$success?1:0,$terminal?1:0,$delay,$id]);
    }
}
