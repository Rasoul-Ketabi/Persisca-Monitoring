<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap.php';
use Monitor\Services\PublicStatus;
$settings = array_merge(\Monitor\Models\Settings::defaults(), [
    'app_name'=>'Sample Status', 'company_name'=>'Acme & Sons', 'logo_path'=>'',
    'og_image_path'=>'public/assets/images/status-og-default.jpg','footer_text'=>'<b>not html</b>',
]);
$enabled=true;
$data = [
  'state'=>'pending','active'=>[],'resolved'=>[],'last_checked_at'=>null,
  'monitors'=>[[
    'name'=>'<script>alert(1)</script>', 'is_active'=>1,'operational_status'=>'unknown',
    'last_checked_at'=>null,'last_response_time_ms'=>null, 'history'=>array_values(PublicStatus::days(new DateTimeZone('UTC'),new DateTimeImmutable('2026-10-31T12:00:00Z'))),
    'uptime'=>null,'samples'=>0,'observed_from'=>null,
  ]]
];
[$metaTitle,$metaDescription]=PublicStatus::metadata($data['state'],$settings['app_name']);
$ogTitle=$metaTitle;$ogDescription=$metaDescription;
$canonical='https://example.com/status/';$ogImage='https://example.com/public/assets/images/status-og-default.jpg';
ob_start();require monitor_root().'/app/Views/public/status.php';$html=ob_get_clean();
$tests=[
['Unknown state visible',str_contains($html,'Status Pending')],
['Unsafe monitor name escaped',str_contains($html,'&lt;script&gt;alert(1)&lt;/script&gt;')],
['No raw script tag',!str_contains($html,'<script>alert(1)</script>')],
['Footer text escaped',str_contains($html,'&lt;b&gt;not html&lt;/b&gt;')],
['No false uptime percentage',!str_contains($html,'100% uptime')],
['30 daily history labels',substr_count($html,'class="sp-history-bar')===30],
['Static OG image tag',str_contains($html,'property="og:image"')],
['Canonical status',str_contains($html,'https://example.com/status/')],
['Keyboard focusable history',substr_count($html,'tabindex="0"')===30],
['Response no-data shown',str_contains($html,'No data')],
['No raw URL',!str_contains($html,'internal.example')],
];
$failed=0;foreach($tests as [$label,$pass]){if(!$pass)$failed++;echo ($pass?'PASS':'FAIL').' '.$label.PHP_EOL;}
echo (count($tests)-$failed).'/'.count($tests).' passed'.PHP_EOL;
exit($failed?1:0);
