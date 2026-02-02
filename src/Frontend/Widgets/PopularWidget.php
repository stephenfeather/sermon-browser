<?php

declare(strict_types=1);

namespace SermonBrowser\Frontend\Widgets;

use SermonBrowser\Config\OptionsManager;
use SermonBrowser\Facades\File;
use SermonBrowser\Frontend\UrlBuilder;

/**
 * Popular Widget display functions.
 *
 * Provides static methods for displaying popular sermons, series, and preachers.
 *
 * @since 1.0.0
 */
final class PopularWidget
{
    /**
     * jQuery selector prefix for popular type triggers.
     */
    private const JS_POPULAR_SELECTOR = 'jQuery("#popular_';

    /**
     * jQuery selector prefix for wrapper element.
     */
    private const JS_WRAPPER_SELECTOR = 'jQuery("#sb_popular_wrapper';

    /**
     * Display the most popular sermons widget in sidebar.
     *
     * Renders tabbed content showing popular sermons, series, and preachers.
     *
     * @param array<string, mixed> $args Widget arguments (before_widget, after_widget, etc.).
     *
     * @return void
     */
    public static function widget(array $args): void
    {
        $beforeWidget = $args['before_widget'] ?? '';
        $afterWidget = $args['after_widget'] ?? '';
        $beforeTitle = $args['before_title'] ?? '';
        $afterTitle = $args['after_title'] ?? '';
        $suffix = $args['suffix'] ?? '_w';
        $options = $args['options'] ?? OptionsManager::get('popular_widget_options');

        echo $beforeWidget;
        if ($options['title'] != '') {
            echo $beforeTitle . $options['title'] . $afterTitle;
        }

        $jscript = '';
        $trigger = [];
        $output = [];

        // Build popular content sections
        if ($options['display_sermons']) {
            self::buildSermonsSection($options, $suffix, $output, $trigger, $jscript);
        }

        if ($options['display_series']) {
            self::buildSeriesSection($options, $suffix, $output, $trigger, $jscript);
        }

        if ($options['display_preachers']) {
            self::buildPreachersSection($options, $suffix, $output, $trigger, $jscript);
        }

        // Cookie-based state restoration
        $jscript .= self::buildCookieRestoration($suffix, $output);

        self::renderWidgetOutput($trigger, $suffix, $output, $jscript, $afterWidget);
    }

    /**
     * Build the popular sermons section.
     *
     * @param array<string, mixed> $options Widget options.
     * @param string $suffix Element ID suffix.
     * @param array<string, string> &$output Output array (by reference).
     * @param array<string> &$trigger Trigger array (by reference).
     * @param string &$jscript JavaScript string (by reference).
     * @return void
     */
    private static function buildSermonsSection(
        array $options,
        string $suffix,
        array &$output,
        array &$trigger,
        string &$jscript
    ): void {
        $sermons = File::getPopularSermons((int) $options['limit']);
        if (!$sermons) {
            return;
        }

        $output['sermons'] = '<div class="popular-sermons' . $suffix . '"><ul>';
        foreach ($sermons as $sermon) {
            $output['sermons'] .= '<li><a href="' . UrlBuilder::build(['sermon_id' => $sermon->id], true) . '">';
            $output['sermons'] .= $sermon->title . '</a></li>';
        }
        $output['sermons'] .= '</ul></div>';
        $trigger[] = '<a id="popular_sermons_trigger' . $suffix . '" href="#">Sermons</a>';
        $jscript .= self::buildClickHandler('sermons', $suffix, ['series', 'preachers'], $output['sermons']);
    }

    /**
     * Build the popular series section.
     *
     * @param array<string, mixed> $options Widget options.
     * @param string $suffix Element ID suffix.
     * @param array<string, string> &$output Output array (by reference).
     * @param array<string> &$trigger Trigger array (by reference).
     * @param string &$jscript JavaScript string (by reference).
     * @return void
     */
    private static function buildSeriesSection(
        array $options,
        string $suffix,
        array &$output,
        array &$trigger,
        string &$jscript
    ): void {
        $seriesList = File::getPopularSeries((int) $options['limit']);
        if ($seriesList) {
            $output['series'] = '<div class="popular-series' . $suffix . '"><ul>';
            foreach ($seriesList as $series) {
                $output['series'] .= '<li><a href="' . UrlBuilder::build(['series' => $series->id], true) . '">';
                $output['series'] .= $series->name . '</a></li>';
            }
            $output['series'] .= '</ul></div>';
        }
        $trigger[] = '<a id="popular_series_trigger' . $suffix . '" href="#">Series</a>';
        $jscript .= self::buildClickHandler('series', $suffix, ['sermons', 'preachers'], $output['series'] ?? '');
    }

    /**
     * Build the popular preachers section.
     *
     * @param array<string, mixed> $options Widget options.
     * @param string $suffix Element ID suffix.
     * @param array<string, string> &$output Output array (by reference).
     * @param array<string> &$trigger Trigger array (by reference).
     * @param string &$jscript JavaScript string (by reference).
     * @return void
     */
    private static function buildPreachersSection(
        array $options,
        string $suffix,
        array &$output,
        array &$trigger,
        string &$jscript
    ): void {
        $preachersList = File::getPopularPreachers((int) $options['limit']);
        if (!$preachersList) {
            return;
        }

        $output['preachers'] = '<div class="popular-preachers' . $suffix . '"><ul>';
        foreach ($preachersList as $preacher) {
            $output['preachers'] .= '<li><a href="' . UrlBuilder::build(['preacher' => $preacher->id], true) . '">';
            $output['preachers'] .= $preacher->name . '</a></li>';
        }
        $output['preachers'] .= '</ul></div>';
        $trigger[] = '<a id="popular_preachers_trigger' . $suffix . '" href="#">Preachers</a>';
        $jscript .= self::buildClickHandler('preachers', $suffix, ['sermons', 'series'], $output['preachers']);
    }

    /**
     * Build a click handler for a tab trigger.
     *
     * @param string $type The tab type (sermons, series, preachers).
     * @param string $suffix Element ID suffix.
     * @param array<string> $otherTypes Other tab types to clear styling.
     * @param string $content HTML content to display.
     * @return string JavaScript click handler code.
     */
    private static function buildClickHandler(
        string $type,
        string $suffix,
        array $otherTypes,
        string $content
    ): string {
        $js = self::JS_POPULAR_SELECTOR . $type . '_trigger' . $suffix . '").click(function() {';
        $js .= 'jQuery(this).attr("style", "font-weight:bold");';
        foreach ($otherTypes as $other) {
            $js .= self::JS_POPULAR_SELECTOR . $other . '_trigger' . $suffix . '").removeAttr("style");';
        }
        $js .= 'jQuery.setSbCookie("' . $type . '");';
        $js .= self::JS_WRAPPER_SELECTOR . $suffix . '").fadeOut("slow", function() {';
        $js .= self::JS_WRAPPER_SELECTOR . $suffix . '").html("' . addslashes($content) . '").fadeIn("slow");';
        $js .= '});';
        $js .= 'return false;';
        $js .= '});';
        return $js;
    }

    /**
     * Build cookie restoration JavaScript.
     *
     * @param string $suffix Element ID suffix.
     * @param array<string, string> $output Content output array.
     * @return string JavaScript cookie restoration code.
     */
    private static function buildCookieRestoration(string $suffix, array $output): string
    {
        $js = '';
        $types = ['preachers', 'series', 'sermons'];
        foreach ($types as $type) {
            $js .= 'if (jQuery.getSbCookie() == "' . $type . '") { ';
            $js .= self::JS_POPULAR_SELECTOR . $type . '_trigger' . $suffix . '").attr("style", "font-weight:bold"); ';
            $js .= self::JS_WRAPPER_SELECTOR . $suffix . '").html("' . addslashes($output[$type] ?? '') . '")};';
        }
        return $js;
    }

    /**
     * Render the widget output HTML and scripts.
     *
     * @param array<string> $trigger Trigger links.
     * @param string $suffix Element ID suffix.
     * @param array<string, string> $output Content output.
     * @param string $jscript JavaScript code.
     * @param string $afterWidget After widget HTML.
     * @return void
     */
    private static function renderWidgetOutput(
        array $trigger,
        string $suffix,
        array $output,
        string $jscript,
        string $afterWidget
    ): void {
        echo '<p>' . implode(' | ', $trigger) . '</p>';
        echo '<div id="sb_popular_wrapper' . $suffix . '">' . current($output) . '</div>';
        echo '<script type="text/javascript">jQuery.setSbCookie = function (value) {
            document.cookie = "sb_popular="+encodeURIComponent(value);
        };</script>';
        echo '<script type="text/javascript">jQuery.getSbCookie = function () {
            var cookieValue = null;
            if (document.cookie && document.cookie != "") {
                var cookies = document.cookie.split(";");
                for (var i = 0; i < cookies.length; i++) {
                    var cookie = jQuery.trim(cookies[i]);
                    var name = "sb_popular";
                    if (cookie.substring(0, name.length + 1) == (name + "=")) {
                        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                        break;
                    }
                }
            }
            return cookieValue;
        }</script>';
        echo '<script type="text/javascript">jQuery(document).ready(function() {' . $jscript . '});</script>';
        echo $afterWidget;
    }

    /**
     * Print the most popular widget with default styling.
     *
     * Convenience method for template usage with predefined styling.
     *
     * @return void
     */
    public static function printMostPopular(): void
    {
        $args = [
            'before_widget' => '<div id="sermon_most_popular" style="border: 1px solid ; margin: 0pt 0pt 1em 2em; '
                . 'padding: 5px; float: right; font-size: 75%; line-height: 1em">',
            'after_widget' => '</div>',
            'before_title' => '<span class="popular_title">',
            'after_title' => '</span>',
            'suffix' => '_f',
        ];
        self::widget($args);
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
