<?php

/**
 * Pure, testable functions for Sermon Browser.
 *
 * This file contains functions that are extracted from the main plugin code
 * to enable easier unit testing. These functions are "pure" - they have no
 * side effects and their output depends only on their inputs.
 *
 * @package SermonBrowser
 * @since 0.46.0
 */

declare(strict_types=1);

// Note: No direct access check here because this file is loaded by Composer autoload
// during testing, before WordPress constants are defined. The main plugin file
// (sermon.php) handles access control.

/**
 * Generate a random filename suffix for temporary files.
 *
 * Replacement for the deprecated preg_replace /e modifier pattern.
 * Original: preg_replace('/([ ])/e', 'chr(rand(97,122))', '        ')
 *
 * @param int $length Number of random characters to generate.
 * @return string Random lowercase letters.
 *
 * @since 0.46.0
 */
function sb_generate_temp_suffix(int $length = 2): string
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
 * Replacement for deprecated implode() argument order.
 * Original: implode($ref_output, ", ")
 * Fixed: implode(", ", $ref_output)
 *
 * @param array  $passages  Array of passage strings.
 * @param string $separator Separator between passages.
 * @return string Joined passages.
 *
 * @since 0.46.0
 */
function sb_join_passages(array $passages, string $separator = ', '): string
{
    return implode($separator, $passages);
}

/**
 * Get the locale string for setlocale().
 *
 * Replacement for deprecated WPLANG constant usage.
 * Original: WPLANG.'.UTF-8'
 * Fixed: get_locale() . '.UTF-8'
 *
 * @return string Locale string with UTF-8 suffix, or empty if no locale.
 *
 * @since 0.46.0
 */
function sb_get_locale_string(): string
{
    // In test environment, get_locale may be a stub.
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
 * Replacement for deprecated is_site_admin() function.
 * Original: is_site_admin()
 * Fixed: is_super_admin()
 *
 * @return bool True if user is super admin.
 *
 * @since 0.46.0
 */
function sb_is_super_admin(): bool
{
    if (!function_exists('is_super_admin')) {
        return false;
    }

    return is_super_admin();
}
