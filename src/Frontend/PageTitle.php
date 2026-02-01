<?php

declare(strict_types=1);

namespace SermonBrowser\Frontend;

use SermonBrowser\Facades\Sermon;

/**
 * Page title modifier for Sermon Browser.
 *
 * Modifies the page title when viewing a single sermon to include
 * the sermon title and preacher name.
 *
 * @since 1.0.0
 */
final class PageTitle
{
    /**
     * Modify page title for single sermon pages.
     *
     * Appends the sermon title and preacher name to the page title
     * when viewing a single sermon.
     *
     * @param string $title The original page title.
     *
     * @return string The modified title.
     */
    public static function modify(string $title): string
    {
        if (isset($_GET['sermon_id'])) {
            $id = (int) $_GET['sermon_id'];
            $sermon = Sermon::findWithRelations($id);

            if ($sermon) {
                return $title . ' (' . stripslashes($sermon->title) . ' - ' . stripslashes($sermon->preacher_name) . ')';
            }

            return $title . ' (' . __('No sermons found.', 'sermon-browser') . ')';
        }

        return $title;
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
