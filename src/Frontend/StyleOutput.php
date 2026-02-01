<?php

declare(strict_types=1);

namespace SermonBrowser\Frontend;

/**
 * Handles CSS style output for Sermon Browser.
 *
 * Provides methods for outputting custom CSS with proper caching headers.
 *
 * @since 1.0.0
 */
class StyleOutput
{
    /**
     * Cache duration in seconds (7 days).
     */
    private const CACHE_MAX_AGE = 60 * 60 * 24 * 7;

    /**
     * Get the custom CSS style content.
     *
     * @return string
     */
    public static function getStyleContent(): string
    {
        return (string) sb_get_option('css_style');
    }

    /**
     * Get the last modified timestamp for styles.
     *
     * @return int
     */
    public static function getLastModified(): int
    {
        return (int) sb_get_option('style_date_modified');
    }

    /**
     * Check if a 304 Not Modified response should be sent.
     *
     * @param int    $lastModified     The last modified timestamp.
     * @param string $ifModifiedSince  The If-Modified-Since header value.
     * @return bool
     */
    public static function shouldReturn304(int $lastModified, string $ifModifiedSince): bool
    {
        if (empty($ifModifiedSince)) {
            return false;
        }

        $headerTime = strtotime($ifModifiedSince);

        return $headerTime !== false && $headerTime >= $lastModified;
    }

    /**
     * Get the cache max-age value in seconds.
     *
     * @return int
     */
    public static function getCacheMaxAge(): int
    {
        return self::CACHE_MAX_AGE;
    }

    /**
     * Format a timestamp as a GMT date string.
     *
     * @param int $timestamp The Unix timestamp.
     * @return string The formatted date string.
     */
    public static function formatGmtDate(int $timestamp): string
    {
        return gmdate("D, d M Y H:i:s", $timestamp) . ' GMT';
    }

    /**
     * Output the CSS with appropriate headers.
     *
     * @return void
     */
    public static function output(): void
    {
        header('Content-Type: text/css');

        $lastModified = self::getLastModified();
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';

        if (self::shouldReturn304($lastModified, $ifModifiedSince)) {
            if (php_sapi_name() === 'CGI') {
                header("Status: 304 Not Modified");
            } else {
                header("HTTP/1.0 304 Not Modified");
            }
        } else {
            $gmtDate = self::formatGmtDate($lastModified);
            header('Last-Modified: ' . $gmtDate, true, 200);
        }

        $expires = self::getCacheMaxAge();
        header("Cache-Control: max-age=" . $expires);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');

        echo self::getStyleContent();
        exit;
    }
}
