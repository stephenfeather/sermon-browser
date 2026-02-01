<?php

declare(strict_types=1);

namespace SermonBrowser\Http\Handler;

use SermonBrowser\Facades\File;

/**
 * Handles local file show/redirect requests.
 *
 * Redirects the browser to the file's public URL without forcing download.
 * Files must exist in the database to be served (path traversal protection).
 *
 * @since 0.6.0
 */
class FileRedirectHandler
{
    use HandlerTrait;

    /**
     * Handle a file redirect request.
     *
     * Validates the file exists in database, increments download count,
     * and redirects to the file's public URL.
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
        $url = sb_get_option('upload_url') . $fileName;

        self::incrementDownloadCount($fileName);

        header('Location: ' . $url);
        exit;
    }
}
