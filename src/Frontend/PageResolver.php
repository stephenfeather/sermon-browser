<?php

declare(strict_types=1);

namespace SermonBrowser\Frontend;

/**
 * Resolves the main sermons page ID and URL.
 */
class PageResolver
{
    /**
     * Cached page ID.
     */
    private static ?int $pageId = null;

    /**
     * Cached display URL.
     */
    private static ?string $displayUrl = null;

    /**
     * Get the page ID of the main sermons page.
     *
     * Uses WP_Query to find pages/posts containing the [sermon] or [sermons] shortcode.
     *
     * @return int Page ID or 0 if not found.
     */
    public static function getPageId(): int
    {
        if (self::$pageId !== null) {
            return self::$pageId;
        }

        $foundId = 0;

        // Try 1: Look for pages with [sermons] or [sermon] shortcode
        $query = new \WP_Query([
            'post_type' => 'page',
            'post_status' => ['publish', 'private'],
            's' => '[sermon',
            'orderby' => 'date',
            'order' => 'ASC',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ]);

        if ($query->have_posts()) {
            foreach ($query->posts as $post_id) {
                $content = get_post_field('post_content', $post_id);
                if (preg_match('/\[sermons?\s*[\]\s]/', $content)) {
                    $foundId = (int) $post_id;
                    break;
                }
            }
        }

        // Try 2: Look in any post type
        if ($foundId === 0) {
            $query = new \WP_Query([
                'post_type' => 'any',
                'post_status' => ['publish', 'private'],
                's' => '[sermon',
                'orderby' => 'date',
                'order' => 'ASC',
                'posts_per_page' => 10,
                'fields' => 'ids',
            ]);

            if ($query->have_posts()) {
                foreach ($query->posts as $post_id) {
                    $content = get_post_field('post_content', $post_id);
                    if (preg_match('/\[sermons?\s*[\]\s]/', $content)) {
                        $foundId = (int) $post_id;
                        break;
                    }
                }
            }
        }

        self::$pageId = $foundId;
        return self::$pageId;
    }

    /**
     * Check if sermons are displayed on the current page.
     *
     * @return bool True if sermons page exists.
     */
    public static function displaysFrontEnd(): bool
    {
        return self::getPageId() !== 0;
    }

    /**
     * Get the URL of the main sermons page.
     *
     * @return string The display URL or empty string if not found.
     */
    public static function getDisplayUrl(): string
    {
        if (self::$displayUrl !== null) {
            return self::$displayUrl;
        }

        $pageid = self::getPageId();

        if ($pageid === 0) {
            self::$displayUrl = '';
        } elseif (defined('SB_AJAX') && SB_AJAX) {
            self::$displayUrl = site_url() . '/?page_id=' . $pageid;
        } else {
            $url = get_permalink($pageid);

            // Hack to force true permalink even if page used for front page
            if ($url === site_url() || $url === '' || $url === false) {
                $url = site_url() . '/?page_id=' . $pageid;
            }

            self::$displayUrl = $url;
        }

        return self::$displayUrl;
    }

    /**
     * Get the query character to append parameters to the URL.
     *
     * @param bool $returnEntity Whether to return HTML entity (&amp;) or plain (&).
     * @return string The query character ('?', '&', or '&amp;').
     */
    public static function getQueryChar(bool $returnEntity = true): string
    {
        if (strpos(self::getDisplayUrl(), '?') === false) {
            return '?';
        }

        return $returnEntity ? '&amp;' : '&';
    }

    /**
     * Clear cached values (useful for testing).
     */
    public static function clearCache(): void
    {
        self::$pageId = null;
        self::$displayUrl = null;
    }
}
