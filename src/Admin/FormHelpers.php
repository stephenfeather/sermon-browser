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
