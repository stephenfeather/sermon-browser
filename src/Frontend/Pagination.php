<?php

declare(strict_types=1);

namespace SermonBrowser\Frontend;

/**
 * Pagination utility for Sermon Browser.
 *
 * Provides static methods for printing next/previous page navigation links.
 *
 * @since 1.0.0
 */
final class Pagination
{
    /**
     * Print link to next page of sermon results.
     *
     * Outputs an HTML anchor tag linking to the next page if one exists.
     *
     * @param int $limit Number of sermons per page. If 0, uses the option value.
     *
     * @return void
     */
    public static function printNextPageLink(int $limit = 0): void
    {
        global $record_count;

        if ($limit === 0) {
            $limit = (int) sb_get_option('sermons_per_page');
        }

        $current = isset($_REQUEST['pagenum']) ? (int) $_REQUEST['pagenum'] : 1;

        if ($current < ceil($record_count / $limit)) {
            $url = sb_build_url(['pagenum' => ++$current], false);
            echo '<a href="' . $url . '">' . __('Next page', 'sermon-browser') . ' &raquo;</a>';
        }
    }

    /**
     * Print link to previous page of sermon results.
     *
     * Outputs an HTML anchor tag linking to the previous page if one exists.
     *
     * @param int $limit Number of sermons per page. If 0, uses the option value.
     *
     * @return void
     */
    public static function printPrevPageLink(int $limit = 0): void
    {
        if ($limit === 0) {
            $limit = (int) sb_get_option('sermons_per_page');
        }

        $current = isset($_REQUEST['pagenum']) ? (int) $_REQUEST['pagenum'] : 1;

        if ($current > 1) {
            $url = sb_build_url(['pagenum' => --$current], false);
            echo '<a href="' . $url . '">&laquo; ' . __('Previous page', 'sermon-browser') . '</a>';
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
