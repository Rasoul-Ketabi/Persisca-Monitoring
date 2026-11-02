<?php
declare(strict_types=1);
namespace Monitor\Services;

use InvalidArgumentException;

final class MonitorInput {
    public static function url(string $input): string {
        $url = trim($input);
        if ($url === '' || strlen($url) > 2048 || preg_match('/[\x00-\x20\x7F\\\\]/', $url)) {
            throw new InvalidArgumentException('Enter a valid HTTP or HTTPS URL.');
        }
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host']) ||
            !in_array(strtolower($parts['scheme']), ['http', 'https'], true) ||
            isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException('Enter a valid HTTP or HTTPS URL without credentials or a fragment.');
        }
        $host = (string) $parts['host'];
        if ($host === '' || str_contains($host, '%') || str_contains($host, '@') ||
            (!filter_var($host, FILTER_VALIDATE_IP) &&
                !filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) &&
                !preg_match('/^(?=.{1,253}$)[a-zA-Z0-9](?:[a-zA-Z0-9.-]*[a-zA-Z0-9])?$/D', $host))) {
            throw new InvalidArgumentException('Enter a valid HTTP or HTTPS hostname.');
        }
        // Avoid hostnames with consecutive dots or ambiguous numeric forms.
        if (str_contains($host, '..') || (preg_match('/^[0-9.]+$/', $host) && !filter_var($host, FILTER_VALIDATE_IP))) {
            throw new InvalidArgumentException('Enter a valid HTTP or HTTPS hostname.');
        }
        if (isset($parts['port']) && ($parts['port'] < 1 || $parts['port'] > 65535)) {
            throw new InvalidArgumentException('Enter a valid URL port.');
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Enter a valid HTTP or HTTPS URL.');
        }
        return $url;
    }

    public static function codes(string $input): string {
        $raw = trim($input);
        if ($raw === '' || strlen($raw) > 255) {
            throw new InvalidArgumentException('Expected status codes are invalid. Enter codes such as 200,204,301.');
        }
        $codes = [];
        foreach (explode(',', $raw) as $part) {
            $part = trim($part);
            if (!preg_match('/^[1-5][0-9]{2}$/D', $part)) {
                throw new InvalidArgumentException('Expected status codes are invalid. Use comma-separated codes from 100 to 599.');
            }
            $codes[(int) $part] = (int) $part;
        }
        if (count($codes) > 25) { throw new InvalidArgumentException('Enter no more than 25 expected status codes.'); }
        sort($codes);
        return implode(',', $codes);
    }

    public static function fromPost(array $post): array {
        $name = trim((string) ($post['name'] ?? ''));
        if ($name === '' || strlen($name) > 160) {
            throw new InvalidArgumentException('Monitor name is required (maximum 160 characters).');
        }
        $interval = self::integer($post, 'check_interval_minutes', [1, 2, 5, 10, 15, 30, 60], 'Choose a supported check interval.');
        $timeout = self::integerRange($post, 'timeout_seconds', 1, 60, 'Timeout must be between 1 and 60 seconds.');
        $retries = self::integerRange($post, 'retry_count', 0, 5, 'Retry count must be between 0 and 5.');
        $delay = self::integerRange($post, 'retry_delay_seconds', 0, 30, 'Retry delay must be between 0 and 30 seconds.');
        $failureThreshold = self::integerRange($post + ['failure_threshold' => '2'], 'failure_threshold', 1, 5, 'Failure confirmation must be between 1 and 5 checks.');
        return [
            'name' => $name,
            'url' => self::url((string) ($post['url'] ?? '')),
            'is_active' => isset($post['is_active']) ? 1 : 0,
            'is_public' => isset($post['is_public']) ? 1 : 0,
            'allow_private_network' => isset($post['allow_private_network']) ? 1 : 0,
            'check_interval_minutes' => $interval,
            'timeout_seconds' => $timeout,
            'expected_status_codes' => self::codes((string) ($post['expected_status_codes'] ?? '')),
            'retry_count' => $retries,
            'retry_delay_seconds' => $delay,
            'failure_threshold' => $failureThreshold,
            'telegram_notifications_enabled' => isset($post['telegram_notifications_enabled']) ? 1 : 0,
        ];
    }

    private static function integerRange(array $post, string $key, int $min, int $max, string $error): int {
        $value = (string) ($post[$key] ?? '');
        if (!preg_match('/^[0-9]{1,3}$/D', $value) || (int) $value < $min || (int) $value > $max) {
            throw new InvalidArgumentException($error);
        }
        return (int) $value;
    }
    private static function integer(array $post, string $key, array $options, string $error): int {
        $value = (string) ($post[$key] ?? '');
        if (!preg_match('/^[0-9]{1,3}$/D', $value) || !in_array((int) $value, $options, true)) {
            throw new InvalidArgumentException($error);
        }
        return (int) $value;
    }
}
