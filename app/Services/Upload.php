<?php
declare(strict_types=1);
namespace Monitor\Services;

use RuntimeException;

final class Upload {
    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico'];

    public static function save(array $file, string $kind, string $directory): string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The image could not be uploaded. Maximum file size is 3 MB.');
        }
        if (!is_uploaded_file((string) ($file['tmp_name'] ?? '')) || (int) ($file['size'] ?? 0) > 3145728) {
            throw new RuntimeException('Select an image smaller than 3 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        if (!isset(self::TYPES[$mime])) { throw new RuntimeException('Use a JPG, PNG, WebP, GIF, or ICO image.'); }
        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'ico'];
        if (!in_array($extension, $allowed, true)) { throw new RuntimeException('Unsupported image extension.'); }
        if ($mime === 'image/x-icon' || $mime === 'image/vnd.microsoft.icon') {
            if ($kind !== 'favicon') { throw new RuntimeException('ICO files are only supported for favicons.'); }
        } else {
            $dimensions = @getimagesize((string) $file['tmp_name']);
            if ($dimensions === false || $dimensions[0] < 1 || $dimensions[1] < 1 || $dimensions[0] > 10000 || $dimensions[1] > 10000 || $dimensions[0] * $dimensions[1] > 50000000) {
                throw new RuntimeException('The image is invalid or exceeds the dimension limit.');
            }
        }
        $finalExt = self::TYPES[$mime];
        if (($finalExt === 'jpg' && !in_array($extension, ['jpg', 'jpeg'], true)) || ($finalExt !== 'jpg' && $extension !== $finalExt)) {
            throw new RuntimeException('The image extension does not match its content.');
        }
        if (!is_dir($directory) || !is_writable($directory)) { throw new RuntimeException('The upload directory is not writable.'); }
        $filename = $kind . '-' . bin2hex(random_bytes(12)) . '.' . $finalExt;
        $target = rtrim($directory, '/') . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) { throw new RuntimeException('Could not save the uploaded image.'); }
        @chmod($target, 0644);
        return $filename;
    }
}
