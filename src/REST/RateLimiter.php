<?php

/**
 * Rate Limiter for REST API.
 *
 * Provides rate limiting for REST API endpoints using WordPress transients.
 * Different limits are applied based on authentication status and endpoint type.
 *
 * @package SermonBrowser\REST
 * @since 0.7.1
 */

declare(strict_types=1);

namespace SermonBrowser\REST;

use SermonBrowser\Constants;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class RateLimiter
 *
 * Handles rate limiting for REST API endpoints using WordPress transients.
 */
class RateLimiter
{
    /**
     * Check if the request is within rate limits.
     *
     * Returns true if allowed, or a WP_Error if rate limited.
     *
     * @param WP_REST_Request $request The REST request.
     * @param bool $isSearchEndpoint Whether this is the search endpoint (stricter limits).
     * @return true|WP_Error True if allowed, WP_Error with 429 status if rate limited.
     */
    public function check(WP_REST_Request $request, bool $isSearchEndpoint = false): true|WP_Error
    {
        $ip = $this->getClientIp($request);
        $isAuthenticated = is_user_logged_in();
        $limit = $this->getLimit($isAuthenticated, $isSearchEndpoint);
        $window = Constants::RATE_LIMIT_WINDOW;

        $key = $this->getTransientKey($ip, $isSearchEndpoint);
        $data = $this->getTransientData($key);

        $now = time();

        // Reset if window has expired.
        if ($data === null || $data['reset'] <= $now) {
            $data = [
                'count' => 0,
                'reset' => $now + $window,
            ];
        }

        // Increment count.
        $data['count']++;

        // Save to transient.
        set_transient($key, $data, $window);

        // Check if over limit.
        if ($data['count'] > $limit) {
            $retryAfter = $data['reset'] - $now;

            return new WP_Error(
                'rest_rate_limit_exceeded',
                sprintf(
                    /* translators: %d is the rate limit number */
                    __('Rate limit exceeded. Maximum %d requests per minute allowed.', 'sermon-browser'),
                    $limit
                ),
                [
                    'status' => 429,
                    'retry_after' => $retryAfter,
                    'limit' => $limit,
                    'remaining' => 0,
                    'reset' => $data['reset'],
                ]
            );
        }

        return true;
    }

    /**
     * Add rate limit headers to a response.
     *
     * Adds X-RateLimit-Limit, X-RateLimit-Remaining, and X-RateLimit-Reset headers.
     *
     * @param WP_REST_Response $response The response object.
     * @param WP_REST_Request $request The request object.
     * @param bool $isSearchEndpoint Whether this is the search endpoint.
     * @return WP_REST_Response The response with rate limit headers.
     */
    public function addHeaders(
        WP_REST_Response $response,
        WP_REST_Request $request,
        bool $isSearchEndpoint = false
    ): WP_REST_Response {
        $ip = $this->getClientIp($request);
        $isAuthenticated = is_user_logged_in();
        $limit = $this->getLimit($isAuthenticated, $isSearchEndpoint);

        $key = $this->getTransientKey($ip, $isSearchEndpoint);
        $data = $this->getTransientData($key);

        $now = time();

        if ($data === null || $data['reset'] <= $now) {
            $remaining = $limit;
            $reset = $now + Constants::RATE_LIMIT_WINDOW;
        } else {
            $remaining = max(0, $limit - $data['count']);
            $reset = $data['reset'];
        }

        $response->header('X-RateLimit-Limit', (string) $limit);
        $response->header('X-RateLimit-Remaining', (string) $remaining);
        $response->header('X-RateLimit-Reset', (string) $reset);

        return $response;
    }

    /**
     * Get the rate limit for the current user.
     *
     * @param bool $isAuthenticated Whether the user is authenticated.
     * @param bool $isSearchEndpoint Whether this is the search endpoint.
     * @return int The rate limit.
     */
    public function getLimit(bool $isAuthenticated, bool $isSearchEndpoint = false): int
    {
        if ($isSearchEndpoint) {
            return $isAuthenticated
                ? Constants::RATE_LIMIT_SEARCH_AUTHENTICATED
                : Constants::RATE_LIMIT_SEARCH_ANONYMOUS;
        }

        return $isAuthenticated
            ? Constants::RATE_LIMIT_AUTHENTICATED
            : Constants::RATE_LIMIT_ANONYMOUS;
    }

    /**
     * Get the client IP address.
     *
     * Checks X-Forwarded-For and other headers for clients behind proxies.
     * Can be customized via the 'sermon_browser_rate_limit_ip' filter.
     *
     * @param WP_REST_Request $request The REST request.
     * @return string The client IP address.
     */
    protected function getClientIp(WP_REST_Request $request): string
    {
        // Check for forwarded IP headers (for clients behind proxies).
        $forwardedFor = $request->get_header('X-Forwarded-For');
        if (!empty($forwardedFor)) {
            // X-Forwarded-For can contain multiple IPs, take the first.
            $ips = explode(',', $forwardedFor);
            $ip = trim($ips[0]);
        } else {
            $realIp = $request->get_header('X-Real-IP');
            if (!empty($realIp)) {
                $ip = trim($realIp);
            } else {
                // Fall back to REMOTE_ADDR.
                $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
            }
        }

        /**
         * Filter the client IP for rate limiting.
         *
         * @param string $ip The detected IP address.
         * @param WP_REST_Request $request The REST request.
         */
        return (string) apply_filters('sermon_browser_rate_limit_ip', $ip, $request);
    }

    /**
     * Get the transient key for rate limiting.
     *
     * @param string $ip The client IP address.
     * @param bool $isSearchEndpoint Whether this is the search endpoint.
     * @return string The transient key.
     */
    protected function getTransientKey(string $ip, bool $isSearchEndpoint): string
    {
        $suffix = $isSearchEndpoint ? '_search' : '';
        return 'sb_rate_' . md5($ip) . $suffix;
    }

    /**
     * Get transient data.
     *
     * @param string $key The transient key.
     * @return array<string, int>|null The transient data or null if not found.
     */
    protected function getTransientData(string $key): ?array
    {
        $data = get_transient($key);

        if ($data === false || !is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * Reset rate limit for an IP (useful for testing).
     *
     * @param string $ip The IP address to reset.
     * @param bool $isSearchEndpoint Whether to reset the search endpoint limit.
     * @return bool True if reset was successful.
     */
    public function reset(string $ip, bool $isSearchEndpoint = false): bool
    {
        $key = $this->getTransientKey($ip, $isSearchEndpoint);
        return delete_transient($key);
    }
}
