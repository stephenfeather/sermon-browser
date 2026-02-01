<?php

declare(strict_types=1);

namespace SermonBrowser\Utilities;

/**
 * Pure helper functions for Sermon Browser.
 *
 * Contains utility functions that have no side effects and whose output
 * depends only on their inputs.
 *
 * @since 1.0.0
 */
class HelperFunctions
{
    /**
     * Generate a random filename suffix for temporary files.
     *
     * Replacement for the deprecated preg_replace /e modifier pattern.
     *
     * @param int $length Number of random characters to generate.
     * @return string Random lowercase letters.
     */
    public static function generateTempSuffix(int $length = 2): string
    {
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= chr(rand(97, 122)); // a-z
        }

        return $result;
    }

    /**
     * Join an array of passages with a separator.
     *
     * @param array<int, string> $passages  Array of passage strings.
     * @param string             $separator Separator between passages.
     * @return string Joined passages.
     */
    public static function joinPassages(array $passages, string $separator = ', '): string
    {
        return implode($separator, $passages);
    }

    /**
     * Get the locale string for setlocale().
     *
     * @return string Locale string with UTF-8 suffix, or empty if no locale.
     */
    public static function getLocaleString(): string
    {
        if (!function_exists('get_locale')) {
            return '';
        }

        $locale = get_locale();

        if (empty($locale)) {
            return '';
        }

        return $locale . '.UTF-8';
    }

    /**
     * Check if current user has super admin privileges.
     *
     * @return bool True if user is super admin.
     */
    public static function isSuperAdmin(): bool
    {
        if (!function_exists('is_super_admin')) {
            return false;
        }

        return is_super_admin();
    }
}
