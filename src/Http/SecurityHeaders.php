<?php

/**
 * Security Headers.
 *
 * Adds security headers to protect against common web vulnerabilities.
 *
 * @package SermonBrowser
 * @since 0.7.1
 */

declare(strict_types=1);

namespace SermonBrowser\Http;

/**
 * Class SecurityHeaders
 *
 * Adds security headers for plugin responses including:
 * - X-Content-Type-Options: nosniff (prevents MIME-type sniffing)
 * - X-Frame-Options: SAMEORIGIN (prevents clickjacking)
 *
 * @since 0.7.1
 */
class SecurityHeaders
{
    /**
     * Security headers to add.
     *
     * @var array<string, string>
     */
    private const HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
    ];

    /**
     * Register security header hooks.
     *
     * Hooks into WordPress to add security headers for:
     * - Plugin admin pages
     * - REST API responses
     */
    public static function register(): void
    {
        // Add headers for plugin admin pages.
        add_action('admin_init', [self::class, 'addAdminHeaders'], 1);

        // Add headers for REST API responses.
        add_filter('rest_pre_serve_request', [self::class, 'addRestHeaders'], 10, 4);
    }

    /**
     * Add security headers for admin pages.
     *
     * Only adds headers for Sermon Browser admin pages to avoid
     * conflicts with other plugins.
     */
    public static function addAdminHeaders(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $page = $_GET['page'] ?? '';

        // Only add headers for Sermon Browser admin pages.
        if (strpos($page, 'sermon') !== 0) {
            return;
        }

        self::send();
    }

    /**
     * Add security headers for REST API responses.
     *
     * Only adds headers for Sermon Browser REST endpoints.
     *
     * @param mixed             $served  Whether the request has already been served.
     * @param \WP_REST_Response $result  Result to send to the client.
     * @param \WP_REST_Request  $request Request used to generate the response.
     * @param \WP_REST_Server   $server  Server instance.
     * @return mixed
     */
    public static function addRestHeaders($served, $result, $request, $server)
    {
        $route = $request->get_route();

        // Only add headers for Sermon Browser REST endpoints.
        if (strpos($route, '/sermon-browser/') === 0) {
            self::send();
        }

        return $served;
    }

    /**
     * Send security headers.
     *
     * Can be called directly by file download handlers or other
     * components that generate their own responses.
     *
     * @param bool $force Send headers even if already sent.
     */
    public static function send(bool $force = false): void
    {
        // Don't send if headers already sent (unless forced).
        if (!$force && headers_sent()) {
            return;
        }

        foreach (self::HEADERS as $name => $value) {
            header("{$name}: {$value}");
        }
    }

    /**
     * Get the security headers array.
     *
     * Useful for testing or for components that need to set headers differently.
     *
     * @return array<string, string>
     */
    public static function getHeaders(): array
    {
        return self::HEADERS;
    }
}
