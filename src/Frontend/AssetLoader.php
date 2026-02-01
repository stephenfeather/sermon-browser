<?php

declare(strict_types=1);

namespace SermonBrowser\Frontend;

use SermonBrowser\Config\OptionsManager;

/**
 * Asset Loader for Sermon Browser frontend.
 *
 * Handles loading of JavaScript and CSS assets on pages
 * that use the sermon browser shortcode or widgets.
 *
 * @since 1.0.0
 */
final class AssetLoader
{
    /**
     * Add JavaScript and CSS headers where required.
     *
     * Outputs RSS feed links for podcasts and enqueues necessary
     * scripts and styles for sermon pages.
     *
     * @return void
     */
    public static function addHeaders(): void
    {
        global $post, $wp_scripts;

        if (!isset($post->ID) || $post->ID === '') {
            return;
        }

        echo "<!-- Added by SermonBrowser (version " . SB_CURRENT_VERSION . ") - http://www.sermonbrowser.com/ -->\r";
        echo '<link rel="alternate" type="application/rss+xml" title="' . __('Sermon podcast', 'sermon-browser') . '" href="' . OptionsManager::get('podcast_url') . "\" />\r";

        wp_enqueue_style('sb_style');

        // Check if post contains sermon shortcode.
        $hasSermonShortcode = isset($post->post_content) && strpos($post->post_content, '[sermons') !== false;

        if ($hasSermonShortcode) {
            self::enqueueSermonPageAssets();
        } else {
            self::maybeEnqueueWidgetAssets();
        }
    }

    /**
     * Enqueue assets for pages with sermon shortcode.
     *
     * @return void
     */
    private static function enqueueSermonPageAssets(): void
    {
        global $post;

        if (OptionsManager::get('filter_type') === 'dropdown') {
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css', [], '1.13.2');
        }

        $hasFilterParams = isset($_REQUEST['title'])
            || isset($_REQUEST['preacher'])
            || isset($_REQUEST['date'])
            || isset($_REQUEST['enddate'])
            || isset($_REQUEST['series'])
            || isset($_REQUEST['service'])
            || isset($_REQUEST['book'])
            || isset($_REQUEST['stag']);

        if ($hasFilterParams) {
            echo '<link rel="alternate" type="application/rss+xml" title="' . __('Custom sermon podcast', 'sermon-browser') . '" href="' . UrlBuilder::podcastUrl() . "\" />\r";
        }

        wp_enqueue_script('jquery');
    }

    /**
     * Enqueue jQuery if sermon browser popular widget is active.
     *
     * @return void
     */
    private static function maybeEnqueueWidgetAssets(): void
    {
        $sidebarsWidgets = wp_get_sidebars_widgets();

        if (isset($sidebarsWidgets['wp_inactive_widgets'])) {
            unset($sidebarsWidgets['wp_inactive_widgets']);
        }

        if (!is_array($sidebarsWidgets)) {
            return;
        }

        foreach ($sidebarsWidgets as $widgets) {
            if (is_array($widgets) && in_array('sermon-browser-popular', $widgets, true)) {
                wp_enqueue_script('jquery');
                break;
            }
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
