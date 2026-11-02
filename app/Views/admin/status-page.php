<section class="panel settings-panel"><div class="panel-header"><div><h2>Public status page</h2><p>Choose what visitors can see at your public status URL.</p></div><a href="<?= e(status_url()) ?>" class="button secondary" target="_blank" rel="noopener noreferrer">Open status page ↗</a></div>
<div class="settings-form"><div class="form-section"><h3>Publish settings</h3><p>Only monitors marked Public are shown. Private monitors and their incidents stay hidden.</p>
<form method="post" action="<?= e(admin_url('status-page')) ?>">
<?= \Monitor\Core\Security::csrfField() ?>
<div class="monitor-switches">
<?php foreach ([
    'status_page_enabled' => ['Enable public status page','Disable to show a generic unavailable page and HTTP 404.'],
    'status_show_uptime' => ['Show 30-day availability','Display observed check ratios and 30 calendar-day history.'],
    'status_show_response_time' => ['Show response time','Publish the latest measured scheduled response time.'],
    'status_show_incidents' => ['Show incidents','Include active public outages and the latest resolved incidents.'],
    'status_show_footer' => ['Show page footer','Use your configured company name and footer text.'],
] as $key => [$label, $help]): ?>
<label class="monitor-toggle"><input type="checkbox" name="<?= e($key) ?>" value="1" <?= ($settings[$key] ?? '1') === '1' ? 'checked' : '' ?>><span><strong><?= e($label) ?></strong><small><?= e($help) ?></small></span></label>
<?php endforeach; ?></div>
<div class="save-bar"><span>Social sharing metadata follows the current public status. The Open Graph image is managed under Branding.</span><button class="button primary" type="submit">Save status page settings</button></div>
</form></div></div></section>
<section class="panel" class="status-admin-detail"><div class="panel-header"><div><h2>Public page details</h2><p>Your existing branding, timezone, and monitor visibility settings are used automatically.</p></div></div><div class="detail-stats"><div><small>Public URL</small><strong><?= e(absolute_url('status/')) ?></strong></div><div><small>History</small><strong>30 calendar days</strong></div><div><small>Open Graph image</small><strong><?= ($settings['og_image_path'] ?? '') ? 'Configured' : 'Default' ?></strong></div></div></section>
