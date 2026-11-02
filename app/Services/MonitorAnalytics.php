<?php
declare(strict_types=1);
namespace Monitor\Services;

use Monitor\Core\Database;
use Monitor\Models\SystemMeta;
use PDO;

/** Observed scheduled-check availability, not continuous SLA uptime. Timestamps are UTC. */
final class MonitorAnalytics {
    public const PERIODS = ['24h' => 86400, '7d' => 604800, '30d' => 2592000];

    public static function period(string $input): string {
        return isset(self::PERIODS[$input]) ? $input : '24h';
    }

    public static function percentage(int $successful, int $total): ?float {
        return $total > 0 ? round(100.0 * $successful / $total, 2) : null;
    }

    /** Period summary uses SQL aggregates and only recorded scheduled checks. */
    public static function summary(int $monitorId, string $period, ?\DateTimeImmutable $at = null): array {
        $period = self::period($period);
        $end = ($at ?: new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('UTC'));
        $from = $end->modify('-' . self::PERIODS[$period] . ' seconds');
        $pdo = Database::connection();
        $begin = $from->format('Y-m-d H:i:s');
        $until = $end->format('Y-m-d H:i:s');
        $useAggregates = self::backfillCaughtUp($pdo) && $period !== '24h';
        if ($useAggregates) {
            $firstWhole = $from->setTime((int)$from->format('H'), 0)->modify('+1 hour');
            $lastWhole = $end->setTime((int)$end->format('H'), 0);
            if ($firstWhole < $lastWhole) {
                $q = $pdo->prepare('SELECT COALESCE(SUM(scheduled_check_count),0) AS checks, COALESCE(SUM(successful_check_count),0) AS successes, COALESCE(SUM(failed_check_count),0) AS failures, COALESCE(SUM(response_sample_count),0) AS response_count, COALESCE(SUM(sum_response_time_ms),0) AS response_sum, MIN(min_response_time_ms) AS response_min, MAX(max_response_time_ms) AS response_max, MIN(first_check_at) AS first_at FROM monitor_hourly_stats WHERE monitor_id = ? AND hour_start >= ? AND hour_start < ?');
                $q->execute([$monitorId, $firstWhole->format('Y-m-d H:i:s'), $lastWhole->format('Y-m-d H:i:s')]);
                $total = self::normalize($q->fetch(PDO::FETCH_ASSOC) ?: []);
                $edges = [[$begin, $firstWhole->format('Y-m-d H:i:s')],[$lastWhole->format('Y-m-d H:i:s'), $until]];
                foreach ($edges as [$start,$stop]) {
                    if ($start >= $stop) { continue; }
                    $total = self::combine($total, self::rawSummary($pdo, $monitorId, $start, $stop));
                }
            } else { $total = self::rawSummary($pdo, $monitorId, $begin, $until); }
        } else { $total = self::rawSummary($pdo, $monitorId, $begin, $until); }
        $downtime = self::incidentDowntime($pdo, $monitorId, $begin, $until);
        $count = $total['checks'];
        return [
            'period' => $period, 'period_start' => $begin, 'period_end' => $until,
            'check_count' => $count, 'successful_checks' => $total['successes'], 'failed_checks' => $total['failures'],
            'uptime_percentage' => self::percentage($total['successes'], $count),
            'avg_response_ms' => $total['response_count'] ? round($total['response_sum'] / $total['response_count'], 1) : null,
            'min_response_ms' => $total['response_min'], 'max_response_ms' => $total['response_max'],
            'response_samples' => $total['response_count'], 'first_observed_at' => $total['first_at'],
            'downtime_seconds' => $downtime, 'method' => 'scheduled_check_samples',
        ];
    }

    private static function normalize(array $row): array {
        return [
            'checks' => (int)($row['checks'] ?? 0), 'successes' => (int)($row['successes'] ?? 0),
            'failures' => (int)($row['failures'] ?? 0), 'response_count' => (int)($row['response_count'] ?? 0),
            'response_sum' => (int)($row['response_sum'] ?? 0),
            'response_min' => isset($row['response_min']) ? (int)$row['response_min'] : null,
            'response_max' => isset($row['response_max']) ? (int)$row['response_max'] : null,
            'first_at' => $row['first_at'] ?? null,
        ];
    }
    private static function combine(array $a, array $b): array {
        foreach (['checks','successes','failures','response_count','response_sum'] as $key) { $a[$key] += $b[$key]; }
        foreach (['response_min', 'response_max'] as $key) {
            if ($b[$key] !== null) {
                $a[$key] = $a[$key] === null ? $b[$key] : ($key === 'response_min' ? min($a[$key],$b[$key]) : max($a[$key],$b[$key]));
            }
        }
        if ($b['first_at'] && (!$a['first_at'] || $b['first_at'] < $a['first_at'])) { $a['first_at'] = $b['first_at']; }
        return $a;
    }
    private static function rawSummary(PDO $pdo, int $id, string $from, string $end): array {
        $q = $pdo->prepare("SELECT COUNT(*) AS checks, COALESCE(SUM(success = 1),0) AS successes, COALESCE(SUM(success = 0),0) AS failures, COUNT(response_time_ms) AS response_count, COALESCE(SUM(response_time_ms),0) AS response_sum, MIN(response_time_ms) AS response_min, MAX(response_time_ms) AS response_max, MIN(checked_at) AS first_at FROM monitor_checks WHERE monitor_id=? AND check_type='scheduled' AND checked_at>=? AND checked_at<?");
        $q->execute([$id,$from,$end]);
        return self::normalize($q->fetch(PDO::FETCH_ASSOC) ?: []);
    }
    private static function backfillCaughtUp(PDO $pdo): bool {
        $cursor = SystemMeta::getMany(['hourly_aggregated_check_id'])['hourly_aggregated_check_id'];
        if ($cursor === null) { return false; }
        $maximum = (int)$pdo->query("SELECT COALESCE(MAX(id),0) FROM monitor_checks WHERE check_type='scheduled'")->fetchColumn();
        return (int)$cursor >= $maximum;
    }

    /** Union of clipped intervals prevents double counting even with corrupt overlaps. */
    public static function unionSeconds(array $intervals, int $from, int $until): int {
        $clipped = [];
        foreach ($intervals as $span) {
            $s = max($from, (int)$span[0]); $e = min($until, (int)$span[1]);
            if ($e > $s) { $clipped[] = [$s, $e]; }
        }
        usort($clipped, static fn($a,$b) => $a[0] <=> $b[0]);
        $total = 0; $cursor = $from;
        foreach ($clipped as [$s,$e]) { if ($e > $cursor) { $total += $e - max($cursor, $s); $cursor = $e; } }
        return $total;
    }
    private static function incidentDowntime(PDO $pdo, int $id, string $from, string $until): int {
        $stmt = $pdo->prepare('SELECT started_at, recovered_at FROM incidents WHERE monitor_id=? AND started_at < ? AND (recovered_at IS NULL OR recovered_at > ?) ORDER BY started_at');
        $stmt->execute([$id,$until,$from]);
        $spans = [];
        $now = strtotime($until . ' UTC');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $spans[] = [strtotime($row['started_at'] . ' UTC'), $row['recovered_at'] ? strtotime($row['recovered_at'] . ' UTC') : $now];
        }
        return self::unionSeconds($spans, strtotime($from . ' UTC'), $now);
    }

    /** Bounded time series; no response bodies or unbounded raw rows. */
    public static function responseSeries(int $id, string $period): array {
        $period = self::period($period);
        $from = gmdate('Y-m-d H:i:s',time() - self::PERIODS[$period]);
        $pdo = Database::connection();
        if ($period === '24h') {
            $q = $pdo->prepare("SELECT DATE_FORMAT(checked_at,'%Y-%m-%d %H') AS h, FLOOR(MINUTE(checked_at)/15) AS q, AVG(response_time_ms) AS response FROM monitor_checks WHERE monitor_id=? AND check_type='scheduled' AND response_time_ms IS NOT NULL AND checked_at>=? GROUP BY h,q ORDER BY h,q LIMIT 100");
            $q->execute([$id,$from]);
            return array_map(static fn($r) => ['time'=>$r['h'].':'.sprintf('%02d',(int)$r['q']*15).':00','response_ms'=>round((float)$r['response'],1)], $q->fetchAll(PDO::FETCH_ASSOC));
        }
        $hours = $period === '7d' ? 1 : 6;
        if (!self::backfillCaughtUp($pdo)) {
            $q=$pdo->prepare("SELECT MIN(checked_at) AS h, AVG(response_time_ms) AS response FROM monitor_checks WHERE monitor_id=? AND check_type='scheduled' AND response_time_ms IS NOT NULL AND checked_at>=? GROUP BY DATE_FORMAT(checked_at,'%Y-%m-%d'), FLOOR(HOUR(checked_at)/$hours) ORDER BY h ASC LIMIT 200");
            $q->execute([$id,$from]);
            return array_map(static fn($r) => ['time'=>$r['h'],'response_ms'=>round((float)$r['response'],1)],$q->fetchAll(PDO::FETCH_ASSOC));
        }
        $q = $pdo->prepare("SELECT MIN(hour_start) AS h, SUM(sum_response_time_ms) AS response_sum, SUM(response_sample_count) AS response_count FROM monitor_hourly_stats WHERE monitor_id = ? AND hour_start >= ? AND response_sample_count > 0 GROUP BY DATE_FORMAT(hour_start,'%Y-%m-%d'), FLOOR(HOUR(hour_start)/$hours) ORDER BY h ASC LIMIT 200");
        $q->execute([$id,$from]);
        $rows = [];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) { if ((int)$r['response_count']) { $rows[] = ['time'=>$r['h'], 'response_ms'=>round((float)$r['response_sum']/(int)$r['response_count'],1)]; } }
        return $rows;
    }

    public static function dashboardSummary(): array {
        $pdo=Database::connection();
        $from=gmdate('Y-m-d H:i:s', time()-86400);
        $q=$pdo->prepare("SELECT COUNT(*) AS checks, COALESCE(SUM(success=0),0) AS failed, COALESCE(SUM(success=1),0) AS passed, COUNT(response_time_ms) AS response_count, AVG(response_time_ms) AS response_avg FROM monitor_checks WHERE check_type='scheduled' AND checked_at >= ?");
        $q->execute([$from]); $r=$q->fetch(PDO::FETCH_ASSOC);
        return ['checks'=>(int)$r['checks'], 'failed'=>(int)$r['failed'], 'passed'=>(int)$r['passed'], 'availability'=>self::percentage((int)$r['passed'],(int)$r['checks']), 'avg_ms'=>$r['response_avg']===null?null:round((float)$r['response_avg'],1)];
    }
}
