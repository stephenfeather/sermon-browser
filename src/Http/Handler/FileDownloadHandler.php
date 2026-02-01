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
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $fileSize = @filesize($filePath);
        if ($fileSize !== false && $fileSize > 0) {
            header('Content-Length: ' . $fileSize);
        }
    }
}
