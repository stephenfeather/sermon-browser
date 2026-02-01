<?php

/**
 * Admin Notices Handler.
 *
 * Handles display of admin notices and alerts.
 *
 * @package SermonBrowser\Admin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin;

/**
 * Class AdminNotices
 *
 * Manages admin notices and alerts for SermonBrowser.
 */
final class AdminNotices
{
    /**
     * Render admin alerts and notices.
     *
     * Displays alerts for configuration issues such as missing MP3 shortcode
     * or display page.
     *
     * @return void
     */
    public static function render(): void
    {
        self::renderMp3ShortcodeAlert();
        self::renderDisplayPageAlert();
    }

    /**
     * Render MP3 shortcode configuration alerts.
     *
     * @return void
     */
    private static function renderMp3ShortcodeAlert(): void
    {
        $mp3Shortcode = sb_get_option('mp3_shortcode');

        if (stripos($mp3Shortcode, '%SERMONURL%') === false) {
            echo '<div id="message" class="updated fade"><p><b>';
            _e(
                'Error:</b> The MP3 shortcode must link to individual sermon files. You do this by including '
                . '<span style="color:red">%SERMONURL%</span> in your shortcode (e.g. [audio mp3="%SERMONURL%"]). '
                . 'SermonBrowser will then replace %SERMONURL% with a link to each sermon.',
                'sermon-browser'
            );
            echo '</div>';
            return;
        }

        if (do_shortcode($mp3Shortcode) === $mp3Shortcode) {
            echo '<div id="message" class="updated fade"><p><b>';
            _e(
                'Error:</b> You have specified a custom MP3 shortcode, but Wordpress doesn&#146;t know how to '
                . 'interpret it. Make sure the shortcode is correct, and that the appropriate plugin is activated.',
                'sermon-browser'
            );
            echo '</div>';
        }
    }

    /**
     * Render display page configuration alert.
     *
     * @return void
     */
    private static function renderDisplayPageAlert(): void
    {
        if (sb_display_url() === "") {
            $createPageUrl = admin_url('page-new.php');
            echo '<div id="message" class="updated"><p><b>' . __('Hint:', 'sermon-browser') . '</b> '
                . sprintf(
                    __('%sCreate a page%s that includes the shortcode [sermons], so that SermonBrowser knows where '
                    . 'to display the sermons on your site.', 'sermon-browser'),
                    '<a href="' . $createPageUrl . '">',
                    '</a>'
                )
                . '</div>';
        }
    }

    // =========================================================================
    // Prevent instantiation
    // =========================================================================

    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct()
    {
        // Static class - cannot be instantiated
    }
}
