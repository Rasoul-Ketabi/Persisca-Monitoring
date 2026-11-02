<?php
/** @var array $data */
[$heading, $overviewDescription] = \Monitor\Services\PublicStatus::labels($data['state']);
$activeIncidents = $data['active'];
$publicMonitors = $data['monitors'];
$historyEnabled = ($settings['status_show_uptime'] ?? '1') === '1';
$incidentEnabled = ($settings['status_show_incidents'] ?? '1') === '1';
$responseEnabled = ($settings['status_show_response_time'] ?? '1') === '1';
$footerEnabled = ($settings['status_show_footer'] ?? '1') === '1';
$statusClass = in_array($data['state'], ['major','issues'], true) ? 'issue' : $data['state'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($metaTitle) ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">
<meta name="robots" content="<?= $enabled ? 'index,follow' : 'noindex,nofollow' ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:type" content="website"><meta property="og:title" content="<?= e($ogTitle) ?>"><meta property="og:description" content="<?= e($ogDescription) ?>"><meta property="og:url" content="<?= e($canonical) ?>"><meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="<?= e($ogTitle) ?>"><meta name="twitter:description" content="<?= e($ogDescription) ?>"><meta name="twitter:image" content="<?= e($ogImage) ?>">
<?php if ($settings['favicon_path']): ?><link rel="icon" href="<?= e(url($settings['favicon_path'])) ?>"><?php endif; ?>
<link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
</head>
<body class="status-public-v7">
<div class="sp-shell">
<header class="sp-header"><a href="<?= e($brandLink ?? status_url()) ?>" class="sp-brand" aria-label="Status home">
<?php if ($settings['logo_path']): ?><img src="<?= e(url($settings['logo_path'])) ?>" alt="<?= e($settings['app_name']) ?> logo">
<?php else: ?><span class="sp-monogram" aria-hidden="true"><?= e(strtoupper(mb_safe_substr((string)$settings['app_name'],1))) ?></span><?php endif; ?>
<span><?= e($settings['app_name']) ?></span></a>
<div class="sp-header-links"><span class="sp-header-caption">Service status</span><?php if ($settings['support_url']): ?><a href="<?= e($settings['support_url']) ?>" target="_blank" rel="noopener noreferrer">Get support <span aria-hidden="true">↗</span></a><?php endif; ?></div></header>
<main id="main-content" class="sp-main">
<?php if (!$enabled): ?>
<section class="sp-hero pending"><div class="sp-hero-icon" aria-hidden="true">○</div><h1>Status page unavailable</h1><p>This status page is not available.</p></section>
<?php else: ?>
<div class="sp-intro"><div><div class="sp-eyebrow"><span class="sp-live-dot" aria-hidden="true"></span> SERVICE HEALTH</div><h1><?= e($pageHeading ?? 'System status') ?></h1><p><?= e($pageIntro ?? 'Availability and incident updates for our services.') ?></p></div><div class="sp-last-updated"><span>Latest scheduled check</span><strong><?= $data['last_checked_at'] ? e(display_date($data['last_checked_at'], 'M j, Y · g:i A')) : 'Awaiting first check' ?></strong></div></div>
<section class="sp-hero <?= e($statusClass) ?>" aria-label="Overall service status"><div class="sp-hero-main"><span class="sp-status-symbol" aria-hidden="true"><?= in_array($statusClass,['issue'],true) ? '!' : ($statusClass === 'operational' ? '✓' : '○') ?></span><div><span class="sp-hero-caption">CURRENT STATUS</span><h2><?= e($heading) ?></h2><p><?= e($overviewDescription) ?></p></div></div><span class="sp-summary-tag"><?= count($publicMonitors) ?> public <?= count($publicMonitors) === 1 ? 'service' : 'services' ?></span></section>
<?php if ($incidentEnabled && $activeIncidents): ?>
<section class="sp-section"><div class="sp-section-head"><div><span class="sp-kicker">CURRENT EVENTS</span><h2>Active incidents</h2></div><span class="sp-count"><?= count($activeIncidents) ?> ongoing</span></div>
<div class="sp-incident-list"><?php foreach ($activeIncidents as $incident): ?>
<article class="sp-incident-card"><div class="sp-incident-mark" aria-hidden="true">!</div><div><div class="sp-incident-top"><strong><?= e($incident['monitor_name']) ?></strong><span class="sp-chip issue">Investigating</span></div><p>Availability issue under investigation.</p><div class="sp-incident-meta"><span>First detected: <?= e(display_date($incident['started_at'], 'M j, Y · g:i A')) ?></span><span>Duration: <?= e(incident_duration($incident)) ?></span></div></div></article>
<?php endforeach; ?></div></section>
<?php endif; ?>
<section class="sp-section" id="services"><div class="sp-section-head"><div><span class="sp-kicker">MONITORING</span><h2>Services</h2></div><span class="sp-count"><?= count($publicMonitors) ?> tracked</span></div>
<?php if (!$publicMonitors): ?><div class="sp-empty"><span aria-hidden="true">◌</span><h3>No public services configured</h3><p>Once services are published, their status will appear here.</p></div>
<?php else: ?><div class="sp-services">
<?php foreach ($publicMonitors as $monitor):
[$monitorStatus,$monitorTone] = \Monitor\Services\PublicStatus::monitorLabel($monitor);
$uptimeText = $monitor['uptime'] === null ? 'Not enough data' : uptime_label((float)$monitor['uptime']);
$days = $monitor['history'];
$firstDate = $days[0]['date'] ?? '';
$lastDate = $days[count($days)-1]['date'] ?? '';
?>
<article class="sp-service-card"><div class="sp-service-top"><div class="sp-service-name"><span class="sp-service-dot <?= e($monitorTone) ?>" aria-hidden="true"></span><div><h3><?= e($monitor['name']) ?></h3><span class="sp-service-sub">Public service</span></div></div><span class="sp-chip <?= e($monitorTone) ?>"><?= e($monitorStatus) ?></span></div>
<div class="sp-service-numbers">
<?php if ($historyEnabled): ?><div><span>Observed check success, last 30 days</span><strong><?= e($uptimeText) ?></strong><small><?= (int)$monitor['samples'] ?> scheduled checks<?= $monitor['samples'] > 0 && $monitor['observed_from'] ? ' since ' . e(display_date($monitor['observed_from'], 'M j')) : '' ?></small></div><?php endif; ?>
<div><span>Last scheduled check</span><strong class="sp-smaller"><?= e(display_date($monitor['last_checked_at'], 'M j · g:i A')) ?></strong><small><?= $monitor['last_checked_at'] ? 'Recorded result' : 'No data yet' ?></small></div>
<?php if ($responseEnabled): ?><div><span>Last response time</span><strong class="sp-smaller"><?= $monitor['last_checked_at'] ? e(response_label($monitor['last_response_time_ms'] === null ? null : (float)$monitor['last_response_time_ms'])) : 'No data' ?></strong><small>Latest scheduled request</small></div><?php endif; ?>
</div>
<?php if ($historyEnabled): ?><div class="sp-history-head"><strong>30-day availability</strong><span>Each bar represents one local calendar day</span></div><div class="sp-history-grid" role="group" aria-label="30-day availability for <?= e($monitor['name']) ?>">
<?php foreach ($days as $day):
$dayText = (new DateTimeImmutable($day['date']))->format('M j');
$label = $dayText . ': ' . ($day['checks'] ? uptime_label($day['uptime_percentage']).' successful checks ('.$day['successes'].' of '.$day['checks'].')' : 'No scheduled checks');
if ($day['downtime_seconds']) { $label .= ', confirmed downtime ' . duration_label($day['downtime_seconds']); }
if ($day['approximate']) { $label .= ', UTC hourly aggregation used'; }
?>
<span class="sp-history-bar <?= e($day['state']) ?>" tabindex="0" role="img" aria-label="<?= e($label) ?>" title="<?= e($label) ?>"><span class="sp-bar-popup" aria-hidden="true"><?= e($label) ?></span></span>
<?php endforeach; ?></div><div class="sp-history-foot"><span><?= e($firstDate ? (new DateTimeImmutable($firstDate))->format('M j') : '') ?></span><span>Observed checks only · Unobserved days are not uptime</span><span><?= e($lastDate ? (new DateTimeImmutable($lastDate))->format('M j') : '') ?></span></div>
<?php endif; ?></article>
<?php endforeach; ?></div><?php endif; ?></section>
<?php if ($incidentEnabled): ?><section class="sp-section sp-recent"><div class="sp-section-head"><div><span class="sp-kicker">HISTORY</span><h2>Recent incidents</h2></div><span class="sp-count">Last 30 days</span></div>
<?php if (!$data['resolved']): ?><div class="sp-no-incidents"><span class="sp-check-icon" aria-hidden="true">✓</span><div><strong>No resolved incidents in this period</strong><p>Past incidents will be listed here when available.</p></div></div>
<?php else: ?><div class="sp-resolved-list"><?php foreach ($data['resolved'] as $incident): ?><article class="sp-resolved-item"><div class="sp-check-icon" aria-hidden="true">✓</div><div class="sp-resolved-name"><strong><?= e($incident['monitor_name']) ?></strong><span><?= e(display_date($incident['started_at'], 'M j, Y · g:i A')) ?> to <?= e(display_date($incident['recovered_at'], 'M j, Y · g:i A')) ?></span></div><div class="sp-resolved-end"><span class="sp-chip operational">Resolved</span><small><?= e(incident_duration($incident)) ?></small></div></article><?php endforeach; ?></div><?php endif; ?>
</section><?php endif; ?>
<div class="sp-explain"><span aria-hidden="true">ⓘ</span><p>Status comes from scheduled checks. Availability percentages describe observed check samples, not continuous uptime. Data gaps and monitoring pauses are not assumed to be healthy. Refresh this page for the latest results.</p></div>
<?php endif; ?></main>
<?php if ($footerEnabled): ?><footer class="sp-footer"><span>© <?= date('Y') ?> <?= e($settings['company_name'] ?: $settings['app_name']) ?><?= $settings['footer_text'] ? ' · ' . e($settings['footer_text']) : '' ?></span><span>Service status</span></footer><?php endif; ?>
</div></body></html>
