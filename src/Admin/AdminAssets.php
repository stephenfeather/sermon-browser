<?php

/**
 * Admin Assets Handler.
 *
 * Handles enqueuing of admin-specific JavaScript and CSS assets.
 *
 * @package SermonBrowser\Admin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin;

/**
 * Class AdminAssets
 *
 * Manages admin asset loading for SermonBrowser pages.
 */
final class AdminAssets
{
    /**
     * Enqueue admin scripts and styles.
     *
     * Loads necessary JavaScript and CSS for SermonBrowser admin pages.
     *
     * @return void
     */
    public static function enqueue(): void
    {
        if (!self::isSermonBrowserPage()) {
            return;
        }

        wp_enqueue_script('jquery');

        // Enqueue admin AJAX module (Phase 3).
        wp_enqueue_script(
            'sb-admin-ajax',
            SB_PLUGIN_URL . '/assets/js/admin-ajax.js',
            array('jquery'),
            SB_CURRENT_VERSION,
            true
        );

        // Localize nonces and i18n for AJAX handlers.
        wp_localize_script('sb-admin-ajax', 'sbAjaxSettings', self::getAjaxSettings());

        // Additional assets for sermon editor page.
        if (self::isSermonEditorPage()) {
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style(
                'jquery-ui-css',
                'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css',
                array(),
                '1.13.2'
            );
            wp_enqueue_style('sb_style');
        }
    }

    /**
     * Check if current page is a SermonBrowser admin page.
     *
     * @return bool
     */
    private static function isSermonBrowserPage(): bool
    {
        if (!isset($_REQUEST['page'])) {
            return false;
        }

        return substr($_REQUEST['page'], 14) === 'sermon-browser';
    }

    /**
     * Check if current page is the sermon editor page.
     *
     * @return bool
     */
    private static function isSermonEditorPage(): bool
    {
        return isset($_REQUEST['page']) && $_REQUEST['page'] === 'sermon-browser/new_sermon.php';
    }

    /**
     * Get AJAX settings for localization.
     *
     * @return array<string, mixed>
     */
    private static function getAjaxSettings(): array
    {
        return array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'preacherNonce' => wp_create_nonce('sb_preacher_nonce'),
            'seriesNonce'   => wp_create_nonce('sb_series_nonce'),
            'serviceNonce'  => wp_create_nonce('sb_service_nonce'),
            'fileNonce'     => wp_create_nonce('sb_file_nonce'),
            'sermonNonce'   => wp_create_nonce('sb_sermon_nonce'),
            'i18n'          => self::getI18nStrings(),
        );
    }

    /**
     * Get internationalized strings for JavaScript.
     *
     * @return array<string, string>
     */
    private static function getI18nStrings(): array
    {
        return array(
            'edit'          => __('Edit', 'sermon-browser'),
            'delete'        => __('Delete', 'sermon-browser'),
            'view'          => __('View', 'sermon-browser'),
            'rename'        => __('Rename', 'sermon-browser'),
            'createSermon'  => __('Create sermon', 'sermon-browser'),
            'noResults'     => __('No results', 'sermon-browser'),
            'confirmDelete' => __('Are you sure?', 'sermon-browser'),
            'previous'      => __('&laquo; Previous', 'sermon-browser'),
            'next'          => __('Next &raquo;', 'sermon-browser'),
        );
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
