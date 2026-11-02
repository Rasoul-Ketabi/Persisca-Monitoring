<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Monitor\Core\Security;
use Monitor\Models\Settings;
use Monitor\Services\PortalAuth;
use Monitor\Services\PortalMonitors;
use Monitor\Services\MonitorInput;
use Monitor\Services\MonitorAnalytics;
use Monitor\Services\TelegramLinkManager;

if (!monitor_installed()) {to(url('install/'));}
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
$allowed=['dashboard','login','verify','logout','monitors','monitors/add','monitors/edit','monitors/view','incidents','analytics','profile','status-page','telegram'];
if (!isset($portalPage)||!in_array($portalPage,$allowed,true)) {monitor_error_page(404);}
$error=null;
if ($portalPage==='login'||$portalPage==='verify') {
    if(PortalAuth::current()) {to(portal_url());}
    if(Security::post()) {
        Security::requireCsrf();
        try {
            if($portalPage==='login') {PortalAuth::request((string)($_POST['email']??''));flash('success','If this account is available, a login code has been sent.');to(portal_url('verify'));}
            if(PortalAuth::verify((string)($_POST['code']??''))) {to(portal_url());}
            $error='Code not accepted. Check the code and try again, or request a new one.';
        } catch(Throwable $e) {error_log('Portal login failed: '.get_class($e));$error='Sign in is temporarily unavailable.';}
    }
    $settings=Settings::all();$flash=pull_flash();$pageTitle='Portal Sign In';
    require monitor_root().'/app/Views/portal/top.php';
    require monitor_root().'/app/Views/portal/login.php';
    require monitor_root().'/app/Views/portal/bottom.php';exit;
}
if($portalPage==='logout') {
    if(!Security::post()) {http_response_code(405);header('Allow: POST');exit;}
    Security::requireCsrf();PortalAuth::logout();to(portal_url('login'));
}
$portalUser=PortalAuth::current();
if(!$portalUser) {to(portal_url('login'));}
$uid=(int)$portalUser['id'];
$monitorId=null;$monitor=null;$telegramConnectUrl=null;
if(in_array($portalPage,['monitors/edit','monitors/view','analytics'],true)) {
    $raw=(string)($_GET['id']??'');
    if(!preg_match('/^[1-9][0-9]{0,17}$/D',$raw)) {monitor_error_page(404);}
    $monitorId=(int)$raw;
    $monitor=PortalMonitors::find($uid,$monitorId);
    if(!$monitor) {monitor_error_page(404);}
}
if(Security::post()) {
    Security::requireCsrf();
    try {
        $action=(string)($_POST['action']??'save');
        if($portalPage==='telegram') {
            if($action==='connect') {
                $telegramConnectUrl=TelegramLinkManager::connect($uid);
            } elseif($action==='disconnect') {
                if(($_POST['confirm_disconnect']??'')!=='yes') {throw new RuntimeException('Confirm disconnecting Telegram.');}
                TelegramLinkManager::disconnect($uid);
                flash('success','Telegram disconnected.');to(portal_url('telegram'));
            } elseif($action==='alerts') {
                TelegramLinkManager::setAlerts($uid,isset($_POST['telegram_notifications_enabled']));
                flash('success','Telegram alert preference updated.');to(portal_url('telegram'));
            } else {http_response_code(405);exit;}
        }
        if($portalPage==='status-page') {
            if($action==='publication') {
                \Monitor\Services\PortalUsers::setPublication($uid,isset($_POST['public_status_enabled']));
                flash('success','Public status page settings updated.');to(portal_url('status-page'));
            }
            if($action==='visibility') {
                $idString=(string)($_POST['id']??'');
                if(!preg_match('/^[1-9][0-9]{0,17}$/D',$idString)) {monitor_error_page(404);}
                $id=(int)$idString;
                if (!PortalMonitors::find($uid,$id)) {monitor_error_page(404);}
                PortalMonitors::setVisibility($uid,$id,isset($_POST['is_public']));
                flash('success','Monitor visibility updated.');to(portal_url('status-page'));
            }
            http_response_code(405);exit;
        }
        if($portalPage==='monitors/add'&&$action==='save') {
            $data=MonitorInput::fromPost($_POST);$data['allow_private_network']=0;
            // Portal public flag is reserved for personal status pages; no global public exposure.
            $id=PortalMonitors::add($uid,$data);
            flash('success','Monitor added.');to(portal_url('monitors/view').'?id='.$id);
        }
        if($portalPage==='monitors/edit'&&$action==='save') {
            $data=MonitorInput::fromPost($_POST);$data['allow_private_network']=0;
            PortalMonitors::update($uid,$monitorId,$data);
            flash('success','Monitor updated.');to(portal_url('monitors/view').'?id='.$monitorId);
        }
        if(in_array($portalPage,['monitors','monitors/view','monitors/edit'],true)&&in_array($action,['toggle','delete','test'],true)) {
            $raw=$monitorId===null?(string)($_POST['id']??''):(string)$monitorId;
            if(!preg_match('/^[1-9][0-9]{0,17}$/D',$raw)) {monitor_error_page(404);}
            $m=PortalMonitors::find($uid,(int)$raw);
            if(!$m) {monitor_error_page(404);}
            if($action==='delete') {if(($_POST['confirm_delete']??'')!=='yes'){throw new RuntimeException('Confirm deletion first.');}PortalMonitors::delete($uid,(int)$raw);flash('success','Monitor and its history deleted.');to(portal_url('monitors'));}
            if($action==='toggle') {PortalMonitors::toggle($uid,(int)$raw,!(bool)$m['is_active']);flash('success','Monitoring state updated.');to(portal_url('monitors'));}
            $result=PortalMonitors::test($uid,(int)$raw);
            flash($result['success']?'success':'error',$result['success']?'Manual test succeeded.':'Manual test failed. Check the diagnostics.');
            to(portal_url('monitors/view').'?id='.$raw);
        }
        if($portalPage!=='telegram') {http_response_code(405);exit;}
    } catch(InvalidArgumentException|RuntimeException $e) {$error=$e->getMessage();}
    catch(Throwable $e) {error_log('Portal monitor operation: '.get_class($e));$error='This action could not be completed.';}
}
$portalMonitors=[];$incidentRows=[];$recentChecks=[];$history=[];$analytics=[];$analyticsSeries=[];
if(in_array($portalPage,['dashboard','monitors','status-page'],true)) {$portalMonitors=PortalMonitors::all($uid);}
if($portalPage==='dashboard') {$summary=PortalMonitors::dashboard($uid);$incidentRows=PortalMonitors::incidents($uid,null,5);$recentChecks=PortalMonitors::recentChecks($uid);}
if($portalPage==='incidents') {$incidentRows=PortalMonitors::incidents($uid);}
if($portalPage==='monitors/view') {$history=PortalMonitors::checks($uid,$monitorId,30);$incidentRows=PortalMonitors::incidents($uid,$monitorId,10);}
if($portalPage==='analytics') {
    $range=MonitorAnalytics::period((string)($_GET['range']??'24h'));
    $analytics=MonitorAnalytics::summary($monitorId,$range);
    $analyticsSeries=MonitorAnalytics::responseSeries($monitorId,$range);
}
if($portalPage==='monitors/add') {$monitor=['name'=>'','url'=>'','is_active'=>1,'is_public'=>0,'check_interval_minutes'=>5,'timeout_seconds'=>10,'expected_status_codes'=>'200','retry_count'=>2,'retry_delay_seconds'=>2,'failure_threshold'=>2,'telegram_notifications_enabled'=>0];}
$settings=Settings::all();$flash=pull_flash();
$pageTitle=match($portalPage){'dashboard'=>'Dashboard','monitors'=>'My Monitors','monitors/add'=>'Add Monitor','monitors/edit'=>'Edit Monitor','monitors/view'=>'Monitor Details','incidents'=>'Incidents','analytics'=>'Analytics','profile'=>'My Account','status-page'=>'Public Status Page','telegram'=>'Telegram',default=>'Account'};
require monitor_root().'/app/Views/portal/top.php';
require monitor_root().'/app/Views/portal/page.php';
require monitor_root().'/app/Views/portal/bottom.php';
