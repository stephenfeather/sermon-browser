<?php

declare(strict_types=1);

namespace SermonBrowser\Frontend;

use SermonBrowser\Config\FileTypes;
use SermonBrowser\Config\OptionsManager;
use SermonBrowser\Constants;
use SermonBrowser\Frontend\PageResolver;

/**
 * File display utilities for Sermon Browser frontend.
 *
 * Provides static methods for displaying file links, icons, and media content.
 *
 * @since 1.0.0
 */
final class FileDisplay
{
    /**
     * Cached filetypes array from filetypes.php.
     *
     * @var array<string, array<string, string>>|null
     */
    private static ?array $filetypes = null;

    /**
     * Cached site icons array from filetypes.php.
     *
     * @var array<string, string>|null
     */
    private static ?array $siteIcons = null;

    /**
     * Default site icon filename.
     *
     * @var string|null
     */
    private static ?string $defaultSiteIcon = null;

    /**
     * Print link with icon for attached files.
     *
     * Outputs an anchor tag with an appropriate file type icon based on extension.
     * For MP3 files, uses the configured mp3_shortcode if available.
     *
     * @param string $url The file URL or filename.
     *
     * @return void Outputs HTML directly.
     */
    public static function printUrl(string $url): void
    {
        self::loadFiletypes();

        $pathinfo = pathinfo($url);
        $ext = $pathinfo['extension'] ?? '';

        // Build the display URL based on file type
        if (str_starts_with($url, Constants::HTTP) || str_starts_with($url, Constants::HTTPS)) {
            $displayUrl = PageResolver::getDisplayUrl() . PageResolver::getQueryChar(false) . 'show&url=' . rawurlencode($url);
        } elseif (strtolower($ext) === 'mp3') {
            $displayUrl = PageResolver::getDisplayUrl() . PageResolver::getQueryChar(false) . 'show&file_name=' . rawurlencode($url);
        } else {
            $displayUrl = PageResolver::getDisplayUrl() . PageResolver::getQueryChar(false) . 'download&file_name=' . rawurlencode($url);
        }

        // Determine the icon to use
        $uicon = self::$defaultSiteIcon;
        foreach (self::$siteIcons as $site => $icon) {
            if (strpos($displayUrl, $site) !== false) {
                $uicon = $icon;
                break;
            }
        }
        $uicon = self::$filetypes[$ext]['icon'] ?? $uicon;

        // Handle MP3 shortcode
        if (strtolower($ext) === 'mp3') {
            $mp3Shortcode = OptionsManager::get(Constants::OPT_MP3_SHORTCODE);
            if (do_shortcode($mp3Shortcode) !== $mp3Shortcode) {
                echo do_shortcode(str_ireplace('%SERMONURL%', $displayUrl, $mp3Shortcode));
                return;
            }
        }

        $iconUrl = SB_PLUGIN_URL . '/assets/images/icons/' . $uicon;

        // Get file type name
        if (!isset(self::$filetypes[$ext]['name'])) {
            $typeName = sprintf(__('%s file', 'sermon-browser'), addslashes($ext));
        } else {
            $typeName = addslashes(self::$filetypes[$ext]['name']);
        }

        echo '<a href="' . esc_attr($displayUrl) . '">'
            . '<img class="site-icon" alt="' . esc_attr($typeName) . '" '
            . 'title="' . esc_attr($typeName) . '" src="' . esc_attr($iconUrl) . '">'
            . '</a>';
    }

    /**
     * Print file link with download option for MP3 files.
     *
     * Wraps the file icon in a div and adds a download link for MP3 files.
     *
     * @param string $url The file URL or filename.
     *
     * @return void Outputs HTML directly.
     */
    public static function printUrlLink(string $url): void
    {
        echo '<div class="sermon_file">';
        self::printUrl($url);

        if (strtolower(substr($url, -4)) === '.mp3') {
            if (str_starts_with($url, Constants::HTTP) || str_starts_with($url, Constants::HTTPS)) {
                $param = 'url';
            } else {
                $param = 'file_name';
            }
            $encodedUrl = rawurlencode($url);
            echo ' <a href="' . esc_url(PageResolver::getDisplayUrl() . PageResolver::getQueryChar() . 'download&' . $param . '=' . $encodedUrl)
                . '">' . esc_html__('Download', 'sermon-browser') . '</a>';
        }
        echo '</div>';
    }

    /**
     * Decode and output base64 encoded shortcode.
     *
     * @param string $code Base64 encoded shortcode content.
     *
     * @return void Outputs decoded shortcode result.
     */
    public static function printCode(string $code): void
    {
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Legacy feature for shortcode storage
        $decoded = base64_decode($code, true);
        if ($decoded !== false) {
            echo do_shortcode($decoded);
        }
    }

    /**
     * Get the first MP3 file attached to a sermon.
     *
     * Used primarily for podcast feeds. Stats tracking can be disabled
     * for iTunes/FeedBurner compatibility.
     *
     * @param object $sermon The sermon object.
     * @param bool   $stats  Whether to track stats (default true).
     *
     * @return string|null The MP3 URL or null if none found.
     */
    public static function firstMp3(object $sermon, bool $stats = true): ?string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (stripos($userAgent, 'itunes') !== false || stripos($userAgent, 'FeedBurner') !== false) {
            $stats = false;
        }

        $stuff = sb_get_stuff($sermon, true);
        $files = array_merge((array)($stuff['Files'] ?? []), (array)($stuff['URLs'] ?? []));

        foreach ($files as $file) {
            $extension = strtolower(substr($file, strrpos($file, '.') + 1));
            if ($extension !== 'mp3') {
                continue;
            }

            if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
                if ($stats) {
                    return PageResolver::getDisplayUrl() . PageResolver::getQueryChar() . 'show&amp;url=' . rawurlencode($file);
                }
                return $file;
            }

            // Local file
            if (!$stats) {
                return trailingslashit(site_url())
                    . OptionsManager::get(Constants::OPT_UPLOAD_DIR)
                    . rawurlencode($file);
            }
            return PageResolver::getDisplayUrl() . PageResolver::getQueryChar() . 'show&amp;file_name=' . rawurlencode($file);
        }

        return null;
    }

    /**
     * Load filetypes configuration from FileTypes class.
     *
     * This method loads and caches the filetypes, siteicons, and default icon
     * from the PSR-4 FileTypes configuration class.
     *
     * @return void
     */
    private static function loadFiletypes(): void
    {
        if (self::$filetypes !== null) {
            return;
        }

        self::$filetypes = FileTypes::getTypes();
        self::$siteIcons = FileTypes::getSiteIcons();
        self::$defaultSiteIcon = FileTypes::getDefaultSiteIcon();
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
