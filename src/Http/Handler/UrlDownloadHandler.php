<?php

declare(strict_types=1);

namespace SermonBrowser\Http\Handler;

use SermonBrowser\Facades\File;

/**
 * Handles external URL download requests.
 *
 * Downloads content from external URLs and serves as an attachment.
 * SECURITY: Only URLs registered in the database can be downloaded (SSRF protection).
 *
 * @since 0.6.0
 */
class UrlDownloadHandler
{
    use HandlerTrait;

    /**
     * Handle a URL download request.
     *
     * Validates the URL exists in database, downloads the file,
     * and serves it as an attachment.
     *
     * @return never
     */
    public static function handle(): void
    {
        $requestedUrl = rawurldecode($_REQUEST['url'] ?? '');

        // SECURITY: Validate URL exists in database (SSRF protection)
        $file = File::findOneBy('name', $requestedUrl);
        if ($file === null || $file->type !== 'url') {
            self::urlNotFound(esc_html__('Invalid or unregistered URL.', 'sermon-browser'));
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $downloadedFile = download_url($file->name);

        if (is_wp_error($downloadedFile)) {
            self::urlNotFound(esc_html($requestedUrl));
        }

        self::sendHeaders($file->name, $downloadedFile);
        self::incrementDownloadCount($file->name);

        self::outputFile($downloadedFile);
        self::cleanup($downloadedFile);
        exit;
    }

    /**
     * Send appropriate HTTP headers for URL download.
     *
     * @param string $url           The original URL.
     * @param string $downloadedFile Path to the downloaded temp file.
     */
    private static function sendHeaders(string $url, string $downloadedFile): void
    {
        $mimeType = mime_content_type($downloadedFile);
        if ($mimeType === false) {
            $mimeType = 'application/octet-stream';
        }

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . basename($url) . '"');

        $fileSize = @filesize($downloadedFile);
        if ($fileSize !== false && $fileSize > 0) {
            header('Content-Length: ' . $fileSize);
        }
    }

    /**
     * Output the downloaded file content.
     *
     * @param string $filePath Path to the file.
     */
    private static function outputFile(string $filePath): void
    {
        readfile($filePath);
    }

    /**
     * Clean up the temporary downloaded file.
     *
     * @param string $filePath Path to the temporary file.
     */
    private static function cleanup(string $filePath): void
    {
        @unlink($filePath);
    }
}
