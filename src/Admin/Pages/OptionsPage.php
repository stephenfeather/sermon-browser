<?php

/**
 * Options Page.
 *
 * Handles the Options admin page for configuring SermonBrowser settings.
 *
 * @package SermonBrowser\Admin\Pages
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Pages;

use SermonBrowser\Admin\FormHelpers;
use SermonBrowser\Constants;
use SermonBrowser\Facades\Book;

/**
 * Class OptionsPage
 *
 * Manages plugin options including upload settings, podcast URLs, and import options.
 */
class OptionsPage
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Render the options page.
     *
     * @return void
     */
    public function render(): void
    {
        // Security check.
        if (!current_user_can('manage_options')) {
            wp_die(__(Constants::ERR_NO_PERMISSION, 'sermon-browser'));
        }

        // Handle form submissions.
        $this->handlePost();

        // Display alerts.
        sb_do_alerts();

        // Render the page.
        $this->renderPage();
    }

    /**
     * Handle POST submissions.
     *
     * @return void
     */
    private function handlePost(): void
    {
        if (isset($_POST['resetdefault'])) {
            $this->handleResetDefaults();
        } elseif (isset($_POST['save'])) {
            $this->handleSaveOptions();
        }
    }

    /**
     * Handle reset to default options.
     *
     * @return void
     */
    private function handleResetDefaults(): void
    {
        // Verify nonce.
        if (
            !isset($_POST['sermon_options_save_reset_nonce']) ||
            !wp_verify_nonce($_POST['sermon_options_save_reset_nonce'], 'sermon_options_save_reset')
        ) {
            wp_die(__(Constants::ERR_NO_PERMISSION, 'sermon-browser'));
        }

        $dir = sb_get_default('sermon_path');

        // Set podcast URL.
        if (sb_display_url() === "#") {
            sb_update_option('podcast_url', site_url() . sb_query_char(false) . 'podcast');
        } else {
            sb_update_option('podcast_url', sb_display_url() . sb_query_char(false) . 'podcast');
        }

        // Reset all options to defaults.
        sb_update_option('upload_dir', $dir);
        sb_update_option('upload_url', sb_get_default('attachment_url'));
        sb_update_option('display_method', 'dynamic');
        sb_update_option('sermons_per_page', '10');
        sb_update_option('filter_type', 'oneclick');
        sb_update_option('filter_hide', 'hide');
        sb_update_option('hide_no_attachments', false);
        sb_update_option('mp3_shortcode', '[audio mp3="%SERMONURL%"]');
        sb_update_option('esv_api_key', '');

        // Create upload directories.
        $this->createUploadDirectories($dir);

        // Reset bible books database.
        $this->resetBibleBooks();

        // Display status message.
        $this->displayUploadStatus();
    }

    /**
     * Handle save options.
     *
     * @return void
     */
    private function handleSaveOptions(): void
    {
        // Verify nonce.
        if (
            !isset($_POST['sermon_options_save_reset_nonce']) ||
            !wp_verify_nonce($_POST['sermon_options_save_reset_nonce'], 'sermon_options_save_reset')
        ) {
            wp_die(__(Constants::ERR_NO_PERMISSION, 'sermon-browser'));
        }

        $dir = rtrim(str_replace("\\", "/", sanitize_text_field(stripslashes($_POST['dir']))), "/") . "/";

        // Validate directory path to prevent path traversal attacks.
        if (!$this->isValidUploadDirectory($dir)) {
            echo '<div id="message" class="updated fade"><p><b>' .
                esc_html__('Invalid upload directory path. Directory cannot contain ".." or start with "/".', 'sermon-browser') .
                '</b></p></div>';
            return;
        }

        // Save options.
        sb_update_option('podcast_url', esc_url($_POST['podcast']));

        if ((int) $_POST['perpage'] > 0) {
            sb_update_option('sermons_per_page', (int) $_POST['perpage']);
        }

        if ((int) $_POST['perpage'] === -100) {
            update_option('show_donate_reminder', 'off');
        }

        sb_update_option('upload_dir', $dir);
        sb_update_option('filter_type', sanitize_key($_POST['filtertype']));
        sb_update_option('filter_hide', isset($_POST['filterhide']));
        sb_update_option('upload_url', trailingslashit(site_url()) . $dir);
        sb_update_option('import_prompt', isset($_POST['import_prompt']));
        sb_update_option('import_title', isset($_POST['import_title']));
        sb_update_option('import_artist', isset($_POST['import_artist']));
        sb_update_option('import_album', isset($_POST['import_album']));
        sb_update_option('import_comments', isset($_POST['import_comments']));
        sb_update_option('import_filename', sanitize_key($_POST['import_filename']));
        sb_update_option('hide_no_attachments', isset($_POST['hide_no_attachments']));
        sb_update_option('mp3_shortcode', sanitize_text_field(stripslashes($_POST['mp3_shortcode'])));
        sb_update_option('esv_api_key', esc_html(stripslashes($_POST['esv_api_key'])));

        // Create upload directories.
        $this->createUploadDirectories($dir);

        // Display status message.
        $this->displayUploadStatus(__('Options saved successfully.', 'sermon-browser'));
    }

    /**
     * Create upload directories if they don't exist.
     *
     * @param string $dir Directory path.
     * @return void
     */
    private function createUploadDirectories(string $dir): void
    {
        if (!is_dir(SB_ABSPATH . $dir) && sb_mkdir(SB_ABSPATH . $dir)) {
            @chmod(SB_ABSPATH . $dir, 0755); // NOSONAR - WordPress standard directory permission
        }

        if (!is_dir(SB_ABSPATH . $dir . 'images') && sb_mkdir(SB_ABSPATH . $dir . 'images')) {
            @chmod(SB_ABSPATH . $dir . 'images', 0755); // NOSONAR - WordPress standard directory permission
        }
    }

    /**
     * Validate upload directory path to prevent path traversal attacks.
     *
     * @param string $dir Directory path to validate.
     * @return bool True if valid, false otherwise.
     */
    private function isValidUploadDirectory(string $dir): bool
    {
        // Reject paths with directory traversal, absolute paths, or null bytes.
        $hasTraversal = strpos($dir, '..') !== false;
        $isAbsolute = strpos($dir, '/') === 0;
        $hasNullByte = strpos($dir, "\0") !== false;

        if ($hasTraversal || $isAbsolute || $hasNullByte) {
            return false;
        }

        return true;
    }

    /**
     * Reset bible books database to defaults.
     *
     * @return void
     */
    private function resetBibleBooks(): void
    {
        $books = sb_get_default('bible_books');
        $eng_books = sb_get_default('eng_bible_books');

        // Reset bible books using Book Facade.
        Book::resetBooksForLocale($books, $eng_books);

        // Rewrite book names for non-English locales.
        if ($books !== $eng_books) {
            $this->rewriteSermonBookNames($books, $eng_books);
        }
    }

    /**
     * Rewrite sermon book names for non-English locales.
     *
     * @param array $books     Localized book names.
     * @param array $eng_books English book names.
     * @return void
     */
    private function rewriteSermonBookNames(array $books, array $eng_books): void
    {
        $sermon_books = Book::getSermonsWithVerseData();

        foreach ($sermon_books as $sermon_book) {
            $start_verse = unserialize($sermon_book->start, ['allowed_classes' => false]);
            $end_verse = unserialize($sermon_book->end, ['allowed_classes' => false]);

            $start_index = array_search($start_verse[0]['book'], $eng_books, true);
            $end_index = array_search($end_verse[0]['book'], $eng_books, true);

            if ($start_index !== false) {
                $start_verse[0]['book'] = $books[$start_index];
            }

            if ($end_index !== false) {
                $end_verse[0]['book'] = $books[$end_index];
            }

            $sermon_book->start = serialize($start_verse);
            $sermon_book->end = serialize($end_verse);

            Book::updateSermonVerseData((int) $sermon_book->id, $sermon_book->start, $sermon_book->end);
        }
    }

    /**
     * Display upload folder status message.
     *
     * @param string|null $successMessage Custom success message.
     * @return void
     */
    private function displayUploadStatus(?string $successMessage = null): void
    {
        $checkSermonUpload = sb_checkSermonUploadable();

        switch ($checkSermonUpload) {
            case "unwriteable":
                echo '<div id="message" class="updated fade"><p><b>';
                if (IS_MU && !sb_is_super_admin()) {
                    _e('Upload is disabled. Please contact your administrator.', 'sermon-browser');
                } else {
                    _e('Error: The upload folder is not writeable. You need to CHMOD the folder to 666 or 777.', 'sermon-browser');
                }
                echo '</b></div>';
                break;

            case "notexist":
                echo '<div id="message" class="updated fade"><p><b>';
                if (IS_MU && !sb_is_super_admin()) {
                    _e('Upload is disabled. Please contact your administrator.', 'sermon-browser');
                } else {
                    _e('Error: The upload folder you have specified does not exist.', 'sermon-browser');
                }
                echo '</b></div>';
                break;

            default:
                echo '<div id="message" class="updated fade"><p><b>';
                if ($successMessage) {
                    echo esc_html($successMessage);
                } else {
                    _e('Default loaded successfully.', 'sermon-browser');
                }
                echo '</b></div>';
                break;
        }
    }

    /**
     * Render the options page HTML.
     *
     * @return void
     */
    private function renderPage(): void
    {
        ?>
        <div class="wrap">
            <a href="http://www.sermonbrowser.com/">
                <img src="<?php echo SB_PLUGIN_URL; ?>/assets/images/logo-small.png"
                     width="191" height="35"
                     style="margin: 1em 2em; float: right;"
                     alt="<?php esc_attr_e('Sermon Browser logo', 'sermon-browser'); ?>"/>
            </a>
            <form method="post">
                <h2><?php _e('Basic Options', 'sermon-browser') ?></h2>
                <br style="clear:both"/>
                <div class="widefat" style="background: #fff; border: 1px solid #c3c4c7; padding: 1em;">
                    <?php $this->renderUploadFolderField(); ?>
                    <?php $this->renderPodcastFields(); ?>
                    <?php $this->renderMp3ShortcodeField(); ?>
                    <?php $this->renderEsvApiKeyField(); ?>
                    <?php $this->renderSermonsPerPageField(); ?>
                    <?php $this->renderFilterTypeFields(); ?>
                    <?php $this->renderHideNoAttachmentsField(); ?>
                    <?php $this->renderPhpIniWarnings(); ?>
                </div>

                <h2><?php _e('Import Options', 'sermon-browser') ?></h2>
                <p>
                    <?php
                    printf(
                        __(
                            'SermonBrowser can speed up the process of importing existing MP3s by reading the information stored in each MP3 file and pre-filling the SermonBrowser fields. Use this section to specify what information you want imported into SermonBrowser. Once you have selected the options, go to %s to import your files.',
                            'sermon-browser'
                        ),
                        '<a href="' . admin_url('admin.php?page=sermon-browser/files.php') . '">' .
                        __('Files', 'sermon-browser') . '</a>'
                    );
                    ?>
                </p>
                <div class="widefat" style="background: #fff; border: 1px solid #c3c4c7; padding: 1em;">
                    <?php $this->renderImportOptions(); ?>
                </div>

                <?php wp_nonce_field('sermon_options_save_reset', 'sermon_options_save_reset_nonce'); ?>
                <p class="submit">
                    <input type="submit" name="save" value="<?php _e('Save', 'sermon-browser') ?> &raquo;"/>
                    <input type="submit" name="resetdefault" value="<?php _e('Reset to defaults', 'sermon-browser') ?>"/>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render upload folder field.
     *
     * @return void
     */
    private function renderUploadFolderField(): void
    {
        if (!IS_MU || sb_is_super_admin()) {
            ?>
            <div style="display: flex; gap: 1em; margin-bottom: 1em;">
                <label for="sb-upload-dir" style="min-width: 180px; text-align: right; padding-top: 0.5em;">
                    <?php _e('Upload folder', 'sermon-browser') ?>:
                </label>
                <div style="flex: 1;">
                    <input type="text" id="sb-upload-dir" name="dir"
                           value="<?php echo htmlspecialchars(sb_get_option('upload_dir')) ?>"
                           style="width:100%"/>
                </div>
            </div>
            <?php
        } else {
            ?>
            <input type="hidden" name="dir"
                   value="<?php echo htmlspecialchars(sb_get_option('upload_dir')) ?>">
            <?php
        }
    }

    /**
     * Render podcast URL fields.
     *
     * @return void
     */
    private function renderPodcastFields(): void
    {
        ?>
        <div style="display: flex; gap: 1em; margin-bottom: 1em;">
            <label for="sb-podcast-url" style="min-width: 180px; text-align: right; padding-top: 0.5em;">
                <?php _e('Public podcast feed', 'sermon-browser') ?>:
            </label>
            <div style="flex: 1;">
                <input type="text" id="sb-podcast-url" name="podcast"
                       value="<?php echo htmlspecialchars(sb_get_option('podcast_url')) ?>"
                       style="width:100%"/>
            </div>
        </div>
        <div style="display: flex; gap: 1em; margin-bottom: 1em;">
            <label style="min-width: 180px; text-align: right; padding-top: 0.5em;">
                <?php _e('Private podcast feed', 'sermon-browser') ?>:
            </label>
            <div style="flex: 1;">
                <?php
                if (sb_display_url() === '') {
                    echo htmlspecialchars(site_url());
                } else {
                    echo htmlspecialchars(sb_display_url());
                }
                echo sb_query_char();
                ?>podcast
            </div>
        </div>
        <?php
    }

    /**
     * Render MP3 shortcode field.
     *
     * @return void
     */
    private function renderMp3ShortcodeField(): void
    {
        ?>
        <div style="display: flex; gap: 1em; margin-bottom: 1em;">
            <label for="sb-mp3-shortcode" style="min-width: 180px; text-align: right; padding-top: 0.5em;">
                <?php _e('MP3 shortcode', 'sermon-browser') ?>:
                <br/><?php _e('Default: ', 'sermon-browser') ?>[audio mp3=&quot;%SERMONURL%&quot;]
            </label>
            <div style="flex: 1;">
                <input type="text" id="sb-mp3-shortcode" name="mp3_shortcode"
                       value="<?php echo htmlspecialchars(sb_get_option('mp3_shortcode')) ?>"
                       style="width:100%"/>
            </div>
        </div>
        <?php
    }

    /**
     * Render ESV API key field.
     *
     * @return void
     */
    private function renderEsvApiKeyField(): void
    {
        $template = sb_get_option('single_template');
        $extra_text = '';

        if (!sb_get_option('esv_api_key') && strpos($template, '[esvtext]') !== null) {
            $extra_text = __('<br/>Without an API key, your site will display text from the KJV, instead.', 'sermon-browser');
        }
        ?>
        <div style="display: flex; gap: 1em; margin-bottom: 1em;">
            <label for="sb-esv-api-key" style="min-width: 180px; text-align: right; padding-top: 0.5em;">
                <?php _e('ESV API Key (required to display the ESV text)', 'sermon-browser') ?>:
                <br/>
                <?php
                echo __(
                    'You can sign up for an API Key <a href="https://api.esv.org/account/create-application/">here</a> (you\'ll need to create an account).<br/>A key looks like this: 82e261b8b5b6ed0b6e7f09332d2acc48d88ee7fa',
                    'sermon-browser'
                ) . $extra_text;
                ?>
            </label>
            <div style="flex: 1;">
                <input type="text" id="sb-esv-api-key" name="esv_api_key"
                       value="<?php echo htmlspecialchars(sb_get_option('esv_api_key')) ?>"
                       style="width:100%"/>
            </div>
        </div>
        <?php
    }

    /**
     * Render sermons per page field.
     *
     * @return void
     */
    private function renderSermonsPerPageField(): void
    {
        ?>
        <div style="display: flex; gap: 1em; margin-bottom: 1em;">
            <label for="sb-perpage" style="min-width: 180px; text-align: right; padding-top: 0.5em;">
                <?php _e('Sermons per page', 'sermon-browser') ?>:
            </label>
            <div style="flex: 1;">
                <input type="text" id="sb-perpage" name="perpage" value="<?php echo sb_get_option('sermons_per_page') ?>"/>
            </div>
        </div>
        <?php
    }

    /**
     * Render filter type fields.
     *
     * @return void
     */
    private function renderFilterTypeFields(): void
    {
        $ft = sb_get_option('filter_type');
        $filter_options = [
            'dropdown' => __('Drop-down', 'sermon-browser'),
            'oneclick' => __('One-click', 'sermon-browser'),
            'none'     => __('None', 'sermon-browser'),
        ];
        ?>
        <div style="display: flex; gap: 1em; margin-bottom: 1em;">
            <label for="sb-filtertype-dropdown" style="min-width: 180px; text-align: right; padding-top: 0.5em;">
                <?php _e('Filter type', 'sermon-browser') ?>:
            </label>
            <div style="flex: 1;">
                <?php
                $first = true;
                foreach ($filter_options as $value => $filter_option) {
                    $checked = ($ft === $value) ? Constants::CHECKED : '';
                    $id = $first ? ' id="sb-filtertype-dropdown"' : '';
                    $first = false;
                    echo "<input type=\"radio\" name=\"filtertype\" value=\"{$value}\"{$id} {$checked}/> {$filter_option}<br/>\n";
                }
                ?>
                <div style="margin-top: 0.5em;">
                    <input type="checkbox" id="sb-filterhide" name="filterhide"
                        <?php echo (sb_get_option('filter_hide') === 'hide') ? Constants::CHECKED : ''; ?>
                           value="hide"/>
                    <label for="sb-filterhide"><?php _e('Minimise filter', 'sermon-browser'); ?></label>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render hide no attachments field.
     *
     * @return void
     */
    private function renderHideNoAttachmentsField(): void
    {
        ?>
        <div style="display: flex; gap: 1em; margin-bottom: 1em;">
            <label for="sb-hide-no-attachments" style="min-width: 180px; text-align: right; padding-top: 0.5em;">
                <?php _e('Hide sermons without attachments?', 'sermon-browser') ?>
            </label>
            <div style="flex: 1;">
                <input type="checkbox" id="sb-hide-no-attachments" name="hide_no_attachments"
                    <?php echo sb_get_option('hide_no_attachments') ? Constants::CHECKED : ''; ?>
                       value="1"/>
            </div>
        </div>
        <?php
    }

    /**
     * Render PHP.ini warnings.
     *
     * @return void
     */
    private function renderPhpIniWarnings(): void
    {
        $allow_uploads = ini_get('file_uploads');
        $max_filesize = sb_return_kbytes(ini_get('upload_max_filesize'));
        $max_post = sb_return_kbytes(ini_get('post_max_size'));
        $max_execution = ini_get('max_execution_time');
        $max_input = ini_get('max_input_time');
        $max_memory = sb_return_kbytes(ini_get('memory_limit'));
        $checkSermonUpload = sb_checkSermonUploadable();

        // Upload folder errors
        if ($checkSermonUpload === "unwriteable") {
            $msg = IS_MU
                ? __('The upload folder is not writeable. You need to specify a folder that you have permissions to write to.', 'sermon-browser')
                : __('The upload folder is not writeable. You need to specify a folder that you have permissions to write to, or CHMOD this folder to 666 or 777.', 'sermon-browser');
            echo FormHelpers::displayError($msg);
        } elseif ($checkSermonUpload === "notexist") {
            echo FormHelpers::displayError(
                __('The upload folder you have specified does not exist.', 'sermon-browser')
            );
        }

        // Upload permissions error
        if ($allow_uploads === '0') {
            $msg = IS_MU
                ? __('Your administrator does not allow file uploads. You will need to upload via FTP.', 'sermon-browser')
                : __('Your php.ini file does not allow uploads. Please change file_uploads in php.ini.', 'sermon-browser');
            echo FormHelpers::displayError($msg);
        }

        // File size warnings
        if (IS_MU) {
            $max_filesize = min($max_filesize, $max_post);
        }
        if ($max_filesize < 15360) {
            $suffix = IS_MU
                ? __('k. You may need to upload via FTP.', 'sermon-browser')
                : __('k. Please change upload_max_filesize to at least 15M in php.ini.', 'sermon-browser');
            echo FormHelpers::displayWarning(
                __('The maximum file size you can upload is only ', 'sermon-browser') . $max_filesize . $suffix
            );
        }

        // Standard (non-MU) specific warnings
        if (!IS_MU) {
            if ($max_post < 15360) {
                echo FormHelpers::displayWarning(
                    __('The maximum file size you send through the browser is only ', 'sermon-browser') .
                    $max_post .
                    __('k. Please change post_max_size to at least 15M in php.ini.', 'sermon-browser')
                );
            }

            if ($max_input < 600 && $max_input != -1) {
                echo FormHelpers::displayWarning(
                    __('The maximum time allowed for an upload script to run is only ', 'sermon-browser') .
                    $max_input .
                    __(' seconds. Please change max_input_time to at least 600 in php.ini.', 'sermon-browser')
                );
            }

            if ($max_memory < 16384) {
                echo FormHelpers::displayWarning(
                    __('The maximum amount of memory allowed is only ', 'sermon-browser') .
                    $max_memory .
                    __('k. Please change memory_limit to at least 16M in php.ini.', 'sermon-browser')
                );
            }
        }

        // Execution time warning (both MU and standard)
        if (IS_MU) {
            $max_execution = (($max_execution < $max_input) || $max_input == -1) ? $max_execution : $max_input;
        }
        if ($max_execution < 600) {
            $suffix = IS_MU
                ? __(' seconds. If your files take longer than this to upload, you will need to upload via FTP.', 'sermon-browser')
                : __(' seconds. Please change max_execution_time to at least 600 in php.ini.', 'sermon-browser');
            echo FormHelpers::displayWarning(
                __('The maximum time allowed for any script to run is only ', 'sermon-browser') . $max_execution . $suffix
            );
        }
    }

    /**
     * Render import options fields.
     *
     * @return void
     */
    private function renderImportOptions(): void
    {
        ?>
        <div style="display: flex; gap: 1em; margin-bottom: 1em;">
            <label for="sb-import-prompt" style="min-width: 280px; text-align: right; padding-top: 0.5em;">
                <?php _e('Add files prompt to top of Add Sermon page?', 'sermon-browser') ?>
            </label>
            <div style="flex: 1;">
                <input type="checkbox" id="sb-import-prompt" name="import_prompt"
                    <?php echo sb_get_option('import_prompt') ? Constants::CHECKED : ''; ?>
                       value="1"/>
            </div>
        </div>
        <div style="display: flex; gap: 1em; margin-bottom: 1em;">
            <label for="sb-import-title" style="min-width: 280px; text-align: right; padding-top: 0.5em;">
                <?php _e('Use title tag for sermon title?', 'sermon-browser') ?>
            </label>
            <div style="flex: 1;">
                <input type="checkbox" id="sb-import-title" name="import_title"
                    <?php echo sb_get_option('import_title') ? Constants::CHECKED : ''; ?>
                       value="1"/>
            </div>
        </div>
        <div style="display: flex; gap: 1em; margin-bottom: 1em;">
            <label for="sb-import-artist" style="min-width: 280px; text-align: right; padding-top: 0.5em;">
                <?php _e('Use artist tag for preacher?', 'sermon-browser') ?>
            </label>
            <div style="flex: 1;">
                <input type="checkbox" id="sb-import-artist" name="import_artist"
                    <?php echo sb_get_option('import_artist') ? Constants::CHECKED : ''; ?>
                       value="1"/>
            </div>
        </div>
        <div style="display: flex; gap: 1em; margin-bottom: 1em;">
            <label for="sb-import-album" style="min-width: 280px; text-align: right; padding-top: 0.5em;">
                <?php _e('Use album tag for series?', 'sermon-browser') ?>
            </label>
            <div style="flex: 1;">
                <input type="checkbox" id="sb-import-album" name="import_album"
                    <?php echo sb_get_option('import_album') ? Constants::CHECKED : ''; ?>
                       value="1"/>
            </div>
        </div>
        <div style="display: flex; gap: 1em; margin-bottom: 1em;">
            <label for="sb-import-comments" style="min-width: 280px; text-align: right; padding-top: 0.5em;">
                <?php _e('Use comments tag for sermon description?', 'sermon-browser') ?>
            </label>
            <div style="flex: 1;">
                <input type="checkbox" id="sb-import-comments" name="import_comments"
                    <?php echo sb_get_option('import_comments') ? Constants::CHECKED : ''; ?>
                       value="1"/>
            </div>
        </div>
        <div style="display: flex; gap: 1em; margin-bottom: 1em;">
            <label for="sb-import-filename" style="min-width: 280px; text-align: right; padding-top: 0.5em;">
                <?php _e('Attempt to extract date from filename', 'sermon-browser') ?>
            </label>
            <div style="flex: 1;">
                <select id="sb-import-filename" name="import_filename">
                    <?php
                    $filename_options = [
                        'none' => __('Disabled', 'sermon-browser'),
                        'uk'   => __('UK-formatted date (dd-mm-yyyy)', 'sermon-browser'),
                        'us'   => __('US-formatted date (mm-dd-yyyy)', 'sermon-browser'),
                        'int'  => __('International formatted date (yyyy-mm-dd)', 'sermon-browser'),
                    ];
                    $saved_option = sb_get_option('import_filename');

                    foreach ($filename_options as $option => $text) {
                        $sel = ($saved_option === $option) ? ' ' . Constants::SELECTED : '';
                        echo "<option value=\"{$option}\"{$sel}>{$text}</option>\n";
                    }
                    ?>
                </select>
                <br/>
                <?php _e('(Use if you name your files something like 2008-11-06-eveningsermon.mp3)', 'sermon-browser'); ?>
            </div>
        </div>
        <?php
    }
}
