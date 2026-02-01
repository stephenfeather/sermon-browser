<?php

/**
 * File Sync Handler.
 *
 * Handles scanning the upload directory for new files uploaded via FTP.
 *
 * @package SermonBrowser\Admin
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin;

use SermonBrowser\Facades\File;

/**
 * Class FileSync
 *
 * Synchronizes the database with files in the upload directory.
 */
class FileSync
{
    /**
     * Scan upload directory for new files uploaded via FTP.
     *
     * Removes database entries for files that no longer exist on disk,
     * and creates entries for new files found in the directory.
     *
     * @return void
     */
    public static function sync(): void
    {
        File::deleteEmptyUnlinked();
        $fileNames = File::findAllFileNames();
        $dir = SB_ABSPATH . sb_get_option('upload_dir');

        foreach ($fileNames as $fileName) {
            if (!file_exists($dir . $fileName)) {
                File::deleteUnlinkedByName($fileName);
            }
        }

        $dh = @opendir($dir);
        if ($dh) {
            while (false !== ($file = readdir($dh))) {
                if ($file != "." && $file != ".." && !is_dir($dir . $file) && !in_array($file, $fileNames)) {
                    File::create(['type' => 'file', 'name' => $file, 'sermon_id' => 0, 'count' => 0, 'duration' => 0]);
                }
            }
            closedir($dh);
        }
    }
}
