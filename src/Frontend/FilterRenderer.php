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

use SermonBrowser\Constants;
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
            self::renderDropdownFilter($hideFilter, $jsHide);
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
                sb_get_default('eng_bible_books'),
                sb_get_default('bible_books')
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

            $url = sb_build_url([$id => $result->$filterField], false);

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
        // PHP 8 fix: Early return if dates array is empty
        if (empty($dates)) {
            return;
        }

        $dateOutput = "<div id = \"dates\" class=\"filter\">\r";
        $dateOutput .= "<span class=\"filter-heading\">" . esc_html__('Date', 'sermon-browser') . ":</span> \r";

        $first = $dates[0];
        $last = end($dates);
        $count = 0;

        if ($first->year === $last->year) {
            if ($first->month === $last->month) {
                $dateOutput = '';
            } else {
                $previousMonth = -1;
                foreach ($dates as $date) {
                    if ($date->month !== $previousMonth) {
                        if ($count !== 0) {
                            $dateOutput .= '(' . $count . '), ';
                        }
                        $url = sb_build_url([
                            'date' => $date->year . '-' . $date->month . '-01',
                            'enddate' => $date->year . '-' . $date->month . '-31'
                        ], false);
                        $monthName = wp_date('F', strtotime("{$date->year}-{$date->month}-{$date->day}"));
                        $dateOutput .= '<a href="' . esc_url($url) . '">' . esc_html($monthName) . '</a> ';
                        $previousMonth = $date->month;
                        $count = 1;
                    } else {
                        $count++;
                    }
                }
                $dateOutput .= '(' . $count . '), ';
            }
        } else {
            $previousYear = 0;
            foreach ($dates as $date) {
                if ($date->year !== $previousYear) {
                    if ($count !== 0) {
                        $dateOutput .= '(' . $count . '), ';
                    }
                    $url = sb_build_url([
                        'date' => $date->year . '-01-01',
                        'enddate' => $date->year . '-12-31'
                    ], false);
                    $dateOutput .= '<a href="' . esc_url($url) . '">' . (int) $date->year . '</a> ';
                    $previousYear = $date->year;
                    $count = 1;
                } else {
                    $count++;
                }
            }
            $dateOutput .= '(' . $count . '), ';
        }

        if ($dateOutput !== '') {
            echo rtrim($dateOutput, ', ') . "</div>\r";
        }
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

        if (count($returnedQuery) > 0) {
            return esc_url(sb_display_url() . sb_query_char() . implode('&', $returnedQuery));
        }

        return sb_display_url();
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
        $hideEmpty = sb_get_option('hide_no_attachments');
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

        echo "<span class=\"inline_controls\"><a href=\"#\" id=\"show_hide_filter\"></a></span>";

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
            if (isset($_REQUEST[$filterOption])) {
                if ($filterOption !== 'enddate') {
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
            $url = sb_build_url([
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

    /**
     * Render the dropdown filter type.
     *
     * @param bool $hideFilter Whether to hide filter by default.
     * @param string $jsHide JavaScript for hide functionality.
     * @return void
     */
    private static function renderDropdownFilter(bool $hideFilter, string $jsHide): void
    {
        $translatedBooks = array_combine(
            sb_get_default('eng_bible_books'),
            sb_get_default('bible_books')
        );

        $preachers = Preacher::findAllForFilter();
        $series = Series::findAllForFilter();
        $services = Service::findAllForFilter();
        $bookCount = Book::findAllWithSermonCount();

        $sortByOptions = [
            'Title' => 'm.title',
            'Preacher' => 'preacher',
            'Date' => 'm.datetime',
            'Passage' => 'b.id',
        ];

        $directionOptions = [
            __('Ascending', 'sermon-browser') => 'asc',
            __('Descending', 'sermon-browser') => 'desc',
        ];

        $currentSortBy = isset($_REQUEST['sortby']) ? sanitize_text_field($_REQUEST['sortby']) : 'm.datetime';
        $currentDir = 'desc';
        if (isset($_REQUEST['dir'])) {
            $dir = strtolower($_REQUEST['dir']);
            if ($dir === 'asc' || $dir === 'desc') {
                $currentDir = $dir;
            }
        }

        self::renderDropdownHtml(
            $preachers,
            $series,
            $services,
            $bookCount,
            $translatedBooks,
            $sortByOptions,
            $directionOptions,
            $currentSortBy,
            $currentDir
        );

        self::renderDropdownJs($hideFilter, $jsHide);
    }

    /**
     * Render the dropdown filter HTML form.
     *
     * @param array<object> $preachers Preacher data.
     * @param array<object> $series Series data.
     * @param array<object> $services Service data.
     * @param array<object> $bookCount Book count data.
     * @param array<string, string> $translatedBooks Book translations.
     * @param array<string, string> $sortByOptions Sort by options.
     * @param array<string, string> $directionOptions Direction options.
     * @param string $currentSortBy Current sort by value.
     * @param string $currentDir Current direction value.
     * @return void
     */
    private static function renderDropdownHtml(
        array $preachers,
        array $series,
        array $services,
        array $bookCount,
        array $translatedBooks,
        array $sortByOptions,
        array $directionOptions,
        string $currentSortBy,
        string $currentDir
    ): void {
        ?>
        <span class="inline_controls"><a href="#" id="show_hide_filter"></a></span>
        <div id="mainfilter">
            <form method="post" id="sermon-filter" action="<?php echo esc_url(sb_display_url()); ?>">
                <div style="clear:both">
                    <table class="sermonbrowser">
                        <tr>
                            <td class="fieldname"><?php esc_html_e('Preacher', 'sermon-browser'); ?></td>
                            <td class="field"><select name="preacher" id="preacher">
                                    <option value="0" <?php echo (!isset($_REQUEST['preacher']) || $_REQUEST['preacher'] == 0) ? Constants::SELECTED : ''; ?>><?php esc_html_e('[All]', 'sermon-browser'); ?></option>
                                    <?php foreach ($preachers as $preacher) : ?>
                                    <option value="<?php echo (int) $preacher->id; ?>" <?php echo isset($_REQUEST['preacher']) && $_REQUEST['preacher'] == $preacher->id ? Constants::SELECTED : ''; ?>><?php echo esc_html(stripslashes($preacher->name) . ' (' . $preacher->count . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="fieldname rightcolumn"><?php esc_html_e('Services', 'sermon-browser'); ?></td>
                            <td class="field"><select name="service" id="service">
                                    <option value="0" <?php echo (!isset($_REQUEST['service']) || $_REQUEST['service'] == 0) ? Constants::SELECTED : ''; ?>><?php esc_html_e('[All]', 'sermon-browser'); ?></option>
                                    <?php foreach ($services as $service) : ?>
                                    <option value="<?php echo (int) $service->id; ?>" <?php echo isset($_REQUEST['service']) && $_REQUEST['service'] == $service->id ? Constants::SELECTED : ''; ?>><?php echo esc_html(stripslashes($service->name) . ' (' . $service->count . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldname"><?php esc_html_e('Book', 'sermon-browser'); ?></td>
                            <td class="field"><select name="book">
                                    <option value=""><?php esc_html_e('[All]', 'sermon-browser'); ?></option>
                                    <?php foreach ($bookCount as $book) : ?>
                                    <option value="<?php echo esc_attr($book->name); ?>" <?php echo isset($_REQUEST['book']) && $_REQUEST['book'] === $book->name ? Constants::SELECTED : ''; ?>><?php echo esc_html(($translatedBooks[stripslashes($book->name)] ?? stripslashes($book->name)) . ' (' . $book->count . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="fieldname rightcolumn"><?php esc_html_e('Series', 'sermon-browser'); ?></td>
                            <td class="field"><select name="series" id="series">
                                    <option value="0" <?php echo (!isset($_REQUEST['series']) || $_REQUEST['series'] == 0) ? Constants::SELECTED : ''; ?>><?php esc_html_e('[All]', 'sermon-browser'); ?></option>
                                    <?php foreach ($series as $item) : ?>
                                    <option value="<?php echo (int) $item->id; ?>" <?php echo isset($_REQUEST['series']) && $_REQUEST['series'] == $item->id ? Constants::SELECTED : ''; ?>><?php echo esc_html(stripslashes($item->name) . ' (' . $item->count . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldname"><?php esc_html_e('Start date', 'sermon-browser'); ?></td>
                            <td class="field"><input type="text" name="date" id="date" value="<?php echo esc_attr($_REQUEST['date'] ?? ''); ?>" /></td>
                            <td class="fieldname rightcolumn"><?php esc_html_e('End date', 'sermon-browser'); ?></td>
                            <td class="field"><input type="text" name="enddate" id="enddate" value="<?php echo esc_attr($_REQUEST['enddate'] ?? ''); ?>" /></td>
                        </tr>
                        <tr>
                            <td class="fieldname"><?php esc_html_e('Keywords', 'sermon-browser'); ?></td>
                            <td class="field" colspan="3"><input style="width: 98.5%" type="text" id="title" name="title" value="<?php echo esc_attr($_REQUEST['title'] ?? ''); ?>" /></td>
                        </tr>
                        <tr>
                            <td class="fieldname"><?php esc_html_e('Sort by', 'sermon-browser'); ?></td>
                            <td class="field"><select name="sortby" id="sortby">
                                    <?php foreach ($sortByOptions as $label => $value) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php echo $currentSortBy === $value ? Constants::SELECTED : ''; ?>><?php esc_html_e($label, 'sermon-browser'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="fieldname rightcolumn"><?php esc_html_e('Direction', 'sermon-browser'); ?></td>
                            <td class="field"><select name="dir" id="dir">
                                    <?php foreach ($directionOptions as $label => $value) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php echo $currentDir === $value ? Constants::SELECTED : ''; ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">&nbsp;</td>
                            <td class="field"><input type="submit" class="filter" value="<?php esc_attr_e('Filter &raquo;', 'sermon-browser'); ?>"></td>
                        </tr>
                    </table>
                    <input type="hidden" name="page" value="1">
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render JavaScript for dropdown filter (datepicker).
     *
     * @param bool $hideFilter Whether filter is hidden by default.
     * @param string $jsHide Hide/show JavaScript.
     * @return void
     */
    private static function renderDropdownJs(bool $hideFilter, string $jsHide): void
    {
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                $('#date').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: new Date(1970, 0, 1),
                    changeMonth: true,
                    changeYear: true
                });
                $('#enddate').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: new Date(1970, 0, 1),
                    changeMonth: true,
                    changeYear: true
                });
                <?php if ($hideFilter === true) : ?>
                    <?php echo $jsHide; ?>
                <?php endif; ?>
            });
        </script>
        <?php
    }
}
