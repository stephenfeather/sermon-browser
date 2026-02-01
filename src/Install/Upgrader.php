<?php

declare(strict_types=1);

namespace SermonBrowser\Install;

use SermonBrowser\Constants;

/**
 * Handles upgrade procedures for Sermon Browser.
 *
 * Migrates old-style options and performs version upgrades.
 *
 * @since 1.0.0
 */
class Upgrader
{
    /**
     * Get the mapping of old options to new options (standard).
     *
     * @return array<int, array{old_option: string, new_option: string}>
     */
    public static function getStandardOptionMappings(): array
    {
        return [
            ['old_option' => 'sb_sermon_style_date_modified', 'new_option' => 'style_date_modified'],
            ['old_option' => 'sb_sermon_db_version', 'new_option' => 'db_version'],
            ['old_option' => 'sb_sermon_version', 'new_option' => 'code_version'],
            ['old_option' => 'sb_podcast', 'new_option' => 'podcast_url'],
            ['old_option' => 'sb_filtertype', 'new_option' => 'filter_type'],
            ['old_option' => 'sb_filterhide', 'new_option' => 'filter_hide'],
            ['old_option' => 'sb_widget_sermon', 'new_option' => 'sermons_widget_options'],
            ['old_option' => 'sb_sermon_upload_dir', 'new_option' => 'upload_dir'],
            ['old_option' => 'sb_sermon_upload_url', 'new_option' => 'upload_url'],
            ['old_option' => 'sb_display_method', 'new_option' => 'display_method'],
            ['old_option' => 'sb_sermons_per_page', 'new_option' => 'sermons_per_page'],
            ['old_option' => 'sb_show_donate_reminder', 'new_option' => 'show_donate_reminder'],
        ];
    }

    /**
     * Get the mapping of old options to new options (base64 encoded).
     *
     * @return array<int, array{old_option: string, new_option: string}>
     */
    public static function getBase64OptionMappings(): array
    {
        return [
            ['old_option' => 'sb_sermon_single_form', 'new_option' => 'single_template'],
            ['old_option' => 'sb_sermon_single_output', 'new_option' => 'single_output'],
            ['old_option' => 'sb_sermon_multi_form', 'new_option' => 'search_template'],
            ['old_option' => 'sb_sermon_multi_output', 'new_option' => 'search_output'],
            ['old_option' => 'sb_sermon_style', 'new_option' => 'css_style'],
        ];
    }

    /**
     * Upgrade old-style sermonbrowser options (prior to 0.43).
     *
     * @return void
     */
    public static function upgradeOptions(): void
    {
        $standardOptions = self::getStandardOptionMappings();

        foreach ($standardOptions as $option) {
            $old = get_option($option['old_option']);
            if ($old) {
                sb_update_option($option['new_option'], $old);
                delete_option($option['old_option']);
            }
        }

        $base64Options = self::getBase64OptionMappings();

        foreach ($base64Options as $option) {
            $old = get_option($option['old_option']);
            if ($old) {
                $decoded = stripslashes(base64_decode($old));
                sb_update_option($option['new_option'], $decoded);
                delete_option($option['old_option']);
            }
        }

        delete_option('sb_sermon_style_output');
    }

    /**
     * Run the version upgrade procedures.
     *
     * Adds options that were added since the last database update.
     *
     * @param string $oldVersion The previous version (reserved for future version-specific upgrades).
     * @param string $newVersion The new version.
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function versionUpgrade(string $oldVersion, string $newVersion): void
    {
        // $oldVersion reserved for future version-specific upgrade logic
        unset($oldVersion);

        sb_update_option('code_version', $newVersion);

        if (sb_get_option('filter_type') === '') {
            sb_update_option('filter_type', 'dropdown');
        }

        // Clear template cache so new template engine takes effect.
        delete_transient('sb_template_search');
        delete_transient('sb_template_single');
    }

    /**
     * Run the database upgrade procedures.
     *
     * Modifies database structure based on version.
     *
     * @param string $oldVersion The previous database version.
     * @return void
     */
    public static function databaseUpgrade(string $oldVersion): void
    {
        require_once SB_INCLUDES_DIR . '/admin.php';

        global $wpdb;
        $sermonUploadDir = sb_get_default('sermon_path');

        switch ($oldVersion) {
            case '1.0':
                self::upgradeFrom10($wpdb, $sermonUploadDir);
                // no break - intentional fall-through for cascading upgrades
            case '1.1':
                self::upgradeFrom11($sermonUploadDir);
                // no break
            case '1.2':
                self::upgradeFrom12($wpdb);
                // no break
            case '1.3':
                self::upgradeFrom13($wpdb);
                // no break
            case '1.4':
                self::upgradeFrom14($wpdb);
                // no break
            case '1.5':
                self::upgradeFrom15($wpdb);
                // no break
            case '1.6':
                self::upgradeFrom16();
                return;
            default:
                update_option('sb_sermon_db_version', '1.0');
        }
    }

    /**
     * Upgrade from database version 1.0.
     *
     * @param \wpdb  $wpdb            WordPress database instance.
     * @param string $sermonUploadDir The sermon upload directory.
     * @return void
     */
    private static function upgradeFrom10(\wpdb $wpdb, string $sermonUploadDir): void
    {
        $oldSermonPath = dirname(__FILE__) . "/files/";
        $files = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}sb_stuff WHERE type = 'file' ORDER BY name ASC"
        );

        foreach ((array) $files as $file) {
            @chmod(SB_ABSPATH . $oldSermonPath . $file->name, 0644);
            @rename(
                SB_ABSPATH . $oldSermonPath . $file->name,
                SB_ABSPATH . $sermonUploadDir . $file->name
            );
        }

        $tableName = $wpdb->prefix . "sb_preachers";
        if ($wpdb->get_var("show tables like '{$tableName}'") === $tableName) {
            $wpdb->query(
                "ALTER TABLE {$tableName} ADD description TEXT NOT NULL, ADD image VARCHAR(255) NOT NULL"
            );
        }

        update_option('sb_sermon_db_version', '1.1');
    }

    /**
     * Upgrade from database version 1.1.
     *
     * @param string $sermonUploadDir The sermon upload directory.
     * @return void
     */
    private static function upgradeFrom11(string $sermonUploadDir): void
    {
        // Note: $defaultStyle was undefined in original code
        add_option('sb_sermon_style', base64_encode(''));

        $imagesDir = SB_ABSPATH . $sermonUploadDir . 'images';
        if (!is_dir($imagesDir) && sb_mkdir($imagesDir)) {
            @chmod($imagesDir, 0755);
        }

        update_option('sb_sermon_db_version', '1.2');
    }

    /**
     * Upgrade from database version 1.2.
     *
     * @param \wpdb $wpdb WordPress database instance.
     * @return void
     */
    private static function upgradeFrom12(\wpdb $wpdb): void
    {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sb_stuff ADD count INT(10) NOT NULL");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sb_books_sermons ADD INDEX (sermon_id)");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sb_sermons_tags ADD INDEX (sermon_id)");

        update_option('sb_sermon_db_version', '1.3');
    }

    /**
     * Upgrade from database version 1.3.
     *
     * @param \wpdb $wpdb WordPress database instance.
     * @return void
     */
    private static function upgradeFrom13(\wpdb $wpdb): void
    {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sb_series ADD page_id INT(10) NOT NULL");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sb_sermons ADD page_id INT(10) NOT NULL");

        add_option('sb_display_method', 'dynamic');
        add_option('sb_sermons_per_page', '10');
        add_option('sb_sermon_style_date_modified', strtotime('now'));

        update_option('sb_sermon_db_version', '1.4');
    }

    /**
     * Upgrade from database version 1.4.
     *
     * @param \wpdb $wpdb WordPress database instance.
     * @return void
     */
    private static function upgradeFrom14(\wpdb $wpdb): void
    {
        self::removeDuplicateIndexes($wpdb);
        self::removeDuplicateTags($wpdb);

        sb_delete_unused_tags();

        $wpdb->query("ALTER TABLE {$wpdb->prefix}sb_tags CHANGE name name VARCHAR(255)");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sb_tags ADD UNIQUE (name)");

        update_option('sb_sermon_db_version', '1.5');
    }

    /**
     * Remove duplicate indexes added by a previous bug.
     *
     * @param \wpdb $wpdb WordPress database instance.
     */
    private static function removeDuplicateIndexes(\wpdb $wpdb): void
    {
        $extraIndexes = $wpdb->get_results(
            "SELECT index_name, table_name FROM INFORMATION_SCHEMA.STATISTICS " .
            "WHERE table_schema = '" . DB_NAME . "' AND index_name LIKE 'sermon_id_%'"
        );

        if (!is_array($extraIndexes)) {
            return;
        }

        foreach ($extraIndexes as $extraIndex) {
            $wpdb->query("ALTER TABLE {$extraIndex->table_name} DROP INDEX {$extraIndex->index_name}");
        }
    }

    /**
     * Remove duplicate tags added by a previous bug.
     *
     * @param \wpdb $wpdb WordPress database instance.
     */
    private static function removeDuplicateTags(\wpdb $wpdb): void
    {
        $uniqueTags = $wpdb->get_results("SELECT DISTINCT name FROM {$wpdb->prefix}sb_tags");

        if (!is_array($uniqueTags)) {
            return;
        }

        foreach ($uniqueTags as $tag) {
            self::consolidateTagDuplicates($wpdb, $tag->name);
        }
    }

    /**
     * Consolidate duplicate tags with the same name.
     *
     * @param \wpdb  $wpdb    WordPress database instance.
     * @param string $tagName The tag name to consolidate.
     */
    private static function consolidateTagDuplicates(\wpdb $wpdb, string $tagName): void
    {
        $tagIds = $wpdb->get_results(
            $wpdb->prepare("SELECT id FROM {$wpdb->prefix}sb_tags WHERE name=%s", $tagName)
        );

        if (!is_array($tagIds) || count($tagIds) < 2) {
            return;
        }

        $primaryTagId = $tagIds[0]->id;

        foreach ($tagIds as $tagId) {
            $wpdb->query(
                "UPDATE {$wpdb->prefix}sb_sermons_tags " .
                "SET tag_id='{$primaryTagId}' WHERE tag_id='{$tagId->id}'"
            );

            if ($primaryTagId !== $tagId->id) {
                $wpdb->query("DELETE FROM {$wpdb->prefix}sb_tags WHERE id='{$tagId->id}'");
            }
        }
    }

    /**
     * Upgrade from database version 1.5.
     *
     * @param \wpdb $wpdb WordPress database instance.
     * @return void
     */
    private static function upgradeFrom15(\wpdb $wpdb): void
    {
        self::upgradeOptions();

        $wpdb->query("ALTER TABLE {$wpdb->prefix}sb_stuff ADD duration VARCHAR (6) NOT NULL");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}sb_sermons CHANGE date `datetime` DATETIME NOT NULL");

        // Populate time portion of date/time field
        $sermonDates = $wpdb->get_results(
            "SELECT id, datetime, service_id, time, override FROM {$wpdb->prefix}sb_sermons"
        );

        if ($sermonDates) {
            $services = $wpdb->get_results(
                "SELECT id, time FROM {$wpdb->prefix}sb_services ORDER BY id asc"
            );

            $serviceTime = [];
            foreach ($services as $service) {
                $serviceTime[$service->id] = $service->time;
            }

            foreach ($sermonDates as $sermonDate) {
                if ($sermonDate->override) {
                    $newTime = strtotime($sermonDate->time) - strtotime(Constants::DEFAULT_TIME)
                        + strtotime($sermonDate->datetime);
                } else {
                    $newTime = strtotime($serviceTime[$sermonDate->service_id] ?? Constants::DEFAULT_TIME)
                        - strtotime(Constants::DEFAULT_TIME) + strtotime($sermonDate->datetime);
                }

                $formattedDate = date("Y-m-d H:i:s", $newTime);
                $wpdb->query(
                    "UPDATE {$wpdb->prefix}sb_sermons SET datetime = '{$formattedDate}' WHERE id={$sermonDate->id}"
                );
            }
        }

        sb_update_option('import_prompt', true);
        sb_update_option('import_title', false);
        sb_update_option('import_artist', false);
        sb_update_option('import_album', false);
        sb_update_option('import_comments', false);
        sb_update_option('import_filename', 'none');
        sb_update_option('hide_no_attachments', false);
        sb_update_option('db_version', '1.6');
    }

    /**
     * Upgrade from database version 1.6.
     *
     * @return void
     */
    private static function upgradeFrom16(): void
    {
        sb_update_option('mp3_shortcode', '[audio mp3="%SERMONURL%"]');
        sb_update_option('db_version', '1.7');
    }
}
