<?php

/**
 * Filter Renderer for sermon search page filters.
 *
 * Provides static methods to render filter UI components including
 * one-click filters, dropdown filters, and filter line items.
 *
 * @package SermonBrowser\Frontend
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Frontend;

use SermonBrowser\Config\Defaults;
use SermonBrowser\Config\OptionsManager;
use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Service;
use SermonBrowser\Facades\Book;
use SermonBrowser\Facades\Sermon;

/**
 * Class FilterRenderer
 *
 * Renders filter UI components for the sermon search page.
 */
class FilterRenderer
{
    /**
     * Track elements that have "more" links applied.
     *
     * @var array<string>
     */
    private static array $moreApplied = [];

    /**
     * Filter options used for URL building.
     *
     * @var array<string>
     */
    private static array $filterOptions = [];

    /**
     * Render the complete filter section.
     *
     * Supports two filter types: 'oneclick' and 'dropdown'.
     *
     * @param array<string, mixed> $filter Filter configuration array.
     * @return void
     */
    public static function render(array $filter): void
    {
        self::$moreApplied = [];
        $hideFilter = false;
        $jsHide = '';

        if (isset($filter['filterhide']) && $filter['filterhide'] === 'hide') {
            $hideFilter = true;
            $jsHide = self::getHideFilterJs();
        }

        $filterType = $filter['filter'] ?? '';

        if ($filterType === 'oneclick') {
            self::renderOneClickFilter($filter, $hideFilter, $jsHide);
        } elseif ($filterType === 'dropdown') {
            DropdownFilterRenderer::render($hideFilter, $jsHide);
        }
    }

    /**
     * Render a single filter line for a given parameter.
     *
     * @param string $id The filter ID (e.g., 'preacher', 'book').
     * @param array<object> $results The filter results with count.
     * @param string $filterField The field to use for filter value.
     * @param string $display The field to display.
     * @param int $maxNum Maximum items to show before "more" link.
     * @return void
     */
    public static function renderLine(
        string $id,
        array $results,
        string $filterField,
        string $display,
        int $maxNum = 7
    ): void {
        $translateBook = false;
        $translatedBooks = [];

        if ($id === 'book') {
            $translateBook = true;
            $translatedBooks = array_combine(
                Defaults::get('eng_bible_books'),
                Defaults::get('bible_books')
            );
        }

        echo "<div id = \"{$id}\" class=\"filter\">\r";
        echo "<span class=\"filter-heading\">" . esc_html__(ucwords($id), 'sermon-browser') . ":</span> \r";

        $i = 1;
        $more = false;

        foreach ($results as $result) {
            if ($i === $maxNum) {
                echo "<span id=\"{$id}-more\">";
                $more = true;
                self::$moreApplied[] = $id;
            }

            if ($i !== 1) {
                echo ", \r";
            }

            $url = UrlBuilder::build([$id => $result->$filterField], false);

            if ($translateBook && isset($translatedBooks[stripslashes($result->$display)])) {
                $displayValue = $translatedBooks[stripslashes($result->$display)];
            } else {
                $displayValue = stripslashes($result->$display);
            }

            echo '<a href="' . esc_url($url) . '">' . esc_html($displayValue) . '</a>&nbsp;(' . (int) $result->count . ')';
            $i++;
        }

        echo ".";

        if ($more) {
            $moreCount = $i - $maxNum;
            echo "</span>\r";
            echo "<span id=\"{$id}-more-link\" style=\"display:none\">";
            echo "&hellip; (<a id=\"{$id}-toggle\" href=\"#\"><strong>";
            echo (int) $moreCount . ' ' . esc_html__('more', 'sermon-browser');
            echo '</strong></a>)</span>';
        }

        echo '</div>';
    }

    /**
     * Render the date filter line.
     *
     * @param array<object> $dates Array of date objects with year, month, day.
     * @return void
     */
    public static function renderDateLine(array $dates): void
    {
        if (empty($dates)) {
            return;
        }

        $first = $dates[0];
        $last = end($dates);

        // Same year and month - nothing to show
        if ($first->year === $last->year && $first->month === $last->month) {
            return;
        }

        $dateOutput = "<div id = \"dates\" class=\"filter\">\r";
        $dateOutput .= "<span class=\"filter-heading\">" . esc_html__('Date', 'sermon-browser') . ":</span> \r";

        if ($first->year === $last->year) {
            $dateOutput .= self::renderMonthlyDateLinks($dates);
        } else {
            $dateOutput .= self::renderYearlyDateLinks($dates);
        }

        echo rtrim($dateOutput, ', ') . "</div>\r";
    }

    /**
     * Render monthly date links when all dates are in the same year.
     *
     * @param array<object> $dates Array of date objects.
     * @return string HTML output for monthly links.
     */
    private static function renderMonthlyDateLinks(array $dates): string
    {
        $output = '';
        $previousMonth = -1;
        $count = 0;

        foreach ($dates as $date) {
            if ($date->month !== $previousMonth) {
                if ($count !== 0) {
                    $output .= '(' . $count . '), ';
                }
                $url = UrlBuilder::build([
                    'date' => $date->year . '-' . $date->month . '-01',
                    'enddate' => $date->year . '-' . $date->month . '-31'
                ], false);
                $monthName = wp_date('F', strtotime("{$date->year}-{$date->month}-{$date->day}"));
                $output .= '<a href="' . esc_url($url) . '">' . esc_html($monthName) . '</a> ';
                $previousMonth = $date->month;
                $count = 1;
            } else {
                $count++;
            }
        }
        $output .= '(' . $count . '), ';

        return $output;
    }

    /**
     * Render yearly date links when dates span multiple years.
     *
     * @param array<object> $dates Array of date objects.
     * @return string HTML output for yearly links.
     */
    private static function renderYearlyDateLinks(array $dates): string
    {
        $output = '';
        $previousYear = 0;
        $count = 0;

        foreach ($dates as $date) {
            if ($date->year !== $previousYear) {
                if ($count !== 0) {
                    $output .= '(' . $count . '), ';
                }
                $url = UrlBuilder::build([
                    'date' => $date->year . '-01-01',
                    'enddate' => $date->year . '-12-31'
                ], false);
                $output .= '<a href="' . esc_url($url) . '">' . (int) $date->year . '</a> ';
                $previousYear = $date->year;
                $count = 1;
            } else {
                $count++;
            }
        }
        $output .= '(' . $count . '), ';

        return $output;
    }

    /**
     * Get the filter URL minus a given parameter.
     *
     * @param string $param1 First parameter to exclude.
     * @param string $param2 Second parameter to exclude (optional).
     * @return string The filtered URL.
     */
    public static function urlMinusParameter(string $param1, string $param2 = ''): string
    {
        $existingParams = array_merge((array) $_GET, (array) $_POST);
        $returnedQuery = [];

        foreach (array_keys($existingParams) as $query) {
            if (in_array($query, self::$filterOptions, true) && $query !== $param1 && $query !== $param2) {
                $returnedQuery[] = "{$query}=" . urlencode((string) $existingParams[$query]);
            }
        }

        if (!empty($returnedQuery)) {
            return esc_url(PageResolver::getDisplayUrl() . PageResolver::getQueryChar() . implode('&', $returnedQuery));
        }

        return PageResolver::getDisplayUrl();
    }

    /**
     * Get the "more applied" array for external access.
     *
     * @return array<string> Array of element IDs with "more" links.
     */
    public static function getMoreApplied(): array
    {
        return self::$moreApplied;
    }

    /**
     * Set the filter options array.
     *
     * @param array<string> $options The filter options.
     * @return void
     */
    public static function setFilterOptions(array $options): void
    {
        self::$filterOptions = $options;
    }

    /**
     * Get the filter options array.
     *
     * @return array<string> The filter options.
     */
    public static function getFilterOptions(): array
    {
        return self::$filterOptions;
    }

    /**
     * Get the JavaScript for hiding/showing filter.
     *
     * @return string JavaScript code.
     */
    private static function getHideFilterJs(): string
    {
        $showText = __('Show filter', 'sermon-browser');
        $hideText = __('Hide filter', 'sermon-browser');

        return "
        var filter_visible = false;
        jQuery('#mainfilter').hide();
        jQuery('#show_hide_filter').text('[ {$showText} ]');
        jQuery('#show_hide_filter').click(function() {
            jQuery('#mainfilter:visible').slideUp('slow');
            jQuery('#mainfilter:hidden').slideDown('slow');
            if (filter_visible) {
                jQuery('#show_hide_filter').text('[ {$showText} ]');
                filter_visible = false;
            } else {
                jQuery('#show_hide_filter').text('[ {$hideText} ]');
                filter_visible = true;
            }
            return false;
        });";
    }

    /**
     * Render the one-click filter type.
     *
     * @param array<string, mixed> $filter Filter configuration.
     * @param bool $hideFilter Whether to hide the filter by default.
     * @param string $jsHide JavaScript for hide functionality.
     * @return void
     */
    private static function renderOneClickFilter(array $filter, bool $hideFilter, string $jsHide): void
    {
        $hideCustomPodcast = true;
        self::$filterOptions = ['preacher', 'book', 'service', 'series', 'date', 'enddate', 'title'];

        $output = self::buildActiveFilterOutput();

        if ($output !== '') {
            $hideCustomPodcast = false;
        }

        // Get sermon data
        $hideEmpty = OptionsManager::get('hide_no_attachments');
        $sermons = sb_get_sermons($filter, [], 1, 99999, $hideEmpty);
        $ids = [];
        foreach ($sermons as $sermon) {
            $ids[] = $sermon->id;
        }

        // Get filter data using Facades
        $preachers = Preacher::findBySermonIdsWithCount($ids);
        $series = Series::findBySermonIdsWithCount($ids);
        $services = Service::findBySermonIdsWithCount($ids);
        $bookCount = Book::findBySermonIdsWithCount($ids);
        $dates = Sermon::findDatesForIds($ids);

        // Replace placeholders with actual values
        $output = str_replace('*preacher*', isset($preachers[0]->name) ? $preachers[0]->name : '', $output);
        $output = str_replace('*book*', isset($_REQUEST['book']) ? esc_html($_REQUEST['book']) : '', $output);
        $output = str_replace('*service*', isset($services[0]->name) ? $services[0]->name : '', $output);
        $output = str_replace('*series*', isset($series[0]->name) ? $series[0]->name : '', $output);

        echo '<span class="inline_controls"><button type="button" id="show_hide_filter" class="button-link" aria-label="' . esc_attr__('Toggle filter', 'sermon-browser') . '"></button></span>';

        if ($output !== '') {
            echo '<div class="filtered">' . esc_html__('Active filter', 'sermon-browser') . ': ' . $output . "</div>\r";
        }

        echo '<div id="mainfilter">';

        if (count($preachers) > 1) {
            self::renderLine('preacher', $preachers, 'id', 'name', 7);
        }
        if (count($bookCount) > 1) {
            self::renderLine('book', $bookCount, 'name', 'name', 10);
        }
        if (count($series) > 1) {
            self::renderLine('series', $series, 'id', 'name', 10);
        }
        if (count($services) > 1) {
            self::renderLine('service', $services, 'id', 'name', 10);
        }

        self::renderDateLine($dates);

        echo "</div>\r";

        // Output JavaScript if needed
        if (count(self::$moreApplied) > 0 || $output !== '' || $hideCustomPodcast === true || $hideFilter === true) {
            self::renderOneClickJs($hideFilter, $jsHide, $hideCustomPodcast);
        }
    }

    /**
     * Build the active filter output string.
     *
     * @return string The active filter HTML.
     */
    private static function buildActiveFilterOutput(): string
    {
        $output = '';

        foreach (self::$filterOptions as $filterOption) {
            if (isset($_REQUEST[$filterOption]) && $filterOption !== 'enddate') {
                if ($output !== '') {
                    $output .= "\r, ";
                }

                if ($filterOption === 'date') {
                    $output .= self::buildDateFilterOutput();
                } else {
                    $output .= '<strong>' . esc_html__(ucwords($filterOption), 'sermon-browser') . '</strong>:&nbsp;*' . $filterOption . '*';
                    $output .= '&nbsp;(<a href="' . esc_url(self::urlMinusParameter($filterOption)) . '">x</a>)';
                }
            }
        }

        return $output;
    }

    /**
     * Build the date portion of active filter output.
     *
     * @return string The date filter HTML.
     */
    private static function buildDateFilterOutput(): string
    {
        $output = '<strong>' . esc_html__('Date', 'sermon-browser') . '</strong>:&nbsp;';
        $date = sanitize_text_field($_REQUEST['date'] ?? '');
        $endDate = sanitize_text_field($_REQUEST['enddate'] ?? '');

        if (substr($date, 0, 4) === substr($endDate, 0, 4)) {
            $year = (int) substr($date, 0, 4);
            $output .= $year . '&nbsp;(<a href="' . esc_url(self::urlMinusParameter('date', 'enddate')) . '">x</a>)';
        }

        if (substr($date, 5, 2) === substr($endDate, 5, 2)) {
            $monthName = wp_date('F', strtotime($date));
            $url = UrlBuilder::build([
                'date' => (int) substr($date, 0, 4) . '-01-01',
                'enddate' => (int) substr($date, 0, 4) . '-12-31'
            ], false);
            $output .= ', ' . esc_html($monthName) . ' (<a href="' . esc_url($url) . '">x</a>)';
        }

        return $output;
    }

    /**
     * Render JavaScript for one-click filter.
     *
     * @param bool $hideFilter Whether filter is hidden by default.
     * @param string $jsHide Hide/show JavaScript.
     * @param bool $hideCustomPodcast Whether to hide custom podcast.
     * @return void
     */
    private static function renderOneClickJs(bool $hideFilter, string $jsHide, bool $hideCustomPodcast): void
    {
        echo "<script type=\"text/javascript\">\r";
        echo "\tjQuery(document).ready(function() {\r";

        if ($hideFilter === true) {
            echo $jsHide . "\r";
        }

        if ($hideCustomPodcast === true) {
            echo "\t\tjQuery('.podcastcustom').hide();\r";
        }

        if (count(self::$moreApplied) > 0) {
            foreach (self::$moreApplied as $elementId) {
                $elementId = esc_js($elementId);
                echo "\t\tjQuery('#{$elementId}-more').hide();\r";
                echo "\t\tjQuery('#{$elementId}-more-link').show();\r";
                echo "\t\tjQuery('a#{$elementId}-toggle').click(function() {\r";
                echo "\t\t\tjQuery('#{$elementId}-more').show();\r";
                echo "\t\t\tjQuery('#{$elementId}-more-link').hide();\r";
                echo "\t\t\treturn false;\r";
                echo "\t\t});\r";
            }
        }

        echo "\t});\r";
        echo "</script>\r";
    }
}
