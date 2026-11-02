<?php
declare(strict_types=1);
use Monitor\Services\PortalUsers;
use Monitor\Services\Authorization;
use Monitor\Models\Settings;
use Monitor\Core\Security;
use Monitor\Core\Database;

if (!isset($page,$user) || !in_array($page,['portal-users','portal-users/add','portal-users/edit','portal-users/view'],true)) {http_response_code(404);exit;}
Authorization::requireAccess($user,'portal-users',$_SERVER['REQUEST_METHOD']??'GET');
$portalTarget=null;$portalMonitors=[];
if(in_array($page,['portal-users/edit','portal-users/view'],true)) {
    $rawId=(string)($_GET['id']??'');
    if(!preg_match('/^[1-9][0-9]{0,17}$/D',$rawId)) {monitor_error_page(404,true);}
    $portalTarget=PortalUsers::find((int)$rawId);
    if(!$portalTarget) {monitor_error_page(404,true);}
    if($page==='portal-users/view') {$portalMonitors=PortalUsers::owned((int)$rawId);}
}
$error=null;
if(Security::post()) {
    Security::requireCsrf();
    try {
        if($page==='portal-users/add') {
            $newId=PortalUsers::create($_POST);
            flash('success','Portal user created.');to(admin_url('portal-users/view').'?id='.$newId);
        }
        if($page==='portal-users/edit') {
            PortalUsers::update((int)$portalTarget['id'],$_POST);
            flash('success','Portal user updated.');to(admin_url('portal-users/view').'?id='.$portalTarget['id']);
        }
        if($page==='portal-users'&&($_POST['action']??'')==='mail') {
            $email=trim((string)($_POST['mail_from_email']??''));
            $name=trim((string)($_POST['mail_from_name']??''));
            if($email!==''&&(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||strpbrk($email,"\r\n")!==false)) {throw new RuntimeException('Enter a valid sender email.');}
            if(strlen($name)>100||strpbrk($name,"\r\n")!==false) {throw new RuntimeException('Sender name must be one line, up to 100 characters.');}
            Settings::putMany(['mail_from_email'=>$email,'mail_from_name'=>$name]);
            flash('success','Email sender settings saved.');to(admin_url('portal-users'));
        }
        http_response_code(405);exit;
    } catch(RuntimeException|InvalidArgumentException $e) {$error=$e->getMessage();}
    catch(Throwable $e) {error_log('Portal admin operation: '.get_class($e));$error='This operation could not be completed.';}
}
$portalRows=$page==='portal-users'?PortalUsers::all():[];
$settings=Settings::all();$flash=pull_flash();
$pageTitle=match($page){'portal-users'=>'Portal Users','portal-users/add'=>'Add Portal User','portal-users/edit'=>'Edit Portal User',default=>'Portal User Details'};
$pageSubtitle='Manage email-only accounts, monitor ownership, and quotas.';
require monitor_root().'/app/Views/partials/admin-top.php';
require monitor_root().'/app/Views/admin/portal-users.php';
require monitor_root().'/app/Views/partials/admin-bottom.php';
