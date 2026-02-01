<?php

/**
 * Tag Cleanup for SermonBrowser.
 *
 * Provides functionality to clean up unused tags.
 *
 * @package SermonBrowser\Admin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin;

use SermonBrowser\Facades\Tag;

/**
 * Class TagCleanup
 *
 * Handles deletion of unused tags.
 */
final class TagCleanup
{
    /**
     * Delete all unused tags.
     *
     * Tags are considered unused when they are not attached to any sermon.
     *
     * @return int Number of tags deleted.
     */
    public static function cleanup(): int
    {
        return Tag::deleteUnused();
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
