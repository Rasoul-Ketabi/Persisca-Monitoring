<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {http_response_code(404);exit;}
require dirname(__DIR__).'/bootstrap.php';
use Monitor\Services\Mailer;
use Monitor\Services\Authorization;
$root=dirname(__DIR__);$count=0;$failed=0;
$test=static function(bool $ok,string $label)use(&$count,&$failed):void{$count++;if(!$ok){$failed++;}echo ($ok?'PASS ':'FAIL ').$label.PHP_EOL;};
$read=static fn(string $file):string=>(string)file_get_contents($root.'/'.$file);
$valid=['mail_transport'=>'smtp','smtp_host'=>'smtp.example.com','smtp_port'=>'587','smtp_encryption'=>'tls','smtp_auth_enabled'=>'1','smtp_username'=>'user@example.com','smtp_password'=>'newpass','mail_from_email'=>'status@example.com','mail_from_name'=>'Example Status','mail_reply_to'=>'reply@example.com','smtp_timeout'=>'15'];
$save=Mailer::validateSettings($valid,['smtp_password'=>'older']);
$test($save['mail_transport']==='smtp','SMTP settings accepted');
$test($save['smtp_host']==='smtp.example.com','Host retained');
$test($save['smtp_password']==='newpass','New SMTP password stored');
$test($save['smtp_timeout']==='15','Timeout retained');
$test($save['mail_reply_to']==='reply@example.com','Reply-To retained');
foreach(['tls','ssl','none'] as $mode){$v=$valid;$v['smtp_encryption']=$mode;unset($v['smtp_auth_enabled']);$result=Mailer::validateSettings($v,[]);$test($result['smtp_encryption']===$mode,'Encryption '.$mode.' available');}
foreach([25,465,587,2525,65535] as $port){$v=$valid;$v['smtp_port']=(string)$port;$test(Mailer::validateSettings($v,[])['smtp_port']===(string)$port,'Port '.$port.' accepted');}
foreach(['0','65536','abc','-1'] as $port){$v=$valid;$v['smtp_port']=$port;try{Mailer::validateSettings($v,[]);$test(false,'Port '.$port.' rejected');}catch(RuntimeException $e){$test(true,'Port '.$port.' rejected');}}
foreach(['2','61','abc'] as $timeout){$v=$valid;$v['smtp_timeout']=$timeout;try{Mailer::validateSettings($v,[]);$test(false,'Timeout '.$timeout.' rejected');}catch(RuntimeException $e){$test(true,'Timeout '.$timeout.' rejected');}}
foreach(["mail.example.com\r\nBcc: other@example.com",'smtp://example.com','example.com/private'] as $host){$v=$valid;$v['smtp_host']=$host;try{Mailer::validateSettings($v,[]);$test(false,'Unsafe hostname blocked');}catch(RuntimeException $e){$test(true,'Unsafe hostname blocked');}}
$v=$valid;$v['smtp_password']='';$test(!isset(Mailer::validateSettings($v,['smtp_password'=>'keep'])['smtp_password']),'Blank password keeps original via no change');
$v=$valid;$v['smtp_password']='';$v['smtp_password_clear']='1';$v['confirm_password_clear']='yes';unset($v['smtp_auth_enabled']);$test(Mailer::validateSettings($v,['smtp_password'=>'keep'])['smtp_password']==='','Explicit clear allowed when SMTP auth disabled');
$v=$valid;$v['smtp_password']='';$v['smtp_password_clear']='1';$v['confirm_password_clear']='yes';try{Mailer::validateSettings($v,['smtp_password'=>'keep']);$test(false,'Clear blocked when auth active');}catch(RuntimeException $e){$test(true,'Clear blocked when auth active');}
$v=$valid;$v['smtp_encryption']='none';try{Mailer::validateSettings($v,[]);$test(false,'Cleartext auth blocked');}catch(RuntimeException $e){$test(true,'Cleartext auth blocked');}
foreach(['mail_from_email'=>'bad@example.com\nBcc: a@example.com','mail_from_name'=>"Company\r\nX-Test: injected",'mail_reply_to'=>"foo@example.com\nBcc: a@example.com"] as $field=>$value){$v=$valid;$v[$field]=$value;try{Mailer::validateSettings($v,[]);$test(false,'Injected '.$field.' blocked');}catch(RuntimeException $e){$test(true,'Injected '.$field.' blocked');}}
$test(Mailer::validAddress('person@example.com'),'Valid address accepted');
$test(!Mailer::validAddress("person@example.com\r\nX: bad"),'Header injection blocked');
$test(!Mailer::validHost('localhost;rm'),'Unsafe host rejected');
$test(!Authorization::allowed(['role'=>'admin'],'settings/mail'),'Standard Admin cannot GET SMTP settings');
$test(!Authorization::allowed(['role'=>'admin'],'settings/mail','POST'),'Standard Admin cannot POST SMTP settings');
$test(Authorization::allowed(['role'=>'super_admin'],'settings/mail'),'Super Admin can view SMTP settings');
$test(str_contains($read('admin/_dispatch.php'),"'settings/mail'") && str_contains($read('app/Helpers/functions.php'),"'settings/mail' => 'admin/settings/mail/'"),'Folder route registered');
$test(str_contains($read('app/Views/admin/mail.php'),"Security::csrfField()"),'SMTP settings and test forms have CSRF');
$test(str_contains($read('app/Views/admin/mail.php'),'name="smtp_password" type="password"') && !str_contains($read('app/Views/admin/mail.php'),"value=\"<?= e((string)(\$settings['smtp_password']"),'Saved password never rendered as input value');
$test(str_contains($read('app/Services/SmtpClient.php'),"'verify_peer' => true") && str_contains($read('app/Services/SmtpClient.php'),"'verify_peer_name' => true"),'TLS certificate and host verification enabled');
$test(str_contains($read('app/Services/SmtpClient.php'),'stream_socket_client') && !str_contains($read('app/Services/SmtpClient.php'),'shell_exec'),'Native socket SMTP');
$test(str_contains($read('app/Services/SmtpClient.php'),'STREAM_CRYPTO_METHOD_TLS_CLIENT'),'STARTTLS present');
$test(str_contains($read('app/Services/SmtpClient.php'),'AUTH LOGIN') && str_contains($read('app/Services/SmtpClient.php'),'AUTH PLAIN'),'Both AUTH methods implemented');
$test(str_contains($read('app/Services/SmtpClient.php'),'(?m)^\\.'),'SMTP dot-stuffing present');
$test(str_contains($read('app/Services/Mailer.php'),'Content-Transfer-Encoding: base64'),'UTF-8 safe message bodies');
$test(str_contains($read('app/Services/Mailer.php'),'Message-ID: '),'Message-ID supplied');
$test(str_contains($read('app/Services/Mailer.php'),'public static function otp(') && str_contains($read('app/Services/Mailer.php'),'return self::send($recipient,$subject,$body)'),'OTP uses one common mail service');
$test(!str_contains($read('app/Services/PortalAuth.php'),'mail(') && str_contains($read('app/Services/PortalAuth.php'),'Mailer::otp'),'OTP handler has no direct mail');
$test(str_contains($read('app/Services/PortalAuth.php'),'UPDATE portal_otp_codes SET consumed_at=UTC_TIMESTAMP() WHERE id=?'),'Undelivered OTP consumed');
$test(str_contains($read('app/Views/admin/system.php'),'MAIL DELIVERY') && !str_contains($read('app/Views/admin/system.php'),"Settings::get('smtp_password') ?></"),'Health displays mail state but no SMTP password');
$test(str_contains($read('database/patches/v0.2.3.sql'),"ON DUPLICATE KEY UPDATE setting_key=setting_key"),'Migration preserves existing settings');
$test(str_contains($read('database/schema.sql'),'CREATE TABLE IF NOT EXISTS settings'),'Fresh schema retains settings');
$test(trim($read('VERSION'))==='v0.2.4' && str_contains($read('install/index.php'),"'installed_version', 'v0.2.4'"),'Version and fresh install metadata aligned');
echo ($count-$failed).'/'.$count.' passed'.PHP_EOL;
exit($failed?1:0);
