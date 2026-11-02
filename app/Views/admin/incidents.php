<section class="panel incident-panel">
  <div class="panel-header"><div><h2>Confirmed incidents</h2><p>Outages open only after the configured number of failed scheduled checks.</p></div><span class="pill <?= $incidentCounts['active_incidents'] ? 'pill-danger' : 'pill-success' ?>"><?= (int) $incidentCounts['active_incidents'] ?> active</span></div>
  <nav class="incident-tabs" aria-label="Incident filters"><a class="<?= $incidentTab === 'active' ? 'selected' : '' ?>" href="<?= e(admin_url('incidents')) ?>">Active (<?= (int) $incidentCounts['active_incidents'] ?>)</a><a class="<?= $incidentTab === 'history' ? 'selected' : '' ?>" href="<?= e(admin_url('incidents') . '?tab=history') ?>">History</a></nav>
  <?php if (!$incidentRows): ?><div class="monitor-empty"><span class="monitor-empty-icon">✓</span><h2><?= $incidentTab === 'active' ? 'No active incidents' : 'No resolved incidents yet' ?></h2><p><?= $incidentTab === 'active' ? 'Confirmed outages will appear here as monitoring runs.' : 'Resolved outages will appear after a monitored service recovers.' ?></p></div>
  <?php else: ?><div class="monitor-table-scroll"><table class="monitor-table incident-table"><thead><tr><th>Monitor</th><th>Started</th><th><?= $incidentTab === 'active' ? 'Detected' : 'Recovered' ?></th><th>Duration</th><th>Failures</th><th>Latest error</th><th>Details</th></tr></thead><tbody>
  <?php foreach ($incidentRows as $incident): ?><tr>
    <td data-label="Monitor"><a class="monitor-title" href="<?= e(admin_url('monitors/view') . '?id=' . (int) $incident['monitor_id']) ?>"><?= e($incident['monitor_name']) ?></a><span class="pill <?= $incident['status'] === 'open' ? 'pill-danger' : 'pill-success' ?>"><?= $incident['status'] === 'open' ? 'Open' : 'Resolved' ?></span></td>
    <td data-label="Started"><?= e(display_date($incident['started_at'])) ?></td>
    <td data-label="<?= $incidentTab === 'active' ? 'Detected' : 'Recovered' ?>"><?= e(display_date($incidentTab === 'active' ? $incident['detected_at'] : $incident['recovered_at'])) ?></td>
    <td data-label="Duration"><?= e(incident_duration($incident)) ?></td>
    <td data-label="Failures"><?= (int) $incident['failure_count'] ?></td>
    <td data-label="Latest error"><span class="history-error"><?= e($incident['last_error_type'] ?: ($incident['last_http_status'] ? 'HTTP ' . $incident['last_http_status'] : 'Unavailable')) ?></span></td>
    <td data-label="Details"><a class="monitor-action-link" href="<?= e(admin_url('incidents/view') . '?id=' . (int) $incident['id']) ?>">View incident</a></td>
  </tr><?php endforeach; ?></tbody></table></div>
  <?php if ($incidentPages > 1): ?><div class="history-pagination">
    <?php if ($incidentPage > 1): ?><a href="<?= e(admin_url('incidents') . '?tab=' . $incidentTab . '&p=' . ($incidentPage - 1)) ?>">Previous</a><?php endif; ?>
    <span>Page <?= $incidentPage ?> of <?= $incidentPages ?></span>
    <?php if ($incidentPage < $incidentPages): ?><a href="<?= e(admin_url('incidents') . '?tab=' . $incidentTab . '&p=' . ($incidentPage + 1)) ?>">Next</a><?php endif; ?>
  </div><?php endif; ?>
  <?php endif; ?>
</section>
<div class="monitor-info-bar"><span class="status-infodot"></span><span>A failed check does not necessarily mean an outage. Manual tests never create or resolve incidents.</span></div>
