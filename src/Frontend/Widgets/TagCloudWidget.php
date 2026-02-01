<?php

declare(strict_types=1);

namespace SermonBrowser\Frontend\Widgets;

/**
 * Tag Cloud Widget display functions.
 *
 * Provides static method for displaying sermon tag cloud in sidebar.
 *
 * @since 1.0.0
 */
final class TagCloudWidget
{
    /**
     * Display the tag cloud widget in sidebar.
     *
     * Renders a cloud of sermon tags.
     *
     * @param array<string, mixed> $args Widget arguments (before_widget, after_widget, etc.).
     *
     * @return void
     */
    public static function widget(array $args): void
    {
        $beforeWidget = isset($args['before_widget']) ? $args['before_widget'] : '';
        $afterWidget = isset($args['after_widget']) ? $args['after_widget'] : '';
        $beforeTitle = isset($args['before_title']) ? $args['before_title'] : '';
        $afterTitle = isset($args['after_title']) ? $args['after_title'] : '';

        echo $beforeWidget;
        echo $beforeTitle . __('Sermon Browser tags', 'sermon-browser') . $afterTitle;
        sb_print_tag_clouds();
        echo $afterWidget;
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
