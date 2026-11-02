<?php
declare(strict_types=1);
namespace Monitor\Services;

use RuntimeException;
use Throwable;

/** Reusable GET checker shared by manual diagnostics and scheduled cron checks. */
final class HttpMonitor {
    private const MAX_REDIRECTS = 5;

    public static function check(array $monitor): array {
        $retries = min(5, max(0, (int) ($monitor['retry_count'] ?? 0)));
        $delay = min(30, max(0, (int) ($monitor['retry_delay_seconds'] ?? 0)));
        $accepted = array_map('intval', explode(',', (string) ($monitor['expected_status_codes'] ?? '200')));
        $attempts = 0;
        $result = [];
        for ($index = 0; $index <= $retries; $index++) {
            if ($index > 0 && $delay > 0) { sleep($delay); }
            $attempts++;
            $result = self::singleAttempt(
                (string) $monitor['url'],
                (int) ($monitor['timeout_seconds'] ?? 10),
                $accepted,
                !empty($monitor['allow_private_network'])
            );
            if ($result['success']) { break; }
        }
        $result['attempts'] = $attempts;
        $result['checked_at'] = gmdate('Y-m-d H:i:s');
        return $result;
    }

    private static function singleAttempt(string $originalUrl, int $timeout, array $accepted, bool $allowPrivate): array {
        $url = $originalUrl;
        $totalMilliseconds = 0;
        if (!function_exists('curl_init')) {
            return self::failure('Environment Error', 'The PHP cURL extension is not available.', 0, '', 0);
        }
        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            try {
                $url = MonitorInput::url($url);
                $resolved = self::resolve($url, $allowPrivate);
            } catch (Throwable $exception) {
                $type = $exception instanceof \InvalidArgumentException ? 'Target Policy Error' : 'DNS Error';
                return self::failure($type, $exception->getMessage(), 0, $url, $totalMilliseconds);
            }
            $location = null;
            $handle = curl_init($url);
            if ($handle === false) {
                return self::failure('Environment Error', 'Could not initialize cURL.', 0, $url, $totalMilliseconds);
            }
            $options = [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTPGET => true,
                CURLOPT_NOBODY => false,
                CURLOPT_TIMEOUT => min(60, max(1, $timeout)),
                CURLOPT_CONNECTTIMEOUT => min(10, max(1, $timeout)),
                CURLOPT_USERAGENT => 'HTTP-Monitor/1.0 (+availability-check)',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_PROXY => '',
                CURLOPT_NOPROXY => '*',
                CURLOPT_MAXREDIRS => 0,
                CURLOPT_WRITEFUNCTION => static fn ($curl, string $chunk): int => strlen($chunk),
                CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$location): int {
                    if (stripos($line, 'Location:') === 0) { $location = trim(substr($line, 9)); }
                    return strlen($line);
                },
                CURLOPT_RESOLVE => $resolved,
            ];
            if (defined('CURLOPT_PROTOCOLS')) { $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS; }
            curl_setopt_array($handle, $options);
            $ok = curl_exec($handle);
            $errno = curl_errno($handle);
            $error = curl_error($handle);
            $http = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $time = (int) round((float) curl_getinfo($handle, CURLINFO_TOTAL_TIME) * 1000);
            $totalMilliseconds += $time;
            $effective = (string) (curl_getinfo($handle, CURLINFO_EFFECTIVE_URL) ?: $url);
            $connectTime = (float) curl_getinfo($handle, CURLINFO_CONNECT_TIME);
            curl_close($handle);
            if ($ok === false) {
                return self::failure(self::classifyCurl($errno, $connectTime), self::safeCurlMessage($error), $http, $effective, $totalMilliseconds);
            }
            if (in_array($http, [301, 302, 303, 307, 308], true) && $location) {
                if ($redirects === self::MAX_REDIRECTS) {
                    return self::failure('Redirect Error', 'The endpoint redirected too many times.', $http, $effective, $totalMilliseconds);
                }
                try { $url = self::redirectUrl($effective, $location); }
                catch (Throwable $exception) { return self::failure('Redirect Error', $exception->getMessage(), $http, $effective, $totalMilliseconds); }
                continue;
            }
            if (!in_array($http, $accepted, true)) {
                return self::failure('HTTP Status Error', 'Received HTTP ' . $http . '; expected ' . implode(', ', $accepted) . '.', $http, $effective, $totalMilliseconds);
            }
            return [
                'success' => true, 'http_status' => $http, 'response_time_ms' => $totalMilliseconds,
                'effective_url' => $effective, 'error_type' => '', 'error_message' => '',
            ];
        }
        return self::failure('Redirect Error', 'The endpoint redirected too many times.', 0, $url, $totalMilliseconds);
    }

    /** Pin resolved IP addresses in cURL so DNS rebinding cannot bypass the policy check. */
    private static function resolve(string $url, bool $allowPrivate): array {
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['host'])) { throw new \InvalidArgumentException('Invalid host.'); }
        $host = trim((string) $parts['host'], '[]');
        $port = (int) ($parts['port'] ?? (strtolower((string) $parts['scheme']) === 'https' ? 443 : 80));
        $ips = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } elseif (strcasecmp($host, 'localhost') === 0) {
            $ips = ['127.0.0.1'];
        } else {
            if (function_exists('dns_get_record')) {
                $records = @dns_get_record($host, DNS_A | DNS_AAAA);
                foreach (($records ?: []) as $record) {
                    if (!empty($record['ip'])) { $ips[] = $record['ip']; }
                    if (!empty($record['ipv6'])) { $ips[] = $record['ipv6']; }
                }
            }
            if (!$ips && function_exists('gethostbynamel')) { $ips = @gethostbynamel($host) ?: []; }
        }
        $ips = array_values(array_unique(array_filter($ips, static fn ($ip) => filter_var($ip, FILTER_VALIDATE_IP))));
        if (!$ips) { throw new RuntimeException('The hostname could not be resolved.'); }
        if (!$allowPrivate) {
            foreach ($ips as $ip) {
                if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    throw new \InvalidArgumentException('The target resolves to a private or reserved address. Enable private-network access for this monitor only if you trust that target.');
                }
            }
        }
        // IPv6 resolver addresses need brackets in the CURLOPT_RESOLVE address syntax.
        $address = str_contains($ips[0], ':') ? '[' . $ips[0] . ']' : $ips[0];
        $hostKey = str_contains($host, ':') ? '[' . $host . ']' : $host;
        return [$hostKey . ':' . $port . ':' . $address];
    }

    private static function redirectUrl(string $base, string $location): string {
        $location = trim($location);
        if ($location === '' || preg_match('/[\x00-\x1F\x7F\\\\]/', $location)) {
            throw new RuntimeException('Invalid redirect target.');
        }
        $location = explode('#', $location, 2)[0];
        $baseParts = parse_url($base);
        if (!is_array($baseParts) || !isset($baseParts['scheme'], $baseParts['host'])) {
            throw new RuntimeException('Invalid redirect source.');
        }
        $origin = $baseParts['scheme'] . '://' . $baseParts['host'] . (isset($baseParts['port']) ? ':' . $baseParts['port'] : '');
        if (str_starts_with($location, '//')) { $target = $baseParts['scheme'] . ':' . $location; }
        elseif (preg_match('~^[a-z][a-z0-9+.-]*:~i', $location)) { $target = $location; }
        elseif (str_starts_with($location, '/')) { $target = $origin . $location; }
        elseif (str_starts_with($location, '?')) { $target = $origin . ($baseParts['path'] ?? '/') . $location; }
        else {
            $path = $baseParts['path'] ?? '/';
            $target = $origin . substr($path, 0, (int) strrpos($path, '/') + 1) . $location;
        }
        return MonitorInput::url($target);
    }

    private static function failure(string $type, string $message, int $status, string $effective, int $elapsed): array {
        return [
            'success' => false, 'http_status' => $status ?: null,
            'response_time_ms' => max(0, $elapsed), 'effective_url' => $effective,
            'error_type' => $type, 'error_message' => $message,
        ];
    }
    private static function safeCurlMessage(string $error): string {
        // Avoid persisting local filesystem paths or request credentials from cURL errors.
        $error = preg_replace('~(?:/(?:home|etc|var|opt|usr|private)/)[^\s]+~', '[server path]', $error);
        return substr((string) $error, 0, 500) ?: 'The HTTP request failed.';
    }
    private static function classifyCurl(int $errno, float $connectTime): string {
        return match ($errno) {
            6 => 'DNS Error',
            7 => 'Connection Failed',
            28 => $connectTime === 0.0 ? 'Connection Timeout' : 'Request Timeout',
            35, 51, 58, 60, 77, 83, 90 => 'SSL Error',
            47 => 'Redirect Error',
            default => 'Unknown Error',
        };
    }
}
