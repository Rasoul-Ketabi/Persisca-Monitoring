<?php
declare(strict_types=1);
// Public Telegram callback: never create an admin or portal session.
define('MONITOR_NO_SESSION', true);
require dirname(__DIR__, 2).'/bootstrap.php';
use Monitor\Models\Settings;
use Monitor\Services\TelegramLinkManager;
if (($_SERVER['REQUEST_METHOD']??'') !== 'POST') {http_response_code(405);header('Allow: POST');exit;}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$secret=Settings::get('telegram_webhook_secret');
$received=(string)($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN']??'');
if ($secret==='' || $received==='' || !hash_equals($secret,$received)) {http_response_code(403);echo '{"ok":false}';exit;}
if (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH']>32768) {http_response_code(413);echo '{"ok":false}';exit;}
$body=file_get_contents('php://input',false,null,0,32769);
if ($body===false || strlen($body)>32768) {http_response_code(413);echo '{"ok":false}';exit;}
$payload=json_decode($body,true,16);
if (!is_array($payload)) {http_response_code(400);echo '{"ok":false}';exit;}
try {TelegramLinkManager::handleUpdate($payload);} catch (Throwable $e) {error_log('Telegram linking webhook failure: '.get_class($e));http_response_code(503);echo '{"ok":false}';exit;}
echo '{"ok":true}';
