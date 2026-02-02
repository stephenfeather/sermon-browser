<?php

/**
 * Podcast Helper utilities for Sermon Browser.
 *
 * Provides static methods for formatting podcast feed data including
 * dates, file sizes, durations, and URLs.
 *
 * @package SermonBrowser\Podcast
 * @since 1.0.0
 */

declare(strict_types=1);

namespace SermonBrowser\Podcast;

use SermonBrowser\Config\FileTypes;
use SermonBrowser\Config\OptionsManager;
use SermonBrowser\Constants;
use SermonBrowser\Facades\File;

/**
 * Class PodcastHelper
 *
 * Static helper methods for podcast feed generation.
 */
final class PodcastHelper
{
    /**
     * Cached filetypes array from filetypes.php.
     *
     * @var array<string, array<string, string>>|null
     */
    private static ?array $filetypes = null;

    /**
     * Format ISO date for RSS pubDate element.
     *
     * @param object|string|int $sermon The sermon object with datetime property, or a datetime string/timestamp.
     *
     * @return string Formatted date in RFC 2822 format.
     */
    public static function formatIsoDate($sermon): string
    {
        if (is_object($sermon)) {
            return date(Constants::RFC822_DATE, strtotime($sermon->datetime));
        }
        if (is_int($sermon)) {
            return date(Constants::RFC822_DATE, $sermon);
        }
        return date(Constants::RFC822_DATE, strtotime($sermon));
    }

    /**
     * Get media file size for enclosure element.
     *
     * @param string $mediaName The filename or URL.
     * @param string $mediaType The type: 'Files' or 'URLs'.
     *
     * @return string Length attribute string like 'length="12345"' or empty string.
     */
    public static function getMediaSize(string $mediaName, string $mediaType): string
    {
        if ($mediaType === 'URLs') {
            if (ini_get('allow_url_fopen')) {
                $headers = array_change_key_case(@get_headers($mediaName, 1), CASE_LOWER);
                $filesize = $headers['content-length'] ?? null;
                if ($filesize) {
                    return 'length="' . $filesize . '"';
                }
            }
            return '';
        }

        $filepath = SB_ABSPATH . OptionsManager::get('upload_dir') . $mediaName;
        $size = @filesize($filepath);
        return 'length="' . ($size ?: 0) . '"';
    }

    /**
     * Get MP3 duration for iTunes duration element.
     *
     * @param string $mediaName The filename.
     * @param string $mediaType The type: 'Files' or 'URLs'.
     *
     * @return string Duration string in HH:MM:SS format or empty string.
     */
    public static function getMp3Duration(string $mediaName, string $mediaType): string
    {
        if (strtolower(substr($mediaName, -3)) !== 'mp3' || $mediaType !== 'Files') {
            return '';
        }

        $duration = File::getFileDuration($mediaName);
        if ($duration) {
            return $duration;
        }

        // Duration not cached, analyze the file
        if (!class_exists('getID3')) {
            require_once ABSPATH . WPINC . '/ID3/getid3.php';
        }

        $getID3 = new \getID3();
        $filepath = SB_ABSPATH . OptionsManager::get('upload_dir') . $mediaName;
        $mediaFileInfo = $getID3->analyze($filepath);
        $duration = $mediaFileInfo['playtime_string'] ?? '';

        File::setFileDuration($mediaName, $duration);

        return $duration;
    }

    /**
     * Encode string for XML output.
     *
     * Replaces special characters with XML entities.
     *
     * @param string $string The string to encode.
     *
     * @return string The XML-safe encoded string.
     */
    public static function xmlEncode(string $string): string
    {
        $string = str_replace('&amp;amp;', '&amp;', str_replace('&', '&amp;', $string));
        $string = str_replace('"', '&quot;', $string);
        $string = str_replace("'", '&apos;', $string);
        $string = str_replace('<', '&lt;', $string);
        $string = str_replace('>', '&gt;', $string);
        return $string;
    }

    /**
     * Get podcast file URL with optional stats tracking.
     *
     * Stats tracking is disabled for iTunes/FeedBurner/AppleCoreMedia user agents
     * for compatibility.
     *
     * @param string $mediaName The filename or URL.
     * @param string $mediaType The type: 'Files' or 'URLs'.
     *
     * @return string The podcast-ready URL.
     */
    public static function getFileUrl(string $mediaName, string $mediaType): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stats = true;

        if (
            stripos($userAgent, 'itunes') !== false
            || stripos($userAgent, 'FeedBurner') !== false
            || stripos($userAgent, 'AppleCoreMedia') !== false
        ) {
            $stats = false;
        }

        if ($mediaType === 'URLs') {
            if ($stats) {
                $mediaName = sb_display_url() . sb_query_char() . 'show&amp;url=' . rawurlencode($mediaName);
            }
        } else {
            if (!$stats) {
                $mediaName = trailingslashit(site_url())
                    . ltrim(OptionsManager::get('upload_dir'), '/')
                    . rawurlencode($mediaName);
            } else {
                $mediaName = sb_display_url() . sb_query_char() . 'show&amp;file_name=' . rawurlencode($mediaName);
            }
        }

        return self::xmlEncode($mediaName);
    }

    /**
     * Get MIME type attribute for enclosure element.
     *
     * @param string $mediaName The filename to get MIME type for.
     *
     * @return string Type attribute string like ' type="audio/mpeg"' or empty string.
     */
    public static function getMimeType(string $mediaName): string
    {
        self::loadFiletypes();

        $extension = strtolower(substr($mediaName, strrpos($mediaName, '.') + 1));

        if (array_key_exists($extension, self::$filetypes)) {
            return ' type="' . self::$filetypes[$extension]['content-type'] . '"';
        }

        return '';
    }

    /**
     * Load filetypes configuration from filetypes.php.
     *
     * @return void
     */
    private static function loadFiletypes(): void
    {
        if (self::$filetypes !== null) {
            return;
        }

        self::$filetypes = FileTypes::getTypes();
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
