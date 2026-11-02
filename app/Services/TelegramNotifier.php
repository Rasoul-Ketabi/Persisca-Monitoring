<?php
declare(strict_types=1);
namespace Monitor\Services;

use Monitor\Models\Settings;

/** All Bot API communication is isolated here. Never log token-bearing URLs. */
final class TelegramNotifier {
    /** Generic Bot API request for getMe and setWebhook. Response is never logged verbatim. */
    public static function api(string $token, string $method, array $fields = []): array {
        $fail = static fn (string $message): array => ['success'=>false, 'error_message'=>$message, 'data'=>[]];
        if (!self::validTokenShape($token) || !in_array($method,['getMe','setWebhook'],true)) {return $fail('Telegram configuration is invalid.');}
        if (!function_exists('curl_init')) {return $fail('PHP cURL is unavailable.');}
        $curl=curl_init('https://api.telegram.org/bot'.$token.'/'.$method);
        if ($curl===false) {return $fail('Unable to initialize Telegram HTTPS request.');}
        $response='';
        $options=[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($fields,'','&',PHP_QUERY_RFC3986),
            CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded','Accept: application/json'],
            CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>9,
            CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
            CURLOPT_WRITEFUNCTION=>static function($handle,string $part) use (&$response):int {
                if(strlen($response)+strlen($part)>16384) {return 0;}
                $response.=$part;return strlen($part);
            }];
        if (defined('CURLOPT_PROTOCOLS_STR')) {$options[constant('CURLOPT_PROTOCOLS_STR')]='https';}
        elseif (defined('CURLOPT_PROTOCOLS')) {$options[constant('CURLOPT_PROTOCOLS')]=CURLPROTO_HTTPS;}
        curl_setopt_array($curl,$options);
        try {$ok=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);}
        finally {curl_close($curl);}
        if ($ok===false) {return $fail('Telegram HTTPS request failed.');}
        $data=json_decode($response,true);
        if(!is_array($data) || $status<200 || $status>=300 || empty($data['ok'])) {return $fail('Telegram rejected the request. Check bot configuration.');}
        return ['success'=>true,'error_message'=>'','data'=>$data];
    }
    public static function validTokenShape(string $token): bool {
        return strlen($token) <= 256 && (bool) preg_match('/^[0-9]+:[A-Za-z0-9_-]{10,}$/D', $token);
    }
    public static function validChatIdShape(string $chat): bool {
        return strlen($chat) <= 128 && (bool) preg_match('/^(?:-?[0-9]{1,22}|@[A-Za-z][A-Za-z0-9_]{3,})$/D', $chat);
    }
    public static function escape(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    private static function date(?string $utc, string $zone): string {
        if (!$utc) { return 'Unavailable'; }
        try {
            $timezone = new \DateTimeZone($zone);
            return (new \DateTimeImmutable($utc, new \DateTimeZone('UTC')))
                ->setTimezone($timezone)->format('M j, Y H:i T');
        } catch (\Throwable $error) { return 'Unavailable'; }
    }
    public static function message(array $entry, ?string $appName = null, ?string $zone = null): string {
        $appName ??= Settings::get('app_name', 'Monitor');
        $zone ??= Settings::get('timezone', 'UTC');
        $name = self::escape(mb_safe_substr((string) ($entry['monitor_name'] ?? 'Service'), 160));
        $url = self::escape(mb_safe_substr((string) ($entry['monitor_url'] ?? ''), 350));
        $app = self::escape(mb_safe_substr($appName, 120));
        $footer = "\n\n" . $app;
        if ($entry['event_type'] === 'down') {
            $reason = self::escape(mb_safe_substr((string) ($entry['last_error_type'] ?: 'Availability check failed'), 100));
            $attempts = max(1, (int) ($entry['opening_attempts'] ?? 1));
            return "🔴 <b>{$name} is DOWN</b>\n\nURL: {$url}\nStarted: "
                . self::escape(self::date($entry['started_at'], $zone)) . "\nDetected: "
                . self::escape(self::date($entry['detected_at'], $zone)) . "\nReason: {$reason}\nAttempts: {$attempts}" . $footer;
        }
        $duration = duration_label(max(0, (int) ($entry['duration_seconds'] ?? 0)));
        $http = $entry['recovery_http'] === null ? 'Unavailable' : 'HTTP ' . (int) $entry['recovery_http'];
        $time = $entry['recovery_response'] === null ? 'Unavailable' : (int) $entry['recovery_response'] . ' ms';
        return "🟢 <b>{$name} is back online</b>\n\nURL: {$url}\nRecovered: "
            . self::escape(self::date($entry['recovered_at'], $zone)) . "\nDowntime: "
            . self::escape($duration) . "\nLast response: {$http}\nResponse time: {$time}" . $footer;
    }
    /** @return array{success:bool,provider_message_id:?string,error_code:?string,error_message:string} */
    public static function send(string $token, string $chatId, string $text): array {
        $errorResult = static fn (string $reason, ?string $code = null): array => [
            'success' => false, 'provider_message_id' => null, 'error_code' => $code,
            'error_message' => mb_safe_substr($reason, 200),
        ];
        if (!self::validTokenShape($token) || !self::validChatIdShape($chatId)) {
            return $errorResult('Bot Token or Chat ID is not configured correctly.');
        }
        if (!function_exists('curl_init')) { return $errorResult('PHP cURL is unavailable.'); }
        $curl = curl_init('https://api.telegram.org/bot' . $token . '/sendMessage');
        if ($curl === false) { return $errorResult('Unable to initialize HTTPS connection.'); }
        $response = '';
        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['chat_id' => $chatId, 'text' => mb_safe_substr($text, 3800),
                'parse_mode' => 'HTML', 'disable_web_page_preview' => 'true'], '', '&', PHP_QUERY_RFC3986),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 9,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'StatusMonitor/1.0',
            CURLOPT_WRITEFUNCTION => static function ($handle, string $piece) use (&$response): int {
                if (strlen($response) + strlen($piece) > 16384) { return 0; }
                $response .= $piece;
                return strlen($piece);
            },
        ];
        if (defined('CURLOPT_PROTOCOLS_STR')) { $options[constant('CURLOPT_PROTOCOLS_STR')] = 'https'; }
        elseif (defined('CURLOPT_PROTOCOLS')) { $options[constant('CURLOPT_PROTOCOLS')] = CURLPROTO_HTTPS; }
        curl_setopt_array($curl, $options);
        try {
            $sent = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        } finally { curl_close($curl); }
        if ($sent === false) { return $errorResult('Telegram HTTPS request failed. Check TLS and outbound connectivity.'); }
        $body = json_decode($response, true);
        if (!is_array($body)) { return $errorResult('Telegram returned an unexpected response.', (string) $status); }
        if ($status >= 200 && $status < 300 && !empty($body['ok'])) {
            $messageId = $body['result']['message_id'] ?? null;
            return ['success' => true, 'provider_message_id' => is_scalar($messageId) ? (string) $messageId : null,
                'error_code' => null, 'error_message' => ''];
        }
        $code = (string) ($body['error_code'] ?? $status);
        $description = (string) ($body['description'] ?? 'Request was rejected.');
        // A provider may echo request text. Never trust it or persist token-bearing URLs.
        $description = str_replace($token, '[redacted]', $description);
        $description = preg_replace('~https?://\S+~i', '[link]', $description) ?? '';
        $description = preg_replace('/[\x00-\x1F\x7F]/', ' ', $description) ?? '';
        return $errorResult('Telegram returned ' . $code . ': ' . mb_safe_substr($description, 145), $code);
    }
}
