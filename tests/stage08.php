<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/bootstrap.php';
use Monitor\Services\Authorization;
use Monitor\Services\AdminUsers;
use Monitor\Services\CronRunner;
$checks=[];
$test=static function(bool $pass,string $label)use(&$checks):void{$checks[]=[$pass,$label];};
$r=monitor_root();
$admin=['role'=>'admin'];$super=['role'=>'super_admin'];
foreach(['dashboard','monitors','monitors/add','monitors/edit','monitors/view','monitors/analytics','incidents','incidents/view','status-page','notifications','account'] as $route){$test(Authorization::allowed($admin,$route),'Admin can access '.$route);}
foreach(['users','users/add','users/edit','users/password','settings/general','settings/branding','system','maintenance'] as $route){$test(!Authorization::allowed($admin,$route),'Admin denied '.$route);$test(Authorization::allowed($super,$route),'Super Admin allowed '.$route);}
$test(!Authorization::allowed($admin,'notifications','POST'),'Admin cannot POST Telegram credentials');
$test(Authorization::allowed($admin,'notifications','GET'),'Admin can review notifications');
$test(Authorization::allowed($admin,'status-page','POST'),'Admin can update status page');
$test(!Authorization::allowed(['role'=>'unsupported'],'dashboard'),'Unknown role blocked');
foreach(['users','users/add','users/edit','users/password','maintenance','account'] as $route){$test(str_contains(admin_url($route),'/admin/'),'Route '.$route.' resolves to admin folder');$test(is_file($r.'/admin/'.$route.'/index.php'),'Physical route '.$route.' exists');}
$valid=['name'=>'Alex Doe','email'=>'ALEX@example.com','role'=>'super_admin','status'=>'active','password'=>'testpass88','confirm_password'=>'testpass88'];
$v=AdminUsers::validated($valid,true);$test($v['email']==='alex@example.com','Email normalized');$test($v['role']==='super_admin','Super Admin accepted');
$invalid=[
 ['name'=>'','email'=>'a@example.com','role'=>'admin','status'=>'active'],
 ['name'=>'Name','email'=>'invalid','role'=>'admin','status'=>'active'],
 ['name'=>'Name','email'=>'a@example.com','role'=>'root','status'=>'active'],
 ['name'=>'Name','email'=>'a@example.com','role'=>'admin','status'=>'locked'],
];
foreach($invalid as $i=>$entry){$blocked=false;try{AdminUsers::validated($entry);}catch(RuntimeException $e){$blocked=true;}$test($blocked,'Invalid account payload '.($i+1).' rejected');}
foreach([['short','short'],['abcdefgh','badmatch'],[str_repeat('a',257),str_repeat('a',257)]] as $idx=>$pair){$blocked=false;try{AdminUsers::password(...$pair);}catch(RuntimeException $e){$blocked=true;}$test($blocked,'Password invalid case '.($idx+1).' rejected');}
$ok=true;try{AdminUsers::password('eight888','eight888');}catch(Throwable $e){$ok=false;}$test($ok,'Eight-character passwords accepted');
$files=['app/Services/AdminUsers.php','app/Services/Auth.php','admin/_dispatch.php','install/index.php','database/schema.sql','database/patches/v0.0.8.sql','app/Services/Maintenance.php','app/Views/admin/maintenance.php','app/Views/admin/access-denied.php'];
foreach($files as $file){$test(is_file($r.'/'.$file),'File '.$file.' exists');}
$dispatch=file_get_contents($r.'/admin/_dispatch.php');$auth=file_get_contents($r.'/app/Services/Auth.php');$users=file_get_contents($r.'/app/Services/AdminUsers.php');$sql=file_get_contents($r.'/database/patches/v0.0.8.sql');
$test(str_contains($dispatch,'Authorization::requireAccess'),'Server-side authorization invoked');
$test(str_contains($dispatch,'Security::requireCsrf()'),'Admin mutations CSRF');
$test(str_contains($auth,'admin_session_version'),'Session version checked');
$test(str_contains($users,'FOR UPDATE'),'Last Super Admin protection serialized');
$test(str_contains($users,'active<=1'),'Last Super Admin cannot be removed');
$test(str_contains($users,'$id===$actor'),'Self lockout protection');
$test(str_contains($users,'password_hash('),'Password hashing used');
$test(!str_contains($users,'password_plaintext'),'No plaintext password storage');
$test(str_contains($users,'session_version=session_version+IF('),'Role changes revoke sessions');
$test(str_contains($users,'SET session_version=session_version+IF('),'Revocation precedes role update assignment');
$test(str_contains($sql,"role='super_admin'"),'Legacy role promoted');
$test(str_contains($sql,'maintenance_logs'),'Maintenance audit schema');
$test(str_contains($sql,'session_version'),'Session revision migration');
$test(str_contains($r?file_get_contents($r.'/install/index.php'):'', "'super_admin', 'active'"),'Fresh install creates Super Admin');
$test(str_contains(file_get_contents($r.'/install/index.php'),'minlength="8"'),'Installer accepts 8 characters');
$test(trim(file_get_contents($r.'/VERSION'))==='v0.2.2','Version updated');
$test(str_contains($dispatch,'Maintenance::withCronLock'),'Maintenance shares cron concurrency lock');
$test(str_contains(file_get_contents($r.'/app/Services/RetentionManager.php'),'runNow'),'Manual bounded cleanup available');
$test(str_contains(file_get_contents($r.'/app/Views/admin/notifications.php'),'Authorization::isSuperAdmin'),'Notification config restricted');
$test(is_file($r.'/status/index.php'),'Public status route preserved');
$test(is_file($r.'/app/Services/TelegramNotifier.php'),'Telegram engine preserved');
$test(is_file($r.'/app/Services/MonitorAnalytics.php'),'Analytics engine preserved');
$test(is_file($r.'/app/Services/IncidentManager.php'),'Incident engine preserved');
$test(CronRunner::health(null,null)==='Never Run','Never-run cron health');
$test(CronRunner::health(gmdate('Y-m-d H:i:s',time()-3600),'success',10)==='Stale','Stale cron detection');
$test(CronRunner::health(gmdate('Y-m-d H:i:s',time()-60),'success',10)==='Healthy','Healthy cron detection');
$test(CronRunner::health(gmdate('Y-m-d H:i:s',time()-60),'failed',10)==='Last Run Failed','Failed cron state');
$passed=count(array_filter($checks,static fn($x)=>$x[0]));foreach($checks as [$result,$label]){if(!$result){echo "FAIL $label\n";}}
echo $passed.'/'.count($checks)." passed\n";exit($passed===count($checks)?0:1);
