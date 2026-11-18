<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {http_response_code(404);exit;}
require dirname(__DIR__).'/bootstrap.php';
use Monitor\Services\NotificationManager;
use Monitor\Models\Settings;
use Monitor\Core\Database;

// No MySQL driver needed. These fakes capture the actual SQL outbox decisions.
final class RoutingTestPDO extends PDO {
    public array $inserted=[];
    public array $queries=[];
    public ?array $owner=null;
    public array $dueRows=[];
    public array $claimed=[];
    public array $completed=[];
    public function __construct() {}
    public function prepare(string $query,array $options=[]): PDOStatement|false {
        $this->queries[]=$query;
        return new RoutingTestStatement($this,$query);
    }
    public function query(string $query, ?int $fetchMode=null, mixed ...$fetchModeArgs): PDOStatement|false {
        $this->queries[]=$query;
        return new RoutingTestStatement($this,$query);
    }
    public function exec(string $statement): int|false {$this->queries[]=$statement;return 0;}
}
final class RoutingTestStatement extends PDOStatement {
    private array $params=[];
    public function __construct(private RoutingTestPDO $connection, private string $sql) {}
    public function execute(?array $params=null): bool {
        $this->params=$params??[];
        if (str_contains($this->sql,'INSERT INTO notification_logs')) {
            $this->connection->inserted[]=[
                'monitor_id'=>$this->params[0], 'incident_id'=>$this->params[1],
                'destination_chat_id'=>$this->params[2], 'recipient_type'=>$this->params[3],
                'recipient_id'=>$this->params[4], 'event_type'=>$this->params[5],
                'status'=>$this->params[6], 'error_code'=>$this->params[8],
            ];
        }
        if (str_contains($this->sql,"status='processing',attempt_count=attempt_count+1")) {
            $this->connection->claimed[]=$this->params[0];
        }
        if (str_contains($this->sql,"provider_message_id=")) {
            $this->connection->completed[]=$this->params;
        }
        return true;
    }
    public function rowCount(): int {return 1;}
    public function fetch(int $mode=PDO::FETCH_DEFAULT, int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0):mixed {
        if (str_contains($this->sql,'FROM portal_users')) {return $this->connection->owner?:false;}
        if (str_contains($this->sql,'FROM notification_logs l JOIN monitors m')) {
            return $this->connection->dueRows[$this->params[0]]??false;
        }
        return false;
    }
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT, mixed ...$args):array {
        if (str_contains($this->sql,'SELECT id FROM notification_logs')) {return array_keys($this->connection->dueRows);}
        return [];
    }
}

$count=0;$errors=0;
$check=static function(bool $pass,string $label)use(&$count,&$errors):void {
    $count++;if (!$pass) {$errors++;}echo ($pass?'PASS ':'FAIL ').$label.PHP_EOL;
};
$settings=[
    'telegram_enabled'=>'1','telegram_notify_down'=>'1','telegram_notify_recovery'=>'1',
    'telegram_bot_token'=>'123456789:abcdefghijklmnop','telegram_chat_id'=>"12345\n-1001122334455",
];
$settingsCache=new ReflectionProperty(Settings::class,'cache');
$settingsCache->setValue(null,$settings);
$pdo=new RoutingTestPDO();
$platform=['id'=>12,'portal_user_id'=>null,'telegram_notifications_enabled'=>1];
$user=['id'=>14,'portal_user_id'=>21,'telegram_notifications_enabled'=>1];
$transition=['transition'=>'down','incident_id'=>400];
$check(NotificationManager::recipientForMonitor($platform)===['type'=>'admin_global','id'=>0],'Null owner routes to global only');
$check(NotificationManager::recipientForMonitor($user)===['type'=>'portal_user','id'=>21],'Positive owner routes to user only');
try {NotificationManager::recipientForMonitor(['portal_user_id'=>0]);$check(false,'Invalid owner blocked');}
catch (InvalidArgumentException $e) {$check(true,'Invalid owner blocked');}
$check(NotificationManager::eligible($platform,$transition,$settings),'Platform admin eligibility retained');
$check(!NotificationManager::eligible($user,$transition,$settings),'User-owned cannot enter global eligibility');
NotificationManager::enqueue($pdo,$platform,$transition);
$check(count($pdo->inserted)===2,'Platform event sends to both admin destinations');
$check(count(array_filter($pdo->inserted,static fn($entry)=>$entry['recipient_type']!=='admin_global'))===0,'No user recipient created for platform');
$check(!array_filter($pdo->queries,static fn($q)=>str_contains($q,'FROM portal_users')),'Platform branch never queries portal users');
$check(count(array_unique(array_column($pdo->inserted,'destination_chat_id')))===2,'Admin multi-chat destinations preserved');
$settingsCache->setValue(null, array_merge($settings,['telegram_enabled'=>'0']));
$pdoOff=new RoutingTestPDO();
NotificationManager::enqueue($pdoOff,$platform,$transition);
$check($pdoOff->inserted===[],'Disabled global alerts do not queue platform deliveries');
$settingsCache->setValue(null,$settings);
$pdoUser=new RoutingTestPDO();$pdoUser->owner=['id'=>21,'status'=>'active','telegram_chat_id'=>'99887766','telegram_notifications_enabled'=>1];
NotificationManager::enqueue($pdoUser,$user,$transition);
$check(count($pdoUser->inserted)===1,'User-owned creates exactly one personal recipient');
$check($pdoUser->inserted[0]['recipient_type']==='portal_user' && $pdoUser->inserted[0]['recipient_id']===21,'Personal recipient bound to owner ID');
$check($pdoUser->inserted[0]['destination_chat_id']==='99887766','Uses only owner connected Telegram chat');
$check($pdoUser->inserted[0]['status']==='pending','Connected eligible user queued');
$check(!in_array('12345',array_column($pdoUser->inserted,'destination_chat_id'),true),'Admin destination never queued for user monitor');
$check(count(array_filter($pdoUser->queries,static fn($q)=>str_contains($q,'FROM portal_users')))>0,'Personal branch queries owner');
$noGlobal=array_merge($settings,['telegram_enabled'=>'0','telegram_chat_id'=>'']);
$settingsCache->setValue(null,$noGlobal);
$pdoInd=new RoutingTestPDO();$pdoInd->owner=$pdoUser->owner;
NotificationManager::enqueue($pdoInd,$user,$transition);
$check(count($pdoInd->inserted)===1 && $pdoInd->inserted[0]['status']==='pending','User alerts independent of global admin notification toggle');
$settingsCache->setValue(null,$settings);
foreach ([
    ['owner'=>['id'=>21,'status'=>'active','telegram_chat_id'=>null,'telegram_notifications_enabled'=>1],'code'=>'not_connected'],
    ['owner'=>['id'=>21,'status'=>'disabled','telegram_chat_id'=>'99887766','telegram_notifications_enabled'=>1],'code'=>'owner_disabled'],
    ['owner'=>['id'=>21,'status'=>'active','telegram_chat_id'=>'99887766','telegram_notifications_enabled'=>0],'code'=>'alerts_disabled'],
    ['owner'=>null,'code'=>'owner_unavailable'],
] as $case) {
    $stub=new RoutingTestPDO();$stub->owner=$case['owner'];NotificationManager::enqueue($stub,$user,$transition);
    $entry=$stub->inserted[0]??null;
    $check($entry!==null && $entry['recipient_type']==='portal_user' && $entry['status']==='failed' && $entry['error_code']===$case['code'] && $entry['destination_chat_id']==='', 'No fallback: '.$case['code']);
}
$settingsCache->setValue(null,array_merge($settings,['telegram_bot_token'=>'']));
$stub=new RoutingTestPDO();$stub->owner=$pdoUser->owner;NotificationManager::enqueue($stub,$user,$transition);
$check(($stub->inserted[0]['error_code']??'')==='bot_unavailable','Missing bot token logs user unavailability without admin fallback');
$settingsCache->setValue(null,$settings);
$off=$user;$off['telegram_notifications_enabled']=0;$stub=new RoutingTestPDO();$stub->owner=$pdoUser->owner;
NotificationManager::enqueue($stub,$off,$transition);
$check($stub->inserted===[],'Per-monitor disable queues nobody');
$stub=new RoutingTestPDO();$stub->owner=$pdoUser->owner;
NotificationManager::enqueue($stub,$user,['transition'=>'none','incident_id'=>400]);
$check($stub->inserted===[],'Continued outage queues nobody');
NotificationManager::enqueue($stub,$user,['transition'=>'recovered','incident_id'=>400]);
$check(count($stub->inserted)===1 && $stub->inserted[0]['event_type']==='recovered','Recovery routes to owner only');
$check(NotificationManager::matchesOwnership(['portal_user_id'=>null,'recipient_type'=>'admin_global','recipient_id'=>0]),'Platform/global stored delivery permitted');
$check(!NotificationManager::matchesOwnership(['portal_user_id'=>21,'recipient_type'=>'admin_global','recipient_id'=>0]),'Old global row for portal monitor rejected');
$check(!NotificationManager::matchesOwnership(['portal_user_id'=>null,'recipient_type'=>'portal_user','recipient_id'=>21]),'Old portal row for platform monitor rejected');
$check(!NotificationManager::matchesOwnership(['portal_user_id'=>22,'recipient_type'=>'portal_user','recipient_id'=>21]),'Transferred monitor old owner rejected');
$check(NotificationManager::matchesOwnership(['portal_user_id'=>21,'recipient_type'=>'portal_user','recipient_id'=>21]),'Current connected owner allowed');
$check(!NotificationManager::matchesOwnership(['portal_user_id'=>0,'recipient_type'=>'admin_global','recipient_id'=>0]),'Invalid ownership fails closed');
// Confirm the actual delivery loop marks a pending pre-upgrade wrong-recipient row terminal without send.
$settingsCache->setValue(null,$settings);
$queue=new RoutingTestPDO();$queue->dueRows=[1=>[
    'id'=>1,'attempt_count'=>1,'event_type'=>'down','destination_chat_id'=>'12345',
    'recipient_type'=>'admin_global','recipient_id'=>0,'portal_user_id'=>21,
    'telegram_notifications_enabled'=>1,
]];
$oldConnection=new ReflectionProperty(Database::class,'connection');$oldConnection->setValue(null,$queue);
$countDue=NotificationManager::deliverDue(3);
$check($countDue===1 && count($queue->completed)===1,'Legacy wrong-owner queued alert was processed without a Telegram request');
$check($queue->completed[0][0]==='failed' && $queue->completed[0][2]==='wrong_owner','Wrong-owner outbox row terminally rejected');
$oldConnection->setValue(null,null);
$source=(string)file_get_contents(dirname(__DIR__).'/app/Services/NotificationManager.php');
$check(str_contains($source,'if (!self::matchesOwnership($row))'),'Ownership guard appears before Telegram send');
$check(strpos($source,'if (!self::matchesOwnership($row))')<strpos($source,'TelegramNotifier::send($token,'),'Guard precedes send operation');
$schema=(string)file_get_contents(dirname(__DIR__).'/database/schema.sql');
$patch=(string)file_get_contents(dirname(__DIR__).'/database/patches/v0.2.4.sql');
$check(str_contains($schema,'uniq_notification_recipient_event'),'Recipient-aware idempotency retained');
$check(str_contains($patch,"l.status IN ('pending','processing')") && str_contains($patch,'wrong_owner'),'SQL quarantines only queued misrouted records');
$check(!str_contains($patch,'DELETE FROM') && !str_contains($patch,'DROP TABLE'),'Migration non-destructive');
$check(trim((string)file_get_contents(dirname(__DIR__).'/VERSION'))==='v0.2.4','Release version correct');
echo ($count-$errors).'/'.$count.' passed'.PHP_EOL;
exit($errors?1:0);
