<?php
declare(strict_types=1);
namespace Monitor\Services;

use DateTimeImmutable;
use DateTimeZone;
use Monitor\Core\Database;
use Monitor\Models\Settings;
use Monitor\Models\SystemMeta;
use PDO;

/** Safe public-only read model. No private monitor URLs, provider errors, or credentials. */
final class PublicStatus {
    public const DAYS = 30;

    public static function overall(array $monitors, array $activeIncidents): string {
        if (!$monitors) { return 'none'; }
        $active = array_values(array_filter($monitors, static fn(array $m): bool => (int)$m['is_active'] === 1));
        $paused = count($monitors) - count($active);
        $up = count(array_filter($active, static fn(array $m): bool => $m['operational_status'] === 'up'));
        $down = count(array_filter($active, static fn(array $m): bool => $m['operational_status'] === 'down'));
        if ($down || $activeIncidents) {
            return $active && $down === count($active) ? 'major' : 'issues';
        }
        if ($active && $up === count($active) && $paused === 0) { return 'operational'; }
        return 'pending';
    }

    public static function labels(string $status): array {
        return match ($status) {
            'operational' => ['All Systems Operational', 'All public services are reporting operational status.'],
            'issues' => ['Some Systems Experiencing Issues', 'One or more public services have a confirmed availability issue.'],
            'major' => ['Major Service Disruption', 'All currently monitored public services are reporting an outage.'],
            'none' => ['No Public Monitors', 'No public services are configured yet.'],
            default => ['Status Pending', 'Some public services have not been checked or monitoring is paused.'],
        };
    }

    public static function monitorLabel(array $monitor): array {
        if (!(int)$monitor['is_active']) {
            return ['Monitoring Paused', 'paused'];
        }
        return match ($monitor['operational_status'] ?? 'unknown') {
            'up' => ['Operational', 'operational'],
            'down' => ['Outage', 'outage'],
            default => ['Status Pending', 'pending'],
        };
    }

    public static function metadata(string $state, string $appName, string $configuredDescription = ''): array {
        [$label, $description] = self::labels($state);
        // The current operational state takes precedence over stale static OG copy.
        return ["$label | $appName", $description . ($configuredDescription !== '' ? ' ' . $configuredDescription : '')];
    }

    public static function snapshot(array $settings, ?int $ownerId = null): array {
        $pdo = Database::connection();
        // NULL scope is reserved for the platform's global status page. Personal pages
        // always bind the authenticated publication owner's ID in every read query.
        $scope = $ownerId === null ? ' IS NULL' : ' = ?';
        $args = $ownerId === null ? [] : [$ownerId];
        $q = $pdo->prepare('SELECT id, name, is_active, operational_status, last_checked_at, last_response_time_ms, current_incident_id FROM monitors WHERE is_public=1 AND portal_user_id'.$scope.' ORDER BY name ASC, id ASC LIMIT 500');
        $q->execute($args);
        $monitors = $q->fetchAll(PDO::FETCH_ASSOC);
        $q = $pdo->prepare("SELECT i.id, i.monitor_id, i.status, i.started_at, i.detected_at, i.recovered_at, i.duration_seconds, m.name AS monitor_name FROM incidents i INNER JOIN monitors m ON m.id=i.monitor_id WHERE m.is_public=1 AND m.portal_user_id".$scope." AND i.status='open' ORDER BY i.started_at DESC,i.id DESC LIMIT 30");
        $q->execute($args);
        $active = $q->fetchAll(PDO::FETCH_ASSOC);
        $q = $pdo->prepare("SELECT i.id, i.monitor_id, i.status, i.started_at, i.detected_at, i.recovered_at, i.duration_seconds, m.name AS monitor_name FROM incidents i INNER JOIN monitors m ON m.id=i.monitor_id WHERE m.is_public=1 AND m.portal_user_id".$scope." AND i.status='resolved' AND i.recovered_at>=UTC_TIMESTAMP()-INTERVAL 30 DAY ORDER BY i.recovered_at DESC,i.id DESC LIMIT 12");
        $q->execute($args);
        $resolved = $q->fetchAll(PDO::FETCH_ASSOC);
        $state = self::overall($monitors, $active);
        $updated = null;
        foreach ($monitors as $monitor) {
            if ($monitor['last_checked_at'] && ($updated === null || $monitor['last_checked_at'] > $updated)) { $updated = $monitor['last_checked_at']; }
        }
        $zone = self::zone((string)($settings['timezone'] ?? 'UTC'));
        foreach ($monitors as &$monitor) {
            $monitor['history'] = self::history((int)$monitor['id'], $zone, !(int)$monitor['is_active']);
            $summary = MonitorAnalytics::summary((int)$monitor['id'], '30d');
            $monitor['uptime'] = $summary['uptime_percentage'];
            $monitor['samples'] = $summary['check_count'];
            $monitor['observed_from'] = $summary['first_observed_at'];
        }
        unset($monitor);
        return ['monitors'=>$monitors,'active'=>$active,'resolved'=>$resolved,'state'=>$state,'last_checked_at'=>$updated];
    }

    public static function zone(string $name): DateTimeZone {
        try { return new DateTimeZone($name); } catch (\Throwable $e) { return new DateTimeZone('UTC'); }
    }

    /** Build 30 calendar days in the configured timezone (including today). */
    public static function days(DateTimeZone $zone, ?DateTimeImmutable $now = null): array {
        $today = ($now ?: new DateTimeImmutable('now', new DateTimeZone('UTC')))->setTimezone($zone)->setTime(0, 0);
        $days = [];
        for ($i=self::DAYS-1; $i>=0; $i--) {
            $start = $today->modify("-$i days");
            $end = $start->modify('+1 day');
            $days[$start->format('Y-m-d')] = [
                'date'=>$start->format('Y-m-d'), 'start'=>$start->getTimestamp(), 'end'=>$end->getTimestamp(),
                'checks'=>0, 'successes'=>0, 'failures'=>0, 'downtime_seconds'=>0,
                'incident_count'=>0, 'approximate'=>false, 'state'=>'no_data', 'uptime_percentage'=>null,
            ];
        }
        return $days;
    }

    /** Daily state is sampled check availability, never assumed continuous uptime. */
    public static function dailyState(int $checks, int $successes, bool $currentlyPaused = false, int $confirmedDowntime = 0): string {
        if ($checks <= 0) { return $confirmedDowntime > 0 ? 'partial' : ($currentlyPaused ? 'paused' : 'no_data'); }
        if ($successes <= 0) { return 'outage'; }
        if ($confirmedDowntime > 0) { return 'partial'; }
        if ($successes >= $checks) { return 'operational'; }
        return 'partial';
    }

    /** Daily history backed by durable hourly statistics plus only the not-yet-aggregated raw rows.
     * Hourly UTC buckets that cross non-integer local offsets are flagged approximate when
     * detailed raw data is unavailable. Raw-check retention does not erase day histories.
     */
    public static function history(int $monitorId, DateTimeZone $zone, bool $paused): array {
        $days = self::days($zone);
        $first = reset($days); $last = end($days);
        $begin = gmdate('Y-m-d H:i:s', $first['start'] - 3600);
        $end = gmdate('Y-m-d H:i:s', $last['end'] + 3600);
        $pdo = Database::connection();
        $cursor = (int)(SystemMeta::getMany(['hourly_aggregated_check_id'])['hourly_aggregated_check_id'] ?? 0);
        $q = $pdo->prepare('SELECT hour_start AS bucket, scheduled_check_count AS checks, successful_check_count AS successes FROM monitor_hourly_stats WHERE monitor_id=? AND hour_start>=? AND hour_start<? ORDER BY hour_start');
        $q->execute([$monitorId, $begin, $end]);
        $hours = [];
        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $hours[$row['bucket']] = [(int)$row['checks'], (int)$row['successes']];
        }
        // Summarise only raw checks that haven't yet entered hourly aggregates.
        $raw = $pdo->prepare("SELECT DATE_FORMAT(checked_at, '%Y-%m-%d %H:00:00') AS bucket, COUNT(*) AS checks, COALESCE(SUM(success=1),0) AS successes FROM monitor_checks WHERE monitor_id=? AND check_type='scheduled' AND id>? AND checked_at>=? AND checked_at<? GROUP BY bucket");
        $raw->execute([$monitorId, $cursor, $begin, $end]);
        while ($row = $raw->fetch(PDO::FETCH_ASSOC)) {
            $bucket = $row['bucket'];
            $old = $hours[$bucket] ?? [0,0];
            $hours[$bucket] = [$old[0]+(int)$row['checks'], $old[1]+(int)$row['successes']];
        }
        foreach ($hours as $bucket => [$count, $passed]) {
            $utc = new DateTimeImmutable($bucket, new DateTimeZone('UTC'));
            $local = $utc->setTimezone($zone);
            $date = $local->format('Y-m-d');
            if (!isset($days[$date])) { continue; }
            $days[$date]['checks'] += $count;
            $days[$date]['successes'] += $passed;
            $days[$date]['failures'] += $count - $passed;
            if ($date !== $utc->modify('+3599 seconds')->setTimezone($zone)->format('Y-m-d')) {
                $days[$date]['approximate'] = true;
            }
        }
        // Confirmed downtime overlaps use actual incident timestamps, clipped to each local day.
        $q = $pdo->prepare('SELECT started_at, recovered_at FROM incidents WHERE monitor_id=? AND started_at<? AND (recovered_at IS NULL OR recovered_at>?) ORDER BY started_at');
        $q->execute([$monitorId, gmdate('Y-m-d H:i:s',$last['end']), gmdate('Y-m-d H:i:s',$first['start'])]);
        $spans = [];
        $now = time();
        while ($incident = $q->fetch(PDO::FETCH_ASSOC)) {
            $start = strtotime($incident['started_at'].' UTC');
            $stop = $incident['recovered_at'] ? strtotime($incident['recovered_at'].' UTC') : $now;
            if (!$start || !$stop) { continue; }
            $spans[] = [$start,$stop];
            $date = (new DateTimeImmutable('@'.$start))->setTimezone($zone)->format('Y-m-d');
            if (isset($days[$date])) { $days[$date]['incident_count']++; }
        }
        $todayKey = array_key_last($days);
        foreach ($days as $key => &$day) {
            $day['downtime_seconds'] = MonitorAnalytics::unionSeconds($spans, $day['start'], min($day['end'], $now));
            $day['uptime_percentage'] = MonitorAnalytics::percentage($day['successes'],$day['checks']);
            $day['state'] = self::dailyState($day['checks'], $day['successes'], $paused && $key === $todayKey, $day['downtime_seconds']);
        }
        unset($day);
        return array_values($days);
    }
}
