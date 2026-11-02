<?php
declare(strict_types=1);
namespace Monitor\Services;
use Monitor\Core\Database;
use Monitor\Models\Settings;
use Monitor\Models\SystemMeta;
use PDO;
use Throwable;

final class Maintenance {
    public static function audit(int $actor,string $action,string $summary): void {
        try { Database::connection()->prepare('INSERT INTO maintenance_logs(user_id,action,status,summary,created_at) VALUES (?,?,?,?,UTC_TIMESTAMP())')->execute([$actor,substr($action,0,60),'success',substr($summary,0,240)]); }
        catch (Throwable $e) { error_log('Maintenance audit unavailable: '.get_class($e)); }
    }
    /** Coordinate web maintenance with the CLI cron lock to avoid double aggregation. */
    public static function withCronLock(callable $job): mixed {
        $lock=@fopen(monitor_root().'/storage/cron-monitor.lock','c');
        if (!$lock) {throw new \RuntimeException('The cron lock cannot be opened. Check storage permissions.');}
        try {
            if (!flock($lock,LOCK_EX|LOCK_NB)) {throw new \RuntimeException('Cron is already running. Run this action again after it completes.');}
            return $job();
        } finally {flock($lock,LOCK_UN);fclose($lock);}
    }
    public static function overview(): array {
        $pdo=Database::connection();$metrics=[];
        $queries=[
          'Active Super Admins'=>"SELECT COUNT(*) FROM users WHERE role='super_admin' AND status='active'",
          'Active Admins'=>"SELECT COUNT(*) FROM users WHERE role='admin' AND status='active'",
          'Overdue active monitors'=>"SELECT COUNT(*) FROM monitors WHERE is_active=1 AND next_check_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 MINUTE)",
          'Missing monitor schedules'=>"SELECT COUNT(*) FROM monitors WHERE is_active=1 AND next_check_at IS NULL",
          'Multiple open incidents'=>"SELECT COUNT(*) FROM (SELECT monitor_id FROM incidents WHERE status='open' GROUP BY monitor_id HAVING COUNT(*)>1) t",
          'Down state without incident'=>"SELECT COUNT(*) FROM monitors m LEFT JOIN incidents i ON i.id=m.current_incident_id AND i.status='open' WHERE m.operational_status='down' AND i.id IS NULL",
          'Broken incident pointer'=>"SELECT COUNT(*) FROM monitors m LEFT JOIN incidents i ON i.id=m.current_incident_id AND i.status='open' AND i.monitor_id=m.id WHERE m.current_incident_id IS NOT NULL AND i.id IS NULL",
          'Invalid runtime states'=>"SELECT COUNT(*) FROM monitors WHERE operational_status NOT IN ('up','down','unknown')",
          'Pending notifications'=>"SELECT COUNT(*) FROM notification_logs WHERE status='pending'",
          'Failed notifications'=>"SELECT COUNT(*) FROM notification_logs WHERE status='failed'",
          'Checks missing monitors'=>"SELECT COUNT(*) FROM monitor_checks c LEFT JOIN monitors m ON m.id=c.monitor_id WHERE m.id IS NULL",
          'Incidents missing monitors'=>"SELECT COUNT(*) FROM incidents i LEFT JOIN monitors m ON m.id=i.monitor_id WHERE m.id IS NULL",
          'Notifications missing incidents'=>"SELECT COUNT(*) FROM notification_logs l LEFT JOIN incidents i ON i.id=l.incident_id WHERE i.id IS NULL",
        ];
        foreach ($queries as $label=>$query) { try {$metrics[$label]=(int)$pdo->query($query)->fetchColumn();}catch(Throwable $e){$metrics[$label]=null;} }
        $meta=SystemMeta::getMany(['cron_last_started_at','cron_last_completed_at','cron_last_status','last_retention_cleanup_at','installed_version','hourly_last_aggregated_at']);
        $version=trim((string)file_get_contents(monitor_root().'/VERSION'));
        $paths=['storage'=>'storage','logs'=>'storage/logs','cache'=>'storage/cache','uploads'=>'public/assets/uploads'];
        $storage=[];foreach ($paths as $label=>$path){$storage[$label]=is_writable(monitor_root().'/'.$path);}
        $tables=['users','monitors','monitor_checks','incidents','notification_logs','monitor_hourly_stats','maintenance_logs'];
        $missing=[];foreach($tables as $table){try{$pdo->query('SELECT 1 FROM '.$table.' LIMIT 1');}catch(Throwable $e){$missing[]=$table;}}
        return ['counts'=>$metrics,'meta'=>$meta,'code_version'=>$version,'version_match'=>($meta['installed_version']??'')===$version,'missing_tables'=>$missing,'storage'=>$storage,'cron_health'=>CronRunner::health($meta['cron_last_completed_at']??null,$meta['cron_last_status']??null,(int)Settings::get('cron_stale_after_minutes','10'))];
    }
    public static function repairSchedules(): int {
        $pdo=Database::connection();
        $q=$pdo->prepare("UPDATE monitors SET next_check_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE is_active=1 AND next_check_at IS NULL LIMIT 100");
        $q->execute();return $q->rowCount();
    }
    public static function recentLogs(): array {
        return Database::connection()->query('SELECT l.action,l.status,l.summary,l.created_at,u.name AS actor FROM maintenance_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 30')->fetchAll(PDO::FETCH_ASSOC);
    }
}
