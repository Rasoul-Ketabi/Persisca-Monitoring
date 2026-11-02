<?php
$isEdit = $page === 'monitors/edit';
$formUrl = $isEdit ? admin_url('monitors/edit') . '?id=' . $monitorId : admin_url('monitors/add');
$m = $editingMonitor;
?>
<div class="monitor-form-layout"><section class="panel settings-panel monitor-form-panel">
<div class="panel-header"><div><h2><?= $isEdit ? 'Monitor configuration' : 'New monitor' ?></h2><p><?= $isEdit ? 'Update your endpoint settings or run a manual diagnostic.' : 'Add a website or HTTP endpoint to your workspace.' ?></p></div><a class="monitor-back-link" href="<?= e(admin_url('monitors')) ?>">← All monitors</a></div>
<form method="post" action="<?= e($formUrl) ?>" class="settings-form monitor-edit-form"><?= \Monitor\Core\Security::csrfField() ?><input type="hidden" name="action" value="save">
  <div class="form-section"><h3>Endpoint details</h3><p>Give your endpoint a name and a complete HTTP or HTTPS URL.</p>
    <label>Monitor name<input name="name" required maxlength="160" value="<?= e((string) $m['name']) ?>" placeholder="Main Website"></label>
    <label>Monitor URL<input type="url" name="url" required maxlength="2048" value="<?= e((string) $m['url']) ?>" placeholder="https://example.com"><span class="field-help">Use a complete http:// or https:// address. Credentials inside URLs are not allowed.</span></label>
  </div>
  <div class="form-section"><h3>Availability & visibility</h3><p>Active monitors are checked when due by the server cron. Paused monitors are skipped.</p>
    <div class="monitor-switches"><?php if ($isEdit && $m['operational_status'] === 'down'): ?><div class="monitor-diagnostic-result test-fail-panel"><strong>Active incident</strong><span>Pausing monitoring will stop automatic recovery detection. The incident will remain open.</span></div><?php endif; ?><label class="monitor-toggle"><input type="checkbox" name="is_active" value="1" <?= $m['is_active'] ? 'checked' : '' ?>><span><strong>Active</strong><small>Include this endpoint in scheduled checks</small></span></label>
    <label class="monitor-toggle"><input type="checkbox" name="is_public" value="1" <?= $m['is_public'] ? 'checked' : '' ?>><span><strong>Show on public status page</strong><small>Allow this service to appear publicly when status publishing is enabled</small></span></label>
    <label class="monitor-toggle"><input type="checkbox" name="allow_private_network" value="1" <?= $m['allow_private_network'] ? 'checked' : '' ?> <?= \Monitor\Services\Authorization::isSuperAdmin($user) ? '' : 'disabled' ?>><span><strong>Allow private-network targets</strong><small>Opt in to localhost, internal IPs, and private-network redirects. Super Admin only. Enable only for trusted endpoints.</small></span></label></div>
  </div>
  <div class="form-section"><h3>Notifications</h3><p>Uses the global Telegram settings when an outage is confirmed or recovered.</p>
    <label class="monitor-toggle"><input type="checkbox" name="telegram_notifications_enabled" value="1" <?= !empty($m['telegram_notifications_enabled']) ? 'checked' : '' ?>><span><strong>Send Telegram alerts for this monitor</strong><small>Global Telegram must also be enabled in Notifications.</small></span></label>
  </div>
  <div class="form-section"><h3>Check preferences</h3><p>Both manual and scheduled checks use these settings.</p><div class="field-grid">
    <label>Check interval<select name="check_interval_minutes"><?php foreach ([1, 2, 5, 10, 15, 30, 60] as $interval): ?><option value="<?= $interval ?>" <?= (int) $m['check_interval_minutes'] === $interval ? 'selected' : '' ?>><?= $interval ?> minute<?= $interval === 1 ? '' : 's' ?></option><?php endforeach; ?></select></label>
    <label>Request timeout (seconds)<input type="number" name="timeout_seconds" min="1" max="60" required value="<?= (int) $m['timeout_seconds'] ?>"></label>
    <label>Retry count<input type="number" name="retry_count" min="0" max="5" required value="<?= (int) $m['retry_count'] ?>"><span class="field-help">Additional attempts after the first request.</span></label>
    <label>Retry delay (seconds)<input type="number" name="retry_delay_seconds" min="0" max="30" required value="<?= (int) $m['retry_delay_seconds'] ?>"></label>
  </div><label>Expected HTTP status codes<input name="expected_status_codes" required maxlength="255" value="<?= e((string) $m['expected_status_codes']) ?>" placeholder="200,204,301"><span class="field-help">Enter comma-separated codes from 100 to 599. Ranges are not supported yet. Redirects are followed, with a limit of five hops.</span></label>
    <label>Failure confirmation<select name="failure_threshold"><?php foreach ([1,2,3,4,5] as $threshold): ?><option value="<?= $threshold ?>" <?= (int) ($m['failure_threshold'] ?? 2) === $threshold ? 'selected' : '' ?>><?= $threshold ?> failed scheduled check<?= $threshold === 1 ? '' : 's' ?></option><?php endforeach; ?></select><span class="field-help">Number of consecutive failed scheduled checks before this monitor is marked Down. Retries within a check do not count as separate checks.</span></label>
  </div>
  <div class="save-bar"><a href="<?= e(admin_url('monitors')) ?>" class="monitor-cancel">Cancel</a><button type="submit" class="button primary"><?= $isEdit ? 'Save changes' : 'Save monitor' ?></button></div>
</form></section>
<aside class="monitor-aside">
  <section class="panel monitor-diagnostic"><div class="panel-header"><div><h2>Manual connection test</h2><p>Run a real HTTP check from the hosting server.</p></div></div>
    <?php if ($isEdit): ?>
      <form method="post" action="<?= e($formUrl) ?>"><?= \Monitor\Core\Security::csrfField() ?><button type="submit" name="action" value="test" class="button primary monitor-test-btn">↗ Test Now</button></form>
      <p class="field-help">Checks this saved configuration. Save any changes before running the test. Manual results do not change scheduled check state.</p><a class="monitor-action-link" href="<?= e(admin_url('monitors/view') . '?id=' . $monitorId) ?>">View scheduled check history →</a>
      <?php if (!empty($m['last_manual_test_at'])): ?>
        <div class="monitor-diagnostic-result <?= $m['last_manual_test_success'] ? 'test-ok-panel' : 'test-fail-panel' ?>">
          <strong><?= $m['last_manual_test_success'] ? 'Connection successful' : 'Connection failed' ?></strong>
          <span>Checked: <?= e(display_date((string) $m['last_manual_test_at'])) ?></span>
          <span>Attempts: <?= (int) $m['last_manual_test_attempts'] ?></span>
          <span>HTTP status: <?= $m['last_manual_test_http_status'] === null ? 'Unavailable' : (int) $m['last_manual_test_http_status'] ?></span>
          <span>Response time: <?= $m['last_manual_test_response_ms'] === null ? 'Unavailable' : (int) $m['last_manual_test_response_ms'] . ' ms' ?></span>
          <?php if (!empty($m['last_manual_test_error_type'])): ?><span>Reason: <?= e($m['last_manual_test_error_type']) ?></span><?php endif; ?>
          <?php if (!empty($m['last_manual_test_error'])): ?><span class="monitor-error-text"><?= e($m['last_manual_test_error']) ?></span><?php endif; ?>
          <?php if (!empty($m['last_manual_test_effective_url'])): ?><span class="diagnostic-url">Final URL: <?= e($m['last_manual_test_effective_url']) ?></span><?php endif; ?>
        </div>
      <?php else: ?><div class="monitor-awaiting">No manual tests have been run yet.</div><?php endif; ?>
      <div class="monitor-divider"></div><div class="monitor-quick-actions"><form method="post" action="<?= e($formUrl) ?>"><?= \Monitor\Core\Security::csrfField() ?><button class="monitor-action-link" type="submit" name="action" value="toggle"><?= $m['is_active'] ? 'Pause this monitor' : 'Resume this monitor' ?></button></form><form method="post" action="<?= e($formUrl) ?>" data-confirm="Delete this monitor and all its check, incident and notification history? This cannot be undone."><?= \Monitor\Core\Security::csrfField() ?><button class="monitor-action-link monitor-delete" type="submit" name="action" value="delete">Delete monitor</button></form></div>
    <?php else: ?><div class="monitor-awaiting">Save your monitor first, then you can test its endpoint.</div><?php endif; ?>
  </section>
  <section class="panel monitor-tips"><h3>How checks work</h3><p>Manual checks use GET with SSL verification enabled. Retries happen after a failed attempt. Manual diagnostics do not create incidents or overwrite scheduled check history.</p></section>
</aside></div>
