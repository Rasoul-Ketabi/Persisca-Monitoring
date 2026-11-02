<?php $m = $editingMonitor; $id = (int) $m['id']; ?>
<div class="monitor-analytics-shortcut"><a class="button primary" href="<?= e(admin_url('monitors/analytics') . '?id=' . $id) ?>">View uptime & response analytics →</a><span>Uptime measures completed scheduled checks. Unchecked time is excluded.</span></div>
<div class="monitor-detail-grid">
<section class="panel"><div class="panel-header"><div><h2><?= e($m['name']) ?></h2><p class="diagnostic-url"><?= e($m['url']) ?></p></div><a class="button primary" href="<?= e(admin_url('monitors/edit') . '?id=' . $id) ?>">Edit monitor</a></div>
<div class="detail-stats">
<div><small>Configuration</small><strong><?= $m['is_active'] ? 'Active' : 'Paused' ?></strong></div>
<div><small>Operational state</small><strong><?= e(monitor_state($m)) ?></strong></div>
<div><small>Consecutive failed checks</small><strong><?= (int) $m['consecutive_failures'] ?> / <?= (int) $m['failure_threshold'] ?></strong></div>
<div><small>Last success</small><strong><?= e(display_date($m['last_success_at'])) ?></strong></div>
<div><small>Last failure</small><strong><?= e(display_date($m['last_failure_at'])) ?></strong></div>
<div><small>Visibility</small><strong><?= $m['is_public'] ? 'Public' : 'Private' ?></strong></div>
<div><small>Last scheduled result</small><strong><?= !$m['last_checked_at'] ? 'Never Checked' : ($m['last_check_success'] ? 'Successful' : 'Failed') ?></strong></div>
<div><small>Response time</small><strong><?= $m['last_response_time_ms'] === null ? 'Unavailable' : (int) $m['last_response_time_ms'] . ' ms' ?></strong></div>
<div><small>HTTP status</small><strong><?= $m['last_http_status'] === null ? 'Unavailable' : (int) $m['last_http_status'] ?></strong></div>
<div><small>Attempts</small><strong><?= $m['last_attempt_count'] === null ? 'Unavailable' : (int) $m['last_attempt_count'] ?></strong></div>
<div><small>Last scheduled check</small><strong><?= e(display_date($m['last_checked_at'])) ?></strong></div>
<div><small>Next scheduled check</small><strong><?= !$m['is_active'] ? 'Paused' : e($m['next_check_at'] ? display_date($m['next_check_at']) : 'Next cron run') ?></strong></div>
</div>
<?php if ($m['last_error_type']): ?><div class="monitor-diagnostic-result test-fail-panel"><strong><?= e($m['last_error_type']) ?></strong><span class="monitor-error-text"><?= e($m['last_error_message']) ?></span></div><?php endif; ?>
<?php if ($m['current_incident_id']): ?><div class="monitor-diagnostic-result test-fail-panel"><strong>Confirmed outage</strong><a href="<?= e(admin_url('incidents/view') . '?id=' . (int) $m['current_incident_id']) ?>">View active incident #<?= (int) $m['current_incident_id'] ?></a><?php if (!$m['is_active']): ?><span>Monitoring paused. Recovery cannot be detected until this monitor is resumed.</span><?php endif; ?></div><?php elseif ((int) $m['consecutive_failures'] > 0): ?><div class="monitor-awaiting"><?= (int) $m['consecutive_failures'] ?> failed scheduled check(s) pending confirmation. The monitor has not been declared Down.</div><?php endif; ?>
<div class="monitor-detail-settings"><span>Every <?= (int) $m['check_interval_minutes'] ?> min</span><span>Timeout <?= (int) $m['timeout_seconds'] ?>s</span><span>Accepted HTTP <?= e($m['expected_status_codes']) ?></span><span><?= (int) $m['retry_count'] ?> retries</span><span><?= (int) $m['retry_delay_seconds'] ?>s retry delay</span></div>
<form method="post" action="<?= e(admin_url('monitors/view') . '?id=' . $id) ?>"><?= \Monitor\Core\Security::csrfField() ?><button type="submit" name="action" value="test" class="button primary">Test Now</button></form><p class="field-help">Manual tests do not affect the scheduled snapshot or history below.</p>
</section>
<section class="panel"><div class="panel-header"><div><h2>Recent incidents</h2><p>Confirmed outage history for this monitor.</p></div></div><div class="incident-history-list"><?php if (!$monitorIncidents): ?><div class="monitor-awaiting">No confirmed incidents recorded.</div><?php else: ?><?php foreach ($monitorIncidents as $incident): ?><div class="incident-history-row"><div><a class="monitor-title" href="<?= e(admin_url('incidents/view') . '?id=' . (int) $incident['id']) ?>">Incident #<?= (int) $incident['id'] ?></a><small><?= e(display_date($incident['started_at'])) ?> · <?= e(incident_duration($incident)) ?></small></div><span class="pill <?= $incident['status'] === 'open' ? 'pill-danger' : 'pill-success' ?>"><?= $incident['status'] === 'open' ? 'Open' : 'Resolved' ?></span></div><?php endforeach; ?><?php endif; ?></div></section>
<section class="panel"><div class="panel-header"><div><h2>Recent check history</h2><p><?= (int) $historyCount ?> recorded scheduled checks. Latest first.</p></div></div>
<?php if (!$historyRows): ?><div class="monitor-awaiting">No scheduled checks have completed yet. Check your cron settings in System Health.</div>
<?php else: ?><div class="monitor-table-scroll"><table class="monitor-table"><thead><tr><th>Checked at</th><th>Type</th><th>Result</th><th>HTTP</th><th>Response</th><th>Attempts</th><th>Error</th></tr></thead><tbody>
<?php foreach ($historyRows as $check): ?><tr>
<td data-label="Checked at"><?= e(display_date($check['checked_at'])) ?></td>
<td data-label="Type"><?= e(ucfirst($check['check_type'])) ?></td>
<td data-label="Result"><span class="pill <?= $check['success'] ? 'pill-success' : 'pill-danger' ?>"><?= $check['success'] ? 'Successful' : 'Failed' ?></span></td>
<td data-label="HTTP"><?= $check['http_status'] !== null ? (int) $check['http_status'] : '—' ?></td>
<td data-label="Response"><?= $check['response_time_ms'] !== null ? (int) $check['response_time_ms'] . ' ms' : '—' ?></td>
<td data-label="Attempts"><?= (int) $check['attempt_count'] ?></td>
<td data-label="Error"><span class="history-error" title="<?= e($check['error_message']) ?>"><?= e($check['error_type'] ?: '—') ?></span><?php if ($check['error_message']): ?><small class="monitor-error-text"><?= e($check['error_message']) ?></small><?php endif; ?></td>
</tr><?php endforeach; ?></tbody></table></div>
<?php if ($historyPages > 1): ?><div class="history-pagination">
<?php if ($historyPage > 1): ?><a href="<?= e(admin_url('monitors/view') . '?id=' . $id . '&p=' . ($historyPage - 1)) ?>">← Previous</a><?php endif; ?>
<span>Page <?= $historyPage ?> of <?= $historyPages ?></span>
<?php if ($historyPage < $historyPages): ?><a href="<?= e(admin_url('monitors/view') . '?id=' . $id . '&p=' . ($historyPage + 1)) ?>">Next →</a><?php endif; ?>
</div><?php endif; ?>
<?php endif; ?></section>
</div>
