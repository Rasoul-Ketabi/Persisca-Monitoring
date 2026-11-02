<?php
use Monitor\Models\Settings;
$telegramEnabled = Settings::get('telegram_enabled') === '1';
$hasToken = Settings::get('telegram_bot_token') !== '';
$destinationIds = \Monitor\Services\TelegramDestinations::parse(Settings::get('telegram_chat_id'));
$hasChat = (bool) $destinationIds;
$destinationCount = count($destinationIds);
$destinationReady = $hasToken && $hasChat;
?>
<?php if (\Monitor\Services\Authorization::isSuperAdmin($user)): ?>
<div class="notify-layout">
  <section class="panel settings-panel">
    <div class="panel-header"><div><h2>Telegram notifications</h2><p>Receive alerts when a scheduled check confirms an outage or detects recovery.</p></div><span class="pill <?= $telegramEnabled && $destinationReady ? 'pill-success' : 'pill-neutral' ?>"><?= $telegramEnabled && $destinationReady ? 'Enabled' : 'Disabled' ?></span></div>
    <form method="post" action="<?= e(admin_url('notifications')) ?>" class="settings-form">
      <?= \Monitor\Core\Security::csrfField() ?><input type="hidden" name="action" value="save">
      <div class="form-section"><h3>Delivery channel</h3><p>Telegram is optional and disabled until you enable it.</p>
        <label class="monitor-toggle"><input type="checkbox" name="telegram_enabled" value="1" <?= $telegramEnabled ? 'checked' : '' ?>><span><strong>Enable Telegram notifications</strong><small>Send alerts only on confirmed Down and Recovery transitions.</small></span></label>
        <label>Bot Token<input type="password" name="telegram_bot_token" value="" autocomplete="new-password" maxlength="256" placeholder="<?= $hasToken ? 'Saved token (leave blank to keep)' : 'Paste BotFather token' ?>"><span class="field-help">Token status: <strong><?= $hasToken ? 'Saved' : 'Not configured' ?></strong>. Leave blank to keep the existing token. The token is never displayed after saving.</span></label>
        <label>Telegram Chat IDs<textarea name="telegram_chat_id" rows="5" maxlength="1600" placeholder="123456789&#10;-1001234567890&#10;@channelname"><?= e(Settings::get('telegram_chat_id')) ?></textarea><span class="field-help">One Chat ID or @channel username per line, up to 10 unique destinations. Personal, group and channel IDs are supported. Remove a line to stop future delivery to that destination.</span></label>
        <label class="monitor-toggle"><input type="checkbox" name="telegram_notify_down" value="1" <?= Settings::get('telegram_notify_down') === '1' ? 'checked' : '' ?>><span><strong>Send Down alerts</strong><small>One notification when an incident opens.</small></span></label>
        <label class="monitor-toggle"><input type="checkbox" name="telegram_notify_recovery" value="1" <?= Settings::get('telegram_notify_recovery') === '1' ? 'checked' : '' ?>><span><strong>Send Recovery alerts</strong><small>One notification when an incident resolves.</small></span></label>
      </div>
      <div class="form-section"><h3>Remove saved token</h3><p>This clears the token and disables delivery. Other notification settings remain available.</p>
        <label class="monitor-toggle"><input type="checkbox" name="remove_token" value="1"><span><strong>Remove saved bot token</strong><small>Use only when replacing or disconnecting your Telegram bot.</small></span></label>
        <label class="monitor-toggle"><input type="checkbox" name="confirm_remove" value="yes"><span><strong>Confirm removal</strong><small>Required when removing the saved token.</small></span></label>
      </div>
      <div class="save-bar"><button type="submit" class="button primary">Save settings</button></div>
    </form>
  </section>
  <aside class="notify-side">
    <section class="panel monitor-tips"><div class="panel-header"><div><h2>Connection test</h2><p>Send a test message to each saved destination.</p></div></div>
    <div class="notification-state-grid"><div><small>Bot Token</small><strong><?= $hasToken ? 'Configured' : 'Missing' ?></strong></div><div><small>Destinations</small><strong><?= $hasChat ? $destinationCount . ' configured' : 'Missing' ?></strong></div></div>
    <form method="post" action="<?= e(admin_url('notifications')) ?>"><?= \Monitor\Core\Security::csrfField() ?><button class="button primary" type="submit" name="action" value="test" <?= !$destinationReady ? 'disabled' : '' ?>>Send Test Message</button></form>
    <p class="field-help">Save your token and Chat IDs before testing. Testing works even when global notifications are disabled. It does not create an incident.</p>
    </section>
    <section class="panel monitor-tips"><div class="panel-header"><div><h2>Portal Telegram linking</h2><p>Register the Telegram webhook so Portal Users can connect their accounts.</p></div></div>
      <div class="notification-state-grid"><div><small>Bot username</small><strong><?= e(Settings::get('telegram_bot_username') ?: 'Not discovered') ?></strong></div><div><small>Webhook</small><strong><?= Settings::get('telegram_webhook_configured')==='1'?'Configured':'Not configured' ?></strong></div></div>
      <p class="field-help">Requires an HTTPS Base URL and a saved bot token. The webhook secret is generated automatically and never shown.</p>
      <form method="post" action="<?= e(admin_url('notifications')) ?>"><?= \Monitor\Core\Security::csrfField() ?><button class="button primary" name="action" value="configure_webhook" type="submit" <?= !$hasToken?'disabled':'' ?>>Configure Webhook</button></form>
    </section>
    <section class="panel monitor-tips"><h3>Delivery rules</h3><p>Down and Recovery alerts come from scheduled checks only. Repeated failures do not send new alerts. Each destination has its own delivery log and up to three attempts. A failure for one chat does not stop the others.</p><a class="monitor-back-link" href="<?= e(admin_url('system')) ?>">View System Health →</a></section>
  </aside>
</div>
<?php else: ?><section class="panel"><div class="panel-header"><div><h2>Telegram delivery</h2><p>View alert history. Only a Super Admin can change the bot or destinations.</p></div></div><div class="notification-state-grid"><div><small>Channel</small><strong><?= $telegramEnabled ? 'Enabled' : 'Disabled' ?></strong></div><div><small>Bot</small><strong><?= $hasToken ? 'Configured' : 'Missing' ?></strong></div></div></section><?php endif; ?>
<section class="panel notification-log-panel">
<div class="panel-header"><div><h2>Delivery history</h2><p>Telegram incident notifications and delivery attempts.</p></div><span class="pill pill-neutral"><?= (int) $notificationCount ?> records</span></div>
<div class="notification-filters"><?php foreach (['all'=>'All','sent'=>'Sent','failed'=>'Failed','pending'=>'Pending'] as $key=>$label): ?><a class="pill <?= $notificationFilter === $key ? 'pill-purple' : 'pill-neutral' ?>" href="<?= e(admin_url('notifications') . ($key === 'all' ? '' : '?filter=' . $key)) ?>"><?= e($label) ?></a><?php endforeach; ?></div>
<?php if (!$notificationRows): ?><div class="monitor-awaiting">No matching notification deliveries yet. Alerts appear after a confirmed outage or recovery.</div>
<?php else: ?><div class="monitor-table-scroll"><table class="monitor-table"><thead><tr><th>Time (UTC display adjusted)</th><th>Monitor</th><th>Event</th><th>Channel</th><th>Recipient</th><th>Destination</th><th>Status</th><th>Attempts</th><th>Last error</th></tr></thead><tbody>
<?php foreach ($notificationRows as $log): ?><tr>
<td data-label="Time"><?= e(display_date($log['created_at'])) ?></td>
<td data-label="Monitor"><a href="<?= e(admin_url('incidents/view') . '?id=' . (int) $log['incident_id']) ?>"><?= e($log['monitor_name']) ?></a></td>
<td data-label="Event"><?= e(ucfirst($log['event_type'])) ?></td><td data-label="Channel"><?= e(ucfirst($log['channel'])) ?></td>
<td data-label="Recipient"><?= $log['recipient_type']==='portal_user' ? 'Portal User'.($log['recipient_name'] ? ': '.e($log['recipient_name']) : '') : 'Admin / Global' ?></td><td data-label="Destination"><?= $log['recipient_type']==='portal_user' ? 'Private destination' : e($log['destination_chat_id'] ?: 'Unavailable') ?></td>
<td data-label="Status"><span class="pill <?= $log['status'] === 'sent' ? 'pill-success' : ($log['status'] === 'failed' ? 'pill-danger' : 'pill-neutral') ?>"><?= e(ucfirst($log['status'])) ?></span></td>
<td data-label="Attempts"><?= (int) $log['attempt_count'] ?></td><td data-label="Last error" class="notify-error"><?= e($log['error_message'] ?: '—') ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php if ($notificationPages > 1): ?><div class="notify-pagination"><span>Page <?= (int) $notificationPage ?> of <?= (int) $notificationPages ?></span><div><?php if ($notificationPage > 1): ?><a class="button secondary" href="<?= e(admin_url('notifications') . '?filter=' . rawurlencode($notificationFilter) . '&p=' . ($notificationPage - 1)) ?>">Previous</a><?php endif; ?><?php if ($notificationPage < $notificationPages): ?><a class="button secondary" href="<?= e(admin_url('notifications') . '?filter=' . rawurlencode($notificationFilter) . '&p=' . ($notificationPage + 1)) ?>">Next</a><?php endif; ?></div></div><?php endif; ?>
<?php endif; ?>
</section>
