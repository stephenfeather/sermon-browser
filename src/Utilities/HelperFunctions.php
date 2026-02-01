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
            $result .= chr(random_int(97, 122)); // a-z
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

    /**
     * Convert php.ini mega- or giga-byte numbers into kilobytes.
     *
     * @param string $val Value like '15M' or '1G'.
     * @return int Value in kilobytes.
     */
    public static function returnKbytes(string $val): int
    {
        $val = trim($val);
        if ($val === '') {
            return 0;
        }

        $last = strtolower($val[strlen($val) - 1]);
        $num = (int) $val;

        switch ($last) {
            case 'g':
                $num *= 1024;
                // Fall through intentionally
            case 'm':
                $num *= 1024;
                break;
            default:
                // Value is already in kilobytes or has no unit suffix
                break;
        }

        return $num;
    }

    /**
     * Recursive mkdir function with chmod.
     *
     * @param string $pathname Directory path to create.
     * @param int    $mode     Permission mode.
     * @return bool True on success.
     */
    public static function mkdir(string $pathname, int $mode = 0755): bool
    {
        is_dir(dirname($pathname)) || self::mkdir(dirname($pathname), $mode);
        @mkdir($pathname, $mode);
        return @chmod($pathname, $mode);
    }

    /**
     * Sanitize Windows paths to use forward slashes.
     *
     * @param string $path The path to sanitize.
     * @return string Sanitized path.
     */
    public static function sanitisePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('|/+|', '/', $path);
        return $path;
    }

    /**
     * Output a file in chunks (for large file downloads).
     *
     * @param string $filename Path to the file.
     * @return bool True on success, false on failure.
     */
    public static function outputFile(string $filename): bool
    {
        $handle = fopen($filename, 'rb');
        if ($handle === false) {
            return false;
        }

        if (ob_get_level() === 0) {
            ob_start();
        }

        while (!feof($handle)) {
            set_time_limit((int) ini_get('max_execution_time'));
            $buffer = fread($handle, 1048576); // 1MB chunks
            echo $buffer;
            ob_flush();
            flush();
        }

        return fclose($handle);
    }
}
