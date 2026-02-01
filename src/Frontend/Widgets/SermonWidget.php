<?php

declare(strict_types=1);

namespace SermonBrowser\Frontend\Widgets;

/**
 * Sermon Widget display functions.
 *
 * Provides static methods for displaying sermons in widgets and templates.
 *
 * @since 1.0.0
 */
final class SermonWidget
{
    /**
     * Display sermons for template use.
     *
     * Outputs a list of sermons based on provided options.
     *
     * @param array<string, mixed> $options Display options.
     *                                      - display_preacher: bool Show preacher name
     *                                      - display_passage: bool Show Bible passage
     *                                      - display_date: bool Show sermon date
     *                                      - preacher: int Filter by preacher ID
     *                                      - service: int Filter by service ID
     *                                      - series: int Filter by series ID
     *                                      - limit: int Maximum sermons to show
     *                                      - url_only: bool Only output URL of first sermon
     *
     * @return void
     */
    public static function display(array $options = []): void
    {
        $default = [
            'display_preacher' => 1,
            'display_passage' => 1,
            'display_date' => 1,
            'preacher' => 0,
            'service' => 0,
            'series' => 0,
            'limit' => 5,
            'url_only' => 0,
        ];
        $options = array_merge($default, $options);

        $displayPreacher = $options['display_preacher'];
        $displayPassage = $options['display_passage'];
        $displayDate = $options['display_date'];
        $preacher = $options['preacher'];
        $service = $options['service'];
        $series = $options['series'];
        $limit = $options['limit'];
        $urlOnly = $options['url_only'];

        if ($urlOnly == 1) {
            $limit = 1;
        }

        $sermons = sb_get_sermons(
            [
                'preacher' => $preacher,
                'service' => $service,
                'series' => $series,
            ],
            [],
            1,
            $limit
        );

        if ($urlOnly == 1) {
            sb_print_sermon_link($sermons[0], true);
        } else {
            echo "<ul class=\"sermon-widget\">\r";
            foreach ((array) $sermons as $sermon) {
                echo "\t<li>";
                echo "<span class=\"sermon-title\"><a href=\"";
                sb_print_sermon_link($sermon, true);
                echo "\">" . stripslashes($sermon->title) . "</a></span>";
                if ($displayPassage) {
                    $foo = unserialize($sermon->start);
                    $bar = unserialize($sermon->end);
                    echo "<span class=\"sermon-passage\"> (" . sb_get_books($foo[0], $bar[0]) . ")</span>";
                }
                if ($displayPreacher) {
                    echo "<span class=\"sermon-preacher\">" . __('by', 'sermon-browser') . " <a href=\"";
                    sb_print_preacher_link($sermon);
                    echo "\">" . stripslashes($sermon->preacher) . "</a></span>";
                }
                if ($displayDate) {
                    echo " <span class=\"sermon-date\">" . __('on', 'sermon-browser') . " ";
                    echo sb_formatted_date($sermon) . "</span>";
                }
                echo ".</li>\r";
            }
            echo "</ul>\r";
        }
    }

    /**
     * Display the sermon widget in sidebar.
     *
     * Renders a list of sermons based on saved widget options.
     *
     * @param array<string, mixed> $args        Widget arguments (before_widget, after_widget, etc.).
     * @param array<string, mixed>|int $widgetArgs Widget instance arguments.
     *
     * @return void
     */
    public static function widget(array $args, $widgetArgs = 1): void
    {
        $beforeWidget = $args['before_widget'] ?? '';
        $afterWidget = $args['after_widget'] ?? '';
        $beforeTitle = $args['before_title'] ?? '';
        $afterTitle = $args['after_title'] ?? '';

        if (is_numeric($widgetArgs)) {
            $widgetArgs = ['number' => $widgetArgs];
        }
        $widgetArgs = wp_parse_args($widgetArgs, ['number' => -1]);
        $number = $widgetArgs['number'] ?? -1;

        $options = sb_get_option('sermons_widget_options');
        if (!isset($options[$number])) {
            return;
        }

        $widgetOpts = self::extractWidgetOptions($options[$number]);

        echo $beforeWidget;
        echo $beforeTitle . $widgetOpts['title'] . $afterTitle;

        $sermons = sb_get_sermons(
            [
                'preacher' => $widgetOpts['preacher'],
                'service' => $widgetOpts['service'],
                'series' => $widgetOpts['series'],
            ],
            [],
            1,
            $widgetOpts['limit']
        );

        echo "<ul class=\"sermon-widget\">";
        foreach ((array) $sermons as $sermon) {
            self::renderSermonItem($sermon, $widgetOpts);
        }
        echo "</ul>";
        echo $afterWidget;
    }

    /**
     * Extract widget options with defaults.
     *
     * @param array<string, mixed> $opts Raw widget options.
     * @return array<string, mixed> Normalized options with defaults.
     */
    private static function extractWidgetOptions(array $opts): array
    {
        return [
            'title' => $opts['title'] ?? '',
            'preacher' => $opts['preacher'] ?? 0,
            'service' => $opts['service'] ?? 0,
            'series' => $opts['series'] ?? 0,
            'limit' => $opts['limit'] ?? 5,
            'book' => $opts['book'] ?? false,
            'preacherz' => $opts['preacherz'] ?? false,
            'date' => $opts['date'] ?? false,
        ];
    }

    /**
     * Render a single sermon item in the widget list.
     *
     * @param object $sermon Sermon object.
     * @param array<string, mixed> $opts Widget display options.
     * @return void
     */
    private static function renderSermonItem(object $sermon, array $opts): void
    {
        echo "<li><span class=\"sermon-title\">";
        echo '<a href="' . sb_build_url(['sermon_id' => $sermon->id], true) . '">';
        echo stripslashes($sermon->title) . '</a></span>';

        if ($opts['book']) {
            $start = unserialize($sermon->start);
            $end = unserialize($sermon->end);
            if (isset($start[0], $end[0])) {
                echo " <span class=\"sermon-passage\">(" . sb_get_books($start[0], $end[0]) . ")</span>";
            }
        }

        if ($opts['preacherz']) {
            echo " <span class=\"sermon-preacher\">" . __('by', 'sermon-browser') . " <a href=\"";
            sb_print_preacher_link($sermon);
            echo "\">" . stripslashes($sermon->preacher) . "</a></span>";
        }

        if ($opts['date']) {
            echo " <span class=\"sermon-date\">" . __(' on ', 'sermon-browser');
            echo sb_formatted_date($sermon) . "</span>";
        }

        echo ".</li>";
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
