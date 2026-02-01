<?php

declare(strict_types=1);

namespace SermonBrowser\Http\Handler;

use SermonBrowser\Facades\File;

/**
 * Handles external URL show/redirect requests.
 *
 * Redirects the browser to an external URL without forcing download.
 * SECURITY: Only URLs registered in the database can be redirected to (open redirect protection).
 *
 * @since 0.6.0
 */
class UrlRedirectHandler
{
    use HandlerTrait;

    /**
     * Handle a URL redirect request.
     *
     * Validates the URL exists in database, increments download count,
     * and redirects to the external URL.
     *
     * @return never
     */
    public static function handle(): void
    {
        $requestedUrl = rawurldecode($_GET['url'] ?? '');

        // SECURITY: Validate URL exists in database (open redirect protection)
        $file = File::findOneBy('name', $requestedUrl);

        if ($file === null || $file->type !== 'url') {
            wp_die(
                esc_html__('Invalid or unregistered URL.', 'sermon-browser'),
                esc_html__('URL not found', 'sermon-browser'),
                ['response' => 404]
            );
        }

        self::incrementDownloadCount($requestedUrl);

        // Use wp_redirect for proper redirect handling
        $safeUrl = esc_url_raw($file->name);
        if (wp_redirect($safeUrl, 302, 'Sermon Browser')) {
            exit;
        }

        // Fallback if redirect fails
        wp_die(
            esc_html__('Unable to redirect to external URL.', 'sermon-browser'),
            esc_html__('Redirect failed', 'sermon-browser'),
            ['response' => 500]
        );
    }
}
