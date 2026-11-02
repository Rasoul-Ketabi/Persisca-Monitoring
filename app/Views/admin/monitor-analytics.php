<?php
$id = (int)$editingMonitor['id'];
$current = $analytics;
$points = $analyticsSeries;
$rangeNames = ['24h'=>'Last 24 hours','7d'=>'Last 7 days','30d'=>'Last 30 days'];
$line = '';
$min = 0;
$max = 0;
if (count($points) >= 2) {
    $values = array_map(static fn($p) => (float)$p['response_ms'], $points);
    $min = min($values); $max = max($values);
    $low = max(0, $min - max(10, ($max - $min) * .15));
    $high = $max + max(10, ($max - $min) * .15);
    $span = max(1, $high - $low);
    $coordinates = [];
    foreach ($points as $i => $point) {
        $x = 32 + 920 * $i / (count($points)-1);
        $y = 212 - 176 * (((float)$point['response_ms'] - $low) / $span);
        $coordinates[] = number_format($x, 2,'.','').','.number_format($y,2,'.','');
    }
    $line = implode(' ', $coordinates);
}
?>
<div class="monitor-analytics-shortcut"><a href="<?= e(admin_url('monitors/view').'?id='.$id) ?>">← Monitor details</a><span><?= e($editingMonitor['name']) ?> · <?= e(monitor_state($editingMonitor)) ?></span></div>
<section class="panel analytics-summary-panel">
<div class="panel-header"><div><h2>Availability and response analytics</h2><p>Only actual completed scheduled checks contribute to availability. Paused and unchecked periods are not assumed to be online.</p></div></div>
<div class="analytics-range-tabs" role="navigation" aria-label="Analytics date range">
<?php foreach ($rangeNames as $key=>$name): ?><a class="<?= $analyticsRange===$key?'selected':'' ?>" href="<?= e(admin_url('monitors/analytics').'?id='.$id.'&range='.$key) ?>"><?= e($name) ?></a><?php endforeach; ?>
</div>
<div class="analytics-metric-grid">
<div><small>Observed check availability</small><strong><?= e(uptime_label($current['uptime_percentage'])) ?></strong><span><?= (int)$current['successful_checks'] ?> successful of <?= (int)$current['check_count'] ?> checks</span></div>
<div><small>Average HTTP response</small><strong><?= e(response_label($current['avg_response_ms'])) ?></strong><span><?= (int)$current['response_samples'] ?> measured responses</span></div>
<div><small>Fastest / slowest</small><strong><?= e(response_label($current['min_response_ms'])) ?> / <?= e(response_label($current['max_response_ms'])) ?></strong><span>Measured HTTP requests</span></div>
<div><small>Confirmed incident downtime</small><strong><?= e(duration_label($current['downtime_seconds'])) ?></strong><span>Overlapping confirmed incidents, separate from sampled uptime</span></div>
</div>
<?php if (!$current['check_count']): ?><div class="monitor-awaiting">No scheduled checks were recorded in this period. Configure cron or choose a longer range.</div><?php endif; ?>
<p class="analytics-disclaimer">Sample uptime = successful scheduled checks ÷ total scheduled checks. It is not a continuous-time SLA. No data is treated as unknown, not 100% uptime. Existing history is limited to the date monitoring actually began.</p>
</section>
<section class="panel analytics-chart-panel"><div class="panel-header"><div><h2>Response-time trend</h2><p><?= e($rangeNames[$analyticsRange]) ?>. Recorded request duration in milliseconds, grouped into time buckets.</p></div><span class="pill pill-neutral"><?= count($points) ?> time buckets</span></div>
<?php if (count($points) < 2): ?><div class="monitor-awaiting analytics-empty-chart">Not enough response-time data to draw this chart yet.</div>
<?php else: ?><div class="analytics-chart-content" role="img" aria-label="Measured response time trend from <?= e(display_date($points[0]['time'])) ?> to <?= e(display_date($points[count($points)-1]['time'])) ?>, minimum <?= e(response_label($min)) ?>, maximum <?= e(response_label($max)) ?>">
<svg viewBox="0 0 990 240" preserveAspectRatio="xMidYMid meet" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
<line x1="32" y1="36" x2="952" y2="36" stroke="#e5eaf4" stroke-dasharray="6 7"/><line x1="32" y1="124" x2="952" y2="124" stroke="#e5eaf4" stroke-dasharray="6 7"/><line x1="32" y1="212" x2="952" y2="212" stroke="#e5eaf4"/>
<polyline points="<?= e($line) ?>" fill="none" stroke="#536dfe" stroke-width="3.5" stroke-linejoin="round" stroke-linecap="round"/>
</svg><div class="analytics-chart-labels"><small><?= e(display_date($points[0]['time'],'M j, g:i A')) ?></small><small><?= e(display_date($points[count($points)-1]['time'],'M j, g:i A')) ?></small></div></div>
<?php endif; ?></section>
<section class="panel analytics-period-panel"><div class="panel-header"><div><h2>Period comparison</h2><p>Calculated from real checks within each time window.</p></div></div><div class="analytics-period-rows">
<?php foreach ($analyticsAll as $key=>$summary): ?><a href="<?= e(admin_url('monitors/analytics').'?id='.$id.'&range='.$key) ?>"><span><?= e($rangeNames[$key]) ?></span><strong><?= e(uptime_label($summary['uptime_percentage'])) ?></strong><small><?= (int)$summary['check_count'] ?> checks</small><span><?= e(response_label($summary['avg_response_ms'])) ?></span></a><?php endforeach; ?>
</div><div class="analytics-disclaimer">First scheduled check in selected period: <?= e(display_date($current['first_observed_at'])) ?>. Historical pause durations before this release cannot be reconstructed, so uptime is presented as observed check availability. Hourly aggregates are kept after raw checks are removed.</div></section>
