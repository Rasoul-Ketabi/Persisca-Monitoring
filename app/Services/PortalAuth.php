<?php
declare(strict_types=1);
namespace Monitor\Services;
use Monitor\Core\Database;
use PDO;
use Throwable;

final class PortalAuth {
    private static ?array $cached=null;
    public static function clear(): void {self::$cached=null;}
    public static function current(): ?array {
        if (self::$cached!==null) {return self::$cached;}
        $id=(int)($_SESSION['portal_user_id']??0);
        if ($id<1) {return null;}
        $now=time();
        if ($now-(int)($_SESSION['portal_last_activity']??0)>3600) {self::logout();return null;}
        $user=PortalUsers::find($id);
        if (!$user||$user['status']!=='active'||(int)$user['session_version']!==(int)($_SESSION['portal_session_version']??0)) {self::logout();return null;}
        $_SESSION['portal_last_activity']=$now;
        if ($now-(int)($_SESSION['portal_last_rotation']??$now)>=900) {session_regenerate_id(true);$_SESSION['portal_last_rotation']=$now;}
        return self::$cached=$user;
    }
    public static function logout(): void {
        $_SESSION=[];self::$cached=null;
        if(session_status()===PHP_SESSION_ACTIVE) {
            if(ini_get('session.use_cookies')) {$params=session_get_cookie_params();setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>$params['path'],'secure'=>$params['secure'],'httponly'=>true,'samesite'=>'Lax']);}
            session_destroy();
        }
    }
    /** Persist requests for unknown addresses as well, avoiding enumeration and IP throttle bypass. */
    public static function request(string $email): void {
        $email=strtolower(trim($email));
        $_SESSION['portal_login_email']=strlen($email)<=190 && filter_var($email,FILTER_VALIDATE_EMAIL)?$email:'';
        if (!$_SESSION['portal_login_email']) {return;}
        $ip=(string)($_SERVER['REMOTE_ADDR']??'');
        $emailHash=hash('sha256',$email);$ipHash=hash('sha256',$ip);
        $pdo=Database::connection();
        // 60 second cooldown and 5/15 minute account and IP windows.
        $q=$pdo->prepare("SELECT (SELECT COUNT(*) FROM portal_otp_requests WHERE email_hash=? AND requested_at>UTC_TIMESTAMP()-INTERVAL 15 MINUTE) AS email_count, (SELECT COUNT(*) FROM portal_otp_requests WHERE ip_hash=? AND requested_at>UTC_TIMESTAMP()-INTERVAL 15 MINUTE) AS ip_count, (SELECT MAX(requested_at) FROM portal_otp_requests WHERE email_hash=?) AS last_email");
        $q->execute([$emailHash,$ipHash,$emailHash]);$stats=$q->fetch(PDO::FETCH_ASSOC);
        if ((int)$stats['email_count']>=5||(int)$stats['ip_count']>=20 || ($stats['last_email'] && strtotime($stats['last_email'].' UTC')>time()-60)) {return;}
        $pdo->prepare('INSERT INTO portal_otp_requests(email_hash,ip_hash,requested_at) VALUES(?,?,UTC_TIMESTAMP())')->execute([$emailHash,$ipHash]);
        $q=$pdo->prepare("SELECT id FROM portal_users WHERE email=? AND status='active' LIMIT 1");$q->execute([$email]);$id=(int)$q->fetchColumn();
        if(!$id) {return;}
        $code=sprintf('%06d',random_int(0,999999));$hash=password_hash($code,PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE portal_otp_codes SET consumed_at=UTC_TIMESTAMP() WHERE portal_user_id=? AND consumed_at IS NULL")->execute([$id]);
        $pdo->prepare('INSERT INTO portal_otp_codes(portal_user_id,code_hash,expires_at,requested_ip_hash,created_at) VALUES(?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 10 MINUTE),?,UTC_TIMESTAMP())')->execute([$id,$hash,$ipHash]);
        $otpId=(int)$pdo->lastInsertId();
        try {
            if (!Mailer::otp($email,$code)) { $pdo->prepare('UPDATE portal_otp_codes SET consumed_at=UTC_TIMESTAMP() WHERE id=?')->execute([$otpId]);error_log('Portal OTP transport failed.'); }
        } catch(Throwable $e) {$pdo->prepare('UPDATE portal_otp_codes SET consumed_at=UTC_TIMESTAMP() WHERE id=?')->execute([$otpId]);error_log('Portal OTP transport exception: '.get_class($e));}
    }
    public static function verify(string $code): bool {
        $email=(string)($_SESSION['portal_login_email']??'');
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)||!preg_match('/^[0-9]{6}$/D',$code)) {return false;}
        $pdo=Database::connection();$pdo->beginTransaction();
        try {
            $q=$pdo->prepare("SELECT o.id,o.code_hash,o.attempt_count,o.expires_at,u.id AS user_id,u.session_version FROM portal_otp_codes o JOIN portal_users u ON u.id=o.portal_user_id WHERE u.email=? AND u.status='active' AND o.consumed_at IS NULL ORDER BY o.id DESC LIMIT 1 FOR UPDATE");
            $q->execute([$email]);$row=$q->fetch(PDO::FETCH_ASSOC);
            if(!$row || (int)$row['attempt_count']>=5||strtotime($row['expires_at'].' UTC')<=time()) {$pdo->commit();return false;}
            $good=password_verify($code,$row['code_hash']);
            $pdo->prepare('UPDATE portal_otp_codes SET attempt_count=attempt_count+1,consumed_at=IF(?=1 OR attempt_count>=4,UTC_TIMESTAMP(),consumed_at) WHERE id=?')->execute([$good?1:0,$row['id']]);
            if($good) {$pdo->prepare('UPDATE portal_users SET last_login_at=UTC_TIMESTAMP() WHERE id=?')->execute([$row['user_id']]);}
            $pdo->commit();
            if(!$good) {return false;}
            session_regenerate_id(true);unset($_SESSION['csrf'],$_SESSION['portal_login_email']);
            $_SESSION['portal_user_id']=(int)$row['user_id'];$_SESSION['portal_session_version']=(int)$row['session_version'];
            $_SESSION['portal_last_activity']=time();$_SESSION['portal_last_rotation']=time();self::$cached=null;
            return true;
        } catch(Throwable $e) {if($pdo->inTransaction()) {$pdo->rollBack();}throw $e;}
    }
}
