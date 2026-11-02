<?php
declare(strict_types=1);
namespace Monitor\Services;

/** One saved Telegram setting contains newline-separated destinations. Legacy single IDs remain valid. */
final class TelegramDestinations {
    public const MAX_DESTINATIONS = 10;

    /** @return list<string> Normalized, unique destinations. Reject invalid entries instead of silently ignoring them. */
    public static function parse(string $input): array {
        if (strlen($input) > 1600) { throw new \InvalidArgumentException('Too many Chat IDs. Enter up to 10 destinations.'); }
        $destinations = [];
        $seen = [];
        foreach (preg_split('/\r\n|\n|\r/', $input) ?: [] as $line) {
            $chat = trim($line);
            if ($chat === '') { continue; }
            if (!TelegramNotifier::validChatIdShape($chat)) {
                throw new \InvalidArgumentException('Invalid Telegram Chat ID or @channel username: ' . mb_safe_substr($chat, 40));
            }
            $key = str_starts_with($chat, '@') ? strtolower($chat) : $chat;
            if (isset($seen[$key])) { continue; }
            $seen[$key] = true;
            $destinations[] = $chat;
            if (count($destinations) > self::MAX_DESTINATIONS) {
                throw new \InvalidArgumentException('You can configure up to 10 Telegram destinations.');
            }
        }
        return $destinations;
    }

    public static function normalize(string $input): string {
        return implode("\n", self::parse($input));
    }
}
