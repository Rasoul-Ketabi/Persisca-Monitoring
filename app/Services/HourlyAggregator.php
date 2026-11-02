<?php
declare(strict_types=1);
namespace Monitor\Services;
use Monitor\Core\Database;
use Monitor\Models\SystemMeta;
use PDO;

final class HourlyAggregator {
    public static function process(int $limit = 1500): int {
        $pdo=Database::connection(); $pdo->beginTransaction();
        try {
            $cursor = (int)(SystemMeta::getMany(['hourly_aggregated_check_id'])['hourly_aggregated_check_id'] ?? 0);
            $query=$pdo->prepare("SELECT id,monitor_id,success,response_time_ms,checked_at FROM monitor_checks WHERE id>? AND check_type='scheduled' ORDER BY id ASC LIMIT ".max(1,min(5000,$limit)));
            $query->execute([$cursor]); $rows=$query->fetchAll(PDO::FETCH_ASSOC);
            $sql='INSERT INTO monitor_hourly_stats (monitor_id,hour_start,scheduled_check_count,successful_check_count,failed_check_count,response_sample_count,sum_response_time_ms,min_response_time_ms,max_response_time_ms,first_check_at,last_check_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE scheduled_check_count=scheduled_check_count+VALUES(scheduled_check_count),successful_check_count=successful_check_count+VALUES(successful_check_count),failed_check_count=failed_check_count+VALUES(failed_check_count),response_sample_count=response_sample_count+VALUES(response_sample_count),sum_response_time_ms=sum_response_time_ms+VALUES(sum_response_time_ms),min_response_time_ms=CASE WHEN VALUES(min_response_time_ms) IS NULL THEN min_response_time_ms WHEN min_response_time_ms IS NULL THEN VALUES(min_response_time_ms) ELSE LEAST(min_response_time_ms,VALUES(min_response_time_ms)) END,max_response_time_ms=CASE WHEN VALUES(max_response_time_ms) IS NULL THEN max_response_time_ms WHEN max_response_time_ms IS NULL THEN VALUES(max_response_time_ms) ELSE GREATEST(max_response_time_ms,VALUES(max_response_time_ms)) END,first_check_at=LEAST(first_check_at,VALUES(first_check_at)),last_check_at=GREATEST(last_check_at,VALUES(last_check_at)),updated_at=VALUES(updated_at)';
            $upsert=$pdo->prepare($sql);
            $groups=[];
            foreach ($rows as $row) {
                $hour=substr($row['checked_at'],0,13).':00:00';
                $key=$row['monitor_id'].'|'.$hour;
                if (!isset($groups[$key])) { $groups[$key]=['id'=>(int)$row['monitor_id'],'hour'=>$hour,'count'=>0,'passed'=>0,'failed'=>0,'resp_count'=>0,'resp_sum'=>0,'min'=>null,'max'=>null,'first'=>$row['checked_at'],'last'=>$row['checked_at']]; }
                $g=&$groups[$key]; $g['count']++; $g[(int)$row['success'] ? 'passed':'failed']++;
                $g['first']=min($g['first'],$row['checked_at']); $g['last']=max($g['last'],$row['checked_at']);
                if ($row['response_time_ms'] !== null) { $ms=(int)$row['response_time_ms']; $g['resp_count']++;$g['resp_sum']+=$ms;$g['min']=$g['min']===null?$ms:min($g['min'],$ms);$g['max']=$g['max']===null?$ms:max($g['max'],$ms); }
                unset($g);
            }
            foreach ($groups as $g) { $upsert->execute([$g['id'],$g['hour'],$g['count'],$g['passed'],$g['failed'],$g['resp_count'],$g['resp_sum'],$g['min'],$g['max'],$g['first'],$g['last']]); }
            if ($rows) { SystemMeta::putMany(['hourly_aggregated_check_id'=>(string)end($rows)['id'],'hourly_last_aggregated_at'=>gmdate('Y-m-d H:i:s')]); }
            $pdo->commit(); return count($rows);
        } catch (\Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
    }
}
