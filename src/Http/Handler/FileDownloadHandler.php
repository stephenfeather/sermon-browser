<?php

declare(strict_types=1);

namespace SermonBrowser\Http\Handler;

use SermonBrowser\Core\HelperFunctions;
use SermonBrowser\Facades\File;

/**
 * Handles local file download requests.
 *
 * Forces browser download of files stored in the upload directory.
 * Files must exist in the database to be served (path traversal protection).
 *
 * @since 0.6.0
 */
class FileDownloadHandler
{
    use HandlerTrait;

    /**
     * Handle a file download request.
     *
     * Validates the file exists in database, sets appropriate headers,
     * and streams the file content.
     *
     * @return never
     */
    public static function handle(): void
    {
        $requestedName = rawurldecode($_GET['file_name'] ?? '');
        $file = File::findOneBy('name', $requestedName);

        if ($file === null) {
            self::notFound(esc_html($requestedName));
        }

        $fileName = $file->name;
        $filePath = SB_ABSPATH . sb_get_option('upload_dir') . $fileName;

        self::sendHeaders($fileName, $filePath);
        self::incrementDownloadCount($fileName);

        HelperFunctions::outputFile($filePath);
        exit;
    }

    /**
     * Send appropriate HTTP headers for file download.
     *
     * @param string $fileName The file name for Content-Disposition.
     * @param string $filePath The full path to the file.
     */
    private static function sendHeaders(string $fileName, string $filePath): void
    {
        header('Content-Type: application/octet-stream');

        // Sanitize filename for Content-Disposition header to prevent header injection.
        $safeFileName = self::sanitizeFilenameForHeader($fileName);
        header('Content-Disposition: attachment; ' . $safeFileName);

        $fileSize = @filesize($filePath);
        if ($fileSize !== false && $fileSize > 0) {
            header('Content-Length: ' . $fileSize);
        }
    }

    /**
     * Sanitize a filename for use in Content-Disposition header.
     *
     * Prevents header injection by sanitizing the filename and using
     * RFC 5987 encoding for non-ASCII characters.
     *
     * @param string $fileName The original filename.
     * @return string The sanitized filename parameter for Content-Disposition.
     */
    private static function sanitizeFilenameForHeader(string $fileName): string
    {
        // Remove any characters that could be used for header injection.
        $sanitized = preg_replace('/[\r\n\t]/', '', $fileName);

        // ASCII-only filename (fallback for old clients).
        $asciiName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $sanitized);

        // RFC 5987 encoded filename for modern clients (supports UTF-8).
        $encodedName = rawurlencode($sanitized);

        // Return both for maximum compatibility.
        return 'filename="' . $asciiName . '"; filename*=UTF-8\'\'' . $encodedName;
    }
}
