<?php

/**
 * Upload Helper.
 *
 * Provides upload form rendering and validation utilities.
 *
 * @package SermonBrowser\Admin
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin;

use SermonBrowser\Facades\File;

/**
 * Class UploadHelper
 *
 * Handles upload form rendering and upload folder validation.
 */
class UploadHelper
{
    /**
     * Check to see if upload folder is writeable.
     *
     * @param string $foldername Optional subfolder name.
     * @return string|false 'writeable', 'unwriteable', 'notexist', or false.
     */
    public static function checkUploadable(string $foldername = ''): string|false
    {
        $sermonUploadDir = SB_ABSPATH . sb_get_option('upload_dir') . $foldername;

        if (!is_dir($sermonUploadDir)) {
            return 'notexist';
        }

        // Dir exists - test if writeable
        $fp = @fopen($sermonUploadDir . 'sermontest.txt', 'w');
        if (!$fp) {
            return 'unwriteable';
        }

        // Delete this test file
        fclose($fp);
        @unlink($sermonUploadDir . 'sermontest.txt');
        return 'writeable';
    }

    /**
     * Returns true if any ID3 import options have been selected.
     *
     * @return bool
     */
    public static function importOptionsSet(): bool
    {
        if (
            !sb_get_option('import_title')
            && !sb_get_option('import_artist')
            && !sb_get_option('import_album')
            && !sb_get_option('import_comments')
            && (!sb_get_option('import_filename') || sb_get_option('import_filename') == 'none')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Displays notice if ID3 import options have not been set.
     *
     * @param bool $long Whether to display the long version of the message.
     * @return void
     */
    public static function renderImportMessage(bool $long = false): void
    {
        if (!self::importOptionsSet()) {
            if ($long) {
                _e('SermonBrowser can automatically pre-fill this form by reading ID3 tags from MP3 files.', 'sermon-browser');
                echo ' ';
            }
            printf(
                // translators: %s is a link to the import options page.
                __('You will need to set the %s before you can import MP3s and pre-fill the Add Sermons form.', 'sermon-browser'),
                '<a href="' . admin_url('admin.php?page=sermon-browser/options.php') . '">'
                    . __('import options', 'sermon-browser') . '</a>'
            );
        }
    }

    /**
     * Echoes the upload form.
     *
     * @return void
     */
    public static function renderForm(): void
    {
        ?>
        <table style="width:100%; border-spacing:2px" class="widefat">
            <form method="post" enctype="multipart/form-data" action ="<?php echo admin_url('admin.php?page=sermon-browser/files.php'); ?>" >
            <?php wp_nonce_field('sb_file_upload', 'sb_file_upload_nonce'); ?>
            <?php wp_nonce_field('sb_file_import', 'sb_file_import_nonce'); ?>
            <thead>
            <tr>
                <th scope="col" colspan="3"><?php
                if (self::importOptionsSet()) {
                    printf(
                        // translators: %s is a link to the Add Sermons page.
                        __("Select an MP3 file here to have the %s form pre-filled using ID3 tags.", 'sermon-browser'),
                        "<a href=\"" . admin_url('admin.php?page=sermon-browser/new_sermon.php') . "\">"
                            . __('Add Sermons', 'sermon-browser') . '</a>'
                    );
                } else {
                    _e('Upload file', 'sermon-browser');
                }
                ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th style="width:20em; white-space:nowrap; vertical-align:top" scope="row"><?php _e('File to upload', 'sermon-browser'); ?>: </th>
        <?php
        $checkSermonUpload = self::checkUploadable();
        if ($checkSermonUpload == 'writeable') {
            ?>
                <td style="width:40px"><input type="file" size="40" value="" name="upload" /></td>
                <td class="submit"><input type="submit" name="save" value="<?php _e('Upload', 'sermon-browser'); ?> &raquo;" /></td>
            <?php
        } elseif (IS_MU) {
            ?>
                <td><?php _e('Upload is disabled. Please contact your systems administrator.', 'sermon-browser'); ?></td>
            <?php
        } else {
            ?>
                <td><?php _e('Upload is disabled. Please check your folder setting in Options.', 'sermon-browser'); ?></td>
            <?php
        }
        ?>
            </tr>
        <?php if (self::importOptionsSet()) { ?>
            <tr>
                <th style="white-space:nowrap; vertical-align:top" scope="row"><?php _e('URL to import', 'sermon-browser'); ?>: </th>
                <td>
                    <input type="text" size="40" value="" name="url"/><br/>
                    <span style="line-height: 29px"><input type="radio" name="import_type" value="remote" checked="checked" /><?php _e('Link to remote file', 'sermon-browser'); ?> <input type="radio" name="import_type" value="download" /><?php _e('Copy remote file to server', 'sermon-browser'); ?></span>
                </td>
                <td class="submit"><input type="submit" name="import_url" value="<?php _e('Import', 'sermon-browser'); ?> &raquo;" /></td>
            </tr>
        <?php } ?>
        </form>
        <?php if (isset($_GET['page']) && $_GET['page'] == 'sermon-browser/new_sermon.php') { ?>
            <form method="get" action="<?php echo admin_url('admin.php?page=sermon-browser/new_sermon.php'); ?>">
            <input type="hidden" name="page" value="sermon-browser/new_sermon.php" />
            <tr>
                <th style="white-space:nowrap; vertical-align:top" scope="row"><?php _e('Choose existing file', 'sermon-browser'); ?>: </th>
                <td>
                    <select name="getid3">
                        <?php
                            $files = File::findUnlinked();
                            echo count($files) == 0 ? '<option value="0">No files found</option>' : '<option value="0"></option>';
                        foreach ($files as $file) {
                            ?>
                                <option value="<?php echo $file->id; ?>"><?php echo $file->name; ?></option>
                        <?php } ?>
                    </select>
                </td>
                <td class="submit"><input type="submit" value="<?php _e('Select', 'sermon-browser'); ?> &raquo;" /></td>
            </tr>
        </form>
        <?php } ?>
            </tbody>
    </table>
        <?php
    }
}
