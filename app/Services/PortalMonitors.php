<?php
declare(strict_types=1);
namespace Monitor\Services;
use Monitor\Core\Database;
use Monitor\Models\MonitorRepository;
use PDO;
use RuntimeException;
use Throwable;

final class PortalMonitors {
    public static function all(int $uid): array {
        $q=Database::connection()->prepare('SELECT * FROM monitors WHERE portal_user_id=? ORDER BY created_at DESC,id DESC LIMIT 1000');$q->execute([$uid]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function find(int $uid,int $id): ?array {
        $q=Database::connection()->prepare('SELECT * FROM monitors WHERE id=? AND portal_user_id=? LIMIT 1');$q->execute([$id,$uid]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
    }
    public static function add(int $uid,array $data): int {
        $data['allow_private_network']=0;
        $pdo=Database::connection();$pdo->beginTransaction();
        try {
            $q=$pdo->prepare("SELECT monitor_limit FROM portal_users WHERE id=? AND status='active' FOR UPDATE");$q->execute([$uid]);$limit=$q->fetchColumn();
            if($limit===false) {throw new RuntimeException('Your account is unavailable.');}
            $q=$pdo->prepare('SELECT COUNT(*) FROM monitors WHERE portal_user_id=?');$q->execute([$uid]);
            if((int)$q->fetchColumn()>=(int)$limit) {throw new RuntimeException('Your monitor limit has been reached.');}
            $id=MonitorRepository::create($data,$uid);$pdo->commit();return $id;
        } catch(Throwable $e) {if($pdo->inTransaction()) {$pdo->rollBack();}throw $e;}
    }
    public static function update(int $uid,int $id,array $data): void {
        $data['allow_private_network']=0;
        $fields=['name','url','is_active','is_public','check_interval_minutes','timeout_seconds','expected_status_codes','retry_count','retry_delay_seconds','failure_threshold','telegram_notifications_enabled'];
        $sets=implode(',',array_map(static fn($f)=>$f.'=?',$fields));
        $q=Database::connection()->prepare("UPDATE monitors SET $sets,next_check_at=NULL,updated_at=UTC_TIMESTAMP() WHERE id=? AND portal_user_id=?");
        $q->execute([...array_map(static fn($f)=>$data[$f],$fields),$id,$uid]);
    }
    public static function setVisibility(int $uid,int $id,bool $public): void {
        $q=Database::connection()->prepare('UPDATE monitors SET is_public=?,updated_at=UTC_TIMESTAMP() WHERE id=? AND portal_user_id=?');
        $q->execute([$public ? 1 : 0,$id,$uid]);
        // No disclosure of whether an arbitrary ID belongs to somebody else.
        if (!self::find($uid,$id)) { throw new RuntimeException('Monitor not found.'); }
    }
    public static function toggle(int $uid,int $id,bool $active): void {
        Database::connection()->prepare('UPDATE monitors SET is_active=?,next_check_at=NULL,updated_at=UTC_TIMESTAMP() WHERE id=? AND portal_user_id=?')->execute([$active?1:0,$id,$uid]);
    }
    public static function delete(int $uid,int $id): void {
        Database::connection()->prepare('DELETE FROM monitors WHERE id=? AND portal_user_id=?')->execute([$id,$uid]);
    }
    public static function test(int $uid,int $id): array {
        $monitor=self::find($uid,$id);if(!$monitor) {throw new RuntimeException('Monitor not found.');}
        // Reuse the same protected HTTP check implementation as admin operations.
        $result=HttpMonitor::check($monitor);
        $q=Database::connection()->prepare('UPDATE monitors SET last_manual_test_at=?,last_manual_test_success=?,last_manual_test_http_status=?,last_manual_test_response_ms=?,last_manual_test_error_type=?,last_manual_test_error=?,last_manual_test_effective_url=?,last_manual_test_attempts=? WHERE id=? AND portal_user_id=?');
        $q->execute([$result['checked_at'],$result['success']?1:0,$result['http_status'],$result['response_time_ms'],$result['error_type']?:null,substr((string)$result['error_message'],0,2000)?:null,substr((string)$result['effective_url'],0,2048)?:null,$result['attempts'],$id,$uid]);
        return $result;
    }
    public static function checks(int $uid,int $id,int $limit=30): array {
        $limit=max(1,min(100,$limit));
        $q=Database::connection()->prepare("SELECT c.checked_at,c.check_type,c.success,c.http_status,c.response_time_ms,c.attempt_count,c.error_type FROM monitor_checks c JOIN monitors m ON m.id=c.monitor_id WHERE m.portal_user_id=? AND m.id=? ORDER BY c.checked_at DESC,c.id DESC LIMIT $limit");
        $q->execute([$uid,$id]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function incidents(int $uid,?int $id=null,int $limit=50): array {
        $limit=max(1,min(100,$limit));
        $sql="SELECT i.id,i.monitor_id,i.status,i.started_at,i.recovered_at,i.duration_seconds,m.name AS monitor_name FROM incidents i JOIN monitors m ON m.id=i.monitor_id WHERE m.portal_user_id=?";
        if($id!==null) {$sql.=' AND m.id=?';}
        $sql.=" ORDER BY i.started_at DESC,i.id DESC LIMIT $limit";
        $q=Database::connection()->prepare($sql);$q->execute($id===null?[$uid]:[$uid,$id]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function recentChecks(int $uid): array {
        $q=Database::connection()->prepare("SELECT c.checked_at,c.success,m.name AS monitor_name FROM monitor_checks c JOIN monitors m ON m.id=c.monitor_id WHERE m.portal_user_id=? AND c.check_type='scheduled' ORDER BY c.checked_at DESC,c.id DESC LIMIT 10");
        $q->execute([$uid]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function dashboard(int $uid): array {
        $q=Database::connection()->prepare("SELECT COUNT(*) AS monitors,COALESCE(SUM(is_active=1),0) AS active,COALESCE(SUM(operational_status='down' AND is_active=1),0) AS down FROM monitors WHERE portal_user_id=?");$q->execute([$uid]);return $q->fetch(PDO::FETCH_ASSOC);
    }
}
