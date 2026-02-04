<?php

declare(strict_types=1);

namespace SermonBrowser\Install;

use SermonBrowser\Config\OptionsManager;

/**
 * Handles uninstallation of Sermon Browser.
 *
 * Removes database tables, options, and optionally uploaded files.
 *
 * @since 1.0.0
 */
class Uninstaller
{
    /**
     * Get the list of database table names (without prefix).
     *
     * @return array<int, string>
     */
    public static function getTableNames(): array
    {
        return [
            'sb_preachers',
            'sb_series',
            'sb_services',
            'sb_sermons',
            'sb_stuff',
            'sb_books',
            'sb_books_sermons',
            'sb_sermons_tags',
            'sb_tags',
        ];
    }

    /**
     * Run the full uninstall procedure.
     *
     * @param bool $wipeFiles Whether to also delete uploaded files.
     * @return void
     */
    public static function run(bool $wipeFiles = false): void
    {
        if ($wipeFiles) {
            self::wipeUploadDirectory();
        }

        self::dropTables();
        self::deleteOptions();
        self::displayMessage();
    }

    /**
     * Wipe all files in the upload directory.
     *
     * @return void
     */
    public static function wipeUploadDirectory(): void
    {
        $dir = SB_ABSPATH . OptionsManager::get('upload_dir');

        $handle = @opendir($dir);
        if ($handle) {
            while (false !== ($file = readdir($handle))) {
                if ($file !== "." && $file !== "..") {
                    self::safeUnlinkFile($dir, $file);
                }
            }
            closedir($handle);
        }
    }

    /**
     * Safely delete a file from a directory.
     *
     * Validates that the file is within the expected directory to prevent
     * path traversal attacks before deletion.
     *
     * @param string $dir  The directory path.
     * @param string $file The filename to delete.
     * @return bool True if file was deleted or didn't exist, false on error.
     */
    private static function safeUnlinkFile(string $dir, string $file): bool
    {
        // Use only the basename to prevent path traversal.
        $safeFilename = basename($file);
        if (empty($safeFilename)) {
            return true;
        }

        $filePath = $dir . $safeFilename;

        if (!file_exists($filePath)) {
            return true;
        }

        // Resolve real paths to prevent symlink attacks.
        $realDir = realpath($dir);
        $realFilePath = realpath($filePath);

        // Ensure resolved paths are valid and file is within directory.
        if (
            $realDir === false ||
            $realFilePath === false ||
            strpos($realFilePath, $realDir) !== 0
        ) {
            return false;
        }

        return @unlink($realFilePath);
    }

    /**
     * Drop all Sermon Browser database tables.
     *
     * @return void
     */
    public static function dropTables(): void
    {
        global $wpdb;

        $tables = self::getTableNames();

        foreach ($tables as $table) {
            $fullTableName = $wpdb->prefix . $table;
            if ($wpdb->get_var("show tables like '{$fullTableName}'") === $fullTableName) {
                $wpdb->query("DROP TABLE {$fullTableName}");
            }
        }
    }

    /**
     * Delete all Sermon Browser options.
     *
     * @return void
     */
    public static function deleteOptions(): void
    {
        delete_option('sermonbrowser_options');

        $specialOptions = sb_special_option_names();

        foreach ($specialOptions as $option) {
            delete_option("sermonbrowser_{$option}");
        }
    }

    /**
     * Display the uninstall completion message.
     *
     * @return void
     */
    public static function displayMessage(): void
    {
        if (IS_MU) {
            echo '<div id="message" class="updated fade"><p><b>'
                . __('All sermon data has been removed.', 'sermon-browser')
                . '</b></div>';
        } else {
            echo '<div id="message" class="updated fade"><p><b>'
                . __('Uninstall completed. The SermonBrowser plugin has been deactivated.', 'sermon-browser')
                . '</b></div>';

            self::deactivatePlugin();
        }
    }

    /**
     * Deactivate the plugin.
     *
     * @return void
     */
    private static function deactivatePlugin(): void
    {
        $activePlugins = get_option('active_plugins');
        $pluginIndex = array_search('sermon-browser/sermon.php', $activePlugins);

        if ($pluginIndex !== false) {
            array_splice($activePlugins, $pluginIndex, 1);
            do_action('deactivate_sermon-browser/sermon.php');
            update_option('active_plugins', $activePlugins);
        }
    }
}
