<?php

declare(strict_types=1);

namespace SermonBrowser\Http\Handler;

use SermonBrowser\Facades\File;

/**
 * Shared functionality for HTTP request handlers.
 *
 * Provides common methods for error handling and download tracking.
 *
 * @since 0.6.0
 */
trait HandlerTrait
{
    /**
     * Display a 404 not found error and terminate.
     *
     * @param string $message The error message.
     * @return never
     */
    protected static function notFound(string $message): void
    {
        wp_die(
            esc_html($message) . ' ' . esc_html__('not found', 'sermon-browser'),
            esc_html__('File not found', 'sermon-browser'),
            ['response' => 404]
        );
    }

    /**
     * Display a URL not found error and terminate.
     *
     * @param string $message The error message.
     * @return never
     */
    protected static function urlNotFound(string $message): void
    {
        wp_die(
            esc_html($message) . ' ' . esc_html__('not found', 'sermon-browser'),
            esc_html__('URL not found', 'sermon-browser'),
            ['response' => 404]
        );
    }

    /**
     * Increment download count for a file.
     *
     * Only increments for non-privileged users (not editors/publishers).
     *
     * @param string $name The file name or URL.
     */
    protected static function incrementDownloadCount(string $name): void
    {
        if (!(current_user_can('edit_posts') || current_user_can('publish_posts'))) {
            File::incrementCountByName($name);
        }
    }
}
