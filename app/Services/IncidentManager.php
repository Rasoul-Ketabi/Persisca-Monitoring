<?php
declare(strict_types=1);
namespace Monitor\Services;

use PDO;

/** Applies only persisted scheduled results, inside the caller's monitor-row transaction. */
final class IncidentManager {
    /** Pure state-decision helper; also used by deterministic unit tests. */
    public static function decide(array $monitor, bool $success, string $checkedAt): array {
        $previous = (string) ($monitor['operational_status'] ?? 'unknown');
        $failures = (int) ($monitor['consecutive_failures'] ?? 0);
        $threshold = max(1, min(5, (int) ($monitor['failure_threshold'] ?? 2)));
        $start = $monitor['failure_sequence_started_at'] ?? null;
        if ($success) {
            return ['transition' => $previous === 'down' ? 'recovered' : 'none', 'previous_status' => $previous,
                'current_status' => 'up', 'consecutive_failures' => 0, 'failure_sequence_started_at' => null];
        }
        $failures++;
        if (!$start || $failures === 1) { $start = $checkedAt; }
        $down = $previous === 'down' || $failures >= $threshold;
        return ['transition' => $previous !== 'down' && $down ? 'down' : 'none',
            'previous_status' => $previous, 'current_status' => $down ? 'down' : $previous,
            'consecutive_failures' => $failures, 'failure_sequence_started_at' => $start];
    }

    /**
     * Caller MUST lock the monitor row SELECT ... FOR UPDATE and own the transaction.
     * No independent COMMIT here. Throws on inconsistent state to roll back the check and incident together.
     */
    public static function apply(PDO $pdo, array $monitor, int $checkId, array $result, string $checkedAt): array {
        $id = (int) $monitor['id'];
        $success = !empty($result['success']);
        $decision = self::decide($monitor, $success, $checkedAt);
        $transition = $decision['transition'];
        $incidentId = (int) ($monitor['current_incident_id'] ?? 0);
        $errorType = !empty($result['error_type']) ? mb_safe_substr((string) $result['error_type'], 100) : null;
        $errorMessage = !empty($result['error_message']) ? mb_safe_substr((string) $result['error_message'], 2000) : null;
        $http = $result['http_status'] ?: null;
        $start = $decision['failure_sequence_started_at'];
        $duration = null;
        $recoveredStart = null;
        $detectedAt = null;

        if ($decision['previous_status'] === 'down' && !$incidentId) {
            throw new \RuntimeException('A DOWN monitor is missing its active incident.');
        }
        if ($incidentId) {
            $verify = $pdo->prepare("SELECT id FROM incidents WHERE id = ? AND monitor_id = ? AND status = 'open' FOR UPDATE");
            $verify->execute([$incidentId, $id]);
            if (!$verify->fetchColumn()) { throw new \RuntimeException('The active incident reference is inconsistent.'); }
        }
        if ($transition === 'down') {
            if ($incidentId) { throw new \RuntimeException('An open incident already exists.'); }
            $first = $pdo->prepare("SELECT id FROM monitor_checks WHERE monitor_id = ? AND check_type = 'scheduled' AND success = 0 AND checked_at >= ? AND checked_at <= ? ORDER BY checked_at, id LIMIT 1");
            $first->execute([$id, $start, $checkedAt]);
            $openingCheckId = (int) ($first->fetchColumn() ?: $checkId);
            $create = $pdo->prepare("INSERT INTO incidents (monitor_id, status, started_at, detected_at, opening_check_id, failure_count, last_error_type, last_error_message, last_http_status, created_at, updated_at) VALUES (?, 'open', ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())");
            $create->execute([$id, $start, $checkedAt, $openingCheckId, $decision['consecutive_failures'], $errorType, $errorMessage, $http]);
            $incidentId = (int) $pdo->lastInsertId();
            $link = $pdo->prepare("UPDATE monitor_checks SET incident_id = ? WHERE monitor_id = ? AND check_type = 'scheduled' AND success = 0 AND checked_at >= ? AND checked_at <= ? AND incident_id IS NULL");
            $link->execute([$incidentId, $id, $start, $checkedAt]);
        } elseif ($decision['previous_status'] === 'down' && !$success) {
            $update = $pdo->prepare("UPDATE incidents SET failure_count = failure_count + 1, last_error_type = ?, last_error_message = ?, last_http_status = ?, updated_at = UTC_TIMESTAMP() WHERE id = ? AND monitor_id = ? AND status = 'open'");
            $update->execute([$errorType, $errorMessage, $http, $incidentId, $id]);
            if ($update->rowCount() !== 1) { throw new \RuntimeException('Unable to continue the open incident.'); }
            $pdo->prepare('UPDATE monitor_checks SET incident_id = ? WHERE id = ?')->execute([$incidentId, $checkId]);
        } elseif ($transition === 'recovered') {
            $open = $pdo->prepare("SELECT started_at, detected_at FROM incidents WHERE id = ? AND monitor_id = ? AND status = 'open' FOR UPDATE");
            $open->execute([$incidentId, $id]);
            $opened = $open->fetch(PDO::FETCH_ASSOC);
            $startedAt = (string) $opened['started_at'];
            $detectedAt = (string) $opened['detected_at'];
            $recoveredStart = $startedAt;
            $duration = max(0, (new \DateTimeImmutable($checkedAt, new \DateTimeZone('UTC')))->getTimestamp()
                - (new \DateTimeImmutable($startedAt, new \DateTimeZone('UTC')))->getTimestamp());
            $close = $pdo->prepare("UPDATE incidents SET status = 'resolved', recovered_at = ?, closing_check_id = ?, duration_seconds = ?, updated_at = UTC_TIMESTAMP() WHERE id = ? AND status = 'open'");
            $close->execute([$checkedAt, $checkId, $duration, $incidentId]);
            if ($close->rowCount() !== 1) { throw new \RuntimeException('Unable to close the open incident.'); }
            $pdo->prepare('UPDATE monitor_checks SET incident_id = ? WHERE id = ?')->execute([$incidentId, $checkId]);
        }
        $currentIncidentId = $transition === 'recovered' ? null : ($incidentId ?: null);
        $fields = $pdo->prepare('UPDATE monitors SET operational_status = ?, consecutive_failures = ?, failure_sequence_started_at = ?, last_success_at = CASE WHEN ? = 1 THEN ? ELSE last_success_at END, last_failure_at = CASE WHEN ? = 0 THEN ? ELSE last_failure_at END, current_incident_id = ? WHERE id = ?');
        $fields->execute([$decision['current_status'], $decision['consecutive_failures'], $start,
            $success ? 1 : 0, $checkedAt, $success ? 1 : 0, $checkedAt, $currentIncidentId, $id]);
        return [
            'transition' => $transition, 'monitor_id' => $id, 'incident_id' => $incidentId ?: null,
            'previous_status' => $decision['previous_status'], 'current_status' => $decision['current_status'],
            'started_at' => $recoveredStart ?? $start, 'detected_at' => $transition === 'down' ? $checkedAt : $detectedAt,
            'recovered_at' => $transition === 'recovered' ? $checkedAt : null, 'duration_seconds' => $duration,
        ];
    }
}
