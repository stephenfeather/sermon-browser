<?php

declare(strict_types=1);

namespace SermonBrowser\Frontend;

/**
 * URL Builder utility for Sermon Browser frontend links.
 *
 * Provides static methods for building search URLs, sermon links,
 * preacher links, series links, and other navigation URLs.
 *
 * @since 1.0.0
 */
final class UrlBuilder
{
    /**
     * Word list for URL building - parameters that are preserved in URL building.
     *
     * @var array<string>
     */
    private const URL_PARAMS = [
        'preacher',
        'title',
        'date',
        'enddate',
        'series',
        'service',
        'sortby',
        'dir',
        'book',
        'stag',
        'podcast',
    ];

    /**
     * Build a URL for search links.
     *
     * Merges provided parameters with existing GET/POST parameters,
     * filtering based on the URL parameter whitelist.
     *
     * @param array<string, mixed> $params Parameters to include in the URL.
     * @param bool                 $clear  If true, only include params from $params array.
     *
     * @return string The built URL, escaped for safe output.
     */
    public static function build(array $params, bool $clear = false): string
    {
        $merged = array_merge((array) $_GET, (array) $_POST, $params);
        $queryParts = [];

        foreach ($merged as $key => $value) {
            if (array_key_exists($key, $params) || (in_array($key, self::URL_PARAMS, true) && !$clear)) {
                $queryParts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
            }
        }

        if (!empty($queryParts)) {
            return esc_url(sb_display_url() . sb_query_char() . implode('&', $queryParts));
        }

        return sb_display_url();
    }

    /**
     * Get the podcast URL with default parameters.
     *
     * @return string The podcast URL.
     */
    public static function podcastUrl(): string
    {
        return str_replace(
            ' ',
            '%20',
            self::build(['podcast' => 1, 'dir' => 'desc', 'sortby' => 'm.datetime'])
        );
    }

    /**
     * Get a sermon detail page URL.
     *
     * @param object $sermon Sermon object with 'id' property.
     *
     * @return string The sermon URL.
     */
    public static function sermonLink(object $sermon): string
    {
        return self::build(['sermon_id' => $sermon->id], true);
    }

    /**
     * Get a preacher search URL.
     *
     * @param object $sermon Sermon object with 'pid' (preacher ID) property.
     *
     * @return string The preacher search URL.
     */
    public static function preacherLink(object $sermon): string
    {
        return self::build(['preacher' => $sermon->pid], false);
    }

    /**
     * Get a series search URL.
     *
     * @param object $sermon Sermon object with 'ssid' (series ID) property.
     *
     * @return string The series search URL.
     */
    public static function seriesLink(object $sermon): string
    {
        return self::build(['series' => $sermon->ssid], false);
    }

    /**
     * Get a service search URL.
     *
     * @param object $sermon Sermon object with 'sid' (service ID) property.
     *
     * @return string The service search URL.
     */
    public static function serviceLink(object $sermon): string
    {
        return self::build(['service' => $sermon->sid], false);
    }

    /**
     * Get a Bible book search URL.
     *
     * @param string $bookName The name of the Bible book.
     *
     * @return string The book search URL.
     */
    public static function bookLink(string $bookName): string
    {
        return self::build(['book' => $bookName], false);
    }

    /**
     * Get a tag search URL.
     *
     * @param string $tag The tag to search for.
     *
     * @return string The tag search URL.
     */
    public static function tagLink(string $tag): string
    {
        return self::build(['stag' => $tag], false);
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
