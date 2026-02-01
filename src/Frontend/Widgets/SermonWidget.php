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
        $beforeWidget = isset($args['before_widget']) ? $args['before_widget'] : '';
        $afterWidget = isset($args['after_widget']) ? $args['after_widget'] : '';
        $beforeTitle = isset($args['before_title']) ? $args['before_title'] : '';
        $afterTitle = isset($args['after_title']) ? $args['after_title'] : '';

        if (is_numeric($widgetArgs)) {
            $widgetArgs = ['number' => $widgetArgs];
        }
        $widgetArgs = wp_parse_args($widgetArgs, ['number' => -1]);
        $number = isset($widgetArgs['number']) ? $widgetArgs['number'] : -1;

        $options = sb_get_option('sermons_widget_options');
        if (!isset($options[$number])) {
            return;
        }

        // Extract widget-specific options.
        $widgetOpts = $options[$number];
        $title = isset($widgetOpts['title']) ? $widgetOpts['title'] : '';
        $preacher = isset($widgetOpts['preacher']) ? $widgetOpts['preacher'] : 0;
        $service = isset($widgetOpts['service']) ? $widgetOpts['service'] : 0;
        $series = isset($widgetOpts['series']) ? $widgetOpts['series'] : 0;
        $limit = isset($widgetOpts['limit']) ? $widgetOpts['limit'] : 5;
        $book = isset($widgetOpts['book']) ? $widgetOpts['book'] : false;
        $preacherz = isset($widgetOpts['preacherz']) ? $widgetOpts['preacherz'] : false;
        $date = isset($widgetOpts['date']) ? $widgetOpts['date'] : false;

        echo $beforeWidget;
        echo $beforeTitle . $title . $afterTitle;

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

        echo "<ul class=\"sermon-widget\">";
        foreach ((array) $sermons as $sermon) {
            echo "<li><span class=\"sermon-title\">";
            echo '<a href="' . sb_build_url(['sermon_id' => $sermon->id], true) . '">';
            echo stripslashes($sermon->title) . '</a></span>';
            if ($book) {
                $foo = unserialize($sermon->start);
                $bar = unserialize($sermon->end);
                if (isset($foo[0]) && isset($bar[0])) {
                    echo " <span class=\"sermon-passage\">(" . sb_get_books($foo[0], $bar[0]) . ")</span>";
                }
            }
            if ($preacherz) {
                echo " <span class=\"sermon-preacher\">" . __('by', 'sermon-browser') . " <a href=\"";
                sb_print_preacher_link($sermon);
                echo "\">" . stripslashes($sermon->preacher) . "</a></span>";
            }
            if ($date) {
                echo " <span class=\"sermon-date\">" . __(' on ', 'sermon-browser');
                echo sb_formatted_date($sermon) . "</span>";
            }
            echo ".</li>";
        }
        echo "</ul>";
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
