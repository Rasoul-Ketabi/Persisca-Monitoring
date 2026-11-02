<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap.php';
use Monitor\Models\NotificationRepository;

$checks = [];
$test = static function (bool $result, string $description) use (&$checks): void {
    $checks[] = [$result, $description];
};
$test(NotificationRepository::normalizeFilter(null) === 'all', 'Missing filter defaults to all');
$test(NotificationRepository::normalizeFilter('') === 'all', 'Empty filter defaults to all');
$test(NotificationRepository::normalizeFilter(['sent']) === 'all', 'Array query input is rejected safely');
$test(NotificationRepository::normalizeFilter('invalid') === 'all', 'Unknown filter defaults to all');
foreach (['all', 'sent', 'failed', 'pending'] as $filter) {
    $test(NotificationRepository::normalizeFilter($filter) === $filter, 'Valid filter: ' . $filter);
}
$method = new ReflectionMethod(NotificationRepository::class, 'where');
foreach (['sent','failed','pending'] as $filter) {
    $condition = $method->invoke(null, $filter);
    $test(str_contains($condition, 'l.status'), 'Joined delivery query uses qualified status for ' . $filter);
}
$dispatch = (string) file_get_contents(dirname(__DIR__) . '/admin/_dispatch.php');
$test(str_contains($dispatch, "NotificationRepository::normalizeFilter(\$_GET['filter'] ?? null)"), 'Admin route safely normalizes missing filter');
$repository = (string) file_get_contents(dirname(__DIR__) . '/app/Models/NotificationRepository.php');
$test(str_contains($repository, 'FROM notification_logs l WHERE '), 'Count query also uses delivery alias');
$test(str_contains($repository, 'JOIN monitors m ON m.id = l.monitor_id'), 'List query retains monitor join');
$passed = 0;
foreach ($checks as [$pass, $description]) {
    echo ($pass ? 'PASS ' : 'FAIL ') . $description . PHP_EOL;
    $passed += (int) $pass;
}
echo $passed . '/' . count($checks) . ' passed' . PHP_EOL;
exit($passed === count($checks) ? 0 : 1);
