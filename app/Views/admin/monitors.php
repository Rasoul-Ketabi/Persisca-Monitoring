<section class="panel monitor-panel">
  <div class="panel-header monitor-panel-head">
    <div><h2>Configured monitors</h2><p>Scheduled results come from your server cron. Manual tests are available separately.</p></div>
    <div class="monitor-panel-tools"><span class="pill pill-neutral"><?= count($monitorList) ?> configured</span><a href="<?= e(admin_url('monitors/add')) ?>" class="button primary">+ Add monitor</a></div>
  </div>
  <?php if (!$monitorList): ?>
    <div class="monitor-empty"><span class="monitor-empty-icon">⌁</span><h2>No monitors yet</h2><p>Add an endpoint, then configure the server cron to start scheduled checks.</p><a class="button primary" href="<?= e(admin_url('monitors/add')) ?>">Add your first monitor</a></div>
  <?php else: ?>
  <div class="monitor-table-scroll"><table class="monitor-table"><thead><tr><th>Monitor</th><th>Owner</th><th>State</th><th>Last scheduled check</th><th>HTTP</th><th>Response</th><th>Next check</th><th>Interval</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($monitorList as $monitor): ?>
      <tr>
        <td data-label="Monitor"><div class="monitor-identity"><span class="monitor-service-dot"></span><div><a class="monitor-title" href="<?= e(admin_url('monitors/view') . '?id=' . $monitor['id']) ?>"><?= e($monitor['name']) ?></a><span class="monitor-url" title="<?= e($monitor['url']) ?>"><?= e($monitor['url']) ?></span><small class="monitor-muted"><?= $monitor['is_public'] ? 'Public' : 'Private' ?></small></div></div></td>
        <td data-label="Owner"><?= e($monitor['owner_name'] ?: 'Platform') ?></td>
        <td data-label="State"><span class="pill <?= monitor_state_class($monitor) ?>"><?= e(monitor_state($monitor)) ?></span><?php if ($monitor['is_active'] && $monitor['operational_status'] !== 'down' && (int) $monitor['consecutive_failures']): ?><small class="test-time"><?= (int) $monitor['consecutive_failures'] ?> failure(s) pending confirmation</small><?php endif; ?></td>
        <td data-label="Last scheduled check">
          <?php if (empty($monitor['last_checked_at'])): ?><span class="monitor-muted">Never Checked</span>
          <?php else: ?><span class="pill <?= $monitor['last_check_success'] ? 'pill-success' : 'pill-danger' ?>"><?= $monitor['last_check_success'] ? 'Successful' : 'Failed' ?></span><small class="test-time"><?= e(display_date($monitor['last_checked_at'])) ?></small><?php endif; ?>
        </td>
        <td data-label="HTTP"><?= $monitor['last_http_status'] !== null ? (int) $monitor['last_http_status'] : '—' ?></td>
        <td data-label="Response"><?= $monitor['last_response_time_ms'] !== null ? (int) $monitor['last_response_time_ms'] . ' ms' : '—' ?></td>
        <td data-label="Next check"><?= $monitor['is_active'] ? e($monitor['next_check_at'] ? display_date($monitor['next_check_at']) : 'Due on next cron run') : 'Paused' ?></td>
        <td data-label="Interval"><?= (int) $monitor['check_interval_minutes'] ?> min</td>
        <td data-label="Actions"><div class="monitor-actions">
          <a href="<?= e(admin_url('monitors/view') . '?id=' . $monitor['id']) ?>" class="monitor-action-link">History</a>
          <a href="<?= e(admin_url('monitors/edit') . '?id=' . $monitor['id']) ?>" class="monitor-action-link">Edit</a>
          <form method="post" action="<?= e(admin_url('monitors')) ?>"><?= \Monitor\Core\Security::csrfField() ?><input type="hidden" name="id" value="<?= (int) $monitor['id'] ?>"><button class="monitor-action-link" type="submit" name="action" value="test">Test Now</button></form>
          <form method="post" action="<?= e(admin_url('monitors')) ?>" <?= $monitor['is_active'] && $monitor['operational_status'] === 'down' ? 'data-confirm="This monitor has an active incident. Pausing monitoring stops automatic recovery detection. Continue?"' : '' ?>><?= \Monitor\Core\Security::csrfField() ?><input type="hidden" name="id" value="<?= (int) $monitor['id'] ?>"><button class="monitor-action-link" type="submit" name="action" value="toggle"><?= $monitor['is_active'] ? 'Pause' : 'Resume' ?></button></form>
          <form method="post" action="<?= e(admin_url('monitors')) ?>" data-confirm="Delete this monitor and its check and incident history? This cannot be undone."><?= \Monitor\Core\Security::csrfField() ?><input type="hidden" name="id" value="<?= (int) $monitor['id'] ?>"><button class="monitor-action-link monitor-delete" type="submit" name="action" value="delete">Delete</button></form>
        </div></td>
      </tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>
<div class="monitor-info-bar"><span class="status-infodot"></span><span>Active / Paused describes configuration. Last scheduled check describes the most recent check result. Down is confirmed after the configured failure threshold. Pending failures are not incidents.</span></div>
