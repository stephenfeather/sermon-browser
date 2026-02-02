<?php

/**
 * Form Helpers for SermonBrowser admin pages.
 *
 * Provides helper methods for rendering form elements.
 *
 * @package SermonBrowser\Admin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin;

/**
 * Class FormHelpers
 *
 * Static helper methods for rendering admin form elements.
 */
final class FormHelpers
{
    /**
     * Build and return a textarea HTML element.
     *
     * @param string $name The textarea name attribute.
     * @param string $html The textarea content.
     *
     * @return string The textarea HTML.
     */
    public static function textarea(string $name, string $html): string
    {
        $id = esc_attr($name);
        $output = '<textarea id="' . $id . '" name="' . $id . '" cols="75" rows="20" style="width:100%">';
        $output .= stripslashes(str_replace('\r\n', "\n", $html));
        $output .= '</textarea>';

        return $output;
    }

    /**
     * Display error message row.
     *
     * @param string $message Error message.
     * @return string HTML for error row.
     */
    public static function displayError(string $message): string
    {
        return '<div style="display: flex; gap: 1em; margin-bottom: 1em;">' .
            '<label style="min-width: 180px; text-align: right; padding-top: 0.5em; color: #AA0000; font-weight: bold;">' .
            __('Error', 'sermon-browser') . ':</label>' .
            '<div style="flex: 1; color: #AA0000;">' . $message . '</div></div>';
    }

    /**
     * Display warning message row.
     *
     * @param string $message Warning message.
     * @return string HTML for warning row.
     */
    public static function displayWarning(string $message): string
    {
        return '<div style="display: flex; gap: 1em; margin-bottom: 1em;">' .
            '<label style="min-width: 180px; text-align: right; padding-top: 0.5em; color: #FFDC00; font-weight: bold;">' .
            __('Warning', 'sermon-browser') . ':</label>' .
            '<div style="flex: 1; color: #FF8C00;">' . $message . '</div></div>';
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
