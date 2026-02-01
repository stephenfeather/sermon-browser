<?php

declare(strict_types=1);

namespace SermonBrowser\Frontend\Widgets;

use SermonBrowser\Constants;
use SermonBrowser\Facades\File;

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
        // Extract widget arguments
        $beforeWidget = isset($args['before_widget']) ? $args['before_widget'] : '';
        $afterWidget = isset($args['after_widget']) ? $args['after_widget'] : '';
        $beforeTitle = isset($args['before_title']) ? $args['before_title'] : '';
        $afterTitle = isset($args['after_title']) ? $args['after_title'] : '';
        $suffix = isset($args['suffix']) ? $args['suffix'] : '_w';
        $options = isset($args['options']) ? $args['options'] : sb_get_option('popular_widget_options');

        echo $beforeWidget;
        if ($options['title'] != '') {
            echo $beforeTitle . $options['title'] . $afterTitle;
        }

        $jscript = '';
        $trigger = [];
        $output = [];

        // Popular Sermons using Facade
        if ($options['display_sermons']) {
            $sermons = File::getPopularSermons((int) $options['limit']);
            if ($sermons) {
                $output['sermons'] = '<div class="popular-sermons' . $suffix . '"><ul>';
                foreach ($sermons as $sermon) {
                    $output['sermons'] .= '<li><a href="' . sb_build_url(['sermon_id' => $sermon->id], true) . '">';
                    $output['sermons'] .= $sermon->title . '</a></li>';
                }
                $output['sermons'] .= '</ul></div>';
                $trigger[] = '<a id="popular_sermons_trigger' . $suffix . '" href="#">Sermons</a>';
                $jscript .= 'jQuery("#popular_sermons_trigger' . $suffix . '").click(function() {
                            jQuery(this).attr("style", "font-weight:bold");
                            jQuery("#popular_series_trigger' . $suffix . '").removeAttr("style");
                            jQuery("#popular_preachers_trigger' . $suffix . '").removeAttr("style");
                            jQuery.setSbCookie("sermons");
                            jQuery("#sb_popular_wrapper' . $suffix . '").fadeOut("slow", function() {
                                jQuery("#sb_popular_wrapper' . $suffix . ' . Constants::JS_HTML_SUFFIX . ';
                $jscript .= addslashes($output['sermons']) . '").fadeIn("slow");
                            });
                            return false;
                        });';
            }
        }

        // Popular Series using Facade
        if ($options['display_series']) {
            $seriesList = File::getPopularSeries((int) $options['limit']);
            if ($seriesList) {
                $output['series'] = '<div class="popular-series' . $suffix . '"><ul>';
                foreach ($seriesList as $series) {
                    $output['series'] .= '<li><a href="' . sb_build_url(['series' => $series->id], true) . '">';
                    $output['series'] .= $series->name . '</a></li>';
                }
                $output['series'] .= '</ul></div>';
            }
            $trigger[] = '<a id="popular_series_trigger' . $suffix . '" href="#">Series</a>';
            $jscript .= 'jQuery("#popular_series_trigger' . $suffix . '").click(function() {
                        jQuery(this).attr("style", "font-weight:bold");
                        jQuery("#popular_sermons_trigger' . $suffix . '").removeAttr("style");
                        jQuery("#popular_preachers_trigger' . $suffix . '").removeAttr("style");
                        jQuery.setSbCookie("series");
                        jQuery("#sb_popular_wrapper' . $suffix . '").fadeOut("slow", function() {
                            jQuery("#sb_popular_wrapper' . $suffix . ' . Constants::JS_HTML_SUFFIX . ';
            $jscript .= addslashes($output['series'] ?? '') . '").fadeIn("slow");
                        });
                        return false;
                    });';
        }

        // Popular Preachers using Facade
        if ($options['display_preachers']) {
            $preachersList = File::getPopularPreachers((int) $options['limit']);
            if ($preachersList) {
                $output['preachers'] = '<div class="popular-preachers' . $suffix . '"><ul>';
                foreach ($preachersList as $preacher) {
                    $output['preachers'] .= '<li><a href="' . sb_build_url(['preacher' => $preacher->id], true) . '">';
                    $output['preachers'] .= $preacher->name . '</a></li>';
                }
                $output['preachers'] .= '</ul></div>';
                $trigger[] = '<a id="popular_preachers_trigger' . $suffix . '" href="#">Preachers</a>';
                $jscript .= 'jQuery("#popular_preachers_trigger' . $suffix . '").click(function() {
                            jQuery(this).attr("style", "font-weight:bold");
                            jQuery("#popular_series_trigger' . $suffix . '").removeAttr("style");
                            jQuery("#popular_sermons_trigger' . $suffix . '").removeAttr("style");
                            jQuery.setSbCookie("preachers");
                            jQuery("#sb_popular_wrapper' . $suffix . '").fadeOut("slow", function() {
                                jQuery("#sb_popular_wrapper' . $suffix . ' . Constants::JS_HTML_SUFFIX . ';
                $jscript .= addslashes($output['preachers']) . '").fadeIn("slow");
                            });
                            return false;
                        });';
            }
        }

        // Cookie-based state restoration
        $jscript .= 'if (jQuery.getSbCookie() == "preachers") { ';
        $jscript .= 'jQuery("#popular_preachers_trigger' . $suffix . ' . Constants::JS_BOLD_STYLE . ';
        $jscript .= ' . Constants::JS_POPULAR_WRAPPER . ' . $suffix . ' . Constants::JS_HTML_SUFFIX . ' . addslashes($output['preachers'] ?? '');
        $jscript .= '")};';

        $jscript .= 'if (jQuery.getSbCookie() == "series") { ';
        $jscript .= 'jQuery("#popular_series_trigger' . $suffix . ' . Constants::JS_BOLD_STYLE . ';
        $jscript .= ' . Constants::JS_POPULAR_WRAPPER . ' . $suffix . ' . Constants::JS_HTML_SUFFIX . ' . addslashes($output['series'] ?? '');
        $jscript .= '")};';

        $jscript .= 'if (jQuery.getSbCookie() == "sermons") { ';
        $jscript .= 'jQuery("#popular_sermons_trigger' . $suffix . ' . Constants::JS_BOLD_STYLE . ';
        $jscript .= ' . Constants::JS_POPULAR_WRAPPER . ' . $suffix . ' . Constants::JS_HTML_SUFFIX . ' . addslashes($output['sermons'] ?? '');
        $jscript .= '")};';

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
