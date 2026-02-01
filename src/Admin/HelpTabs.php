<?php

/**
 * Help Tabs for SermonBrowser admin pages.
 *
 * Adds contextual help tabs to WordPress admin screens.
 *
 * @package SermonBrowser\Admin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin;

use WP_Screen;

/**
 * Class HelpTabs
 *
 * Registers help tabs and sidebar for SermonBrowser admin pages.
 */
final class HelpTabs
{
    /**
     * Register help tabs for the current screen.
     *
     * @param WP_Screen $screen Current screen object.
     *
     * @return void
     */
    public static function register(WP_Screen $screen): void
    {
        if (!isset($_GET['page'])) {
            return;
        }

        $page = $_GET['page'];

        // Only process sermon-browser pages.
        if (strpos($page, 'sermon-browser/') !== 0) {
            return;
        }

        $content = self::getHelpContent($page);

        if (!empty($content)) {
            $screen->add_help_tab([
                'id'      => 'sermon-browser-help',
                'title'   => __('SermonBrowser Help', 'sermon-browser'),
                'content' => '<p>' . $content . '</p>',
            ]);
        }

        // Add sidebar with useful links.
        $screen->set_help_sidebar(self::getSidebar());
    }

    /**
     * Get help content for a specific page.
     *
     * @param string $page The page slug.
     *
     * @return string Help content HTML.
     */
    private static function getHelpContent(string $page): string
    {
        switch ($page) {
            case 'sermon-browser/sermon.php':
                return __('From this page you can edit or delete any of your sermons. The most recent sermons are found at the top. Use the filter options to quickly find the one you want.', 'sermon-browser');

            case 'sermon-browser/new_sermon.php':
            case 'sermon-browser/files.php':
            case 'sermon-browser/preachers.php':
            case 'sermon-browser/manage.php':
            case 'sermon-browser/options.php':
                $content = __('It&#146;s important that these options are set correctly, as otherwise SermonBrowser won&#146;t behave as you expect.', 'sermon-browser') . '<ul>';
                $content .= '<li>' . __('The upload folder would normally be <b>wp-content/uploads/sermons</b>', 'sermon-browser') . '</li>';
                $content .= '<li>' . __('You should only change the public podcast feed if you re-direct your podcast using a service like Feedburner. Otherwise it should be the same as the private podcast feed.', 'sermon-browser') . '</li>';
                $content .= '<li>' . __('The MP3 shortcode you need will be in the documation of your favourite MP3 plugin. Use the tag %SERMONURL% in place of the URL of the MP3 file (e.g. [haiku url="%SERMONURL%"] or [audio:%SERMONURL%]).', 'sermon-browser') . '</li></ul>';
                return $content;

            case 'sermon-browser/templates.php':
                return sprintf(__('Template editing is one of the most powerful features of SermonBrowser. Be sure to look at the complete list of %stemplate tags%s.', 'sermon-browser'), '<a href="http://www.sermonbrowser.com/customisation/">', '</a>');

            default:
                return '';
        }
    }

    /**
     * Get help sidebar HTML.
     *
     * @return string Sidebar HTML.
     */
    private static function getSidebar(): string
    {
        $sidebar = '<p><strong>' . __('For more information:', 'sermon-browser') . '</strong></p>';
        $sidebar .= '<p><a href="http://www.sermonbrowser.com/tutorials/">' . __('Tutorial Screencasts', 'sermon-browser') . '</a></p>';
        $sidebar .= '<p><a href="http://www.sermonbrowser.com/faq/">' . __('Frequently Asked Questions', 'sermon-browser') . '</a></p>';
        $sidebar .= '<p><a href="http://www.sermonbrowser.com/forum/">' . __('Support Forum', 'sermon-browser') . '</a></p>';
        $sidebar .= '<p><a href="http://www.sermonbrowser.com/customisation/">' . __('Shortcode syntax', 'sermon-browser') . '</a></p>';
        $sidebar .= '<p><a href="http://www.sermonbrowser.com/donate/">' . __('Donate', 'sermon-browser') . '</a></p>';

        return $sidebar;
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
